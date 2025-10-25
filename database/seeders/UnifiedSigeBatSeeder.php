<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UnifiedSigeBatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Questo seeder popola il database unificato con dati di esempio basati
     * su entrambe le versioni del database (sige_batUmberto e sige_batMichele).
     *
     * @return void
     */
    public function run()
    {
        $this->seedGradi();
        $this->seedCompagnie();
        $this->seedPlotoni();
        $this->seedPoli();
        $this->seedMansioni();
        $this->seedRuoli();
        $this->seedMilitari();
        // $this->seedCertificatiLavoratori(); // DEPRECATO - tabelle rimosse
        // $this->seedIdoneita(); // DEPRECATO - tabelle rimosse
    }

    /**
     * Seed gradi table
     */
    private function seedGradi()
    {
        $gradi = [
            ['nome' => 'C.le', 'ordine' => 1, 'abbreviazione' => 'C.le'],
            ['nome' => 'C.le Magg.', 'ordine' => 2, 'abbreviazione' => 'C.M.'],
            ['nome' => 'C.le Magg. Sc.', 'ordine' => 3, 'abbreviazione' => 'C.M.S.'],
            ['nome' => 'C.le Magg. Ca.', 'ordine' => 4, 'abbreviazione' => 'C.M.C.'],
            ['nome' => 'Sergente', 'ordine' => 5, 'abbreviazione' => 'Serg.'],
            ['nome' => 'Sergente Magg.', 'ordine' => 6, 'abbreviazione' => 'Serg. M.'],
            ['nome' => 'Sergente Magg. Ca.', 'ordine' => 7, 'abbreviazione' => 'Serg. M.C.'],
            ['nome' => 'Maresciallo', 'ordine' => 8, 'abbreviazione' => 'Mar.'],
            ['nome' => 'Maresciallo Ord.', 'ordine' => 9, 'abbreviazione' => 'Mar. Ord.'],
            ['nome' => 'Maresciallo Capo', 'ordine' => 10, 'abbreviazione' => 'Mar. Ca.'],
            ['nome' => 'Primo Maresciallo', 'ordine' => 11, 'abbreviazione' => '1° Mar.'],
            ['nome' => 'Luogotenente', 'ordine' => 12, 'abbreviazione' => 'Lgt.'],
            ['nome' => 'Sottotenente', 'ordine' => 13, 'abbreviazione' => 'S.Ten.'],
            ['nome' => 'Tenente', 'ordine' => 14, 'abbreviazione' => 'Ten.'],
            ['nome' => 'Capitano', 'ordine' => 15, 'abbreviazione' => 'Cap.'],
            ['nome' => 'Maggiore', 'ordine' => 16, 'abbreviazione' => 'Magg.'],
            ['nome' => 'Tenente Colonnello', 'ordine' => 17, 'abbreviazione' => 'Ten. Col.'],
            ['nome' => 'Colonnello', 'ordine' => 18, 'abbreviazione' => 'Col.'],
        ];

        foreach ($gradi as $grado) {
            DB::table('gradi')->insert([
                'nome' => $grado['nome'],
                'ordine' => $grado['ordine'],
                'abbreviazione' => $grado['abbreviazione'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed compagnie table
     */
    private function seedCompagnie()
    {
        $compagnie = [
            ['nome' => '1° Compagnia', 'descrizione' => 'Prima compagnia operativa', 'codice' => 'C1'],
            ['nome' => 'Compagnia Trasmissioni', 'descrizione' => 'Specializzata in comunicazioni tattiche', 'codice' => 'CT'],
        ];

        foreach ($compagnie as $compagnia) {
            DB::table('compagnie')->insert([
                'nome' => $compagnia['nome'],
                'descrizione' => $compagnia['descrizione'],
                'codice' => $compagnia['codice'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed plotoni table
     */
    private function seedPlotoni()
    {
        $plotoni = [
            ['nome' => 'Primo Plotone', 'compagnia_id' => 1, 'descrizione' => 'Plotone di comando della 1° Compagnia'],
            ['nome' => 'Secondo Plotone', 'compagnia_id' => 1, 'descrizione' => 'Plotone operativo della 1° Compagnia'],
            ['nome' => 'Terzo Plotone', 'compagnia_id' => 1, 'descrizione' => 'Plotone logistico della 1° Compagnia'],
            ['nome' => 'Plotone Comando', 'compagnia_id' => 2, 'descrizione' => 'Plotone di comando della Compagnia Trasmissioni'],
            ['nome' => 'Plotone Trasmissioni', 'compagnia_id' => 2, 'descrizione' => 'Plotone specializzato in comunicazioni'],
            ['nome' => 'Plotone Supporto', 'compagnia_id' => 2, 'descrizione' => 'Plotone di supporto logistico'],
            ['nome' => 'PLOTONE TEST', 'compagnia_id' => 2, 'descrizione' => 'Plotone per test e simulazioni'], // Da Michele
        ];

        foreach ($plotoni as $plotone) {
            DB::table('plotoni')->insert([
                'nome' => $plotone['nome'],
                'compagnia_id' => $plotone['compagnia_id'],
                'descrizione' => $plotone['descrizione'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed poli table
     */
    private function seedPoli()
    {
        $poli = [
            ['nome' => 'Polo Amministrativo', 'compagnia_id' => 1, 'descrizione' => 'Polo per gestione amministrativa'],
            ['nome' => 'Polo Logistico', 'compagnia_id' => 1, 'descrizione' => 'Polo per gestione logistica'],
            ['nome' => 'Polo Operativo', 'compagnia_id' => 1, 'descrizione' => 'Polo per operazioni tattiche'],
            ['nome' => 'Polo Operativo', 'compagnia_id' => 2, 'descrizione' => 'Polo per operazioni di comunicazione'],
            ['nome' => 'Polo Logistico', 'compagnia_id' => 2, 'descrizione' => 'Polo per supporto logistico'],
            ['nome' => 'Polo Addestrativo', 'compagnia_id' => 2, 'descrizione' => 'Polo per addestramento personale'],
        ];

        foreach ($poli as $polo) {
            DB::table('poli')->insert([
                'nome' => $polo['nome'],
                'compagnia_id' => $polo['compagnia_id'],
                'descrizione' => $polo['descrizione'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed mansioni table
     */
    private function seedMansioni()
    {
        $mansioni = [
            ['nome' => 'Operatore SATCOM', 'descrizione' => 'Specialista nelle comunicazioni satellitari'],
            ['nome' => 'Autista Mezzi Tattici e Speciali', 'descrizione' => 'Conduttore di veicoli militari tattici e speciali'],
            ['nome' => 'Radiofonista', 'descrizione' => 'Specialista nelle comunicazioni radio'],
            ['nome' => 'Operatore Reti e Infrastrutture di Comunicazione', 'descrizione' => 'Gestione di reti e infrastrutture di comunicazione'],
            ['nome' => 'Manutentore', 'descrizione' => 'Tecnico per la manutenzione di apparati e mezzi'],
            ['nome' => 'Operatore Cyber', 'descrizione' => 'Specialista in sicurezza informatica e operazioni cyber'],
        ];

        foreach ($mansioni as $mansione) {
            DB::table('mansioni')->insert([
                'nome' => $mansione['nome'],
                'descrizione' => $mansione['descrizione'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed ruoli table
     */
    private function seedRuoli()
    {
        $ruoli = [
            ['nome' => 'Lavoratore', 'descrizione' => 'Ruolo base'],
            ['nome' => 'Preposto', 'descrizione' => 'Supervisore diretto dei lavoratori'],
            ['nome' => 'Dirigente', 'descrizione' => 'Responsabile dell\'organizzazione'],
        ];

        foreach ($ruoli as $ruolo) {
            DB::table('ruoli')->insert([
                'nome' => $ruolo['nome'],
                'descrizione' => $ruolo['descrizione'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed militari table - unisce dati da entrambe le versioni
     */
    private function seedMilitari()
    {
        $militari = [
            [
                'nome' => 'Marco', 
                'cognome' => 'Rossi', 
                'grado_id' => 15,
                'compagnia_id' => 2, 
                'plotone_id' => 1, 
                'polo_id' => 1, 
                'ruolo_id' => 3, 
                'mansione_id' => 1,
                'ruolo' => 'Dirigente',
                'certificati_note' => 'Tutti i certificati aggiornati',
                'idoneita_note' => 'Idoneità complete',
                'data_nascita' => '1980-05-15',
                'codice_fiscale' => 'RSSMRC80E15H501A',
                'email' => 'marco.rossi@esercito.it',
                'telefono' => '3471234567',
                'note' => 'Comandante di plotone'
            ],
            [
                'nome' => 'Giuseppe', 
                'cognome' => 'Bianchi', 
                'grado_id' => 7,
                'compagnia_id' => 2, 
                'plotone_id' => 2, 
                'polo_id' => 1, 
                'ruolo_id' => 2, 
                'mansione_id' => 2,
                'ruolo' => 'Preposto',
                'certificati_note' => 'In attesa di rinnovo corso preposti',
                'idoneita_note' => 'PEFO da rinnovare',
                'data_nascita' => '1985-08-22',
                'codice_fiscale' => 'BNCGPP85M22H501B',
                'email' => 'giuseppe.bianchi@esercito.it',
                'telefono' => '3482345678',
                'note' => null
            ],
            [
                'nome' => 'Paolo', 
                'cognome' => 'Verdi', 
                'grado_id' => 4,
                'compagnia_id' => 2, 
                'plotone_id' => 3, 
                'polo_id' => 2, 
                'ruolo_id' => 1, 
                'mansione_id' => 3,
                'ruolo' => 'Lavoratore',
                'certificati_note' => 'Corso 8h completato',
                'idoneita_note' => 'Tutte le idoneità valide',
                'data_nascita' => '1990-03-10',
                'codice_fiscale' => 'VRDPLA90C10H501C',
                'email' => 'paolo.verdi@esercito.it',
                'telefono' => '3493456789',
                'note' => null
            ],
            [
                'nome' => 'Andrea', 
                'cognome' => 'Ferrari', 
                'grado_id' => 1,
                'compagnia_id' => 2, 
                'plotone_id' => 2, 
                'polo_id' => 1, 
                'ruolo_id' => 1, 
                'mansione_id' => 4,
                'ruolo' => 'Lavoratore',
                'certificati_note' => 'Da completare corso 8h',
                'idoneita_note' => 'Idoneità SMI in scadenza',
                'data_nascita' => '1992-11-05',
                'codice_fiscale' => 'FRRNDR92S05H501D',
                'email' => 'andrea.ferrari@esercito.it',
                'telefono' => '3504567890',
                'note' => 'In formazione'
            ],
            [
                'nome' => 'Luca', 
                'cognome' => 'Romano', 
                'grado_id' => 8,
                'compagnia_id' => 2, 
                'plotone_id' => 1, 
                'polo_id' => 3, 
                'ruolo_id' => 2, 
                'mansione_id' => 5,
                'ruolo' => 'Preposto',
                'certificati_note' => 'Corsi completi',
                'idoneita_note' => 'Idoneità valide',
                'data_nascita' => '1982-07-18',
                'codice_fiscale' => 'RMNLCU82L18H501E',
                'email' => 'luca.romano@esercito.it',
                'telefono' => '3515678901',
                'note' => null
            ],
            [
                'nome' => 'Fabio', 
                'cognome' => 'Marino', 
                'grado_id' => 14,
                'compagnia_id' => 2, 
                'plotone_id' => 3, 
                'polo_id' => 2, 
                'ruolo_id' => 3, 
                'mansione_id' => 6,
                'ruolo' => 'Dirigente',
                'certificati_note' => 'Aggiornamento dirigenti programmato',
                'idoneita_note' => 'Idoneità complete',
                'data_nascita' => '1975-04-30',
                'codice_fiscale' => 'MRNFBA75D30H501F',
                'email' => 'fabio.marino@esercito.it',
                'telefono' => '3526789012',
                'note' => 'Responsabile sicurezza'
            ],
            [
                'nome' => 'Matteo', 
                'cognome' => 'Greco', 
                'grado_id' => 2,
                'compagnia_id' => 2, 
                'plotone_id' => 2, 
                'polo_id' => 1, 
                'ruolo_id' => 1, 
                'mansione_id' => 1,
                'ruolo' => 'Lavoratore',
                'certificati_note' => 'Corso 4h completato',
                'idoneita_note' => 'PEFO da rinnovare',
                'data_nascita' => '1995-09-12',
                'codice_fiscale' => 'GRCMTT95P12H501G',
                'email' => 'matteo.greco@esercito.it',
                'telefono' => '3537890123',
                'note' => 'Specialista SATCOM junior'
            ],
            [
                'nome' => 'Davide', 
                'cognome' => 'Conti', 
                'grado_id' => 6,
                'compagnia_id' => 2, 
                'plotone_id' => 1, 
                'polo_id' => 3, 
                'ruolo_id' => 2, 
                'mansione_id' => 2,
                'ruolo' => 'Preposto',
                'certificati_note' => 'Corso preposti in scadenza',
                'idoneita_note' => 'Idoneità mansione da rinnovare',
                'data_nascita' => '1988-02-25',
                'codice_fiscale' => 'CNTDVD88B25H501H',
                'email' => 'davide.conti@esercito.it',
                'telefono' => '3548901234',
                'note' => 'Preposto ai mezzi tattici'
            ],
            [
                'nome' => 'Alessandro', 
                'cognome' => 'Mancini', 
                'grado_id' => 3,
                'compagnia_id' => 2, 
                'plotone_id' => 3, 
                'polo_id' => 2, 
                'ruolo_id' => 1, 
                'mansione_id' => 3,
                'ruolo' => 'Lavoratore',
                'certificati_note' => 'Corsi base completati',
                'idoneita_note' => 'Tutte le idoneità valide',
                'data_nascita' => '1993-12-07',
                'codice_fiscale' => 'MNCLSN93T07H501I',
                'email' => 'alessandro.mancini@esercito.it',
                'telefono' => '3559012345',
                'note' => null
            ],
            [
                'nome' => 'Roberto', 
                'cognome' => 'Ricci', 
                'grado_id' => 10,
                'compagnia_id' => 2, 
                'plotone_id' => 1, 
                'polo_id' => 1, 
                'ruolo_id' => 2, 
                'mansione_id' => 4,
                'ruolo' => 'Preposto',
                'certificati_note' => 'Aggiornamento preposti completato',
                'idoneita_note' => 'Idoneità in regola',
                'data_nascita' => '1983-06-20',
                'codice_fiscale' => 'RCCRRT83H20H501J',
                'email' => 'roberto.ricci@esercito.it',
                'telefono' => '3560123456',
                'note' => 'Preposto alle infrastrutture di comunicazione'
            ],
            [
                'nome' => 'Michele', 
                'cognome' => 'Di Gennaro', 
                'grado_id' => 14,
                'compagnia_id' => 2, 
                'plotone_id' => 1, 
                'polo_id' => null, 
                'ruolo_id' => 3, 
                'mansione_id' => null,
                'ruolo' => 'Dirigente',
                'certificati_note' => null,
                'idoneita_note' => null,
                'data_nascita' => '1978-01-15',
                'codice_fiscale' => 'DGNMHL78A15H501K',
                'email' => 'michele.digennaro@esercito.it',
                'telefono' => '3571234567',
                'note' => 'U meghj'
            ],
        ];

        foreach ($militari as $militare) {
            DB::table('militari')->insert([
                'nome' => $militare['nome'],
                'cognome' => $militare['cognome'],
                'grado_id' => $militare['grado_id'],
                'compagnia_id' => $militare['compagnia_id'],
                'plotone_id' => $militare['plotone_id'],
                'polo_id' => $militare['polo_id'],
                'ruolo_id' => $militare['ruolo_id'],
                'mansione_id' => $militare['mansione_id'],
                'ruolo' => $militare['ruolo'],
                'certificati_note' => $militare['certificati_note'],
                'idoneita_note' => $militare['idoneita_note'],
                'data_nascita' => $militare['data_nascita'],
                'codice_fiscale' => $militare['codice_fiscale'],
                'email' => $militare['email'],
                'telefono' => $militare['telefono'],
                'note' => $militare['note'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed certificati_lavoratori table
     * Utilizza una combinazione di dati provenienti da entrambe le versioni
     * con date più recenti e dettagli migliorati
     */
    private function seedCertificatiLavoratori()
    {
        $certificati = [
            ['militare_id' => 1, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2022-01-13', 'data_scadenza' => '2025-01-26', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 1, 'tipo' => 'corsi_lavoratori_preposti', 'data_ottenimento' => '2022-10-13', 'data_scadenza' => '2027-10-13', 'file_path' => null, 'note' => 'Note per il certificato corsi_lavoratori_preposti', 'in_scadenza' => false],
            ['militare_id' => 1, 'tipo' => 'corsi_lavoratori_dirigenti', 'data_ottenimento' => '2022-01-13', 'data_scadenza' => '2025-01-26', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_dirigenti', 'in_scadenza' => false],
            ['militare_id' => 2, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2023-05-13', 'data_scadenza' => '2025-04-01', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 2, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2022-01-13', 'data_scadenza' => '2025-01-05', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 2, 'tipo' => 'corsi_lavoratori_preposti', 'data_ottenimento' => '2023-07-13', 'data_scadenza' => '2025-03-25', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_preposti', 'in_scadenza' => false],
            ['militare_id' => 3, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2021-01-13', 'data_scadenza' => '2025-03-20', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 3, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2023-07-13', 'data_scadenza' => '2024-11-11', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => true],
            ['militare_id' => 4, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2023-05-13', 'data_scadenza' => '2025-02-27', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 5, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2023-08-13', 'data_scadenza' => '2028-08-13', 'file_path' => null, 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 5, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2024-08-13', 'data_scadenza' => '2025-03-21', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 5, 'tipo' => 'corsi_lavoratori_preposti', 'data_ottenimento' => '2022-07-13', 'data_scadenza' => '2027-07-13', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_preposti', 'in_scadenza' => false],
            ['militare_id' => 6, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2024-08-13', 'data_scadenza' => '2025-01-09', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 6, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2024-02-13', 'data_scadenza' => '2025-03-30', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 6, 'tipo' => 'corsi_lavoratori_preposti', 'data_ottenimento' => '2023-06-13', 'data_scadenza' => '2024-11-27', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_preposti', 'in_scadenza' => true],
            ['militare_id' => 6, 'tipo' => 'corsi_lavoratori_dirigenti', 'data_ottenimento' => '2024-01-13', 'data_scadenza' => '2025-03-01', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_dirigenti', 'in_scadenza' => false],
            ['militare_id' => 7, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2023-11-13', 'data_scadenza' => '2028-11-13', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 8, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2021-05-13', 'data_scadenza' => '2024-12-14', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => true],
            ['militare_id' => 8, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2022-12-13', 'data_scadenza' => '2025-03-18', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 9, 'tipo' => 'corsi_lavoratori_4h', 'data_ottenimento' => '2024-12-13', 'data_scadenza' => '2029-12-13', 'file_path' => null, 'note' => 'Note per il certificato corsi_lavoratori_4h', 'in_scadenza' => false],
            ['militare_id' => 9, 'tipo' => 'corsi_lavoratori_8h', 'data_ottenimento' => '2023-11-13', 'data_scadenza' => '2025-04-11', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_8h', 'in_scadenza' => false],
            ['militare_id' => 10, 'tipo' => 'corsi_lavoratori_preposti', 'data_ottenimento' => '2024-01-13', 'data_scadenza' => '2025-03-30', 'file_path' => 'certificati/esempio.pdf', 'note' => 'Note per il certificato corsi_lavoratori_preposti', 'in_scadenza' => false],
        ];

        foreach ($certificati as $certificato) {
            // Calcola se in scadenza (o imposta manualmente per i test)
            $in_scadenza = $certificato['in_scadenza'];
            if (!isset($certificato['in_scadenza'])) {
                $scadenza = Carbon::parse($certificato['data_scadenza']);
                $oggi = Carbon::now();
                $in_scadenza = $scadenza->diffInMonths($oggi) <= 3 && $scadenza->greaterThan($oggi);
            }

            DB::table('certificati_lavoratori')->insert([
                'militare_id' => $certificato['militare_id'],
                'tipo' => $certificato['tipo'],
                'data_ottenimento' => $certificato['data_ottenimento'],
                'data_scadenza' => $certificato['data_scadenza'],
                'file_path' => $certificato['file_path'],
                'note' => $certificato['note'],
                'in_scadenza' => $in_scadenza,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Seed idoneita table
     * Utilizza una combinazione di dati provenienti da entrambe le versioni
     * con date più recenti e dettagli migliorati
     */
    private function seedIdoneita()
    {
        $idoneita = [
            ['militare_id' => 1, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2023-10-13', 'data_scadenza' => '2024-11-29', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => true],
            ['militare_id' => 1, 'tipo' => 'idoneita', 'data_ottenimento' => '2023-10-13', 'data_scadenza' => '2025-03-03', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => false],
            ['militare_id' => 2, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2023-11-13', 'data_scadenza' => '2024-11-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_mansione', 'in_scadenza' => true],
            ['militare_id' => 2, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-09-13', 'data_scadenza' => '2025-09-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 3, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-09-13', 'data_scadenza' => '2025-04-12', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 3, 'tipo' => 'idoneita', 'data_ottenimento' => '2024-03-13', 'data_scadenza' => '2025-03-29', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => false],
            ['militare_id' => 4, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-07-13', 'data_scadenza' => '2025-07-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_mansione', 'in_scadenza' => false],
            ['militare_id' => 4, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-10-13', 'data_scadenza' => '2025-10-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 5, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-06-13', 'data_scadenza' => '2024-11-26', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_mansione', 'in_scadenza' => true],
            ['militare_id' => 5, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-07-13', 'data_scadenza' => '2025-03-29', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 6, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-06-13', 'data_scadenza' => '2025-03-19', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_mansione', 'in_scadenza' => false],
            ['militare_id' => 6, 'tipo' => 'idoneita', 'data_ottenimento' => '2025-01-13', 'data_scadenza' => '2026-01-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => false],
            ['militare_id' => 7, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-09-13', 'data_scadenza' => '2025-01-12', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_mansione', 'in_scadenza' => true],
            ['militare_id' => 7, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-10-13', 'data_scadenza' => '2025-10-13', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 7, 'tipo' => 'idoneita', 'data_ottenimento' => '2023-09-13', 'data_scadenza' => '2024-12-02', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => true],
            ['militare_id' => 8, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-04-13', 'data_scadenza' => '2025-04-13', 'file_path' => null, 'note' => 'Note per idoneita_mansione', 'in_scadenza' => false],
            ['militare_id' => 8, 'tipo' => 'idoneita', 'data_ottenimento' => '2024-11-13', 'data_scadenza' => '2025-03-28', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => false],
            ['militare_id' => 9, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2023-10-13', 'data_scadenza' => '2024-10-13', 'file_path' => null, 'note' => 'Note per idoneita_smi', 'in_scadenza' => true],
            ['militare_id' => 9, 'tipo' => 'idoneita', 'data_ottenimento' => '2024-05-13', 'data_scadenza' => '2025-05-13', 'file_path' => null, 'note' => 'Note per idoneita', 'in_scadenza' => false],
            ['militare_id' => 10, 'tipo' => 'idoneita_mansione', 'data_ottenimento' => '2024-01-13', 'data_scadenza' => '2025-01-13', 'file_path' => null, 'note' => 'Note per idoneita_mansione', 'in_scadenza' => true],
            ['militare_id' => 10, 'tipo' => 'idoneita_smi', 'data_ottenimento' => '2024-05-13', 'data_scadenza' => '2025-03-18', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita_smi', 'in_scadenza' => false],
            ['militare_id' => 10, 'tipo' => 'idoneita', 'data_ottenimento' => '2024-04-13', 'data_scadenza' => '2025-04-07', 'file_path' => 'idoneita/esempio.pdf', 'note' => 'Note per idoneita', 'in_scadenza' => false],
        ];

        foreach ($idoneita as $record) {
            // Calcola se in scadenza (o imposta manualmente per i test)
            $in_scadenza = $record['in_scadenza'];
            if (!isset($record['in_scadenza'])) {
                $scadenza = Carbon::parse($record['data_scadenza']);
                $oggi = Carbon::now();
                $in_scadenza = $scadenza->diffInMonths($oggi) <= 3 && $scadenza->greaterThan($oggi);
            }

            DB::table('idoneita')->insert([
                'militare_id' => $record['militare_id'],
                'tipo' => $record['tipo'],
                'data_ottenimento' => $record['data_ottenimento'],
                'data_scadenza' => $record['data_scadenza'],
                'file_path' => $record['file_path'],
                'note' => $record['note'],
                'in_scadenza' => $in_scadenza,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
