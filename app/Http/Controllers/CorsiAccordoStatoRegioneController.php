<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\ConfigurazioneCorsoSpp;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExcelStyleService;
use Illuminate\Support\Facades\Log;

class CorsiAccordoStatoRegioneController extends Controller
{
    /**
     * Visualizza la pagina Corsi Accordo Stato Regione
     */
    public function index(Request $request)
    {
        // Recupera tutti i corsi attivi di tipo 'accordo_stato_regione' ordinati
        $corsi = ConfigurazioneCorsoSpp::attivi()
            ->perTipo('accordo_stato_regione')
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
        
        return view('scadenze.corsi-accordo-stato-regione', compact('data', 'corsi'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        try {
            $request->validate([
                'corso_id' => 'required|exists:configurazione_corsi_spp,id',
                'data' => 'nullable|date',
            ]);
            
            $corsoId = $request->corso_id;
            $data = $request->data;
            
            // Usa una transazione per evitare race conditions
            return \DB::transaction(function () use ($militare, $corsoId, $data) {
                // Cerca con lock per evitare race conditions
                $scadenzaCorso = \App\Models\ScadenzaCorsoSpp::lockForUpdate()
                    ->where('militare_id', $militare->id)
                    ->where('configurazione_corso_spp_id', $corsoId)
                    ->first();
                
                if (!$scadenzaCorso) {
                    $scadenzaCorso = new \App\Models\ScadenzaCorsoSpp();
                    $scadenzaCorso->militare_id = $militare->id;
                    $scadenzaCorso->configurazione_corso_spp_id = $corsoId;
                }
                
                $scadenzaCorso->data_conseguimento = $data;
                $scadenzaCorso->save();
                
                // Calcola e restituisci la nuova scadenza
                $scadenzaCalcolata = $scadenzaCorso->calcolaScadenza();
                
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
            Log::error('Errore aggiornamento scadenza Corsi Accordo Stato Regione', [
                'militare_id' => $militare->id,
                'corso_id' => $request->corso_id ?? 'N/A',
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
     * Esporta i dati Corsi Accordo Stato Regione in Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            // Usa il servizio per stili Excel
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Corsi Accordo Stato Regione');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Corsi ASR');
            
            // Ottieni i militari con le loro scadenze
            $query = Militare::with(['grado', 'compagnia', 'scadenza']);
            
            // Se sono stati passati ID specifici (export filtrato), filtra per questi
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
            }
            
            $militari = $query->orderByGradoENome()->get();
            
            // Titolo principale
            $excelService->applyTitleStyle($sheet, 'A1:S1', 'CORSI ACCORDO STATO REGIONE');
            
            // Header colonne (riga 2)
            $headers = [
                'N.', 'COMP.', 'GRADO', 'COGNOME', 'NOME',
                'TRATT. CONS.', 'TRATT. SCAD.',
                'MMT CONS.', 'MMT SCAD.',
                'MULET. CONS.', 'MULET. SCAD.',
                'PLE CONS.', 'PLE SCAD.',
                'MOTOS. CONS.', 'MOTOS. SCAD.',
                'FUNI C.', 'FUNI S.',
                'RLS CONS.', 'RLS SCAD.'
            ];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            $excelService->applyHeaderStyle($sheet, 'A2:S2');
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
                
                // Abilitazione Trattori (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'F', $scadenza?->abilitazione_trattori_data_conseguimento, 5);
                
                // Abilitazione MMT (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'H', $scadenza?->abilitazione_mmt_data_conseguimento, 5);
                
                // Abilitazione Muletto (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'J', $scadenza?->abilitazione_muletto_data_conseguimento, 5);
                
                // Abilitazione PLE (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'L', $scadenza?->abilitazione_ple_data_conseguimento, 5);
                
                // Corso Motosega (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'N', $scadenza?->corso_motosega_data_conseguimento, 5);
                
                // Addetti Funi e Catene (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'P', $scadenza?->addetti_funi_catene_data_conseguimento, 5);
                
                // Corso RLS (5 anni)
                $excelService->addScadenzaRow($sheet, $row, 'R', $scadenza?->corso_rls_data_conseguimento, 5);
                
                $row++;
            }
            
            // Stile dati generali
            if ($row > 3) {
                $excelService->applyDataStyle($sheet, 'A3:E' . ($row - 1));
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(6);   // N.
            $sheet->getColumnDimension('B')->setWidth(16);  // Compagnia
            $sheet->getColumnDimension('C')->setWidth(14);  // Grado
            $sheet->getColumnDimension('D')->setWidth(18);  // Cognome
            $sheet->getColumnDimension('E')->setWidth(16);  // Nome
            
            // Date colonne
            for ($i = ord('F'); $i <= ord('S'); $i++) {
                $sheet->getColumnDimension(chr($i))->setWidth(13);
            }
            
            // Legenda
            $excelService->addLegenda($sheet, $row + 1);
            
            // Data generazione
            $excelService->addGenerationInfo($sheet, $row + 3);
            
            // Freeze header
            $excelService->freezeHeader($sheet, 2);
            
            // Salva
            $filename = 'Corsi_Accordo_Stato_Regione_' . date('Y-m-d_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'corsi_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Corsi Accordo Stato Regione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
}
