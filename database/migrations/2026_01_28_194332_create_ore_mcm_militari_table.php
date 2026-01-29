<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabella per tracciare le ore MCM svolte dai militari.
     * MCM richiede un totale di 40 ore per essere completato.
     * Lun-Gio = 8 ore/giorno, Ven = 4 ore/giorno.
     */
    public function up(): void
    {
        Schema::create('ore_mcm_militari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('teatro_operativo_id')->nullable()->constrained('teatri_operativi')->onDelete('set null');
            $table->date('data'); // Data della giornata MCM
            $table->integer('ore')->default(0); // Ore svolte in quella giornata (8 lun-gio, 4 ven)
            $table->enum('stato', ['pianificato', 'completato', 'annullato'])->default('pianificato');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indici per performance
            $table->index(['militare_id', 'teatro_operativo_id']);
            $table->index(['data', 'stato']);
            
            // Un militare può avere una sola registrazione per data
            $table->unique(['militare_id', 'data']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ore_mcm_militari');
    }
};
