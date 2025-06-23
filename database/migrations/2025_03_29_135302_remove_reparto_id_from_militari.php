<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificare se la colonna reparto_id esiste
        if (Schema::hasColumn('militari', 'reparto_id')) {
            // Disabilitare temporaneamente i controlli foreign key
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Eseguire l'aggiornamento
            Schema::table('militari', function (Blueprint $table) {
                $table->dropColumn('reparto_id');
            });
            
            // Riabilitare i controlli foreign key
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('militari', 'reparto_id')) {
            Schema::table('militari', function (Blueprint $table) {
                $table->unsignedBigInteger('reparto_id')->nullable();
            });
        }
    }
};
