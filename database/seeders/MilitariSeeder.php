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
// use App\Models\CertificatiLavoratori; // DEPRECATO - tabelle rimosse
// use App\Models\Idoneita; // DEPRECATO - tabelle rimosse
use App\Models\MilitareValutazione;
use App\Models\Evento;
use App\Models\Presenza;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MilitariSeeder extends Seeder
{
    public function run()
    {
        // Elimina tutti i dati esistenti in ordine inverso rispetto alle dipendenze
        // SQLite non supporta SET FOREIGN_KEY_CHECKS, usa PRAGMA
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }
        
        // Elimina dati dipendenti
        Presenza::truncate();
        Evento::truncate();
        MilitareValutazione::truncate();
        Idoneita::truncate();
        CertificatiLavoratori::truncate();
        Militare::truncate();
        
        // Elimina strutture organizzative
        Polo::truncate();
        Plotone::truncate();
        Compagnia::truncate();
        Mansione::truncate();
        Ruolo::truncate();
        Grado::truncate();
        
        // Riattiva i foreign key checks
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
        
        // 1. Crea i gradi militari (ordine crescente di importanza)
        $gradi = [
            ['nome' => 'Soldato', 'abbreviazione' => 'Sol.', 'ordine' => 1],
            ['nome' => 'Caporale', 'abbreviazione' => 'Cap.', 'ordine' => 2],
            ['nome' => 'Caporal Maggiore', 'abbreviazione' => 'C.M.', 'ordine' => 3],
            ['nome' => 'Sergente', 'abbreviazione' => 'Serg.', 'ordine' => 4],
            ['nome' => 'Maresciallo', 'abbreviazione' => 'Mar.', 'ordine' => 5],
            ['nome' => 'Tenente', 'abbreviazione' => 'Ten.', 'ordine' => 6],
            ['nome' => 'Capitano', 'abbreviazione' => 'Cap.', 'ordine' => 7],
        ];

        foreach ($gradi as $grado) {
            Grado::create($grado);
        }

        // 2. Crea i ruoli
        $ruoli = [
            ['nome' => 'Lavoratore', 'descrizione' => 'Personale di base senza responsabilità di supervisione'],
            ['nome' => 'Preposto', 'descrizione' => 'Personale con responsabilità di supervisione e coordinamento'],
            ['nome' => 'Dirigente', 'descrizione' => 'Personale con responsabilità di comando e gestione'],
        ];

        foreach ($ruoli as $ruolo) {
            Ruolo::create($ruolo);
        }

        // 3. Crea le mansioni
        $mansioni = [
            ['nome' => 'Addetto amministrazione', 'descrizione' => 'Gestione pratiche amministrative e burocratiche'],
            ['nome' => 'Addetto logistica', 'descrizione' => 'Gestione rifornimenti e movimentazione materiali'],
            ['nome' => 'Addetto armeria', 'descrizione' => 'Custodia e manutenzione armamenti'],
            ['nome' => 'Addetto sala operativa', 'descrizione' => 'Monitoraggio e coordinamento operazioni'],
            ['nome' => 'Autista', 'descrizione' => 'Conduzione mezzi militari'],
            ['nome' => 'Comandante di plotone', 'descrizione' => 'Comando e coordinamento plotone'],
            ['nome' => 'Comandante di compagnia', 'descrizione' => 'Comando e coordinamento compagnia'],
            ['nome' => 'Addetto infermeria', 'descrizione' => 'Assistenza sanitaria di base'],
            ['nome' => 'Addetto informatico', 'descrizione' => 'Gestione sistemi informatici'],
            ['nome' => 'Addetto radio', 'descrizione' => 'Gestione comunicazioni radio'],
            ['nome' => 'Addetto fureria', 'descrizione' => 'Gestione economato e vettovagliamento'],
            ['nome' => 'Addetto MGE', 'descrizione' => 'Gestione mezzi ed equipaggiamenti'],
        ];

        foreach ($mansioni as $mansione) {
            Mansione::create($mansione);
        }

        // 4. Crea una compagnia
        $compagnia = Compagnia::create([
            'nome' => '1ª Compagnia Trasmissioni', 
            'descrizione' => 'Compagnia specializzata in comunicazioni e supporto tecnico',
            'codice' => '1CT'
        ]);

        // 5. Crea i plotoni
        $plotoni = [
            ['nome' => '1° Plotone Trasmissioni', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Plotone operativo principale'],
            ['nome' => '2° Plotone Supporto', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Plotone supporto logistico'],
            ['nome' => '3° Plotone Tecnico', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Plotone manutenzione tecnica'],
        ];

        $plotoniCreati = [];
        foreach ($plotoni as $plotone) {
            $plotoniCreati[] = Plotone::create($plotone);
        }

        // 6. Crea i poli come specificato
        $poli = [
            ['nome' => 'Polo Satellitare', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Gestione comunicazioni satellitari'],
            ['nome' => 'Polo Informatico', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Gestione sistemi informatici'],
            ['nome' => 'Polo MGE', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Gestione mezzi ed equipaggiamenti'],
            ['nome' => 'Fureria', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Gestione economato e vettovagliamento'],
            ['nome' => 'SIGE', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Sistema Informativo per la Gestione dell\'Esercito'],
            ['nome' => 'Polo Radio', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Gestione comunicazioni radio'],
            ['nome' => 'Armeria', 'compagnia_id' => $compagnia->id, 'descrizione' => 'Custodia e manutenzione armamenti'],
        ];

        $poliCreati = [];
        foreach ($poli as $polo) {
            $poliCreati[] = Polo::create($polo);
        }

        // 7. Crea utente amministratore se non esiste
        $admin = User::firstOrCreate([
            'email' => 'admin@c2ms.local'
        ], [
            'name' => 'Amministratore Sistema',
            'password' => Hash::make('password123'),
        ]);

                 // 8. Dati per i militari (20 militari come richiesto)
         $militariData = [
             // COMANDO DI COMPAGNIA - nessun polo, nessun plotone
             [
                 'nome' => 'Mario', 'cognome' => 'Rossi', 'grado_id' => 7, // Capitano
                 'ruolo_id' => 3, 'mansione_id' => 7, 'plotone_id' => null, 'polo_id' => null,
                 'foto' => '1.png', 'note' => 'Comandante di compagnia. Esperienza pluriennale nel comando. Ottima leadership.',
                 'certificati_note' => 'Tutti i certificati aggiornati. Corso comando completato nel 2023.',
                 'idoneita_note' => 'Idoneità al comando confermata. Visite mediche regolari.',
             ],
             
             // COMANDANTI DI PLOTONE - solo plotone, nessun polo
             [
                 'nome' => 'Paolo', 'cognome' => 'Verdi', 'grado_id' => 6, // Tenente
                 'ruolo_id' => 2, 'mansione_id' => 6, 'plotone_id' => 1, 'polo_id' => null,
                 'foto' => '2.png', 'note' => 'Comandante 1° Plotone. Giovane ma molto determinato.',
                 'certificati_note' => 'Corsi di specializzazione completati. Aggiornamento previsto per marzo 2025.',
                 'idoneita_note' => 'Idoneità piena. Controlli sanitari regolari.',
             ],
             [
                 'nome' => 'Luca', 'cognome' => 'Bianchi', 'grado_id' => 6, // Tenente
                 'ruolo_id' => 2, 'mansione_id' => 6, 'plotone_id' => 2, 'polo_id' => null,
                 'foto' => '4.png', 'note' => 'Comandante 2° Plotone. Specialista in logistica.',
                 'certificati_note' => 'Certificazioni logistiche aggiornate. Corso NATO completato.',
                 'idoneita_note' => 'Idoneità confermata. Specializzazione in operazioni complesse.',
             ],
             [
                 'nome' => 'Andrea', 'cognome' => 'Neri', 'grado_id' => 6, // Tenente
                 'ruolo_id' => 2, 'mansione_id' => 6, 'plotone_id' => 3, 'polo_id' => null,
                 'foto' => '5.png', 'note' => 'Comandante 3° Plotone. Esperto in sistemi tecnici.',
                 'certificati_note' => 'Certificazioni tecniche avanzate. Corso specialistico completato.',
                 'idoneita_note' => 'Idoneità tecnica superiore. Abilitazioni speciali confermate.',
             ],

             // TUTTI GLI ALTRI MILITARI - sia polo che plotone
             [
                 'nome' => 'Marco', 'cognome' => 'Blu', 'grado_id' => 5, // Maresciallo
                 'ruolo_id' => 2, 'mansione_id' => 9, 'plotone_id' => 1, 'polo_id' => 2, // Polo Informatico + 1° Plotone
                 'foto' => '6.png', 'note' => 'Responsabile Polo Informatico. Esperto in cybersecurity.',
                 'certificati_note' => 'Certificazioni informatiche avanzate. Corso cybersecurity 2024.',
                 'idoneita_note' => 'Idoneità per sistemi classificati. Nulla osta sicurezza.',
             ],
             [
                 'nome' => 'Giulia', 'cognome' => 'Ferrari', 'grado_id' => 4, // Sergente
                 'ruolo_id' => 1, 'mansione_id' => 9, 'plotone_id' => 1, 'polo_id' => 2, // Polo Informatico + 1° Plotone
                 'foto' => '15.png', 'note' => 'Specialista sistemi informatici. Ottima competenza tecnica.',
                 'certificati_note' => 'Certificazioni Microsoft e Linux aggiornate.',
                 'idoneita_note' => 'Idoneità per lavoro su sistemi sensibili.',
             ],
             [
                 'nome' => 'Francesco', 'cognome' => 'Romano', 'grado_id' => 4, // Sergente
                 'ruolo_id' => 2, 'mansione_id' => 10, 'plotone_id' => 1, 'polo_id' => 6, // Polo Radio + 1° Plotone
                 'foto' => '7.png', 'note' => 'Responsabile comunicazioni radio. Esperienza operativa.',
                 'certificati_note' => 'Certificazioni radio avanzate. Corso NATO COMMS.',
                 'idoneita_note' => 'Idoneità per operazioni radio classificate.',
             ],
             [
                 'nome' => 'Elena', 'cognome' => 'Conti', 'grado_id' => 3, // Caporal Maggiore
                 'ruolo_id' => 1, 'mansione_id' => 10, 'plotone_id' => 1, 'polo_id' => 6, // Polo Radio + 1° Plotone
                 'foto' => '16.png', 'note' => 'Operatrice radio esperta. Precisione e affidabilità.',
                 'certificati_note' => 'Certificazioni base radio aggiornate.',
                 'idoneita_note' => 'Idoneità per turni notturni e operazioni prolungate.',
             ],
             [
                 'nome' => 'Claudio', 'cognome' => 'Martini', 'grado_id' => 5, // Maresciallo
                 'ruolo_id' => 2, 'mansione_id' => 12, 'plotone_id' => 2, 'polo_id' => 3, // Polo MGE + 2° Plotone
                 'foto' => '8.png', 'note' => 'Responsabile MGE. Esperto in mezzi militari.',
                 'certificati_note' => 'Patenti speciali e certificazioni meccaniche.',
                 'idoneita_note' => 'Idoneità per conduzione mezzi pesanti.',
             ],
             [
                 'nome' => 'Sara', 'cognome' => 'Ricci', 'grado_id' => 3, // Caporal Maggiore
                 'ruolo_id' => 1, 'mansione_id' => 12, 'plotone_id' => 2, 'polo_id' => 3, // Polo MGE + 2° Plotone
                 'foto' => '17.png', 'note' => 'Specialista manutenzione mezzi. Molto precisa.',
                 'certificati_note' => 'Certificazioni meccaniche di base.',
                 'idoneita_note' => 'Idoneità per lavori di manutenzione.',
             ],
             [
                 'nome' => 'Roberto', 'cognome' => 'Galli', 'grado_id' => 4, // Sergente
                 'ruolo_id' => 2, 'mansione_id' => 11, 'plotone_id' => 2, 'polo_id' => 4, // Fureria + 2° Plotone
                 'foto' => '9.png', 'note' => 'Responsabile Fureria. Gestione economato impeccabile.',
                 'certificati_note' => 'Certificazioni amministrative e contabili.',
                 'idoneita_note' => 'Idoneità per gestione fondi e materiali.',
             ],
             [
                 'nome' => 'Anna', 'cognome' => 'Moretti', 'grado_id' => 2, // Caporale
                 'ruolo_id' => 1, 'mansione_id' => 11, 'plotone_id' => 2, 'polo_id' => 4, // Fureria + 2° Plotone
                 'foto' => '18.png', 'note' => 'Addetta fureria. Organizzata e metodica.',
                 'certificati_note' => 'Corsi base amministrazione completati.',
                 'idoneita_note' => 'Idoneità per mansioni amministrative.',
             ],
             [
                 'nome' => 'Giovanni', 'cognome' => 'Lombardi', 'grado_id' => 4, // Sergente
                 'ruolo_id' => 2, 'mansione_id' => 3, 'plotone_id' => 3, 'polo_id' => 7, // Armeria + 3° Plotone
                 'foto' => '10.png', 'note' => 'Responsabile Armeria. Massima precisione e sicurezza.',
                 'certificati_note' => 'Certificazioni armiere e sicurezza.',
                 'idoneita_note' => 'Idoneità per gestione armamenti.',
             ],
             [
                 'nome' => 'Chiara', 'cognome' => 'Esposito', 'grado_id' => 3, // Caporal Maggiore
                 'ruolo_id' => 1, 'mansione_id' => 1, 'plotone_id' => 3, 'polo_id' => 5, // SIGE + 3° Plotone
                 'foto' => '19.png', 'note' => 'Specialista SIGE. Competenze informatiche avanzate.',
                 'certificati_note' => 'Certificazioni SIGE e database.',
                 'idoneita_note' => 'Idoneità per sistemi informativi militari.',
             ],
             [
                 'nome' => 'Davide', 'cognome' => 'Russo', 'grado_id' => 3, // Caporal Maggiore
                 'ruolo_id' => 1, 'mansione_id' => 1, 'plotone_id' => 3, 'polo_id' => 1, // Polo Satellitare + 3° Plotone
                 'foto' => '11.png', 'note' => 'Operatore satellitare. Competenze tecniche specialistiche.',
                 'certificati_note' => 'Certificazioni comunicazioni satellitari.',
                 'idoneita_note' => 'Idoneità per operazioni satellitari.',
             ],
             [
                 'nome' => 'Matteo', 'cognome' => 'Costa', 'grado_id' => 2, // Caporale
                 'ruolo_id' => 1, 'mansione_id' => 5, 'plotone_id' => 1, 'polo_id' => 2, // Polo Informatico + 1° Plotone
                 'foto' => '12.png', 'note' => 'Autista del 1° Plotone. Guida sicura e responsabile.',
                 'certificati_note' => 'Patenti militari aggiornate.',
                 'idoneita_note' => 'Idoneità alla guida confermata.',
             ],
             [
                 'nome' => 'Federica', 'cognome' => 'Mancini', 'grado_id' => 1, // Soldato
                 'ruolo_id' => 1, 'mansione_id' => 4, 'plotone_id' => 1, 'polo_id' => 6, // Polo Radio + 1° Plotone
                 'foto' => '20.png', 'note' => 'Operatrice sala operativa. Attenzione e precisione.',
                 'certificati_note' => 'Corsi base operativi completati.',
                 'idoneita_note' => 'Idoneità per turni operativi.',
             ],
             [
                 'nome' => 'Simone', 'cognome' => 'Barbieri', 'grado_id' => 2, // Caporale
                 'ruolo_id' => 1, 'mansione_id' => 2, 'plotone_id' => 2, 'polo_id' => 3, // Polo MGE + 2° Plotone
                 'foto' => '13.png', 'note' => 'Addetto logistica 2° Plotone. Organizzazione impeccabile.',
                 'certificati_note' => 'Certificazioni logistiche di base.',
                 'idoneita_note' => 'Idoneità per movimentazione carichi.',
             ],
             [
                 'nome' => 'Valentina', 'cognome' => 'Santoro', 'grado_id' => 1, // Soldato
                 'ruolo_id' => 1, 'mansione_id' => 8, 'plotone_id' => 3, 'polo_id' => 7, // Armeria + 3° Plotone
                 'foto' => '16.png', 'note' => 'Addetta infermeria. Preparazione sanitaria di base.',
                 'certificati_note' => 'Corso primo soccorso e BLS.',
                 'idoneita_note' => 'Idoneità sanitaria per assistenza.',
             ],
             [
                 'nome' => 'Alessandro', 'cognome' => 'De Luca', 'grado_id' => 1, // Soldato
                 'ruolo_id' => 1, 'mansione_id' => 4, 'plotone_id' => 3, 'polo_id' => 5, // SIGE + 3° Plotone
                 'foto' => '14.png', 'note' => 'Operatore tecnico 3° Plotone. In formazione.',
                 'certificati_note' => 'Corsi base in corso di completamento.',
                 'idoneita_note' => 'Idoneità di base confermata.',
             ],
         ];

        // 9. Crea i militari con foto profilo
        $militariCreati = [];
        foreach ($militariData as $index => $data) {
            $militare = Militare::create([
                'nome' => $data['nome'],
                'cognome' => $data['cognome'],
                'grado_id' => $data['grado_id'],
                'ruolo_id' => $data['ruolo_id'],
                'mansione_id' => $data['mansione_id'],
                'plotone_id' => $data['plotone_id'],
                'polo_id' => $data['polo_id'],
                'compagnia_id' => $compagnia->id,
                'note' => $data['note'],
                'certificati_note' => $data['certificati_note'],
                'idoneita_note' => $data['idoneita_note'],
                'foto_path' => 'private/Foto profilo/' . $data['foto'],
            ]);
            
            $militariCreati[] = $militare;
        }

        // 10. Crea certificati lavoratori per ogni militare con stati realistici
        $tipiCertificati = ['corsi_lavoratori_4h', 'corsi_lavoratori_8h', 'corsi_lavoratori_preposti', 'corsi_lavoratori_dirigenti'];
        
        foreach ($militariCreati as $index => $militare) {
            // Assegna certificati in base al ruolo
            $certificatiDaAssegnare = ['corsi_lavoratori_4h', 'corsi_lavoratori_8h'];
            
            if ($militare->ruolo_id >= 2) { // Preposto o Dirigente
                $certificatiDaAssegnare[] = 'corsi_lavoratori_preposti';
            }
            
            if ($militare->ruolo_id == 3) { // Solo Dirigenti
                $certificatiDaAssegnare[] = 'corsi_lavoratori_dirigenti';
            }
            
            foreach ($certificatiDaAssegnare as $certIndex => $tipo) {
                // Logica realistica per stati certificati:
                // 65% attivi, 25% in scadenza, 8% scaduti, 2% non presenti
                $rand = rand(1, 100);
                
                if ($rand <= 2) {
                    // 2% non presenti - skip questo certificato
                    continue;
                } elseif ($rand <= 10) {
                    // 8% scaduti (scaduti da 1-12 mesi)
                    $mesiScaduti = rand(1, 12);
                    $dataOttenimento = Carbon::now()->subMonths(60 + $mesiScaduti); // 5 anni + mesi scaduti
                    $dataScadenza = $dataOttenimento->copy()->addYears(5);
                    $note = '🚨 CERTIFICATO SCADUTO da ' . $mesiScaduti . ' mesi - RINNOVO URGENTE';
                } elseif ($rand <= 35) {
                    // 25% in scadenza (prossimi 1-6 mesi)
                    $mesiAllaScadenza = rand(1, 6);
                    $dataOttenimento = Carbon::now()->subMonths(60 - $mesiAllaScadenza); // 5 anni - mesi alla scadenza
                    $dataScadenza = $dataOttenimento->copy()->addYears(5);
                    $note = '⚠️ Certificato scade tra ' . $mesiAllaScadenza . ' mesi - Programmato rinnovo per ' . $dataScadenza->subMonths(1)->format('M Y');
                } else {
                    // 65% attivi (validi per 1-4 anni)
                    $anniValidi = rand(1, 4);
                    $dataOttenimento = Carbon::now()->subMonths(60 - ($anniValidi * 12)); // 5 anni - anni validi
                    $dataScadenza = $dataOttenimento->copy()->addYears(5);
                    $note = '✅ Certificato valido fino al ' . $dataScadenza->format('d/m/Y') . ' (' . $anniValidi . ' anni rimanenti)';
                }
                
                CertificatiLavoratori::create([
                    'militare_id' => $militare->id,
                    'tipo' => $tipo,
                    'data_ottenimento' => $dataOttenimento,
                    'data_scadenza' => $dataScadenza,
                    'note' => $note,
                ]);
            }
        }

        // 11. Crea idoneità per ogni militare con stati realistici
        $tipiIdoneita = ['idoneita', 'idoneita_mansione', 'idoneita_smi'];
        
        foreach ($militariCreati as $militare) {
            foreach ($tipiIdoneita as $tipo) {
                // SMI solo per alcuni ruoli specifici
                if ($tipo == 'idoneita_smi' && !in_array($militare->mansione_id, [3, 6, 7, 9, 10])) {
                    continue;
                }
                
                // Logica realistica per stati idoneità:
                // 70% attive, 20% in scadenza, 8% scadute, 2% non presenti
                $rand = rand(1, 100);
                
                if ($rand <= 2) {
                    // 2% non presenti - skip questa idoneità
                    continue;
                } elseif ($rand <= 10) {
                    // 8% scadute (scadute da 1-6 mesi)
                    $mesiScaduti = rand(1, 6);
                    $dataOttenimento = Carbon::now()->subMonths(12 + $mesiScaduti); // 1 anno + mesi scaduti
                    $dataScadenza = $dataOttenimento->copy()->addYear();
                    $note = '🚨 IDONEITÀ SCADUTA da ' . $mesiScaduti . ' mesi - VISITA MEDICA URGENTE';
                } elseif ($rand <= 30) {
                    // 20% in scadenza (prossimi 1-3 mesi)
                    $mesiAllaScadenza = rand(1, 3);
                    $dataOttenimento = Carbon::now()->subMonths(12 - $mesiAllaScadenza); // 1 anno - mesi alla scadenza
                    $dataScadenza = $dataOttenimento->copy()->addYear();
                    $note = '⏰ Idoneità scade tra ' . $mesiAllaScadenza . ' mesi - Prenotare visita medica entro ' . $dataScadenza->subWeeks(2)->format('d/m/Y');
                } else {
                    // 70% attive (valide per 1-11 mesi)
                    $mesiValidi = rand(1, 11);
                    $dataOttenimento = Carbon::now()->subMonths(12 - $mesiValidi); // 1 anno - mesi validi
                    $dataScadenza = $dataOttenimento->copy()->addYear();
                    $note = '✅ Idoneità valida fino al ' . $dataScadenza->format('d/m/Y') . ' (' . $mesiValidi . ' mesi rimanenti) - Controlli regolari';
                }
                
                Idoneita::create([
                    'militare_id' => $militare->id,
                    'tipo' => $tipo,
                    'data_ottenimento' => $dataOttenimento,
                    'data_scadenza' => $dataScadenza,
                    'note' => $note,
                ]);
            }
        }

        // 12. Crea valutazioni per ogni militare con aree di miglioramento
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

        // 13. Crea alcuni eventi per i militari
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

        // 14. Crea presenze per l'ultimo mese
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

        $this->command->info('✅ Database popolato con successo!');
        $this->command->info('📊 Creati:');
        $this->command->info('   - 20 militari con foto profilo');
        $this->command->info('   - 3 plotoni operativi');
        $this->command->info('   - 7 poli specialistici');
        $this->command->info('   - Certificati e idoneità complete');
        $this->command->info('   - Valutazioni e note realistiche');
        $this->command->info('   - Eventi e presenze storiche');
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
            // Competenze tecniche
            'AREA TECNICA: Necessario approfondimento sui nuovi sistemi informatici. Consigliato corso di aggiornamento entro il trimestre.',
            'COMPETENZE DIGITALI: Da migliorare l\'utilizzo degli strumenti software avanzati. Previsto affiancamento con personale esperto.',
            'PROCEDURE OPERATIVE: Richiede maggiore familiarità con le procedure di emergenza. Pianificata sessione di training specifico.',
            
            // Comunicazione e relazioni
            'COMUNICAZIONE: Sviluppare maggiore chiarezza nell\'esposizione durante i briefing. Suggerito corso di public speaking.',
            'LEADERSHIP: Potenziare le capacità di coordinamento del team nei progetti complessi. Previsto mentoring con superiori.',
            'COLLABORAZIONE: Migliorare l\'integrazione con altri reparti. Organizzare incontri interdisciplinari.',
            
            // Organizzazione e metodo
            'GESTIONE TEMPO: Ottimizzare la pianificazione delle attività quotidiane. Implementare strumenti di time management.',
            'PRECISIONE: Aumentare l\'attenzione ai dettagli nelle verifiche finali. Istituire checklist di controllo.',
            'DOCUMENTAZIONE: Migliorare la completezza nella redazione dei rapporti. Fornire template standardizzati.',
            
            // Iniziativa e proattività
            'PROATTIVITÀ: Incoraggiare maggiore iniziativa personale nei progetti di miglioramento. Assegnare responsabilità specifiche.',
            'PROBLEM SOLVING: Sviluppare approccio più sistematico nella risoluzione dei problemi. Corso di metodologie analitiche.',
            'AGGIORNAMENTO: Mantenere costante l\'aggiornamento professionale. Pianificare partecipazione a seminari settoriali.',
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
