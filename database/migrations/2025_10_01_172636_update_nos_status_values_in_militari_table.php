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
        // Aggiorna i valori esistenti
        DB::table('militari')->where('nos_status', 'SI')->update(['nos_status' => 'si']);
        DB::table('militari')->where('nos_status', 'NO')->update(['nos_status' => 'no']);
        
        // Aggiorna la colonna per accettare i nuovi valori
        Schema::table('militari', function (Blueprint $table) {
            $table->enum('nos_status', ['si', 'no', 'da richiedere', 'non previsto', 'in attesa'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ripristina i valori originali
        DB::table('militari')->where('nos_status', 'si')->update(['nos_status' => 'SI']);
        DB::table('militari')->where('nos_status', 'no')->update(['nos_status' => 'NO']);
        
        // Ripristina la colonna originale
        Schema::table('militari', function (Blueprint $table) {
            $table->enum('nos_status', ['SI', 'NO'])->nullable()->change();
        });
    }
};