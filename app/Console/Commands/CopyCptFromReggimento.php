<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CodiciServizioGerarchia;
use App\Models\TipoServizio;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use Illuminate\Support\Facades\DB;

/**
 * Comando per copiare i codici CPT dal reggimento ai battaglioni (macro entità).
 * 
 * Questo comando esegue una copia una tantum di tutti i codici CPT esistenti
 * nel reggimento a tutte le unità di tipo "battaglione".
 */
class CopyCptFromReggimento extends Command
{
    /**
     * Nome e firma del comando console.
     *
     * @var string
     */
    protected $signature = 'cpt:copy-from-reggimento 
                            {--dry-run : Mostra cosa verrebbe copiato senza effettuare modifiche}
                            {--skip-existing : Salta i codici che esistono già nel battaglione}';

    /**
     * Descrizione del comando console.
     *
     * @var string
     */
    protected $description = 'Copia tutti i codici CPT dal reggimento a tutte le macro entità (battaglioni)';

    /**
     * Esegue il comando console.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');

        $this->info('=== Copia Codici CPT dal Reggimento ai Battaglioni ===');
        $this->newLine();

        // 1. Trova il reggimento (unità root o di tipo reggimento)
        $reggimento = $this->findReggimento();

        if (!$reggimento) {
            $this->error('Impossibile trovare il reggimento nella gerarchia organizzativa.');
            return Command::FAILURE;
        }

        $this->info("Reggimento trovato: {$reggimento->name} (ID: {$reggimento->id})");

        // 2. Recupera i codici CPT del reggimento
        $codiciReggimento = CodiciServizioGerarchia::withoutGlobalScopes()
            ->where('organizational_unit_id', $reggimento->id)
            ->get();

        if ($codiciReggimento->isEmpty()) {
            $this->warn('Nessun codice CPT trovato nel reggimento.');
            return Command::SUCCESS;
        }

        $this->info("Codici CPT nel reggimento: {$codiciReggimento->count()}");
        $this->newLine();

        // 3. Trova tutti i battaglioni
        $battaglioni = $this->findBattaglioni();

        if ($battaglioni->isEmpty()) {
            $this->warn('Nessun battaglione trovato nella gerarchia organizzativa.');
            return Command::SUCCESS;
        }

        $this->info("Battaglioni trovati: {$battaglioni->count()}");
        foreach ($battaglioni as $battaglione) {
            $this->line("  - {$battaglione->name} (ID: {$battaglione->id})");
        }
        $this->newLine();

        if ($dryRun) {
            $this->warn('=== MODALITÀ DRY-RUN: Nessuna modifica verrà effettuata ===');
            $this->newLine();
        }

        // 4. Copia i codici a ogni battaglione
        $totaleCopiati = 0;
        $totaleSaltati = 0;

        DB::beginTransaction();

        try {
            foreach ($battaglioni as $battaglione) {
                $this->info("Elaborazione: {$battaglione->name}");

                $copiati = 0;
                $saltati = 0;

                foreach ($codiciReggimento as $codice) {
                    // Verifica se il codice esiste già nel battaglione
                    $esistente = CodiciServizioGerarchia::withoutGlobalScopes()
                        ->where('organizational_unit_id', $battaglione->id)
                        ->where('codice', $codice->codice)
                        ->exists();

                    if ($esistente) {
                        if ($skipExisting) {
                            $saltati++;
                            continue;
                        }
                        
                        // Aggiorna il codice esistente
                        if (!$dryRun) {
                            CodiciServizioGerarchia::withoutGlobalScopes()
                                ->where('organizational_unit_id', $battaglione->id)
                                ->where('codice', $codice->codice)
                                ->update([
                                    'macro_attivita' => $codice->macro_attivita,
                                    'tipo_attivita' => $codice->tipo_attivita,
                                    'attivita_specifica' => $codice->attivita_specifica,
                                    'impiego' => $codice->impiego,
                                    'descrizione_impiego' => $codice->descrizione_impiego,
                                    'colore_badge' => $codice->colore_badge,
                                    'attivo' => $codice->attivo,
                                    'ordine' => $codice->ordine,
                                    'esenzione_alzabandiera' => $codice->esenzione_alzabandiera,
                                    'disponibilita_limitata' => $codice->disponibilita_limitata,
                                    'conta_come_presente' => $codice->conta_come_presente,
                                ]);
                        }
                        $copiati++;
                    } else {
                        // Crea nuovo codice
                        if (!$dryRun) {
                            CodiciServizioGerarchia::create([
                                'organizational_unit_id' => $battaglione->id,
                                'codice' => $codice->codice,
                                'macro_attivita' => $codice->macro_attivita,
                                'tipo_attivita' => $codice->tipo_attivita,
                                'attivita_specifica' => $codice->attivita_specifica,
                                'impiego' => $codice->impiego,
                                'descrizione_impiego' => $codice->descrizione_impiego,
                                'colore_badge' => $codice->colore_badge,
                                'attivo' => $codice->attivo,
                                'ordine' => $codice->ordine,
                                'esenzione_alzabandiera' => $codice->esenzione_alzabandiera,
                                'disponibilita_limitata' => $codice->disponibilita_limitata,
                                'conta_come_presente' => $codice->conta_come_presente,
                            ]);
                        }
                        $copiati++;
                    }
                }

                $this->line("  Copiati/Aggiornati: {$copiati}, Saltati: {$saltati}");
                $totaleCopiati += $copiati;
                $totaleSaltati += $saltati;
            }

            // 5. Sincronizza anche tipi_servizio
            $this->newLine();
            $this->info("=== Sincronizzazione Tipi Servizio ===");
            
            $tipiServizioSource = TipoServizio::withoutGlobalScopes()
                ->where('organizational_unit_id', $reggimento->id)
                ->get();
            
            $this->info("Tipi servizio nel reggimento: {$tipiServizioSource->count()}");
            
            $totaleTs = 0;
            foreach ($battaglioni as $battaglione) {
                $copied = 0;
                foreach ($tipiServizioSource as $tipo) {
                    $exists = TipoServizio::withoutGlobalScopes()
                        ->where('organizational_unit_id', $battaglione->id)
                        ->where('codice', $tipo->codice)
                        ->exists();
                    
                    if ($exists) {
                        if (!$dryRun) {
                            TipoServizio::withoutGlobalScopes()
                                ->where('organizational_unit_id', $battaglione->id)
                                ->where('codice', $tipo->codice)
                                ->update([
                                    'nome' => $tipo->nome,
                                    'descrizione' => $tipo->descrizione,
                                    'colore_badge' => $tipo->colore_badge,
                                    'categoria' => $tipo->categoria,
                                    'attivo' => $tipo->attivo,
                                    'ordine' => $tipo->ordine,
                                ]);
                        }
                    } else {
                        if (!$dryRun) {
                            TipoServizio::withoutGlobalScopes()->create([
                                'organizational_unit_id' => $battaglione->id,
                                'codice' => $tipo->codice,
                                'nome' => $tipo->nome,
                                'descrizione' => $tipo->descrizione,
                                'colore_badge' => $tipo->colore_badge,
                                'categoria' => $tipo->categoria,
                                'attivo' => $tipo->attivo,
                                'ordine' => $tipo->ordine,
                            ]);
                        }
                    }
                    $copied++;
                }
                $this->line("  {$battaglione->name}: {$copied} tipi servizio");
                $totaleTs += $copied;
            }

            if (!$dryRun) {
                DB::commit();
                $this->newLine();
                $this->info("=== Operazione completata ===");
                $this->info("Totale codici copiati/aggiornati: {$totaleCopiati}");
                $this->info("Totale codici saltati: {$totaleSaltati}");
                $this->info("Totale tipi servizio sincronizzati: {$totaleTs}");
            } else {
                DB::rollBack();
                $this->newLine();
                $this->warn("=== Dry-run completato ===");
                $this->info("Codici che verrebbero copiati/aggiornati: {$totaleCopiati}");
                $this->info("Codici che verrebbero saltati: {$totaleSaltati}");
                $this->info("Tipi servizio che verrebbero sincronizzati: {$totaleTs}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Errore durante la copia: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Trova l'unità sorgente dei codici CPT.
     * Cerca prima nel reggimento, poi nel "Comando di Reggimento" (sezione legacy),
     * poi in qualsiasi unità non-battaglione che contenga codici.
     */
    private function findReggimento(): ?OrganizationalUnit
    {
        // Prima prova a trovare per tipo "reggimento"
        $tipoReggimento = OrganizationalUnitType::reggimento();

        if ($tipoReggimento) {
            $reggimento = OrganizationalUnit::where('type_id', $tipoReggimento->id)
                ->active()
                ->first();

            // Verifica se ha codici CPT
            if ($reggimento) {
                $hasCodici = CodiciServizioGerarchia::withoutGlobalScopes()
                    ->where('organizational_unit_id', $reggimento->id)
                    ->exists();
                    
                if ($hasCodici) {
                    return $reggimento;
                }
            }
        }

        // Cerca "Comando di Reggimento" o unità simili con codici legacy
        $comandoReggimento = OrganizationalUnit::where('name', 'like', '%Comando%Reggimento%')
            ->active()
            ->first();
            
        if ($comandoReggimento) {
            $hasCodici = CodiciServizioGerarchia::withoutGlobalScopes()
                ->where('organizational_unit_id', $comandoReggimento->id)
                ->exists();
                
            if ($hasCodici) {
                return $comandoReggimento;
            }
        }

        // Fallback: trova qualsiasi unità non-battaglione con il maggior numero di codici
        $tipoBattaglione = OrganizationalUnitType::battaglione();
        $battaglioneIds = $tipoBattaglione 
            ? OrganizationalUnit::where('type_id', $tipoBattaglione->id)->pluck('id')->toArray()
            : [];
            
        $unitWithMostCodes = CodiciServizioGerarchia::withoutGlobalScopes()
            ->selectRaw('organizational_unit_id, count(*) as cnt')
            ->whereNotIn('organizational_unit_id', $battaglioneIds)
            ->groupBy('organizational_unit_id')
            ->orderByDesc('cnt')
            ->first();
            
        if ($unitWithMostCodes) {
            return OrganizationalUnit::find($unitWithMostCodes->organizational_unit_id);
        }

        // Ultimo fallback: trova la root della gerarchia
        return OrganizationalUnit::roots()
            ->active()
            ->first();
    }

    /**
     * Trova tutte le unità di tipo battaglione.
     */
    private function findBattaglioni()
    {
        $tipoBattaglione = OrganizationalUnitType::battaglione();

        if (!$tipoBattaglione) {
            return collect();
        }

        return OrganizationalUnit::where('type_id', $tipoBattaglione->id)
            ->active()
            ->orderBy('name')
            ->get();
    }
}
