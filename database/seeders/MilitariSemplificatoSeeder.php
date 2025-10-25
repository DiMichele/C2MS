<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grado;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\Militare;
use App\Models\Presenza;
use App\Models\Evento;
use App\Models\MilitareValutazione;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MilitariSemplificatoSeeder extends Seeder
{
    public function run()
    {
        // Controlla se esistono già militari
        if (Militare::count() > 0) {
            $this->command->info('⚠️  Militari già presenti nel database. Saltando creazione.');
            return;
        }

        // Ottieni i dati esistenti
        $compagnia = Compagnia::first();
        $plotoni = Plotone::all();
        $poli = Polo::all();
        $gradi = Grado::all();
        $ruoli = Ruolo::all();
        $mansioni = Mansione::all();

        // Crea utente amministratore se non esiste
        $admin = User::firstOrCreate([
            'email' => 'admin@c2ms.local'
        ], [
            'name' => 'Amministratore Sistema',
            'password' => Hash::make('password123'),
        ]);

        // Dati militari semplificati
        $militariData = [
            // COMANDO DI COMPAGNIA
            [
                'nome' => 'Mario', 'cognome' => 'Rossi', 
                'grado' => 'Capitano', 'ruolo' => 'Comandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => null, 'polo' => null,
                'note' => 'Comandante di compagnia. Esperienza pluriennale nel comando.',
            ],
            
            // COMANDANTI DI PLOTONE
            [
                'nome' => 'Paolo', 'cognome' => 'Verdi', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '1° Plotone Operativo', 'polo' => null,
                'note' => 'Comandante 1° Plotone. Giovane ma determinato.',
            ],
            [
                'nome' => 'Luca', 'cognome' => 'Bianchi', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '2° Plotone Supporto', 'polo' => null,
                'note' => 'Comandante 2° Plotone. Specialista in logistica.',
            ],
            [
                'nome' => 'Andrea', 'cognome' => 'Neri', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '3° Plotone Tecnico', 'polo' => null,
                'note' => 'Comandante 3° Plotone. Esperto in sistemi tecnici.',
            ],
            [
                'nome' => 'Giuseppe', 'cognome' => 'Ferrari', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '4° Plotone Logistico', 'polo' => null,
                'note' => 'Comandante 4° Plotone. Specialista in supporto logistico.',
            ],

            // RESPONSABILI POLI
            [
                'nome' => 'Marco', 'cognome' => 'Blu', 
                'grado' => 'Maresciallo', 'ruolo' => 'Capo Servizio', 'mansione' => 'Specialista IT',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Informatico',
                'note' => 'Responsabile Polo Informatico. Esperto in cybersecurity.',
            ],
            [
                'nome' => 'Francesco', 'cognome' => 'Romano', 
                'grado' => 'Sergente', 'ruolo' => 'Capo Servizio', 'mansione' => 'Operatore Radio',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Responsabile comunicazioni radio. Esperienza operativa.',
            ],
            [
                'nome' => 'Claudio', 'cognome' => 'Martini', 
                'grado' => 'Maresciallo', 'ruolo' => 'Capo Servizio', 'mansione' => 'Meccanico',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo MGE',
                'note' => 'Responsabile MGE. Esperto in mezzi militari.',
            ],
            [
                'nome' => 'Roberto', 'cognome' => 'Galli', 
                'grado' => 'Sergente', 'ruolo' => 'Capo Servizio', 'mansione' => 'Logistico',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Fureria',
                'note' => 'Responsabile Fureria. Gestione economato impeccabile.',
            ],
            [
                'nome' => 'Giovanni', 'cognome' => 'Lombardi', 
                'grado' => 'Sergente', 'ruolo' => 'Capo Servizio', 'mansione' => 'Armiere',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Armeria',
                'note' => 'Responsabile Armeria. Massima precisione e sicurezza.',
            ],

            // SPECIALISTI E OPERATORI
            [
                'nome' => 'Giulia', 'cognome' => 'Ferrari', 
                'grado' => 'Sergente', 'ruolo' => 'Specialista', 'mansione' => 'Specialista IT',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Informatico',
                'note' => 'Specialista sistemi informatici. Ottima competenza tecnica.',
            ],
            [
                'nome' => 'Elena', 'cognome' => 'Conti', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Operatore', 'mansione' => 'Operatore Radio',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Operatrice radio esperta. Precisione e affidabilità.',
            ],
            [
                'nome' => 'Sara', 'cognome' => 'Ricci', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Operatore', 'mansione' => 'Meccanico',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo MGE',
                'note' => 'Specialista manutenzione mezzi. Molto precisa.',
            ],
            [
                'nome' => 'Anna', 'cognome' => 'Moretti', 
                'grado' => 'Caporale', 'ruolo' => 'Addetto', 'mansione' => 'Logistico',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Fureria',
                'note' => 'Addetta fureria. Organizzata e metodica.',
            ],
            [
                'nome' => 'Chiara', 'cognome' => 'Esposito', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Operatore', 'mansione' => 'Specialista IT',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo SIGE',
                'note' => 'Specialista SIGE. Competenze informatiche avanzate.',
            ],
            [
                'nome' => 'Davide', 'cognome' => 'Russo', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Operatore', 'mansione' => 'Operatore Radio',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Satellitare',
                'note' => 'Operatore satellitare. Competenze tecniche specialistiche.',
            ],
            [
                'nome' => 'Matteo', 'cognome' => 'Costa', 
                'grado' => 'Caporale', 'ruolo' => 'Operatore', 'mansione' => 'Autista',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo MGE',
                'note' => 'Autista del 1° Plotone. Guida sicura e responsabile.',
            ],
            [
                'nome' => 'Federica', 'cognome' => 'Mancini', 
                'grado' => 'Soldato', 'ruolo' => 'Militare di Truppa', 'mansione' => 'Militare Semplice',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Operatrice sala operativa. Attenzione e precisione.',
            ],
            [
                'nome' => 'Simone', 'cognome' => 'Barbieri', 
                'grado' => 'Caporale', 'ruolo' => 'Operatore', 'mansione' => 'Logistico',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Logistico',
                'note' => 'Addetto logistica 2° Plotone. Organizzazione impeccabile.',
            ],
            [
                'nome' => 'Valentina', 'cognome' => 'Santoro', 
                'grado' => 'Soldato', 'ruolo' => 'Militare di Truppa', 'mansione' => 'Sanitario',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Sanitario',
                'note' => 'Addetta infermeria. Preparazione sanitaria di base.',
            ],
            [
                'nome' => 'Alessandro', 'cognome' => 'De Luca', 
                'grado' => 'Soldato', 'ruolo' => 'Militare di Truppa', 'mansione' => 'Militare Semplice',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo SIGE',
                'note' => 'Operatore tecnico 3° Plotone. In formazione.',
            ],
            [
                'nome' => 'Stefano', 'cognome' => 'Rizzo', 
                'grado' => 'Soldato', 'ruolo' => 'Militare di Truppa', 'mansione' => 'Logistico',
                'plotone' => '4° Plotone Logistico', 'polo' => 'Polo Logistico',
                'note' => 'Addetto logistica 4° Plotone. Nuovo arrivato.',
            ],
            [
                'nome' => 'Michela', 'cognome' => 'Galli', 
                'grado' => 'Soldato', 'ruolo' => 'Militare di Truppa', 'mansione' => 'Militare Semplice',
                'plotone' => '4° Plotone Logistico', 'polo' => 'Polo Fureria',
                'note' => 'Addetta amministrazione 4° Plotone. In formazione.',
            ],
        ];

        // Crea i militari
        $militariCreati = [];
        foreach ($militariData as $data) {
            // Trova i riferimenti
            $grado = $gradi->where('nome', $data['grado'])->first();
            $ruolo = $ruoli->where('nome', $data['ruolo'])->first();
            $mansione = $mansioni->where('nome', $data['mansione'])->first();
            $plotone = $data['plotone'] ? $plotoni->where('nome', $data['plotone'])->first() : null;
            $polo = $data['polo'] ? $poli->where('nome', $data['polo'])->first() : null;

            if (!$grado || !$ruolo || !$mansione) {
                $this->command->error("Errore: Grado, ruolo o mansione non trovati per {$data['nome']} {$data['cognome']}");
                continue;
            }

            $militare = Militare::create([
                'nome' => $data['nome'],
                'cognome' => $data['cognome'],
                'grado_id' => $grado->id,
                'ruolo_id' => $ruolo->id,
                'mansione_id' => $mansione->id,
                'plotone_id' => $plotone ? $plotone->id : null,
                'polo_id' => $polo ? $polo->id : null,
                'compagnia_id' => $compagnia->id,
                'note' => $data['note'],
            ]);
            
            $militariCreati[] = $militare;
        }

        // Crea presenze per l'ultimo mese
        $dataInizio = Carbon::now()->subDays(30);
        $dataFine = Carbon::now();
        
        foreach ($militariCreati as $militare) {
            $data = $dataInizio->copy();
            
            while ($data->lte($dataFine)) {
                // Skip weekend
                if (!$data->isWeekend()) {
                    $stato = rand(1, 100) <= 95 ? 'Presente' : 'Assente'; // 95% presenza
                    
                    Presenza::create([
                        'militare_id' => $militare->id,
                        'data' => $data->copy(),
                        'stato' => $stato,
                        'note' => $stato == 'Assente' ? 'Assenza giustificata' : null,
                    ]);
                }
                
                $data->addDay();
            }
        }

        // Crea alcuni eventi per i militari
        $tipiEventi = ['Addestramento', 'Missione', 'Corso', 'Servizio Speciale', 'Esercitazione'];
        
        foreach ($militariCreati as $militare) {
            // Ogni militare ha 1-3 eventi
            $numEventi = rand(1, 3);
            
            for ($i = 0; $i < $numEventi; $i++) {
                $dataInizio = Carbon::now()->addDays(rand(-30, 60));
                $dataFine = $dataInizio->copy()->addDays(rand(1, 7));
                
                Evento::create([
                    'militare_id' => $militare->id,
                    'tipologia' => $tipiEventi[array_rand($tipiEventi)],
                    'nome' => $this->getNomeEventoRandom(),
                    'data_inizio' => $dataInizio,
                    'data_fine' => $dataFine,
                    'localita' => $this->getLocalitaRandom(),
                    'note' => 'Evento programmato secondo calendario operativo.',
                    'stato' => $dataInizio->isPast() ? 'completato' : 'programmato',
                ]);
            }
        }

        // Crea valutazioni per ogni militare
        foreach ($militariCreati as $militare) {
            $valutazione = [
                'militare_id' => $militare->id,
                'valutatore_id' => $admin->id,
                'precisione_lavoro' => rand(3, 5),
                'affidabilita' => rand(3, 5),
                'capacita_tecnica' => rand(3, 5),
                'collaborazione' => rand(4, 5),
                'iniziativa' => rand(3, 5),
                'autonomia' => rand(3, 5),
                'note_positive' => $this->getNotaPositivaRandom($militare),
            ];
            
            // 60% ha aree di miglioramento specifiche
            if (rand(1, 10) <= 6) {
                $valutazione['note_negative'] = $this->getAreaMiglioramentoRandom($militare);
            }
            
            MilitareValutazione::create($valutazione);
        }

        $this->command->info('✅ Militari creati con successo!');
        $this->command->info('📊 Creati:');
        $this->command->info('   - ' . count($militariCreati) . ' militari con assegnazioni complete');
        $this->command->info('   - Presenze per l\'ultimo mese');
        $this->command->info('   - Eventi e valutazioni realistiche');
    }

    /**
     * Genera note positive casuali realistiche
     */
    private function getNotaPositivaRandom($militare)
    {
        $notePositive = [
            'Dimostra sempre grande professionalità e dedizione nel lavoro',
            'Eccellente capacità di lavorare in team e supportare i colleghi',
            'Molto preciso nell\'esecuzione dei compiti assegnati',
            'Mostra iniziativa e proattività nelle attività quotidiane',
            'Affidabile e puntuale, sempre disponibile quando necessario',
            'Ottima capacità di apprendimento e adattamento',
            'Dimostra leadership naturale e capacità di coordinamento',
            'Mantiene sempre un atteggiamento positivo e costruttivo',
            'Eccellente gestione dello stress e delle situazioni complesse',
            'Competenze tecniche superiori alla media del grado',
        ];
        
        return $notePositive[array_rand($notePositive)];
    }

    /**
     * Genera aree di miglioramento specifiche e costruttive
     */
    private function getAreaMiglioramentoRandom($militare)
    {
        $areeMiglioramento = [
            'AREA TECNICA: Necessario approfondimento sui nuovi sistemi informatici. Consigliato corso di aggiornamento entro il trimestre.',
            'COMPETENZE DIGITALI: Da migliorare l\'utilizzo degli strumenti software avanzati. Previsto affiancamento con personale esperto.',
            'PROCEDURE OPERATIVE: Richiede maggiore familiarità con le procedure di emergenza. Pianificata sessione di training specifico.',
            'COMUNICAZIONE: Sviluppare maggiore chiarezza nell\'esposizione durante i briefing. Suggerito corso di public speaking.',
            'LEADERSHIP: Potenziare le capacità di coordinamento del team nei progetti complessi. Previsto mentoring con superiori.',
            'COLLABORAZIONE: Migliorare l\'integrazione con altri reparti. Organizzare incontri interdisciplinari.',
            'GESTIONE TEMPO: Ottimizzare la pianificazione delle attività quotidiane. Implementare strumenti di time management.',
            'PRECISIONE: Aumentare l\'attenzione ai dettagli nelle verifiche finali. Istituire checklist di controllo.',
            'DOCUMENTAZIONE: Migliorare la completezza nella redazione dei rapporti. Fornire template standardizzati.',
            'PROATTIVITÀ: Incoraggiare maggiore iniziativa personale nei progetti di miglioramento. Assegnare responsabilità specifiche.',
        ];
        
        return $areeMiglioramento[array_rand($areeMiglioramento)];
    }

    /**
     * Genera nomi eventi casuali
     */
    private function getNomeEventoRandom()
    {
        $nomiEventi = [
            'Esercitazione Alpha-7',
            'Corso Aggiornamento Tecnico',
            'Addestramento Comunicazioni',
            'Missione Supporto Logistico',
            'Corso Primo Soccorso',
            'Esercitazione Congiunta',
            'Addestramento Sistemi',
            'Corso Specializzazione',
            'Servizio Guardia Speciale',
            'Esercitazione Notturna',
        ];
        
        return $nomiEventi[array_rand($nomiEventi)];
    }

    /**
     * Genera località casuali
     */
    private function getLocalitaRandom()
    {
        $localita = [
            'Caserma Centrale',
            'Campo Addestramento Nord',
            'Centro Tecnico Milano',
            'Base Operativa Roma',
            'Poligono di Tiro',
            'Centro Formazione',
            'Sede Distaccata',
            'Campo Esercitazioni',
            'Centro Logistico',
            'Struttura Esterna',
        ];
        
        return $localita[array_rand($localita)];
    }
}
