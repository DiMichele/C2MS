<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per popolare gli approntamenti basati sui dati del file Excel
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Popolare la tabella approntamenti con i dati trovati nel file Excel
        $approntamenti = [
            [
                'nome' => 'KOSOVO',
                'codice' => 'KOS',
                'descrizione' => 'Missione internazionale in Kosovo',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#28a745'
            ],
            [
                'nome' => 'CJ CBRN',
                'codice' => 'CBRN',
                'descrizione' => 'Centro di Joint CBRN (Chimico, Biologico, Radiologico, Nucleare)',
                'data_inizio' => '2025-01-01',
                'data_fine' => null,
                'stato' => 'attivo',
                'colore_badge' => '#dc3545'
            ],
            [
                'nome' => 'CENTURIA',
                'codice' => 'CENT',
                'descrizione' => 'Operazione Centuria',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#ffc107'
            ],
            [
                'nome' => 'GIBUTI',
                'codice' => 'GIB',
                'descrizione' => 'Missione internazionale a Gibuti',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#17a2b8'
            ],
            [
                'nome' => 'LIBIA',
                'codice' => 'LIB',
                'descrizione' => 'Missione internazionale in Libia',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#6f42c1'
            ],
            [
                'nome' => 'GIORDANIA',
                'codice' => 'GIO',
                'descrizione' => 'Missione internazionale in Giordania',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#fd7e14'
            ],
            [
                'nome' => 'JFHQ II SEMESTRE/ LCC',
                'codice' => 'JFHQ',
                'descrizione' => 'Joint Force Headquarters II Semestre / Land Component Command',
                'data_inizio' => '2025-07-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#20c997'
            ],
            [
                'nome' => 'LCC',
                'codice' => 'LCC',
                'descrizione' => 'Land Component Command',
                'data_inizio' => '2025-01-01',
                'data_fine' => null,
                'stato' => 'attivo',
                'colore_badge' => '#6c757d'
            ],
            [
                'nome' => 'IPPOCAMPO/ LCC',
                'codice' => 'IPPO',
                'descrizione' => 'Operazione Ippocampo / Land Component Command',
                'data_inizio' => '2025-01-01',
                'data_fine' => '2025-12-31',
                'stato' => 'attivo',
                'colore_badge' => '#e83e8c'
            ],
            [
                'nome' => 'TRASFERITO',
                'codice' => 'TRASF',
                'descrizione' => 'Personale trasferito ad altra unità',
                'data_inizio' => null,
                'data_fine' => null,
                'stato' => 'completato',
                'colore_badge' => '#6c757d'
            ],
            [
                'nome' => 'SEDE',
                'codice' => 'SEDE',
                'descrizione' => 'Servizio presso la sede principale',
                'data_inizio' => '2025-01-01',
                'data_fine' => null,
                'stato' => 'attivo',
                'colore_badge' => '#007bff'
            ]
        ];

        foreach ($approntamenti as $approntamento) {
            DB::table('approntamenti')->insert(array_merge($approntamento, [
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
        // Svuotare la tabella approntamenti
        DB::table('approntamenti')->truncate();
    }
};
