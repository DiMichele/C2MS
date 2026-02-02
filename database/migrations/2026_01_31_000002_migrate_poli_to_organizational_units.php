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
     * Migra tutti gli uffici/poli dalla tabella 'poli' a 'organizational_units'.
     * 
     * Gli uffici sono associati al Battaglione Leonessa come default.
     * 
     * Per ogni polo:
     * 1. Crea OrganizationalUnit con:
     *    - type_id = 5 (Ufficio)
     *    - parent_id = ID del Battaglione Leonessa
     *    - legacy_polo_id = polo.id
     * 2. Aggiorna i militari: SET ufficio_unit_id = nuovo_unit_id WHERE polo_id = polo.id
     */
    public function up(): void
    {
        $ufficioTypeId = 5; // Tipo "Ufficio"
        $leonessaId = 7; // Battaglione Leonessa
        
        // Verifica che il Battaglione Leonessa esista
        $leonessa = DB::table('organizational_units')->where('id', $leonessaId)->first();
        if (!$leonessa) {
            Log::error("MigratePoliToOrganizationalUnits: Battaglione Leonessa (ID: {$leonessaId}) non trovato");
            return;
        }

        // Ottieni tutti i poli/uffici
        $poli = DB::table('poli')->get();
        
        if ($poli->isEmpty()) {
            Log::info('MigratePoliToOrganizationalUnits: Nessun polo/ufficio da migrare');
            return;
        }

        Log::info("MigratePoliToOrganizationalUnits: Migrazione di {$poli->count()} poli/uffici");

        foreach ($poli as $polo) {
            // Verifica se esiste già un'OrganizationalUnit con questo legacy_polo_id
            $existingUnit = DB::table('organizational_units')
                ->where('legacy_polo_id', $polo->id)
                ->first();
            
            if ($existingUnit) {
                Log::info("Polo '{$polo->nome}' (ID: {$polo->id}) già migrato come OrganizationalUnit ID: {$existingUnit->id}");
                
                // Aggiorna comunque i militari
                $this->updateMilitari($polo->id, $existingUnit->id);
                continue;
            }

            // Calcola path e depth
            $newPath = $leonessa->path
                ? $leonessa->path . '.' . $leonessa->id
                : (string) $leonessa->id;
            $newDepth = $leonessa->depth + 1;

            // Crea l'OrganizationalUnit per l'ufficio
            $newUnitId = DB::table('organizational_units')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'type_id' => $ufficioTypeId,
                'parent_id' => $leonessaId,
                'name' => $polo->nome,
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $polo->nome), 0, 10)),
                'description' => $polo->descrizione,
                'path' => $newPath,
                'depth' => $newDepth,
                'sort_order' => 0,
                'legacy_polo_id' => $polo->id,
                'is_active' => true,
                'created_at' => $polo->created_at ?? now(),
                'updated_at' => now(),
            ]);

            // Aggiungi alla closure table
            $this->addToClosureTable($newUnitId, $leonessaId);
            
            // Aggiorna i militari con il nuovo ufficio_unit_id
            $this->updateMilitari($polo->id, $newUnitId);

            Log::info("Polo '{$polo->nome}' (ID: {$polo->id}) migrato come OrganizationalUnit ID: {$newUnitId}");
        }

        Log::info('MigratePoliToOrganizationalUnits: Migrazione completata');
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
     * Aggiorna i militari con il nuovo ufficio_unit_id
     */
    protected function updateMilitari(int $poloId, int $newUnitId): void
    {
        // Verifica se la colonna ufficio_unit_id esiste
        if (!Schema::hasColumn('militari', 'ufficio_unit_id')) {
            Log::warning("MigratePoliToOrganizationalUnits: Colonna ufficio_unit_id non presente in militari");
            return;
        }
        
        $updated = DB::table('militari')
            ->where('polo_id', $poloId)
            ->update(['ufficio_unit_id' => $newUnitId]);
        
        if ($updated > 0) {
            Log::info("Aggiornati {$updated} militari con ufficio_unit_id = {$newUnitId} (polo_id = {$poloId})");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $ufficioTypeId = 5;
        
        // Ripristina ufficio_unit_id a null per tutti i militari
        if (Schema::hasColumn('militari', 'ufficio_unit_id')) {
            DB::table('militari')
                ->whereNotNull('ufficio_unit_id')
                ->update(['ufficio_unit_id' => null]);
        }
        
        // Ottieni tutti i poli con legacy_polo_id
        $units = DB::table('organizational_units')
            ->whereNotNull('legacy_polo_id')
            ->where('type_id', $ufficioTypeId)
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

        Log::info('MigratePoliToOrganizationalUnits: Rollback completato');
    }
};
