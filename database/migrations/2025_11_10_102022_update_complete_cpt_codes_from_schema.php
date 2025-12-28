<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiorna completamente i codici CPT secondo lo schema ufficiale.
     * Include tutte le categorie: DISPONIBILE, ASSENTE, PROVVEDIMENTI MEDICO SANITARI, 
     * SERVIZIO, OPERAZIONE, ADD.APP./CATTEDRE, SUPERCISI.
     */
    public function up(): void
    {
        // Disabilita temporaneamente i vincoli di foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Svuota la tabella
        DB::table('tipi_servizio')->truncate();
        
        // Riabilita i vincoli
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Array completo dei tipi servizio secondo lo schema
        $tipiServizio = [
            // ========== DISPONIBILE (Grigio) ==========
            [
                'codice' => 'NULL',
                'nome' => 'DISPONIBILITA\' TOT',
                'descrizione' => 'Disponibile',
                'colore_badge' => '#e9ecef',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 1
            ],
            
            // ========== ASSENTE - LICENZA(I) (Giallo) ==========
            [
                'codice' => 'ls',
                'nome' => 'LICENZA STRAORD. (ls)',
                'descrizione' => 'Licenza straordinaria - Indisponibile ma richiamabile su esigenza',
                'colore_badge' => '#fff3cd',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 10
            ],
            [
                'codice' => 'lsds',
                'nome' => 'LICENZA STRAORD. DISPENSA DAL SERVIZIO (ls)',
                'descrizione' => 'Licenza straordinaria dispensa dal servizio - Indisponibile ma richiamabile su esigenza',
                'colore_badge' => '#fff3cd',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 11
            ],
            [
                'codice' => 'lo',
                'nome' => 'LICENZA ORD. (lo)',
                'descrizione' => 'Licenza ordinaria',
                'colore_badge' => '#fff3cd',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 12
            ],
            [
                'codice' => 'lm',
                'nome' => 'LICENZA DI MATERNITA\' (lm)',
                'descrizione' => 'Licenza di maternità - NON DISPONIBILE',
                'colore_badge' => '#fff3cd',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 13
            ],
            
            // ========== ASSENTE - PERMESSO (p) (Giallo) ==========
            [
                'codice' => 'p',
                'nome' => 'PERMESSINO (p)',
                'descrizione' => 'Permesso',
                'colore_badge' => '#fff3cd',
                'categoria' => 'permesso',
                'attivo' => 1,
                'ordine' => 20
            ],
            
            // ========== PROVVEDIMENTI MEDICO SANITARI (Rosso) ==========
            [
                'codice' => 'RMD',
                'nome' => 'RIPOSO MEDICO DOMICILIARE',
                'descrizione' => 'Riposo medico domiciliare - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 30
            ],
            [
                'codice' => 'lc',
                'nome' => 'LICENZA DI CONVALESCENZA',
                'descrizione' => 'Licenza di convalescenza - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 31
            ],
            [
                'codice' => 'is',
                'nome' => 'ISOLAMENTO QUARANTENA',
                'descrizione' => 'Isolamento quarantena - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 32
            ],
            [
                'codice' => 'fp',
                'nome' => 'FORZA POTENZIALE',
                'descrizione' => 'Forza potenziale - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'assenza',
                'attivo' => 1,
                'ordine' => 33
            ],
            
            // ========== SERVIZIO - ISOLATO (Verde) ==========
            [
                'codice' => 'S-G1',
                'nome' => 'GUARDIA D\'AVANZO LUNGA',
                'descrizione' => 'Servizio isolato - Guardia d\'avanzo lunga - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 40
            ],
            [
                'codice' => 'S-G2',
                'nome' => 'GUARDIA D\'AVANZO CORTA',
                'descrizione' => 'Servizio isolato - Guardia d\'avanzo corta - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 41
            ],
            [
                'codice' => 'S-SA',
                'nome' => 'SORVEGLIANZA D\'AVANZO',
                'descrizione' => 'Servizio isolato - Sorveglianza d\'avanzo - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 42
            ],
            [
                'codice' => 'S-CD1',
                'nome' => 'CONDUTTORE GUARDIA LUNGO',
                'descrizione' => 'Servizio isolato - Conduttore guardia lungo - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 43
            ],
            [
                'codice' => 'S-CD2',
                'nome' => 'CONDUTTORE GUARDIA CORTO',
                'descrizione' => 'Servizio isolato - Conduttore guardia corto - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 44
            ],
            [
                'codice' => 'S-CD3',
                'nome' => 'CONDUTTORE PIAN DEL TERMINE LUNGO',
                'descrizione' => 'Servizio isolato - Conduttore Pian del Termine lungo - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 45
            ],
            [
                'codice' => 'S-CD4',
                'nome' => 'CONDUTTORE PIAN DEL TERMINE CORTO',
                'descrizione' => 'Servizio isolato - Conduttore Pian del Termine corto - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 46
            ],
            [
                'codice' => 'S-SG',
                'nome' => 'SOTTUFFICIALE DI GIORNATA',
                'descrizione' => 'Servizio isolato - Sottufficiale di giornata - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 47
            ],
            [
                'codice' => 'S-CG',
                'nome' => 'COMANDANTE DELLA GUARDIA',
                'descrizione' => 'Servizio isolato - Comandante della guardia - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 48
            ],
            [
                'codice' => 'S-UI',
                'nome' => 'UFFICIALE DI ISPEZIONE',
                'descrizione' => 'Servizio isolato - Ufficiale di ispezione - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 49
            ],
            [
                'codice' => 'S-UP',
                'nome' => 'UFFICIALE DI PICCHETTO',
                'descrizione' => 'Servizio isolato - Ufficiale di picchetto - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 50
            ],
            [
                'codice' => 'S-AE',
                'nome' => 'AREE ESTERNE',
                'descrizione' => 'Servizio isolato - Aree esterne - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 51
            ],
            [
                'codice' => 'S-ARM',
                'nome' => 'ARMIERE DI SERVIZIO',
                'descrizione' => 'Servizio isolato - Armiere di servizio - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 52
            ],
            [
                'codice' => 'SI-GD',
                'nome' => 'SERVIZIO ISOLATO-GUARDIA DISTACCATA',
                'descrizione' => 'Servizio isolato - Guardia distaccata - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 53
            ],
            [
                'codice' => 'SI',
                'nome' => 'SERVIZIO ISOLATO-CAPOMACCHINA CAU ()',
                'descrizione' => 'Servizio isolato - Capomacchina CAU - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 54
            ],
            [
                'codice' => 'SI-VM',
                'nome' => 'VISITA MEDICA',
                'descrizione' => 'Servizio isolato - Visita medica - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 55
            ],
            [
                'codice' => 'S-PI',
                'nome' => 'PRONTO IMPIEGO',
                'descrizione' => 'Servizio isolato - Pronto impiego - PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#d4edda',
                'categoria' => 'servizio',
                'attivo' => 1,
                'ordine' => 56
            ],
            
            // ========== OPERAZIONE (TO, NAME) (Rosso) ==========
            [
                'codice' => 'TO',
                'nome' => 'OPERAZIONE (TO, NAME)',
                'descrizione' => 'Operazione - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'missione',
                'attivo' => 1,
                'ordine' => 60
            ],
            
            // ========== ADD. APP. (A) / CATTEDRE - PROFILASSI SANITARIA (PS) (Giallo) ==========
            [
                'codice' => 'APS1',
                'nome' => 'PRELIEVI (1)',
                'descrizione' => 'Profilassi sanitaria - Prelievi - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 70
            ],
            [
                'codice' => 'APS2',
                'nome' => 'VACCINI (2)',
                'descrizione' => 'Profilassi sanitaria - Vaccini - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 71
            ],
            [
                'codice' => 'APS3',
                'nome' => 'ECG (3)',
                'descrizione' => 'Profilassi sanitaria - ECG - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 72
            ],
            [
                'codice' => 'APS4',
                'nome' => 'IDONEITA (4)',
                'descrizione' => 'Profilassi sanitaria - Idoneità - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 73
            ],
            
            // ========== ADD. APP. (A) / CATTEDRE - LEZIONE (L) (Giallo) ==========
            [
                'codice' => 'AL-ELIX',
                'nome' => 'ELITRASPORTO (ELIX)',
                'descrizione' => 'Lezione - Elitrasporto - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 80
            ],
            [
                'codice' => 'AL-MCM',
                'nome' => 'MCM',
                'descrizione' => 'Lezione - MCM - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 81
            ],
            [
                'codice' => 'AL-BLS',
                'nome' => 'BLS',
                'descrizione' => 'Lezione - BLS - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 82
            ],
            [
                'codice' => 'AL-CIED',
                'nome' => 'C-IED',
                'descrizione' => 'Lezione - C-IED - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 83
            ],
            [
                'codice' => 'AL-SM',
                'nome' => 'STRESS MANAGEMENT',
                'descrizione' => 'Lezione - Stress Management - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 84
            ],
            [
                'codice' => 'AL-RM',
                'nome' => 'RAPPORTO MEDIA',
                'descrizione' => 'Lezione - Rapporto media - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 85
            ],
            [
                'codice' => 'AL-RSPP',
                'nome' => 'RSPP',
                'descrizione' => 'Lezione - RSPP - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 86
            ],
            [
                'codice' => 'AL-LEG',
                'nome' => 'ASPETTI LEGALI (LEG)',
                'descrizione' => 'Lezione - Aspetti legali - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 87
            ],
            [
                'codice' => 'AL-SEA',
                'nome' => 'SEXUAL EXPLOITATION AND ABUSE',
                'descrizione' => 'Lezione - Sexual exploitation and abuse - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 88
            ],
            [
                'codice' => 'AL-MI',
                'nome' => 'MALATTIE INFETTIVE',
                'descrizione' => 'Lezione - Malattie infettive - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 89
            ],
            [
                'codice' => 'AL-PO',
                'nome' => 'PROPAGANDA OSTILE',
                'descrizione' => 'Lezione - Propaganda ostile - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 90
            ],
            [
                'codice' => 'AL-PI',
                'nome' => 'PUBBLICA INFORMAZIONE E COMUNICAZIONE',
                'descrizione' => 'Lezione - Pubblica informazione e comunicazione - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 91
            ],
            
            // ========== ADD. APP. (A) / CATTEDRE - POLIGONO (P) (Giallo) ==========
            [
                'codice' => 'AP-M',
                'nome' => 'MANTENIMENTO',
                'descrizione' => 'Poligono - Mantenimento - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 100
            ],
            [
                'codice' => 'AP-A',
                'nome' => 'APPRONTAMENTO',
                'descrizione' => 'Poligono - Approntamento - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 101
            ],
            
            // ========== ADD. APP. (A) / CATTEDRE - CORSI (C) (Giallo) ==========
            [
                'codice' => 'AC-SW',
                'nome' => 'CORSO IN SMART WORKING (C-SW)',
                'descrizione' => 'Corso in smart working - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 110
            ],
            [
                'codice' => 'AC',
                'nome' => 'CORSO SERVIZIO ISOLATO (C)',
                'descrizione' => 'Corso servizio isolato - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 111
            ],
            
            // ========== ADD. APP. (A) / CATTEDRE - PEFO (Giallo) ==========
            [
                'codice' => 'PEFO',
                'nome' => 'PEFO',
                'descrizione' => 'PEFO - DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fff3cd',
                'categoria' => 'formazione',
                'attivo' => 1,
                'ordine' => 120
            ],
            
            // ========== SUPERCISI-X-ELIXC-G supp (Rosso) ==========
            [
                'codice' => 'SUPERCISI',
                'nome' => 'SUPERCISI-X-ELIXC-G supp',
                'descrizione' => 'Supercisi - NON DISPONIBILE',
                'colore_badge' => '#f8d7da',
                'categoria' => 'missione',
                'attivo' => 1,
                'ordine' => 130
            ],
        ];
        
        // Inserisci tutti i tipi servizio
        foreach ($tipiServizio as $tipo) {
            DB::table('tipi_servizio')->insert([
                'codice' => $tipo['codice'],
                'nome' => $tipo['nome'],
                'descrizione' => $tipo['descrizione'],
                'colore_badge' => $tipo['colore_badge'],
                'categoria' => $tipo['categoria'],
                'attivo' => $tipo['attivo'],
                'ordine' => $tipo['ordine'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non implementato - troppo rischioso eliminare dati storici
        // In caso di necessità, ripristinare da backup
    }
};
