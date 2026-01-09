<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $deleted = DB::table('configurazione_ruolini')
            ->where('stato_presenza', 'default')
            ->delete();

        if ($deleted > 0) {
            \Illuminate\Support\Facades\Log::info("Migrazione: eliminate {$deleted} righe con stato_presenza='default'");
        }

        DB::statement("ALTER TABLE configurazione_ruolini MODIFY COLUMN stato_presenza ENUM('presente', 'assente') NOT NULL DEFAULT 'assente' COMMENT 'presente=conta come presente, assente=conta come assente'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE configurazione_ruolini MODIFY COLUMN stato_presenza ENUM('presente', 'assente', 'default') NOT NULL DEFAULT 'default'");
    }
};
