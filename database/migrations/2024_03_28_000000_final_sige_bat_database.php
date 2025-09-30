<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FinalSigeBatDatabase extends Migration
{
    /**
     * Run the migrations.
     * Questa migration crea un database unificato che combina le caratteristiche migliori
     * di entrambe le versioni del database (sige_batUmberto e sige_batMichele).
     *
     * @return void
     */
    public function up()
    {
        // Standard Laravel tables (solo se non esistono)
        if (!Schema::hasTable('users')) {
            $this->createUsersTable();
        }
        if (!Schema::hasTable('password_reset_tokens')) {
            $this->createPasswordResetTokensTable();
        }
        if (!Schema::hasTable('failed_jobs')) {
            $this->createFailedJobsTable();
        }
        if (!Schema::hasTable('cache')) {
            $this->createCacheTable();
        }
        if (!Schema::hasTable('cache_locks')) {
            $this->createCacheLocksTable();
        }
        if (!Schema::hasTable('jobs')) {
            $this->createJobsTable();
        }
        if (!Schema::hasTable('job_batches')) {
            $this->createJobBatchesTable();
        }
        if (!Schema::hasTable('personal_access_tokens')) {
            $this->createPersonalAccessTokensTable();
        }
        if (!Schema::hasTable('sessions')) {
            $this->createSessionsTable();
        }
        
        // Custom application tables
        $this->createGradiTable();
        $this->createCompagnieTable();
        $this->createPlotoniTable();
        $this->createPoliTable();
        $this->createMansioniTable();
        $this->createRuoliTable();
        $this->createMilitariTable();
        $this->createPresenzeTable();
        $this->createCertificatiTable();
        $this->createCertificatiLavoratoriTable();
        $this->createIdoneitaTable();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop tables in reverse order to rispetto delle foreign key constraints
        Schema::dropIfExists('idoneita');
        Schema::dropIfExists('certificati_lavoratori');
        Schema::dropIfExists('certificati');
        Schema::dropIfExists('presenze');
        Schema::dropIfExists('militari');
        Schema::dropIfExists('ruoli');
        Schema::dropIfExists('mansioni');
        Schema::dropIfExists('poli');
        Schema::dropIfExists('plotoni');
        Schema::dropIfExists('compagnie');
        Schema::dropIfExists('gradi');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }

    /**
     * Create users table.
     */
    private function createUsersTable()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Create password reset tokens table.
     */
    private function createPasswordResetTokensTable()
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Create failed jobs table.
     */
    private function createFailedJobsTable()
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    /**
     * Create cache table.
     */
    private function createCacheTable()
    {
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });
    }

    /**
     * Create cache locks table.
     */
    private function createCacheLocksTable()
    {
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });
    }

    /**
     * Create jobs table.
     */
    private function createJobsTable()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    /**
     * Create job batches table.
     */
    private function createJobBatchesTable()
    {
        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });
    }

    /**
     * Create personal access tokens table.
     */
    private function createPersonalAccessTokensTable()
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Create sessions table.
     */
    private function createSessionsTable()
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Create gradi table - migliorata con limite varchar ottimizzato
     */
    private function createGradiTable()
    {
        Schema::create('gradi', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);  // Limitato a 50 caratteri come in Michele
            $table->integer('ordine');
            $table->string('abbreviazione', 20)->nullable(); // Campo aggiunto per abbreviazioni utili
            $table->timestamps();
        });
    }

    /**
     * Create compagnie table - migliorata con descrizione e limiti campi
     */
    private function createCompagnieTable()
    {
        Schema::create('compagnie', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);  // Limitato a 100 caratteri come in Michele
            $table->string('descrizione')->nullable(); // Aggiunto da Michele
            $table->string('codice', 20)->nullable(); // Codice identificativo aggiuntivo
            $table->timestamps();
        });
    }

    /**
     * Create plotoni table - migliorata con limiti campi
     */
    private function createPlotoniTable()
    {
        Schema::create('plotoni', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);  // Limitato a 100 caratteri come in Michele
            $table->foreignId('compagnia_id')->constrained('compagnie')->onDelete('cascade');
            $table->string('descrizione')->nullable(); // Aggiunto per coerenza
            $table->timestamps();
        });
    }

    /**
     * Create poli table - migliorata con limiti campi
     */
    private function createPoliTable()
    {
        Schema::create('poli', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);  // Limitato a 100 caratteri come in Michele
            $table->foreignId('compagnia_id')->constrained('compagnie')->onDelete('cascade');
            $table->string('descrizione')->nullable(); // Aggiunto per coerenza
            $table->timestamps();
        });
    }

    /**
     * Create mansioni table - migliorata con descrizione e limiti campi
     */
    private function createMansioniTable()
    {
        Schema::create('mansioni', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);  // Limitato a 100 caratteri come in Michele
            $table->text('descrizione')->nullable(); // Da Michele
            $table->timestamps();
        });
    }

    /**
     * Create ruoli table - migliorata con descrizione e limiti campi
     */
    private function createRuoliTable()
    {
        Schema::create('ruoli', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50);  // Limitato a 50 caratteri come in Michele
            $table->text('descrizione')->nullable(); // Da Michele
            $table->timestamps();
        });
    }

    /**
     * Create militari table - versione unificata con tutti i campi e relazioni
     */
    private function createMilitariTable()
    {
        Schema::create('militari', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);  // Limitato a 100 caratteri come in Michele
            $table->string('cognome', 100);  // Limitato a 100 caratteri come in Michele
            $table->foreignId('grado_id')->nullable()->constrained('gradi')->onDelete('set null');
            $table->foreignId('compagnia_id')->nullable()->constrained('compagnie')->onDelete('set null'); // Da Michele
            $table->foreignId('plotone_id')->nullable()->constrained('plotoni')->onDelete('set null');
            $table->foreignId('polo_id')->nullable()->constrained('poli')->onDelete('set null');
            $table->foreignId('ruolo_id')->nullable()->constrained('ruoli')->onDelete('set null');
            $table->foreignId('mansione_id')->nullable()->constrained('mansioni')->onDelete('set null');
            $table->string('ruolo', 50)->default('Lavoratore'); // Da Umberto, mantenuto per compatibilità
            $table->text('certificati_note')->nullable(); // Migliorato a TEXT come in Michele
            $table->text('idoneita_note')->nullable(); // Migliorato a TEXT come in Michele
            $table->date('data_nascita')->nullable(); // Campo aggiuntivo utile
            $table->string('codice_fiscale', 16)->nullable(); // Campo aggiuntivo utile
            $table->string('email')->nullable(); // Campo aggiuntivo utile
            $table->string('telefono', 20)->nullable(); // Campo aggiuntivo utile
            $table->text('note')->nullable();
            $table->timestamps();

            // Indici aggiuntivi da Michele per prestazioni ottimizzate
            $table->index(['cognome', 'nome']);
            $table->index('grado_id');
        });
    }

    /**
     * Create presenze table - migliorata con note e vincoli
     */
    private function createPresenzeTable()
    {
        Schema::create('presenze', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->date('data');
            $table->enum('stato', ['Presente', 'Assente', 'Permesso', 'Licenza', 'Missione']); // Esteso con più opzioni
            $table->text('note')->nullable(); // Da Michele
            $table->timestamps();

            // Vincolo UNIQUE da Michele
            $table->unique(['militare_id', 'data']);
        });
    }

    /**
     * Create certificati table - da Umberto con miglioramenti
     */
    private function createCertificatiTable()
    {
        Schema::create('certificati', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza'); // NOT NULL come in Michele per migliore validazione
            $table->string('file_path')->nullable();
            $table->string('durata')->nullable();
            $table->text('note')->nullable(); // Aggiunto per consistenza
            $table->timestamps();
        });
    }

    /**
     * Create certificati_lavoratori table - versione migliorata
     */
    private function createCertificatiLavoratoriTable()
    {
        Schema::create('certificati_lavoratori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza'); // NOT NULL come in Michele per migliore validazione
            $table->string('file_path')->nullable();
            $table->text('note')->nullable();
            $table->boolean('in_scadenza')->default(false); // Flag utile per segnalare certificati in scadenza
            $table->timestamps();
            
            // Indice composto per velocizzare query sui certificati in scadenza
            $table->index(['militare_id', 'tipo', 'data_scadenza']);
        });
    }

    /**
     * Create idoneita table - versione migliorata
     */
    private function createIdoneitaTable()
    {
        Schema::create('idoneita', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->string('tipo', 100);
            $table->date('data_ottenimento');
            $table->date('data_scadenza'); // NOT NULL come in Michele per migliore validazione
            $table->string('file_path')->nullable();
            $table->text('note')->nullable();
            $table->boolean('in_scadenza')->default(false); // Flag utile per segnalare idoneità in scadenza
            $table->timestamps();
            
            // Indice composto per velocizzare query sulle idoneità in scadenza
            $table->index(['militare_id', 'tipo', 'data_scadenza']);
        });
    }
}
