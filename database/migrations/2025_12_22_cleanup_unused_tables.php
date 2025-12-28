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
 * Tabelle rimosse:
 * - certificati (sostituita da scadenze_militari)
 * - certificati_lavoratori (sostituita da scadenze_militari)
 * - idoneita (sostituita da scadenze_militari + tipi_idoneita)
 * - presenze (non utilizzata, gestita via CPT)
 * - uffici (tabella vuota)
 * - incarichi (tabella vuota)
 * 
 * @version 1.0
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
        
        // Backup certificati_lavoratori se ha dati
        if (Schema::hasTable('certificati_lavoratori') && DB::table('certificati_lavoratori')->exists()) {
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
        
        // Backup idoneita se ha dati
        if (Schema::hasTable('idoneita') && DB::table('idoneita')->exists()) {
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
        
        // =====================================================
        // FASE 2: RIMOZIONE TABELLE NON UTILIZZATE
        // =====================================================
        
        // Rimuovi foreign keys prima di droppare le tabelle
        Schema::table('certificati', function (Blueprint $table) {
            try {
                $table->dropForeign(['militare_id']);
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        });
        
        Schema::table('certificati_lavoratori', function (Blueprint $table) {
            try {
                $table->dropForeign(['militare_id']);
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        });
        
        Schema::table('idoneita', function (Blueprint $table) {
            try {
                $table->dropForeign(['militare_id']);
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        });
        
        Schema::table('presenze', function (Blueprint $table) {
            try {
                $table->dropForeign(['militare_id']);
                $table->dropForeign(['tipo_servizio_id']);
            } catch (\Exception $e) {
                // Ignora se non esiste
            }
        });
        
        // Drop tabelle
        Schema::dropIfExists('certificati');
        Schema::dropIfExists('certificati_lavoratori');
        Schema::dropIfExists('idoneita');
        Schema::dropIfExists('presenze');
        Schema::dropIfExists('uffici');
        Schema::dropIfExists('incarichi');
        
        // =====================================================
        // FASE 3: AGGIUNGI COMMENTI ALLE TABELLE PRINCIPALI
        // =====================================================
        
        // Aggiungi commenti descrittivi alle tabelle principali
        DB::statement("ALTER TABLE militari COMMENT = 'Anagrafica principale dei militari. Contiene dati personali, assegnazioni organizzative e riferimenti a scadenze.'");
        DB::statement("ALTER TABLE scadenze_militari COMMENT = 'TABELLA PRINCIPALE SCADENZE - Contiene tutte le date di conseguimento per idoneità, corsi e poligoni.'");
        DB::statement("ALTER TABLE pianificazioni_giornaliere COMMENT = 'Pianificazione giornaliera CPT - Assegnazioni servizi/assenze per ogni militare.'");
        DB::statement("ALTER TABLE tipi_servizio COMMENT = 'Tipi di servizio/assenza utilizzabili nel CPT con relativi codici e colori.'");
        DB::statement("ALTER TABLE roles COMMENT = 'RUOLI SISTEMA - Ruoli per autenticazione e permessi utente (admin, comandante, etc).'");
        DB::statement("ALTER TABLE ruoli COMMENT = 'RUOLI OPERATIVI - Ruoli funzionali militari (Comandante, Operatore, etc). Non confondere con roles!'");
        DB::statement("ALTER TABLE approntamenti COMMENT = 'TEATRI OPERATIVI - Missioni e operazioni a cui sono assegnati i militari.'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina le tabelle dai backup
        
        // Ripristina certificati
        if (Schema::hasTable('_backup_certificati')) {
            Schema::create('certificati', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('militare_id');
                $table->string('tipo', 100);
                $table->date('data_ottenimento');
                $table->date('data_scadenza');
                $table->string('file_path', 255)->nullable();
                $table->text('note')->nullable();
                $table->timestamps();
                
                $table->foreign('militare_id')->references('id')->on('militari')->onDelete('cascade');
                $table->index(['militare_id', 'tipo', 'data_scadenza']);
            });
            DB::statement('INSERT INTO certificati SELECT * FROM _backup_certificati');
            Schema::dropIfExists('_backup_certificati');
        }
        
        // Ripristina certificati_lavoratori
        if (Schema::hasTable('_backup_certificati_lavoratori')) {
            Schema::create('certificati_lavoratori', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('militare_id');
                $table->string('tipo', 50);
                $table->date('data_conseguimento');
                $table->date('data_scadenza');
                $table->text('note')->nullable();
                $table->timestamps();
                
                $table->foreign('militare_id')->references('id')->on('militari')->onDelete('cascade');
                $table->index(['militare_id', 'tipo', 'data_scadenza']);
            });
            DB::statement('INSERT INTO certificati_lavoratori SELECT * FROM _backup_certificati_lavoratori');
            Schema::dropIfExists('_backup_certificati_lavoratori');
        }
        
        // Ripristina idoneita
        if (Schema::hasTable('_backup_idoneita')) {
            Schema::create('idoneita', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('militare_id');
                $table->string('tipo', 100);
                $table->date('data_ottenimento');
                $table->date('data_scadenza');
                $table->string('file_path', 255)->nullable();
                $table->text('note')->nullable();
                $table->boolean('in_scadenza')->default(false);
                $table->timestamps();
                
                $table->foreign('militare_id')->references('id')->on('militari')->onDelete('cascade');
                $table->index(['militare_id', 'tipo', 'data_scadenza']);
            });
            DB::statement('INSERT INTO idoneita SELECT * FROM _backup_idoneita');
            Schema::dropIfExists('_backup_idoneita');
        }
        
        // Ricrea presenze (vuota)
        Schema::create('presenze', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('militare_id');
            $table->date('data');
            $table->enum('stato', ['Presente', 'Assente', 'Permesso', 'Licenza', 'Missione']);
            $table->unsignedBigInteger('tipo_servizio_id')->nullable();
            $table->text('note_servizio')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->foreign('militare_id')->references('id')->on('militari')->onDelete('cascade');
            $table->foreign('tipo_servizio_id')->references('id')->on('tipi_servizio')->onDelete('set null');
        });
        
        // Ricrea uffici (vuota)
        Schema::create('uffici', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });
        
        // Ricrea incarichi (vuota)
        Schema::create('incarichi', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
            
            $table->unique('nome');
        });
    }
};

