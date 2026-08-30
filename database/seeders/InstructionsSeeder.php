<?php

namespace Database\Seeders;

use App\Models\Instruction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InstructionsSeeder extends Seeder
{
    /**
     * Adds example knowledge-base entries matching those described in the
     * thesis (chapter 2.8), for a realistic screenshot of the admin panel.
     * Does NOT wipe any existing entries.
     */
    public function run(): void
    {
        $entries = [
            [
                'title' => 'Godziny pracy działu technicznego',
                'content' => "Dział techniczny pracuje od poniedziałku do piątku w godzinach 8:00–17:00. Zgłoszenia przesłane poza tymi godzinami są przyjmowane automatycznie przez system, ale obsługiwane przez konsultanta dopiero następnego dnia roboczego. W przypadku awarii krytycznej (całkowity brak dostępu do systemu) zgłoszenie należy oznaczyć priorytetem \"Pilny\" — takie zgłoszenia są obsługiwane w pierwszej kolejności również poza standardowymi godzinami pracy.",
                'created_by' => 'Administrator',
                'is_active' => true,
                'days_ago' => 10,
            ],
            [
                'title' => 'Procedura zwrotu płatności',
                'content' => "Zwrot płatności następuje w ciągu 14 dni roboczych na konto, z którego dokonano płatności. Warunkiem rozpoczęcia procedury zwrotu jest podanie przez klienta numeru zamówienia oraz przyczyny zwrotu. Zwroty za usługi subskrypcyjne realizowane są proporcjonalnie do niewykorzystanego okresu rozliczeniowego. Przy podwójnym obciążeniu karty (duplikat transakcji) zwrot całej nadpłaconej kwoty następuje automatycznie po weryfikacji przez dział księgowości, zwykle w ciągu 3–5 dni roboczych.",
                'created_by' => 'Administrator',
                'is_active' => true,
                'days_ago' => 8,
            ],
            [
                'title' => 'Znane problemy aplikacji mobilnej (Android)',
                'content' => "Wersja 2.4.1 aplikacji na Androida ma znany problem z zawieszaniem się podczas synchronizacji danych offline na niektórych modelach urządzeń Samsung. Zespół developerski pracuje nad poprawką w wersji 2.4.2. Tymczasowe obejście: wyłączyć synchronizację w tle w Ustawieniach → Konto → Synchronizacja, a następnie wykonywać synchronizację ręcznie. Jeśli klient zgłasza ten problem, poinformuj go o znanym błędzie i podaj obejście zamiast eskalować zgłoszenie do zespołu technicznego.",
                'created_by' => 'Marek Nowak',
                'is_active' => true,
                'days_ago' => 3,
            ],
            [
                'title' => 'Stara procedura resetu hasła (nieaktualna)',
                'content' => "UWAGA: ta instrukcja jest nieaktualna od czasu wdrożenia nowego systemu logowania. Reset hasła przez panel administratora został wycofany. Klienci resetują hasło wyłącznie przez link wysyłany automatycznie na e-mail po kliknięciu \"Nie pamiętam hasła\" na stronie logowania.",
                'created_by' => 'Administrator',
                'is_active' => false,
                'days_ago' => 60,
            ],
        ];

        foreach ($entries as $e) {
            $createdAt = Carbon::now()->subDays($e['days_ago']);

            $instruction = new Instruction([
                'title' => $e['title'],
                'content' => $e['content'],
                'is_active' => $e['is_active'],
                'created_by' => $e['created_by'],
            ]);
            $instruction->created_at = $createdAt;
            $instruction->updated_at = $createdAt;
            $instruction->save();
        }

        $this->command?->info('Dodano ' . count($entries) . ' przykładowych wpisów bazy wiedzy.');
    }
}
