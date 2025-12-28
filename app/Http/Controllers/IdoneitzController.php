<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\Compagnia;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class IdoneitzController extends Controller
{
    /**
     * Visualizza la pagina Idoneità Sanitarie
     */
    public function index(Request $request)
    {
        // Query base per i militari
        $query = Militare::with(['scadenza', 'compagnia', 'grado']);
        
        // FILTRO PERMESSI: Se l'utente non è admin, filtra per la sua compagnia
        $user = Auth::user();
        $userCompagniaId = null;
        
        if ($user && !$user->hasRole('admin') && !$user->hasRole('amministratore')) {
            if ($user->compagnia_id) {
                $query->where('compagnia_id', $user->compagnia_id);
                $userCompagniaId = $user->compagnia_id;
            }
        }
        
        // Filtro compagnia (se l'utente è admin può filtrare per qualsiasi compagnia)
        if ($request->filled('compagnia_id') && (!$userCompagniaId || $user->hasRole('admin') || $user->hasRole('amministratore'))) {
            $query->where('compagnia_id', $request->compagnia_id);
        }
        
        // Ordinamento
        $militari = $query->orderByGradoENome()->get();
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) {
            $scadenza = $militare->scadenza;
            
            return [
                'militare' => $militare,
                'idoneita_mansione' => $this->calcolaScadenza($scadenza?->idoneita_mans_data_conseguimento, 1), // 1 anno
                'idoneita_smi' => $this->calcolaScadenza($scadenza?->idoneita_smi_data_conseguimento, 1), // 1 anno
                'ecg' => $this->calcolaScadenza($scadenza?->ecg_data_conseguimento, 1), // 1 anno
                'prelievi' => $this->calcolaScadenza($scadenza?->prelievi_data_conseguimento, 1), // 1 anno
            ];
        });
        
        // Ottieni le compagnie per il filtro (solo se admin o se l'utente non ha compagnia)
        $compagnie = [];
        if (!$userCompagniaId || $user->hasRole('admin') || $user->hasRole('amministratore')) {
            $compagnie = Compagnia::orderBy('nome')->get();
        }
        
        return view('scadenze.idoneita', compact('data', 'compagnie', 'userCompagniaId'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        // Verifica permessi: l'utente può modificare solo militari della sua compagnia
        $user = Auth::user();
        if ($user && !$user->hasRole('admin') && !$user->hasRole('amministratore')) {
            if ($user->compagnia_id && $militare->compagnia_id !== $user->compagnia_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi per modificare questo militare'
                ], 403);
            }
        }
        
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
        
        // Calcola la nuova scadenza (tutte le idoneità hanno durata 1 anno)
        $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, 1);
        
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
     * Esporta i dati Idoneità in Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Query base
            $query = Militare::with(['grado', 'compagnia', 'scadenza']);
            
            // FILTRO PERMESSI: Se l'utente non è admin, filtra per la sua compagnia
            $user = Auth::user();
            if ($user && !$user->hasRole('admin') && !$user->hasRole('amministratore')) {
                if ($user->compagnia_id) {
                    $query->where('compagnia_id', $user->compagnia_id);
                }
            }
            
            // Filtro compagnia
            if ($request->filled('compagnia_id')) {
                $query->where('compagnia_id', $request->compagnia_id);
            }
            
            $militari = $query->orderByGradoENome()->get();
            
            // Stile header
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0a2342']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            
            // Titolo
            $sheet->setCellValue('A1', 'SCADENZE IDONEITÀ SANITARIE');
            $sheet->mergeCells('A1:M1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension('1')->setRowHeight(25);
            
            // Header colonne
            $headers = ['N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 
                        'IDONEITÀ MANS. CONS.', 'IDONEITÀ MANS. SCAD.', 
                        'IDONEITÀ SMI CONS.', 'IDONEITÀ SMI SCAD.', 
                        'ECG CONS.', 'ECG SCAD.',
                        'PRELIEVI CONS.', 'PRELIEVI SCAD.'];
            
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
                
                // Idoneità Mansione
                $this->addScadenzaToSheet($sheet, $row, 'F', $scadenza?->idoneita_mans_data_conseguimento, 1);
                
                // Idoneità SMI
                $this->addScadenzaToSheet($sheet, $row, 'H', $scadenza?->idoneita_smi_data_conseguimento, 1);
                
                // ECG
                $this->addScadenzaToSheet($sheet, $row, 'J', $scadenza?->ecg_data_conseguimento, 1);
                
                // Prelievi
                $this->addScadenzaToSheet($sheet, $row, 'L', $scadenza?->prelievi_data_conseguimento, 1);
                
                // Bordi
                $sheet->getStyle('A' . $row . ':M' . $row)->applyFromArray([
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
            $sheet->getColumnDimension('F')->setWidth(25);  // IDONEITÀ MANS. CONS.
            $sheet->getColumnDimension('G')->setWidth(25);  // IDONEITÀ MANS. SCAD.
            $sheet->getColumnDimension('H')->setWidth(25);  // IDONEITÀ SMI CONS.
            $sheet->getColumnDimension('I')->setWidth(25);  // IDONEITÀ SMI SCAD.
            $sheet->getColumnDimension('J')->setWidth(18);  // ECG CONS.
            $sheet->getColumnDimension('K')->setWidth(18);  // ECG SCAD.
            $sheet->getColumnDimension('L')->setWidth(20);  // PRELIEVI CONS.
            $sheet->getColumnDimension('M')->setWidth(20);  // PRELIEVI SCAD.
            
            // Salva
            $filename = 'Scadenze_Idoneita_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'idoneita_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Idoneità', [
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
        
        // Verifica se è prenotato (data conseguimento nel futuro)
        $isPrenotato = $data->isFuture();
        
        $coloreFondo = 'FFFFFF'; // Default bianco
        if ($isPrenotato) {
            $coloreFondo = '87CEEB'; // Blu chiaro - prenotato
        } elseif ($giorniRimanenti < 0) {
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
