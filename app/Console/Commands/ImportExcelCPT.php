<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Approntamento;
use App\Models\TipoServizio;
use App\Models\PatenteMilitare;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportExcelCPT extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'import:cpt-excel {file=CPT.xlsx}';

    /**
     * The console command description.
     */
    protected $description = 'Importa i dati dal file CPT.xlsx nel database';

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

        $this->info("Importazione dati da {$filePath}...");

        try {
            DB::beginTransaction();

            // Carica il file Excel
            $reader = IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $worksheet->getHighestRow();
            $highestColumn = $worksheet->getHighestColumn();
            
            $this->info("Dimensioni foglio: {$highestRow} righe x {$highestColumn} colonne");

            // Crea la pianificazione mensile per Settembre 2025
            $pianificazione = $this->creaPianificazioneMensile();
            
            // Importa i militari e le loro assegnazioni
            $this->importaMilitari($worksheet, $highestRow, $pianificazione);
            
            DB::commit();
            
            $this->info("✅ Importazione completata con successo!");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Errore durante l'importazione: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Crea la pianificazione mensile per Settembre 2025
     */
    private function creaPianificazioneMensile()
    {
        $this->info("Creazione pianificazione mensile Settembre 2025...");
        
        $pianificazione = PianificazioneMensile::updateOrCreate(
            ['anno' => 2025, 'mese' => 9],
            [
                'nome' => 'Settembre 2025',
                'attiva' => true,
                'data_creazione' => Carbon::today()
            ]
        );
        
        $this->info("✓ Pianificazione creata: {$pianificazione->nome}");
        
        return $pianificazione;
    }

    /**
     * Importa i militari dal foglio Excel
     */
    private function importaMilitari($worksheet, $highestRow, $pianificazione)
    {
        $this->info("Importazione militari...");
        
        $importati = 0;
        $aggiornati = 0;
        
        $progressBar = $this->output->createProgressBar($highestRow - 2);
        $progressBar->start();
        
        for ($row = 3; $row <= $highestRow; $row++) {
            $numeroMatricola = $worksheet->getCell('A' . $row)->getValue();
            $patenti = $worksheet->getCell('B' . $row)->getValue();
            $categoria = $worksheet->getCell('C' . $row)->getValue();
            $gradoNome = $worksheet->getCell('D' . $row)->getValue();
            $nomeCompleto = $worksheet->getCell('E' . $row)->getValue();
            $approntamentoNome = $worksheet->getCell('F' . $row)->getValue();
            
            // Salta righe vuote
            if (empty($nomeCompleto) || empty($numeroMatricola)) {
                $progressBar->advance();
                continue;
            }
            
            // Parsing nome e cognome
            $nomeParti = $this->parseNomeCompleto($nomeCompleto);
            if (!$nomeParti) {
                $this->warn("Impossibile parsare nome: {$nomeCompleto}");
                $progressBar->advance();
                continue;
            }
            
            // Trova o crea il grado
            $grado = $this->findOrCreateGrado($gradoNome);
            
            // Trova o crea l'approntamento
            $approntamento = $this->findOrCreateApprontamento($approntamentoNome);
            
            // Crea o aggiorna il militare
            $militare = Militare::updateOrCreate(
                ['numero_matricola' => $numeroMatricola],
                [
                    'cognome' => $nomeParti['cognome'],
                    'nome' => $nomeParti['nome'],
                    'grado_id' => $grado?->id,
                    'categoria' => $this->normalizzaCategoria($categoria),
                    'approntamento_principale_id' => $approntamento?->id
                ]
            );
            
            if ($militare->wasRecentlyCreated) {
                $importati++;
            } else {
                $aggiornati++;
            }
            
            // Importa le patenti
            $this->importaPatenti($militare, $patenti);
            
            // Importa le assegnazioni giornaliere
            $this->importaAssegnazioniGiornaliere($worksheet, $row, $militare, $pianificazione);
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info("✓ Militari importati: {$importati}");
        $this->info("✓ Militari aggiornati: {$aggiornati}");
    }

    /**
     * Parsing del nome completo
     */
    private function parseNomeCompleto($nomeCompleto)
    {
        if (empty($nomeCompleto)) {
            return null;
        }
        
        // Formato: "COGNOME Nome1 Nome2"
        $parti = explode(' ', trim($nomeCompleto));
        
        if (count($parti) < 2) {
            return null;
        }
        
        $cognome = array_shift($parti); // Primo elemento è il cognome
        $nome = implode(' ', $parti);   // Il resto è il nome
        
        return [
            'cognome' => ucfirst(strtolower($cognome)),
            'nome' => ucwords(strtolower($nome))
        ];
    }

    /**
     * Trova o crea un grado
     */
    private function findOrCreateGrado($gradoNome)
    {
        if (empty($gradoNome)) {
            return null;
        }
        
        $gradoNome = trim($gradoNome);
        
        // Cerca prima per nome esatto
        $grado = Grado::where('nome', $gradoNome)->first();
        
        if (!$grado) {
            // Crea nuovo grado con ordine basato su logica militare
            $ordine = $this->getOrdineGrado($gradoNome);
            
            $grado = Grado::create([
                'nome' => $gradoNome,
                'ordine' => $ordine
            ]);
            
            $this->info("Nuovo grado creato: {$gradoNome} (ordine: {$ordine})");
        }
        
        return $grado;
    }

    /**
     * Ottiene l'ordine del grado basato sul nome
     */
    private function getOrdineGrado($gradoNome)
    {
        $ordini = [
            'Generale' => 100,
            'Col.' => 90, 'Colonnello' => 90,
            'Ten. Col.' => 85, 'Tenente Colonnello' => 85,
            'Magg.' => 80, 'Maggiore' => 80,
            'Cap.' => 75, 'Capitano' => 75,
            '1° Ten.' => 70, 'Primo Tenente' => 70,
            '2° Ten.' => 65, 'Secondo Tenente' => 65,
            'Sottoten.' => 60, 'Sottotenente' => 60,
            
            'SERG. MAGG. AIUT.' => 55, 'Sergente Maggiore Aiutante' => 55,
            'SERG MAGG CA' => 52, 'Sergente Maggiore Capo' => 52,
            'SERG MAGG' => 50, 'Sergente Maggiore' => 50,
            'SERG.' => 45, 'Sergente' => 45,
            
            'GRAD. AIUT.' => 40, 'Graduato Aiutante' => 40,
            '1° GRAD.' => 35, 'Primo Graduato' => 35,
            'GRAD. CA.' => 32, 'Graduato Capo' => 32,
            'GRAD. SC.' => 30, 'Graduato Scelto' => 30,
            'GRAD.' => 25, 'Graduato' => 25,
        ];
        
        return $ordini[$gradoNome] ?? 20;
    }

    /**
     * Normalizza la categoria militare
     */
    private function normalizzaCategoria($categoria)
    {
        $categoria = strtoupper(trim($categoria));
        
        return match($categoria) {
            'U' => 'U',
            'SU' => 'SU', 
            'GRAD' => 'GRAD',
            default => null
        };
    }

    /**
     * Trova o crea un approntamento
     */
    private function findOrCreateApprontamento($approntamentoNome)
    {
        if (empty($approntamentoNome) || $approntamentoNome === '/') {
            return null;
        }
        
        $approntamentoNome = trim($approntamentoNome);
        
        $approntamento = Approntamento::where('nome', $approntamentoNome)->first();
        
        if (!$approntamento) {
            $approntamento = Approntamento::create([
                'nome' => $approntamentoNome,
                'codice' => strtoupper(substr($approntamentoNome, 0, 5)),
                'descrizione' => "Approntamento importato: {$approntamentoNome}",
                'stato' => 'attivo',
                'colore_badge' => $this->getColoreApprontamento($approntamentoNome)
            ]);
            
            $this->info("Nuovo approntamento creato: {$approntamentoNome}");
        }
        
        return $approntamento;
    }

    /**
     * Ottiene un colore per l'approntamento
     */
    private function getColoreApprontamento($nome)
    {
        $colori = [
            '#28a745', '#dc3545', '#ffc107', '#17a2b8', 
            '#6f42c1', '#fd7e14', '#20c997', '#6c757d'
        ];
        
        return $colori[abs(crc32($nome)) % count($colori)];
    }

    /**
     * Importa le patenti del militare
     */
    private function importaPatenti($militare, $stringaPatenti)
    {
        if (empty($stringaPatenti) || $stringaPatenti === '//') {
            return;
        }
        
        $patenti = PatenteMilitare::parseFromExcel($stringaPatenti);
        
        foreach ($patenti as $patente) {
            PatenteMilitare::updateOrCreate(
                [
                    'militare_id' => $militare->id,
                    'categoria' => $patente['categoria']
                ],
                [
                    'tipo' => $patente['tipo'],
                    'data_ottenimento' => Carbon::now()->subYears(2), // Data fittizia
                    'data_scadenza' => Carbon::now()->addYears(8)     // Data fittizia
                ]
            );
        }
    }

    /**
     * Importa le assegnazioni giornaliere
     */
    private function importaAssegnazioniGiornaliere($worksheet, $row, $militare, $pianificazione)
    {
        // Colonne G-AJ rappresentano i giorni 1-30 di Settembre
        // G=1, H=2, I=3, ..., X=18, Y=19, Z=20, AA=21, ..., AJ=30
        $colonne = [];
        for ($i = 0; $i < 30; $i++) {
            if ($i < 20) {
                // G-Z (giorni 1-20)
                $colonne[] = chr(ord('G') + $i);
            } else {
                // AA-AJ (giorni 21-30)
                $colonne[] = 'A' . chr(ord('A') + ($i - 20));
            }
        }
        
        foreach ($colonne as $index => $colonna) {
            $giorno = $index + 1; // Giorni 1-30
            $codiceServizio = $worksheet->getCell($colonna . $row)->getValue();
            
            if (empty($codiceServizio)) {
                continue;
            }
            
            $tipoServizio = TipoServizio::where('codice', $codiceServizio)->first();
            
            if ($tipoServizio) {
                PianificazioneGiornaliera::updateOrCreate(
                    [
                        'pianificazione_mensile_id' => $pianificazione->id,
                        'militare_id' => $militare->id,
                        'giorno' => $giorno
                    ],
                    [
                        'tipo_servizio_id' => $tipoServizio->id
                    ]
                );
            } else {
                // Crea nuovo tipo servizio se non esiste
                // Tronca il codice se troppo lungo e gestisci duplicati
                $codiceCorto = strlen($codiceServizio) > 10 ? 
                              substr($codiceServizio, 0, 10) : 
                              $codiceServizio;
                              
                // Cerca se esiste già un tipo servizio con questo codice troncato
                $tipoServizio = TipoServizio::where('codice', $codiceCorto)->first();
                
                if (!$tipoServizio) {
                    $tipoServizio = TipoServizio::create([
                        'codice' => $codiceCorto,
                        'nome' => "Servizio {$codiceServizio}",
                        'descrizione' => "Tipo servizio importato: {$codiceServizio}",
                        'categoria' => 'servizio',
                        'colore_badge' => '#6c757d'
                    ]);
                }
                
                PianificazioneGiornaliera::updateOrCreate(
                    [
                        'pianificazione_mensile_id' => $pianificazione->id,
                        'militare_id' => $militare->id,
                        'giorno' => $giorno
                    ],
                    [
                        'tipo_servizio_id' => $tipoServizio->id
                    ]
                );
            }
        }
    }
}
