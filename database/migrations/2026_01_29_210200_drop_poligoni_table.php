<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per eliminare la tabella poligoni (legacy).
 * 
 * La tabella poligoni è stata sostituita da scadenze_poligoni.
 * I campi punteggio, esito, istruttore, arma_utilizzata, colpi_sparati, colpi_a_segno
 * non sono più necessari (l'utente ha confermato che basta solo la data).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Elimina la tabella poligoni se esiste
        Schema::dropIfExists('poligoni');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea la tabella poligoni (versione legacy)
        if (!Schema::hasTable('poligoni')) {
            Schema::create('poligoni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade')
                      ->comment('Militare che ha effettuato il poligono');
                $table->foreignId('tipo_poligono_id')->constrained('tipi_poligono')->onDelete('cascade')
                      ->comment('Tipo di poligono effettuato');
                $table->date('data_poligono')->comment('Data di esecuzione del poligono');
                $table->integer('punteggio')->nullable()->comment('Punteggio ottenuto');
                $table->enum('esito', ['SUPERATO', 'NON_SUPERATO', 'DA_VALUTARE'])->default('DA_VALUTARE')
                      ->comment('Esito del poligono');
                $table->text('note')->nullable()->comment('Note aggiuntive sul poligono');
                $table->string('istruttore', 100)->nullable()->comment('Nome dell\'istruttore');
                $table->string('arma_utilizzata', 50)->nullable()->comment('Tipo di arma utilizzata');
                $table->integer('colpi_sparati')->nullable()->comment('Numero di colpi sparati');
                $table->integer('colpi_a_segno')->nullable()->comment('Numero di colpi a segno');
                $table->timestamps();
                
                $table->index(['militare_id', 'data_poligono']);
                $table->index('data_poligono');
                $table->index('esito');
            });
        }
    }
};
