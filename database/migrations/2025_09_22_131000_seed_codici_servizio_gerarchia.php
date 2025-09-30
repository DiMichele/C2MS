<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per popolare la gerarchia dei codici servizio dalla pagina CODICI del file Excel
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Popolare la tabella con i codici dalla pagina CODICI del file Excel
        $codici = [
            // DISPONIBILE
            [
                'codice' => '.',
                'macro_attivita' => 'DISPONIBILE',
                'tipo_attivita' => 'NULL',
                'attivita_specifica' => 'Disponibilità generica',
                'impiego' => 'DISPONIBILE',
                'descrizione_impiego' => 'DISPONIBILITÀ TOTALE',
                'colore_badge' => '#28a745',
                'ordine' => 1
            ],
            
            // ASSENTE - LICENZE
            [
                'codice' => 'ls',
                'macro_attivita' => 'ASSENTE',
                'tipo_attivita' => 'LICENZA',
                'attivita_specifica' => 'LICENZA STRAORD.',
                'impiego' => 'INDISPONIBILE',
                'descrizione_impiego' => 'INDISPONIBILE MA RICHIAMABILE SU ESIGENZA',
                'colore_badge' => '#ffc107',
                'ordine' => 10
            ],
            [
                'codice' => 'lsds',
                'macro_attivita' => 'ASSENTE',
                'tipo_attivita' => 'LICENZA',
                'attivita_specifica' => 'LICENZA STRAORD. DISPENSA DAL SERVIZIO',
                'impiego' => 'INDISPONIBILE',
                'descrizione_impiego' => 'INDISPONIBILE MA RICHIAMABILE SU ESIGENZA',
                'colore_badge' => '#ffc107',
                'ordine' => 11
            ],
            [
                'codice' => 'lo',
                'macro_attivita' => 'ASSENTE',
                'tipo_attivita' => 'LICENZA',
                'attivita_specifica' => 'LICENZA ORD.',
                'impiego' => 'INDISPONIBILE',
                'descrizione_impiego' => 'INDISPONIBILE MA RICHIAMABILE SU ESIGENZA',
                'colore_badge' => '#17a2b8',
                'ordine' => 12
            ],
            [
                'codice' => 'lm',
                'macro_attivita' => 'ASSENTE',
                'tipo_attivita' => 'LICENZA',
                'attivita_specifica' => 'LICENZA DI MATERNITÀ',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#e83e8c',
                'ordine' => 13
            ],
            
            // ASSENTE - PERMESSI
            [
                'codice' => 'p',
                'macro_attivita' => 'ASSENTE',
                'tipo_attivita' => 'PERMESSO',
                'attivita_specifica' => 'PERMESSINO',
                'impiego' => 'INDISPONIBILE',
                'descrizione_impiego' => 'INDISPONIBILE MA RICHIAMABILE SU ESIGENZA',
                'colore_badge' => '#fd7e14',
                'ordine' => 20
            ],
            
            // PROVVEDIMENTI MEDICO SANITARI
            [
                'codice' => 'RMD',
                'macro_attivita' => 'PROVVEDIMENTI MEDICO SANITARI',
                'tipo_attivita' => 'MEDICO',
                'attivita_specifica' => 'RIPOSO MEDICO DOMICILIARE',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#dc3545',
                'ordine' => 30
            ],
            [
                'codice' => 'lc',
                'macro_attivita' => 'PROVVEDIMENTI MEDICO SANITARI',
                'tipo_attivita' => 'MEDICO',
                'attivita_specifica' => 'LICENZA DI CONVALESCENZA',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#dc3545',
                'ordine' => 31
            ],
            [
                'codice' => 'is',
                'macro_attivita' => 'PROVVEDIMENTI MEDICO SANITARI',
                'tipo_attivita' => 'MEDICO',
                'attivita_specifica' => 'ISOLAMENTO/QUARANTENA',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#dc3545',
                'ordine' => 32
            ],
            [
                'codice' => 'fp',
                'macro_attivita' => 'PROVVEDIMENTI MEDICO SANITARI',
                'tipo_attivita' => 'MEDICO',
                'attivita_specifica' => 'FORZA POTENZIALE',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#dc3545',
                'ordine' => 33
            ],
            
            // SERVIZIO - Guardie e servizi
            [
                'codice' => 'S-G1',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'GUARDIA',
                'attivita_specifica' => 'GUARDIA D\'AVANZO LUNGA',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#007bff',
                'ordine' => 40
            ],
            [
                'codice' => 'S-G2',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'GUARDIA',
                'attivita_specifica' => 'GUARDIA D\'AVANZO CORTA',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#007bff',
                'ordine' => 41
            ],
            [
                'codice' => 'S-SA',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'GUARDIA',
                'attivita_specifica' => 'SORVEGLIANZA D\'AVANZO',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#007bff',
                'ordine' => 42
            ],
            [
                'codice' => 'S-SG',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'COMANDO',
                'attivita_specifica' => 'SOTTUFFICIALE DI GIORNATA',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#6f42c1',
                'ordine' => 50
            ],
            [
                'codice' => 'S-CG',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'COMANDO',
                'attivita_specifica' => 'COMANDANTE DELLA GUARDIA',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#6f42c1',
                'ordine' => 51
            ],
            [
                'codice' => 'S-UI',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'COMANDO',
                'attivita_specifica' => 'UFFICIALE DI ISPEZIONE',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#6f42c1',
                'ordine' => 52
            ],
            [
                'codice' => 'S-UP',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'COMANDO',
                'attivita_specifica' => 'UFFICIALE DI PICCHETTO',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#6f42c1',
                'ordine' => 53
            ],
            
            // SERVIZIO ISOLATO
            [
                'codice' => 'SI',
                'macro_attivita' => 'SERVIZIO',
                'tipo_attivita' => 'ISOLATO',
                'attivita_specifica' => 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU',
                'impiego' => 'PRESENTE_SERVIZIO',
                'descrizione_impiego' => 'PRESENTE ED IN SERVIZIO COMANDATO',
                'colore_badge' => '#20c997',
                'ordine' => 60
            ],
            
            // OPERAZIONE
            [
                'codice' => 'TO',
                'macro_attivita' => 'OPERAZIONE',
                'tipo_attivita' => 'OPERAZIONE',
                'attivita_specifica' => 'TURNO ORDINARIO',
                'impiego' => 'NON_DISPONIBILE',
                'descrizione_impiego' => 'NON DISPONIBILE',
                'colore_badge' => '#28a745',
                'ordine' => 70
            ],
            
            // ADDESTRAMENTO - MCM e C-IED
            [
                'codice' => 'AL-MCM',
                'macro_attivita' => 'ADD./APP.',
                'tipo_attivita' => 'LEZIONE',
                'attivita_specifica' => 'MCM',
                'impiego' => 'DISPONIBILE_ESIGENZA',
                'descrizione_impiego' => 'DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#ffc107',
                'ordine' => 80
            ],
            [
                'codice' => 'AL-CIED',
                'macro_attivita' => 'ADD./APP.',
                'tipo_attivita' => 'LEZIONE',
                'attivita_specifica' => 'C-IED',
                'impiego' => 'DISPONIBILE_ESIGENZA',
                'descrizione_impiego' => 'DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#ffc107',
                'ordine' => 81
            ],
            
            // POLIGONO
            [
                'codice' => 'AP-M',
                'macro_attivita' => 'ADD./APP.',
                'tipo_attivita' => 'POLIGONO',
                'attivita_specifica' => 'MANTENIMENTO',
                'impiego' => 'DISPONIBILE_ESIGENZA',
                'descrizione_impiego' => 'DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fd7e14',
                'ordine' => 90
            ],
            [
                'codice' => 'AP-A',
                'macro_attivita' => 'ADD./APP.',
                'tipo_attivita' => 'POLIGONO',
                'attivita_specifica' => 'APPRONTAMENTO',
                'impiego' => 'DISPONIBILE_ESIGENZA',
                'descrizione_impiego' => 'DISPONIBILE SU ESIGENZA MOTIVATA',
                'colore_badge' => '#fd7e14',
                'ordine' => 91
            ]
        ];

        foreach ($codici as $codice) {
            DB::table('codici_servizio_gerarchia')->insert(array_merge($codice, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('codici_servizio_gerarchia')->truncate();
    }
};
