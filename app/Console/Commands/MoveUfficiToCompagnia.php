<?php

namespace App\Console\Commands;

use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Services\OrganizationalHierarchyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MoveUfficiToCompagnia extends Command
{
    protected $signature = 'uffici:move-to-compagnia 
                            {--compagnia= : Nome della compagnia di destinazione (default: 124^ Compagnia)}
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire}
                            {--force : Esegui senza chiedere conferma}';

    protected $description = 'Sposta tutti gli uffici sotto una compagnia specifica';

    public function __construct(
        protected OrganizationalHierarchyService $hierarchyService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $compagniaNome = $this->option('compagnia') ?: '124^ Compagnia';
        $dryRun = $this->option('dry-run');

        $this->info("Ricerca compagnia di destinazione: {$compagniaNome}");

        // Trova la compagnia di destinazione
        $compagnia = OrganizationalUnit::where('name', 'like', "%{$compagniaNome}%")
            ->whereHas('type', fn($q) => $q->where('code', 'compagnia'))
            ->first();

        if (!$compagnia) {
            // Prova ricerca più ampia
            $compagnia = OrganizationalUnit::where('name', 'like', '%124%')
                ->whereHas('type', fn($q) => $q->where('code', 'compagnia'))
                ->first();
        }

        if (!$compagnia) {
            $this->error("Compagnia '{$compagniaNome}' non trovata!");
            
            // Mostra le compagnie disponibili
            $this->info("\nCompagnie disponibili:");
            OrganizationalUnit::whereHas('type', fn($q) => $q->where('code', 'compagnia'))
                ->get()
                ->each(fn($c) => $this->line("  - {$c->name} (ID: {$c->id})"));
            
            return Command::FAILURE;
        }

        $this->info("Compagnia trovata: {$compagnia->name} (ID: {$compagnia->id}, depth: {$compagnia->depth})");

        // Trova tutti gli uffici
        $uffici = OrganizationalUnit::whereHas('type', fn($q) => $q->where('code', 'ufficio'))
            ->where('parent_id', '!=', $compagnia->id) // Escludi quelli già sotto la compagnia
            ->get();

        if ($uffici->isEmpty()) {
            $this->info("Nessun ufficio da spostare.");
            return Command::SUCCESS;
        }

        $this->info("\nUffici da spostare ({$uffici->count()}):");
        foreach ($uffici as $ufficio) {
            $currentParent = $ufficio->parent?->name ?? 'ROOT';
            $this->line("  - {$ufficio->name} (attualmente sotto: {$currentParent}, depth: {$ufficio->depth})");
        }

        if ($dryRun) {
            $this->warn("\n[DRY-RUN] Nessuna modifica effettuata.");
            return Command::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm("\nVuoi procedere con lo spostamento di {$uffici->count()} uffici sotto '{$compagnia->name}'?")) {
            $this->info("Operazione annullata.");
            return Command::SUCCESS;
        }

        $this->info("\nSpostamento in corso...");
        $bar = $this->output->createProgressBar($uffici->count());
        $bar->start();

        $moved = 0;
        $errors = [];

        foreach ($uffici as $ufficio) {
            try {
                $this->hierarchyService->moveUnit($ufficio, $compagnia);
                $moved++;
            } catch (\Exception $e) {
                $errors[] = "{$ufficio->name}: {$e->getMessage()}";
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Spostati {$moved}/{$uffici->count()} uffici sotto '{$compagnia->name}'.");

        if (!empty($errors)) {
            $this->warn("\nErrori riscontrati:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        // Verifica finale
        $this->newLine();
        $this->info("Verifica struttura finale:");
        $compagnia->refresh();
        $figli = $compagnia->children()->with('type')->get();
        foreach ($figli as $figlio) {
            $tipo = $figlio->type?->code ?? 'N/A';
            $this->line("  - {$figlio->name} [{$tipo}] (depth: {$figlio->depth})");
        }

        return Command::SUCCESS;
    }
}
