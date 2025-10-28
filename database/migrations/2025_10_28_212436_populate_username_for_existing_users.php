<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Popola il campo username per gli utenti esistenti
     * generandolo dalla parte locale dell'email (prima della @)
     */
    public function up(): void
    {
        // Genera username dalla email (parte prima della @)
        DB::statement("
            UPDATE users 
            SET username = LOWER(SUBSTRING_INDEX(email, '@', 1))
            WHERE username IS NULL OR username = ''
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non è possibile ripristinare i valori originali
    }
};
