<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge il permesso per vedere tutti i militari nella Board
     * (necessario per creare attività cross-compagnia)
     */
    public function up(): void
    {
        // Verifica se il permesso esiste già
        $exists = DB::table('permissions')
            ->where('name', 'board.view_all_militari')
            ->exists();

        if (!$exists) {
            DB::table('permissions')->insert([
                'name' => 'board.view_all_militari',
                'display_name' => 'Visualizza tutti i militari nella Board',
                'description' => 'Permette di vedere e selezionare militari di tutte le compagnie quando si creano o modificano attività. Utile per i Comandanti di Compagnia che devono creare attività congiunte.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'board.view_all_militari')
            ->delete();
    }
};

