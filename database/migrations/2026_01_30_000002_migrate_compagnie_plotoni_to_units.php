<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use Ramsey\Uuid\Uuid;

/**
 * Migration per migrare automaticamente le compagnie e i plotoni esistenti
 * in unità organizzative durante il deploy.
 * 
 * Questa migration:
 * 1. Crea i tipi di unità di base se non esistono
 * 2. Crea il battaglione Leonessa come root
 * 3. Migra le compagnie esistenti come figli del battaglione
 * 4. Migra i plotoni esistenti come figli delle compagnie
 * 5. Aggiorna i militari con il nuovo organizational_unit_id
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Esegui solo se la tabella organizational_units esiste
        if (!DB::getSchemaBuilder()->hasTable('organizational_units')) {
            Log::warning('Tabella organizational_units non esistente. Salto migration.');
            return;
        }

        Log::info('Avvio migrazione compagnie e plotoni a unità organizzative...');

        DB::transaction(function () {
            // 1. Assicurati che esistano i tipi di unità base
            $this->ensureUnitTypes();

            // 2. Crea/trova il battaglione Leonessa
            $leonessa = $this->createBattaglioneLeonessa();

            // 3. Migra le compagnie esistenti
            $this->migrateCompagnie($leonessa);

            // 4. Migra i plotoni esistenti
            $this->migratePlotoni();
        });

        Log::info('Migrazione compagnie e plotoni completata.');
    }

    /**
     * Assicura che esistano i tipi di unità di base.
     */
    protected function ensureUnitTypes(): void
    {
        $types = [
            [
                'code' => 'reggimento',
                'name' => 'Reggimento',
                'description' => 'Unità militare principale',
                'icon' => 'fa-flag',
                'color' => '#0A2342',
                'default_depth_level' => 0,
                'sort_order' => 1,
            ],
            [
                'code' => 'battaglione',
                'name' => 'Battaglione',
                'description' => 'Unità operativa di un reggimento',
                'icon' => 'fa-shield-alt',
                'color' => '#1A3A5F',
                'default_depth_level' => 1,
                'sort_order' => 2,
            ],
            [
                'code' => 'compagnia',
                'name' => 'Compagnia',
                'description' => 'Unità tattica di un battaglione',
                'icon' => 'fa-users',
                'color' => '#2A4A7F',
                'default_depth_level' => 2,
                'sort_order' => 3,
            ],
            [
                'code' => 'plotone',
                'name' => 'Plotone',
                'description' => 'Unità base di una compagnia',
                'icon' => 'fa-user-friends',
                'color' => '#3A5A9F',
                'default_depth_level' => 3,
                'sort_order' => 4,
            ],
        ];

        foreach ($types as $type) {
            OrganizationalUnitType::firstOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        Log::info('Tipi unità base verificati/creati.');
    }

    /**
     * Crea o trova il Battaglione Leonessa.
     */
    protected function createBattaglioneLeonessa(): OrganizationalUnit
    {
        $battaglioneType = OrganizationalUnitType::where('code', 'battaglione')->first();

        if (!$battaglioneType) {
            throw new \Exception('Tipo battaglione non trovato.');
        }

        // Cerca se esiste già
        $leonessa = OrganizationalUnit::where('code', 'LEON')
            ->orWhere('name', 'Battaglione Leonessa')
            ->first();

        if ($leonessa) {
            Log::info('Battaglione Leonessa già esistente: ' . $leonessa->id);
            return $leonessa;
        }

        // Crea nuovo
        $leonessa = OrganizationalUnit::create([
            'id' => Uuid::uuid4()->toString(),
            'name' => 'Battaglione Leonessa',
            'code' => 'LEON',
            'type_id' => $battaglioneType->id,
            'parent_id' => null,
            'depth' => 0,
            'path' => '',
            'description' => '11° Reggimento Trasmissioni - Battaglione Leonessa',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // Aggiorna path
        $leonessa->path = $leonessa->id;
        $leonessa->save();

        Log::info('Creato Battaglione Leonessa: ' . $leonessa->id);

        return $leonessa;
    }

    /**
     * Migra le compagnie esistenti come figli del battaglione.
     */
    protected function migrateCompagnie(OrganizationalUnit $leonessa): void
    {
        $compagniaType = OrganizationalUnitType::where('code', 'compagnia')->first();

        if (!$compagniaType) {
            throw new \Exception('Tipo compagnia non trovato.');
        }

        // Carica le compagnie dalla tabella legacy
        $compagnie = DB::table('compagnie')->get();

        foreach ($compagnie as $compagnia) {
            // Verifica se già migrata
            $existing = OrganizationalUnit::where('legacy_compagnia_id', $compagnia->id)->first();
            if ($existing) {
                Log::info("Compagnia {$compagnia->nome} già migrata: {$existing->id}");
                continue;
            }

            // Crea unità organizzativa
            $unit = OrganizationalUnit::create([
                'id' => Uuid::uuid4()->toString(),
                'name' => $compagnia->nome,
                'code' => $compagnia->sigla ?? $compagnia->numero ?? null,
                'type_id' => $compagniaType->id,
                'parent_id' => $leonessa->id,
                'depth' => 1,
                'path' => $leonessa->id,
                'description' => $compagnia->descrizione ?? null,
                'is_active' => true,
                'sort_order' => $compagnia->id,
                'legacy_compagnia_id' => $compagnia->id,
            ]);

            // Aggiorna path
            $unit->path = $leonessa->id . '/' . $unit->id;
            $unit->save();

            // Aggiorna militari di questa compagnia
            $updated = DB::table('militari')
                ->where('compagnia_id', $compagnia->id)
                ->whereNull('organizational_unit_id')
                ->update([
                    'organizational_unit_id' => $unit->id,
                    'updated_at' => now(),
                ]);

            // Aggiorna utenti di questa compagnia
            DB::table('users')
                ->where('compagnia_id', $compagnia->id)
                ->whereNull('organizational_unit_id')
                ->update([
                    'organizational_unit_id' => $unit->id,
                    'updated_at' => now(),
                ]);

            Log::info("Migrata compagnia {$compagnia->nome}: {$unit->id} ({$updated} militari aggiornati)");
        }
    }

    /**
     * Migra i plotoni esistenti come figli delle compagnie.
     */
    protected function migratePlotoni(): void
    {
        $plotoneType = OrganizationalUnitType::where('code', 'plotone')->first();

        if (!$plotoneType) {
            throw new \Exception('Tipo plotone non trovato.');
        }

        // Carica i plotoni dalla tabella legacy
        $plotoni = DB::table('plotoni')->get();

        foreach ($plotoni as $plotone) {
            // Verifica se già migrato
            $existing = OrganizationalUnit::where('legacy_plotone_id', $plotone->id)->first();
            if ($existing) {
                Log::info("Plotone {$plotone->nome} già migrato: {$existing->id}");
                continue;
            }

            // Trova l'unità organizzativa della compagnia parent
            $parentUnit = null;
            if ($plotone->compagnia_id) {
                $parentUnit = OrganizationalUnit::where('legacy_compagnia_id', $plotone->compagnia_id)->first();
            }

            if (!$parentUnit) {
                Log::warning("Compagnia parent non trovata per plotone {$plotone->nome}. Plotone saltato.");
                continue;
            }

            // Crea unità organizzativa
            $unit = OrganizationalUnit::create([
                'id' => Uuid::uuid4()->toString(),
                'name' => $plotone->nome,
                'code' => $plotone->codice ?? null,
                'type_id' => $plotoneType->id,
                'parent_id' => $parentUnit->id,
                'depth' => 2,
                'path' => $parentUnit->path,
                'description' => $plotone->descrizione ?? null,
                'is_active' => true,
                'sort_order' => $plotone->id,
                'legacy_plotone_id' => $plotone->id,
            ]);

            // Aggiorna path
            $unit->path = $parentUnit->path . '/' . $unit->id;
            $unit->save();

            // Aggiorna militari di questo plotone che non hanno già un'unità organizzativa
            // assegnata (solo se non già assegnati alla compagnia)
            $updated = DB::table('militari')
                ->where('plotone_id', $plotone->id)
                ->whereNull('organizational_unit_id')
                ->update([
                    'organizational_unit_id' => $unit->id,
                    'updated_at' => now(),
                ]);

            Log::info("Migrato plotone {$plotone->nome}: {$unit->id} ({$updated} militari aggiornati)");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi le unità create dalla migrazione (identificabili da legacy_*)
        OrganizationalUnit::whereNotNull('legacy_plotone_id')->delete();
        OrganizationalUnit::whereNotNull('legacy_compagnia_id')->delete();
        OrganizationalUnit::where('code', 'LEON')->delete();

        // Resetta organizational_unit_id su militari e users
        DB::table('militari')->update(['organizational_unit_id' => null]);
        DB::table('users')->update(['organizational_unit_id' => null]);

        Log::info('Rollback migrazione compagnie e plotoni completato.');
    }
};
