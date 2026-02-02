<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MigrateToFullHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:full-hierarchy 
                            {--dry-run : Esegui in modalità dry-run senza salvare le modifiche}
                            {--step= : Esegui solo un passaggio specifico (1-6)}
                            {--force : Forza l\'esecuzione anche se alcune migrazioni sono già state eseguite}
                            {--verify : Verifica l\'integrità dei dati dopo la migrazione}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comando master per orchestrare tutte le migrazioni del sistema multi-tenancy';

    /**
     * Passaggi della migrazione
     */
    protected array $steps = [
        1 => 'Associa ruoli esistenti alle unità',
        2 => 'Migra plotoni a OrganizationalUnit',
        3 => 'Migra uffici (poli) a OrganizationalUnit',
        4 => 'Sincronizza organizational_unit_id in configurazione_ruolini',
        5 => 'Aggiorna militari con ufficio_unit_id',
        6 => 'Verifica integrità dati',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificStep = $this->option('step');
        $force = $this->option('force');
        $verify = $this->option('verify');

        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║  SUGECO - Migrazione Completa Sistema Multi-Tenancy            ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($dryRun) {
            $this->warn('🔸 Modalità DRY-RUN: nessuna modifica verrà salvata');
            $this->info('');
        }

        // Se richiesto un passaggio specifico
        if ($specificStep) {
            if (!isset($this->steps[(int)$specificStep])) {
                $this->error("Passaggio {$specificStep} non valido. Usa 1-6.");
                return Command::FAILURE;
            }
            return $this->runStep((int)$specificStep, $dryRun, $force);
        }

        // Esegui tutti i passaggi
        $this->info('📋 Passaggi da eseguire:');
        foreach ($this->steps as $num => $desc) {
            $this->line("   {$num}. {$desc}");
        }
        $this->info('');

        if (!$dryRun && !$this->confirm('Continuare con la migrazione?')) {
            $this->warn('Migrazione annullata.');
            return Command::SUCCESS;
        }

        $startTime = now();
        $errors = 0;

        foreach ($this->steps as $stepNum => $stepDesc) {
            $result = $this->runStep($stepNum, $dryRun, $force);
            if ($result === Command::FAILURE) {
                $errors++;
            }
        }

        $this->info('');
        $this->info('════════════════════════════════════════════════════════════════');
        
        $duration = now()->diffInSeconds($startTime);
        
        if ($errors === 0) {
            $this->info("✅ Migrazione completata con successo in {$duration} secondi!");
        } else {
            $this->warn("⚠️ Migrazione completata con {$errors} errori in {$duration} secondi.");
        }

        // Verifica integrità se richiesto
        if ($verify || !$dryRun) {
            $this->info('');
            $this->info('🔍 Verifica integrità dati...');
            $this->verifyIntegrity();
        }

        Log::info('MigrateToFullHierarchy: Migrazione completata', [
            'dry_run' => $dryRun,
            'errors' => $errors,
            'duration_seconds' => $duration,
        ]);

        return $errors === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Esegue un singolo passaggio
     */
    protected function runStep(int $stepNum, bool $dryRun, bool $force): int
    {
        $stepDesc = $this->steps[$stepNum] ?? 'Passaggio sconosciuto';
        
        $this->info('');
        $this->info("──────────────────────────────────────────────────────");
        $this->info("📌 Passaggio {$stepNum}: {$stepDesc}");
        $this->info("──────────────────────────────────────────────────────");

        try {
            switch ($stepNum) {
                case 1:
                    return $this->step1_migrateRoles($dryRun, $force);
                case 2:
                    return $this->step2_migratePlotoni($dryRun, $force);
                case 3:
                    return $this->step3_migratePoli($dryRun, $force);
                case 4:
                    return $this->step4_syncConfigurazioneRuolini($dryRun, $force);
                case 5:
                    return $this->step5_updateMilitariUfficio($dryRun, $force);
                case 6:
                    return $this->step6_verifyIntegrity($dryRun);
                default:
                    $this->error("Passaggio {$stepNum} non implementato.");
                    return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Errore nel passaggio {$stepNum}: {$e->getMessage()}");
            Log::error("MigrateToFullHierarchy: Errore passaggio {$stepNum}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Passaggio 1: Associa ruoli esistenti alle unità
     */
    protected function step1_migrateRoles(bool $dryRun, bool $force): int
    {
        $roles = DB::table('roles')
            ->whereNotNull('compagnia_id')
            ->when(!$force, fn($q) => $q->whereNull('organizational_unit_id'))
            ->get();

        if ($roles->isEmpty()) {
            $this->info('   ✓ Nessun ruolo da migrare');
            return Command::SUCCESS;
        }

        $this->info("   Trovati {$roles->count()} ruoli da migrare");

        $migrated = 0;
        foreach ($roles as $role) {
            $unit = DB::table('organizational_units')
                ->where('legacy_compagnia_id', $role->compagnia_id)
                ->first();

            if ($unit) {
                if (!$dryRun) {
                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update(['organizational_unit_id' => $unit->id, 'updated_at' => now()]);
                }
                $this->line("     • Ruolo '{$role->name}' → Unità '{$unit->name}'");
                $migrated++;
            } else {
                $this->warn("     ⚠ Ruolo '{$role->name}': nessuna unità per compagnia_id={$role->compagnia_id}");
            }
        }

        $this->info("   ✓ Migrati {$migrated}/{$roles->count()} ruoli");
        return Command::SUCCESS;
    }

    /**
     * Passaggio 2: Migra plotoni a OrganizationalUnit
     */
    protected function step2_migratePlotoni(bool $dryRun, bool $force): int
    {
        // Verifica se i plotoni sono già stati migrati
        $existingPlotoni = DB::table('organizational_units')
            ->whereNotNull('legacy_plotone_id')
            ->count();

        if ($existingPlotoni > 0 && !$force) {
            $this->info("   ✓ Plotoni già migrati ({$existingPlotoni} unità esistenti)");
            return Command::SUCCESS;
        }

        $this->info("   La migrazione plotoni viene gestita dalle Laravel migrations.");
        $this->info("   Esegui: php artisan migrate --force");
        
        return Command::SUCCESS;
    }

    /**
     * Passaggio 3: Migra uffici (poli) a OrganizationalUnit
     */
    protected function step3_migratePoli(bool $dryRun, bool $force): int
    {
        // Verifica se i poli sono già stati migrati
        $existingPoli = DB::table('organizational_units')
            ->whereNotNull('legacy_polo_id')
            ->count();

        if ($existingPoli > 0 && !$force) {
            $this->info("   ✓ Poli/Uffici già migrati ({$existingPoli} unità esistenti)");
            return Command::SUCCESS;
        }

        $this->info("   La migrazione poli viene gestita dalle Laravel migrations.");
        $this->info("   Esegui: php artisan migrate --force");
        
        return Command::SUCCESS;
    }

    /**
     * Passaggio 4: Sincronizza organizational_unit_id in configurazione_ruolini
     */
    protected function step4_syncConfigurazioneRuolini(bool $dryRun, bool $force): int
    {
        $configs = DB::table('configurazione_ruolini')
            ->whereNotNull('compagnia_id')
            ->when(!$force, fn($q) => $q->whereNull('organizational_unit_id'))
            ->get();

        if ($configs->isEmpty()) {
            $this->info('   ✓ Nessuna configurazione da sincronizzare');
            return Command::SUCCESS;
        }

        $this->info("   Trovate {$configs->count()} configurazioni da sincronizzare");

        $synced = 0;
        foreach ($configs as $config) {
            $unit = DB::table('organizational_units')
                ->where('legacy_compagnia_id', $config->compagnia_id)
                ->first();

            if ($unit) {
                if (!$dryRun) {
                    DB::table('configurazione_ruolini')
                        ->where('id', $config->id)
                        ->update(['organizational_unit_id' => $unit->id, 'updated_at' => now()]);
                }
                $synced++;
            }
        }

        $this->info("   ✓ Sincronizzate {$synced}/{$configs->count()} configurazioni");
        return Command::SUCCESS;
    }

    /**
     * Passaggio 5: Aggiorna militari con ufficio_unit_id
     */
    protected function step5_updateMilitariUfficio(bool $dryRun, bool $force): int
    {
        if (!Schema::hasColumn('militari', 'ufficio_unit_id')) {
            $this->warn("   ⚠ Colonna ufficio_unit_id non presente. Esegui prima le migrazioni.");
            return Command::SUCCESS;
        }

        $militari = DB::table('militari')
            ->whereNotNull('polo_id')
            ->when(!$force, fn($q) => $q->whereNull('ufficio_unit_id'))
            ->get();

        if ($militari->isEmpty()) {
            $this->info('   ✓ Nessun militare da aggiornare');
            return Command::SUCCESS;
        }

        $this->info("   Trovati {$militari->count()} militari da aggiornare");

        $updated = 0;
        foreach ($militari as $militare) {
            $unit = DB::table('organizational_units')
                ->where('legacy_polo_id', $militare->polo_id)
                ->first();

            if ($unit) {
                if (!$dryRun) {
                    DB::table('militari')
                        ->where('id', $militare->id)
                        ->update(['ufficio_unit_id' => $unit->id, 'updated_at' => now()]);
                }
                $updated++;
            }
        }

        $this->info("   ✓ Aggiornati {$updated}/{$militari->count()} militari");
        return Command::SUCCESS;
    }

    /**
     * Passaggio 6: Verifica integrità dati
     */
    protected function step6_verifyIntegrity(bool $dryRun): int
    {
        $this->verifyIntegrity();
        return Command::SUCCESS;
    }

    /**
     * Verifica l'integrità dei dati migrati
     */
    protected function verifyIntegrity(): void
    {
        $issues = [];

        // 1. Ruoli senza organizational_unit_id (non globali)
        $rolesNoUnit = DB::table('roles')
            ->whereNull('organizational_unit_id')
            ->where('is_global', false)
            ->count();
        if ($rolesNoUnit > 0) {
            $issues[] = "Ruoli non globali senza organizational_unit_id: {$rolesNoUnit}";
        }

        // 2. Militari con polo_id ma senza ufficio_unit_id
        if (Schema::hasColumn('militari', 'ufficio_unit_id')) {
            $militariNoUfficio = DB::table('militari')
                ->whereNotNull('polo_id')
                ->whereNull('ufficio_unit_id')
                ->count();
            if ($militariNoUfficio > 0) {
                $issues[] = "Militari con polo_id ma senza ufficio_unit_id: {$militariNoUfficio}";
            }
        }

        // 3. Configurazioni ruolini senza organizational_unit_id
        $configsNoUnit = DB::table('configurazione_ruolini')
            ->whereNotNull('compagnia_id')
            ->whereNull('organizational_unit_id')
            ->count();
        if ($configsNoUnit > 0) {
            $issues[] = "Configurazioni ruolini senza organizational_unit_id: {$configsNoUnit}";
        }

        // 4. Unità orfane nella closure table
        $orphanClosures = DB::table('unit_closure as uc')
            ->leftJoin('organizational_units as ou', 'uc.descendant_id', '=', 'ou.id')
            ->whereNull('ou.id')
            ->count();
        if ($orphanClosures > 0) {
            $issues[] = "Record orfani in unit_closure: {$orphanClosures}";
        }

        if (empty($issues)) {
            $this->info('   ✅ Nessun problema di integrità rilevato');
        } else {
            $this->warn('   ⚠️ Problemi rilevati:');
            foreach ($issues as $issue) {
                $this->warn("      • {$issue}");
            }
        }

        // Statistiche
        $this->info('');
        $this->info('   📊 Statistiche:');
        $this->table(
            ['Tabella', 'Totale', 'Con Unit ID', 'Percentuale'],
            [
                ['roles', DB::table('roles')->count(), DB::table('roles')->whereNotNull('organizational_unit_id')->count(), $this->calcPercentage('roles', 'organizational_unit_id')],
                ['configurazione_ruolini', DB::table('configurazione_ruolini')->count(), DB::table('configurazione_ruolini')->whereNotNull('organizational_unit_id')->count(), $this->calcPercentage('configurazione_ruolini', 'organizational_unit_id')],
                ['organizational_units', DB::table('organizational_units')->count(), '-', '-'],
            ]
        );
    }

    /**
     * Calcola la percentuale di record con un campo popolato
     */
    protected function calcPercentage(string $table, string $column): string
    {
        $total = DB::table($table)->count();
        if ($total === 0) return '0%';
        $populated = DB::table($table)->whereNotNull($column)->count();
        return round(($populated / $total) * 100, 1) . '%';
    }
}
