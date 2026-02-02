<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Migra tutti i plotoni dalla tabella 'plotoni' a 'organizational_units'.
     * 
     * Per ogni plotone:
     * 1. Trova la compagnia parent
     * 2. Trova l'OrganizationalUnit della compagnia (via legacy_compagnia_id)
     * 3. Crea OrganizationalUnit con:
     *    - type_id = 4 (Plotone)
     *    - parent_id = OrganizationalUnit della compagnia
     *    - legacy_plotone_id = plotone.id
     */
    public function up(): void
    {
        $plotoneTypeId = 4; // Tipo "Plotone"
        
        // Ottieni tutti i plotoni
        $plotoni = DB::table('plotoni')->get();
        
        if ($plotoni->isEmpty()) {
            Log::info('MigratePlotoniToOrganizationalUnits: Nessun plotone da migrare');
            return;
        }

        Log::info("MigratePlotoniToOrganizationalUnits: Migrazione di {$plotoni->count()} plotoni");

        foreach ($plotoni as $plotone) {
            // Verifica se esiste già un'OrganizationalUnit con questo legacy_plotone_id
            $existingUnit = DB::table('organizational_units')
                ->where('legacy_plotone_id', $plotone->id)
                ->first();
            
            if ($existingUnit) {
                Log::info("Plotone '{$plotone->nome}' (ID: {$plotone->id}) già migrato come OrganizationalUnit ID: {$existingUnit->id}");
                continue;
            }

            // Trova l'OrganizationalUnit parent (compagnia)
            $parentUnit = DB::table('organizational_units')
                ->where('legacy_compagnia_id', $plotone->compagnia_id)
                ->first();
            
            if (!$parentUnit) {
                Log::warning("Plotone '{$plotone->nome}' (ID: {$plotone->id}): compagnia parent (ID: {$plotone->compagnia_id}) non trovata come OrganizationalUnit");
                continue;
            }

            // Calcola path e depth
            $newPath = $parentUnit->path
                ? $parentUnit->path . '.' . $parentUnit->id
                : (string) $parentUnit->id;
            $newDepth = $parentUnit->depth + 1;

            // Crea l'OrganizationalUnit per il plotone
            $newUnitId = DB::table('organizational_units')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'type_id' => $plotoneTypeId,
                'parent_id' => $parentUnit->id,
                'name' => $plotone->nome,
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $plotone->nome), 0, 10)),
                'description' => $plotone->descrizione,
                'path' => $newPath,
                'depth' => $newDepth,
                'sort_order' => 0,
                'legacy_plotone_id' => $plotone->id,
                'is_active' => true,
                'created_at' => $plotone->created_at ?? now(),
                'updated_at' => now(),
            ]);

            // Aggiungi alla closure table
            $this->addToClosureTable($newUnitId, $parentUnit->id);

            Log::info("Plotone '{$plotone->nome}' (ID: {$plotone->id}) migrato come OrganizationalUnit ID: {$newUnitId}");
        }

        Log::info('MigratePlotoniToOrganizationalUnits: Migrazione completata');
    }

    /**
     * Aggiunge l'unità alla closure table.
     */
    protected function addToClosureTable(int $unitId, int $parentId): void
    {
        // Auto-relazione (depth 0)
        DB::table('unit_closure')->insert([
            'ancestor_id' => $unitId,
            'descendant_id' => $unitId,
            'depth' => 0,
        ]);

        // Relazioni con tutti gli antenati del parent
        $ancestorRelations = DB::table('unit_closure')
            ->where('descendant_id', $parentId)
            ->get();

        foreach ($ancestorRelations as $relation) {
            DB::table('unit_closure')->insert([
                'ancestor_id' => $relation->ancestor_id,
                'descendant_id' => $unitId,
                'depth' => $relation->depth + 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $plotoneTypeId = 4;
        
        // Ottieni tutti i plotoni con legacy_plotone_id
        $units = DB::table('organizational_units')
            ->whereNotNull('legacy_plotone_id')
            ->where('type_id', $plotoneTypeId)
            ->get();

        foreach ($units as $unit) {
            // Rimuovi dalla closure table
            DB::table('unit_closure')
                ->where('descendant_id', $unit->id)
                ->orWhere('ancestor_id', $unit->id)
                ->delete();
            
            // Rimuovi l'unità
            DB::table('organizational_units')
                ->where('id', $unit->id)
                ->delete();
        }

        Log::info('MigratePlotoniToOrganizationalUnits: Rollback completato');
    }
};
