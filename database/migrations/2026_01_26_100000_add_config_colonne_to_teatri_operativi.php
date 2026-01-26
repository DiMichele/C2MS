<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge la colonna config_colonne (JSON) alla tabella teatri_operativi
 * 
 * Permette di configurare quali cattedre sono visibili/richieste per ogni Teatro Operativo
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teatri_operativi', function (Blueprint $table) {
            // JSON per configurare le colonne visibili e richieste per questo teatro
            // Struttura: { "colonna_nome": { "visibile": true/false, "richiesta": true/false } }
            $table->json('config_colonne')->nullable()->after('colore_badge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teatri_operativi', function (Blueprint $table) {
            $table->dropColumn('config_colonne');
        });
    }
};
