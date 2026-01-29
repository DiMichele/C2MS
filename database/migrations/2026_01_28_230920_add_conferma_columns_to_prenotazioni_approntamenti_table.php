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
        Schema::table('prenotazioni_approntamenti', function (Blueprint $table) {
            $table->timestamp('data_conferma')->nullable()->after('stato');
            $table->foreignId('confirmed_by')->nullable()->after('data_conferma')
                  ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenotazioni_approntamenti', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn(['data_conferma', 'confirmed_by']);
        });
    }
};
