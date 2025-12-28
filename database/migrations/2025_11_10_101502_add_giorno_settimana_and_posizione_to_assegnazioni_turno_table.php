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
        Schema::table('assegnazioni_turno', function (Blueprint $table) {
            $table->string('giorno_settimana', 20)->after('data_servizio')->nullable();
            $table->integer('posizione')->default(1)->after('giorno_settimana');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assegnazioni_turno', function (Blueprint $table) {
            $table->dropColumn(['giorno_settimana', 'posizione']);
        });
    }
};
