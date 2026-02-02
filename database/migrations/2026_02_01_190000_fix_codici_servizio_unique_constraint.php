<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modifica il vincolo UNIQUE sulla tabella codici_servizio_gerarchia.
 * 
 * Il vincolo originale era solo su `codice`, ma dovrebbe essere
 * su (`organizational_unit_id`, `codice`) per permettere lo stesso
 * codice in unità organizzative diverse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
            // Rimuovi il vecchio vincolo UNIQUE solo su codice
            $table->dropUnique(['codice']);
            
            // Aggiungi il nuovo vincolo UNIQUE composito
            $table->unique(['organizational_unit_id', 'codice'], 'csg_unit_codice_unique');
        });
    }

    public function down(): void
    {
        Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
            // Rimuovi il vincolo composito
            $table->dropUnique('csg_unit_codice_unique');
            
            // Ripristina il vincolo originale
            $table->unique('codice');
        });
    }
};
