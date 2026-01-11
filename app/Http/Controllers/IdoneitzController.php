<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\Compagnia;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
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
        // ARCHITETTURA: Il Global Scope (CompagniaScope) filtra già automaticamente
        // i militari visibili (owner + acquired). NON aggiungere where compagnia_id qui!
        $user = Auth::user();
        $userCompagniaId = $user->compagnia_id ?? null;
        $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('amministratore'));
        
        $query = Militare::withVisibilityFlags()
            ->with(['scadenza', 'compagnia', 'grado']);
        
        // Filtro compagnia esplicito (solo admin può filtrare per qualsiasi compagnia)
        if ($request->filled('compagnia_id') && $isAdmin) {
            $query->where('compagnia_id', $request->compagnia_id);
        }
        
        // Ordinamento
        $militari = $query->orderByGradoENome()->get();
        
        // Ottieni la colonna "operazioni" (Teatro Operativo) dalla board
        $colonnaTO = BoardColumn::where('slug', 'operazioni')->first();
        
        // Ottieni tutte le attività T.O. attive o future con i militari assegnati
        $attivitaTO = collect();
        if ($colonnaTO) {
            $attivitaTO = BoardActivity::where('column_id', $colonnaTO->id)
                ->where(function($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', Carbon::today());
                })
                ->with('militari')
                ->get();
        }
        
        // Crea una mappa militare_id => attività T.O.
        $militariTO = [];
        foreach ($attivitaTO as $attivita) {
            foreach ($attivita->militari as $m) {
                if (!isset($militariTO[$m->id])) {
                    $militariTO[$m->id] = [];
                }
                $militariTO[$m->id][] = [
                    'id' => $attivita->id,
                    'title' => $attivita->title,
                    'start_date' => $attivita->start_date,
                    'end_date' => $attivita->end_date,
                ];
            }
        }
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) use ($militariTO) {
            $scadenza = $militare->scadenza;
            
            return [
                'militare' => $militare,
                'idoneita_mansione' => $this->calcolaScadenza($scadenza?->idoneita_mans_data_conseguimento, 1), // 1 anno
                'idoneita_smi' => $this->calcolaScadenza($scadenza?->idoneita_smi_data_conseguimento, 1), // 1 anno
                'ecg' => $this->calcolaScadenza($scadenza?->ecg_data_conseguimento, 1), // 1 anno
                'prelievi' => $this->calcolaScadenza($scadenza?->prelievi_data_conseguimento, 1), // 1 anno
                'teatro_operativo' => $militariTO[$militare->id] ?? [],
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
        // VERIFICA PERMESSI via Policy (single source of truth)
        // La policy verifica che sia owner (non acquired) E abbia permessi
        try {
            $this->authorize('update', $militare);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai i permessi per modificare questo militare. I militari acquisiti sono in sola lettura.'
            ], 403);
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
            
            // ARCHITETTURA: Il Global Scope (CompagniaScope) filtra già automaticamente
            // i militari visibili (owner + acquired). NON aggiungere where compagnia_id qui!
            $user = Auth::user();
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('amministratore'));
            
            $query = Militare::with(['grado', 'compagnia', 'scadenza']);
            
            // Filtro compagnia esplicito (solo admin può filtrare per qualsiasi compagnia)
            if ($request->filled('compagnia_id') && $isAdmin) {
                $query->where('compagnia_id', $request->compagnia_id);
            }
            
            $militari = $query->orderByGradoENome()->get();
            
            // Ottieni attività T.O. per l'export
            $colonnaTO = BoardColumn::where('slug', 'operazioni')->first();
            $attivitaTO = collect();
            if ($colonnaTO) {
                $attivitaTO = BoardActivity::where('column_id', $colonnaTO->id)
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', Carbon::today());
                    })
                    ->with('militari')
                    ->get();
            }
            
            // Mappa militare_id => attività T.O.
            $militariTO = [];
            foreach ($attivitaTO as $attivita) {
                foreach ($attivita->militari as $m) {
                    if (!isset($militariTO[$m->id])) {
                        $militariTO[$m->id] = [];
                    }
                    $militariTO[$m->id][] = $attivita->title . ' (' . $attivita->start_date->format('d/m/Y') . ')';
                }
            }
            
            // Stile header
            $headerStyle = [
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0a2342']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];
            
            // Titolo
            $sheet->setCellValue('A1', 'SCADENZE IDONEITÀ SANITARIE');
            $sheet->mergeCells('A1:N1');
            $sheet->getStyle('A1')->applyFromArray($headerStyle);
            $sheet->getRowDimension('1')->setRowHeight(25);
            
            // Header colonne
            $headers = ['N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'TEATRO OPERATIVO',
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
                
                // Teatro Operativo
                $toList = $militariTO[$militare->id] ?? [];
                $sheet->setCellValue('F' . $row, implode("\n", $toList));
                if (count($toList) > 0) {
                    $sheet->getStyle('F' . $row)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCDD2']],
                    ]);
                }
                $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
                
                // Idoneità Mansione
                $this->addScadenzaToSheet($sheet, $row, 'G', $scadenza?->idoneita_mans_data_conseguimento, 1);
                
                // Idoneità SMI
                $this->addScadenzaToSheet($sheet, $row, 'I', $scadenza?->idoneita_smi_data_conseguimento, 1);
                
                // ECG
                $this->addScadenzaToSheet($sheet, $row, 'K', $scadenza?->ecg_data_conseguimento, 1);
                
                // Prelievi
                $this->addScadenzaToSheet($sheet, $row, 'M', $scadenza?->prelievi_data_conseguimento, 1);
                
                // Bordi
                $sheet->getStyle('A' . $row . ':N' . $row)->applyFromArray([
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
            $sheet->getColumnDimension('F')->setWidth(35);  // Teatro Operativo
            
            // Date - larghezza aumentata per intestazioni lunghe
            $sheet->getColumnDimension('G')->setWidth(25);  // IDONEITÀ MANS. CONS.
            $sheet->getColumnDimension('H')->setWidth(25);  // IDONEITÀ MANS. SCAD.
            $sheet->getColumnDimension('I')->setWidth(25);  // IDONEITÀ SMI CONS.
            $sheet->getColumnDimension('J')->setWidth(25);  // IDONEITÀ SMI SCAD.
            $sheet->getColumnDimension('K')->setWidth(18);  // ECG CONS.
            $sheet->getColumnDimension('L')->setWidth(18);  // ECG SCAD.
            $sheet->getColumnDimension('M')->setWidth(20);  // PRELIEVI CONS.
            $sheet->getColumnDimension('N')->setWidth(20);  // PRELIEVI SCAD.
            
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
