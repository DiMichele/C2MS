<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per supportare tutti i dati del file CPT.xlsx
 * 
 * Questa migration aggiunge tutte le tabelle e campi necessari
 * per importare e gestire i dati del calendario Excel CPT.xlsx
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Aggiungere campi mancanti alla tabella militari (solo se non esistono)
        if (!Schema::hasColumn('militari', 'categoria')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->enum('categoria', ['U', 'SU', 'GRAD'])->nullable()->after('grado_id')
                      ->comment('U=Ufficiali, SU=Sottufficiali, GRAD=Graduati');
            });
        }
        
        if (!Schema::hasColumn('militari', 'numero_matricola')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->string('numero_matricola', 20)->nullable()->after('categoria')
                      ->comment('Numero progressivo/matricola militare');
            });
        }
        
        if (!Schema::hasColumn('militari', 'approntamento_principale_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->foreignId('approntamento_principale_id')->nullable()->after('mansione_id')
                      ->comment('Approntamento/missione principale assegnato');
            });
        }

        // 2. Creare tabella approntamenti (missioni/operazioni) solo se non esiste
        if (!Schema::hasTable('approntamenti')) {
            Schema::create('approntamenti', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->comment('Nome approntamento (es. KOSOVO, CJ CBRN)');
            $table->string('codice', 20)->nullable()->comment('Codice identificativo breve');
            $table->text('descrizione')->nullable();
            $table->date('data_inizio')->nullable()->comment('Data inizio missione/operazione');
            $table->date('data_fine')->nullable()->comment('Data fine missione/operazione');
            $table->enum('stato', ['attivo', 'completato', 'sospeso', 'pianificato'])->default('attivo');
            $table->string('colore_badge', 7)->default('#007bff')->comment('Colore per la visualizzazione');
            $table->timestamps();
            
            $table->index(['stato', 'data_inizio']);
            });
        }

        // 3. Creare tabella pivot per militari-approntamenti solo se non esiste
        if (!Schema::hasTable('militare_approntamenti')) {
            Schema::create('militare_approntamenti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('approntamento_id')->constrained('approntamenti')->onDelete('cascade');
            $table->string('ruolo', 100)->nullable()->comment('Ruolo nel approntamento');
            $table->date('data_assegnazione')->comment('Data assegnazione all\'approntamento');
            $table->date('data_fine_assegnazione')->nullable();
            $table->boolean('principale')->default(false)->comment('Se è l\'approntamento principale');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->unique(['militare_id', 'approntamento_id', 'data_assegnazione'], 'militare_approntamenti_unique');
            $table->index(['approntamento_id', 'data_assegnazione']);
            });
        }

        // 4. Creare tabella patenti militari solo se non esiste
        if (!Schema::hasTable('patenti_militari')) {
            Schema::create('patenti_militari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('categoria', 10)->comment('Categoria patente (A, B, C, etc.)');
            $table->string('tipo', 50)->nullable()->comment('Tipo specifico (es. ABIL, PROF)');
            $table->date('data_ottenimento');
            $table->date('data_scadenza');
            $table->string('numero_patente', 50)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index(['militare_id', 'categoria']);
            $table->index(['data_scadenza']);
            });
        }

        // 5. Creare tabella tipi di servizio (codici giornalieri) solo se non esiste
        if (!Schema::hasTable('tipi_servizio')) {
            Schema::create('tipi_servizio', function (Blueprint $table) {
            $table->id();
            $table->string('codice', 10)->unique()->comment('Codice breve (TO, lo, S-UI, etc.)');
            $table->string('nome', 100)->comment('Nome completo del servizio');
            $table->text('descrizione')->nullable();
            $table->string('colore_badge', 7)->default('#6c757d')->comment('Colore per la visualizzazione');
            $table->enum('categoria', ['servizio', 'permesso', 'assenza', 'formazione', 'missione'])
                  ->default('servizio');
            $table->boolean('attivo')->default(true);
            $table->integer('ordine')->default(0)->comment('Ordine di visualizzazione');
            $table->timestamps();
            });
        }

        // 6. Creare tabella pianificazioni mensili solo se non esiste
        if (!Schema::hasTable('pianificazioni_mensili')) {
            Schema::create('pianificazioni_mensili', function (Blueprint $table) {
            $table->id();
            $table->integer('anno')->comment('Anno della pianificazione');
            $table->integer('mese')->comment('Mese della pianificazione (1-12)');
            $table->string('nome', 100)->comment('Nome del calendario (es. Settembre 2025)');
            $table->text('descrizione')->nullable();
            $table->enum('stato', ['bozza', 'attiva', 'completata', 'archiviata'])->default('bozza');
            $table->date('data_creazione');
            $table->foreignId('creato_da')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['anno', 'mese']);
            $table->index(['stato', 'anno', 'mese']);
            });
        }

        // 7. Creare tabella pianificazioni giornaliere dettagliate solo se non esiste
        if (!Schema::hasTable('pianificazioni_giornaliere')) {
            Schema::create('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pianificazione_mensile_id')
                  ->constrained('pianificazioni_mensili')->onDelete('cascade');
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->integer('giorno')->comment('Giorno del mese (1-31)');
            $table->foreignId('tipo_servizio_id')->nullable()
                  ->constrained('tipi_servizio')->onDelete('set null');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->unique(['pianificazione_mensile_id', 'militare_id', 'giorno'], 'pianificazioni_giornaliere_unique');
            $table->index(['pianificazione_mensile_id', 'giorno'], 'pianificazioni_giornaliere_mensile_giorno');
            $table->index(['militare_id', 'giorno'], 'pianificazioni_giornaliere_militare_giorno');
            });
        }

        // 8. Modificare tabella presenze per supportare tipi di servizio dettagliati (solo se non esistono)
        if (!Schema::hasColumn('presenze', 'tipo_servizio_id')) {
            Schema::table('presenze', function (Blueprint $table) {
                $table->foreignId('tipo_servizio_id')->nullable()->after('stato')
                      ->constrained('tipi_servizio')->onDelete('set null')
                      ->comment('Tipo di servizio dettagliato');
            });
        }
        
        if (!Schema::hasColumn('presenze', 'note_servizio')) {
            Schema::table('presenze', function (Blueprint $table) {
                $table->text('note_servizio')->nullable()->after('tipo_servizio_id')
                      ->comment('Note specifiche sul servizio del giorno');
            });
        }

        // 9. Aggiungere foreign key per approntamento principale (solo se colonna esiste e non ha già la foreign key)
        if (Schema::hasColumn('militari', 'approntamento_principale_id')) {
            try {
                Schema::table('militari', function (Blueprint $table) {
                    $table->foreign('approntamento_principale_id')
                          ->references('id')->on('approntamenti')
                          ->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key potrebbe già esistere, ignora l'errore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovere in ordine inverso per rispettare le foreign keys
        Schema::table('militari', function (Blueprint $table) {
            $table->dropForeign(['approntamento_principale_id']);
        });
        
        Schema::table('presenze', function (Blueprint $table) {
            $table->dropForeign(['tipo_servizio_id']);
            $table->dropColumn(['tipo_servizio_id', 'note_servizio']);
        });
        
        Schema::dropIfExists('pianificazioni_giornaliere');
        Schema::dropIfExists('pianificazioni_mensili');
        Schema::dropIfExists('tipi_servizio');
        Schema::dropIfExists('patenti_militari');
        Schema::dropIfExists('militare_approntamenti');
        Schema::dropIfExists('approntamenti');
        
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn([
                'categoria', 
                'numero_matricola', 
                'approntamento_principale_id'
            ]);
        });
    }
};
