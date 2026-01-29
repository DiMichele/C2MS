<?php

namespace App\Console\Commands;

use App\Models\Compagnia;
use App\Models\Militare;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\UnitAssignment;
use App\Models\UnitClosure;
use App\Services\OrganizationalHierarchyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Comando per migrare la struttura esistente (compagnie, plotoni, poli)
 * verso il nuovo sistema gerarchico organizzativo.
 */
class MigrateToHierarchy extends Command
{
    protected $signature = 'hierarchy:migrate 
                            {--fresh : Svuota le tabelle prima della migrazione}
                            {--with-assignments : Migra anche le assegnazioni dei militari}
                            {--dry-run : Esegue in modalità simulazione senza modificare il database}';

    protected $description = 'Migra la struttura organizzativa esistente verso il nuovo sistema gerarchico';

    protected OrganizationalHierarchyService $hierarchyService;
    protected bool $dryRun = false;
    protected array $stats = [
        'types_created' => 0,
        'units_created' => 0,
        'assignments_created' => 0,
        'errors' => [],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->hierarchyService = new OrganizationalHierarchyService();
    }

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('*** MODALITÀ SIMULAZIONE - Nessuna modifica verrà effettuata ***');
        }

        $this->info('Avvio migrazione verso gerarchia organizzativa...');
        $this->newLine();

        try {
            if ($this->option('fresh')) {
                $this->cleanTables();
            }

            // Step 1: Crea i tipi di unità
            $this->createUnitTypes();

            // Step 2: Crea la struttura gerarchica
            if (!$this->dryRun) {
                DB::transaction(function () {
                    $this->createHierarchyStructure();
                });
            } else {
                $this->createHierarchyStructure();
            }

            // Step 3: Migra le assegnazioni
            if ($this->option('with-assignments')) {
                $this->migrateAssignments();
            }

            // Step 4: Verifica integrità
            $this->verifyIntegrity();

            // Riepilogo
            $this->printSummary();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Errore durante la migrazione: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Pulisce le tabelle della gerarchia.
     */
    protected function cleanTables(): void
    {
        if ($this->dryRun) {
            $this->warn('  [SIMULAZIONE] Pulizia tabelle saltata');
            return;
        }

        $this->info('Pulizia tabelle esistenti...');
        
        // Disabilita i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        UnitAssignment::truncate();
        UnitClosure::truncate();
        OrganizationalUnit::truncate();
        
        // Riabilita i controlli delle foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('  ✓ Tabelle pulite');
    }

    /**
     * Crea i tipi di unità organizzative.
     */
    protected function createUnitTypes(): void
    {
        $this->info('Creazione tipi di unità...');

        $types = [
            ['code' => 'reggimento', 'name' => 'Reggimento', 'icon' => 'fa-landmark', 'color' => '#0A1E38', 'depth' => 0],
            ['code' => 'battaglione', 'name' => 'Battaglione', 'icon' => 'fa-shield-alt', 'color' => '#1A3A5F', 'depth' => 1],
            ['code' => 'compagnia', 'name' => 'Compagnia', 'icon' => 'fa-users-cog', 'color' => '#2E5A8F', 'depth' => 2],
            ['code' => 'plotone', 'name' => 'Plotone', 'icon' => 'fa-users', 'color' => '#4A7AB0', 'depth' => 3],
            ['code' => 'ufficio', 'name' => 'Ufficio', 'icon' => 'fa-building', 'color' => '#6B8CBF', 'depth' => 2],
            ['code' => 'sezione', 'name' => 'Sezione', 'icon' => 'fa-sitemap', 'color' => '#8BA4CC', 'depth' => 3],
            ['code' => 'infermeria', 'name' => 'Infermeria', 'icon' => 'fa-medkit', 'color' => '#C62828', 'depth' => 1],
            ['code' => 'ccsl', 'name' => 'CCSL', 'icon' => 'fa-warehouse', 'color' => '#FF8F00', 'depth' => 1],
        ];

        foreach ($types as $typeData) {
            if (!$this->dryRun) {
                OrganizationalUnitType::updateOrCreate(
                    ['code' => $typeData['code']],
                    [
                        'name' => $typeData['name'],
                        'icon' => $typeData['icon'],
                        'color' => $typeData['color'],
                        'default_depth_level' => $typeData['depth'],
                        'is_active' => true,
                    ]
                );
            }
            $this->stats['types_created']++;
            $this->line("  + {$typeData['name']}");
        }

        $this->info("  ✓ {$this->stats['types_created']} tipi creati");
    }

    /**
     * Crea la struttura gerarchica.
     */
    protected function createHierarchyStructure(): void
    {
        $this->info('Creazione struttura gerarchica...');

        // 1. Crea il Reggimento (root)
        $reggimento = $this->createReggimento();

        // 2. Crea battaglioni e sezioni di primo livello
        $firstLevelUnits = $this->createFirstLevelUnits($reggimento);

        // 3. Migra le compagnie esistenti
        $this->migrateCompagnie($firstLevelUnits);

        // 4. Migra i plotoni
        $this->migratePlotoni();

        // 5. Migra i poli/uffici
        $this->migratePoli();

        $this->info("  ✓ {$this->stats['units_created']} unità create");
    }

    /**
     * Crea il reggimento root.
     */
    protected function createReggimento(): ?OrganizationalUnit
    {
        $this->line('  Creazione 11° Reggimento Trasmissioni...');

        if ($this->dryRun) {
            $this->stats['units_created']++;
            return null;
        }

        $type = OrganizationalUnitType::where('code', 'reggimento')->first();

        $reggimento = OrganizationalUnit::firstOrCreate(
            ['code' => 'REG11'],
            [
                'uuid' => (string) Str::uuid(),
                'type_id' => $type->id,
                'parent_id' => null,
                'name' => '11° Reggimento Trasmissioni',
                'path' => '',
                'depth' => 0,
                'is_active' => true,
            ]
        );

        UnitClosure::insertForNode($reggimento);
        $this->stats['units_created']++;

        return $reggimento;
    }

    /**
     * Crea unità di primo livello sotto il reggimento.
     * 
     * Struttura 11° Reggimento Trasmissioni:
     * - Comando di Reggimento (con uffici)
     * - Battaglione Leonessa (con compagnie 110, 124, 127)
     * - Battaglione Tonale (con compagnie 137, 140, 154)
     * - CCSL (con 6 plotoni)
     * - Comando alla Sede
     */
    protected function createFirstLevelUnits(?OrganizationalUnit $reggimento): array
    {
        $this->line('  Creazione struttura 11° Reggimento...');

        $units = [];
        $typeMap = [];

        if (!$this->dryRun) {
            $typeMap = [
                'battaglione' => OrganizationalUnitType::where('code', 'battaglione')->first(),
                'ufficio' => OrganizationalUnitType::where('code', 'ufficio')->first(),
                'sezione' => OrganizationalUnitType::where('code', 'sezione')->first(),
                'ccsl' => OrganizationalUnitType::where('code', 'ccsl')->first(),
                'compagnia' => OrganizationalUnitType::where('code', 'compagnia')->first(),
                'plotone' => OrganizationalUnitType::where('code', 'plotone')->first(),
            ];
        }

        // === COMANDO DI REGGIMENTO ===
        if (!$this->dryRun) {
            $comandoReg = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['sezione']->id,
                'name' => 'Comando di Reggimento',
                'code' => 'CMD-REG',
                'sort_order' => 1,
                'is_active' => true,
            ], $reggimento);
            $units['CMD-REG'] = $comandoReg;
            $this->stats['units_created']++;
            $this->line("    + Comando di Reggimento");

            // Uffici del Comando
            foreach ([
                ['code' => 'UMAG', 'name' => 'Ufficio Maggiorità e Personale'],
                ['code' => 'UOAI', 'name' => 'Ufficio OAI'],
                ['code' => 'ULOG', 'name' => 'Ufficio Logistico'],
                ['code' => 'SAMM', 'name' => 'Sezione Amministrativa'],
            ] as $uff) {
                $this->hierarchyService->createUnit([
                    'type_id' => $typeMap['ufficio']->id,
                    'name' => $uff['name'],
                    'code' => $uff['code'],
                    'is_active' => true,
                ], $comandoReg);
                $this->stats['units_created']++;
            }
        }

        // === BATTAGLIONE LEONESSA ===
        if (!$this->dryRun) {
            $leonessa = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['battaglione']->id,
                'name' => 'Battaglione Leonessa',
                'code' => 'BTG-LEON',
                'sort_order' => 2,
                'is_active' => true,
            ], $reggimento);
            $units['BTG-LEON'] = $leonessa;
            $this->stats['units_created']++;
            $this->line("    + Battaglione Leonessa");

            // Compagnie Leonessa
            foreach (['110', '124', '127'] as $num) {
                $comp = $this->hierarchyService->createUnit([
                    'type_id' => $typeMap['compagnia']->id,
                    'name' => $num . '^ Compagnia',
                    'code' => 'C' . $num,
                    'is_active' => true,
                ], $leonessa);
                $units['C' . $num] = $comp;
                $this->stats['units_created']++;
            }
        }

        // === BATTAGLIONE TONALE ===
        if (!$this->dryRun) {
            $tonale = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['battaglione']->id,
                'name' => 'Battaglione Tonale',
                'code' => 'BTG-TON',
                'sort_order' => 3,
                'is_active' => true,
            ], $reggimento);
            $units['BTG-TON'] = $tonale;
            $this->stats['units_created']++;
            $this->line("    + Battaglione Tonale");

            // Compagnie Tonale
            foreach (['137', '140', '154'] as $num) {
                $comp = $this->hierarchyService->createUnit([
                    'type_id' => $typeMap['compagnia']->id,
                    'name' => $num . '^ Compagnia',
                    'code' => 'C' . $num,
                    'is_active' => true,
                ], $tonale);
                $units['C' . $num] = $comp;
                $this->stats['units_created']++;
            }
        }

        // === CCSL ===
        if (!$this->dryRun) {
            $ccsl = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['ccsl']->id,
                'name' => 'Compagnia Comando e Supporto Logistico',
                'code' => 'CCSL',
                'sort_order' => 4,
                'is_active' => true,
            ], $reggimento);
            $units['CCSL'] = $ccsl;
            $this->stats['units_created']++;
            $this->line("    + CCSL");

            // Plotoni CCSL
            foreach ([
                ['code' => 'PL-C3', 'name' => 'Plotone C3'],
                ['code' => 'PL-SG', 'name' => 'Plotone Servizi Generali'],
                ['code' => 'PL-TM', 'name' => 'Plotone Tra.Mat.'],
                ['code' => 'PL-SAN', 'name' => 'Plotone Sanità'],
                ['code' => 'PL-COM', 'name' => 'Plotone Commissariato'],
                ['code' => 'PL-INF', 'name' => 'Plotone Infrastrutturale'],
            ] as $pl) {
                $this->hierarchyService->createUnit([
                    'type_id' => $typeMap['plotone']->id,
                    'name' => $pl['name'],
                    'code' => $pl['code'],
                    'is_active' => true,
                ], $ccsl);
                $this->stats['units_created']++;
            }
        }

        // === COMANDO ALLA SEDE ===
        if (!$this->dryRun) {
            $cmdSede = $this->hierarchyService->createUnit([
                'type_id' => $typeMap['sezione']->id,
                'name' => 'Comando alla Sede',
                'code' => 'CMD-SEDE',
                'sort_order' => 5,
                'is_active' => true,
            ], $reggimento);
            $units['CMD-SEDE'] = $cmdSede;
            $this->stats['units_created']++;
            $this->line("    + Comando alla Sede");
        }

        return $units;
    }

    /**
     * Migra le compagnie esistenti.
     * Mappa le compagnie 110, 124, 127 al Battaglione Leonessa.
     */
    protected function migrateCompagnie(array $battaglioni): void
    {
        $this->line('  Migrazione compagnie esistenti...');

        $compagnie = Compagnia::all();
        $compagniaType = $this->dryRun ? null : OrganizationalUnitType::where('code', 'compagnia')->first();
        
        // Compagnie esistenti vanno sotto Battaglione Leonessa
        $defaultParent = $battaglioni['BTG-LEON'] ?? null;

        foreach ($compagnie as $compagnia) {
            // Verifica se già migrata
            if (!$this->dryRun && OrganizationalUnit::where('legacy_compagnia_id', $compagnia->id)->exists()) {
                $this->line("    - {$compagnia->nome} (già migrata)");
                continue;
            }

            // Determina il codice basato sul numero della compagnia
            $code = $compagnia->codice ?? $this->determineCompagniaCode($compagnia);

            if ($this->dryRun) {
                $this->stats['units_created']++;
                $this->line("    + {$compagnia->nome}");
                continue;
            }

            // Cerca se esiste già un'unità con questo codice
            $existingUnit = OrganizationalUnit::where('code', $code)->first();
            
            if ($existingUnit) {
                // Aggiorna l'unità esistente con il legacy_compagnia_id
                $existingUnit->update(['legacy_compagnia_id' => $compagnia->id]);
                $this->line("    ✓ {$compagnia->nome} collegata a unità esistente {$code}");
            } else {
                // Crea una nuova unità
                $this->hierarchyService->createUnit([
                    'type_id' => $compagniaType->id,
                    'name' => $compagnia->nome,
                    'code' => $code,
                    'description' => $compagnia->descrizione,
                    'legacy_compagnia_id' => $compagnia->id,
                    'is_active' => true,
                ], $defaultParent);

                $this->stats['units_created']++;
                $this->line("    + {$compagnia->nome}");
            }
        }
    }

    /**
     * Determina il codice unità dalla compagnia legacy.
     */
    protected function determineCompagniaCode(Compagnia $compagnia): string
    {
        $nome = strtolower($compagnia->nome);
        
        // Estrai il numero dalla stringa
        preg_match('/(\d+)/', $nome, $matches);
        if (!empty($matches[1])) {
            return 'C' . $matches[1];
        }
        
        return 'C' . $compagnia->id;
    }

    /**
     * Migra i plotoni esistenti.
     */
    protected function migratePlotoni(): void
    {
        $this->line('  Migrazione plotoni...');

        $plotoni = Plotone::with('compagnia')->get();
        $plotoneType = $this->dryRun ? null : OrganizationalUnitType::where('code', 'plotone')->first();

        foreach ($plotoni as $plotone) {
            // Trova la compagnia parent
            $parentUnit = $this->dryRun ? null : OrganizationalUnit::where('legacy_compagnia_id', $plotone->compagnia_id)->first();

            if (!$this->dryRun && !$parentUnit) {
                $this->stats['errors'][] = "Compagnia parent non trovata per plotone {$plotone->nome}";
                continue;
            }

            // Verifica se già migrato
            if (!$this->dryRun && OrganizationalUnit::where('legacy_plotone_id', $plotone->id)->exists()) {
                continue;
            }

            if ($this->dryRun) {
                $this->stats['units_created']++;
                continue;
            }

            $this->hierarchyService->createUnit([
                'type_id' => $plotoneType->id,
                'name' => $plotone->nome,
                'description' => $plotone->descrizione,
                'legacy_plotone_id' => $plotone->id,
                'legacy_compagnia_id' => $plotone->compagnia_id,
                'is_active' => true,
            ], $parentUnit);

            $this->stats['units_created']++;
        }

        $this->line("    ✓ {$plotoni->count()} plotoni elaborati");
    }

    /**
     * Migra i poli/uffici esistenti.
     */
    protected function migratePoli(): void
    {
        $this->line('  Migrazione poli/uffici...');

        if (!class_exists(Polo::class)) {
            $this->line('    (Modello Polo non disponibile)');
            return;
        }

        $poli = Polo::all();
        $ufficioType = $this->dryRun ? null : OrganizationalUnitType::where('code', 'ufficio')->first();

        foreach ($poli as $polo) {
            // Trova il parent (compagnia o reggimento)
            $parentUnit = null;
            if (!$this->dryRun) {
                if ($polo->compagnia_id) {
                    $parentUnit = OrganizationalUnit::where('legacy_compagnia_id', $polo->compagnia_id)->first();
                }
                if (!$parentUnit) {
                    $parentUnit = OrganizationalUnit::roots()->first();
                }
            }

            // Verifica se già migrato
            if (!$this->dryRun && OrganizationalUnit::where('legacy_polo_id', $polo->id)->exists()) {
                continue;
            }

            if ($this->dryRun) {
                $this->stats['units_created']++;
                continue;
            }

            $this->hierarchyService->createUnit([
                'type_id' => $ufficioType->id,
                'name' => $polo->nome,
                'legacy_polo_id' => $polo->id,
                'is_active' => true,
            ], $parentUnit);

            $this->stats['units_created']++;
        }

        $this->line("    ✓ {$poli->count()} poli elaborati");
    }

    /**
     * Migra le assegnazioni dei militari.
     */
    protected function migrateAssignments(): void
    {
        $this->info('Migrazione assegnazioni militari...');

        $militari = Militare::whereNotNull('plotone_id')
            ->orWhereNotNull('polo_id')
            ->get();

        foreach ($militari as $militare) {
            // Trova l'unità appropriata
            $unit = null;

            if (!$this->dryRun) {
                if ($militare->plotone_id) {
                    $unit = OrganizationalUnit::where('legacy_plotone_id', $militare->plotone_id)->first();
                } elseif ($militare->polo_id) {
                    $unit = OrganizationalUnit::where('legacy_polo_id', $militare->polo_id)->first();
                }
            }

            if ($this->dryRun || $unit) {
                if (!$this->dryRun) {
                    UnitAssignment::updateOrCreate(
                        [
                            'unit_id' => $unit->id,
                            'assignable_type' => Militare::class,
                            'assignable_id' => $militare->id,
                        ],
                        [
                            'role' => 'membro',
                            'is_primary' => true,
                        ]
                    );
                }
                $this->stats['assignments_created']++;
            }
        }

        $this->info("  ✓ {$this->stats['assignments_created']} assegnazioni create");

        // Migra anche il campo organizational_unit_id direttamente sui militari
        $this->migrateOrganizationalUnitIds();
    }

    /**
     * Popola il campo organizational_unit_id sui militari basandosi sulla compagnia legacy.
     */
    protected function migrateOrganizationalUnitIds(): void
    {
        $this->info('Popolamento organizational_unit_id sui militari...');

        if ($this->dryRun) {
            $this->line('  [SIMULAZIONE] Skip popolamento');
            return;
        }

        $count = 0;
        $militari = Militare::withoutGlobalScopes()
            ->whereNull('organizational_unit_id')
            ->whereNotNull('compagnia_id')
            ->get();

        foreach ($militari as $militare) {
            // Trova l'unità organizzativa dalla compagnia legacy
            $unit = OrganizationalUnit::where('legacy_compagnia_id', $militare->compagnia_id)->first();
            
            if ($unit) {
                $militare->update(['organizational_unit_id' => $unit->id]);
                $count++;
            }
        }

        $this->info("  ✓ {$count} militari aggiornati con organizational_unit_id");
    }

    /**
     * Verifica l'integrità della struttura.
     */
    protected function verifyIntegrity(): void
    {
        if ($this->dryRun) {
            return;
        }

        $this->info('Verifica integrità...');

        $errors = $this->hierarchyService->checkIntegrity();

        if (!empty($errors)) {
            $this->warn('  Errori trovati:');
            foreach ($errors as $error) {
                $this->warn("    - {$error}");
                $this->stats['errors'][] = $error;
            }

            if ($this->confirm('Vuoi tentare la riparazione automatica?')) {
                $repairs = $this->hierarchyService->repairIntegrity();
                foreach ($repairs as $repair) {
                    $this->info("    ✓ {$repair}");
                }
            }
        } else {
            $this->info('  ✓ Nessun errore di integrità');
        }
    }

    /**
     * Stampa il riepilogo della migrazione.
     */
    protected function printSummary(): void
    {
        $this->newLine();
        $this->info('=== RIEPILOGO MIGRAZIONE ===');
        $this->table(
            ['Metrica', 'Valore'],
            [
                ['Tipi creati', $this->stats['types_created']],
                ['Unità create', $this->stats['units_created']],
                ['Assegnazioni create', $this->stats['assignments_created']],
                ['Errori', count($this->stats['errors'])],
            ]
        );

        if (!empty($this->stats['errors'])) {
            $this->newLine();
            $this->error('Errori riscontrati:');
            foreach ($this->stats['errors'] as $error) {
                $this->error("  - {$error}");
            }
        }

        if ($this->dryRun) {
            $this->newLine();
            $this->warn('Esecuzione in modalità simulazione. Nessuna modifica è stata effettuata.');
            $this->info('Rimuovi --dry-run per eseguire la migrazione reale.');
        }
    }
}
