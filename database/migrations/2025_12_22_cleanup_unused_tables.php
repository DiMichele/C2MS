<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * PULIZIA DATABASE SUGECO
 * 
 * Questa migrazione rimuove le tabelle non utilizzate e rinomina quelle
 * che causano confusione.
 * 
 * IMPORTANTE: Eseguire un BACKUP COMPLETO prima di applicare questa migrazione!
 * 
 * @version 1.1 - Safe version che verifica esistenza tabelle
 * @author SUGECO Team
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // FASE 1: BACKUP TABELLE (crea copie di sicurezza)
        // =====================================================
        
        // Backup certificati se ha dati
        if (Schema::hasTable('certificati') && DB::table('certificati')->exists()) {
            if (!Schema::hasTable('_backup_certificati')) {
                Schema::create('_backup_certificati', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('militare_id');
                    $table->string('tipo', 100);
                    $table->date('data_ottenimento');
                    $table->date('data_scadenza');
                    $table->string('file_path', 255)->nullable();
                    $table->text('note')->nullable();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO _backup_certificati SELECT * FROM certificati');
            }
        }
        
        // Backup certificati_lavoratori se ha dati
        if (Schema::hasTable('certificati_lavoratori') && DB::table('certificati_lavoratori')->exists()) {
            if (!Schema::hasTable('_backup_certificati_lavoratori')) {
                Schema::create('_backup_certificati_lavoratori', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('militare_id');
                    $table->string('tipo', 50);
                    $table->date('data_conseguimento');
                    $table->date('data_scadenza');
                    $table->text('note')->nullable();
                    $table->timestamps();
                });
                DB::statement('INSERT INTO _backup_certificati_lavoratori SELECT * FROM certificati_lavoratori');
            }
        }
        
        // Backup idoneita se ha dati
        if (Schema::hasTable('idoneita') && DB::table('idoneita')->exists()) {
            if (!Schema::hasTable('_backup_idoneita')) {
                Schema::create('_backup_idoneita', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('militare_id');
                    $table->string('tipo', 100);
                    $table->date('data_ottenimento');
                    $table->date('data_scadenza');
                    $table->string('file_path', 255)->nullable();
                    $table->text('note')->nullable();
                    $table->boolean('in_scadenza')->default(false);
                    $table->timestamps();
                });
                DB::statement('INSERT INTO _backup_idoneita SELECT * FROM idoneita');
            }
        }
        
        // =====================================================
        // FASE 2: RIMOZIONE TABELLE NON UTILIZZATE (safe)
        // =====================================================
        
        // Rimuovi foreign keys prima di droppare le tabelle (only if table exists)
        if (Schema::hasTable('certificati')) {
            try {
                Schema::table('certificati', function (Blueprint $table) {
                    $table->dropForeign(['militare_id']);
                });
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        }
        
        if (Schema::hasTable('certificati_lavoratori')) {
            try {
                Schema::table('certificati_lavoratori', function (Blueprint $table) {
                    $table->dropForeign(['militare_id']);
                });
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        }
        
        if (Schema::hasTable('idoneita')) {
            try {
                Schema::table('idoneita', function (Blueprint $table) {
                    $table->dropForeign(['militare_id']);
                });
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        }
        
        if (Schema::hasTable('presenze')) {
            try {
                Schema::table('presenze', function (Blueprint $table) {
                    $table->dropForeign(['militare_id']);
                    $table->dropForeign(['tipo_servizio_id']);
                });
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        }
        
        // Drop tabelle (dropIfExists è già safe)
        Schema::dropIfExists('certificati');
        Schema::dropIfExists('certificati_lavoratori');
        Schema::dropIfExists('idoneita');
        Schema::dropIfExists('presenze');
        Schema::dropIfExists('uffici');
        Schema::dropIfExists('incarichi');
        
        // =====================================================
        // FASE 3: AGGIUNGI COMMENTI ALLE TABELLE PRINCIPALI
        // =====================================================
        
        // Aggiungi commenti descrittivi solo se le tabelle esistono
        try {
            if (Schema::hasTable('militari')) {
                DB::statement("ALTER TABLE militari COMMENT = 'Anagrafica principale dei militari.'");
            }
            if (Schema::hasTable('scadenze_militari')) {
                DB::statement("ALTER TABLE scadenze_militari COMMENT = 'TABELLA PRINCIPALE SCADENZE.'");
            }
            if (Schema::hasTable('pianificazioni_giornaliere')) {
                DB::statement("ALTER TABLE pianificazioni_giornaliere COMMENT = 'Pianificazione giornaliera CPT.'");
            }
            if (Schema::hasTable('tipi_servizio')) {
                DB::statement("ALTER TABLE tipi_servizio COMMENT = 'Tipi di servizio/assenza utilizzabili nel CPT.'");
            }
            if (Schema::hasTable('roles')) {
                DB::statement("ALTER TABLE roles COMMENT = 'RUOLI SISTEMA - Autenticazione e permessi.'");
            }
            if (Schema::hasTable('ruoli')) {
                DB::statement("ALTER TABLE ruoli COMMENT = 'RUOLI OPERATIVI - Ruoli funzionali militari.'");
            }
            if (Schema::hasTable('approntamenti')) {
                DB::statement("ALTER TABLE approntamenti COMMENT = 'TEATRI OPERATIVI - Missioni e operazioni.'");
            }
        } catch (\Exception $e) {
            // Ignora errori sui commenti
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non fare nulla nel rollback - le tabelle sono già state rimosse
        // I backup esistono comunque in _backup_*
    }
};
