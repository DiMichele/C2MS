<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Crea la tabella prenotazioni_approntamenti
 * 
 * Gestisce le prenotazioni dei militari per specifiche cattedre
 * Permette di prenotare un militare per una cattedra e poi confermare la partecipazione
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prenotazioni_approntamenti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('militare_id');
            $table->unsignedBigInteger('teatro_operativo_id');
            $table->string('cattedra', 100); // Nome della colonna/cattedra (es. stress_management)
            $table->date('data_prenotazione'); // Data prevista per la cattedra
            $table->enum('stato', ['prenotato', 'confermato', 'annullato'])->default('prenotato');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // Utente che ha creato la prenotazione
            $table->timestamps();
            
            // Indici
            $table->index('stato');
            $table->index('cattedra');
            $table->index('data_prenotazione');
            $table->index(['militare_id', 'teatro_operativo_id', 'cattedra'], 'pren_app_mil_teatro_cat_idx');
            
            // Foreign keys
            $table->foreign('militare_id')
                  ->references('id')
                  ->on('militari')
                  ->onDelete('cascade');
            
            $table->foreign('teatro_operativo_id')
                  ->references('id')
                  ->on('teatri_operativi')
                  ->onDelete('cascade');
            
            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prenotazioni_approntamenti');
    }
};
