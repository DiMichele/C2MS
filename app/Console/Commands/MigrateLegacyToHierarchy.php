<?php

namespace App\Console\Commands;

use App\Models\BoardActivity;
use App\Models\Compagnia;
use App\Models\ConfigurazioneRuolino;
use App\Models\Militare;
use App\Models\OrganizationalUnit;
use App\Models\ServizioTurno;
use App\Models\AssegnazioneTurno;
use App\Models\PianificazioneGiornaliera;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Comando per migrare i dati legacy (basati su compagnia_id) alla nuova
 * struttura gerarchica organizzativa (organizational_unit_id).
 * 
 * Questo comando:
 * 1. Trova le unità organizzative corrispondenti alle compagnie legacy
 * 2. Aggiorna i record esistenti con il nuovo organizational_unit_id
 * 3. Genera report sulla migrazione
 */
class MigrateLegacyToHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sugeco:migrate-hierarchy 
                            {--dry-run : Simula la migrazione senza modificare i dati}
                            {--force : Forza la migrazione anche se ci sono warning}
                            {--only= : Migra solo un modello specifico (militari, board, turni, ruolini)}';

    /**
     * The console command description.
     */
    protected $description = 'Migra i dati legacy (compagnia_id) alla nuova gerarchia organizzativa';

    /**
     * Mappa delle compagnie agli ID delle unità organizzative.
     */
    protected array $compagniaToUnitMap = [];

    /**
     * Statistiche della migrazione.
     */
    protected array $stats = [
        'users' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'militari' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'board_activities' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'servizi_turno' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'assegnazioni_turno' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'configurazione_ruolini' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
        'pianificazioni_giornaliere' => ['success' => 0, 'failed' => 0, 'skipped' => 0],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $only = $this->option('only');

        $this->info('=== SUGECO Migrazione Gerarchia Organizzativa ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('MODALITÀ DRY-RUN: Nessuna modifica sarà applicata');
            $this->newLine();
        }

        // 1. Costruisci la mappa compagnia -> unità
        if (!$this->buildCompagniaToUnitMap()) {
            $this->error('Impossibile costruire la mappa compagnia -> unità organizzativa');
            return Command::FAILURE;
        }

        // 2. Mostra la mappa e chiedi conferma
        $this->displayCompagniaMapping();

        if (!$dryRun && !$force) {
            if (!$this->confirm('Vuoi procedere con la migrazione?')) {
                $this->info('Migrazione annullata.');
                return Command::SUCCESS;
            }
        }

        // 3. Esegui le migrazioni
        DB::beginTransaction();

        try {
            if (!$only || $only === 'users') {
                $this->migrateUsers($dryRun);
            }
            
            if (!$only || $only === 'militari') {
                $this->migrateMilitari($dryRun);
            }

            if (!$only || $only === 'board') {
                $this->migrateBoardActivities($dryRun);
            }

            if (!$only || $only === 'turni') {
                $this->migrateServiziTurno($dryRun);
                $this->migrateAssegnazioniTurno($dryRun);
            }

            if (!$only || $only === 'ruolini') {
                $this->migrateConfigurazioneRuolini($dryRun);
            }

            $this->migratePianificazioniGiornaliere($dryRun);

            if (!$dryRun) {
                DB::commit();
                $this->info('Migrazione completata con successo!');
            } else {
                DB::rollBack();
                $this->info('Simulazione completata (nessuna modifica applicata)');
            }

            // 4. Mostra report finale
            $this->displayMigrationReport();

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Errore durante la migrazione: ' . $e->getMessage());
            Log::error('Migrazione gerarchia fallita', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }

    /**
     * Costruisce la mappa compagnia_id -> organizational_unit_id.
     */
    protected function buildCompagniaToUnitMap(): bool
    {
        $this->info('Costruzione mappa compagnia -> unità...');

        // Cerca unità con legacy_compagnia_id impostato
        $unitsWithLegacy = OrganizationalUnit::whereNotNull('legacy_compagnia_id')
            ->get()
            ->keyBy('legacy_compagnia_id');

        foreach ($unitsWithLegacy as $compagniaId => $unit) {
            $this->compagniaToUnitMap[$compagniaId] = $unit->id;
        }

        // Se non ci sono mapping, prova a mappare per nome
        if (empty($this->compagniaToUnitMap)) {
            $this->warn('Nessun mapping legacy trovato. Tentativo di mappatura per nome...');
            
            $compagnie = Compagnia::all();
            foreach ($compagnie as $compagnia) {
                $unit = OrganizationalUnit::where('name', 'LIKE', '%' . $compagnia->nome . '%')
                    ->orWhere('code', $compagnia->sigla ?? '')
                    ->first();

                if ($unit) {
                    $this->compagniaToUnitMap[$compagnia->id] = $unit->id;
                }
            }
        }

        return !empty($this->compagniaToUnitMap);
    }

    /**
     * Mostra la mappatura compagnie -> unità.
     */
    protected function displayCompagniaMapping(): void
    {
        $this->info('Mappatura Compagnie -> Unità Organizzative:');
        $this->newLine();

        $rows = [];
        foreach ($this->compagniaToUnitMap as $compagniaId => $unitId) {
            $compagnia = Compagnia::find($compagniaId);
            $unit = OrganizationalUnit::find($unitId);

            $rows[] = [
                $compagniaId,
                $compagnia?->nome ?? 'N/D',
                $unitId,
                $unit?->name ?? 'N/D',
            ];
        }

        $this->table(['Compagnia ID', 'Nome Compagnia', 'Unità ID', 'Nome Unità'], $rows);
        $this->newLine();
    }

    /**
     * Migra gli utenti.
     */
    protected function migrateUsers(bool $dryRun): void
    {
        $this->info('Migrazione Users...');

        $users = User::whereNull('organizational_unit_id')
            ->whereNotNull('compagnia_id')
            ->get();

        $bar = $this->output->createProgressBar($users->count());

        foreach ($users as $user) {
            $unitId = $this->compagniaToUnitMap[$user->compagnia_id] ?? null;

            if (!$unitId) {
                $this->stats['users']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $user->organizational_unit_id = $unitId;
                    $user->saveQuietly();
                }
                $this->stats['users']['success']++;
            } catch (\Exception $e) {
                $this->stats['users']['failed']++;
                Log::warning('Migrazione user fallita', ['id' => $user->id, 'error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra i militari.
     */
    protected function migrateMilitari(bool $dryRun): void
    {
        $this->info('Migrazione Militari...');

        $militari = Militare::withoutGlobalScopes()
            ->whereNull('organizational_unit_id')
            ->whereNotNull('compagnia_id')
            ->get();

        $bar = $this->output->createProgressBar($militari->count());

        foreach ($militari as $militare) {
            $unitId = $this->compagniaToUnitMap[$militare->compagnia_id] ?? null;

            if (!$unitId) {
                $this->stats['militari']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $militare->organizational_unit_id = $unitId;
                    $militare->saveQuietly();
                }
                $this->stats['militari']['success']++;
            } catch (\Exception $e) {
                $this->stats['militari']['failed']++;
                Log::warning('Migrazione militare fallita', ['id' => $militare->id, 'error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra le attività board.
     */
    protected function migrateBoardActivities(bool $dryRun): void
    {
        $this->info('Migrazione Board Activities...');

        $activities = BoardActivity::withoutGlobalScopes()
            ->whereNull('organizational_unit_id')
            ->whereNotNull('compagnia_id')
            ->get();

        $bar = $this->output->createProgressBar($activities->count());

        foreach ($activities as $activity) {
            $unitId = $this->compagniaToUnitMap[$activity->compagnia_id] ?? null;

            if (!$unitId) {
                $this->stats['board_activities']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $activity->organizational_unit_id = $unitId;
                    $activity->saveQuietly();
                }
                $this->stats['board_activities']['success']++;
            } catch (\Exception $e) {
                $this->stats['board_activities']['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra i servizi turno.
     */
    protected function migrateServiziTurno(bool $dryRun): void
    {
        $this->info('Migrazione Servizi Turno...');

        // I servizi turno potrebbero non avere compagnia_id diretto
        // Li associamo all'unità attiva o alla prima unità disponibile
        $servizi = ServizioTurno::whereNull('organizational_unit_id')->get();

        $bar = $this->output->createProgressBar($servizi->count());

        foreach ($servizi as $servizio) {
            // Usa la prima unità dalla mappa
            $unitId = reset($this->compagniaToUnitMap) ?: null;

            if (!$unitId) {
                $this->stats['servizi_turno']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $servizio->organizational_unit_id = $unitId;
                    $servizio->saveQuietly();
                }
                $this->stats['servizi_turno']['success']++;
            } catch (\Exception $e) {
                $this->stats['servizi_turno']['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra le assegnazioni turno.
     */
    protected function migrateAssegnazioniTurno(bool $dryRun): void
    {
        $this->info('Migrazione Assegnazioni Turno...');

        $assegnazioni = AssegnazioneTurno::whereNull('organizational_unit_id')
            ->with('militare')
            ->get();

        $bar = $this->output->createProgressBar($assegnazioni->count());

        foreach ($assegnazioni as $assegnazione) {
            // Usa l'unità del militare assegnato
            $unitId = $assegnazione->militare?->organizational_unit_id
                ?? $this->compagniaToUnitMap[$assegnazione->militare?->compagnia_id ?? 0] ?? null;

            if (!$unitId) {
                $this->stats['assegnazioni_turno']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $assegnazione->organizational_unit_id = $unitId;
                    $assegnazione->saveQuietly();
                }
                $this->stats['assegnazioni_turno']['success']++;
            } catch (\Exception $e) {
                $this->stats['assegnazioni_turno']['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra le configurazioni ruolini.
     */
    protected function migrateConfigurazioneRuolini(bool $dryRun): void
    {
        $this->info('Migrazione Configurazione Ruolini...');

        $configurazioni = ConfigurazioneRuolino::withoutGlobalScopes()
            ->whereNull('organizational_unit_id')
            ->whereNotNull('compagnia_id')
            ->get();

        $bar = $this->output->createProgressBar($configurazioni->count());

        foreach ($configurazioni as $config) {
            $unitId = $this->compagniaToUnitMap[$config->compagnia_id] ?? null;

            if (!$unitId) {
                $this->stats['configurazione_ruolini']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $config->organizational_unit_id = $unitId;
                    $config->saveQuietly();
                }
                $this->stats['configurazione_ruolini']['success']++;
            } catch (\Exception $e) {
                $this->stats['configurazione_ruolini']['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Migra le pianificazioni giornaliere.
     */
    protected function migratePianificazioniGiornaliere(bool $dryRun): void
    {
        $this->info('Migrazione Pianificazioni Giornaliere...');

        $pianificazioni = PianificazioneGiornaliera::whereNull('organizational_unit_id')
            ->with('militare')
            ->get();

        $bar = $this->output->createProgressBar($pianificazioni->count());

        foreach ($pianificazioni as $pian) {
            // Usa l'unità del militare
            $unitId = $pian->militare?->organizational_unit_id
                ?? $this->compagniaToUnitMap[$pian->militare?->compagnia_id ?? 0] ?? null;

            if (!$unitId) {
                $this->stats['pianificazioni_giornaliere']['skipped']++;
                $bar->advance();
                continue;
            }

            try {
                if (!$dryRun) {
                    $pian->organizational_unit_id = $unitId;
                    $pian->saveQuietly();
                }
                $this->stats['pianificazioni_giornaliere']['success']++;
            } catch (\Exception $e) {
                $this->stats['pianificazioni_giornaliere']['failed']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    /**
     * Mostra il report finale della migrazione.
     */
    protected function displayMigrationReport(): void
    {
        $this->newLine();
        $this->info('=== Report Migrazione ===');
        $this->newLine();

        $rows = [];
        foreach ($this->stats as $model => $counts) {
            $rows[] = [
                str_replace('_', ' ', ucfirst($model)),
                $counts['success'],
                $counts['failed'],
                $counts['skipped'],
            ];
        }

        $this->table(['Modello', 'Migrati', 'Falliti', 'Saltati'], $rows);
    }
}
