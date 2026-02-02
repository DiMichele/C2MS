<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aggiunge il campo page_context alla tabella audit_logs
 * per tracciare da quale pagina è stata effettuata l'azione
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('page_context', 100)->nullable()->after('status')
                  ->comment('Pagina/contesto da cui è stata effettuata l\'azione');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('page_context');
        });
    }
};
