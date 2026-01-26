<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\Compagnia;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExcelStyleService;
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
                'idoneita_to' => $this->calcolaScadenza($scadenza?->idoneita_to_data_conseguimento, 1), // 1 anno - Idoneità T.O.
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
        
        try {
            $request->validate([
                'campo' => 'required|string',
                'data' => 'nullable|date',
            ]);
            
            $campo = $request->campo;
            
            // Usa una transazione per evitare race conditions
            return \DB::transaction(function () use ($militare, $campo, $request) {
                // Ottieni o crea il record scadenza con lock
                $scadenza = ScadenzaMilitare::lockForUpdate()
                    ->where('militare_id', $militare->id)
                    ->first();
                    
                if (!$scadenza) {
                    $scadenza = new ScadenzaMilitare();
                    $scadenza->militare_id = $militare->id;
                }
                
                $scadenza->$campo = $request->data;
                $scadenza->save();
                
                // Calcola la nuova scadenza (tutte le idoneità hanno durata 1 anno)
                $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, 1);
                
                return response()->json([
                    'success' => true,
                    'scadenza' => $scadenzaCalcolata,
                ]);
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi: ' . implode(', ', $e->errors()['data'] ?? ['errore sconosciuto'])
            ], 422);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento scadenza idoneità', [
                'militare_id' => $militare->id,
                'campo' => $request->campo ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio. Riprova.'
            ], 500);
        }
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
            // Usa il servizio per stili Excel
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Scadenze Idoneità Sanitarie');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Idoneità Sanitarie');
            
            // ARCHITETTURA: Il Global Scope (CompagniaScope) filtra già automaticamente
            // i militari visibili (owner + acquired). NON aggiungere where compagnia_id qui!
            $user = Auth::user();
            $isAdmin = $user && ($user->hasRole('admin') || $user->hasRole('amministratore'));
            
            $query = Militare::with(['grado', 'compagnia', 'scadenza']);
            
            // Filtro compagnia esplicito (solo admin può filtrare per qualsiasi compagnia)
            if ($request->filled('compagnia_id') && $isAdmin) {
                $query->where('compagnia_id', $request->compagnia_id);
            }
            
            // Se sono stati passati ID specifici (export filtrato), filtra per questi
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
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
            
            // Titolo principale
            $excelService->applyTitleStyle($sheet, 'A1:N1', 'SCADENZE IDONEITÀ SANITARIE');
            
            // Header colonne (riga 2)
            $headers = ['N.', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'TEATRO OP.',
                        'ID. MANS. CONS.', 'ID. MANS. SCAD.', 
                        'ID. SMI CONS.', 'ID. SMI SCAD.', 
                        'ECG CONS.', 'ECG SCAD.',
                        'PRELIEVI CONS.', 'PRELIEVI SCAD.'];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            $excelService->applyHeaderStyle($sheet, 'A2:N2');
            $sheet->getRowDimension('2')->setRowHeight(35);
            
            // Dati militari (riga 3 in poi)
            $row = 3;
            $num = 1;
            
            foreach ($militari as $militare) {
                $scadenza = $militare->scadenza;
                
                $sheet->setCellValue('A' . $row, $num++);
                $sheet->setCellValue('B' . $row, $militare->compagnia->nome ?? '-');
                $sheet->setCellValue('C' . $row, $militare->grado->sigla ?? '');
                $sheet->setCellValue('D' . $row, strtoupper($militare->cognome));
                $sheet->setCellValue('E' . $row, strtoupper($militare->nome));
                
                // Teatro Operativo - badge rosso se presente
                $toList = $militariTO[$militare->id] ?? [];
                if (count($toList) > 0) {
                    $sheet->setCellValue('F' . $row, implode("\n", $toList));
                    $excelService->applyBadgeStyle($sheet, 'F' . $row, 'danger');
                    $sheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
                } else {
                    $sheet->setCellValue('F' . $row, '-');
                }
                
                // Idoneità Mansione
                $excelService->addScadenzaRow($sheet, $row, 'G', $scadenza?->idoneita_mans_data_conseguimento, 1);
                
                // Idoneità SMI
                $excelService->addScadenzaRow($sheet, $row, 'I', $scadenza?->idoneita_smi_data_conseguimento, 1);
                
                // ECG
                $excelService->addScadenzaRow($sheet, $row, 'K', $scadenza?->ecg_data_conseguimento, 1);
                
                // Prelievi
                $excelService->addScadenzaRow($sheet, $row, 'M', $scadenza?->prelievi_data_conseguimento, 1);
                
                $row++;
            }
            
            // Stile dati generali
            if ($row > 3) {
                $excelService->applyDataStyle($sheet, 'A3:F' . ($row - 1));
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(6);   // N.
            $sheet->getColumnDimension('B')->setWidth(18);  // Compagnia
            $sheet->getColumnDimension('C')->setWidth(14);  // Grado
            $sheet->getColumnDimension('D')->setWidth(18);  // Cognome
            $sheet->getColumnDimension('E')->setWidth(16);  // Nome
            $sheet->getColumnDimension('F')->setWidth(28);  // Teatro Operativo
            $sheet->getColumnDimension('G')->setWidth(14);  // ID. MANS. CONS.
            $sheet->getColumnDimension('H')->setWidth(14);  // ID. MANS. SCAD.
            $sheet->getColumnDimension('I')->setWidth(14);  // ID. SMI CONS.
            $sheet->getColumnDimension('J')->setWidth(14);  // ID. SMI SCAD.
            $sheet->getColumnDimension('K')->setWidth(14);  // ECG CONS.
            $sheet->getColumnDimension('L')->setWidth(14);  // ECG SCAD.
            $sheet->getColumnDimension('M')->setWidth(14);  // PRELIEVI CONS.
            $sheet->getColumnDimension('N')->setWidth(14);  // PRELIEVI SCAD.
            
            // Legenda
            $excelService->addLegenda($sheet, $row + 1);
            
            // Data generazione
            $excelService->addGenerationInfo($sheet, $row + 3);
            
            // Freeze header
            $excelService->freezeHeader($sheet, 2);
            
            // Salva
            $filename = 'Scadenze_Idoneita_' . date('Y-m-d_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'idoneita_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Idoneità', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
}
