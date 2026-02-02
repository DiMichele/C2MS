<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Questa migration:
     * 1. Aggiunge la colonna ufficio_unit_id alla tabella militari
     * 2. Sposta gli uffici esistenti sotto il Battaglione Leonessa
     */
    public function up(): void
    {
        // 1. Aggiungere colonna ufficio_unit_id
        Schema::table('militari', function (Blueprint $table) {
            $table->foreignId('ufficio_unit_id')
                  ->nullable()
                  ->after('organizational_unit_id')
                  ->constrained('organizational_units')
                  ->nullOnDelete();
        });

        // 2. Spostare gli uffici (type_id = 5) sotto Battaglione Leonessa (ID: 7)
        // Gli uffici con ID 23-32 sono attualmente figli del Reggimento (parent_id = 1)
        $ufficiIds = DB::table('organizational_units')
            ->where('parent_id', 1)
            ->where('type_id', 5)
            ->pluck('id')
            ->toArray();

        if (!empty($ufficiIds)) {
            DB::table('organizational_units')
                ->whereIn('id', $ufficiIds)
                ->update([
                    'parent_id' => 7, // Battaglione Leonessa
                    'depth' => 2,
                    'updated_at' => now(),
                ]);

            // Aggiorna anche la tabella unit_closure per mantenere la gerarchia corretta
            // Rimuovi vecchie relazioni
            DB::table('unit_closure')
                ->whereIn('descendant_id', $ufficiIds)
                ->where('ancestor_id', 1)
                ->where('depth', 1)
                ->delete();

            // Aggiungi nuove relazioni con Battaglione Leonessa
            foreach ($ufficiIds as $uffId) {
                // Relazione con Battaglione Leonessa (depth 1)
                DB::table('unit_closure')->insert([
                    'ancestor_id' => 7,
                    'descendant_id' => $uffId,
                    'depth' => 1,
                ]);
                
                // Relazione con Reggimento (depth 2)
                DB::table('unit_closure')->updateOrInsert(
                    ['ancestor_id' => 1, 'descendant_id' => $uffId],
                    ['depth' => 2]
                );
            }

            \Log::info('Migrati ' . count($ufficiIds) . ' uffici sotto Battaglione Leonessa');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi la colonna
        Schema::table('militari', function (Blueprint $table) {
            $table->dropForeign(['ufficio_unit_id']);
            $table->dropColumn('ufficio_unit_id');
        });

        // Risposta gli uffici sotto il Reggimento
        $ufficiIds = DB::table('organizational_units')
            ->where('parent_id', 7)
            ->where('type_id', 5)
            ->pluck('id')
            ->toArray();

        if (!empty($ufficiIds)) {
            DB::table('organizational_units')
                ->whereIn('id', $ufficiIds)
                ->update([
                    'parent_id' => 1,
                    'depth' => 1,
                    'updated_at' => now(),
                ]);
        }
    }
};
