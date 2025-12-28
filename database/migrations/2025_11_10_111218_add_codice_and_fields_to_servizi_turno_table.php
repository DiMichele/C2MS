<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->string('codice', 10)->nullable()->after('nome');
            $table->string('sigla_cpt', 20)->nullable()->after('codice');
            $table->text('descrizione')->nullable()->after('sigla_cpt');
            $table->enum('tipo', ['singolo', 'multiplo'])->default('singolo')->after('descrizione');
            $table->time('orario_inizio')->nullable()->after('tipo');
            $table->time('orario_fine')->nullable()->after('orario_inizio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->dropColumn(['codice', 'sigla_cpt', 'descrizione', 'tipo', 'orario_inizio', 'orario_fine']);
        });
    }
};
