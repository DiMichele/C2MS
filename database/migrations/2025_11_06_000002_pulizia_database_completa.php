<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pulizia completa database SUGECO
     * 
     * Rimuove tabelle inutili e obsolete:
     * - eventi (usare board_activities)
     * - certificati* (usare scadenze_militari)
     * - idoneita (usare scadenze_militari)
     * - poligoni (vuota e struttura sbagliata)
     * - tipi_poligono (vuota)
     * - militare_approntamenti (vuota)
     * - notas (vuota, usare 'note')
     * - nos_storico (vuota)
     * - cpt_dashboard_views (vuota)
     * - trasparenza_servizi (vuota)
     */
    public function up(): void
    {
        // Disabilita foreign key checks temporaneamente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // TABELLE DA RIMUOVERE COMPLETAMENTE
        $tabelleDaRimuovere = [
            'eventi',                    // Usare board_activities
            'certificati',               // Usare scadenze_militari
            'certificati_lavoratori',    // Usare scadenze_militari
            'idoneita',                  // Usare scadenze_militari
            'poligoni',                  // Vuota, dati in scadenze_militari
            'tipi_poligono',            // Vuota
            'militare_approntamenti',    // Vuota
            'notas',                    // Vuota (usare note nel model)
            'nos_storico',              // Vuota
            'cpt_dashboard_views',      // Vuota
            'trasparenza_servizi',      // Vuota
            'militare_valutazioni',     // Vuota
        ];
        
        foreach ($tabelleDaRimuovere as $tabella) {
            if (Schema::hasTable($tabella)) {
                Schema::dropIfExists($tabella);
                echo "✅ Rimossa tabella: {$tabella}\n";
            }
        }
        
        // Riabilita foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non è possibile fare rollback di drop table
        // In caso di necessità, ripristinare da backup
    }
};

