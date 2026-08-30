<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AddMaciejTicketsSeeder extends Seeder
{
    /**
     * Adds two tickets for a real email address, so replying as an agent
     * in the admin panel triggers an actual, deliverable email notification
     * (useful for taking a real screenshot of the received email).
     * Does NOT wipe any existing tickets.
     */
    public function run(): void
    {
        $agent = User::where('role', '!=', User::ROLE_ADMIN)->where('is_active', true)->first();
        $agentName = $agent->name ?? 'Marek Nowak';
        $agentId = $agent->id ?? null;

        $customerName = 'Maciej Czapla';
        $customerEmail = 'maciejczapla00@gmail.com';

        // --- Ticket 1: escalated, waiting for an agent to reply ------------
        $createdAt = Carbon::now()->subHours(2);

        $ticket1 = new Ticket([
            'ticket_number' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
            'subject' => 'Problem z synchronizacją danych w aplikacji',
            'description' => "Od dzisiaj rano moje dane w aplikacji nie synchronizują się między urządzeniami. Na telefonie widzę stare dane, a na komputerze aktualne. Próbowałem wylogować się i zalogować ponownie, ale to nie pomogło.",
            'category' => 'technical',
            'priority' => 'high',
            'status' => Ticket::STATUS_ESCALATED,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => null,
            'escalation_reason' => 'ai_unable_to_resolve',
        ]);
        $ticket1->created_at = $createdAt;
        $ticket1->updated_at = $createdAt;
        if ($agentId) {
            $ticket1->assigned_to = $agentId;
        }
        $ticket1->save();

        $t = $createdAt->copy();
        Message::create([
            'ticket_id' => $ticket1->id, 'role' => Message::ROLE_SYSTEM, 'type' => Message::TYPE_STATUS_CHANGE,
            'content' => 'Zgłoszenie zostało przyjęte. Asystent AI jest gotowy do pomocy.',
            'sender_name' => 'System', 'created_at' => $t, 'updated_at' => $t,
        ]);

        $t = $t->copy()->addSeconds(5);
        Message::create([
            'ticket_id' => $ticket1->id, 'role' => Message::ROLE_USER, 'type' => Message::TYPE_CHAT,
            'content' => $ticket1->description, 'sender_name' => $customerName,
            'created_at' => $t, 'updated_at' => $t,
        ]);

        $t = $t->copy()->addSeconds(40);
        Message::create([
            'ticket_id' => $ticket1->id, 'role' => Message::ROLE_AI, 'type' => Message::TYPE_CHAT,
            'content' => 'Rozumiem problem z synchronizacją. Czy mógłby Pan sprawdzić, czy na obu urządzeniach jest zainstalowana najnowsza wersja aplikacji? Proszę też sprawdzić w Ustawieniach → Konto, czy synchronizacja automatyczna jest włączona.',
            'sender_name' => 'Asystent AI', 'ai_tokens_used' => 245, 'ai_model' => 'gpt-4o-mini',
            'created_at' => $t, 'updated_at' => $t,
        ]);

        $t = $t->copy()->addMinutes(3);
        Message::create([
            'ticket_id' => $ticket1->id, 'role' => Message::ROLE_SYSTEM, 'type' => Message::TYPE_ESCALATION,
            'content' => 'Asystent AI nie był w stanie rozwiązać problemu. Przekazuję do konsultanta.' . ($agentId ? " Przypisano do: {$agentName}." : ''),
            'sender_name' => 'System', 'created_at' => $t, 'updated_at' => $t,
        ]);

        // --- Ticket 2: in_progress, already has an agent reply -------------
        $createdAt2 = Carbon::now()->subDays(1);

        $ticket2 = new Ticket([
            'ticket_number' => 'TKT-' . strtoupper(substr(uniqid(), -8)),
            'subject' => 'Pytanie o fakturę za wrzesień',
            'description' => "Dzień dobry, nie mogę znaleźć faktury za wrzesień w panelu klienta. Czy mógłby ktoś mi ją przesłać na e-mail?",
            'category' => 'billing',
            'priority' => 'medium',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => null,
        ]);
        $ticket2->created_at = $createdAt2;
        $ticket2->updated_at = $createdAt2;
        if ($agentId) {
            $ticket2->assigned_to = $agentId;
        }
        $ticket2->save();

        $t2 = $createdAt2->copy();
        Message::create([
            'ticket_id' => $ticket2->id, 'role' => Message::ROLE_SYSTEM, 'type' => Message::TYPE_STATUS_CHANGE,
            'content' => 'Zgłoszenie zostało przyjęte. Asystent AI jest gotowy do pomocy.',
            'sender_name' => 'System', 'created_at' => $t2, 'updated_at' => $t2,
        ]);

        $t2 = $t2->copy()->addSeconds(5);
        Message::create([
            'ticket_id' => $ticket2->id, 'role' => Message::ROLE_USER, 'type' => Message::TYPE_CHAT,
            'content' => $ticket2->description, 'sender_name' => $customerName,
            'created_at' => $t2, 'updated_at' => $t2,
        ]);

        $t2 = $t2->copy()->addSeconds(30);
        Message::create([
            'ticket_id' => $ticket2->id, 'role' => Message::ROLE_AI, 'type' => Message::TYPE_CHAT,
            'content' => 'Dziękuję za zgłoszenie. Sprawdzam historię faktur powiązaną z Pana kontem, przekazuję sprawę do konsultanta, który prześle duplikat faktury.',
            'sender_name' => 'Asystent AI', 'ai_tokens_used' => 198, 'ai_model' => 'gpt-4o-mini',
            'created_at' => $t2, 'updated_at' => $t2,
        ]);

        $t2 = $t2->copy()->addMinutes(2);
        Message::create([
            'ticket_id' => $ticket2->id, 'role' => Message::ROLE_SYSTEM, 'type' => Message::TYPE_ESCALATION,
            'content' => 'Zgłoszenie zostało przekazane do konsultanta.' . ($agentId ? " Przypisano do: {$agentName}." : ''),
            'sender_name' => 'System', 'created_at' => $t2, 'updated_at' => $t2,
        ]);

        $t2 = $t2->copy()->addMinutes(20);
        Message::create([
            'ticket_id' => $ticket2->id, 'role' => Message::ROLE_AGENT, 'type' => Message::TYPE_CHAT,
            'content' => 'Dzień dobry, przesyłam duplikat faktury za wrzesień w załączniku do systemu rozliczeniowego. Proszę o potwierdzenie otrzymania.',
            'sender_name' => $agentName, 'sender_id' => $agentId,
            'created_at' => $t2, 'updated_at' => $t2,
        ]);

        $this->command?->info("Dodano 2 zgłoszenia dla {$customerName} ({$customerEmail}):");
        $this->command?->info("  - {$ticket1->ticket_number} (eskalowane, gotowe do odpowiedzi agenta)");
        $this->command?->info("  - {$ticket2->ticket_number} (w trakcie, ma już jedną odpowiedź agenta)");
    }
}
