<?php

namespace App\Services;

use App\Models\Instruction;
use App\Models\Message;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ChatGPTService
{
    private string $model;
    private int $maxTokens;
    private int $escalationThreshold;

    // Keywords that trigger escalation
    private array $escalationKeywords = [
        'prawnik', 'sąd', 'pozew', 'oszustwo', 'rezygnacja', 'anuluj umowę',
        'zwrot pieniędzy', 'nie działa', 'pilne', 'bardzo pilne', 'natychmiast',
        'lawyer', 'lawsuit', 'fraud', 'cancel', 'refund', 'urgent', 'emergency',
        'not working', 'broken', 'terrible', 'awful', 'incompetent',
        'rozwiązanie umowy', 'reklamacja', 'odszkodowanie',
    ];

    // Safety cap on how much knowledge-base text we inject into the prompt,
    // so a very large set of instructions can't blow out the context window.
    private const MAX_KNOWLEDGE_CHARS = 6000;

    public function __construct()
    {
        $this->model = config('openai.model', 'gpt-4o-mini');
        $this->maxTokens = (int) config('openai.max_tokens', 1000);
        $this->escalationThreshold = 3; // escalate after N failed AI attempts
    }

    /**
     * Process user message and return AI response
     */
    public function processMessage(Ticket $ticket, string $userMessage): array
    {
        try {
            // Check for escalation keywords first
            if ($this->containsEscalationKeywords($userMessage)) {
                return [
                    'success' => true,
                    'escalate' => true,
                    'reason' => 'escalation_keywords',
                    'message' => null,
                ];
            }

            // Build conversation history
            $messages = $this->buildConversationHistory($ticket, $userMessage);

            // Call OpenAI API
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.7,
                'functions' => $this->getFunctionDefinitions(),
                'function_call' => 'auto',
            ]);

            $choice = $response->choices[0];

            // Check if AI wants to escalate via function call
            if ($choice->finishReason === 'function_call') {
                $functionName = $choice->message->functionCall->name;
                if ($functionName === 'escalate_to_human') {
                    $args = json_decode($choice->message->functionCall->arguments, true);
                    return [
                        'success' => true,
                        'escalate' => true,
                        'reason' => $args['reason'] ?? 'ai_requested',
                        'message' => null,
                        'tokens_used' => $response->usage->totalTokens,
                    ];
                }
            }

            $aiContent = $choice->message->content;
            $tokensUsed = $response->usage->totalTokens;

            // Check if AI couldn't resolve after multiple attempts
            $aiAttempts = $ticket->messages()->where('role', Message::ROLE_AI)->count();
            $shouldEscalate = $this->shouldEscalate($aiContent, $aiAttempts);

            return [
                'success' => true,
                'escalate' => $shouldEscalate,
                'reason' => $shouldEscalate ? 'ai_unable_to_resolve' : null,
                'message' => $aiContent,
                'tokens_used' => $tokensUsed,
                'model' => $this->model,
            ];

        } catch (\Exception $e) {
            Log::error('ChatGPT API Error', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'escalate' => true,
                'reason' => 'api_error',
                'message' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze ticket and generate AI summary + categorization
     */
    public function analyzeTicket(Ticket $ticket): array
    {
        try {
            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Jesteś ekspertem analizy zgłoszeń helpdesk. Analizuj i odpowiadaj TYLKO w JSON.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Przeanalizuj zgłoszenie:\nTemat: {$ticket->subject}\nOpis: {$ticket->description}\n\nZwróć JSON: {\"summary\": \"...\", \"sentiment\": \"positive|neutral|negative|frustrated\", \"suggested_category\": \"...\", \"suggested_priority\": \"low|medium|high|urgent\", \"keywords\": []}",
                    ],
                ],
                'max_tokens' => 500,
                'temperature' => 0.3,
            ]);

            $content = $response->choices[0]->message->content;
            $data = json_decode($content, true);

            return [
                'success' => true,
                'data' => $data ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('Ticket analysis failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'data' => []];
        }
    }

    /**
     * Generate suggested response for agents
     */
    public function generateAgentSuggestion(Ticket $ticket): string
    {
        try {
            $history = $ticket->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn($m) => "{$m->role}: {$m->content}")
                ->join("\n");

            $response = OpenAI::chat()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Jesteś asystentem konsultanta. Zaproponuj profesjonalną odpowiedź na zgłoszenie klienta.' . $this->buildKnowledgeBaseBlock(),
                    ],
                    [
                        'role' => 'user',
                        'content' => "Historia rozmowy:\n{$history}\n\nZaproponuj odpowiedź konsultanta:",
                    ],
                ],
                'max_tokens' => 600,
                'temperature' => 0.5,
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Build conversation history for OpenAI API
     */
    private function buildConversationHistory(Ticket $ticket, string $newMessage): array
    {
        $systemPrompt = $this->buildSystemPrompt($ticket);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add ticket initial context
        $messages[] = [
            'role' => 'user',
            'content' => "Zgłoszenie: {$ticket->subject}\n{$ticket->description}",
        ];

        // Add conversation history (last 10 messages)
        $history = $ticket->messages()
            ->whereIn('role', [Message::ROLE_USER, Message::ROLE_AI])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role === Message::ROLE_AI ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $newMessage];

        return $messages;
    }

    /**
     * Build system prompt based on ticket context
     */
    private function buildSystemPrompt(Ticket $ticket): string
    {
        $basePrompt = <<<PROMPT
Jesteś pomocnym asystentem AI systemu ticketowego firmy outsourcingowej. Twoje zadania:

1. Pomagaj klientom rozwiązywać problemy krok po kroku
2. Bądź uprzejmy, profesjonalny i rzeczowy
3. Jeśli problem wymaga dostępu do systemu lub danych klienta - poproś o potrzebne informacje
4. Jeśli NIE możesz rozwiązać problemu po 2-3 próbach - użyj funkcji escalate_to_human
5. Jeśli klient jest bardzo sfrustrowany lub wymaga specjalistycznej pomocy - eskaluj natychmiast

Kategoria zgłoszenia: {$ticket->category}
Priorytet: {$ticket->priority}

WAŻNE: Odpowiadaj w języku klienta (polskim lub angielskim).
Nie ujawniaj, że jesteś AI, chyba że klient zapyta wprost.
Jeśli klient pyta, czy jest AI - potwierdź i zaoferuj połączenie z konsultantem.
PROMPT;

        return $basePrompt . $this->buildKnowledgeBaseBlock();
    }

    /**
     * Fetch active knowledge-base entries (added by staff in the admin panel)
     * and format them as an appendix to the system prompt, so the AI answers
     * according to the company's own procedures/policies instead of guessing.
     */
    private function buildKnowledgeBaseBlock(): string
    {
        try {
            $instructions = Instruction::active()->orderBy('created_at', 'desc')->get();
        } catch (\Exception $e) {
            Log::warning('Failed to load knowledge base instructions', ['error' => $e->getMessage()]);
            return '';
        }

        if ($instructions->isEmpty()) {
            return '';
        }

        $block = "\n\n---\nWEWNĘTRZNA BAZA WIEDZY FIRMY (korzystaj z tego przy odpowiadaniu, ma pierwszeństwo przed ogólną wiedzą):\n\n";
        $usedChars = mb_strlen($block);

        foreach ($instructions as $instruction) {
            $entry = "### {$instruction->title}\n{$instruction->content}\n\n";

            if ($usedChars + mb_strlen($entry) > self::MAX_KNOWLEDGE_CHARS) {
                break;
            }

            $block .= $entry;
            $usedChars += mb_strlen($entry);
        }

        return $block;
    }

    /**
     * OpenAI function definitions for structured AI actions
     */
    private function getFunctionDefinitions(): array
    {
        return [
            [
                'name' => 'escalate_to_human',
                'description' => 'Escalate the ticket to a human consultant when AI cannot resolve the issue',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reason' => [
                            'type' => 'string',
                            'enum' => ['complex_technical', 'billing_issue', 'legal_matter', 'customer_frustrated', 'requires_system_access', 'ai_unable_to_resolve'],
                            'description' => 'Reason for escalation',
                        ],
                        'summary' => [
                            'type' => 'string',
                            'description' => 'Brief summary of the issue for the human agent',
                        ],
                    ],
                    'required' => ['reason'],
                ],
            ],
        ];
    }

    /**
     * Check if message contains escalation keywords
     */
    private function containsEscalationKeywords(string $message): bool
    {
        $messageLower = mb_strtolower($message);
        foreach ($this->escalationKeywords as $keyword) {
            if (str_contains($messageLower, mb_strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determine if AI should escalate based on content and attempt count
     */
    private function shouldEscalate(string $content, int $attemptCount): bool
    {
        if ($attemptCount >= $this->escalationThreshold) {
            return true;
        }

        $escalationPhrases = [
            'nie jestem w stanie', 'nie mogę pomóc', 'wymaga interwencji',
            'unable to help', 'cannot resolve', 'need human assistance',
            'przekazuję do konsultanta', 'escalating to',
        ];

        $contentLower = mb_strtolower($content);
        foreach ($escalationPhrases as $phrase) {
            if (str_contains($contentLower, $phrase)) {
                return true;
            }
        }

        return false;
    }
}

