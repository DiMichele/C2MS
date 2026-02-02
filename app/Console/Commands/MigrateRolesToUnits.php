<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateRolesToUnits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:roles-to-units 
                            {--dry-run : Esegui in modalità dry-run senza salvare le modifiche}
                            {--force : Forza la migrazione anche se organizational_unit_id è già popolato}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migra i ruoli esistenti da compagnia_id a organizational_unit_id';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== Migrazione Ruoli a Unità Organizzative ===');
        
        if ($dryRun) {
            $this->warn('Modalità DRY-RUN: nessuna modifica verrà salvata');
        }

        // Query per trovare ruoli da migrare
        $query = DB::table('roles')
            ->whereNotNull('compagnia_id');
        
        if (!$force) {
            $query->whereNull('organizational_unit_id');
        }

        $roles = $query->get();

        if ($roles->isEmpty()) {
            $this->info('Nessun ruolo da migrare.');
            return Command::SUCCESS;
        }

        $this->info("Trovati {$roles->count()} ruoli da migrare.");

        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->output->progressStart($roles->count());

        foreach ($roles as $role) {
            try {
                // Trova l'OrganizationalUnit corrispondente alla compagnia
                $unit = DB::table('organizational_units')
                    ->where('legacy_compagnia_id', $role->compagnia_id)
                    ->first();

                if (!$unit) {
                    $this->warn("\n  Ruolo '{$role->name}' (ID: {$role->id}): nessuna unità trovata per compagnia_id={$role->compagnia_id}");
                    $skippedCount++;
                    $this->output->progressAdvance();
                    continue;
                }

                if (!$dryRun) {
                    DB::table('roles')
                        ->where('id', $role->id)
                        ->update([
                            'organizational_unit_id' => $unit->id,
                            'updated_at' => now(),
                        ]);
                }

                $this->line("\n  Ruolo '{$role->name}' → Unità '{$unit->name}' (ID: {$unit->id})");
                $migratedCount++;

            } catch (\Exception $e) {
                $this->error("\n  Errore per ruolo '{$role->name}': {$e->getMessage()}");
                $errorCount++;
                Log::error("MigrateRolesToUnits: Errore per ruolo {$role->id}", [
                    'error' => $e->getMessage(),
                    'role' => $role,
                ]);
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        // Riepilogo
        $this->newLine(2);
        $this->info('=== Riepilogo ===');
        $this->table(
            ['Stato', 'Quantità'],
            [
                ['Migrati', $migratedCount],
                ['Saltati (nessuna unità)', $skippedCount],
                ['Errori', $errorCount],
            ]
        );

        // Mostra ruoli globali (senza unità)
        $globalRoles = DB::table('roles')
            ->where('is_global', true)
            ->orWhereNull('compagnia_id')
            ->get();

        if ($globalRoles->isNotEmpty()) {
            $this->newLine();
            $this->info("Ruoli globali (non associati a unità):");
            foreach ($globalRoles as $role) {
                $this->line("  - {$role->name} ({$role->display_name})");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('Modalità DRY-RUN completata. Esegui senza --dry-run per applicare le modifiche.');
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
