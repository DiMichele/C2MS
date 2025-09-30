<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disabilita i foreign key checks temporaneamente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Svuota le tabelle correlate prima
        DB::table('pianificazioni_giornaliere')->truncate();
        
        // Svuota la tabella tipi_servizio
        DB::table('tipi_servizio')->truncate();
        
        // Riabilita i foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Inserisci i codici corretti dall'immagine fornita
        $tipiServizio = [
            // ASSENTE - Giallo
            ['codice' => 'LS', 'nome' => 'LICENZA STRAORD.', 'descrizione' => 'Licenza straordinaria', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 1],
            ['codice' => 'LO', 'nome' => 'LICENZA ORD.', 'descrizione' => 'Licenza ordinaria', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 2],
            ['codice' => 'LM', 'nome' => 'LICENZA DI MATERNITA\'', 'descrizione' => 'Licenza di maternità', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 3],
            ['codice' => 'P', 'nome' => 'PERMESSINO', 'descrizione' => 'Permessino', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 4],
            ['codice' => 'TIR', 'nome' => 'TIROCINIO', 'descrizione' => 'Tirocinio', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 5],
            ['codice' => 'TRAS', 'nome' => 'TRASFERITO', 'descrizione' => 'Trasferito', 'colore_badge' => '#ffff00', 'categoria' => 'assenza', 'ordine' => 6],
            
            // PROVVEDIMENTI MEDICO SANITARI - Rosso
            ['codice' => 'RMD', 'nome' => 'RIPOSO MEDICO DOMICILIARE', 'descrizione' => 'Riposo medico domiciliare', 'colore_badge' => '#ff0000', 'categoria' => 'assenza', 'ordine' => 7],
            ['codice' => 'LC', 'nome' => 'LICENZA DI CONVALESCENZA', 'descrizione' => 'Licenza di convalescenza', 'colore_badge' => '#ff0000', 'categoria' => 'assenza', 'ordine' => 8],
            ['codice' => 'IS', 'nome' => 'ISOLAMENTO/QUARANTENA', 'descrizione' => 'Isolamento/Quarantena', 'colore_badge' => '#ff0000', 'categoria' => 'assenza', 'ordine' => 9],
            
            // SERVIZIO - Verde
            ['codice' => 'S-G1', 'nome' => 'GUARDIA D\'AVANZO LUNGA', 'descrizione' => 'Guardia d\'avanzo lunga', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 10],
            ['codice' => 'S-G2', 'nome' => 'GUARDIA D\'AVANZO CORTA', 'descrizione' => 'Guardia d\'avanzo corta', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 11],
            ['codice' => 'S-SA', 'nome' => 'SORVEGLIANZA D\'AVANZO', 'descrizione' => 'Sorveglianza d\'avanzo', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 12],
            ['codice' => 'S-CD1', 'nome' => 'CONDUTTORE GUARDIA LUNGO', 'descrizione' => 'Conduttore guardia lungo', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 13],
            ['codice' => 'S-CD2', 'nome' => 'CONDUTTORE PIAN DEL TERMINE CORTO', 'descrizione' => 'Conduttore Pian del Termine corto', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 14],
            ['codice' => 'S-SG', 'nome' => 'SOTTUFFICIALE DI GIORNATA', 'descrizione' => 'Sottufficiale di giornata', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 15],
            ['codice' => 'S-CG', 'nome' => 'COMANDANTE DELLA GUARDIA', 'descrizione' => 'Comandante della guardia', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 16],
            ['codice' => 'S-UI', 'nome' => 'UFFICIALE DI ISPEZIONE', 'descrizione' => 'Ufficiale di ispezione', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 17],
            ['codice' => 'S-UP', 'nome' => 'UFFICIALE DI PICCHETTO', 'descrizione' => 'Ufficiale di picchetto', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 18],
            ['codice' => 'S-AE', 'nome' => 'AREE ESTERNE', 'descrizione' => 'Aree esterne', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 19],
            ['codice' => 'S-ARM', 'nome' => 'ARMIERE DI SERVIZIO', 'descrizione' => 'Armiere di servizio', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 20],
            ['codice' => 'SI-GD', 'nome' => 'SERVIZIO ISOLATO-GUARDIA DISTACCATA', 'descrizione' => 'Servizio isolato - Guardia distaccata', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 21],
            ['codice' => 'SI', 'nome' => 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU', 'descrizione' => 'Servizio isolato - Capomacchina/CAU', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 22],
            ['codice' => 'SI-VM', 'nome' => 'VISITA MEDICA', 'descrizione' => 'Visita medica', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 23],
            ['codice' => 'S-PI', 'nome' => 'PRONTO IMPIEGO', 'descrizione' => 'Pronto impiego', 'colore_badge' => '#00b050', 'categoria' => 'servizio', 'ordine' => 24],
            
            // OPERAZIONE - Rosso
            ['codice' => 'TO', 'nome' => 'TEATRO OPERATIVO', 'descrizione' => 'Teatro operativo', 'colore_badge' => '#ff0000', 'categoria' => 'missione', 'ordine' => 25],
            
            // ADD./APP./CATTEDRE - Giallo
            ['codice' => 'APS1', 'nome' => 'PRELIEVI', 'descrizione' => 'Prelievi', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 26],
            ['codice' => 'APS2', 'nome' => 'VACCINI', 'descrizione' => 'Vaccini', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 27],
            ['codice' => 'APS3', 'nome' => 'ECG', 'descrizione' => 'ECG', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 28],
            ['codice' => 'APS4', 'nome' => 'IDONEITA', 'descrizione' => 'Idoneità', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 29],
            ['codice' => 'AL-ELIX', 'nome' => 'ELITRASPORTO', 'descrizione' => 'Elitrasporto', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 30],
            ['codice' => 'AL-MCM', 'nome' => 'MCM', 'descrizione' => 'MCM', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 31],
            ['codice' => 'AL-BLS', 'nome' => 'BLS', 'descrizione' => 'BLS', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 32],
            ['codice' => 'AL-CIED', 'nome' => 'C-IED', 'descrizione' => 'C-IED', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 33],
            ['codice' => 'AL-SM', 'nome' => 'STRESS MANAGEMENT', 'descrizione' => 'Stress Management', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 34],
            ['codice' => 'AL-RM', 'nome' => 'RAPPORTO MEDIA', 'descrizione' => 'Rapporto Media', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 35],
            ['codice' => 'AL-RSPP', 'nome' => 'RSPP', 'descrizione' => 'RSPP', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 36],
            ['codice' => 'AL-LEG', 'nome' => 'ASPETTI LEGALI', 'descrizione' => 'Aspetti Legali', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 37],
            ['codice' => 'AL-SEA', 'nome' => 'SEXUAL EXPLOITATION AND ABUSE', 'descrizione' => 'Sexual Exploitation and Abuse', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 38],
            ['codice' => 'AL-MI', 'nome' => 'MALATTIE INFETTIVE', 'descrizione' => 'Malattie Infettive', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 39],
            ['codice' => 'AL-PO', 'nome' => 'PROPAGANDA OSTILE', 'descrizione' => 'Propaganda Ostile', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 40],
            ['codice' => 'AL-PI', 'nome' => 'PUBBLICA INFORMAZIONE E COMUNICAZIONE', 'descrizione' => 'Pubblica Informazione e Comunicazione', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 41],
            ['codice' => 'AP-M', 'nome' => 'MANTENIMENTO', 'descrizione' => 'Mantenimento', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 42],
            ['codice' => 'AP-A', 'nome' => 'APPRONTAMENTO', 'descrizione' => 'Approntamento', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 43],
            ['codice' => 'AC-SW', 'nome' => 'CORSO IN SMART WORKING', 'descrizione' => 'Corso in Smart Working', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 44],
            ['codice' => 'AC', 'nome' => 'CORSO SERVIZIO ISOLATO', 'descrizione' => 'Corso Servizio Isolato', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 45],
            ['codice' => 'PEFO', 'nome' => 'PEFO', 'descrizione' => 'PEFO', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 46],
            
            // SUPP.CIS/EXE - Verde
            ['codice' => 'EXE', 'nome' => 'ESERCITAZIONE', 'descrizione' => 'Esercitazione', 'colore_badge' => '#00b050', 'categoria' => 'formazione', 'ordine' => 47]
        ];

        foreach ($tipiServizio as $tipo) {
            DB::table('tipi_servizio')->insert(array_merge($tipo, [
                'attivo' => true,
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
        // Ripristina i dati precedenti se necessario
        DB::table('tipi_servizio')->truncate();
        
        // Re-inserisci i dati originali (versione semplificata)
        $tipiServizioOriginali = [
            ['codice' => 'TO', 'nome' => 'Turno Ordinario', 'descrizione' => 'Servizio normale in sede', 'colore_badge' => '#28a745', 'categoria' => 'servizio', 'ordine' => 1],
            ['codice' => 'lo', 'nome' => 'Libero Ordinario', 'descrizione' => 'Giorno di riposo ordinario', 'colore_badge' => '#17a2b8', 'categoria' => 'permesso', 'ordine' => 2],
            ['codice' => 'p', 'nome' => 'Permesso', 'descrizione' => 'Permesso giornaliero', 'colore_badge' => '#fd7e14', 'categoria' => 'permesso', 'ordine' => 3],
        ];

        foreach ($tipiServizioOriginali as $tipo) {
            DB::table('tipi_servizio')->insert(array_merge($tipo, [
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
};