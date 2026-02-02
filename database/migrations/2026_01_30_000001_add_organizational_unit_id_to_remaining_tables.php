<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione per aggiungere organizational_unit_id alle tabelle rimanenti
 * per il sistema multi-tenancy.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi organizational_unit_id a codici_servizio_gerarchia (CPT)
        // Per permettere codici specifici per unità
        if (!Schema::hasColumn('codici_servizio_gerarchia', 'organizational_unit_id')) {
            Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
                $table->unsignedBigInteger('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->comment('Unità organizzativa proprietaria (null = globale)');

                $table->foreign('organizational_unit_id')
                    ->references('id')
                    ->on('organizational_units')
                    ->nullOnDelete();

                $table->index('organizational_unit_id', 'codici_gerarchia_org_unit_idx');
            });
        }

        // Aggiungi indici mancanti per ottimizzare query multi-tenancy
        // BoardActivity
        if (Schema::hasColumn('board_activities', 'organizational_unit_id')) {
            try {
                Schema::table('board_activities', function (Blueprint $table) {
                    $table->index('organizational_unit_id', 'board_activities_org_unit_idx');
                });
            } catch (\Exception $e) {
                // Indice già esistente
            }
        }

        // PianificazioniGiornaliere
        if (Schema::hasColumn('pianificazioni_giornaliere', 'organizational_unit_id')) {
            try {
                Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
                    $table->index('organizational_unit_id', 'pian_giorn_org_unit_idx');
                });
            } catch (\Exception $e) {
                // Indice già esistente
            }
        }

        // ServiziTurno
        if (Schema::hasColumn('servizi_turno', 'organizational_unit_id')) {
            try {
                Schema::table('servizi_turno', function (Blueprint $table) {
                    $table->index('organizational_unit_id', 'servizi_turno_org_unit_idx');
                });
            } catch (\Exception $e) {
                // Indice già esistente
            }
        }

        // AssegnazioniTurno
        if (Schema::hasColumn('assegnazioni_turno', 'organizational_unit_id')) {
            try {
                Schema::table('assegnazioni_turno', function (Blueprint $table) {
                    $table->index('organizational_unit_id', 'assegnazioni_turno_org_unit_idx');
                });
            } catch (\Exception $e) {
                // Indice già esistente
            }
        }

        // ConfigurazioneRuolini
        if (Schema::hasColumn('configurazione_ruolini', 'organizational_unit_id')) {
            try {
                Schema::table('configurazione_ruolini', function (Blueprint $table) {
                    $table->index('organizational_unit_id', 'config_ruolini_org_unit_idx');
                });
            } catch (\Exception $e) {
                // Indice già esistente
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi da codici_servizio_gerarchia
        if (Schema::hasColumn('codici_servizio_gerarchia', 'organizational_unit_id')) {
            Schema::table('codici_servizio_gerarchia', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('codici_gerarchia_org_unit_idx');
                $table->dropColumn('organizational_unit_id');
            });
        }

        // Rimuovi indici
        try {
            Schema::table('board_activities', function (Blueprint $table) {
                $table->dropIndex('board_activities_org_unit_idx');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
                $table->dropIndex('pian_giorn_org_unit_idx');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('servizi_turno', function (Blueprint $table) {
                $table->dropIndex('servizi_turno_org_unit_idx');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assegnazioni_turno', function (Blueprint $table) {
                $table->dropIndex('assegnazioni_turno_org_unit_idx');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('configurazione_ruolini', function (Blueprint $table) {
                $table->dropIndex('config_ruolini_org_unit_idx');
            });
        } catch (\Exception $e) {}
    }
};
