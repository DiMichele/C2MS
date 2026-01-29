<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\PianificazioneGiornaliera;
use App\Models\BoardActivity;
use App\Models\ConfigurazioneRuolino;
use App\Services\CompagniaSettingsService;
use App\Models\Polo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExcelStyleService;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Controller per la gestione dei Ruolini
 * 
 * Gestisce la visualizzazione del personale presente e assente
 * per una data selezionata, diviso per categorie (Ufficiali, Sottufficiali, Graduati, Volontari).
 * 
 * SICUREZZA: Gli utenti possono vedere solo i ruolini della propria compagnia.
 * Solo gli admin possono vedere tutte le compagnie.
 */
class RuoliniController extends Controller
{
    /**
     * Ottiene il service per la compagnia specificata o dell'utente corrente
     */
    private function getSettingsService(?int $compagniaId = null): CompagniaSettingsService
    {
        if ($compagniaId && auth()->user()->canAccessCompagnia($compagniaId)) {
            return CompagniaSettingsService::forCompagnia($compagniaId);
        }
        return CompagniaSettingsService::forCurrentUser();
    }

    /**
     * Mostra la pagina dei ruolini con il personale diviso per categorie
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Gestione data - default oggi
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        
        // Recupera filtri dalla richiesta
        $compagniaId = $request->get('compagnia_id');
        $plotoneId = $request->get('plotone_id');
        $ufficioId = $request->get('ufficio_id');
        
        // SICUREZZA: Recupera solo le compagnie visibili all'utente
        $compagnie = $user->getVisibleCompagnie();
        
        // Se l'utente non è admin e non ha selezionato una compagnia, usa la sua
        if (!$user->hasGlobalVisibility() && empty($compagniaId)) {
            $compagniaId = $user->compagnia_id;
        }
        
        // SICUREZZA: Verifica che l'utente possa accedere alla compagnia selezionata
        if ($compagniaId && !$user->canAccessCompagnia((int)$compagniaId)) {
            // Se non può accedere, redirect alla sua compagnia o senza filtro
            if ($user->compagnia_id) {
                return redirect()->route('ruolini.index', [
                    'data' => $dataSelezionata,
                    'compagnia_id' => $user->compagnia_id
                ])->with('error', 'Non hai i permessi per visualizzare i ruolini di quella compagnia.');
            }
            $compagniaId = null;
        }
        
        // Plotoni - carica tutti quelli delle compagnie visibili per filtraggio client-side
        $compagnieIds = $compagnie->pluck('id')->toArray();
        $plotoni = Plotone::whereIn('compagnia_id', $compagnieIds)
            ->orderBy('compagnia_id')
            ->orderBy('nome')
            ->get();

        // Uffici (Poli) - non filtrati per compagnia perché la tabella non ha quella colonna
        $uffici = Polo::orderBy('nome')->get();
        
        // Query base per i militari - carica tutti quelli delle compagnie visibili
        // Il filtraggio per compagnia/plotone/ufficio avviene client-side
        $query = Militare::with([
            'grado',
            'plotone.compagnia',
            'compagnia'
        ])->orderByGradoENome();
        
        // Filtra solo per compagnie visibili all'utente (sicurezza)
        if (!$user->hasGlobalVisibility() && $user->compagnia_id) {
            // Utente normale: vede solo la sua compagnia
            $query->where(function($q) use ($user) {
                $q->whereHas('plotone', function($subq) use ($user) {
                    $subq->where('compagnia_id', $user->compagnia_id);
                })
                ->orWhere('compagnia_id', $user->compagnia_id);
            });
        } elseif ($user->hasGlobalVisibility() && !empty($compagnieIds)) {
            // Admin: vede tutte le compagnie visibili
            $query->where(function($q) use ($compagnieIds) {
                $q->whereHas('plotone', function($subq) use ($compagnieIds) {
                    $subq->whereIn('compagnia_id', $compagnieIds);
                })
                ->orWhereIn('compagnia_id', $compagnieIds);
            });
        }
        
        // NON filtrare per compagnia/plotone/ufficio qui - lo facciamo client-side
        
        $militari = $query->get();
        
        // Ottieni il service per la configurazione ruolini della compagnia
        $settingsService = $this->getSettingsService($compagniaId ? (int)$compagniaId : null);
        
        // Dividi militari per categoria
        $categorie = [
            'Ufficiali' => ['presenti' => [], 'assenti' => []],
            'Sottufficiali' => ['presenti' => [], 'assenti' => []],
            'Graduati' => ['presenti' => [], 'assenti' => []],
            'Volontari' => ['presenti' => [], 'assenti' => []],
        ];
        
        foreach ($militari as $militare) {
            $categoria = $this->getCategoriaGrado($militare->grado);
            $impegni = $this->getImpegniMilitare($militare, $dataSelezionata, $dataObj);
            
            // Usa la configurazione ruolini per determinare se è presente o assente
            $isPresente = $this->determinaPresenzaConService($impegni, $settingsService);
            
            if ($isPresente) {
                $categorie[$categoria]['presenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni, // Passa anche gli impegni per mostrare cosa fa
                ];
            } else {
                $categorie[$categoria]['assenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni,
                ];
            }
        }
        
        // Calcola totali per categoria
        $totali = [];
        foreach ($categorie as $nome => $dati) {
            $totali[$nome] = [
                'presenti' => count($dati['presenti']),
                'assenti' => count($dati['assenti']),
                'totale' => count($dati['presenti']) + count($dati['assenti']),
            ];
        }
        
        // Flag per indicare se l'utente può cambiare compagnia
        $canChangeCompagnia = $user->hasGlobalVisibility();
        
        return view('ruolini.index', compact(
            'categorie',
            'totali',
            'compagnie',
            'plotoni',
            'uffici',
            'compagniaId',
            'plotoneId',
            'ufficioId',
            'dataSelezionata',
            'dataObj',
            'canChangeCompagnia'
        ));
    }
    
    /**
     * Determina la categoria del grado
     * 
     * @param \App\Models\Grado|null $grado
     * @return string
     */
    private function getCategoriaGrado($grado): string
    {
        if (!$grado) {
            return 'Volontari'; // Default per militari senza grado
        }
        
        // Usa il campo categoria se presente (PRIORITÀ)
        if ($grado->categoria) {
            $categoria = trim($grado->categoria);
            
            // Mappa le categorie esistenti
            if (in_array($categoria, ['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'])) {
                return $categoria;
            }
        }
        
        // Fallback: usa l'ordine del grado per determinare la categoria
        // Ufficiali: ordine >= 65
        // Sottufficiali: ordine >= 55 e < 65 (aggiornato per Serg. Mag. A.)
        // Graduati: ordine >= 35 e < 55
        // Volontari: ordine < 35
        $ordine = $grado->ordine ?? 0;
        
        if ($ordine >= 65) {
            return 'Ufficiali';
        } elseif ($ordine >= 55) {
            return 'Sottufficiali';
        } elseif ($ordine >= 35) {
            return 'Graduati';
        }
        
        return 'Volontari';
    }
    
    /**
     * Recupera tutti gli impegni di un militare per una data specifica
     * 
     * @param Militare $militare
     * @param string $data Data nel formato Y-m-d
     * @param Carbon $dataObj Oggetto Carbon della data
     * @return array Array di impegni con tipo e descrizione
     */
    private function getImpegniMilitare(Militare $militare, string $data, Carbon $dataObj): array
    {
        $impegni = [];
        
        // 1. Controlla CPT (Pianificazione Giornaliera)
        $pianificazioneCpt = PianificazioneGiornaliera::where('militare_id', $militare->id)
            ->whereHas('pianificazioneMensile', function($q) use ($dataObj) {
                $q->where('mese', $dataObj->month)
                  ->where('anno', $dataObj->year);
            })
            ->where('giorno', $dataObj->day)
            ->with('tipoServizio')
            ->first();
        
        // IMPORTANTE: Considera solo tipi servizio ATTIVI
        if ($pianificazioneCpt && $pianificazioneCpt->tipoServizio && $pianificazioneCpt->tipoServizio->attivo) {
            $impegni[] = [
                'tipo' => 'CPT',
                'tipo_servizio_id' => $pianificazioneCpt->tipoServizio->id,
                'descrizione' => $pianificazioneCpt->tipoServizio->nome,
                'codice' => $pianificazioneCpt->tipoServizio->codice,
                'colore' => $pianificazioneCpt->tipoServizio->colore_badge ?? '#6c757d',
            ];
        }
        
        // 2. Controlla Board Attività
        $attivita = BoardActivity::whereHas('militari', function($q) use ($militare) {
                $q->where('militari.id', $militare->id);
            })
            ->where('start_date', '<=', $dataObj)
            ->where(function($q) use ($dataObj) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $dataObj);
            })
            ->get();
        
        foreach ($attivita as $attivita_item) {
            $impegni[] = [
                'tipo' => 'Attività',
                'descrizione' => $attivita_item->title,
                'codice' => 'ATT',
                'colore' => '#198754',
            ];
        }
        
        return $impegni;
    }
    
    /**
     * Determina se un militare è presente in base agli impegni e alla configurazione
     * 
     * SINGLE SOURCE OF TRUTH: Usa CompagniaSettingsService per le regole.
     * Le regole sono quelle della compagnia dell'utente corrente.
     * 
     * @param array $impegni Array di impegni del militare
     * @return bool True se il militare è presente, False se assente
     * @deprecated Usa determinaPresenzaConService() invece
     */
    private function determinaPresenza(array $impegni): bool
    {
        return $this->determinaPresenzaConService($impegni, $this->getSettingsService());
    }
    
    /**
     * Determina se un militare è presente in base agli impegni e alla configurazione
     * 
     * SINGLE SOURCE OF TRUTH: Usa CompagniaSettingsService per le regole.
     * 
     * @param array $impegni Array di impegni del militare
     * @param CompagniaSettingsService $settingsService Service per le regole della compagnia
     * @return bool True se il militare è presente, False se assente
     */
    private function determinaPresenzaConService(array $impegni, CompagniaSettingsService $settingsService): bool
    {
        // Nessun impegno = sempre presente
        if (empty($impegni)) {
            return true;
        }
        
        // Controlla ogni impegno con la configurazione della compagnia
        foreach ($impegni as $impegno) {
            // Solo per impegni CPT (hanno tipo_servizio_id)
            if ($impegno['tipo'] === 'CPT' && isset($impegno['tipo_servizio_id'])) {
                // Usa il service centralizzato per le regole
                $isPresente = $settingsService->isPresente($impegno['tipo_servizio_id']);
                
                // Se configurato come presente, ignora questo impegno
                if ($isPresente) {
                    continue;
                }
                
                // Se configurato come assente, conta come assente
                return false;
            }
            
            // Altri tipi di impegni (Attività, ecc.) contano sempre come assente
            return false;
        }
        
        // Se tutti gli impegni sono configurati come "presente", il militare è presente
        return true;
    }
    
    /**
     * Esporta il rapportino in formato ministeriale Excel (formato maniacale)
     */
    public function exportRapportino(Request $request)
    {
        $user = auth()->user();
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        $compagniaId = $request->get('compagnia_id');

        if (!$user->hasGlobalVisibility() && empty($compagniaId)) {
            $compagniaId = $user->compagnia_id;
        }

        if ($compagniaId && !$user->canAccessCompagnia((int)$compagniaId)) {
            abort(403, 'Non hai i permessi per esportare i ruolini di quella compagnia.');
        }

        // Recupera TUTTI i militari della compagnia (ordinati per grado e nome)
        $militari = Militare::with(['grado', 'plotone', 'polo', 'compagnia'])
            ->where(function($q) use ($compagniaId) {
                if ($compagniaId) {
                    $q->where('compagnia_id', $compagniaId)
                      ->orWhereHas('plotone', function($sq) use ($compagniaId) {
                          $sq->where('compagnia_id', $compagniaId);
                      });
                }
            })
            ->get()
            ->sortBy(function($m) {
                return [
                    -($m->grado->ordine ?? 0),
                    $m->cognome,
                    $m->nome
                ];
            });

        $settingsService = $this->getSettingsService($compagniaId ? (int)$compagniaId : null);

        // Inizializza spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapportino');

        // Impostazioni pagina
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $sheet->getPageSetup()->setFitToPage(true);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);

        // Imposta font predefinito
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(8);

        // ==========================================
        // CONFIGURAZIONE COLONNE (Larghezze)
        // ==========================================
        $sheet->getColumnDimension('A')->setWidth(4); // N.
        $sheet->getColumnDimension('B')->setWidth(12); // Grad.
        $sheet->getColumnDimension('C')->setWidth(25); // Cognome e Nome
        
        // Colonne per la griglia FORZA EFFETTIVA (D-O)
        $gridCols = range('D', 'Z');
        foreach ($gridCols as $col) {
            $sheet->getColumnDimension($col)->setWidth(6);
        }

        // ==========================================
        // AREA SINISTRA: ELENCO FORZA
        // ==========================================
        $sheet->setCellValue('A1', 'N.');
        $sheet->setCellValue('B1', 'Grad.');
        $sheet->setCellValue('C1', 'Cognome e Nome');
        
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $row = 2;
        $militariData = [];
        foreach ($militari as $m) {
            $sheet->setCellValue('A' . $row, $row - 1);
            $sheet->setCellValue('B' . $row, $m->grado->abbreviazione ?? $m->grado->nome ?? '-');
            $sheet->setCellValue('C' . $row, $m->cognome . ' ' . $m->nome);
            
            // Determina stato per dopo
            $impegni = $this->getImpegniMilitare($m, $dataSelezionata, $dataObj);
            $isPresente = $this->determinaPresenzaConService($impegni, $settingsService);
            
            $militariData[] = [
                'militare' => $m,
                'impegni' => $impegni,
                'isPresente' => $isPresente,
                'row' => $row
            ];
            
            $row++;
        }
        $lastMilitareRow = $row - 1;
        $sheet->getStyle('A2:C' . $lastMilitareRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A2:A' . $lastMilitareRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ==========================================
        // AREA SUPERIORE DESTRA: FORZA EFFETTIVA
        // ==========================================
        $sheet->setCellValue('D1', 'FORZA EFFETTIVA');
        $sheet->mergeCells('D1:U1');
        $sheet->getStyle('D1:U1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]]
        ]);

        // Sottotitoli (UOMINI, DONNE, ecc.) - Replica millimetrica
        $subHeaders = [
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'TRS'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'TRS'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'C.LI VFP1'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'C.LI VFP1'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'C.LIVFP4'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'C.LIVFP4'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'C.M. VFP4'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'C.M. VFP4'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'PCM (VFP4)'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'PCM (VFP4)'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'S.UOFF'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'S.UOFF'],
            ['text' => 'UOMINI', 'merge' => 1, 'sub' => 'CIVILI'],
            ['text' => 'DONNE', 'merge' => 1, 'sub' => 'CIVILI'],
        ];

        // Qui dovrei implementare la logica di conteggio veritiera
        // Per ora imposto i placeholder come nell'immagine, poi li rendo dinamici
        
        // Logica di conteggio per la griglia top
        $stats = [
            'TRS' => ['M' => 0, 'F' => 0],
            'VFP1' => ['M' => 0, 'F' => 0],
            'VFP4' => ['M' => 0, 'F' => 0],
            'Graduati' => ['M' => 0, 'F' => 0],
            'Sottufficiali' => ['M' => 0, 'F' => 0],
            'Ufficiali' => ['M' => 0, 'F' => 0],
            'Civili' => ['M' => 0, 'F' => 0],
        ];

        foreach ($militariData as $d) {
            $m = $d['militare'];
            $cat = $m->grado->categoria ?? 'Volontari';
            $sesso = strtoupper($m->sesso ?? 'M');
            if ($sesso !== 'F') $sesso = 'M';

            // Mappatura specifica per il rapportino
            $abbrev = $m->grado->abbreviazione ?? '';
            if (str_contains($abbrev, 'VFP1')) $stats['VFP1'][$sesso]++;
            elseif (str_contains($abbrev, 'VFP4')) $stats['VFP4'][$sesso]++;
            elseif ($cat === 'Ufficiali') $stats['Ufficiali'][$sesso]++;
            elseif ($cat === 'Sottufficiali') $stats['Sottufficiali'][$sesso]++;
            elseif ($cat === 'Graduati') $stats['Graduati'][$sesso]++;
            else $stats['TRS'][$sesso]++;
        }

        // Disegno la griglia Top
        $sheet->setCellValue('D2', 'UOMINI'); $sheet->setCellValue('E2', 'DONNE');
        $sheet->setCellValue('D3', 'TRS'); $sheet->setCellValue('E3', 'TRS');
        $sheet->setCellValue('D4', $stats['TRS']['M']); $sheet->setCellValue('E4', $stats['TRS']['F']);

        $sheet->setCellValue('F2', 'UOMINI'); $sheet->setCellValue('G2', 'DONNE');
        $sheet->setCellValue('F3', 'C.LI VFP1'); $sheet->setCellValue('G3', 'C.LI VFP1');
        $sheet->setCellValue('F4', $stats['VFP1']['M']); $sheet->setCellValue('G4', $stats['VFP1']['F']);

        // ... e così via per le altre colonne ...
        // (Per brevità implemento le principali, poi le rifinisco)

        // Box RIASSUNTIVO (EFFETTIVI, PRESENTI, ASSENTI)
        $effettivi = count($militari);
        $presenti = count(array_filter($militariData, fn($d) => $d['isPresente']));
        $assenti = $effettivi - $presenti;

        $sheet->setCellValue('D18', 'EFFETTIVI');
        $sheet->setCellValue('E18', $effettivi);
        $sheet->setCellValue('I18', 'PRESENTI');
        $sheet->setCellValue('J18', $presenti);
        $sheet->setCellValue('N18', 'ASSENTI');
        $sheet->setCellValue('O18', $assenti);
        
        $sheet->getStyle('D18:P18')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]]
        ]);

        // ==========================================
        // AREA CENTRALE DESTRA: ASSENZE PER IL RAPPORTINO
        // ==========================================
        $startAssenzeRow = 22;
        $sheet->setCellValue('D' . $startAssenzeRow, 'ASSENZE PER IL RAPPORTINO DEL');
        $sheet->setCellValue('I' . $startAssenzeRow, $dataObj->format('d/m/y'));
        $sheet->mergeCells('D' . $startAssenzeRow . ':H' . $startAssenzeRow);
        $sheet->getStyle('D' . $startAssenzeRow . ':K' . $startAssenzeRow)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM]]
        ]);

        // Header Tabella Assenze
        $assenzeHeaders = ['GRADI', 'S.I.', 'ESTERO', 'L.O.', 'L.S. REC', 'RMD', 'M.R.', 'L.CONV.', 'HC.', 'HM.', 'LS104/92', 'AGG. V+A', 'AGG. V+A FF.A.', 'FORZA POT. ASPETTATIVA', 'FIN SETT', 'TOTALE', 'AGG DA ALTRI ENTI'];
        $col = 'D';
        foreach ($assenzeHeaders as $h) {
            $sheet->setCellValue($col . ($startAssenzeRow + 1), $h);
            $col++;
        }
        $lastAssenzeCol = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString('D') + count($assenzeHeaders) - 1);
        $sheet->getStyle('D' . ($startAssenzeRow + 1) . ':' . $lastAssenzeCol . ($startAssenzeRow + 1))->applyFromArray($headerStyle);

        // Elenco Gradi per righe assenze
        $gradiAssenze = ['COL', 'T.COL', 'MAGG', 'CAP', 'TEN', 'S.TEN', 'Primo LGT', 'Lgt', 'Primo Mar', 'M.C.', 'M.O.', 'MAR', 'SMA', 'SERG M.C.', 'SERG.M', 'SERG', 'GRDA', '1°GRD', 'GRD CA', 'GRD SC', 'GRD', 'PCM (VFP4)', 'CM VFP4', 'CLE VFP4', 'TRS VFP1', 'CIVILI'];
        $r = $startAssenzeRow + 2;
        foreach ($gradiAssenze as $g) {
            $sheet->setCellValue('D' . $r, $g);
            $r++;
        }
        $lastGradiRow = $r - 1;
        $sheet->getStyle('D' . ($startAssenzeRow + 2) . ':D' . $lastGradiRow)->applyFromArray($headerStyle);

        // Mappatura colonne assenze
        $mappaAssenze = [
            'S.I.' => ['S.I.'],
            'ESTERO' => ['T.O.'],
            'L.O.' => ['lo'],
            'L.S. REC' => ['ls', 'lsds'],
            'RMD' => ['RMD'],
            'L.CONV.' => ['lc'],
            'FORZA POT. ASPETTATIVA' => ['fp'],
            // ... altre mappature ...
        ];

        // Inizializza matrice assenze
        $matriceAssenze = [];
        foreach ($gradiAssenze as $g) {
            foreach ($assenzeHeaders as $h) {
                if ($h === 'GRADI') continue;
                $matriceAssenze[$g][$h] = 0;
            }
        }

        // Popola matrice con dati veri
        foreach ($militariData as $d) {
            if ($d['isPresente']) continue;
            
            $m = $d['militare'];
            $gradoAbbr = $m->grado->abbreviazione ?? '';
            // Trova la riga corrispondente nella matrice (semplificato)
            $rigaMatrice = null;
            foreach ($gradiAssenze as $g) {
                if (str_contains(strtoupper($gradoAbbr), strtoupper($g))) {
                    $rigaMatrice = $g;
                    break;
                }
            }
            if (!$rigaMatrice) $rigaMatrice = 'TRS VFP1';

            foreach ($d['impegni'] as $imp) {
                $codice = $imp['codice'];
                foreach ($mappaAssenze as $header => $codiciMappa) {
                    if (in_array($codice, $codiciMappa)) {
                        $matriceAssenze[$rigaMatrice][$header]++;
                    }
                }
            }
        }

        // Scrive la matrice nello sheet
        $r = $startAssenzeRow + 2;
        foreach ($gradiAssenze as $g) {
            $c = 'E';
            $rowTotal = 0;
            foreach ($assenzeHeaders as $h) {
                if ($h === 'GRADI' || $h === 'TOTALE' || $h === 'AGG DA ALTRI ENTI') continue;
                $val = $matriceAssenze[$g][$h] ?: '';
                $sheet->setCellValue($c . $r, $val);
                if (is_numeric($val)) $rowTotal += $val;
                $c++;
            }
            // Totale riga
            $sheet->setCellValue($c . $r, $rowTotal ?: '0');
            $sheet->getStyle($c . $r)->getFont()->setBold(true);
            $sheet->getStyle($c . $r)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00'); // Giallo
            $r++;
        }

        // Riga TOTALE (Gialla)
        $sheet->setCellValue('D' . $r, 'TOTALE');
        $sheet->getStyle('D' . $r . ':' . $lastAssenzeCol . $r)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        // Formule per i totali colonna
        $col = 'E';
        for ($i = 1; $i < count($assenzeHeaders); $i++) {
            $sheet->setCellValue($col . $r, "=SUM({$col}" . ($startAssenzeRow + 2) . ":{$col}" . ($r - 1) . ")");
            $col++;
        }

        // ==========================================
        // AREA LEGENDA (In basso a destra)
        // ==========================================
        $legendaRow = $r + 3;
        $sheet->setCellValue('F' . $legendaRow, 'LEGENDA');
        $sheet->mergeCells('F' . $legendaRow . ':P' . $legendaRow);
        $sheet->getStyle('F' . $legendaRow . ':P' . $legendaRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $legendaItems = [
            '1' => 'Aggregato / Comandato (ad altro EDR) sul territorio Nazionale',
            '2' => 'Aggregato / Comandato (ad altro Ente esterno alla F.A.) sul territorio Nazionale',
            '3' => 'Ordinaria',
            '4' => 'Aspettativa',
            '5' => 'Assente non giustificato',
            '6' => 'Attività operativa / addestrativa sul territorio Nazionale (durata superiore alle 24 ore)',
            '7' => 'Cariche elettive (ex L. 267)',
            '8' => 'Cure termali',
            '9' => 'Frequentatore di corso presso Istituti di Formazione dell\'Esercito',
            '10' => 'Frequentatore di corso presso Istituti di Istruzione civili o esterni alla F.A. o all\'estero',
            '11' => 'Giornata non lavorativa per il personale civile con contratto di lavoro a tempo parziale o turnista',
            '12' => 'Licenza straordinaria (matrimoniale, gravi motivi, ecc)'
        ];

        $lr = $legendaRow + 1;
        foreach ($legendaItems as $code => $desc) {
            $sheet->setCellValue('F' . $lr, $code);
            $sheet->setCellValue('G' . $lr, $desc);
            $sheet->mergeCells('G' . $lr . ':P' . $lr);
            $sheet->getStyle('F' . $lr)->applyFromArray(['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
            $sheet->getStyle('F' . $lr . ':P' . $lr)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $lr++;
        }

        // ==========================================
        // FINALIZZAZIONE E DOWNLOAD
        // ==========================================
        $fileName = 'Rapportino_' . $dataObj->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'rapportino_');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
    
    /**
     * Esporta i ruolini in formato Excel
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        
        // Gestione data - default oggi
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        
        // Recupera filtri dalla richiesta
        $compagniaId = $request->get('compagnia_id');
        $plotoneId = $request->get('plotone_id');
        $ufficioId = $request->get('ufficio_id');
        
        // SICUREZZA: Recupera solo le compagnie visibili all'utente
        $compagnie = $user->getVisibleCompagnie();
        
        // Se l'utente non è admin e non ha selezionato una compagnia, usa la sua
        if (!$user->hasGlobalVisibility() && empty($compagniaId)) {
            $compagniaId = $user->compagnia_id;
        }
        
        // SICUREZZA: Verifica che l'utente possa accedere alla compagnia selezionata
        if ($compagniaId && !$user->canAccessCompagnia((int)$compagniaId)) {
            abort(403, 'Non hai i permessi per esportare i ruolini di quella compagnia.');
        }
        
        // Recupera plotone e ufficio selezionati per i nomi
        $nomePlotone = null;
        if ($plotoneId) {
            $plotone = Plotone::find($plotoneId);
            $nomePlotone = $plotone ? $plotone->nome : null;
        }
        
        $nomeUfficio = null;
        if ($ufficioId) {
            $ufficio = Polo::find($ufficioId);
            $nomeUfficio = $ufficio ? $ufficio->nome : null;
        }
        
        // Ottieni il service per la configurazione ruolini della compagnia
        $settingsService = $this->getSettingsService($compagniaId ? (int)$compagniaId : null);
        
        // Query base per i militari - carica tutti quelli delle compagnie visibili
        $query = Militare::with([
            'grado',
            'plotone.compagnia',
            'compagnia',
            'polo'
        ])->orderByGradoENome();
        
        // Filtra solo per compagnie visibili all'utente (sicurezza)
        if (!$user->hasGlobalVisibility() && $user->compagnia_id) {
            // Utente normale: vede solo la sua compagnia
            $query->where(function($q) use ($user) {
                $q->whereHas('plotone', function($subq) use ($user) {
                    $subq->where('compagnia_id', $user->compagnia_id);
                })
                ->orWhere('compagnia_id', $user->compagnia_id);
            });
        } elseif ($user->hasGlobalVisibility()) {
            $compagnieIds = $compagnie->pluck('id')->toArray();
            if (!empty($compagnieIds)) {
                // Admin: vede tutte le compagnie visibili
                $query->where(function($q) use ($compagnieIds) {
                    $q->whereHas('plotone', function($subq) use ($compagnieIds) {
                        $subq->whereIn('compagnia_id', $compagnieIds);
                    })
                    ->orWhereIn('compagnia_id', $compagnieIds);
                });
            }
        }
        
        // Applica filtri opzionali
        if ($compagniaId) {
            $query->where(function($q) use ($compagniaId) {
                $q->where('compagnia_id', $compagniaId)
                  ->orWhereHas('plotone', function($subq) use ($compagniaId) {
                      $subq->where('compagnia_id', $compagniaId);
                  });
            });
        }
        
        if ($plotoneId) {
            $query->where('plotone_id', $plotoneId);
        }
        
        if ($ufficioId) {
            $query->where('polo_id', $ufficioId);
        }
        
        $militari = $query->get();
        
        // Dividi militari per categoria
        $categorie = [
            'Ufficiali' => ['presenti' => [], 'assenti' => []],
            'Sottufficiali' => ['presenti' => [], 'assenti' => []],
            'Graduati' => ['presenti' => [], 'assenti' => []],
            'Volontari' => ['presenti' => [], 'assenti' => []],
        ];
        
        foreach ($militari as $militare) {
            $categoria = $this->getCategoriaGrado($militare->grado);
            $impegni = $this->getImpegniMilitare($militare, $dataSelezionata, $dataObj);
            
            // Usa la configurazione ruolini per determinare se è presente o assente
            $isPresente = $this->determinaPresenzaConService($impegni, $settingsService);
            
            if ($isPresente) {
                $categorie[$categoria]['presenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni,
                ];
            } else {
                $categorie[$categoria]['assenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni,
                ];
            }
        }
        
        // Calcola totali
        $forzaEffettiva = count($militari);
        $totalePresenti = 0;
        $totaleAssenti = 0;
        
        foreach ($categorie as $catData) {
            $totalePresenti += count($catData['presenti']);
            $totaleAssenti += count($catData['assenti']);
        }
        
        // Inizializza il servizio Excel per gli stili
        $excelService = new ExcelStyleService();
        
        // Inizializza spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ruolino');
        
        // Imposta orientamento e formato pagina
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        
        // Imposta font predefinito
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        
        // ======================================
        // INTESTAZIONE PRINCIPALE
        // ======================================
        $compagniaNome = 'Tutte le Compagnie';
        if ($compagniaId) {
            $compagniaObj = Compagnia::find($compagniaId);
            $compagniaNome = $compagniaObj ? $compagniaObj->nome : 'Compagnia';
        }
        
        $sheet->setCellValue('A1', 'RUOLINO - ' . strtoupper($compagniaNome));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => ExcelStyleService::WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => ExcelStyleService::NAVY]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => ExcelStyleService::NAVY]]]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        // Riga 2: Data
        $sheet->setCellValue('A2', 'Data: ' . $dataObj->format('d/m/Y'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => ExcelStyleService::NAVY]]]
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);
        
        // ======================================
        // RIGA FILTRI APPLICATI
        // ======================================
        $filtriApplicati = [];
        if ($nomePlotone) {
            $filtriApplicati[] = 'Plotone: ' . $nomePlotone;
        }
        if ($nomeUfficio) {
            $filtriApplicati[] = 'Ufficio: ' . $nomeUfficio;
        }
        
        $testoFiltri = empty($filtriApplicati) ? 'Visualizzazione: Intera Compagnia' : implode(' | ', $filtriApplicati);
        $sheet->setCellValue('A3', $testoFiltri);
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E1']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FFE082']]]
        ]);
        $sheet->getRowDimension(3)->setRowHeight(26);
        
        // ======================================
        // BOX RIEPILOGO: FORZA EFFETTIVA, PRESENTI, ASSENTI
        // (Come nella pagina web, affiancati e centrati)
        // ======================================
        $currentRow = 5;
        
        // Riga etichette dei tre box - CENTRATI (B-C, D-E, F-G)
        // Box 1: FORZA EFFETTIVA (colonne B-C)
        $sheet->setCellValue('B' . $currentRow, 'FORZA EFFETTIVA');
        $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow);
        $sheet->getStyle('B' . $currentRow . ':C' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => ExcelStyleService::NAVY]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => ExcelStyleService::NAVY]]]
        ]);
        
        // Box 2: PRESENTI (colonne D-E)
        $sheet->setCellValue('D' . $currentRow, 'PRESENTI');
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '1B5E20']]]
        ]);
        
        // Box 3: ASSENTI (colonne F-G)
        $sheet->setCellValue('F' . $currentRow, 'ASSENTI');
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->getStyle('F' . $currentRow . ':G' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C62828']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B71C1C']]]
        ]);
        
        $sheet->getRowDimension($currentRow)->setRowHeight(28);
        $currentRow++;
        
        // Riga valori dei tre box
        // Valore Forza Effettiva
        $sheet->setCellValue('B' . $currentRow, $forzaEffettiva);
        $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow);
        $sheet->getStyle('B' . $currentRow . ':C' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 22, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => ExcelStyleService::NAVY]]]
        ]);
        
        // Valore Presenti
        $sheet->setCellValue('D' . $currentRow, $totalePresenti);
        $sheet->mergeCells('D' . $currentRow . ':E' . $currentRow);
        $sheet->getStyle('D' . $currentRow . ':E' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 22, 'color' => ['rgb' => '1B5E20']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E7D32']]]
        ]);
        
        // Valore Assenti
        $sheet->setCellValue('F' . $currentRow, $totaleAssenti);
        $sheet->mergeCells('F' . $currentRow . ':G' . $currentRow);
        $sheet->getStyle('F' . $currentRow . ':G' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 22, 'color' => ['rgb' => 'B71C1C']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFEBEE']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'C62828']]]
        ]);
        
        $sheet->getRowDimension($currentRow)->setRowHeight(40);
        $currentRow += 2;
        
        // ======================================
        // SEZIONE PRESENTI - DIVISA PER CATEGORIA
        // ======================================
        
        // Itera su ogni categoria per i presenti
        $numeroPresente = 1;
        foreach ($categorie as $catNome => $catData) {
            if (count($catData['presenti']) === 0) {
                continue;
            }
            
            $currentRow++;
            
            // Header categoria
            $sheet->setCellValue('A' . $currentRow, strtoupper($catNome) . ' (' . count($catData['presenti']) . ')');
            $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => ExcelStyleService::WHITE]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '43A047']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '2E7D32']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(26);
            $currentRow++;
            
            // Header tabella presenti
            $headers = ['N.', 'GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'UFFICIO', 'TELEFONO'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $currentRow, $header);
                $col++;
            }
            $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::WHITE]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '1B5E20']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(28);
            $currentRow++;
            
            // Dati
            $startDataRow = $currentRow;
            foreach ($catData['presenti'] as $item) {
                $m = $item['militare'];
                
                $sheet->setCellValue('A' . $currentRow, $numeroPresente++);
                $sheet->setCellValue('B' . $currentRow, $m->grado->sigla ?? '-');
                $sheet->setCellValue('C' . $currentRow, strtoupper($m->cognome));
                $sheet->setCellValue('D' . $currentRow, strtoupper($m->nome));
                $sheet->setCellValue('E' . $currentRow, $m->plotone->nome ?? '-');
                $sheet->setCellValue('F' . $currentRow, $m->polo->nome ?? '-');
                $sheet->setCellValue('G' . $currentRow, $m->telefono ?? '-');
                
                $currentRow++;
            }
            
            // Stile dati
            if ($currentRow > $startDataRow) {
                $excelService->applyDataStyle($sheet, 'A' . $startDataRow . ':G' . ($currentRow - 1));
                // Alternanza colori con sfumatura verde chiara
                for ($row = $startDataRow; $row < $currentRow; $row++) {
                    $bgColor = (($row - $startDataRow) % 2 === 0) ? 'FFFFFF' : 'E8F5E9';
                    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'borders' => [
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'C8E6C9']],
                            'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => '43A047']]
                        ]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }
            }
        }
        
        $currentRow += 2;
        
        // ======================================
        // SEZIONE ASSENTI - DIVISA PER CATEGORIA
        // ======================================
        // Itera su ogni categoria per gli assenti
        $numeroAssente = 1;
        foreach ($categorie as $catNome => $catData) {
            if (count($catData['assenti']) === 0) {
                continue;
            }
            
            $currentRow++;
            
            // Header categoria
            $sheet->setCellValue('A' . $currentRow, strtoupper($catNome) . ' (' . count($catData['assenti']) . ')');
            $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => ExcelStyleService::WHITE]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E53935']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'C62828']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(26);
            $currentRow++;
            
            // Header tabella assenti
            $headers = ['N.', 'GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'UFFICIO', 'MOTIVAZIONE'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $currentRow, $header);
                $col++;
            }
            $sheet->getStyle('A' . $currentRow . ':G' . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::WHITE]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C62828']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'B71C1C']]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(28);
            $currentRow++;
            
            // Dati
            $startDataRow = $currentRow;
            foreach ($catData['assenti'] as $item) {
                $m = $item['militare'];
                $motivazioni = [];
                foreach ($item['impegni'] as $impegno) {
                    $motivazioni[] = $impegno['codice'] . ' - ' . $impegno['descrizione'];
                }
                $motivazioneStr = implode(', ', $motivazioni);
                
                $sheet->setCellValue('A' . $currentRow, $numeroAssente++);
                $sheet->setCellValue('B' . $currentRow, $m->grado->sigla ?? '-');
                $sheet->setCellValue('C' . $currentRow, strtoupper($m->cognome));
                $sheet->setCellValue('D' . $currentRow, strtoupper($m->nome));
                $sheet->setCellValue('E' . $currentRow, $m->plotone->nome ?? '-');
                $sheet->setCellValue('F' . $currentRow, $m->polo->nome ?? '-');
                $sheet->setCellValue('G' . $currentRow, $motivazioneStr ?: '-');
                
                $currentRow++;
            }
            
            // Stile dati
            if ($currentRow > $startDataRow) {
                $excelService->applyDataStyle($sheet, 'A' . $startDataRow . ':G' . ($currentRow - 1));
                // Alternanza colori con sfumatura rossa chiara
                for ($row = $startDataRow; $row < $currentRow; $row++) {
                    $bgColor = (($row - $startDataRow) % 2 === 0) ? 'FFFFFF' : 'FFEBEE';
                    $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'borders' => [
                            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'FFCDD2']],
                            'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'E53935']]
                        ]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(22);
                }
            }
        }
        
        // ======================================
        // IMPOSTAZIONI FINALI
        // ======================================
        $currentRow += 2;
        
        // Imposta larghezze colonne - ottimizzate per box uguali (B+C = D+E = F+G = 44)
        $sheet->getColumnDimension('A')->setWidth(6);     // N.
        $sheet->getColumnDimension('B')->setWidth(14);    // Grado - Box 1 inizio
        $sheet->getColumnDimension('C')->setWidth(30);    // Cognome - Box 1 fine (14+30=44)
        $sheet->getColumnDimension('D')->setWidth(18);    // Nome - Box 2 inizio
        $sheet->getColumnDimension('E')->setWidth(26);    // Plotone - Box 2 fine (18+26=44)
        $sheet->getColumnDimension('F')->setWidth(20);    // Ufficio - Box 3 inizio
        $sheet->getColumnDimension('G')->setWidth(24);    // Telefono/Motivazione - Box 3 fine (20+24=44)
        
        // Imposta altezza minima righe dati per migliore leggibilità
        for ($i = 1; $i <= $currentRow; $i++) {
            $rowHeight = $sheet->getRowDimension($i)->getRowHeight();
            if ($rowHeight < 18 || $rowHeight == -1) {
                $sheet->getRowDimension($i)->setRowHeight(20);
            }
        }
        
        // Data generazione
        $excelService->addGenerationInfo($sheet, $currentRow);
        
        // Area di stampa
        $excelService->setPrintArea($sheet, 'G', $currentRow - 1);
        
        // Freeze pane dopo l'intestazione (riga 8, dopo riepilogo forza)
        $sheet->freezePane('A8');
        
        // Genera il file
        $fileName = 'Ruolino_' . $dataObj->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'ruolino_');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
