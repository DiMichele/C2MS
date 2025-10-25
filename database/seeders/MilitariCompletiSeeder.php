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

class MilitariCompletiSeeder extends Seeder
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

        // Dati militari realistici con assegnazioni complete
        $militariData = [
            // COMANDO DI COMPAGNIA
            [
                'nome' => 'Mario', 'cognome' => 'Rossi', 
                'grado' => 'Capitano', 'ruolo' => 'Comandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => null, 'polo' => null,
                'note' => 'Comandante di compagnia. Esperienza pluriennale nel comando. Ottima leadership.',
                'certificati_note' => 'Tutti i certificati aggiornati. Corso comando completato nel 2023.',
                'idoneita_note' => 'Idoneità al comando confermata. Visite mediche regolari.',
            ],
            
            // COMANDANTI DI PLOTONE
            [
                'nome' => 'Paolo', 'cognome' => 'Verdi', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '1° Plotone Operativo', 'polo' => null,
                'note' => 'Comandante 1° Plotone. Giovane ma molto determinato.',
                'certificati_note' => 'Corsi di specializzazione completati. Aggiornamento previsto per marzo 2025.',
                'idoneita_note' => 'Idoneità piena. Controlli sanitari regolari.',
            ],
            [
                'nome' => 'Luca', 'cognome' => 'Bianchi', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '2° Plotone Supporto', 'polo' => null,
                'note' => 'Comandante 2° Plotone. Specialista in logistica.',
                'certificati_note' => 'Certificazioni logistiche aggiornate. Corso NATO completato.',
                'idoneita_note' => 'Idoneità confermata. Specializzazione in operazioni complesse.',
            ],
            [
                'nome' => 'Andrea', 'cognome' => 'Neri', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '3° Plotone Tecnico', 'polo' => null,
                'note' => 'Comandante 3° Plotone. Esperto in sistemi tecnici.',
                'certificati_note' => 'Certificazioni tecniche avanzate. Corso specialistico completato.',
                'idoneita_note' => 'Idoneità tecnica superiore. Abilitazioni speciali confermate.',
            ],
            [
                'nome' => 'Giuseppe', 'cognome' => 'Ferrari', 
                'grado' => 'Tenente', 'ruolo' => 'Sottocomandante', 'mansione' => 'Comandante di Plotone',
                'plotone' => '4° Plotone Logistico', 'polo' => null,
                'note' => 'Comandante 4° Plotone. Specialista in supporto logistico.',
                'certificati_note' => 'Certificazioni logistiche avanzate. Corso NATO completato.',
                'idoneita_note' => 'Idoneità per operazioni complesse. Leadership confermata.',
            ],

            // RESPONSABILI POLI
            [
                'nome' => 'Marco', 'cognome' => 'Blu', 
                'grado' => 'Maresciallo', 'ruolo' => 'Preposto', 'mansione' => 'Addetto informatico',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Informatico',
                'note' => 'Responsabile Polo Informatico. Esperto in cybersecurity.',
                'certificati_note' => 'Certificazioni informatiche avanzate. Corso cybersecurity 2024.',
                'idoneita_note' => 'Idoneità per sistemi classificati. Nulla osta sicurezza.',
            ],
            [
                'nome' => 'Francesco', 'cognome' => 'Romano', 
                'grado' => 'Sergente', 'ruolo' => 'Preposto', 'mansione' => 'Addetto radio',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Responsabile comunicazioni radio. Esperienza operativa.',
                'certificati_note' => 'Certificazioni radio avanzate. Corso NATO COMMS.',
                'idoneita_note' => 'Idoneità per operazioni radio classificate.',
            ],
            [
                'nome' => 'Claudio', 'cognome' => 'Martini', 
                'grado' => 'Maresciallo', 'ruolo' => 'Preposto', 'mansione' => 'Addetto MGE',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo MGE',
                'note' => 'Responsabile MGE. Esperto in mezzi militari.',
                'certificati_note' => 'Patenti speciali e certificazioni meccaniche.',
                'idoneita_note' => 'Idoneità per conduzione mezzi pesanti.',
            ],
            [
                'nome' => 'Roberto', 'cognome' => 'Galli', 
                'grado' => 'Sergente', 'ruolo' => 'Preposto', 'mansione' => 'Addetto fureria',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Fureria',
                'note' => 'Responsabile Fureria. Gestione economato impeccabile.',
                'certificati_note' => 'Certificazioni amministrative e contabili.',
                'idoneita_note' => 'Idoneità per gestione fondi e materiali.',
            ],
            [
                'nome' => 'Giovanni', 'cognome' => 'Lombardi', 
                'grado' => 'Sergente', 'ruolo' => 'Preposto', 'mansione' => 'Addetto armeria',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Armeria',
                'note' => 'Responsabile Armeria. Massima precisione e sicurezza.',
                'certificati_note' => 'Certificazioni armiere e sicurezza.',
                'idoneita_note' => 'Idoneità per gestione armamenti.',
            ],

            // SPECIALISTI E OPERATORI
            [
                'nome' => 'Giulia', 'cognome' => 'Ferrari', 
                'grado' => 'Sergente', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto informatico',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Informatico',
                'note' => 'Specialista sistemi informatici. Ottima competenza tecnica.',
                'certificati_note' => 'Certificazioni Microsoft e Linux aggiornate.',
                'idoneita_note' => 'Idoneità per lavoro su sistemi sensibili.',
            ],
            [
                'nome' => 'Elena', 'cognome' => 'Conti', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto radio',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Operatrice radio esperta. Precisione e affidabilità.',
                'certificati_note' => 'Certificazioni base radio aggiornate.',
                'idoneita_note' => 'Idoneità per turni notturni e operazioni prolungate.',
            ],
            [
                'nome' => 'Sara', 'cognome' => 'Ricci', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto MGE',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo MGE',
                'note' => 'Specialista manutenzione mezzi. Molto precisa.',
                'certificati_note' => 'Certificazioni meccaniche di base.',
                'idoneita_note' => 'Idoneità per lavori di manutenzione.',
            ],
            [
                'nome' => 'Anna', 'cognome' => 'Moretti', 
                'grado' => 'Caporale', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto fureria',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Fureria',
                'note' => 'Addetta fureria. Organizzata e metodica.',
                'certificati_note' => 'Corsi base amministrazione completati.',
                'idoneita_note' => 'Idoneità per mansioni amministrative.',
            ],
            [
                'nome' => 'Chiara', 'cognome' => 'Esposito', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto amministrazione',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo SIGE',
                'note' => 'Specialista SIGE. Competenze informatiche avanzate.',
                'certificati_note' => 'Certificazioni SIGE e database.',
                'idoneita_note' => 'Idoneità per sistemi informativi militari.',
            ],
            [
                'nome' => 'Davide', 'cognome' => 'Russo', 
                'grado' => 'Caporal Maggiore', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto amministrazione',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Satellitare',
                'note' => 'Operatore satellitare. Competenze tecniche specialistiche.',
                'certificati_note' => 'Certificazioni comunicazioni satellitari.',
                'idoneita_note' => 'Idoneità per operazioni satellitari.',
            ],
            [
                'nome' => 'Matteo', 'cognome' => 'Costa', 
                'grado' => 'Caporale', 'ruolo' => 'Lavoratore', 'mansione' => 'Autista',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo MGE',
                'note' => 'Autista del 1° Plotone. Guida sicura e responsabile.',
                'certificati_note' => 'Patenti militari aggiornate.',
                'idoneita_note' => 'Idoneità alla guida confermata.',
            ],
            [
                'nome' => 'Federica', 'cognome' => 'Mancini', 
                'grado' => 'Soldato', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto sala operativa',
                'plotone' => '1° Plotone Operativo', 'polo' => 'Polo Radio',
                'note' => 'Operatrice sala operativa. Attenzione e precisione.',
                'certificati_note' => 'Corsi base operativi completati.',
                'idoneita_note' => 'Idoneità per turni operativi.',
            ],
            [
                'nome' => 'Simone', 'cognome' => 'Barbieri', 
                'grado' => 'Caporale', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto logistica',
                'plotone' => '2° Plotone Supporto', 'polo' => 'Polo Logistico',
                'note' => 'Addetto logistica 2° Plotone. Organizzazione impeccabile.',
                'certificati_note' => 'Certificazioni logistiche di base.',
                'idoneita_note' => 'Idoneità per movimentazione carichi.',
            ],
            [
                'nome' => 'Valentina', 'cognome' => 'Santoro', 
                'grado' => 'Soldato', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto infermeria',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo Sanitario',
                'note' => 'Addetta infermeria. Preparazione sanitaria di base.',
                'certificati_note' => 'Corso primo soccorso e BLS.',
                'idoneita_note' => 'Idoneità sanitaria per assistenza.',
            ],
            [
                'nome' => 'Alessandro', 'cognome' => 'De Luca', 
                'grado' => 'Soldato', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto sala operativa',
                'plotone' => '3° Plotone Tecnico', 'polo' => 'Polo SIGE',
                'note' => 'Operatore tecnico 3° Plotone. In formazione.',
                'certificati_note' => 'Corsi base in corso di completamento.',
                'idoneita_note' => 'Idoneità di base confermata.',
            ],
            [
                'nome' => 'Stefano', 'cognome' => 'Rizzo', 
                'grado' => 'Soldato', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto logistica',
                'plotone' => '4° Plotone Logistico', 'polo' => 'Polo Logistico',
                'note' => 'Addetto logistica 4° Plotone. Nuovo arrivato.',
                'certificati_note' => 'Formazione di base in corso.',
                'idoneita_note' => 'Idoneità di base confermata.',
            ],
            [
                'nome' => 'Michela', 'cognome' => 'Galli', 
                'grado' => 'Soldato', 'ruolo' => 'Lavoratore', 'mansione' => 'Addetto amministrazione',
                'plotone' => '4° Plotone Logistico', 'polo' => 'Polo Fureria',
                'note' => 'Addetta amministrazione 4° Plotone. In formazione.',
                'certificati_note' => 'Corsi base amministrazione in corso.',
                'idoneita_note' => 'Idoneità di base confermata.',
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
                'certificati_note' => $data['certificati_note'],
                'idoneita_note' => $data['idoneita_note'],
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
