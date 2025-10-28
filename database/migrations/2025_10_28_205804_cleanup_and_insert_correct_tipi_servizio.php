<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Ripulisce la tabella tipi_servizio dai dati di test e inserisce
     * SOLO i codici CPT corretti e ufficiali
     */
    public function up(): void
    {
        // STEP 1: Disabilita temporaneamente i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // STEP 2: Elimina TUTTI i dati di test
        DB::table('tipi_servizio')->truncate();
        
        // STEP 3: Riabilita i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // STEP 4: Inserisci SOLO i TipoServizio CORRETTI
        $tipiServizioCorretti = [
            // ========================================
            // CATEGORIA: ASSENTE
            // ========================================
            ['codice' => 'LS', 'nome' => 'LICENZA STRAORD.', 'descrizione' => 'Licenza Straordinaria', 'colore_badge' => '#ff0000', 'categoria' => 'assenza', 'ordine' => 1],
            ['codice' => 'LO', 'nome' => 'LICENZA ORD.', 'descrizione' => 'Licenza Ordinaria', 'colore_badge' => '#ff6600', 'categoria' => 'assenza', 'ordine' => 2],
            ['codice' => 'LM', 'nome' => 'LICENZA DI MATERNITA\'', 'descrizione' => 'Licenza di Maternità', 'colore_badge' => '#ff99cc', 'categoria' => 'assenza', 'ordine' => 3],
            ['codice' => 'P', 'nome' => 'PERMESSINO', 'descrizione' => 'Permessino', 'colore_badge' => '#ffcc00', 'categoria' => 'assenza', 'ordine' => 4],
            ['codice' => 'TIR', 'nome' => 'TIROCINIO', 'descrizione' => 'Tirocinio', 'colore_badge' => '#9966ff', 'categoria' => 'assenza', 'ordine' => 5],
            ['codice' => 'TRAS', 'nome' => 'TRASFERITO', 'descrizione' => 'Trasferito', 'colore_badge' => '#666666', 'categoria' => 'assenza', 'ordine' => 6],
            
            // ========================================
            // CATEGORIA: PROVVEDIMENTI MEDICO SANITARI
            // ========================================
            ['codice' => 'RMD', 'nome' => 'RIPOSO MEDICO DOMICILIARE', 'descrizione' => 'Riposo Medico Domiciliare', 'colore_badge' => '#ff0066', 'categoria' => 'assenza', 'ordine' => 7],
            ['codice' => 'LC', 'nome' => 'LICENZA DI CONVALESCENZA', 'descrizione' => 'Licenza di Convalescenza', 'colore_badge' => '#ff3399', 'categoria' => 'assenza', 'ordine' => 8],
            ['codice' => 'IS', 'nome' => 'ISOLAMENTO/QUARANTENA', 'descrizione' => 'Isolamento/Quarantena', 'colore_badge' => '#cc0066', 'categoria' => 'assenza', 'ordine' => 9],
            
            // ========================================
            // CATEGORIA: SERVIZIO
            // ========================================
            ['codice' => 'S-G1', 'nome' => 'GUARDIA D\'AVANZO LUNGA', 'descrizione' => 'Guardia d\'Avanzo Lunga', 'colore_badge' => '#0066cc', 'categoria' => 'servizio', 'ordine' => 10],
            ['codice' => 'S-G2', 'nome' => 'GUARDIA D\'AVANZO CORTA', 'descrizione' => 'Guardia d\'Avanzo Corta', 'colore_badge' => '#3399ff', 'categoria' => 'servizio', 'ordine' => 11],
            ['codice' => 'S-SA', 'nome' => 'SORVEGLIANZA D\'AVANZO', 'descrizione' => 'Sorveglianza d\'Avanzo', 'colore_badge' => '#66ccff', 'categoria' => 'servizio', 'ordine' => 12],
            ['codice' => 'S-CD1', 'nome' => 'CONDUTTORE GUARDIA LUNGO', 'descrizione' => 'Conduttore Guardia Lungo', 'colore_badge' => '#0099cc', 'categoria' => 'servizio', 'ordine' => 13],
            ['codice' => 'S-CD2', 'nome' => 'CONDUTTORE PIAN DEL TERMINE CORTO', 'descrizione' => 'Conduttore Pian del Termine Corto', 'colore_badge' => '#00ccff', 'categoria' => 'servizio', 'ordine' => 14],
            ['codice' => 'S-SG', 'nome' => 'SOTTUFFICIALE DI GIORNATA', 'descrizione' => 'Sottufficiale di Giornata', 'colore_badge' => '#006699', 'categoria' => 'servizio', 'ordine' => 15],
            ['codice' => 'S-CG', 'nome' => 'COMANDANTE DELLA GUARDIA', 'descrizione' => 'Comandante della Guardia', 'colore_badge' => '#003366', 'categoria' => 'servizio', 'ordine' => 16],
            ['codice' => 'S-UI', 'nome' => 'UFFICIALE DI ISPEZIONE', 'descrizione' => 'Ufficiale di Ispezione', 'colore_badge' => '#0033cc', 'categoria' => 'servizio', 'ordine' => 17],
            ['codice' => 'S-UP', 'nome' => 'UFFICIALE DI PICCHETTO', 'descrizione' => 'Ufficiale di Picchetto', 'colore_badge' => '#3366cc', 'categoria' => 'servizio', 'ordine' => 18],
            ['codice' => 'S-AE', 'nome' => 'AREE ESTERNE', 'descrizione' => 'Aree Esterne', 'colore_badge' => '#669900', 'categoria' => 'servizio', 'ordine' => 19],
            ['codice' => 'S-ARM', 'nome' => 'ARMIERE DI SERVIZIO', 'descrizione' => 'Armiere di Servizio', 'colore_badge' => '#996633', 'categoria' => 'servizio', 'ordine' => 20],
            ['codice' => 'SI-GD', 'nome' => 'SERVIZIO ISOLATO-GUARDIA DISTACCATA', 'descrizione' => 'Servizio Isolato-Guardia Distaccata', 'colore_badge' => '#cc9900', 'categoria' => 'servizio', 'ordine' => 21],
            ['codice' => 'SI', 'nome' => 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU', 'descrizione' => 'Servizio Isolato-Capomacchina/CAU', 'colore_badge' => '#ff9900', 'categoria' => 'servizio', 'ordine' => 22],
            ['codice' => 'SI-VM', 'nome' => 'VISITA MEDICA', 'descrizione' => 'Visita Medica', 'colore_badge' => '#cc66ff', 'categoria' => 'servizio', 'ordine' => 23],
            ['codice' => 'S-PI', 'nome' => 'PRONTO IMPIEGO', 'descrizione' => 'Pronto Impiego', 'colore_badge' => '#ff3300', 'categoria' => 'servizio', 'ordine' => 24],
            
            // SERVIZI TURNO SETTIMANALI
            ['codice' => 'G-BTG', 'nome' => 'GRADUATO DI BTG', 'descrizione' => 'Graduato di BTG', 'colore_badge' => '#0066ff', 'categoria' => 'servizio', 'ordine' => 25],
            ['codice' => 'NVA', 'nome' => 'NUCLEO VIGILANZA ARMATA D\'AVANZO', 'descrizione' => 'Nucleo Vigilanza Armata d\'Avanzo', 'colore_badge' => '#3300cc', 'categoria' => 'servizio', 'ordine' => 26],
            ['codice' => 'CG', 'nome' => 'CONDUTTORE GUARDIA', 'descrizione' => 'Conduttore Guardia', 'colore_badge' => '#6600ff', 'categoria' => 'servizio', 'ordine' => 27],
            ['codice' => 'NS-DA', 'nome' => 'NUCLEO SORV. D\' AVANZO 07:30 - 17:00', 'descrizione' => 'Nucleo Sorveglianza d\'Avanzo', 'colore_badge' => '#9933ff', 'categoria' => 'servizio', 'ordine' => 28],
            ['codice' => 'PDT', 'nome' => 'VIGILANZA PIAN DEL TERMINE', 'descrizione' => 'Vigilanza Pian del Termine', 'colore_badge' => '#cc66ff', 'categoria' => 'servizio', 'ordine' => 29],
            ['codice' => 'AA', 'nome' => 'ADDETTO ANTINCENDIO', 'descrizione' => 'Addetto Antincendio', 'colore_badge' => '#ff6600', 'categoria' => 'servizio', 'ordine' => 30],
            ['codice' => 'VS-CETLI', 'nome' => 'VIGILANZA SETTIMANALE CETLI', 'descrizione' => 'Vigilanza Settimanale CETLI', 'colore_badge' => '#ff9933', 'categoria' => 'servizio', 'ordine' => 31],
            ['codice' => 'CORR', 'nome' => 'CORRIERE', 'descrizione' => 'Servizio Corriere', 'colore_badge' => '#ffcc66', 'categoria' => 'servizio', 'ordine' => 32],
            ['codice' => 'NDI', 'nome' => 'NUCLEO DIFESA IMMEDIATA', 'descrizione' => 'Nucleo Difesa Immediata', 'colore_badge' => '#cc3300', 'categoria' => 'servizio', 'ordine' => 33],
            
            // ========================================
            // CATEGORIA: OPERAZIONE
            // ========================================
            ['codice' => 'TO', 'nome' => 'TEATRO OPERATIVO', 'descrizione' => 'Teatro Operativo', 'colore_badge' => '#cc0000', 'categoria' => 'missione', 'ordine' => 34],
            
            // ========================================
            // CATEGORIA: ADD./APP./CATTEDRE
            // ========================================
            ['codice' => 'APS1', 'nome' => 'PRELIEVI', 'descrizione' => 'Attività Sanitaria - Prelievi', 'colore_badge' => '#ffff00', 'categoria' => 'formazione', 'ordine' => 35],
            ['codice' => 'APS2', 'nome' => 'VACCINI', 'descrizione' => 'Attività Sanitaria - Vaccini', 'colore_badge' => '#ffff33', 'categoria' => 'formazione', 'ordine' => 36],
            ['codice' => 'APS3', 'nome' => 'ECG', 'descrizione' => 'Attività Sanitaria - ECG', 'colore_badge' => '#ffff66', 'categoria' => 'formazione', 'ordine' => 37],
            ['codice' => 'APS4', 'nome' => 'IDONEITA', 'descrizione' => 'Attività Sanitaria - Idoneità', 'colore_badge' => '#ffff99', 'categoria' => 'formazione', 'ordine' => 38],
            ['codice' => 'AL-ELIX', 'nome' => 'ELITRASPORTO', 'descrizione' => 'Addestramento - Elitrasporto', 'colore_badge' => '#ccff00', 'categoria' => 'formazione', 'ordine' => 39],
            ['codice' => 'AL-MCM', 'nome' => 'MCM', 'descrizione' => 'Addestramento - MCM', 'colore_badge' => '#ccff33', 'categoria' => 'formazione', 'ordine' => 40],
            ['codice' => 'AL-BLS', 'nome' => 'BLS', 'descrizione' => 'Addestramento - Basic Life Support', 'colore_badge' => '#ccff66', 'categoria' => 'formazione', 'ordine' => 41],
            ['codice' => 'AL-CIED', 'nome' => 'C-IED', 'descrizione' => 'Addestramento - Counter-IED', 'colore_badge' => '#ccff99', 'categoria' => 'formazione', 'ordine' => 42],
            ['codice' => 'AL-SM', 'nome' => 'STRESS MANAGEMENT', 'descrizione' => 'Addestramento - Stress Management', 'colore_badge' => '#99ff00', 'categoria' => 'formazione', 'ordine' => 43],
            ['codice' => 'AL-RM', 'nome' => 'RAPPORTO MEDIA', 'descrizione' => 'Addestramento - Rapporto Media', 'colore_badge' => '#99ff33', 'categoria' => 'formazione', 'ordine' => 44],
            ['codice' => 'AL-RSPP', 'nome' => 'RSPP', 'descrizione' => 'Addestramento - RSPP', 'colore_badge' => '#99ff66', 'categoria' => 'formazione', 'ordine' => 45],
            ['codice' => 'AL-LEG', 'nome' => 'ASPETTI LEGALI', 'descrizione' => 'Addestramento - Aspetti Legali', 'colore_badge' => '#99ff99', 'categoria' => 'formazione', 'ordine' => 46],
            ['codice' => 'AL-SEA', 'nome' => 'SEXUAL EXPLOITATION AND ABUSE', 'descrizione' => 'Addestramento - SEA', 'colore_badge' => '#66ff00', 'categoria' => 'formazione', 'ordine' => 47],
            ['codice' => 'AL-MI', 'nome' => 'MALATTIE INFETTIVE', 'descrizione' => 'Addestramento - Malattie Infettive', 'colore_badge' => '#66ff33', 'categoria' => 'formazione', 'ordine' => 48],
            ['codice' => 'AL-PO', 'nome' => 'PROPAGANDA OSTILE', 'descrizione' => 'Addestramento - Propaganda Ostile', 'colore_badge' => '#66ff66', 'categoria' => 'formazione', 'ordine' => 49],
            ['codice' => 'AL-PI', 'nome' => 'PUBBLICA INFORMAZIONE E COMUNICAZIONE', 'descrizione' => 'Addestramento - Pubblica Informazione', 'colore_badge' => '#66ff99', 'categoria' => 'formazione', 'ordine' => 50],
            ['codice' => 'AP-M', 'nome' => 'MANTENIMENTO', 'descrizione' => 'Approntamento - Mantenimento', 'colore_badge' => '#33ff00', 'categoria' => 'formazione', 'ordine' => 51],
            ['codice' => 'AP-A', 'nome' => 'APPRONTAMENTO', 'descrizione' => 'Approntamento - Attività', 'colore_badge' => '#33ff33', 'categoria' => 'formazione', 'ordine' => 52],
            ['codice' => 'AC-SW', 'nome' => 'CORSO IN SMART WORKING', 'descrizione' => 'Corso in Smart Working', 'colore_badge' => '#33ff66', 'categoria' => 'formazione', 'ordine' => 53],
            ['codice' => 'AC', 'nome' => 'CORSO SERVIZIO ISOLATO', 'descrizione' => 'Corso Servizio Isolato', 'colore_badge' => '#33ff99', 'categoria' => 'formazione', 'ordine' => 54],
            ['codice' => 'PEFO', 'nome' => 'PEFO', 'descrizione' => 'PEFO', 'colore_badge' => '#00ff00', 'categoria' => 'formazione', 'ordine' => 55],
            
            // ========================================
            // CATEGORIA: SUPP.CIS/EXE
            // ========================================
            ['codice' => 'EXE', 'nome' => 'ESERCITAZIONE', 'descrizione' => 'Esercitazione', 'colore_badge' => '#00b050', 'categoria' => 'formazione', 'ordine' => 56]
        ];
        
        // Inserisci tutti i tipi servizio corretti
        foreach ($tipiServizioCorretti as $tipo) {
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
        // Non implementiamo il down per sicurezza
        // Se serve ripristinare, meglio farlo manualmente
    }
};
