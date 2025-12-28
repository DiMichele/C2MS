<?php

/**
|--------------------------------------------------------------------------
| SUGECO - Migration: Aggiungi colonne anzianita e statuti a militari
|--------------------------------------------------------------------------
| 
| Aggiunge le colonne mancanti alla tabella militari:
| - anzianita: Data di anzianità di servizio
| - statuti: Array JSON di statuti speciali (104, Orario flessibile, etc.)
| 
| @package SUGECO
| @author Michele Di Gennaro
| @version 1.0
*/

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
        Schema::table('militari', function (Blueprint $table) {
            if (!Schema::hasColumn('militari', 'anzianita')) {
                $table->date('anzianita')->nullable()->after('data_nascita')->comment('Data anzianità di servizio');
            }
            
            if (!Schema::hasColumn('militari', 'statuti')) {
                $table->json('statuti')->nullable()->after('note')->comment('Statuti speciali (104, Orario flessibile, Esente alzabandiera)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            if (Schema::hasColumn('militari', 'statuti')) {
                $table->dropColumn('statuti');
            }
            
            if (Schema::hasColumn('militari', 'anzianita')) {
                $table->dropColumn('anzianita');
            }
        });
    }
};

