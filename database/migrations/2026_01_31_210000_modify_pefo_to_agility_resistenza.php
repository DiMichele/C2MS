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
        Schema::table('militari', function (Blueprint $table) {
            // Rimuovi il campo data_ultimo_pefo
            $table->dropColumn('data_ultimo_pefo');
            
            // Aggiungi i due nuovi campi
            $table->date('data_agility')->nullable()->after('nos_status');
            $table->date('data_resistenza')->nullable()->after('data_agility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('militari', function (Blueprint $table) {
            // Ripristina il campo originale
            $table->date('data_ultimo_pefo')->nullable()->after('nos_status');
            
            // Rimuovi i nuovi campi
            $table->dropColumn(['data_agility', 'data_resistenza']);
        });
    }
};
