<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\TipoServizio;
use App\Models\CodiciServizioGerarchia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportCodiciNos extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:codici-nos {file=CPT.xlsx}';

    /**
     * The console command description.
     */
    protected $description = 'Importa i dati CODICI e NOS dal file CPT.xlsx';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File {$filePath} non trovato!");
            return 1;
        }

        $this->info("Importazione dati CODICI e NOS da {$filePath}...");

        try {
            DB::beginTransaction();

            // Carica il file Excel
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($filePath);
            
            // Importa i gradi mancanti
            $this->importaGradiMancanti();
            
            // Aggiorna i codici servizio con la gerarchia
            $this->aggiornaGerarchiaCodici($spreadsheet);
            
            // Importa i dati NOS
            $this->importaDatiNos($spreadsheet);
            
            DB::commit();
            
            $this->info("✅ Importazione CODICI e NOS completata con successo!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Errore durante l'importazione: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Importa i gradi mancanti trovati nei fogli NOS
     */
    private function importaGradiMancanti()
    {
        $this->info("Importazione gradi mancanti...");
        
        $gradiMancanti = [
            ['nome' => 'Mar. Ord.', 'ordine' => 20],
            ['nome' => 'Serg. Magg. A.', 'ordine' => 55],
            ['nome' => 'Serg. Magg. Ca.', 'ordine' => 52],
            ['nome' => 'Serg. Magg.', 'ordine' => 50],
            ['nome' => 'Grd. A.', 'ordine' => 40],
            ['nome' => '1° Grd.', 'ordine' => 35],
            ['nome' => 'Grd. Ca.', 'ordine' => 32],
            ['nome' => 'Grd. Sc.', 'ordine' => 30],
            ['nome' => 'C.le Magg.', 'ordine' => 25],
            ['nome' => 'Sol.', 'ordine' => 15]
        ];
        
        $importati = 0;
        foreach ($gradiMancanti as $gradoData) {
            $esistente = Grado::where('nome', $gradoData['nome'])->first();
            if (!$esistente) {
                Grado::create($gradoData);
                $importati++;
                $this->info("✓ Grado creato: {$gradoData['nome']}");
            }
        }
        
        $this->info("✓ Gradi mancanti importati: {$importati}");
    }

    /**
     * Aggiorna i codici servizio esistenti con la gerarchia
     */
    private function aggiornaGerarchiaCodici($spreadsheet)
    {
        $this->info("Aggiornamento gerarchia codici servizio...");
        
        // Collega i tipi servizio esistenti con la gerarchia
        $tipiServizio = TipoServizio::all();
        $aggiornati = 0;
        
        foreach ($tipiServizio as $tipo) {
            $gerarchia = CodiciServizioGerarchia::where('codice', $tipo->codice)->first();
            if ($gerarchia && !$tipo->codice_gerarchia_id) {
                $tipo->codice_gerarchia_id = $gerarchia->id;
                $tipo->save();
                $aggiornati++;
            }
        }
        
        $this->info("✓ Codici servizio aggiornati con gerarchia: {$aggiornati}");
    }

    /**
     * Importa i dati NOS dalle pagine NOS del file Excel
     */
    private function importaDatiNos($spreadsheet)
    {
        $this->info("Importazione dati NOS...");
        
        $nosSheets = ['NOS 124^ CP', 'NOS 110^ CP'];
        $totaleAggiornati = 0;
        
        foreach ($nosSheets as $sheetName) {
            $this->info("Processando {$sheetName}...");
            
            $nosSheet = $spreadsheet->getSheetByName($sheetName);
            if (!$nosSheet) {
                $this->warn("Foglio {$sheetName} non trovato!");
                continue;
            }
            
            $aggiornati = $this->processaNosSheet($nosSheet, $sheetName);
            $totaleAggiornati += $aggiornati;
            
            $this->info("✓ Militari aggiornati in {$sheetName}: {$aggiornati}");
        }
        
        $this->info("✓ Totale militari aggiornati con dati NOS: {$totaleAggiornati}");
    }

    /**
     * Processa un singolo foglio NOS
     */
    private function processaNosSheet($nosSheet, $sheetName)
    {
        $highestRow = $nosSheet->getHighestRow();
        $aggiornati = 0;
        
        // Trova la riga di intestazione
        $headerRow = 2; // Di solito è la riga 2
        
        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $grado = trim($nosSheet->getCell('A' . $row)->getValue());
            $cognome = trim($nosSheet->getCell('B' . $row)->getValue());
            $nome = trim($nosSheet->getCell('C' . $row)->getValue());
            $nosStatus = trim($nosSheet->getCell('D' . $row)->getValue());
            $note = trim($nosSheet->getCell('E' . $row)->getValue());
            
            // Per NOS 110^ CP la struttura è diversa
            if ($sheetName === 'NOS 110^ CP') {
                $nomeCompleto = $cognome; // In 110^ CP il nome completo è nella colonna B
                $nosStatus = $note; // Il NOS status è nella colonna E
                $scadenza = $nosStatus; // La colonna D contiene la data di scadenza
                $note = ''; // Reset note
                
                // Parsing del nome completo
                $partialNome = $this->parseNomeCompleto($nomeCompleto);
                if ($partialNome) {
                    $cognome = $partialNome['cognome'];
                    $nome = $partialNome['nome'];
                }
            }
            
            if (empty($grado) || empty($cognome)) {
                continue;
            }
            
            // Trova il militare corrispondente
            $militare = $this->trovaMilitare($grado, $cognome, $nome);
            
            if ($militare) {
                // Aggiorna i dati NOS
                $nosStatusNormalizzato = $this->normalizzaNosStatus($nosStatus);
                $compagniaNos = $this->estraiCompagnia($sheetName);
                
                $militare->update([
                    'nos_status' => $nosStatusNormalizzato,
                    'compagnia_nos' => $compagniaNos,
                    'nos_note' => $note ?: null
                ]);
                
                // Se c'è una data di scadenza (solo per 110^ CP)
                if ($sheetName === 'NOS 110^ CP' && isset($scadenza) && is_numeric($scadenza)) {
                    try {
                        $dataScadenza = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($scadenza - 2);
                        $militare->nos_scadenza = $dataScadenza;
                        $militare->save();
                    } catch (\Exception $e) {
                        // Ignora errori di parsing data
                    }
                }
                
                $aggiornati++;
            } else {
                $this->warn("Militare non trovato: {$grado} {$cognome} {$nome}");
            }
        }
        
        return $aggiornati;
    }

    /**
     * Trova un militare nel database
     */
    private function trovaMilitare($grado, $cognome, $nome)
    {
        // Prima prova con grado esatto
        $gradoObj = Grado::where('nome', $grado)->first();
        if ($gradoObj) {
            $militare = Militare::where('grado_id', $gradoObj->id)
                               ->where('cognome', 'LIKE', "%{$cognome}%")
                               ->first();
            if ($militare) return $militare;
        }
        
        // Prova senza grado, solo nome e cognome
        $militare = Militare::where('cognome', 'LIKE', "%{$cognome}%")
                           ->where('nome', 'LIKE', "%{$nome}%")
                           ->first();
        
        return $militare;
    }

    /**
     * Parsing del nome completo
     */
    private function parseNomeCompleto($nomeCompleto)
    {
        if (empty($nomeCompleto)) {
            return null;
        }
        
        $parti = explode(' ', trim($nomeCompleto));
        
        if (count($parti) < 2) {
            return null;
        }
        
        $cognome = array_shift($parti);
        $nome = implode(' ', $parti);
        
        return [
            'cognome' => ucfirst(strtolower($cognome)),
            'nome' => ucwords(strtolower($nome))
        ];
    }

    /**
     * Normalizza lo status NOS
     */
    private function normalizzaNosStatus($status)
    {
        $status = strtoupper(trim($status));
        
        return match($status) {
            'SI' => 'SI',
            'NO' => 'NO',
            'IN ATTESA' => 'IN_ATTESA',
            'DA RICHIEDERE' => 'DA_RICHIEDERE',
            'NON PREVISTO' => 'NON_PREVISTO',
            default => 'NO'
        };
    }

    /**
     * Estrae la compagnia dal nome del foglio
     */
    private function estraiCompagnia($sheetName)
    {
        if (strpos($sheetName, '124') !== false) {
            return '124^ CP';
        } elseif (strpos($sheetName, '110') !== false) {
            return '110^ CP';
        }
        
        return null;
    }
}
