<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per rimuovere i campi punteggio dal sistema poligoni.
 * 
 * L'utente ha chiarito che per i poligoni serve solo:
 * - Data in cui è stato fatto
 * - Durata validità (dalla configurazione del tipo)
 * - Data scadenza (calcolata automaticamente)
 * 
 * I campi punteggio_minimo, punteggio_massimo, punteggio ed esito non sono necessari.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rimuovi punteggio_minimo e punteggio_massimo dalla tabella tipi_poligono
        if (Schema::hasTable('tipi_poligono')) {
            Schema::table('tipi_poligono', function (Blueprint $table) {
                if (Schema::hasColumn('tipi_poligono', 'punteggio_minimo')) {
                    $table->dropColumn('punteggio_minimo');
                }
                if (Schema::hasColumn('tipi_poligono', 'punteggio_massimo')) {
                    $table->dropColumn('punteggio_massimo');
                }
            });
        }

        // Rimuovi punteggio, esito e altri campi legacy dalla tabella poligoni (se esiste)
        if (Schema::hasTable('poligoni')) {
            Schema::table('poligoni', function (Blueprint $table) {
                if (Schema::hasColumn('poligoni', 'punteggio')) {
                    $table->dropColumn('punteggio');
                }
                if (Schema::hasColumn('poligoni', 'esito')) {
                    $table->dropColumn('esito');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ricrea i campi in tipi_poligono
        if (Schema::hasTable('tipi_poligono')) {
            Schema::table('tipi_poligono', function (Blueprint $table) {
                if (!Schema::hasColumn('tipi_poligono', 'punteggio_minimo')) {
                    $table->integer('punteggio_minimo')->default(0)->after('descrizione')
                          ->comment('Punteggio minimo per superare il poligono');
                }
                if (!Schema::hasColumn('tipi_poligono', 'punteggio_massimo')) {
                    $table->integer('punteggio_massimo')->default(100)->after('punteggio_minimo')
                          ->comment('Punteggio massimo ottenibile');
                }
            });
        }

        // Ricrea i campi in poligoni
        if (Schema::hasTable('poligoni')) {
            Schema::table('poligoni', function (Blueprint $table) {
                if (!Schema::hasColumn('poligoni', 'punteggio')) {
                    $table->integer('punteggio')->nullable()->after('data_poligono')
                          ->comment('Punteggio ottenuto');
                }
                if (!Schema::hasColumn('poligoni', 'esito')) {
                    $table->enum('esito', ['SUPERATO', 'NON_SUPERATO', 'DA_VALUTARE'])
                          ->default('DA_VALUTARE')->after('punteggio')
                          ->comment('Esito del poligono');
                }
            });
        }
    }
};
