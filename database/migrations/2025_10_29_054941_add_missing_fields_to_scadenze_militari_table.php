<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge campi mancanti per:
     * - Antincendio
     * - BLSD
     * - Primo Soccorso Aziendale
     * - ECG
     * - Prelievi
     * - Tiri di Approntamento
     * - Mantenimento Arma Lunga
     * - Mantenimento Arma Corta
     */
    public function up(): void
    {
        Schema::table('scadenze_militari', function (Blueprint $table) {
            // RSPP - Campi mancanti
            $table->date('antincendio_data_conseguimento')->nullable()->after('dirigenti_data_conseguimento');
            $table->date('blsd_data_conseguimento')->nullable()->after('antincendio_data_conseguimento');
            $table->date('primo_soccorso_aziendale_data_conseguimento')->nullable()->after('blsd_data_conseguimento');
            
            // Idoneità Sanitarie - Campi mancanti
            // (idoneita_mansione già esiste come idoneita_mans_data_conseguimento)
            // (idoneita_smi già esiste come idoneita_smi_data_conseguimento)
            $table->date('ecg_data_conseguimento')->nullable()->after('idoneita_smi_data_conseguimento');
            $table->date('prelievi_data_conseguimento')->nullable()->after('ecg_data_conseguimento');
            
            // Poligoni - Campi separati
            // (poligono_approntamento già esiste come poligono_approntamento_data_conseguimento)
            // Rinominiamo e aggiungiamo i campi specifici
            $table->date('tiri_approntamento_data_conseguimento')->nullable()->after('primo_soccorso_aziendale_data_conseguimento');
            $table->date('mantenimento_arma_lunga_data_conseguimento')->nullable()->after('tiri_approntamento_data_conseguimento');
            $table->date('mantenimento_arma_corta_data_conseguimento')->nullable()->after('mantenimento_arma_lunga_data_conseguimento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scadenze_militari', function (Blueprint $table) {
            $table->dropColumn([
                'antincendio_data_conseguimento',
                'blsd_data_conseguimento',
                'primo_soccorso_aziendale_data_conseguimento',
                'ecg_data_conseguimento',
                'prelievi_data_conseguimento',
                'tiri_approntamento_data_conseguimento',
                'mantenimento_arma_lunga_data_conseguimento',
                'mantenimento_arma_corta_data_conseguimento',
            ]);
        });
    }
};
