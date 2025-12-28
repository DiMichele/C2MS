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
        Schema::create('configurazione_campi_anagrafica', function (Blueprint $table) {
            $table->id();
            $table->string('nome_campo')->unique(); // slug per DB (es: 'sesso')
            $table->string('etichetta'); // Label da mostrare (es: 'Sesso')
            $table->enum('tipo_campo', ['text', 'select', 'date', 'number', 'textarea', 'checkbox', 'email', 'tel'])->default('text');
            $table->json('opzioni')->nullable(); // Per select: ["M", "F", "Altro"]
            $table->integer('ordine')->default(0);
            $table->boolean('attivo')->default(true);
            $table->boolean('obbligatorio')->default(false);
            $table->text('descrizione')->nullable(); // Help text
            $table->timestamps();

            // Indici
            $table->index('nome_campo');
            $table->index('attivo');
            $table->index('ordine');
        });
        
        // Crea tabella pivot per memorizzare i valori dei campi custom per ogni militare
        Schema::create('valori_campi_anagrafica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade');
            $table->foreignId('configurazione_campo_id')->constrained('configurazione_campi_anagrafica')->onDelete('cascade');
            $table->text('valore')->nullable();
            $table->timestamps();
            
            $table->unique(['militare_id', 'configurazione_campo_id'], 'militare_campo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valori_campi_anagrafica');
        Schema::dropIfExists('configurazione_campi_anagrafica');
    }
};
