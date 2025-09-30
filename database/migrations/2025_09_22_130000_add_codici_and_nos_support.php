<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per supportare i dati delle pagine CODICI e NOS dal file CPT.xlsx
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Creare tabella per la gerarchia dei codici servizio
        if (!Schema::hasTable('codici_servizio_gerarchia')) {
            Schema::create('codici_servizio_gerarchia', function (Blueprint $table) {
                $table->id();
                $table->string('codice', 20)->unique()->comment('Codice identificativo (es. TO, S-UI, etc.)');
                $table->string('macro_attivita', 100)->nullable()->comment('Macro-attività (es. SERVIZIO, ASSENTE)');
                $table->string('tipo_attivita', 100)->nullable()->comment('Tipo attività (es. LICENZA, PERMESSO)');
                $table->string('attivita_specifica', 200)->comment('Attività specifica dettagliata');
                $table->enum('impiego', ['DISPONIBILE', 'INDISPONIBILE', 'NON_DISPONIBILE', 'PRESENTE_SERVIZIO', 'DISPONIBILE_ESIGENZA'])
                      ->default('DISPONIBILE')
                      ->comment('Tipo di impiego/disponibilità');
                $table->text('descrizione_impiego')->nullable()->comment('Descrizione dettagliata impiego');
                $table->string('colore_badge', 7)->default('#6c757d')->comment('Colore per visualizzazione');
                $table->boolean('attivo')->default(true);
                $table->integer('ordine')->default(0)->comment('Ordine di visualizzazione');
                $table->timestamps();
                
                $table->index(['macro_attivita', 'tipo_attivita']);
                $table->index(['impiego', 'attivo']);
            });
        }
        
        // 2. Aggiornare tabella tipi_servizio per collegamento con gerarchia
        if (!Schema::hasColumn('tipi_servizio', 'codice_gerarchia_id')) {
            Schema::table('tipi_servizio', function (Blueprint $table) {
                $table->foreignId('codice_gerarchia_id')->nullable()->after('id')
                      ->constrained('codici_servizio_gerarchia')->onDelete('set null')
                      ->comment('Collegamento alla gerarchia codici');
            });
        }
        
        // 3. Aggiungere campi NOS alla tabella militari
        if (!Schema::hasColumn('militari', 'nos_status')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->enum('nos_status', ['SI', 'NO', 'IN_ATTESA', 'DA_RICHIEDERE', 'NON_PREVISTO'])
                      ->nullable()->after('categoria')
                      ->comment('Status del Nulla Osta di Sicurezza');
            });
        }
        
        if (!Schema::hasColumn('militari', 'nos_scadenza')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->date('nos_scadenza')->nullable()->after('nos_status')
                      ->comment('Data di scadenza del NOS');
            });
        }
        
        if (!Schema::hasColumn('militari', 'nos_note')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->text('nos_note')->nullable()->after('nos_scadenza')
                      ->comment('Note relative al NOS');
            });
        }
        
        if (!Schema::hasColumn('militari', 'compagnia_nos')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->string('compagnia_nos', 50)->nullable()->after('nos_note')
                      ->comment('Compagnia di riferimento per il NOS (es. 124^ CP, 110^ CP)');
            });
        }
        
        // 4. Creare tabella per storico NOS
        if (!Schema::hasTable('nos_storico')) {
            Schema::create('nos_storico', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->enum('status_precedente', ['SI', 'NO', 'IN_ATTESA', 'DA_RICHIEDERE', 'NON_PREVISTO'])
                      ->comment('Status precedente');
                $table->enum('status_nuovo', ['SI', 'NO', 'IN_ATTESA', 'DA_RICHIEDERE', 'NON_PREVISTO'])
                      ->comment('Nuovo status');
                $table->date('data_cambio')->comment('Data del cambio di status');
                $table->text('motivo')->nullable()->comment('Motivo del cambio');
                $table->foreignId('modificato_da')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['militare_id', 'data_cambio']);
            });
        }
        
        // 5. Creare vista per dashboard CPT (replica della schermata Excel)
        if (!Schema::hasTable('cpt_dashboard_views')) {
            Schema::create('cpt_dashboard_views', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100)->comment('Nome della vista (es. Settembre 2025)');
                $table->text('descrizione')->nullable();
                $table->json('configurazione')->comment('Configurazione colonne e filtri');
                $table->boolean('attiva')->default(true);
                $table->foreignId('creato_da')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpt_dashboard_views');
        Schema::dropIfExists('nos_storico');
        
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn(['nos_status', 'nos_scadenza', 'nos_note', 'compagnia_nos']);
        });
        
        Schema::table('tipi_servizio', function (Blueprint $table) {
            $table->dropForeign(['codice_gerarchia_id']);
            $table->dropColumn('codice_gerarchia_id');
        });
        
        Schema::dropIfExists('codici_servizio_gerarchia');
    }
};
