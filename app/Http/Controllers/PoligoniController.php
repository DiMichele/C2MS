<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Illuminate\Support\Facades\Log;

class PoligoniController extends Controller
{
    /**
     * Visualizza la pagina Poligoni
     */
    public function index(Request $request)
    {
        // Ottieni tutti i militari con le loro scadenze
        $militari = Militare::with('scadenza')
            ->orderByGradoENome()
            ->get();
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) {
            $scadenza = $militare->scadenza;
            
            return [
                'militare' => $militare,
                'tiri_approntamento' => $this->calcolaScadenza($scadenza?->tiri_approntamento_data_conseguimento, 6, 'mesi'), // 6 mesi
                'mantenimento_arma_lunga' => $this->calcolaScadenza($scadenza?->mantenimento_arma_lunga_data_conseguimento, 6, 'mesi'), // 6 mesi
                'mantenimento_arma_corta' => $this->calcolaScadenza($scadenza?->mantenimento_arma_corta_data_conseguimento, 6, 'mesi'), // 6 mesi
            ];
        });
        
        return view('scadenze.poligoni', compact('data'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        $request->validate([
            'campo' => 'required|string',
            'data' => 'nullable|date',
        ]);
        
        // Ottieni o crea il record scadenza
        $scadenza = $militare->scadenza;
        if (!$scadenza) {
            $scadenza = new ScadenzaMilitare();
            $scadenza->militare_id = $militare->id;
            $scadenza->save(); // Salva prima il record con solo il militare_id
        }
        
        $campo = $request->campo;
        
        $scadenza->$campo = $request->data;
        $scadenza->save();
        
        // Calcola la nuova scadenza (tutti i poligoni hanno durata 6 mesi)
        $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, 6, 'mesi');
        
        return response()->json([
            'success' => true,
            'scadenza' => $scadenzaCalcolata,
        ]);
    }
    
    /**
     * Calcola la scadenza e lo stato
     */
    private function calcolaScadenza($dataConseguimento, $durata, $unita = 'anni')
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
        $scadenza = $unita === 'mesi' 
            ? $data->copy()->addMonths($durata) 
            : $data->copy()->addYears($durata);
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
     * Esporta i dati Poligoni in Excel
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
            $sheet->setCellValue('A1', 'SCADENZE POLIGONI - TIRI E MANTENIMENTO');
            $sheet->mergeCells('A1:K1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension('1')->setRowHeight(25);
            
            // Header colonne
            $headers = ['N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 
                        'TIRI APPRONTAMENTO CONS.', 'TIRI APPRONTAMENTO SCAD.', 
                        'MANTENIMENTO A.L. CONS.', 'MANTENIMENTO A.L. SCAD.', 
                        'MANTENIMENTO A.C. CONS.', 'MANTENIMENTO A.C. SCAD.'];
            
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
                
                // Tiri Approntamento (6 mesi)
                $this->addScadenzaToSheet($sheet, $row, 'F', $scadenza?->tiri_approntamento_data_conseguimento, 6, 'mesi');
                
                // Mantenimento Arma Lunga (6 mesi)
                $this->addScadenzaToSheet($sheet, $row, 'H', $scadenza?->mantenimento_arma_lunga_data_conseguimento, 6, 'mesi');
                
                // Mantenimento Arma Corta (6 mesi)
                $this->addScadenzaToSheet($sheet, $row, 'J', $scadenza?->mantenimento_arma_corta_data_conseguimento, 6, 'mesi');
                
                // Bordi
                $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray([
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
            
            // Date - larghezza aumentata per intestazioni lunghe
            $sheet->getColumnDimension('F')->setWidth(28);  // TIRI APPRONTAMENTO CONS.
            $sheet->getColumnDimension('G')->setWidth(28);  // TIRI APPRONTAMENTO SCAD.
            $sheet->getColumnDimension('H')->setWidth(28);  // MANTENIMENTO A.L. CONS.
            $sheet->getColumnDimension('I')->setWidth(28);  // MANTENIMENTO A.L. SCAD.
            $sheet->getColumnDimension('J')->setWidth(28);  // MANTENIMENTO A.C. CONS.
            $sheet->getColumnDimension('K')->setWidth(28);  // MANTENIMENTO A.C. SCAD.
            
            // Salva
            $filename = 'Scadenze_Poligoni_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'poligoni_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Poligoni', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
    
    /**
     * Aggiunge una scadenza al foglio Excel con colori
     */
    private function addScadenzaToSheet($sheet, $row, $colCons, $dataConseguimento, $durata, $unita = 'anni')
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
        $scadenza = $unita === 'mesi' 
            ? $data->copy()->addMonths($durata) 
            : $data->copy()->addYears($durata);
        
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
