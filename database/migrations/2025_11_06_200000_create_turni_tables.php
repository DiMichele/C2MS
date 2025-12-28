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
        // Tabella servizi_turno (tipi di servizi settimanali)
        Schema::create('servizi_turno', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->comment('Nome del servizio (es. Guardia, Piantone, ecc.)');
            $table->integer('num_posti')->default(1)->comment('Numero di militari richiesti per questo servizio');
            $table->boolean('attivo')->default(true)->comment('Se il servizio è attivo');
            $table->integer('ordine')->default(0)->comment('Ordine di visualizzazione');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // Tabella turni_settimanali (una settimana di turni)
        Schema::create('turni_settimanali', function (Blueprint $table) {
            $table->id();
            $table->integer('anno')->comment('Anno di riferimento');
            $table->integer('numero_settimana')->comment('Numero settimana (1-53)');
            $table->date('data_inizio')->comment('Lunedì della settimana');
            $table->date('data_fine')->comment('Domenica della settimana');
            $table->boolean('approvato')->default(false)->comment('Se il turno è stato approvato');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->unique(['anno', 'numero_settimana']);
        });

        // Tabella assegnazioni_turno (chi fa cosa e quando)
        Schema::create('assegnazioni_turno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_settimanale_id')->constrained('turni_settimanali')->onDelete('cascade');
            $table->foreignId('servizio_turno_id')->constrained('servizi_turno')->onDelete('cascade');
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->date('data_servizio')->comment('Data specifica del servizio');
            $table->boolean('sincronizzato_cpt')->default(false)->comment('Se è stato sincronizzato con il CPT');
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->index(['turno_settimanale_id', 'data_servizio']);
            $table->index(['militare_id', 'data_servizio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assegnazioni_turno');
        Schema::dropIfExists('turni_settimanali');
        Schema::dropIfExists('servizi_turno');
    }
};

