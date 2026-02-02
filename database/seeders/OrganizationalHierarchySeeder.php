<?php

namespace Database\Seeders;

use App\Models\Compagnia;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\UnitClosure;
use App\Services\OrganizationalHierarchyService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder per migrare la struttura organizzativa esistente al nuovo sistema gerarchico.
 * 
 * Operazioni:
 * 1. Crea il nodo root Reggimento
 * 2. Crea i battaglioni e sezioni di primo livello
 * 3. Migra le compagnie esistenti sotto i rispettivi battaglioni
 * 4. Migra i plotoni sotto le compagnie
 * 5. Migra i poli/uffici sotto le compagnie
 * 6. Popola la closure table
 */
class OrganizationalHierarchySeeder extends Seeder
{
    protected OrganizationalHierarchyService $hierarchyService;

    public function __construct()
    {
        $this->hierarchyService = new OrganizationalHierarchyService();
    }

    public function run(): void
    {
        DB::transaction(function () {
            // Pulisci le tabelle se necessario (opzionale, commentare in produzione)
            // $this->cleanTables();

            // 1. Crea il Reggimento (root)
            $reggimento = $this->createReggimento();
            $this->command->info("✓ Reggimento creato: {$reggimento->name}");

            // 2. Crea i battaglioni e sezioni di primo livello
            $battaglioni = $this->createBattaglioniESezioni($reggimento);
            $this->command->info("✓ Battaglioni e sezioni creati: " . count($battaglioni));

            // 3. Migra le compagnie esistenti
            $this->migrateCompagnie($battaglioni);

            // 4. Migra i plotoni
            $this->migratePlotoni();

            // 5. Migra i poli/uffici
            $this->migratePoli();

            // 6. Verifica e ripara la closure table
            $this->verifyAndRepairClosureTable();

            $this->command->info("✓ Migrazione completata con successo!");
        });
    }

    /**
     * Crea il nodo root Reggimento.
     */
    protected function createReggimento(): OrganizationalUnit
    {
        $type = OrganizationalUnitType::where('code', 'reggimento')->first();

        return OrganizationalUnit::firstOrCreate(
            ['code' => 'REG11'],
            [
                'uuid' => (string) Str::uuid(),
                'type_id' => $type->id,
                'parent_id' => null,
                'name' => '11° Reggimento Trasmissioni',
                'code' => 'REG11',
                'description' => 'Reggimento Trasmissioni - nodo root della struttura organizzativa',
                'path' => '',
                'depth' => 0,
                'sort_order' => 0,
                'is_active' => true,
                'settings' => [
                    'sede' => 'Sede del Reggimento',
                    'tipo' => 'Trasmissioni',
                ],
            ]
        );
    }

    /**
     * Crea i battaglioni e le sezioni di primo livello.
     * 
     * Struttura 11° Reggimento Trasmissioni:
     * - Comando di Reggimento (con 4 uffici)
     * - Battaglione Leonessa (con compagnie 110, 124, 127)
     * - Battaglione Tonale (con compagnie 137, 140, 154)
     * - CCSL (con 6 plotoni)
     * - Comando alla Sede
     */
    protected function createBattaglioniESezioni(OrganizationalUnit $reggimento): array
    {
        $units = [];
        $typeMap = [
            'battaglione' => OrganizationalUnitType::where('code', 'battaglione')->first(),
            'ufficio' => OrganizationalUnitType::where('code', 'ufficio')->first(),
            'sezione' => OrganizationalUnitType::where('code', 'sezione')->first(),
            'ccsl' => OrganizationalUnitType::where('code', 'ccsl')->first(),
            'compagnia' => OrganizationalUnitType::where('code', 'compagnia')->first(),
            'plotone' => OrganizationalUnitType::where('code', 'plotone')->first(),
        ];

        // ===== COMANDO DI REGGIMENTO =====
        $comandoReg = $this->hierarchyService->createUnit([
            'type_id' => $typeMap['sezione']->id,
            'name' => 'Comando di Reggimento',
            'code' => 'CMD-REG',
            'description' => 'Comando del Reggimento',
            'sort_order' => 1,
            'is_active' => true,
        ], $reggimento);
        $units['CMD-REG'] = $comandoReg;

        // Uffici del Comando di Reggimento
        $ufficiComando = [
            ['code' => 'UMAG', 'name' => 'Ufficio Maggiorità e Personale', 'sort' => 1],
            ['code' => 'UOAI', 'name' => 'Ufficio OAI', 'sort' => 2],
            ['code' => 'ULOG', 'name' => 'Ufficio Logistico', 'sort' => 3],
            ['code' => 'SAMM', 'name' => 'Sezione Amministrativa', 'sort' => 4],
        ];
        foreach ($ufficiComando as $uff) {
            $this->hierarchyService->createUnit([
                'type_id' => $typeMap['ufficio']->id,
                'name' => $uff['name'],
                'code' => $uff['code'],
                'sort_order' => $uff['sort'],
                'is_active' => true,
            ], $comandoReg);
        }

        // ===== BATTAGLIONE LEONESSA =====
        $leonessa = $this->hierarchyService->createUnit([
            'type_id' => $typeMap['battaglione']->id,
            'name' => 'Battaglione Leonessa',
            'code' => 'BTG-LEON',
            'description' => 'Battaglione operativo',
            'sort_order' => 2,
            'is_active' => true,
        ], $reggimento);
        $units['BTG-LEON'] = $leonessa;

        // Compagnie del Battaglione Leonessa
        $compagnieLeonessa = ['110', '124', '127'];
        foreach ($compagnieLeonessa as $i => $num) {
            $comp = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['compagnia']->id,
                'name' => $num . '^ Compagnia',
                'code' => 'C' . $num,
                'sort_order' => $i + 1,
                'is_active' => true,
            ], $leonessa);
            $units['C' . $num] = $comp;
        }

        // ===== BATTAGLIONE TONALE =====
        $tonale = $this->hierarchyService->createUnit([
            'type_id' => $typeMap['battaglione']->id,
            'name' => 'Battaglione Tonale',
            'code' => 'BTG-TON',
            'description' => 'Battaglione operativo',
            'sort_order' => 3,
            'is_active' => true,
        ], $reggimento);
        $units['BTG-TON'] = $tonale;

        // Compagnie del Battaglione Tonale
        $compagnieTonale = ['137', '140', '154'];
        foreach ($compagnieTonale as $i => $num) {
            $comp = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['compagnia']->id,
                'name' => $num . '^ Compagnia',
                'code' => 'C' . $num,
                'sort_order' => $i + 1,
                'is_active' => true,
            ], $tonale);
            $units['C' . $num] = $comp;
        }

        // ===== CCSL (Compagnia Comando e Supporto Logistico) =====
        $ccsl = $this->hierarchyService->createUnit([
            'type_id' => $typeMap['ccsl']->id,
            'name' => 'Compagnia Comando e Supporto Logistico',
            'code' => 'CCSL',
            'description' => 'CCSL - articolata su sei plotoni',
            'sort_order' => 4,
            'is_active' => true,
        ], $reggimento);
        $units['CCSL'] = $ccsl;

        // Plotoni della CCSL
        $plotoniCCSL = [
            ['code' => 'PL-C3', 'name' => 'Plotone C3', 'sort' => 1],
            ['code' => 'PL-SG', 'name' => 'Plotone Servizi Generali', 'sort' => 2],
            ['code' => 'PL-TM', 'name' => 'Plotone Tra.Mat.', 'sort' => 3],
            ['code' => 'PL-SAN', 'name' => 'Plotone Sanità', 'sort' => 4],
            ['code' => 'PL-COM', 'name' => 'Plotone Commissariato', 'sort' => 5],
            ['code' => 'PL-INF', 'name' => 'Plotone Infrastrutturale', 'sort' => 6],
        ];
        foreach ($plotoniCCSL as $pl) {
            $this->hierarchyService->createUnit([
                'type_id' => $typeMap['plotone']->id,
                'name' => $pl['name'],
                'code' => $pl['code'],
                'sort_order' => $pl['sort'],
                'is_active' => true,
            ], $ccsl);
        }

        // ===== COMANDO ALLA SEDE =====
        $cmdSede = $this->hierarchyService->createUnit([
            'type_id' => $typeMap['sezione']->id,
            'name' => 'Comando alla Sede',
            'code' => 'CMD-SEDE',
            'description' => 'Comando alla Sede',
            'sort_order' => 5,
            'is_active' => true,
        ], $reggimento);
        $units['CMD-SEDE'] = $cmdSede;

        return $units;
    }

    /**
     * Migra le compagnie esistenti.
     */
    protected function migrateCompagnie(array $battaglioni): void
    {
        $compagniaType = OrganizationalUnitType::where('code', 'compagnia')->first();
        $compagnie = Compagnia::all();

        // Mappa di default: assegna le compagnie al Battaglione Leonessa
        // FIX: Usare la chiave corretta 'BTG-LEON' invece di 'LEON'
        $defaultBattaglione = $battaglioni['BTG-LEON'] ?? array_values($battaglioni)[0] ?? null;

        foreach ($compagnie as $compagnia) {
            // Determina il battaglione di appartenenza
            // Puoi personalizzare questa logica basandoti sul nome della compagnia
            $battaglione = $this->determineBattaglione($compagnia, $battaglioni) ?? $defaultBattaglione;

            if (!$battaglione) {
                $this->command->warn("⚠ Nessun battaglione trovato per {$compagnia->nome}, sarà sotto il reggimento");
                continue;
            }

            // Verifica se esiste già un'unità per questa compagnia
            $existingUnit = OrganizationalUnit::where('legacy_compagnia_id', $compagnia->id)->first();

            if ($existingUnit) {
                $this->command->info("  - Compagnia '{$compagnia->nome}' già migrata");
                continue;
            }

            // Crea l'unità organizzativa per la compagnia
            $unit = $this->hierarchyService->createUnit([
                'type_id' => $compagniaType->id,
                'name' => $compagnia->nome,
                'code' => $compagnia->codice ?? 'C' . $compagnia->id,
                'description' => $compagnia->descrizione,
                'legacy_compagnia_id' => $compagnia->id,
                'is_active' => true,
            ], $battaglione);

            $this->command->info("  - Compagnia '{$compagnia->nome}' migrata sotto '{$battaglione->name}'");
        }
    }

    /**
     * Migra i plotoni esistenti.
     */
    protected function migratePlotoni(): void
    {
        $plotoneType = OrganizationalUnitType::where('code', 'plotone')->first();
        $plotoni = Plotone::with('compagnia')->get();

        foreach ($plotoni as $plotone) {
            // Trova l'unità compagnia corrispondente
            $compagniaUnit = OrganizationalUnit::where('legacy_compagnia_id', $plotone->compagnia_id)->first();

            if (!$compagniaUnit) {
                $this->command->warn("  ⚠ Compagnia non trovata per plotone '{$plotone->nome}'");
                continue;
            }

            // Verifica se esiste già
            $existingUnit = OrganizationalUnit::where('legacy_plotone_id', $plotone->id)->first();

            if ($existingUnit) {
                continue;
            }

            // Crea l'unità organizzativa per il plotone
            $this->hierarchyService->createUnit([
                'type_id' => $plotoneType->id,
                'name' => $plotone->nome,
                'description' => $plotone->descrizione ?? null,
                'legacy_plotone_id' => $plotone->id,
                'legacy_compagnia_id' => $plotone->compagnia_id,
                'is_active' => true,
            ], $compagniaUnit);
        }

        $this->command->info("✓ Plotoni migrati: " . $plotoni->count());
    }

    /**
     * Migra i poli/uffici esistenti.
     */
    protected function migratePoli(): void
    {
        $ufficioType = OrganizationalUnitType::where('code', 'ufficio')->first();
        
        // Verifica se esiste il modello Polo
        if (!class_exists(Polo::class)) {
            $this->command->info("  - Modello Polo non trovato, skip migrazione poli");
            return;
        }

        $poli = Polo::all();

        foreach ($poli as $polo) {
            // Trova l'unità compagnia corrispondente
            $compagniaUnit = null;
            if ($polo->compagnia_id) {
                $compagniaUnit = OrganizationalUnit::where('legacy_compagnia_id', $polo->compagnia_id)->first();
            }

            // Se non ha compagnia, mettilo sotto il reggimento
            if (!$compagniaUnit) {
                $compagniaUnit = OrganizationalUnit::roots()->first();
            }

            // Verifica se esiste già
            $existingUnit = OrganizationalUnit::where('legacy_polo_id', $polo->id)->first();

            if ($existingUnit) {
                continue;
            }

            // Crea l'unità organizzativa per il polo/ufficio
            $this->hierarchyService->createUnit([
                'type_id' => $ufficioType->id,
                'name' => $polo->nome,
                'legacy_polo_id' => $polo->id,
                'is_active' => true,
            ], $compagniaUnit);
        }

        $this->command->info("✓ Poli/Uffici migrati: " . $poli->count());
    }

    /**
     * Determina il battaglione per una compagnia basandosi sul nome/numero.
     * 
     * Mappatura 11° Reggimento Trasmissioni:
     * - Battaglione Leonessa: 110^, 124^, 127^
     * - Battaglione Tonale: 137^, 140^, 154^
     */
    protected function determineBattaglione(Compagnia $compagnia, array $battaglioni): ?OrganizationalUnit
    {
        $nome = strtolower($compagnia->nome);
        $numero = $compagnia->numero ?? '';

        // Compagnie del Battaglione Leonessa
        $leonessa = ['110', '124', '127'];
        foreach ($leonessa as $num) {
            if (str_contains($nome, $num) || str_contains($numero, $num)) {
                return $battaglioni['BTG-LEON'] ?? null;
            }
        }

        // Compagnie del Battaglione Tonale
        $tonale = ['137', '140', '154'];
        foreach ($tonale as $num) {
            if (str_contains($nome, $num) || str_contains($numero, $num)) {
                return $battaglioni['BTG-TON'] ?? null;
            }
        }

        // Keyword matching
        if (str_contains($nome, 'leonessa')) {
            return $battaglioni['BTG-LEON'] ?? null;
        }
        if (str_contains($nome, 'tonale')) {
            return $battaglioni['BTG-TON'] ?? null;
        }

        // Default: Battaglione Leonessa per compagnie esistenti
        return $battaglioni['BTG-LEON'] ?? null;
    }

    /**
     * Verifica e ripara la closure table.
     */
    protected function verifyAndRepairClosureTable(): void
    {
        $errors = $this->hierarchyService->checkIntegrity();

        if (!empty($errors)) {
            $this->command->warn("⚠ Errori di integrità trovati, eseguo riparazione...");
            foreach ($errors as $error) {
                $this->command->warn("  - {$error}");
            }
            
            $repairs = $this->hierarchyService->repairIntegrity();
            foreach ($repairs as $repair) {
                $this->command->info("  ✓ {$repair}");
            }
        } else {
            $this->command->info("✓ Integrità della gerarchia verificata: OK");
        }
    }

    /**
     * Pulisce le tabelle (opzionale, usare con cautela).
     */
    protected function cleanTables(): void
    {
        UnitClosure::truncate();
        OrganizationalUnit::truncate();
        
        $this->command->warn("⚠ Tabelle organizational_units e unit_closure svuotate");
    }
}
