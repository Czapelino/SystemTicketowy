<?php

namespace App\Services;

use App\Mail\AgentReplyNotification;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketService
{
    public function __construct(
        private ChatGPTService $chatGPT
    ) {}

    /**
     * Create a new ticket with AI analysis
     */
    public function createTicket(array $data): Ticket
    {
        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'category' => $data['category'] ?? 'general',
            'priority' => $data['priority'] ?? Ticket::PRIORITY_MEDIUM,
            'customer_name' => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'status' => Ticket::STATUS_AI_HANDLING,
        ]);

        // Run AI analysis in background (or sync for simplicity)
        $this->runAIAnalysis($ticket);

        // Add initial system message
        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_SYSTEM,
            'type' => Message::TYPE_STATUS_CHANGE,
            'content' => 'Zgłoszenie zostało przyjęte. Asystent AI jest gotowy do pomocy.',
            'sender_name' => 'System',
        ]);

        // Show the customer's own submitted description as the first chat
        // bubble, so the conversation reads naturally from their own message
        // onward instead of starting with the AI's reply out of nowhere.
        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_USER,
            'type' => Message::TYPE_CHAT,
            'content' => $ticket->description,
            'sender_name' => $ticket->customer_name,
        ]);

        // Immediately let AI respond to the initial description,
        // so the customer doesn't have to send an extra message to get a reply.
        $this->generateInitialAIResponse($ticket);

        return $ticket;
    }

    /**
     * Generate the AI's first response based on the ticket description,
     * right after the ticket is created.
     */
    private function generateInitialAIResponse(Ticket $ticket): void
    {
        try {
            $result = $this->chatGPT->processMessage($ticket, $ticket->description);

            if (!$result['success']) {
                $this->escalateTicket($ticket, 'api_error');
                return;
            }

            if ($result['escalate']) {
                $this->escalateTicket($ticket, $result['reason'] ?? 'unknown');
                return;
            }

            Message::create([
                'ticket_id' => $ticket->id,
                'role' => Message::ROLE_AI,
                'type' => Message::TYPE_CHAT,
                'content' => $result['message'],
                'sender_name' => 'Asystent AI',
                'ai_tokens_used' => $result['tokens_used'] ?? 0,
                'ai_model' => $result['model'] ?? config('openai.model'),
            ]);
        } catch (\Exception $e) {
            Log::warning('Initial AI response failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle incoming message from customer
     */
    public function handleCustomerMessage(Ticket $ticket, string $message): array
    {
        // Save customer message
        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_USER,
            'type' => Message::TYPE_CHAT,
            'content' => $message,
            'sender_name' => $ticket->customer_name,
        ]);

        // Don't process with AI if escalated or has agent
        if ($ticket->status === Ticket::STATUS_ESCALATED
            || $ticket->status === Ticket::STATUS_IN_PROGRESS) {
            return [
                'escalated' => true,
                'message' => null,
            ];
        }

        // Process with AI
        $result = $this->chatGPT->processMessage($ticket, $message);

        if (!$result['success']) {
            // API error - escalate
            $this->escalateTicket($ticket, 'api_error');
            return ['escalated' => true, 'message' => null];
        }

        if ($result['escalate']) {
            $this->escalateTicket($ticket, $result['reason'] ?? 'unknown');
            return ['escalated' => true, 'message' => null];
        }

        // Save AI response
        $aiMessage = Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_AI,
            'type' => Message::TYPE_CHAT,
            'content' => $result['message'],
            'sender_name' => 'Asystent AI',
            'ai_tokens_used' => $result['tokens_used'] ?? 0,
            'ai_model' => $result['model'] ?? config('openai.model'),
        ]);

        return [
            'escalated' => false,
            'message' => $aiMessage,
        ];
    }

    /**
     * Handle agent message on ticket
     */
    public function handleAgentMessage(Ticket $ticket, User $agent, string $message, bool $isInternal = false): Message
    {
        $agentMessage = Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_AGENT,
            'type' => $isInternal ? Message::TYPE_INTERNAL_NOTE : Message::TYPE_CHAT,
            'content' => $message,
            'sender_name' => $agent->name,
            'sender_id' => $agent->id,
        ]);

        // Notify the customer by email, but never for internal notes
        // (those are only visible to agents, not the customer).
        if (!$isInternal) {
            $this->notifyCustomerOfAgentReply($ticket, $agentMessage);
        }

        return $agentMessage;
    }

    /**
     * Email the customer that a consultant has replied to their ticket.
     */
    private function notifyCustomerOfAgentReply(Ticket $ticket, Message $message): void
    {
        try {
            Mail::to($ticket->customer_email)->send(new AgentReplyNotification($ticket, $message));
        } catch (\Exception $e) {
            Log::warning('Agent reply email notification failed', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Escalate ticket to human consultant
     */
    public function escalateTicket(Ticket $ticket, string $reason): void
    {
        $ticket->update([
            'status' => Ticket::STATUS_ESCALATED,
            'escalation_reason' => $reason,
        ]);

        // Auto-assign to available agent
        $agent = $this->findAvailableAgent();
        if ($agent) {
            $ticket->update(['assigned_to' => $agent->id]);
        }

        // Create escalation message
        $escalationMessages = [
            'escalation_keywords' => 'Wykryto słowa kluczowe wymagające interwencji konsultanta.',
            'ai_unable_to_resolve' => 'Asystent AI nie był w stanie rozwiązać problemu. Przekazuję do konsultanta.',
            'customer_frustrated' => 'Klient wyraża frustrację. Przekazuję do konsultanta.',
            'api_error' => 'Wystąpił błąd techniczny. Przekazuję do konsultanta.',
            'ai_requested' => 'Asystent AI ocenił, że problem wymaga pomocy konsultanta.',
            'complex_technical' => 'Problem techniczny wymaga specjalistycznej wiedzy konsultanta.',
            'billing_issue' => 'Problem z rozliczeniami wymaga interwencji konsultanta.',
        ];

        $message = $escalationMessages[$reason] ?? 'Zgłoszenie zostało przekazane do konsultanta.';

        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_SYSTEM,
            'type' => Message::TYPE_ESCALATION,
            'content' => $message . ($agent ? " Przypisano do: {$agent->name}." : ''),
            'sender_name' => 'System',
            'metadata' => ['escalation_reason' => $reason, 'assigned_agent' => $agent?->name],
        ]);

        Log::info('Ticket escalated', [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'reason' => $reason,
            'assigned_to' => $agent?->name,
        ]);
    }

    /**
     * Resolve ticket
     */
    public function resolveTicket(Ticket $ticket, User $agent, string $notes): void
    {
        $ticket->update([
            'status' => Ticket::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);

        Message::create([
            'ticket_id' => $ticket->id,
            'role' => Message::ROLE_SYSTEM,
            'type' => Message::TYPE_STATUS_CHANGE,
            'content' => "Zgłoszenie zostało rozwiązane przez {$agent->name}. Notatka: {$notes}",
            'sender_name' => 'System',
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'total' => Ticket::count(),
            'open' => Ticket::where('status', Ticket::STATUS_OPEN)->count(),
            'ai_handling' => Ticket::where('status', Ticket::STATUS_AI_HANDLING)->count(),
            'escalated' => Ticket::where('status', Ticket::STATUS_ESCALATED)->count(),
            'in_progress' => Ticket::where('status', Ticket::STATUS_IN_PROGRESS)->count(),
            'resolved_today' => Ticket::where('status', Ticket::STATUS_RESOLVED)
                ->whereDate('resolved_at', today())
                ->count(),
            'avg_resolution_time' => $this->getAvgResolutionTime(),
            'ai_resolution_rate' => $this->getAIResolutionRate(),
            'by_category' => $this->getByCategory(),
            'by_priority' => $this->getByPriority(),
        ];
    }

    private function runAIAnalysis(Ticket $ticket): void
    {
        try {
            $result = $this->chatGPT->analyzeTicket($ticket);
            if ($result['success'] && !empty($result['data'])) {
                $data = $result['data'];
                $ticket->update([
                    'ai_summary' => $data['summary'] ?? null,
                    'ai_sentiment' => $data['sentiment'] ?? null,
                    'ai_suggested_category' => $data['suggested_category'] ?? null,
                    'tags' => $data['keywords'] ?? [],
                    'priority' => $data['suggested_priority'] ?? $ticket->priority,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('AI analysis failed', ['ticket_id' => $ticket->id]);
        }
    }

    private function findAvailableAgent(): ?User
    {
        return User::where('role', '!=', User::ROLE_ADMIN)
            ->where('is_active', true)
            ->orderByRaw(['assigned_tickets_count' => 1]) // Least loaded
            ->first();
    }

    private function getAvgResolutionTime(): string
    {
        $resolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        if ($resolved->isEmpty()) return '0h';

        $avgMinutes = $resolved->avg(function($t) {
            return $t->created_at->diffInMinutes($t->resolved_at);
        });

        if ($avgMinutes < 60) return round($avgMinutes) . 'min';
        if ($avgMinutes < 1440) return round($avgMinutes / 60, 1) . 'h';
        return round($avgMinutes / 1440, 1) . 'd';
    }

    private function getAIResolutionRate(): float
    {
        $total = Ticket::where('status', Ticket::STATUS_RESOLVED)->count();
        if ($total === 0) return 0;

        // Tickets resolved without escalation = AI resolved
        $aiResolved = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereNull('escalation_reason')
            ->count();

        return round(($aiResolved / $total) * 100, 1);
    }

    private function getByCategory(): array
    {
        return Ticket::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => ['_id' => '$category', 'count' => ['$sum' => 1]]],
                ['$sort' => ['count' => -1]],
                ['$limit' => 5],
            ]);
        })->toArray();
    }

    private function getByPriority(): array
    {
        return Ticket::raw(function($collection) {
            return $collection->aggregate([
                ['$group' => ['_id' => '$priority', 'count' => ['$sum' => 1]]],
            ]);
        })->toArray();
    }
}
