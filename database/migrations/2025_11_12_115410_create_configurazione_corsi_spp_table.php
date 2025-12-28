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
        Schema::create('configurazione_corsi_spp', function (Blueprint $table) {
            $table->id();
            $table->string('codice_corso', 100)->unique()->comment('Codice univoco del corso (es. lavoratore_4h, preposto)');
            $table->string('nome_corso', 255)->comment('Nome descrittivo del corso');
            $table->integer('durata_anni')->unsigned()->comment('Durata validità in anni (es. 4, 2, 1)');
            $table->enum('tipo', ['formazione', 'accordo_stato_regione'])->comment('Tipo di corso');
            $table->boolean('attivo')->default(true)->comment('Se true, il corso è visibile nelle pagine SPP');
            $table->integer('ordine')->unsigned()->default(0)->comment('Ordine di visualizzazione');
            $table->timestamps();
            
            // Indici per performance
            $table->index('tipo');
            $table->index('attivo');
            $table->index(['tipo', 'attivo', 'ordine'], 'idx_tipo_attivo_ordine');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurazione_corsi_spp');
    }
};
