<?php

namespace App\Console\Commands;

use App\Models\OrganizationalUnit;
use App\Services\OrganizationalHierarchyService;
use Illuminate\Console\Command;

/**
 * Comando Artisan per copiare le configurazioni da un'unità template a una nuova.
 * 
 * Uso:
 *   php artisan unit:seed {unitId} --template-unit=7
 *   php artisan unit:seed {unitId} --include-cpt-codes
 */
class SeedNewOrganizationalUnit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unit:seed 
                            {unitId : ID dell\'unità da configurare}
                            {--template-unit= : ID dell\'unità template (default: Battaglione Leonessa o primo battaglione)}
                            {--include-cpt-codes : Copia anche i codici CPT dall\'unità template}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copia le configurazioni (campi anagrafica, ruolini, opzionalmente CPT) da un\'unità template a una nuova unità';

    /**
     * Execute the console command.
     */
    public function handle(OrganizationalHierarchyService $service): int
    {
        $unitId = (int) $this->argument('unitId');
        $templateUnitId = $this->option('template-unit');
        $includeCptCodes = $this->option('include-cpt-codes');

        // Verifica che l'unità esista
        $unit = OrganizationalUnit::find($unitId);
        if (!$unit) {
            $this->error("Unità con ID {$unitId} non trovata.");
            return Command::FAILURE;
        }

        // Ottieni l'unità template
        if ($templateUnitId) {
            $templateUnitId = (int) $templateUnitId;
            $templateUnit = OrganizationalUnit::find($templateUnitId);
            if (!$templateUnit) {
                $this->error("Unità template con ID {$templateUnitId} non trovata.");
                return Command::FAILURE;
            }
        } else {
            // Usa il template di default
            $templateUnitId = $service->getDefaultTemplateUnitId();
            if (!$templateUnitId) {
                $this->error("Nessuna unità template disponibile. Specifica --template-unit=ID.");
                return Command::FAILURE;
            }
            $templateUnit = OrganizationalUnit::find($templateUnitId);
        }

        $this->info("Copiando configurazioni da '{$templateUnit->name}' (ID: {$templateUnitId}) a '{$unit->name}' (ID: {$unitId})...");

        if ($includeCptCodes) {
            $this->warn("Include anche i codici CPT.");
        }

        try {
            $results = $service->seedConfigurationsFromTemplate($unitId, $templateUnitId, $includeCptCodes);

            $this->newLine();
            $this->info('✓ Operazione completata con successo!');
            $this->newLine();

            $this->table(
                ['Tipo Configurazione', 'Copiati', 'Saltati'],
                [
                    ['Campi Anagrafica', $results['campi_anagrafica'], $this->countSkipped($results['skipped'], 'campo_anagrafica')],
                    ['Ruolini', $results['ruolini'], $this->countSkipped($results['skipped'], 'ruolino')],
                    ['Codici CPT', $results['codici_cpt'], $this->countSkipped($results['skipped'], 'codice_cpt')],
                ]
            );

            if (!empty($results['skipped'])) {
                $this->warn("Elementi saltati (già esistenti): " . count($results['skipped']));
                if ($this->getOutput()->isVerbose()) {
                    foreach ($results['skipped'] as $skipped) {
                        $this->line("  - {$skipped}");
                    }
                }
            }

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            $this->error("Errore di validazione: " . $e->getMessage());
            return Command::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Errore durante il seed: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * Conta gli elementi saltati di un certo tipo.
     */
    private function countSkipped(array $skipped, string $prefix): int
    {
        return count(array_filter($skipped, fn($s) => str_starts_with($s, $prefix)));
    }
}
