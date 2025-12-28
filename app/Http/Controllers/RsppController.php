<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\ConfigurazioneCorsoSpp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Illuminate\Support\Facades\Log;

class RsppController extends Controller
{
    /**
     * Visualizza la pagina RSPP con tutte le scadenze relative alla sicurezza
     */
    public function index(Request $request)
    {
        // Recupera tutti i corsi attivi di tipo 'formazione' ordinati
        $corsi = ConfigurazioneCorsoSpp::attivi()
            ->perTipo('formazione')
            ->ordinati()
            ->get();
        
        // Ottieni tutti i militari con le loro scadenze SPP
        $militari = Militare::with(['scadenzeCorsiSpp.corso'])
            ->orderByGradoENome()
            ->get();
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) use ($corsi) {
            $row = [
                'militare' => $militare,
            ];
            
            // Per ogni corso, recupera la scadenza del militare
            foreach ($corsi as $corso) {
                $scadenzaCorso = $militare->scadenzeCorsiSpp->firstWhere('configurazione_corso_spp_id', $corso->id);
                
                if ($scadenzaCorso) {
                    $row[$corso->codice_corso] = $scadenzaCorso->calcolaScadenza();
                } else {
                    // Nessuna scadenza registrata per questo corso
                    $row[$corso->codice_corso] = [
                        'data_scadenza' => null,
                        'stato' => 'mancante',
                        'classe' => 'mancante',
                        'giorni_rimanenti' => null,
                        'data_conseguimento' => null,
                        'durata' => $corso->durata_anni,
                    ];
                }
            }
            
            return $row;
        });
        
        return view('scadenze.rspp', compact('data', 'corsi'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        $request->validate([
            'corso_id' => 'required|exists:configurazione_corsi_spp,id',
            'data' => 'nullable|date',
        ]);
        
        $corsoId = $request->corso_id;
        $data = $request->data;
        
        // Trova o crea la scadenza per questo militare e corso
        $scadenzaCorso = \App\Models\ScadenzaCorsoSpp::updateOrCreate(
            [
                'militare_id' => $militare->id,
                'configurazione_corso_spp_id' => $corsoId,
            ],
            [
                'data_conseguimento' => $data,
            ]
        );
        
        // Calcola e restituisci la nuova scadenza
        $scadenzaCalcolata = $scadenzaCorso->calcolaScadenza();
        
        return response()->json([
            'success' => true,
            'scadenza' => $scadenzaCalcolata,
        ]);
    }
    
    /**
     * Calcola la scadenza e lo stato
     */
    private function calcolaScadenza($dataConseguimento, $durata)
    {
        if (!$dataConseguimento) {
            return [
                'data_conseguimento' => null,
                'data_scadenza' => null,
                'stato' => 'mancante',
                'giorni_rimanenti' => null,
            ];
        }
        
        $data = Carbon::parse($dataConseguimento);
        $scadenza = $data->copy()->addYears($durata);
        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        // Determina lo stato
        if ($giorniRimanenti < 0) {
            $stato = 'scaduto';
        } elseif ($giorniRimanenti <= 30) {
            $stato = 'in_scadenza';
        } else {
            $stato = 'valido';
        }
        
        return [
            'data_conseguimento' => $data,
            'data_scadenza' => $scadenza,
            'stato' => $stato,
            'giorni_rimanenti' => abs($giorniRimanenti),
        ];
    }
    
    /**
     * Esporta i dati RSPP in Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Ottieni tutti i militari con le loro scadenze
            $militari = Militare::with(['grado', 'compagnia', 'scadenza'])
                ->orderByGradoENome()
                ->get();
            
            // Stile header
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0a2342']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            
            // Titolo
            $sheet->setCellValue('A1', 'SCADENZE RSPP - SICUREZZA SUL LAVORO');
            $sheet->mergeCells('A1:P1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension('1')->setRowHeight(25);
            
            // Header colonne
            $headers = ['N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 
                        'LAV. 4H CONS.', 'LAV. 4H SCAD.', 'LAV. 8H CONS.', 'LAV. 8H SCAD.', 
                        'PREPOSTO CONS.', 'PREPOSTO SCAD.', 'DIRIGENTE CONS.', 'DIRIGENTE SCAD.',
                        'ANTINCENDIO CONS.', 'ANTINCENDIO SCAD.', 'BLSD CONS.', 'BLSD SCAD.',
                        'P.S. AZIENDALE CONS.', 'P.S. AZIENDALE SCAD.'];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $sheet->getStyle($col . '2')->applyFromArray($headerStyle);
                $sheet->getStyle($col . '2')->getAlignment()->setWrapText(true);
                $col++;
            }
            $sheet->getRowDimension('2')->setRowHeight(40);
            
            // Dati militari
            $row = 3;
            $num = 1;
            
            foreach ($militari as $militare) {
                $scadenza = $militare->scadenza;
                
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $militare->compagnia->nome ?? '');
                $sheet->setCellValue('C' . $row, $militare->grado->sigla ?? '');
                $sheet->setCellValue('D' . $row, strtoupper($militare->cognome));
                $sheet->setCellValue('E' . $row, strtoupper($militare->nome));
                
                // Lavoratore 4h
                $this->addScadenzaToSheet($sheet, $row, 'F', $scadenza?->lavoratore_4h_data_conseguimento, 4);
                
                // Lavoratore 8h
                $this->addScadenzaToSheet($sheet, $row, 'H', $scadenza?->lavoratore_8h_data_conseguimento, 4);
                
                // Preposto
                $this->addScadenzaToSheet($sheet, $row, 'J', $scadenza?->preposto_data_conseguimento, 2);
                
                // Dirigente
                $this->addScadenzaToSheet($sheet, $row, 'L', $scadenza?->dirigenti_data_conseguimento, 4);
                
                // Antincendio
                $this->addScadenzaToSheet($sheet, $row, 'N', $scadenza?->antincendio_data_conseguimento, 1);
                
                // BLSD
                $this->addScadenzaToSheet($sheet, $row, 'P', $scadenza?->blsd_data_conseguimento, 2);
                
                // Primo Soccorso Aziendale
                $this->addScadenzaToSheet($sheet, $row, 'R', $scadenza?->primo_soccorso_aziendale_data_conseguimento, 2);
                
                // Bordi
                $sheet->getStyle('A' . $row . ':S' . $row)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);
                
                $row++;
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(6);   // N.
            $sheet->getColumnDimension('B')->setWidth(25);  // Compagnia
            $sheet->getColumnDimension('C')->setWidth(12);  // Grado
            $sheet->getColumnDimension('D')->setWidth(20);  // Cognome
            $sheet->getColumnDimension('E')->setWidth(20);  // Nome
            
            // Date - larghezza aumentata per intestazioni lunghe tipo "P.S. AZIENDALE"
            $sheet->getColumnDimension('F')->setWidth(22);  // LAV. 4H CONS.
            $sheet->getColumnDimension('G')->setWidth(22);  // LAV. 4H SCAD.
            $sheet->getColumnDimension('H')->setWidth(22);  // LAV. 8H CONS.
            $sheet->getColumnDimension('I')->setWidth(22);  // LAV. 8H SCAD.
            $sheet->getColumnDimension('J')->setWidth(22);  // PREPOSTO CONS.
            $sheet->getColumnDimension('K')->setWidth(22);  // PREPOSTO SCAD.
            $sheet->getColumnDimension('L')->setWidth(22);  // DIRIGENTE CONS.
            $sheet->getColumnDimension('M')->setWidth(22);  // DIRIGENTE SCAD.
            $sheet->getColumnDimension('N')->setWidth(25);  // ANTINCENDIO CONS.
            $sheet->getColumnDimension('O')->setWidth(25);  // ANTINCENDIO SCAD.
            $sheet->getColumnDimension('P')->setWidth(18);  // BLSD CONS.
            $sheet->getColumnDimension('Q')->setWidth(18);  // BLSD SCAD.
            $sheet->getColumnDimension('R')->setWidth(26);  // P.S. AZIENDALE CONS.
            $sheet->getColumnDimension('S')->setWidth(26);  // P.S. AZIENDALE SCAD.
            
            // Salva
            $filename = 'Scadenze_RSPP_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'rspp_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel RSPP', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
    
    /**
     * Aggiunge una scadenza al foglio Excel con colori
     */
    private function addScadenzaToSheet($sheet, $row, $colCons, $dataConseguimento, $durata)
    {
        $colScad = chr(ord($colCons) + 1);
        
        if (!$dataConseguimento) {
            $sheet->setCellValue($colCons . $row, '');
            $sheet->setCellValue($colScad . $row, '');
            // Sfondo grigio per mancante
            $sheet->getStyle($colCons . $row . ':' . $colScad . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'CCCCCC']],
            ]);
            return;
        }
        
        $data = Carbon::parse($dataConseguimento);
        $scadenza = $data->copy()->addYears($durata);
        
        $sheet->setCellValue($colCons . $row, $data->format('d/m/Y'));
        $sheet->setCellValue($colScad . $row, $scadenza->format('d/m/Y'));
        
        // Calcola stato
        $oggi = Carbon::now();
        $giorniRimanenti = $oggi->diffInDays($scadenza, false);
        
        $coloreFondo = 'FFFFFF'; // Default bianco
        if ($giorniRimanenti < 0) {
            $coloreFondo = 'FF6B6B'; // Rosso - scaduto
        } elseif ($giorniRimanenti <= 30) {
            $coloreFondo = 'FFD93D'; // Giallo - in scadenza
        } else {
            $coloreFondo = '6BCF7F'; // Verde - valido
        }
        
        $sheet->getStyle($colScad . $row)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $coloreFondo]],
        ]);
        
        // Allineamento
        $sheet->getStyle($colCons . $row . ':' . $colScad . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }
}
