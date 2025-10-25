<?php

namespace App\Http\Controllers;

use App\Services\TurniService;
use App\Models\Militare;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

class TurniController extends Controller
{
    protected $turniService;

    public function __construct(TurniService $turniService)
    {
        $this->turniService = $turniService;
    }

    /**
     * Mostra la vista settimanale dei turni
     */
    public function index(Request $request)
    {
        // Ottieni la data dalla query string o usa oggi
        $data = $request->has('data') 
            ? Carbon::parse($request->data) 
            : Carbon::now();

        // Ottieni tutti i dati per la settimana
        $dati = $this->turniService->getDatiSettimana($data);

        return view('servizi.turni.index', $dati);
    }

    /**
     * API: Verifica disponibilità militare per una data
     */
    public function checkDisponibilita(Request $request)
    {
        $request->validate([
            'militare_id' => 'required|exists:militari,id',
            'data' => 'required|date',
        ]);

        $militare = Militare::find($request->militare_id);
        $disponibilita = $militare->isDisponibile($request->data);

        return response()->json($disponibilita);
    }

    /**
     * API: Assegna un militare a un servizio
     */
    public function assegna(Request $request)
    {
        $request->validate([
            'turno_id' => 'required|exists:turni_settimanali,id',
            'servizio_id' => 'required|exists:servizi_turno,id',
            'militare_id' => 'required|exists:militari,id',
            'data' => 'required|date',
            'forza_sovrascrizione' => 'sometimes|boolean',
        ]);

        $risultato = $this->turniService->assegnaMilitare(
            $request->turno_id,
            $request->servizio_id,
            $request->militare_id,
            $request->data,
            $request->boolean('forza_sovrascrizione', false)
        );

        return response()->json($risultato);
    }

    /**
     * API: Rimuovi un'assegnazione
     * DISABILITATO - Tabella assegnazioni_turno non esiste
     */
    public function rimuovi(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Funzionalità disabilitata - tabella assegnazioni_turno non esiste'
        ]);
    }

    /**
     * Copia settimana precedente
     */
    public function copiaSettimana(Request $request)
    {
        $request->validate([
            'turno_id' => 'required|exists:turni_settimanali,id',
        ]);

        $risultato = $this->turniService->copiaSettimanaPrecedente($request->turno_id);

        return response()->json($risultato);
    }

    /**
     * Sincronizza tutte le assegnazioni con il CPT
     */
    public function sincronizza(Request $request)
    {
        $request->validate([
            'turno_id' => 'required|exists:turni_settimanali,id',
        ]);

        $risultato = $this->turniService->sincronizzaTutteAssegnazioni($request->turno_id);

        return response()->json([
            'success' => true,
            'message' => "Sincronizzate {$risultato['sincronizzate']} assegnazioni. Fallite: {$risultato['fallite']}",
            'data' => $risultato
        ]);
    }

    /**
     * Genera lo spreadsheet con i turni
     */
    private function generaSpreadsheet($data)
    {
        $dati = $this->turniService->getDatiSettimana($data);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Foglio1');

        // RIGA 1-3: Intestazione
        $sheet->setCellValue('A1', "11° REGGIMENTO TRASMISSIONI");
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        
        $sheet->setCellValue('A2', 'Battaglione Trasmissioni "LEONESSA"');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        
        $sheet->setCellValue('A3', '124^ Compagnia');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        
        // Riga vuota
        $sheet->getRowDimension(4)->setRowHeight(5);

        // RIGA 5: Header giorni settimana
        $sheet->setCellValue('A5', 'TIPO DI SERVIZIO');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A5')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        $col = 'B';
        foreach ($dati['giorniSettimana'] as $giorno) {
            $sheet->setCellValue($col . '5', strtoupper($giorno['giorno_settimana']));
            $sheet->getStyle($col . '5')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle($col . '5')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $col++;
        }

        // RIGA 6: Date (formato gg/mm/yyyy)
        $col = 'B';
        foreach ($dati['giorniSettimana'] as $giorno) {
            $sheet->setCellValue($col . '6', $giorno['giorno_num']);
            $sheet->getStyle($col . '6')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle($col . '6')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $col++;
        }

        // Bordi header
        $headerStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A5:H6')->applyFromArray($headerStyle);

        // Popola servizi e assegnazioni
        $row = 7;
        foreach ($dati['serviziTurno'] as $servizio) {
            $maxPosti = max($servizio->num_posti, 1);
            
            for ($posto = 0; $posto < $maxPosti; $posto++) {
                // Nome servizio solo alla prima riga
                if ($posto === 0) {
                    $sheet->setCellValue('A' . $row, $servizio->nome);
                    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(10);
                    $sheet->getStyle('A' . $row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    
                    // Merge se multi-posto
                    if ($maxPosti > 1) {
                        $sheet->mergeCells('A' . $row . ':A' . ($row + $maxPosti - 1));
                    }
                }

                // Assegnazioni per ogni giorno
                $col = 'B';
                foreach ($dati['giorniSettimana'] as $giorno) {
                    $dataKey = $giorno['data']->format('Y-m-d');
                    $assegnazioni = $dati['matriceTurni'][$servizio->id]['assegnazioni'][$dataKey] ?? collect();
                    
                    $assegnazione = $assegnazioni->get($posto);
                    
                    if ($assegnazione) {
                        $testoCompleto = ($assegnazione->militare->grado->sigla ?? '') . ' ' . strtoupper($assegnazione->militare->cognome);
                        $sheet->setCellValue($col . $row, $testoCompleto);
                    } else {
                        $sheet->setCellValue($col . $row, '');
                    }
                    
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle($col . $row)->getFont()->setSize(10);
                    
                    $col++;
                }
                
                // Bordi
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ];
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
                
                $row++;
            }
        }

        // Firma
        $row += 2;
        $sheet->setCellValue('F' . $row, "IL COMANDANTE LA COMPAGNIA");
        $sheet->mergeCells('F' . $row . ':H' . $row);
        $sheet->getStyle('F' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('F' . $row)->getFont()->setBold(true)->setSize(10);
        
        $row++;
        $sheet->setCellValue('F' . $row, "Cap. t.(tlm.) RN Mattia CACCAMO");
        $sheet->mergeCells('F' . $row . ':H' . $row);
        $sheet->getStyle('F' . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('F' . $row)->getFont()->setSize(10);

        // Larghezze colonne
        $sheet->getColumnDimension('A')->setWidth(45); // Tipo di Servizio
        for ($c = 'B'; $c <= 'H'; $c++) {
            $sheet->getColumnDimension($c)->setWidth(28); // Giorni della settimana
        }

        // Limita l'area stampabile alla sola tabella
        $ultimaRiga = $row;
        $sheet->getPageSetup()->setPrintArea('A1:H' . $ultimaRiga);
        
        // Imposta l'area di visualizzazione (nasconde celle vuote oltre la tabella)
        $sheet->setSelectedCells('A1');
        
        // Nascondi tutte le righe dopo l'ultima utilizzata
        $sheet->getRowDimension($ultimaRiga + 1)->setVisible(false);
        $sheet->getRowDimension($ultimaRiga + 1)->setOutlineLevel(1);
        $sheet->getRowDimension($ultimaRiga + 1)->setCollapsed(true);
        
        // Nascondi colonne dopo H (I in poi)
        for ($col = 'I'; $col <= 'Z'; $col++) {
            $sheet->getColumnDimension($col)->setVisible(false);
        }
        
        // Limita l'area scorrevole (freeze panes non serve qui)
        // Imposta la selezione iniziale in A1
        $sheet->setSelectedCells('A1:H' . $ultimaRiga);
        
        // Imposta opzioni di stampa per limitare alle pagine necessarie
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        
        // Margini ridotti
        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.5)
            ->setLeft(0.5)
            ->setBottom(0.5);
        
        // Mostra le griglie per una migliore visualizzazione
        $sheet->setShowGridlines(false);

        return $spreadsheet;
    }

    /**
     * Export Excel formato identico a Turni.xlsx
     */
    public function exportExcel(Request $request)
    {
        try {
            $data = $request->has('data') 
                ? Carbon::parse($request->data) 
                : Carbon::now();

            $spreadsheet = $this->generaSpreadsheet($data);

            // Genera file
            $filename = 'Turni_' . $data->format('d-m-Y') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'turni_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Errore export Excel turni', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
}

