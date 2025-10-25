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
        Schema::create('trasparenza_servizi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->unsignedInteger('anno');
            $table->unsignedTinyInteger('mese'); // 1-12
            $table->unsignedTinyInteger('giorno'); // 1-31
            $table->string('codice_servizio', 20)->nullable(); // S-SI, S-UP, S-CG, etc.
            $table->timestamps();
            
            // Indici per performance
            $table->index(['militare_id', 'anno', 'mese']);
            $table->unique(['militare_id', 'anno', 'mese', 'giorno']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trasparenza_servizi');
    }
};
