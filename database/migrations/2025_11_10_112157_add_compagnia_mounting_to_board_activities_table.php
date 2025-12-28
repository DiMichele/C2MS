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
        Schema::table('board_activities', function (Blueprint $table) {
            // Compagnia che "monta" (organizza) l'attività
            $table->unsignedBigInteger('compagnia_mounting_id')->nullable()->after('compagnia_id');
            $table->foreign('compagnia_mounting_id')->references('id')->on('compagnie')->onDelete('set null');
            
            // Sigla CPT suggerita per la sincronizzazione automatica
            $table->string('sigla_cpt_suggerita', 20)->nullable()->after('compagnia_mounting_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_activities', function (Blueprint $table) {
            $table->dropForeign(['compagnia_mounting_id']);
            $table->dropColumn(['compagnia_mounting_id', 'sigla_cpt_suggerita']);
        });
    }
};
