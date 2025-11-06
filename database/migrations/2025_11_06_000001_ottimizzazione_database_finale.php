<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ottimizzazione finale database SUGECO per produzione
     * 
     * - Rimozione tabelle deprecate (vuote)
     * - Aggiunta indici per performance
     */
    public function up(): void
    {
        // === RIMOZIONE TABELLE DEPRECATE ===
        // (solo se vuote - verificato precedentemente)
        Schema::dropIfExists('certificati_lavoratori');
        Schema::dropIfExists('certificati');
        Schema::dropIfExists('idoneita');
        
        // === AGGIUNTA INDICI PER PERFORMANCE ===
        // Uso try-catch per evitare errori se indici già esistono
        
        try {
            // Indici su scadenze_militari per query veloci
            Schema::table('scadenze_militari', function (Blueprint $table) {
                $table->index('lavoratore_4h_data_conseguimento', 'idx_lav_4h');
                $table->index('lavoratore_8h_data_conseguimento', 'idx_lav_8h');
                $table->index('preposto_data_conseguimento', 'idx_preposto');
                $table->index('blsd_data_conseguimento', 'idx_blsd');
                $table->index('idoneita_mans_data_conseguimento', 'idx_idon_mans');
                $table->index('idoneita_smi_data_conseguimento', 'idx_idon_smi');
                $table->index('tiri_approntamento_data_conseguimento', 'idx_tiri');
                $table->index('mantenimento_arma_lunga_data_conseguimento', 'idx_mant_al');
                $table->index('mantenimento_arma_corta_data_conseguimento', 'idx_mant_ac');
            });
        } catch (\Exception $e) {
            // Indici già esistenti, skip
        }
        
        try {
            // Indici su militari
            Schema::table('militari', function (Blueprint $table) {
                $table->index(['cognome', 'nome'], 'idx_cognome_nome');
            });
        } catch (\Exception $e) {
            // Indice già esistente
        }
        
        try {
            // Indici su presenze
            Schema::table('presenze', function (Blueprint $table) {
                $table->index(['data', 'presenza'], 'idx_data_presenza');
            });
        } catch (\Exception $e) {
            // Indice già esistente
        }
        
        try {
            // Indici su pianificazioni_giornaliere
            Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
                $table->index('data', 'idx_data');
            });
        } catch (\Exception $e) {
            // Indice già esistente
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristino tabelle (vuote, ma per consistenza)
        Schema::create('certificati', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->timestamps();
        });
        
        Schema::create('certificati_lavoratori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('certificato_id')->constrained('certificati')->onDelete('cascade');
            $table->date('data_conseguimento');
            $table->date('data_scadenza');
            $table->timestamps();
        });
        
        Schema::create('idoneita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->date('data_conseguimento');
            $table->date('data_scadenza');
            $table->timestamps();
        });
        
        // Rimozione indici
        Schema::table('scadenze_militari', function (Blueprint $table) {
            $table->dropIndex('idx_lav_4h');
            $table->dropIndex('idx_lav_8h');
            $table->dropIndex('idx_preposto');
            $table->dropIndex('idx_blsd');
            $table->dropIndex('idx_idon_mans');
            $table->dropIndex('idx_idon_smi');
            $table->dropIndex('idx_tiri');
            $table->dropIndex('idx_mant_al');
            $table->dropIndex('idx_mant_ac');
        });
        
        Schema::table('militari', function (Blueprint $table) {
            $table->dropIndex('idx_cognome_nome');
            $table->dropIndex('idx_compagnia_id');
        });
        
        Schema::table('presenze', function (Blueprint $table) {
            $table->dropIndex('idx_data_presenza');
        });
        
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->dropIndex('idx_data');
        });
    }
    
};

