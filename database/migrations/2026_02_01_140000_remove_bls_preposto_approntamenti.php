<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rimuove le colonne BLS e PREPOSTO dalla configurazione approntamenti
 * in quanto non pertinenti
 */
return new class extends Migration
{
    public function up(): void
    {
        // Rimuove i record BLS e PREPOSTO dalla tabella config_colonne_approntamenti
        DB::table('config_colonne_approntamenti')
            ->whereIn('campo_db', ['bls', 'preposto'])
            ->delete();
    }

    public function down(): void
    {
        // Ricrea i record se necessario rollback
        $now = now();
        
        // BLS
        DB::table('config_colonne_approntamenti')->insertOrIgnore([
            'campo_db' => 'bls',
            'label' => 'BLS',
            'scadenza_mesi' => 24,
            'fonte' => 'scadenze_militari',
            'campo_sorgente' => 'blsd',
            'attivo' => false,
            'ordine' => 99,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        
        // PREPOSTO
        DB::table('config_colonne_approntamenti')->insertOrIgnore([
            'campo_db' => 'preposto',
            'label' => 'Preposto',
            'scadenza_mesi' => null,
            'fonte' => 'approntamenti',
            'campo_sorgente' => null,
            'attivo' => false,
            'ordine' => 100,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
