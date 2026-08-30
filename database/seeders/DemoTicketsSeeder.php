<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoTicketsSeeder extends Seeder
{
    /**
     * Wipes all existing tickets/messages and replaces them with a
     * realistic, professional-looking demo dataset for screenshots.
     */
    public function run(): void
    {
        // --- Clean slate -----------------------------------------------
        Message::truncate();
        Ticket::truncate();

        $agent = User::where('role', '!=', User::ROLE_ADMIN)->where('is_active', true)->first();
        $agentName = $agent->name ?? 'Marek Nowak';
        $agentId = $agent->id ?? null;

        // --- Demo tickets ------------------------------------------------
        $tickets = [
            [
                'subject' => 'Nie mogę zalogować się do systemu CRM',
                'description' => "Od wczoraj rano nie mogę zalogować się do panelu CRM. Wpisuję prawidłowe hasło, ale system zwraca błąd \"Nieprawidłowe dane logowania\". Próbowałem zresetować hasło, ale link aktywacyjny z e-maila nie działa.",
                'customer_name' => 'Anna Kowalczyk',
                'customer_email' => 'a.kowalczyk@example.com',
                'category' => 'account',
                'priority' => 'high',
                'status' => 'resolved',
                'created_days_ago' => 6,
                'agent' => true,
                'resolution' => 'Konto zostało zablokowane po przekroczeniu limitu prób logowania. Ręcznie odblokowano konto i wysłano nowy link do resetu hasła. Klientka potwierdziła poprawne zalogowanie.',
            ],
            [
                'subject' => 'Błąd przy generowaniu faktury zbiorczej',
                'description' => "Przy próbie wygenerowania faktury zbiorczej za ostatni kwartał system zwraca błąd 500. Pojedyncze faktury generują się bez problemu. Potrzebuję tego pilnie na dzisiejsze zamknięcie miesiąca.",
                'customer_name' => 'Piotr Zieliński',
                'customer_email' => 'p.zielinski@firma-abc.pl',
                'category' => 'billing',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'created_days_ago' => 1,
                'agent' => true,
            ],
            [
                'subject' => 'Aplikacja mobilna zawiesza się przy synchronizacji',
                'description' => "Aplikacja mobilna na Androidzie zawiesza się za każdym razem, gdy próbuję zsynchronizować dane offline. Muszę wymuszać zamknięcie aplikacji. Wersja aplikacji: 2.4.1, telefon: Samsung Galaxy A54.",
                'customer_name' => 'Katarzyna Wójcik',
                'customer_email' => 'k.wojcik@example.com',
                'category' => 'technical',
                'priority' => 'medium',
                'status' => 'escalated',
                'created_days_ago' => 2,
                'escalation_reason' => 'complex_technical',
            ],
            [
                'subject' => 'Prośba o zmianę adresu e-mail przypisanego do konta',
                'description' => "Zmieniłem pracodawcę i chciałbym zaktualizować adres e-mail przypisany do mojego konta z jan.stary@firma.pl na jan.nowak@nowafirma.pl. Jak mogę to zrobić?",
                'customer_name' => 'Jan Nowak',
                'customer_email' => 'jan.nowak@nowafirma.pl',
                'category' => 'account',
                'priority' => 'low',
                'status' => 'resolved',
                'created_days_ago' => 9,
                'resolution' => 'Zaktualizowano adres e-mail w systemie po weryfikacji tożsamości klienta. Wysłano potwierdzenie zmiany na oba adresy.',
            ],
            [
                'subject' => 'Reklamacja - podwójne obciążenie karty',
                'description' => "Na wyciągu z karty widzę dwa identyczne obciążenia za abonament z tego samego dnia, każde na kwotę 249 zł. Proszę o wyjaśnienie i zwrot nadpłaty.",
                'customer_name' => 'Magdalena Lis',
                'customer_email' => 'm.lis@example.com',
                'category' => 'complaint',
                'priority' => 'urgent',
                'status' => 'escalated',
                'created_days_ago' => 0,
                'escalation_reason' => 'billing_issue',
                'hours_ago' => 3,
            ],
            [
                'subject' => 'Jak wyeksportować dane do pliku Excel?',
                'description' => "Szukam opcji eksportu raportu miesięcznego do formatu Excel, ale widzę tylko eksport do PDF. Czy taka funkcja jest dostępna?",
                'customer_name' => 'Tomasz Adamski',
                'customer_email' => 't.adamski@example.com',
                'category' => 'general',
                'priority' => 'low',
                'status' => 'resolved',
                'created_days_ago' => 12,
                'resolution' => 'Poinformowano klienta o lokalizacji opcji eksportu do Excela (Raporty → Eksportuj → XLSX), która została dodana w ostatniej aktualizacji systemu.',
            ],
            [
                'subject' => 'Powolne ładowanie panelu klienta',
                'description' => "Panel klienta ładuje się bardzo wolno od kilku dni, szczególnie zakładka z historią zamówień. Czasami trwa to nawet 30 sekund.",
                'customer_name' => 'Ewa Kaczmarek',
                'customer_email' => 'e.kaczmarek@example.com',
                'category' => 'technical',
                'priority' => 'medium',
                'status' => 'ai_handling',
                'created_days_ago' => 0,
                'hours_ago' => 1,
            ],
            [
                'subject' => 'Prośba o wystawienie duplikatu faktury',
                'description' => "Zgubiłem fakturę za wrzesień, potrzebuję duplikatu do rozliczenia księgowego. Numer zamówienia: ZAM-33021.",
                'customer_name' => 'Robert Michalski',
                'customer_email' => 'r.michalski@firma-xyz.pl',
                'category' => 'billing',
                'priority' => 'low',
                'status' => 'resolved',
                'created_days_ago' => 15,
                'resolution' => 'Wystawiono i wysłano duplikat faktury na adres e-mail klienta.',
            ],
            [
                'subject' => 'Integracja API zwraca błąd 401',
                'description' => "Nasza integracja z Waszym API przestała działać dziś rano — wszystkie zapytania zwracają błąd 401 Unauthorized, mimo że klucz API nie był zmieniany. Czy coś się zmieniło po Waszej stronie?",
                'customer_name' => 'Michał Wiśniewski',
                'customer_email' => 'm.wisniewski@techcorp.pl',
                'category' => 'technical',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'created_days_ago' => 0,
                'hours_ago' => 5,
                'agent' => true,
            ],
            [
                'subject' => 'Pytanie o dostępność wsparcia w weekendy',
                'description' => "Czy dział techniczny jest dostępny w weekendy? Planujemy wdrożenie w sobotę i chcielibyśmy mieć pewność, że w razie problemów uzyskamy pomoc.",
                'customer_name' => 'Agnieszka Sikora',
                'customer_email' => 'a.sikora@example.com',
                'category' => 'general',
                'priority' => 'medium',
                'status' => 'resolved',
                'created_days_ago' => 4,
                'resolution' => 'Poinformowano klientkę o godzinach pracy działu technicznego oraz o możliwości zgłoszenia awarii krytycznej poza godzinami pracy przez formularz priorytetowy.',
            ],
        ];

        foreach ($tickets as $t) {
            $createdAt = isset($t['hours_ago'])
                ? Carbon::now()->subHours($t['hours_ago'])
                : Carbon::now()->subDays($t['created_days_ago']);

            $ticket = new Ticket([
                'ticket_number' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
                'subject' => $t['subject'],
                'description' => $t['description'],
                'category' => $t['category'],
                'priority' => $t['priority'],
                'status' => $t['status'],
                'customer_name' => $t['customer_name'],
                'customer_email' => $t['customer_email'],
                'customer_phone' => null,
                'escalation_reason' => $t['escalation_reason'] ?? null,
            ]);
            $ticket->created_at = $createdAt;
            $ticket->updated_at = $createdAt;

            if (in_array($t['status'], ['in_progress', 'escalated', 'resolved']) && ($t['agent'] ?? false) && $agentId) {
                $ticket->assigned_to = $agentId;
            }

            if ($t['status'] === 'resolved') {
                $ticket->resolved_at = $createdAt->copy()->addHours(rand(2, 30));
                $ticket->resolution_notes = $t['resolution'] ?? 'Problem rozwiązany.';
            }

            $ticket->save();

            // --- Message history for this ticket -------------------------
            $msgTime = $createdAt->copy();

            Message::create([
                'ticket_id' => $ticket->id,
                'role' => Message::ROLE_SYSTEM,
                'type' => Message::TYPE_STATUS_CHANGE,
                'content' => 'Zgłoszenie zostało przyjęte. Asystent AI jest gotowy do pomocy.',
                'sender_name' => 'System',
                'created_at' => $msgTime,
                'updated_at' => $msgTime,
            ]);

            $msgTime = $msgTime->copy()->addSeconds(5);
            Message::create([
                'ticket_id' => $ticket->id,
                'role' => Message::ROLE_USER,
                'type' => Message::TYPE_CHAT,
                'content' => $t['description'],
                'sender_name' => $t['customer_name'],
                'created_at' => $msgTime,
                'updated_at' => $msgTime,
            ]);

            $msgTime = $msgTime->copy()->addSeconds(rand(20, 90));
            Message::create([
                'ticket_id' => $ticket->id,
                'role' => Message::ROLE_AI,
                'type' => Message::TYPE_CHAT,
                'content' => $this->fakeAiReply($t['category']),
                'sender_name' => 'Asystent AI',
                'ai_tokens_used' => rand(180, 420),
                'ai_model' => 'gpt-4o-mini',
                'created_at' => $msgTime,
                'updated_at' => $msgTime,
            ]);

            if (in_array($t['status'], ['escalated', 'in_progress', 'resolved'])) {
                $msgTime = $msgTime->copy()->addMinutes(rand(2, 15));
                Message::create([
                    'ticket_id' => $ticket->id,
                    'role' => Message::ROLE_SYSTEM,
                    'type' => Message::TYPE_ESCALATION,
                    'content' => 'Zgłoszenie zostało przekazane do konsultanta.' . ($agentId ? " Przypisano do: {$agentName}." : ''),
                    'sender_name' => 'System',
                    'created_at' => $msgTime,
                    'updated_at' => $msgTime,
                ]);
            }

            if (in_array($t['status'], ['in_progress', 'resolved'])) {
                $msgTime = $msgTime->copy()->addMinutes(rand(5, 40));
                Message::create([
                    'ticket_id' => $ticket->id,
                    'role' => Message::ROLE_AGENT,
                    'type' => Message::TYPE_CHAT,
                    'content' => $this->fakeAgentReply($t['category']),
                    'sender_name' => $agentName,
                    'sender_id' => $agentId,
                    'created_at' => $msgTime,
                    'updated_at' => $msgTime,
                ]);
            }

            if ($t['status'] === 'resolved') {
                $msgTime = $ticket->resolved_at;
                Message::create([
                    'ticket_id' => $ticket->id,
                    'role' => Message::ROLE_SYSTEM,
                    'type' => Message::TYPE_STATUS_CHANGE,
                    'content' => "Zgłoszenie zostało rozwiązane przez {$agentName}. Notatka: {$ticket->resolution_notes}",
                    'sender_name' => 'System',
                    'created_at' => $msgTime,
                    'updated_at' => $msgTime,
                ]);
            }
        }

        $this->command?->info('Zasiano ' . count($tickets) . ' fikcyjnych zgłoszeń demo.');
    }

    private function fakeAiReply(string $category): string
    {
        $replies = [
            'technical' => 'Dziękuję za zgłoszenie. Rozumiem, że napotkał(a) Pan/Pani problem techniczny. Czy mógłby Pan/Pani podać, jaką przeglądarkę/system operacyjny Pan/Pani używa oraz czy problem występuje za każdym razem?',
            'billing' => 'Dziękuję za kontakt w sprawie rozliczenia. Sprawdzam historię płatności powiązaną z Pana/Pani kontem. Czy może Pan/Pani podać numer zamówienia lub datę transakcji, której dotyczy zgłoszenie?',
            'account' => 'Rozumiem, że potrzebuje Pan/Pani pomocy z kontem. Ze względów bezpieczeństwa, czy może Pan/Pani potwierdzić adres e-mail przypisany do konta?',
            'general' => 'Dziękuję za pytanie. Postaram się pomóc najlepiej jak potrafię. Czy może Pan/Pani doprecyzować, o którą funkcję/sekcję systemu dokładnie chodzi?',
            'complaint' => 'Bardzo przepraszam za niedogodności. Traktuję to zgłoszenie priorytetowo. Przekazuję sprawę do weryfikacji przez konsultanta, który skontaktuje się w tej sprawie.',
            'other' => 'Dziękuję za zgłoszenie. Analizuję przekazane informacje i postaram się jak najszybciej pomóc.',
        ];

        return $replies[$category] ?? $replies['general'];
    }

    private function fakeAgentReply(string $category): string
    {
        $replies = [
            'technical' => 'Dzień dobry, przeanalizowałem zgłoszenie. Sprawdziłem logi systemowe i zidentyfikowałem przyczynę problemu. Wprowadzam poprawkę, proszę spróbować ponownie za kilka minut.',
            'billing' => 'Dzień dobry, zweryfikowałem historię płatności na koncie. Potwierdzam nieprawidłowość i uruchamiam procedurę korekty.',
            'account' => 'Dzień dobry, zweryfikowałem tożsamość i wprowadziłem wymaganą zmianę na koncie.',
            'general' => 'Dzień dobry, dziękuję za cierpliwość. Oto szczegółowa odpowiedź na Pana/Pani pytanie.',
            'complaint' => 'Dzień dobry, jeszcze raz przepraszam za zaistniałą sytuację. Wyjaśniłem sprawę i wdrażam odpowiednie działania naprawcze.',
            'other' => 'Dzień dobry, przejąłem Pana/Pani zgłoszenie i zajmuję się sprawą.',
        ];

        return $replies[$category] ?? $replies['general'];
    }
}
