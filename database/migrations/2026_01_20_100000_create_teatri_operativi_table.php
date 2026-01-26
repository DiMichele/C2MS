<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione per la tabella teatri_operativi
 * 
 * Gestisce i Teatri Operativi (es. Kosovo, Libano, etc.)
 * I militari vengono assegnati ai teatri operativi con stato bozza/confermato
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teatri_operativi', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->string('codice', 20)->nullable()->unique();
            $table->text('descrizione')->nullable();
            $table->date('data_inizio')->nullable();
            $table->date('data_fine')->nullable();
            $table->enum('stato', ['attivo', 'completato', 'sospeso', 'pianificato'])->default('attivo');
            $table->string('colore_badge', 7)->default('#0a2342'); // Colore HEX per badge
            $table->unsignedBigInteger('compagnia_id')->nullable(); // Per segregazione dati
            $table->timestamps();
            
            // Indici
            $table->index('stato');
            $table->index('compagnia_id');
            $table->index(['data_inizio', 'data_fine']);
            
            // Foreign key
            $table->foreign('compagnia_id')
                  ->references('id')
                  ->on('compagnie')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teatri_operativi');
    }
};
