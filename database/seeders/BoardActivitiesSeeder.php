<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
use App\Models\Militare;
use App\Models\ActivityAttachment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BoardActivitiesSeeder extends Seeder
{
    public function run()
    {
        // Disattiva temporaneamente i vincoli delle chiavi esterne
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Prima elimino tutte le attività e allegati esistenti per evitare duplicati
        ActivityAttachment::truncate();
        BoardActivity::truncate();
        
        // Riattiva i vincoli delle chiavi esterne
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Ottengo le colonne e i militari esistenti da utilizzare per creare le attività
        $columns = BoardColumn::all();
        $militari = Militare::all();
        
        // Ora c'è un utente amministratore con ID 1 creato dal UsersSeeder
        $createdBy = 1;
        
        // Attività di esempio per la colonna "In Scadenza"
        $inScadenzaColumn = $columns->where('slug', 'in-scadenza')->first();
        if ($inScadenzaColumn) {
            // Attività 1
            $activity1 = BoardActivity::create([
                'title' => 'Revisione equipaggiamento tattico',
                'description' => 'Controllare ed aggiornare l\'inventario dell\'equipaggiamento tattico in uso. Verificare lo stato di manutenzione e riportare eventuali problemi.',
                'start_date' => Carbon::now()->addDays(2),
                'end_date' => Carbon::now()->addDays(5),
                'column_id' => $inScadenzaColumn->id,
                'created_by' => $createdBy,
                'order' => 1,
                'status' => 'active'
            ]);
            
            // Associa 2-3 militari all'attività
            $militariForActivity = $militari->random(2);
            $activity1->militari()->attach($militariForActivity->pluck('id')->toArray());
            
            // Aggiungi un allegato
            ActivityAttachment::create([
                'activity_id' => $activity1->id,
                'title' => 'Modulo di inventario',
                'url' => 'https://example.com/modulo-inventario',
                'type' => 'link'
            ]);
            
            // Attività 2
            $activity2 = BoardActivity::create([
                'title' => 'Preparazione esercitazione annuale',
                'description' => 'Preparare briefing e documenti per l\'esercitazione annuale. Coordinare con gli altri reparti per la logistica e programmazione.',
                'start_date' => Carbon::now()->addDays(3),
                'end_date' => Carbon::now()->addDays(10),
                'column_id' => $inScadenzaColumn->id,
                'created_by' => $createdBy,
                'order' => 2,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(3);
            $activity2->militari()->attach($militariForActivity->pluck('id')->toArray());
        }
        
        // Attività di esempio per la colonna "Pianificate"
        $pianificateColumn = $columns->where('slug', 'pianificate')->first();
        if ($pianificateColumn) {
            // Attività 3
            $activity3 = BoardActivity::create([
                'title' => 'Corso di primo soccorso',
                'description' => 'Organizzazione corso base di primo soccorso per tutto il personale. Prenotare aula, definire orari e preparare materiale didattico.',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(17),
                'column_id' => $pianificateColumn->id,
                'created_by' => $createdBy,
                'order' => 1,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(4);
            $activity3->militari()->attach($militariForActivity->pluck('id')->toArray());
            
            // Aggiungi allegati
            ActivityAttachment::create([
                'activity_id' => $activity3->id,
                'title' => 'Programma corso',
                'url' => 'https://example.com/programma-corso-primo-soccorso',
                'type' => 'link'
            ]);
            
            ActivityAttachment::create([
                'activity_id' => $activity3->id,
                'title' => 'Lista materiale',
                'url' => 'https://example.com/lista-materiale-corso',
                'type' => 'link'
            ]);
            
            // Attività 4
            $activity4 = BoardActivity::create([
                'title' => 'Aggiornamento procedure operative',
                'description' => 'Revisione e aggiornamento delle procedure operative standard secondo le nuove direttive. Preparare documentazione e presentazione per il briefing.',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(25),
                'column_id' => $pianificateColumn->id,
                'created_by' => $createdBy,
                'order' => 2,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(2);
            $activity4->militari()->attach($militariForActivity->pluck('id')->toArray());
        }
        
        // Attività di esempio per la colonna "Fuori Porta"
        $fuoriPortaColumn = $columns->where('slug', 'fuori-porta')->first();
        if ($fuoriPortaColumn) {
            // Attività 5
            $activity5 = BoardActivity::create([
                'title' => 'Missione addestramento congiunto',
                'description' => 'Partecipazione all\'addestramento congiunto con le forze alleate. Organizzare trasferta, alloggi e programma dettagliato.',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(37),
                'column_id' => $fuoriPortaColumn->id,
                'created_by' => $createdBy,
                'order' => 1,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(5);
            $activity5->militari()->attach($militariForActivity->pluck('id')->toArray());
            
            ActivityAttachment::create([
                'activity_id' => $activity5->id,
                'title' => 'Documenti di viaggio',
                'url' => 'https://example.com/documenti-viaggio',
                'type' => 'link'
            ]);
            
            ActivityAttachment::create([
                'activity_id' => $activity5->id,
                'title' => 'Programma addestramento',
                'url' => 'https://example.com/programma-addestramento',
                'type' => 'link'
            ]);
        }
        
        // Attività di esempio per la colonna "Urgenti"
        $urgentiColumn = $columns->where('slug', 'urgenti')->first();
        if ($urgentiColumn) {
            // Attività 6
            $activity6 = BoardActivity::create([
                'title' => 'Ispezione straordinaria',
                'description' => 'Preparazione documentazione e personale per ispezione straordinaria prevista dalla Direzione. Organizzare briefing preparatorio e verificare tutti i reparti.',
                'start_date' => Carbon::now()->addDay(),
                'end_date' => Carbon::now()->addDays(2),
                'column_id' => $urgentiColumn->id,
                'created_by' => $createdBy,
                'order' => 1,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(3);
            $activity6->militari()->attach($militariForActivity->pluck('id')->toArray());
            
            // Attività 7
            $activity7 = BoardActivity::create([
                'title' => 'Manutenzione sistema comunicazioni',
                'description' => 'Manutenzione urgente del sistema di comunicazioni a seguito di guasto rilevato. Contattare assistenza tecnica e coordinare intervento.',
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDay(),
                'column_id' => $urgentiColumn->id,
                'created_by' => $createdBy,
                'order' => 2,
                'status' => 'active'
            ]);
            
            $militariForActivity = $militari->random(2);
            $activity7->militari()->attach($militariForActivity->pluck('id')->toArray());
            
            ActivityAttachment::create([
                'activity_id' => $activity7->id,
                'title' => 'Scheda tecnica sistema',
                'url' => 'https://example.com/scheda-tecnica',
                'type' => 'link'
            ]);
        }
    }
} 
