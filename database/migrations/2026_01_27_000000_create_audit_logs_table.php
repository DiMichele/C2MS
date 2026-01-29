<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per la creazione della tabella audit_logs.
 * 
 * Questa tabella traccia tutte le azioni degli utenti nel sistema,
 * permettendo di sapere chi ha fatto cosa e quando.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // Chi ha eseguito l'azione
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable(); // Nome utente al momento dell'azione (per storico)
            
            // Tipo di azione
            $table->enum('action', [
                'login',           // Accesso al sistema
                'logout',          // Uscita dal sistema
                'login_failed',    // Tentativo di accesso fallito
                'create',          // Creazione di un record
                'update',          // Modifica di un record
                'delete',          // Eliminazione di un record
                'view',            // Visualizzazione di un record
                'export',          // Esportazione dati
                'import',          // Importazione dati
                'password_change', // Cambio password
                'permission_change', // Modifica permessi
                'other'            // Altre azioni
            ])->index();
            
            // Descrizione leggibile dell'azione
            $table->string('description');
            
            // Entità coinvolta (es. "militare", "certificato", "utente")
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('entity_name')->nullable(); // Nome dell'entità per riferimento rapido
            
            // Dati aggiuntivi (JSON per flessibilità)
            $table->json('old_values')->nullable(); // Valori prima della modifica
            $table->json('new_values')->nullable(); // Valori dopo la modifica
            $table->json('metadata')->nullable();   // Altri dati utili
            
            // Informazioni sulla richiesta
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, etc.
            
            // Compagnia dell'utente (per filtri)
            $table->foreignId('compagnia_id')->nullable()->constrained('compagnie')->nullOnDelete();
            
            // Esito dell'azione
            $table->enum('status', ['success', 'failed', 'warning'])->default('success');
            
            $table->timestamps();
            
            // Indici per ricerche veloci
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
