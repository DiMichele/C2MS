<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix per rendere nome_prenotazione nullable
 */
return new class extends Migration
{
    public function up(): void
    {
        // Usa SQL diretto per evitare problemi con change() in MySQL
        DB::statement('ALTER TABLE prenotazioni_pefo MODIFY COLUMN nome_prenotazione VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE prenotazioni_pefo MODIFY COLUMN nome_prenotazione VARCHAR(255) NOT NULL');
    }
};
