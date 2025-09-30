<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
use Carbon\Carbon;

class BoardActivitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $columns = BoardColumn::all()->keyBy('slug');
        
        if ($columns->isEmpty()) {
            $this->command->error('Nessuna colonna del board trovata! Eseguire prima BoardColumnsSeeder.');
            return;
        }

        // Creiamo un utente di default se non esiste
        $defaultUser = \App\Models\User::first();
        if (!$defaultUser) {
            $defaultUser = \App\Models\User::create([
                'name' => 'Sistema',
                'email' => 'sistema@c2ms.local',
                'password' => bcrypt('password'),
            ]);
        }

        $activities = [
            [
                'title' => 'Pianificazione Addestramento Mensile',
                'description' => 'Organizzare il calendario degli addestramenti per il prossimo mese',
                'column_slug' => 'todo',
                'priority' => 'high',
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(1),
                'end_date' => Carbon::now()->addDays(7),
                'created_by' => $defaultUser->id,
                'order' => 1
            ],
            [
                'title' => 'Controllo Equipaggiamenti',
                'description' => 'Verifica stato e completezza equipaggiamenti individuali',
                'column_slug' => 'progress',
                'priority' => 'normal',
                'status' => 'active',
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(3),
                'created_by' => $defaultUser->id,
                'order' => 1
            ],
            [
                'title' => 'Aggiornamento Certificazioni',
                'description' => 'Verificare scadenze certificazioni e programmare rinnovi',
                'column_slug' => 'review',
                'priority' => 'high',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(2),
                'end_date' => Carbon::now()->addDays(1),
                'created_by' => $defaultUser->id,
                'order' => 1
            ],
            [
                'title' => 'Rapporto Mensile Attività',
                'description' => 'Completato il rapporto delle attività del mese precedente',
                'column_slug' => 'done',
                'priority' => 'normal',
                'status' => 'completed',
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->subDays(5),
                'created_by' => $defaultUser->id,
                'order' => 1
            ],
            [
                'title' => 'Organizzazione Poligono',
                'description' => 'Preparazione e organizzazione sessione tiro al poligono',
                'column_slug' => 'todo',
                'priority' => 'normal',
                'status' => 'active',
                'start_date' => Carbon::now()->addDays(5),
                'end_date' => Carbon::now()->addDays(14),
                'created_by' => $defaultUser->id,
                'order' => 2
            ],
            [
                'title' => 'Manutenzione Mezzi',
                'description' => 'Controllo e manutenzione ordinaria dei mezzi in dotazione',
                'column_slug' => 'progress',
                'priority' => 'high',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(1),
                'end_date' => Carbon::now()->addDays(2),
                'created_by' => $defaultUser->id,
                'order' => 2
            ]
        ];

        foreach ($activities as $activityData) {
            $columnSlug = $activityData['column_slug'];
            unset($activityData['column_slug']);
            
            if (!isset($columns[$columnSlug])) {
                $this->command->warn("Colonna '$columnSlug' non trovata, salto attività.");
                continue;
            }
            
            $activityData['column_id'] = $columns[$columnSlug]->id;
            
            BoardActivity::create($activityData);
        }

        $this->command->info('Creati ' . count($activities) . ' attività del board.');
    }
}