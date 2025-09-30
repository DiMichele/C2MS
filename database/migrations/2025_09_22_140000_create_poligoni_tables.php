<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per creare le tabelle per la gestione dei poligoni
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabella per i tipi di poligono
        if (!Schema::hasTable('tipi_poligono')) {
            Schema::create('tipi_poligono', function (Blueprint $table) {
                $table->id();
                $table->string('nome', 100)->comment('Nome del tipo di poligono (es. Tiro di Precisione, Tiro Rapido, etc.)');
                $table->text('descrizione')->nullable()->comment('Descrizione dettagliata del tipo di poligono');
                $table->integer('punteggio_minimo')->default(0)->comment('Punteggio minimo per superare il poligono');
                $table->integer('punteggio_massimo')->default(100)->comment('Punteggio massimo ottenibile');
                $table->boolean('attivo')->default(true)->comment('Se il tipo di poligono è attivo');
                $table->timestamps();
                
                $table->index('attivo');
            });
        }

        // Tabella per i poligoni effettuati
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

        // Aggiungere campo ultimo_poligono_id ai militari
        if (!Schema::hasColumn('militari', 'ultimo_poligono_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->foreignId('ultimo_poligono_id')->nullable()->after('foto_path')
                      ->constrained('poligoni')->onDelete('set null')
                      ->comment('Riferimento all\'ultimo poligono effettuato');
            });
        }

        // Aggiungere campo data_ultimo_poligono per performance
        if (!Schema::hasColumn('militari', 'data_ultimo_poligono')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->date('data_ultimo_poligono')->nullable()->after('ultimo_poligono_id')
                      ->comment('Data dell\'ultimo poligono (campo denormalizzato per performance)');
                
                $table->index('data_ultimo_poligono');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi i campi dai militari
        if (Schema::hasColumn('militari', 'ultimo_poligono_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->dropForeign(['ultimo_poligono_id']);
                $table->dropColumn('ultimo_poligono_id');
            });
        }

        if (Schema::hasColumn('militari', 'data_ultimo_poligono')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->dropColumn('data_ultimo_poligono');
            });
        }

        // Elimina le tabelle
        Schema::dropIfExists('poligoni');
        Schema::dropIfExists('tipi_poligono');
    }
};
