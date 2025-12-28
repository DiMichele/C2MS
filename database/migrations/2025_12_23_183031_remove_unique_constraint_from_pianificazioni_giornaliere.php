<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rimuove il vincolo UNIQUE per permettere impegni multipli
     * nello stesso giorno per lo stesso militare
     */
    public function up(): void
    {
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            // Rimuovi il vincolo UNIQUE esistente
            $table->dropUnique('pianificazioni_giornaliere_unique');
            
            // Aggiungi un indice normale (non unique) per mantenere le performance
            $table->index(['pianificazione_mensile_id', 'militare_id', 'giorno'], 'pianificazioni_giornaliere_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            // Rimuovi l'indice normale
            $table->dropIndex('pianificazioni_giornaliere_lookup');
            
            // Ripristina il vincolo UNIQUE
            $table->unique(['pianificazione_mensile_id', 'militare_id', 'giorno'], 'pianificazioni_giornaliere_unique');
        });
    }
};
