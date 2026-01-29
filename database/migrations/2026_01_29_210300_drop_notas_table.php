<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per eliminare la tabella notas (legacy).
 * 
 * La tabella notas è stata sostituita dal campo note in militari.
 * Ogni militare può avere note personali direttamente nel campo note.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Elimina la tabella notas se esiste
        Schema::dropIfExists('notas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea la tabella notas
        if (!Schema::hasTable('notas')) {
            Schema::create('notas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->text('contenuto')->nullable();
                $table->timestamps();
                
                $table->unique(['militare_id', 'user_id']);
            });
        }
    }
};
