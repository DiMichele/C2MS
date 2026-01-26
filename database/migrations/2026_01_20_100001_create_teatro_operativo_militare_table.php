<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione per la tabella pivot teatro_operativo_militare
 * 
 * Gestisce l'assegnazione dei militari ai Teatri Operativi
 * con stato bozza/confermato per ciascun militare
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teatro_operativo_militare', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teatro_operativo_id');
            $table->unsignedBigInteger('militare_id');
            $table->enum('stato', ['bozza', 'confermato'])->default('bozza');
            $table->string('ruolo', 100)->nullable(); // Ruolo nel teatro (es. Comandante, Operatore, etc.)
            $table->text('note')->nullable();
            $table->date('data_assegnazione')->nullable();
            $table->date('data_fine_assegnazione')->nullable();
            $table->timestamps();
            
            // Indici
            $table->index('stato');
            $table->index(['teatro_operativo_id', 'militare_id']);
            
            // Unique constraint: un militare può essere assegnato una sola volta per teatro
            $table->unique(['teatro_operativo_id', 'militare_id'], 'teatro_militare_unique');
            
            // Foreign keys
            $table->foreign('teatro_operativo_id')
                  ->references('id')
                  ->on('teatri_operativi')
                  ->onDelete('cascade');
            
            $table->foreign('militare_id')
                  ->references('id')
                  ->on('militari')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teatro_operativo_militare');
    }
};
