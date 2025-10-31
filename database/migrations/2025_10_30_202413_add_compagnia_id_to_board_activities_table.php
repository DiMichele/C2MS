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
            $table->foreignId('compagnia_id')->nullable()->after('created_by')->constrained('compagnie')->onDelete('cascade');
            $table->index('compagnia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_activities', function (Blueprint $table) {
            $table->dropForeign(['compagnia_id']);
            $table->dropColumn('compagnia_id');
        });
    }
};
