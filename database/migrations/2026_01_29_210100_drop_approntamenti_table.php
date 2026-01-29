<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per eliminare la tabella approntamenti.
 * 
 * La tabella approntamenti è stata sostituita da teatri_operativi.
 * Prima di eliminare, assicurarsi che tutti i dati necessari siano stati migrati.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prima rimuovi le FK dalla tabella militare_approntamenti che referenzia approntamenti
        if (Schema::hasTable('militare_approntamenti')) {
            Schema::table('militare_approntamenti', function (Blueprint $table) {
                // Rimuovi la FK se esiste
                try {
                    $table->dropForeign(['approntamento_id']);
                } catch (\Exception $e) {
                    // FK potrebbe non esistere, ignora
                }
            });
            
            // Elimina la tabella pivot militare_approntamenti (legacy)
            Schema::dropIfExists('militare_approntamenti');
        }
        
        // Ora elimina la tabella approntamenti
        Schema::dropIfExists('approntamenti');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea la tabella approntamenti
        if (!Schema::hasTable('approntamenti')) {
            Schema::create('approntamenti', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100);
                $table->string('codice', 20)->nullable();
                $table->text('descrizione')->nullable();
                $table->date('data_inizio')->nullable();
                $table->date('data_fine')->nullable();
                $table->enum('stato', ['attivo', 'completato', 'sospeso', 'pianificato'])->default('attivo');
                $table->string('colore_badge', 7)->default('#007bff');
                $table->timestamps();
            });
        }
    }
};
