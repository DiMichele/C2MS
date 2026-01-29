<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration per ottimizzare gli indici della tabella audit_logs.
 * 
 * Aggiunge indici compositi per le query più frequenti:
 * - Ricerca per data + azione (statistiche dashboard)
 * - Ricerca per compagnia + data (filtri per compagnia)
 * - Ricerca per stato (filtro errori/warning)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Indice composito per statistiche giornaliere (molto usato nella dashboard)
            // Supporta query: COUNT(*) WHERE created_at >= ? AND action = ?
            $table->index(['created_at', 'action'], 'idx_audit_logs_date_action');
            
            // Indice per filtro stato (errori/warning sono meno frequenti, ma critici)
            $table->index('status', 'idx_audit_logs_status');
            
            // Indice composito per filtri con compagnia
            $table->index(['compagnia_id', 'created_at'], 'idx_audit_logs_compagnia_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('idx_audit_logs_date_action');
            $table->dropIndex('idx_audit_logs_status');
            $table->dropIndex('idx_audit_logs_compagnia_date');
        });
    }
};
