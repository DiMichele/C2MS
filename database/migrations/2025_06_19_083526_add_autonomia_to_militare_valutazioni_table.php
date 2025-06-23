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
        Schema::table('militare_valutazioni', function (Blueprint $table) {
            $table->tinyInteger('autonomia')->unsigned()->default(0)->comment('Punteggio 1-5 per autonomia')->after('iniziativa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militare_valutazioni', function (Blueprint $table) {
            $table->dropColumn('autonomia');
        });
    }
};
