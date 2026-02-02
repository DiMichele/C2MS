<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per ristrutturare le prenotazioni PEFO
 * 
 * Aggiunge tipo_prova (agility/resistenza) e gestione conferme singole per militari
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifiche alla tabella prenotazioni_pefo
        Schema::table('prenotazioni_pefo', function (Blueprint $table) {
            // Aggiunge tipo_prova
            $table->enum('tipo_prova', ['agility', 'resistenza'])->after('id')
                ->comment('Tipo di prova: agility o resistenza');
        });

        // Rimuovi colonne non più necessarie (conferma globale)
        Schema::table('prenotazioni_pefo', function (Blueprint $table) {
            // Rimuovi foreign key prima di droppare la colonna
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['confirmed_by', 'data_conferma']);
        });

        // Modifica lo stato: rimuovi 'confermato', usa solo 'attivo' e 'annullato'
        // Prima aggiorna i record esistenti
        DB::statement("UPDATE prenotazioni_pefo SET stato = 'prenotato' WHERE stato = 'confermato'");
        
        // Modifica l'enum (MySQL richiede ALTER COLUMN)
        DB::statement("ALTER TABLE prenotazioni_pefo MODIFY COLUMN stato ENUM('prenotato', 'attivo', 'annullato') DEFAULT 'attivo'");
        
        // Aggiorna tutti i 'prenotato' a 'attivo'
        DB::statement("UPDATE prenotazioni_pefo SET stato = 'attivo' WHERE stato = 'prenotato'");
        
        // Rimuovi 'prenotato' dall'enum
        DB::statement("ALTER TABLE prenotazioni_pefo MODIFY COLUMN stato ENUM('attivo', 'annullato') DEFAULT 'attivo'");

        // 2. Modifiche alla tabella pivot militari_prenotazioni_pefo
        Schema::table('militari_prenotazioni_pefo', function (Blueprint $table) {
            // Aggiungi campi per conferma singola
            $table->boolean('confermato')->default(false)->after('militare_id')
                ->comment('Se il militare è confermato per questa prenotazione');
            $table->timestamp('data_conferma')->nullable()->after('confermato')
                ->comment('Data e ora di conferma');
            $table->foreignId('confermato_da')->nullable()->after('data_conferma')
                ->constrained('users')->nullOnDelete()
                ->comment('Utente che ha confermato');
        });

        // 3. Imposta tipo_prova di default per prenotazioni esistenti
        // (assumiamo agility come default)
        DB::statement("UPDATE prenotazioni_pefo SET tipo_prova = 'agility' WHERE tipo_prova IS NULL OR tipo_prova = ''");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina campi pivot
        Schema::table('militari_prenotazioni_pefo', function (Blueprint $table) {
            $table->dropForeign(['confermato_da']);
            $table->dropColumn(['confermato', 'data_conferma', 'confermato_da']);
        });

        // Ripristina stato originale
        DB::statement("ALTER TABLE prenotazioni_pefo MODIFY COLUMN stato ENUM('prenotato', 'confermato', 'annullato') DEFAULT 'prenotato'");

        // Ripristina colonne conferma globale
        Schema::table('prenotazioni_pefo', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('created_by')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('data_conferma')->nullable()->after('confirmed_by');
        });

        // Rimuovi tipo_prova
        Schema::table('prenotazioni_pefo', function (Blueprint $table) {
            $table->dropColumn('tipo_prova');
        });
    }
};
