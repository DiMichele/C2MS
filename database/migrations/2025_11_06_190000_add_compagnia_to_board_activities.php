<?php

/**
|--------------------------------------------------------------------------
| SUGECO - Migration: Aggiungi compagnia_id a board_activities
|--------------------------------------------------------------------------
| 
| Aggiunge la colonna compagnia_id alla tabella board_activities per
| permettere il filtraggio delle attività per compagnia.
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
        Schema::table('board_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('board_activities', 'compagnia_id')) {
                $table->unsignedBigInteger('compagnia_id')->nullable()->after('column_id');
                $table->foreign('compagnia_id')->references('id')->on('compagnie')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_activities', function (Blueprint $table) {
            if (Schema::hasColumn('board_activities', 'compagnia_id')) {
                $table->dropForeign(['compagnia_id']);
                $table->dropColumn('compagnia_id');
            }
        });
    }
};

