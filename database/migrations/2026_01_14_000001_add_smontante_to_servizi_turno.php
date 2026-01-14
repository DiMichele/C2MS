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
        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->boolean('smontante_cpt')->default(false)->after('sigla_cpt');
            $table->index('smontante_cpt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->dropIndex(['smontante_cpt']);
            $table->dropColumn('smontante_cpt');
        });
    }
};
