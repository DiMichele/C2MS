<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabella pivot per gestire il numero di posti per servizio
     * specifico per ogni settimana. Questo permette di modificare
     * i posti di un servizio senza influenzare le settimane passate.
     */
    public function up(): void
    {
        Schema::create('servizio_turno_settimana', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_settimanale_id')
                ->constrained('turni_settimanali')
                ->onDelete('cascade');
            $table->foreignId('servizio_turno_id')
                ->constrained('servizi_turno')
                ->onDelete('cascade');
            $table->integer('num_posti')->default(0);
            $table->boolean('smontante_cpt')->default(false);
            $table->boolean('attivo')->default(true);
            $table->timestamps();

            // Indice univoco per evitare duplicati
            $table->unique(['turno_settimanale_id', 'servizio_turno_id'], 'servizio_settimana_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servizio_turno_settimana');
    }
};
