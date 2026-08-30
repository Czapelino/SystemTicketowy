<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@helpdesk.pl',
            'password' => Hash::make('admin123'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'department' => 'IT',
        ]);

        // Create supervisor
        User::create([
            'name' => 'Anna Kowalska',
            'email' => 'anna@helpdesk.pl',
            'password' => Hash::make('agent123'),
            'role' => User::ROLE_SUPERVISOR,
            'is_active' => true,
            'department' => 'Support',
        ]);

        // Create agents
        $agents = [
            ['name' => 'Marek Nowak', 'email' => 'marek@helpdesk.pl'],
            ['name' => 'Katarzyna Wiśniewska', 'email' => 'kasia@helpdesk.pl'],
            ['name' => 'Tomasz Zając', 'email' => 'tomek@helpdesk.pl'],
        ];

        foreach ($agents as $agentData) {
            User::create([
                'name' => $agentData['name'],
                'email' => $agentData['email'],
                'password' => Hash::make('agent123'),
                'role' => User::ROLE_AGENT,
                'is_active' => true,
                'department' => 'Support',
            ]);
        }

        // Create sample tickets
        $this->createSampleTickets();

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('Admin: admin@helpdesk.pl / admin123');
        $this->command->info('Agent: marek@helpdesk.pl / agent123');
    }

    private function createSampleTickets(): void
    {
        // Escalated ticket (needs agent attention)
        $ticket1 = Ticket::create([
            'subject' => 'Nie mogę zalogować się do systemu ERP',
            'description' => 'Od wczoraj nie mogę zalogować się do systemu. Pojawia się błąd "Nieprawidłowe hasło" mimo że hasło jest poprawne.',
            'status' => Ticket::STATUS_ESCALATED,
            'priority' => Ticket::PRIORITY_HIGH,
            'category' => 'technical',
            'customer_name' => 'Jan Kowalski',
            'customer_email' => 'jan.kowalski@firma.pl',
            'customer_phone' => '+48 600 123 456',
            'ai_sentiment' => 'frustrated',
            'ai_summary' => 'Klient ma problem z logowaniem do systemu ERP. Problem techniczny, prawdopodobnie związany z blokadą konta.',
            'escalation_reason' => 'ai_unable_to_resolve',
            'tags' => ['login', 'erp', 'hasło', 'blokada'],
        ]);

        Message::create(['ticket_id' => $ticket1->id, 'role' => 'system', 'type' => 'status_change', 'content' => 'Zgłoszenie przyjęte.', 'sender_name' => 'System']);
        Message::create(['ticket_id' => $ticket1->id, 'role' => 'user', 'content' => 'Nie mogę się zalogować od wczoraj', 'sender_name' => 'Jan Kowalski']);
        Message::create(['ticket_id' => $ticket1->id, 'role' => 'assistant', 'content' => 'Rozumiem. Czy próbowałeś zresetować hasło przez opcję "Zapomniałem hasła"?', 'sender_name' => 'Asystent AI', 'ai_tokens_used' => 45]);
        Message::create(['ticket_id' => $ticket1->id, 'role' => 'user', 'content' => 'Tak, próbowałem. Link resetowania też nie działa!', 'sender_name' => 'Jan Kowalski']);
        Message::create(['ticket_id' => $ticket1->id, 'role' => 'assistant', 'content' => 'Problem może być poważniejszy. Przekazuję Cię do konsultanta, który pomoże z dostępem administracyjnym.', 'sender_name' => 'Asystent AI', 'ai_tokens_used' => 67]);
        Message::create(['ticket_id' => $ticket1->id, 'role' => 'system', 'type' => 'escalation', 'content' => 'Zgłoszenie eskalowane do konsultanta z powodu: ai_unable_to_resolve', 'sender_name' => 'System']);

        // AI handling ticket
        $ticket2 = Ticket::create([
            'subject' => 'Jak wygenerować raport miesięczny?',
            'description' => 'Potrzebuję wygenerować raport sprzedaży za ubiegły miesiąc. Nie wiem jak to zrobić.',
            'status' => Ticket::STATUS_AI_HANDLING,
            'priority' => Ticket::PRIORITY_LOW,
            'category' => 'general',
            'customer_name' => 'Maria Nowak',
            'customer_email' => 'maria@firma2.pl',
            'ai_sentiment' => 'neutral',
            'ai_summary' => 'Klient pyta o generowanie raportów miesięcznych.',
            'tags' => ['raport', 'sprzedaż', 'instrukcja'],
        ]);

        Message::create(['ticket_id' => $ticket2->id, 'role' => 'user', 'content' => 'Jak mogę wygenerować raport miesięczny?', 'sender_name' => 'Maria Nowak']);
        Message::create(['ticket_id' => $ticket2->id, 'role' => 'assistant', 'content' => "Oczywiście! Aby wygenerować raport miesięczny:\n1. Wejdź do zakładki 'Raporty'\n2. Wybierz 'Raport sprzedaży'\n3. Ustaw zakres dat\n4. Kliknij 'Generuj'", 'sender_name' => 'Asystent AI', 'ai_tokens_used' => 89]);

        // Resolved ticket
        $ticket3 = Ticket::create([
            'subject' => 'Problem z fakturą VAT',
            'description' => 'Na fakturze jest błędny NIP mojej firmy.',
            'status' => Ticket::STATUS_RESOLVED,
            'priority' => Ticket::PRIORITY_MEDIUM,
            'category' => 'billing',
            'customer_name' => 'Piotr Wiśniewski',
            'customer_email' => 'piotr@firma3.pl',
            'ai_sentiment' => 'neutral',
            'escalation_reason' => 'billing_issue',
            'resolution_notes' => 'Poprawiono NIP na fakturze i wysłano korektę do klienta.',
            'resolved_at' => now()->subHours(2),
        ]);

        $this->command->info("Created {$ticket3->ticket_number}");
    }
}
