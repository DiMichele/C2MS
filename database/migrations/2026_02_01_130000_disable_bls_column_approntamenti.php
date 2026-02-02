<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Disattiva la colonna BLS dalla configurazione approntamenti
 * Il BLS non è rilevante per gli approntamenti
 */
return new class extends Migration
{
    public function up(): void
    {
        // Disattiva la colonna BLS invece di eliminarla (per sicurezza)
        DB::table('config_colonne_approntamenti')
            ->where('campo_db', 'bls')
            ->update(['attivo' => false]);
    }

    public function down(): void
    {
        // Riattiva la colonna BLS
        DB::table('config_colonne_approntamenti')
            ->where('campo_db', 'bls')
            ->update(['attivo' => true]);
    }
};
