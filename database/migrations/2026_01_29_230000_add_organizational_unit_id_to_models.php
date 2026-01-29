<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrazione per aggiungere organizational_unit_id ai modelli principali.
 * 
 * Questa migrazione collega i modelli esistenti al nuovo sistema gerarchico
 * organizzativo, mantenendo i campi legacy (compagnia_id) per retrocompatibilità.
 * 
 * Modelli coinvolti:
 * - militari
 * - pianificazioni_giornaliere
 * - pianificazioni_mensili
 * - assegnazioni_turno
 * - turni_settimanali
 * - servizi_turno
 * - board_activities
 * - configurazione_ruolini
 * 
 * NOTA: Non modifichiamo i vincoli unique esistenti per evitare complicazioni.
 * Il filtro per unità viene gestito a livello applicativo via scope.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Militari - colonna principale per l'assegnazione organizzativa
        Schema::table('militari', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('compagnia_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'militari_org_unit_idx');
        });

        // 2. Pianificazioni Mensili - isolamento per unità
        Schema::table('pianificazioni_mensili', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('anno')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'pian_mensili_org_unit_idx');
        });

        // 3. Pianificazioni Giornaliere
        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('pianificazione_mensile_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'pian_giorn_org_unit_idx');
        });

        // 4. Turni Settimanali - isolamento per unità
        Schema::table('turni_settimanali', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('numero_settimana')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'turni_sett_org_unit_idx');
        });

        // 5. Servizi Turno - isolamento per unità (ogni unità può avere i suoi servizi)
        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('nome')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'servizi_turno_org_unit_idx');
        });

        // 6. Assegnazioni Turno
        Schema::table('assegnazioni_turno', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('turno_settimanale_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'assegn_turno_org_unit_idx');
        });

        // 7. Board Activities - isolamento per unità
        Schema::table('board_activities', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('column_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'board_act_org_unit_idx');
        });

        // 8. Configurazione Ruolini - isolamento per unità
        Schema::table('configurazione_ruolini', function (Blueprint $table) {
            $table->foreignId('organizational_unit_id')
                ->nullable()
                ->after('compagnia_id')
                ->constrained('organizational_units')
                ->nullOnDelete();
            
            $table->index('organizational_unit_id', 'config_ruol_org_unit_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi in ordine inverso
        
        Schema::table('configurazione_ruolini', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('config_ruol_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('board_activities', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('board_act_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('assegnazioni_turno', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('assegn_turno_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('servizi_turno', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('servizi_turno_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('turni_settimanali', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('turni_sett_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('pianificazioni_giornaliere', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('pian_giorn_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('pianificazioni_mensili', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('pian_mensili_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });

        Schema::table('militari', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex('militari_org_unit_idx');
            $table->dropColumn('organizational_unit_id');
        });
    }
};
