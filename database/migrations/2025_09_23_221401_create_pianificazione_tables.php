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
        // Tabella per pianificazioni mensili
        Schema::create('pianificazioni_mensili', function (Blueprint $table) {
            $table->id();
            $table->integer('mese');
            $table->integer('anno');
            $table->string('nome');
            $table->boolean('attiva')->default(true);
            $table->timestamp('data_creazione')->useCurrent();
            $table->timestamps();
            
            $table->unique(['mese', 'anno']);
        });

        // Tabella per pianificazioni giornaliere
        Schema::create('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('pianificazione_mensile_id')->constrained('pianificazioni_mensili')->onDelete('cascade');
            $table->integer('giorno');
            $table->foreignId('tipo_servizio_id')->nullable()->constrained('tipi_servizio')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['militare_id', 'pianificazione_mensile_id', 'giorno'], 'pianificazioni_giornaliere_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pianificazioni_giornaliere');
        Schema::dropIfExists('pianificazioni_mensili');
    }
};