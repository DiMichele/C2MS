<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare le tabelle PEFO
 * 
 * Crea:
 * - Campo data_ultimo_pefo nella tabella militari
 * - Tabella prenotazioni_pefo
 * - Tabella pivot militari_prenotazioni_pefo
 * - Campo prenotazione_pefo_id nella tabella pianificazioni_giornaliere
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Aggiunge il campo data_ultimo_pefo alla tabella militari
        Schema::table('militari', function (Blueprint $table) {
            $table->date('data_ultimo_pefo')->nullable()->after('nos_status')
                ->comment('Data ultimo corso PEFO completato');
        });

        // 2. Crea la tabella prenotazioni_pefo
        Schema::create('prenotazioni_pefo', function (Blueprint $table) {
            $table->id();
            $table->string('nome_prenotazione', 255)
                ->comment('Nome della prenotazione (es: Prove Agility, 14/02/2026)');
            $table->date('data_prenotazione')
                ->comment('Data in cui si svolge il corso PEFO');
            $table->enum('stato', ['prenotato', 'confermato', 'annullato'])
                ->default('prenotato')
                ->comment('Stato della prenotazione');
            $table->text('note')->nullable()
                ->comment('Note aggiuntive');
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Utente che ha creato la prenotazione');
            $table->foreignId('confirmed_by')->nullable()
                ->constrained('users')->nullOnDelete()
                ->comment('Utente che ha confermato la prenotazione');
            $table->timestamp('data_conferma')->nullable()
                ->comment('Data/ora di conferma');
            $table->timestamps();

            // Indici per performance
            $table->index('stato');
            $table->index('data_prenotazione');
            $table->index(['stato', 'data_prenotazione']);
        });

        // 3. Crea la tabella pivot militari_prenotazioni_pefo
        Schema::create('militari_prenotazioni_pefo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prenotazione_pefo_id')
                ->constrained('prenotazioni_pefo')->cascadeOnDelete()
                ->comment('Riferimento alla prenotazione');
            $table->foreignId('militare_id')
                ->constrained('militari')->cascadeOnDelete()
                ->comment('Riferimento al militare');
            $table->timestamps();

            // Indice unique per evitare duplicati
            $table->unique(['prenotazione_pefo_id', 'militare_id'], 'unique_prenotazione_militare');
            
            // Indici per query frequenti
            $table->index('militare_id');
        });

        // 4. Aggiunge il campo prenotazione_pefo_id alla tabella pianificazioni_giornaliere
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->foreignId('prenotazione_pefo_id')->nullable()->after('prenotazione_approntamento_id')
                ->constrained('prenotazioni_pefo')->nullOnDelete()
                ->comment('Riferimento alla prenotazione PEFO collegata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuove il campo da pianificazioni_giornaliere
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->dropForeign(['prenotazione_pefo_id']);
            $table->dropColumn('prenotazione_pefo_id');
        });

        // Elimina la tabella pivot
        Schema::dropIfExists('militari_prenotazioni_pefo');

        // Elimina la tabella prenotazioni_pefo
        Schema::dropIfExists('prenotazioni_pefo');

        // Rimuove il campo data_ultimo_pefo dalla tabella militari
        Schema::table('militari', function (Blueprint $table) {
            $table->dropColumn('data_ultimo_pefo');
        });
    }
};
