<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aggiunge organizational_unit_id a configurazione_campi_anagrafica
     * per segregare le impostazioni colonne anagrafica per unità organizzativa.
     */
    public function up(): void
    {
        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            if (!Schema::hasColumn('configurazione_campi_anagrafica', 'organizational_unit_id')) {
                $table->unsignedBigInteger('organizational_unit_id')
                    ->nullable()
                    ->after('id');

                $table->foreign('organizational_unit_id')
                    ->references('id')
                    ->on('organizational_units')
                    ->nullOnDelete();

                $table->index('organizational_unit_id');
            }
        });

        // Assegna i record esistenti alla prima unità di tipo "Battaglione" (depth=1)
        $defaultUnitId = DB::table('organizational_units')
            ->where('depth', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($defaultUnitId) {
            DB::table('configurazione_campi_anagrafica')
                ->whereNull('organizational_unit_id')
                ->update(['organizational_unit_id' => $defaultUnitId]);
        }

        // Rimuovi unique su nome_campo e aggiungi unique (nome_campo, organizational_unit_id)
        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            $table->dropUnique('configurazione_campi_anagrafica_nome_campo_unique');
            $table->unique(['nome_campo', 'organizational_unit_id'], 'config_campi_anagrafica_nome_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            $table->dropUnique('config_campi_anagrafica_nome_unit_unique');
            $table->unique('nome_campo');
        });

        Schema::table('configurazione_campi_anagrafica', function (Blueprint $table) {
            $table->dropForeign(['organizational_unit_id']);
            $table->dropIndex(['organizational_unit_id']);
            $table->dropColumn('organizational_unit_id');
        });
    }
};
