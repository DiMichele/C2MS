<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assegnazioni_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_settimanale_id')->constrained('turni_settimanali')->onDelete('cascade');
            $table->foreignId('servizio_turno_id')->constrained('servizi_turno')->onDelete('cascade');
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->date('data_servizio');                                  // Data specifica del servizio
            $table->string('giorno_settimana', 20);                         // "GIOVEDI", "VENERDI", etc.
            $table->integer('posizione')->default(1);                       // Posizione nel servizio multiplo
            $table->text('note')->nullable();
            $table->boolean('sincronizzato_cpt')->default(false);          // Se già sincronizzato con CPT
            $table->timestamps();
            
            $table->index('data_servizio');
            $table->index('militare_id');
            $table->index('servizio_turno_id');
            $table->index('sincronizzato_cpt');
            
            // Un militare può essere assegnato una sola volta per servizio/data
            $table->unique(['turno_settimanale_id', 'servizio_turno_id', 'militare_id', 'data_servizio'], 'unique_assegnazione');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assegnazioni_turno');
    }
};

