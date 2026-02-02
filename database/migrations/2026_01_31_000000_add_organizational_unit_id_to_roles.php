<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aggiunge organizational_unit_id alla tabella roles
     * per associare i ruoli a specifiche unità organizzative.
     * 
     * Questo permette di avere ruoli isolati per ogni battaglione/compagnia.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Aggiungi colonna organizational_unit_id se non esiste
            if (!Schema::hasColumn('roles', 'organizational_unit_id')) {
                $table->unsignedBigInteger('organizational_unit_id')
                      ->nullable()
                      ->after('compagnia_id');
                
                $table->foreign('organizational_unit_id')
                      ->references('id')
                      ->on('organizational_units')
                      ->nullOnDelete();
                
                $table->index('organizational_unit_id');
            }
        });

        // Migra i ruoli esistenti da compagnia_id a organizational_unit_id
        $this->migrateExistingRoles();
    }

    /**
     * Migra i ruoli esistenti associandoli alle OrganizationalUnit corrispondenti
     */
    protected function migrateExistingRoles(): void
    {
        // Trova tutti i ruoli con compagnia_id ma senza organizational_unit_id
        $roles = \DB::table('roles')
            ->whereNotNull('compagnia_id')
            ->whereNull('organizational_unit_id')
            ->get();

        foreach ($roles as $role) {
            // Trova l'OrganizationalUnit corrispondente alla compagnia
            $unit = \DB::table('organizational_units')
                ->where('legacy_compagnia_id', $role->compagnia_id)
                ->first();

            if ($unit) {
                \DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['organizational_unit_id' => $unit->id]);
                
                \Log::info("Ruolo '{$role->name}' (ID: {$role->id}) associato a unità '{$unit->name}' (ID: {$unit->id})");
            }
        }

        // Per i ruoli globali (is_global = true), organizational_unit_id resta null
        \Log::info('Migrazione ruoli a organizational_unit_id completata');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'organizational_unit_id')) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex(['organizational_unit_id']);
                $table->dropColumn('organizational_unit_id');
            }
        });
    }
};
