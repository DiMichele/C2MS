<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge colonne per i Corsi Accordo Stato Regione
     */
    public function up(): void
    {
        Schema::table('scadenze_militari', function (Blueprint $table) {
            // Corsi Accordo Stato Regione
            $table->date('abilitazione_trattori_data_conseguimento')->nullable()->after('primo_soccorso_aziendale_data_conseguimento');
            $table->date('abilitazione_mmt_data_conseguimento')->nullable()->after('abilitazione_trattori_data_conseguimento');
            $table->date('abilitazione_muletto_data_conseguimento')->nullable()->after('abilitazione_mmt_data_conseguimento');
            $table->date('abilitazione_ple_data_conseguimento')->nullable()->after('abilitazione_muletto_data_conseguimento');
            $table->date('corso_motosega_data_conseguimento')->nullable()->after('abilitazione_ple_data_conseguimento');
            $table->date('addetti_funi_catene_data_conseguimento')->nullable()->after('corso_motosega_data_conseguimento');
            $table->date('corso_rls_data_conseguimento')->nullable()->after('addetti_funi_catene_data_conseguimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scadenze_militari', function (Blueprint $table) {
            $table->dropColumn([
                'abilitazione_trattori_data_conseguimento',
                'abilitazione_mmt_data_conseguimento',
                'abilitazione_muletto_data_conseguimento',
                'abilitazione_ple_data_conseguimento',
                'corso_motosega_data_conseguimento',
                'addetti_funi_catene_data_conseguimento',
                'corso_rls_data_conseguimento',
            ]);
        });
    }
};
