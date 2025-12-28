<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\PianificazioneGiornaliera;
use App\Models\BoardActivity;
use App\Models\ConfigurazioneRuolino;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

/**
 * Controller per la gestione dei Ruolini
 * 
 * Gestisce la visualizzazione del personale presente e assente
 * per una data selezionata, diviso per categorie (Ufficiali, Sottufficiali, Graduati, Volontari).
 */
class RuoliniController extends Controller
{
    /**
     * Mostra la pagina dei ruolini con il personale diviso per categorie
     */
    public function index(Request $request)
    {
        // Gestione data - default oggi
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        
        // Recupera filtri dalla richiesta
        $compagniaId = $request->get('compagnia_id');
        $plotoneId = $request->get('plotone_id');
        
        // Recupera tutte le compagnie e plotoni per i filtri
        $compagnie = Compagnia::with('plotoni')->orderBy('nome')->get();
        $plotoni = Plotone::with('compagnia')->orderBy('nome')->get();
        
        // Query base per i militari
        $query = Militare::with([
            'grado',
            'plotone.compagnia',
            'compagnia'
        ])->orderByGradoENome();
        
        // Applica filtri
        if ($compagniaId) {
            $query->where(function($q) use ($compagniaId) {
                $q->whereHas('plotone', function($subq) use ($compagniaId) {
                    $subq->where('compagnia_id', $compagniaId);
                })
                ->orWhere('compagnia_id', $compagniaId);
            });
        }
        
        if ($plotoneId) {
            $query->where('plotone_id', $plotoneId);
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
            
            if (empty($impegni)) {
                $categorie[$categoria]['presenti'][] = [
                    'militare' => $militare,
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
        
        return view('ruolini.index', compact(
            'categorie',
            'totali',
            'compagnie',
            'plotoni',
            'compagniaId',
            'plotoneId',
            'dataSelezionata',
            'dataObj'
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
        
        // 2. Controlla Turni Settimanali - DISABILITATO (tabella assegnazioni_turno non esiste)
        // $turno = AssegnazioneTurno::where('militare_id', $militare->id)
        //     ->where('data_servizio', $data)
        //     ->with('servizioTurno')
        //     ->first();
        // 
        // if ($turno && $turno->servizioTurno) {
        //     $impegni[] = [
        //         'tipo' => 'Turno',
        //         'descrizione' => $turno->servizioTurno->nome,
        //         'codice' => $turno->servizioTurno->sigla ?? 'TRN',
        //         'colore' => '#0d6efd',
        //     ];
        // }
        
        // 3. Controlla Board Attività
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
     * @param array $impegni Array di impegni del militare
     * @return bool True se il militare è presente, False se assente
     */
    private function determinaPresenza(array $impegni): bool
    {
        // Nessun impegno = sempre presente
        if (empty($impegni)) {
            return true;
        }
        
        // Controlla ogni impegno con la configurazione
        foreach ($impegni as $impegno) {
            // Solo per impegni CPT (hanno tipo_servizio_id)
            if ($impegno['tipo'] === 'CPT' && isset($impegno['tipo_servizio_id'])) {
                $statoConfigurato = ConfigurazioneRuolino::getStatoPresenzaForTipoServizio($impegno['tipo_servizio_id']);
                
                // Se configurato come presente, ignora questo impegno
                if ($statoConfigurato === 'presente') {
                    continue;
                }
                
                // Se configurato come assente o default, conta come assente
                return false;
            }
            
            // Altri tipi di impegni (Attività, ecc.) contano sempre come assente
            return false;
        }
        
        // Se tutti gli impegni sono configurati come "presente", il militare è presente
        return true;
    }
    
    /**
     * Esporta i ruolini in formato Excel
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        // Gestione data - default oggi
        $dataSelezionata = $request->get('data', Carbon::today()->format('Y-m-d'));
        $dataObj = Carbon::parse($dataSelezionata);
        
        // Recupera filtri dalla richiesta
        $compagniaId = $request->get('compagnia_id');
        $plotoneId = $request->get('plotone_id');
        
        // Query base per i militari
        $query = Militare::with([
            'grado',
            'plotone.compagnia',
            'compagnia'
        ])->orderByGradoENome();
        
        // Applica filtri
        if ($compagniaId) {
            $query->where(function($q) use ($compagniaId) {
                $q->whereHas('plotone', function($subq) use ($compagniaId) {
                    $subq->where('compagnia_id', $compagniaId);
                })
                ->orWhere('compagnia_id', $compagniaId);
            });
        }
        
        if ($plotoneId) {
            $query->where('plotone_id', $plotoneId);
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
            
            if (empty($impegni)) {
                $categorie[$categoria]['presenti'][] = [
                    'militare' => $militare,
                ];
            } else {
                $categorie[$categoria]['assenti'][] = [
                    'militare' => $militare,
                    'impegni' => $impegni,
                ];
            }
        }
        
        // Crea il file Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Calcola i totali
        $totalePresenti = 0;
        $totaleAssenti = 0;
        foreach ($categorie as $catData) {
            $totalePresenti += count($catData['presenti']);
            $totaleAssenti += count($catData['assenti']);
        }
        $forzaEffettiva = $totalePresenti + $totaleAssenti;
        
        // Titolo
        $sheet->setCellValue('A1', 'RUOLINO DEL ' . $dataObj->locale('it')->isoFormat('dddd D MMMM YYYY'));
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['size' => 16, 'bold' => true, 'color' => ['rgb' => '0F3A6D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F2FF']]
        ]);
        $sheet->getRowDimension('1')->setRowHeight(30);
        
        // Riepilogo Forza
        $sheet->setCellValue('A2', 'FORZA EFFETTIVA: ' . $forzaEffettiva);
        $sheet->setCellValue('C2', 'PRESENTI: ' . $totalePresenti);
        $sheet->setCellValue('E2', 'ASSENTI: ' . $totaleAssenti);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0F3A6D']],
        ]);
        $sheet->getStyle('C2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '198754']],
        ]);
        $sheet->getStyle('E2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'DC3545']],
        ]);
        
        $currentRow = 4;
        
        // ======================================
        // SEZIONE PRESENTI
        // ======================================
        $sheet->setCellValue('A' . $currentRow, 'PRESENTI (' . $totalePresenti . ')');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['size' => 14, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']]
        ]);
        $currentRow += 2;
        
        // Header Presenti
        $headerPresenti = ['#', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'TELEFONO', 'ISTITUTI'];
        $col = 'A';
        foreach ($headerPresenti as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $col++;
        }
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '198754']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $currentRow++;
        
        // Dati Presenti
        $numeroPresente = 1;
        foreach ($categorie as $catNome => $catData) {
            foreach ($catData['presenti'] as $item) {
                $m = $item['militare'];
                $istituti = !empty($m->istituti) ? implode(', ', $m->istituti) : '-';
                
                $sheet->setCellValue('A' . $currentRow, $numeroPresente++);
                $sheet->setCellValue('B' . $currentRow, $m->compagnia->nome ?? '-');
                $sheet->setCellValue('C' . $currentRow, $m->grado->sigla ?? '');
                $sheet->setCellValue('D' . $currentRow, $m->cognome);
                $sheet->setCellValue('E' . $currentRow, $m->nome);
                $sheet->setCellValue('F' . $currentRow, $m->plotone->nome ?? '-');
                $sheet->setCellValue('G' . $currentRow, $m->telefono ?? '-');
                $sheet->setCellValue('H' . $currentRow, $istituti);
                
                $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                
                $currentRow++;
            }
        }
        
        $currentRow += 2;
        
        // ======================================
        // SEZIONE ASSENTI
        // ======================================
        $sheet->setCellValue('A' . $currentRow, 'ASSENTI (' . $totaleAssenti . ')');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray([
            'font' => ['size' => 14, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']]
        ]);
        $currentRow += 2;
        
        // Header Assenti
        $headerAssenti = ['#', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'PLOTONE', 'TELEFONO', 'MOTIVAZIONE'];
        $col = 'A';
        foreach ($headerAssenti as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $col++;
        }
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC3545']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
        $currentRow++;
        
        // Dati Assenti
        $numeroAssente = 1;
        foreach ($categorie as $catNome => $catData) {
            foreach ($catData['assenti'] as $item) {
                $m = $item['militare'];
                $motivazioni = [];
                foreach ($item['impegni'] as $impegno) {
                    $motivazioni[] = $impegno['descrizione'];
                }
                $motivazioneStr = implode(', ', $motivazioni);
                
                $sheet->setCellValue('A' . $currentRow, $numeroAssente++);
                $sheet->setCellValue('B' . $currentRow, $m->compagnia->nome ?? '-');
                $sheet->setCellValue('C' . $currentRow, $m->grado->sigla ?? '');
                $sheet->setCellValue('D' . $currentRow, $m->cognome);
                $sheet->setCellValue('E' . $currentRow, $m->nome);
                $sheet->setCellValue('F' . $currentRow, $m->plotone->nome ?? '-');
                $sheet->setCellValue('G' . $currentRow, $m->telefono ?? '-');
                $sheet->setCellValue('H' . $currentRow, $motivazioneStr);
                
                $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                
                $currentRow++;
            }
        }
        
        // Imposta larghezze colonne
        $sheet->getColumnDimension('A')->setWidth(8);   // #
        $sheet->getColumnDimension('B')->setWidth(20);  // Compagnia
        $sheet->getColumnDimension('C')->setWidth(12);  // Grado
        $sheet->getColumnDimension('D')->setWidth(22);  // Cognome
        $sheet->getColumnDimension('E')->setWidth(22);  // Nome
        $sheet->getColumnDimension('F')->setWidth(20);  // Plotone
        $sheet->getColumnDimension('G')->setWidth(18);  // Telefono
        $sheet->getColumnDimension('H')->setWidth(45);  // Istituti (Presenti) / Motivazione (Assenti)
        
        // Genera il file
        $fileName = 'Ruolino_' . $dataObj->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
