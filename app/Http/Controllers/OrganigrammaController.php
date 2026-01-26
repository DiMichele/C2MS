<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use App\Models\Compagnia;
use App\Models\Polo;
use App\Services\ExcelStyleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

/**
 * Controller per la gestione dell'organigramma militare
 * 
 * Questo controller gestisce la visualizzazione dell'organigramma della compagnia
 * con plotoni, poli e militari associati. Utilizza un sistema di cache per
 * ottimizzare le performance delle query complesse.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class OrganigrammaController extends Controller
{
    /**
     * Durata della cache in secondi
     */
    private const CACHE_DURATION = 3600; // 1 ora
    
    /**
     * Mostra la pagina principale dell'organigramma
     * 
     * Carica la struttura completa dell'organigramma con:
     * - Compagnia principale (filtrabile)
     * - Plotoni con militari associati
     * - Poli con militari associati
     * - Informazioni sulle presenze
     * - Statistiche aggregate
     * 
     * Utilizza eager loading per ottimizzare le query e cache per le performance.
     * L'utente admin può selezionare una compagnia, gli altri vedono solo la propria.
     * 
     * @param Request $request
     * @return \Illuminate\View\View|\Illuminate\View\View Vista dell'organigramma o pagina di errore
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $compagniaSelezionataId = null;
        
        // Carica tutte le compagnie per il selettore (solo per admin)
        $compagnie = collect();
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            $compagnie = Compagnia::orderBy('nome')->get();
            $compagniaSelezionataId = $request->get('compagnia_id');
        } else {
            // Utente non admin: usa la sua compagnia
            $compagniaSelezionataId = $user->compagnia_id;
        }
        
        // Genera chiave cache unica per la compagnia selezionata
        $cacheKey = 'organigramma.compagnia.' . ($compagniaSelezionataId ?? 'first');
        
        // Ottieni la compagnia con caching
        $compagnia = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($compagniaSelezionataId) {
            $query = Compagnia::with([
                'plotoni' => function ($q) {
                    $q->orderBy('nome');
                },
                'plotoni.militari' => function ($q) {
                    $q->orderByGradoENome();
                },
                'plotoni.militari.grado'
            ]);
            
            if ($compagniaSelezionataId) {
                return $query->find($compagniaSelezionataId);
            }
            
            return $query->first();
        });
        
        // Carica i poli globali con i militari filtrati per compagnia selezionata
        $poli = collect();
        if ($compagnia) {
            $poli = \App\Models\Polo::with(['militari' => function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id)
                  ->orderByGradoENome();
            }, 'militari.grado'])
            ->whereHas('militari', function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id);
            })
            ->orderBy('nome')
            ->get();
        }
        
        if (!$compagnia) {
            // Se non ci sono compagnie, mostra pagina di errore personalizzata
            return view('errors.custom', [
                'title' => 'Nessuna compagnia trovata',
                'message' => 'Non è stata trovata nessuna compagnia nel sistema. Contattare l\'amministratore.'
            ]);
        }
        
        // Calcola statistiche aggregate (al volo per dati sempre aggiornati)
        $statistiche = $this->calcolaStatistiche($compagnia, $poli);
        
        return view('organigramma.organigramma', array_merge(
            compact('compagnia', 'compagnie', 'compagniaSelezionataId', 'poli'),
            $statistiche
        ));
    }
    
    /**
     * Invalida la cache dell'organigramma
     * 
     * Rimuove la cache dell'organigramma per forzare il refresh
     * dei dati al prossimo caricamento. Utile quando vengono
     * effettuate modifiche alla struttura organizzativa.
     * 
     * @return \Illuminate\Http\RedirectResponse Redirect all'organigramma con messaggio di successo
     */
    public function refreshCache()
    {
        Cache::forget('organigramma.compagnia');
        
        return redirect()->route('organigramma')
            ->with('success', 'Organigramma aggiornato con successo!');
    }

    /**
     * Calcola le statistiche aggregate dell'organigramma
     * 
     * @param Compagnia $compagnia Compagnia di cui calcolare le statistiche
     * @return array Array con le statistiche calcolate
     */
    private function calcolaStatistiche(Compagnia $compagnia, $poli)
    {
        // Calcola totale effettivi dai plotoni
        $totaleEffettiviPlotoni = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->count();
        });
        
        // Calcola totale effettivi dai poli (i poli sono globali ma filtrati per compagnia)
        $totaleEffettiviPoli = $poli->sum(function ($polo) {
            return $polo->militari->count();
        });
        
        $totaleEffettivi = $totaleEffettiviPlotoni + $totaleEffettiviPoli;
        
        // Calcola totale presenti dai plotoni
        $totalePresentiPlotoni = $compagnia->plotoni->sum(function ($plotone) {
            return $plotone->militari->filter(fn($m) => $m->isPresente())->count();
        });
        
        // Calcola totale presenti dai poli
        $totalePresentiPoli = $poli->sum(function ($polo) {
            return $polo->militari->filter(fn($m) => $m->isPresente())->count();
        });
        
        $totalePresenti = $totalePresentiPlotoni + $totalePresentiPoli;
        
        // Calcola percentuale presenti
        $percentualePresenti = $totaleEffettivi > 0 
            ? round(($totalePresenti / $totaleEffettivi) * 100) 
            : 0;
        
        return [
            'totaleEffettivi' => $totaleEffettivi,
            'totalePresenti' => $totalePresenti,
            'percentualePresenti' => $percentualePresenti
        ];
    }
    
    /**
     * Esporta l'organigramma in formato Excel
     * 
     * Genera un file Excel con la vista selezionata (Plotoni o Uffici).
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $compagniaSelezionataId = null;
        
        // Determina la compagnia da esportare
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            $compagniaSelezionataId = $request->get('compagnia_id');
        } else {
            $compagniaSelezionataId = $user->compagnia_id;
        }
        
        // Determina la vista da esportare (default: plotoni)
        $vista = $request->get('vista', 'plotoni');
        
        // Carica la compagnia con plotoni e militari
        $query = Compagnia::with([
            'plotoni' => function ($q) {
                $q->orderBy('nome');
            },
            'plotoni.militari' => function ($q) {
                $q->orderByGradoENome();
            },
            'plotoni.militari.grado',
            'plotoni.militari.mansione'
        ]);
        
        if ($compagniaSelezionataId) {
            $compagnia = $query->find($compagniaSelezionataId);
        } else {
            $compagnia = $query->first();
        }
        
        if (!$compagnia) {
            abort(404, 'Nessuna compagnia trovata');
        }
        
        // Crea il file Excel
        $excelService = new ExcelStyleService();
        
        $sheet = null;
        $tipoVista = '';
        $gruppi = null;
        
        if ($vista === 'uffici') {
            // Carica i poli con militari filtrati per compagnia
            $poli = Polo::with(['militari' => function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id)
                  ->orderByGradoENome();
            }, 'militari.grado', 'militari.plotone'])
            ->whereHas('militari', function($q) use ($compagnia) {
                $q->where('compagnia_id', $compagnia->id);
            })
            ->orderBy('nome')
            ->get();
            
            $spreadsheet = $excelService->createSpreadsheet('Organigramma per Uffici - ' . $compagnia->nome);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Uffici');
            $tipoVista = 'Ufficio';
            $gruppi = $poli;
        } else {
            // Vista plotoni (default)
            $spreadsheet = $excelService->createSpreadsheet('Organigramma per Plotoni - ' . $compagnia->nome);
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Plotoni');
            $tipoVista = 'Plotone';
            $gruppi = $compagnia->plotoni;
        }
        
        $this->popolaFoglioOrganigramma($sheet, $excelService, $compagnia, $gruppi, $tipoVista);
        
        // Genera il file
        $vistaLabel = $vista === 'uffici' ? 'Uffici' : 'Plotoni';
        $fileName = 'Organigramma_' . $vistaLabel . '_' . str_replace(' ', '_', $compagnia->nome) . '_' . Carbon::now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'organigramma_');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
    
    /**
     * Popola un foglio Excel con i dati dell'organigramma
     * 
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param ExcelStyleService $excelService
     * @param Compagnia $compagnia
     * @param \Illuminate\Support\Collection $gruppi (plotoni o uffici)
     * @param string $tipoGruppo ('Plotone' o 'Ufficio')
     */
    private function popolaFoglioOrganigramma($sheet, ExcelStyleService $excelService, $compagnia, $gruppi, string $tipoGruppo): void
    {
        $oggi = Carbon::now()->locale('it')->isoFormat('dddd D MMMM YYYY');
        
        // Calcola totali
        $totaleMilitari = $gruppi->sum(fn($g) => $g->militari->count());
        $totaleGruppi = $gruppi->count();
        
        // Colori sobri (in linea con il sito)
        $navyColor = ExcelStyleService::NAVY;
        $navyLight = '1A3A5F';
        $goldColor = ExcelStyleService::GOLD;
        $grayLight = 'F5F7F9';
        $grayBorder = 'DEE2E6';
        
        // Colonne diverse in base alla vista
        // Plotoni: N., Grado, Cognome, Nome, Incarico (5 colonne: A-E)
        // Uffici: N., Grado, Cognome, Nome, Plotone, Telefono (6 colonne: A-F)
        $isPlotoni = $tipoGruppo === 'Plotone';
        $lastCol = $isPlotoni ? 'E' : 'F';
        
        // ======================================
        // INTESTAZIONE
        // ======================================
        $titolo = 'ORGANIGRAMMA ' . strtoupper($compagnia->nome);
        $sheet->setCellValue('A1', $titolo);
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $navyColor]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $grayLight]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $goldColor]]]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(45);
        
        // Sottotitolo con tipo visualizzazione e data
        $vistaLabel = $isPlotoni ? 'Plotoni' : 'Uffici';
        $sottotitolo = 'Organizzazione per ' . $vistaLabel . '  |  ' . ucfirst($oggi);
        $sheet->setCellValue('A2', $sottotitolo);
        $sheet->mergeCells('A2:' . $lastCol . '2');
        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6c757d']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->getRowDimension(2)->setRowHeight(24);
        
        // ======================================
        // RIEPILOGO - Solo Forza Effettiva centrata
        // ======================================
        $currentRow = 4;
        
        // Box Forza Effettiva centrato
        $sheet->setCellValue('B' . $currentRow, 'FORZA EFFETTIVA');
        $sheet->mergeCells('B' . $currentRow . ':C' . $currentRow);
        $sheet->setCellValue('D' . $currentRow, $totaleMilitari);
        
        $sheet->getStyle('B' . $currentRow . ':C' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => ExcelStyleService::WHITE]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $navyColor]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $navyColor]]]
        ]);
        $sheet->getStyle('D' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $navyColor]],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $navyColor]]]
        ]);
        
        $sheet->getRowDimension($currentRow)->setRowHeight(35);
        $currentRow += 2;
        
        // ======================================
        // CONTENUTO PER OGNI GRUPPO
        // ======================================
        foreach ($gruppi as $gruppo) {
            // Ordina per grado DECRESCENTE (grado più alto prima)
            $militari = $gruppo->militari->sortByDesc(function($militare) {
                return optional($militare->grado)->ordine ?? 0;
            });
            
            if ($militari->count() === 0) {
                continue;
            }
            
            // Header del gruppo - solo nome
            $sheet->setCellValue('A' . $currentRow, $gruppo->nome);
            $sheet->mergeCells('A' . $currentRow . ':' . $lastCol . $currentRow);
            $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => ExcelStyleService::WHITE]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $navyColor]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER, 'indent' => 1],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => $navyColor]]]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(28);
            $currentRow++;
            
            // Header colonne (diverse in base alla vista)
            if ($isPlotoni) {
                $headers = ['N.', 'Grado', 'Cognome', 'Nome', 'Incarico'];
            } else {
                $headers = ['N.', 'Grado', 'Cognome', 'Nome', 'Plotone', 'Telefono'];
            }
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $currentRow, $header);
                $col++;
            }
            $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->applyFromArray([
                'font' => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $navyColor]],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $grayLight]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => $grayBorder]],
                    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => $goldColor]]
                ]
            ]);
            $sheet->getRowDimension($currentRow)->setRowHeight(22);
            $currentRow++;
            
            // Dati militari
            $startDataRow = $currentRow;
            $numero = 1;
            foreach ($militari as $militare) {
                $sheet->setCellValue('A' . $currentRow, $numero++);
                $sheet->setCellValue('B' . $currentRow, $militare->grado->sigla ?? '-');
                $sheet->setCellValue('C' . $currentRow, strtoupper($militare->cognome));
                $sheet->setCellValue('D' . $currentRow, strtoupper($militare->nome));
                
                if ($isPlotoni) {
                    // Per plotoni: mostra incarico
                    $sheet->setCellValue('E' . $currentRow, $militare->mansione->nome ?? '-');
                } else {
                    // Per uffici: mostra plotone e telefono
                    $sheet->setCellValue('E' . $currentRow, $militare->plotone->nome ?? '-');
                    $sheet->setCellValue('F' . $currentRow, $militare->telefono ?? '-');
                }
                
                $currentRow++;
            }
            
            // Stile per le righe dati con alternanza colori
            if ($currentRow > $startDataRow) {
                for ($row = $startDataRow; $row < $currentRow; $row++) {
                    $bgColor = (($row - $startDataRow) % 2 === 0) ? 'FFFFFF' : $grayLight;
                    $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                        'font' => ['size' => 10],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => $grayBorder]]],
                        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
                    ]);
                    // Centra la colonna N.
                    $sheet->getStyle('A' . $row)->applyFromArray([
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(20);
                }
            }
            
            $currentRow += 2; // Spazio tra gruppi
        }
        
        // ======================================
        // IMPOSTAZIONI FINALI
        // ======================================
        
        // Imposta larghezze colonne in base alla vista
        if ($isPlotoni) {
            // Plotoni: N., Grado, Cognome, Nome, Incarico
            $sheet->getColumnDimension('A')->setWidth(6);    // N.
            $sheet->getColumnDimension('B')->setWidth(18);   // Grado
            $sheet->getColumnDimension('C')->setWidth(24);   // Cognome
            $sheet->getColumnDimension('D')->setWidth(22);   // Nome
            $sheet->getColumnDimension('E')->setWidth(35);   // Incarico
        } else {
            // Uffici: N., Grado, Cognome, Nome, Plotone, Telefono
            $sheet->getColumnDimension('A')->setWidth(6);    // N.
            $sheet->getColumnDimension('B')->setWidth(18);   // Grado
            $sheet->getColumnDimension('C')->setWidth(24);   // Cognome
            $sheet->getColumnDimension('D')->setWidth(22);   // Nome
            $sheet->getColumnDimension('E')->setWidth(28);   // Plotone
            $sheet->getColumnDimension('F')->setWidth(18);   // Telefono
        }
        
        // Data generazione
        $excelService->addGenerationInfo($sheet, $currentRow);
        
        // Area di stampa
        $excelService->setPrintArea($sheet, $lastCol, $currentRow - 1);
        
        // Freeze pane dopo intestazione
        $sheet->freezePane('A6');
    }
}
