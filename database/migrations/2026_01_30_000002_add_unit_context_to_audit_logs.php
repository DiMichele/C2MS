<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione per aggiungere il contesto unità all'audit log.
 * 
 * Questo permette di tracciare:
 * - active_unit_id: l'unità che l'utente aveva selezionato quando ha eseguito l'azione
 * - affected_unit_id: l'unità a cui appartiene il record modificato
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Unità attiva dell'utente al momento dell'azione
            $table->unsignedBigInteger('active_unit_id')
                ->nullable()
                ->after('compagnia_id')
                ->comment('Unità organizzativa attiva dell\'utente');

            // Unità del record affected (se applicabile)
            $table->unsignedBigInteger('affected_unit_id')
                ->nullable()
                ->after('active_unit_id')
                ->comment('Unità organizzativa del record modificato');

            // Nome unità (per query più veloci senza JOIN)
            $table->string('active_unit_name', 100)
                ->nullable()
                ->after('affected_unit_id');

            $table->string('affected_unit_name', 100)
                ->nullable()
                ->after('active_unit_name');

            // Indici per performance
            $table->index('active_unit_id', 'audit_logs_active_unit_idx');
            $table->index('affected_unit_id', 'audit_logs_affected_unit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_active_unit_idx');
            $table->dropIndex('audit_logs_affected_unit_idx');
            $table->dropColumn([
                'active_unit_id',
                'affected_unit_id',
                'active_unit_name',
                'affected_unit_name'
            ]);
        });
    }
};
