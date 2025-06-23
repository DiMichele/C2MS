<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\Militare;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EventiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Se ci sono già eventi, non ne creiamo altri
        if (Evento::count() > 0) {
            $this->command->info('Gli eventi esistono già. Salto la creazione.');
            return;
        }

        // Ottieni alcuni militari esistenti
        $militari = Militare::limit(10)->get();
        
        if ($militari->count() === 0) {
            $this->command->warn('Nessun militare trovato. Impossibile creare eventi.');
            return;
        }

        $tipologie = [
            'Missione',
            'Addestramento',
            'Corso di formazione',
            'Esercitazione',
            'Conferenza',
            'Ispezione',
            'Servizio speciale',
            'Operazione',
            'Simulazione',
            'Briefing'
        ];

        $localita = [
            'Base Operativa Nord',
            'Centro Addestramento',
            'Sede Centrale',
            'Campo di Addestramento Alpha',
            'Struttura Beta',
            'Poligono di Tiro',
            'Aula Magna',
            'Sala Conferenze',
            'Campo Esterno',
            'Laboratorio Tecnico'
        ];

        $eventi = [];

        // Eventi passati (ultimi 30 giorni)
        for ($i = 0; $i < 8; $i++) {
            $militare = $militari->random();
            $dataInizio = Carbon::now()->subDays(rand(30, 5));
            $durata = rand(1, 7);
            $dataFine = $dataInizio->copy()->addDays($durata - 1);

            $eventi[] = [
                'militare_id' => $militare->id,
                'tipologia' => $tipologie[array_rand($tipologie)],
                'nome' => 'Evento ' . ($i + 1) . ' - ' . $militare->cognome,
                'data_inizio' => $dataInizio->format('Y-m-d'),
                'data_fine' => $dataFine->format('Y-m-d'),
                'localita' => $localita[array_rand($localita)],
                'note' => 'Evento di esempio completato con successo.',
                'stato' => 'completato',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Eventi attuali (oggi e prossimi giorni)
        for ($i = 0; $i < 5; $i++) {
            $militare = $militari->random();
            $dataInizio = Carbon::now()->addDays(rand(0, 3));
            $durata = rand(1, 5);
            $dataFine = $dataInizio->copy()->addDays($durata - 1);

            $eventi[] = [
                'militare_id' => $militare->id,
                'tipologia' => $tipologie[array_rand($tipologie)],
                'nome' => 'Evento Corrente ' . ($i + 1) . ' - ' . $militare->cognome,
                'data_inizio' => $dataInizio->format('Y-m-d'),
                'data_fine' => $dataFine->format('Y-m-d'),
                'localita' => $localita[array_rand($localita)],
                'note' => 'Evento in corso di svolgimento.',
                'stato' => 'in_corso',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Eventi futuri (prossime settimane)
        for ($i = 0; $i < 12; $i++) {
            $militare = $militari->random();
            $dataInizio = Carbon::now()->addDays(rand(4, 60));
            $durata = rand(1, 10);
            $dataFine = $dataInizio->copy()->addDays($durata - 1);

            $eventi[] = [
                'militare_id' => $militare->id,
                'tipologia' => $tipologie[array_rand($tipologie)],
                'nome' => 'Evento Futuro ' . ($i + 1) . ' - ' . $militare->cognome,
                'data_inizio' => $dataInizio->format('Y-m-d'),
                'data_fine' => $dataFine->format('Y-m-d'),
                'localita' => $localita[array_rand($localita)],
                'note' => 'Evento programmato.',
                'stato' => 'programmato',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Inserisci tutti gli eventi
        Evento::insert($eventi);

        $this->command->info('Creati ' . count($eventi) . ' eventi di esempio.');
    }
}
