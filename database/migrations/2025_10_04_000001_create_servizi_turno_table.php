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
        Schema::create('servizi_turno', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);                                    // "GRADUATO DI BTG", "VIGILANZA PDT"
            $table->string('codice', 20)->unique();                         // "G-BTG", "PDT1", "S-SI"
            $table->string('sigla_cpt', 10)->nullable();                    // Sigla per CPT (es: "S-SI", "G1")
            $table->text('descrizione')->nullable();
            $table->integer('num_posti')->default(1);                       // Numero posti disponibili
            $table->enum('tipo', ['singolo', 'multiplo'])->default('singolo'); // Se ammette più assegnati
            $table->time('orario_inizio')->nullable();                      // Es: 07:30
            $table->time('orario_fine')->nullable();                        // Es: 17:00
            $table->integer('ordine')->default(0);                          // Per ordinamento visualizzazione
            $table->boolean('attivo')->default(true);
            $table->timestamps();
            
            $table->index('codice');
            $table->index('attivo');
            $table->index('ordine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servizi_turno');
    }
};

