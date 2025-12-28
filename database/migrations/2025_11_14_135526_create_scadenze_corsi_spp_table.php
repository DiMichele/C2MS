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
        Schema::create('scadenze_corsi_spp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('configurazione_corso_spp_id')->constrained('configurazione_corsi_spp')->onDelete('cascade');
            $table->date('data_conseguimento')->nullable();
            $table->timestamps();

            // Indici per performance
            $table->index(['militare_id', 'configurazione_corso_spp_id'], 'idx_militare_corso');
            $table->index('data_conseguimento');

            // Constraint univoco: un militare può avere solo una scadenza per corso
            $table->unique(['militare_id', 'configurazione_corso_spp_id'], 'unique_militare_corso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scadenze_corsi_spp');
    }
};
