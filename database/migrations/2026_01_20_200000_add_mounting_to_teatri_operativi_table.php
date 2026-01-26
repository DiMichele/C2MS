<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge il campo mounting_compagnia_id per indicare quale compagnia
     * "ospita" il teatro operativo. I militari assegnati al teatro appariranno
     * nel CPT e nell'anagrafica della compagnia di mounting.
     */
    public function up(): void
    {
        Schema::table('teatri_operativi', function (Blueprint $table) {
            $table->unsignedBigInteger('mounting_compagnia_id')->nullable()->after('compagnia_id');
            $table->foreign('mounting_compagnia_id')->references('id')->on('compagnie')->onDelete('set null');
            $table->index('mounting_compagnia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teatri_operativi', function (Blueprint $table) {
            $table->dropForeign(['mounting_compagnia_id']);
            $table->dropColumn('mounting_compagnia_id');
        });
    }
};
