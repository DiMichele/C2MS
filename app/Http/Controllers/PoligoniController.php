<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaMilitare;
use App\Models\ScadenzaPoligono;
use App\Models\TipoPoligono;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExcelStyleService;
use Illuminate\Support\Facades\Log;

class PoligoniController extends Controller
{
    /**
     * Mapping tra codici tipi_poligono e campi della tabella scadenze_militari
     * Solo questi 3 tipi hanno campi legacy nella tabella scadenze_militari
     */
    private const CAMPO_MAPPING_LEGACY = [
        'teatro_operativo' => 'tiri_approntamento_data_conseguimento',
        'mantenimento_arma_lunga' => 'mantenimento_arma_lunga_data_conseguimento',
        'mantenimento_arma_corta' => 'mantenimento_arma_corta_data_conseguimento',
    ];
    
    /**
     * Verifica se la tabella scadenze_poligoni esiste
     */
    private function tabellaScadenzePoligoniEsiste(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable('scadenze_poligoni');
    }
    
    /**
     * Visualizza la pagina Poligoni
     * Se la tabella scadenze_poligoni non esiste, mostra solo i 3 tipi standard
     */
    public function index(Request $request)
    {
        // Verifica se la tabella scadenze_poligoni esiste
        $tabellaEsiste = $this->tabellaScadenzePoligoniEsiste();
        
        // ARCHITETTURA: Il Global Scope (CompagniaScope) filtra già automaticamente
        // i militari visibili (owner + acquired). NON aggiungere where compagnia_id qui!
        $query = Militare::withVisibilityFlags()
            ->with(['scadenza', 'compagnia', 'grado']);
        
        // Carica scadenzePoligoni solo se la tabella esiste
        if ($tabellaEsiste) {
            $query->with('scadenzePoligoni.tipoPoligono');
        }
        
        // Filtro compagnia esplicito (per admin che vogliono filtrare ulteriormente)
        if ($request->filled('compagnia_id')) {
            $query->where('compagnia_id', $request->compagnia_id);
        }
        
        // Ottieni tutti i militari con le loro scadenze
        $militari = $query->orderByGradoENome()->get();
        
        // Recupera i tipi di poligono
        if ($tabellaEsiste) {
            // Tutti i tipi attivi
            $tipiPoligono = TipoPoligono::where('attivo', true)
                ->orderBy('ordine')
                ->get();
        } else {
            // Solo i 3 tipi standard (quelli con campi legacy)
            $tipiPoligono = TipoPoligono::whereIn('codice', array_keys(self::CAMPO_MAPPING_LEGACY))
                ->where('attivo', true)
                ->orderBy('ordine')
                ->get();
        }
        
        // Costruisci le colonne da mostrare
        $colonne = $tipiPoligono->map(function ($tipo) use ($tabellaEsiste) {
            // Verifica se questo tipo ha un campo legacy nella tabella scadenze_militari
            $campoLegacy = self::CAMPO_MAPPING_LEGACY[$tipo->codice] ?? null;
            
            return [
                'id' => $tipo->id,
                'codice' => $tipo->codice,
                'nome' => $tipo->nome,
                'campo_db' => $campoLegacy, // null per i nuovi tipi
                'durata_mesi' => $tipo->durata_mesi,
                'usa_legacy' => $campoLegacy !== null,
            ];
        });
        
        // Calcola le scadenze per ogni militare
        $data = $militari->map(function ($militare) use ($colonne, $tabellaEsiste) {
            $scadenzaLegacy = $militare->scadenza;
            $scadenzePoligoni = $tabellaEsiste ? $militare->scadenzePoligoni->keyBy('tipo_poligono_id') : collect();
            $result = ['militare' => $militare];
            
            foreach ($colonne as $col) {
                if ($col['usa_legacy'] && $scadenzaLegacy) {
                    // Usa il campo legacy dalla tabella scadenze_militari
                    $campo = $col['campo_db'];
                    $result[$col['codice']] = $this->calcolaScadenza(
                        $scadenzaLegacy->$campo, 
                        $col['durata_mesi'], 
                        'mesi'
                    );
                } elseif ($tabellaEsiste) {
                    // Usa la tabella scadenze_poligoni per i nuovi tipi
                    $scadenzaPoligono = $scadenzePoligoni->get($col['id']);
                    $result[$col['codice']] = $this->calcolaScadenza(
                        $scadenzaPoligono?->data_conseguimento, 
                        $col['durata_mesi'], 
                        'mesi'
                    );
                } else {
                    // Tabella non esiste e non è un tipo legacy - non dovrebbe accadere
                    $result[$col['codice']] = $this->calcolaScadenza(null, $col['durata_mesi'], 'mesi');
                }
            }
            
            return $result;
        });
        
        return view('scadenze.poligoni', compact('data', 'colonne'));
    }
    
    /**
     * Aggiorna una singola scadenza via AJAX
     */
    public function updateSingola(Request $request, Militare $militare)
    {
        try {
            $request->validate([
                'campo' => 'required|string',
                'data' => 'nullable|date',
            ]);
            
            $campo = $request->campo;
            
            // Verifica se è un campo legacy (dalla tabella scadenze_militari)
            $campiLegacy = array_values(self::CAMPO_MAPPING_LEGACY);
            
            // Usa una transazione per evitare race conditions
            return \DB::transaction(function () use ($militare, $campo, $request, $campiLegacy) {
                if (in_array($campo, $campiLegacy)) {
                    // Campo legacy - usa la tabella scadenze_militari
                    $scadenza = $militare->scadenza;
                    if (!$scadenza) {
                        $scadenza = new ScadenzaMilitare();
                        $scadenza->militare_id = $militare->id;
                    }
                    
                    $scadenza->$campo = $request->data;
                    $scadenza->save();
                    
                    // Trova la durata corretta dal mapping
                    $tipoPoligono = TipoPoligono::where('codice', array_search($campo, self::CAMPO_MAPPING_LEGACY))->first();
                    $durataMesi = $tipoPoligono ? $tipoPoligono->durata_mesi : 6;
                    
                    // Calcola la nuova scadenza
                    $scadenzaCalcolata = $this->calcolaScadenza($scadenza->$campo, $durataMesi, 'mesi');
                } else {
                    // Nuovo tipo - usa la tabella scadenze_poligoni
                    // Il campo dovrebbe essere nel formato "tipo_poligono_ID"
                    $tipoPoligonoId = str_replace('tipo_poligono_', '', $campo);
                    
                    if (!is_numeric($tipoPoligonoId)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Campo non valido'
                        ], 400);
                    }
                    
                    $tipoPoligono = TipoPoligono::find($tipoPoligonoId);
                    if (!$tipoPoligono) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Tipo poligono non trovato'
                        ], 404);
                    }
                    
                    // Cerca o crea la scadenza per questo tipo con lock per evitare race conditions
                    $scadenzaPoligono = ScadenzaPoligono::lockForUpdate()
                        ->where('militare_id', $militare->id)
                        ->where('tipo_poligono_id', $tipoPoligonoId)
                        ->first();
                    
                    if (!$scadenzaPoligono) {
                        $scadenzaPoligono = new ScadenzaPoligono();
                        $scadenzaPoligono->militare_id = $militare->id;
                        $scadenzaPoligono->tipo_poligono_id = $tipoPoligonoId;
                    }
                    
                    $scadenzaPoligono->data_conseguimento = $request->data;
                    $scadenzaPoligono->save();
                    
                    // Calcola la nuova scadenza
                    $scadenzaCalcolata = $this->calcolaScadenza($request->data, $tipoPoligono->durata_mesi, 'mesi');
                }
                
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
            Log::error('Errore aggiornamento scadenza poligono', [
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
            // Usa il servizio per stili Excel
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Scadenze Poligoni');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Poligoni');
            
            // Ottieni i militari con le loro scadenze
            $query = Militare::with(['grado', 'compagnia', 'scadenza']);
            
            // Se sono stati passati ID specifici (export filtrato), filtra per questi
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
            }
            
            $militari = $query->orderByGradoENome()->get();
            
            // Titolo principale
            $excelService->applyTitleStyle($sheet, 'A1:K1', 'SCADENZE POLIGONI - QUALIFICHE TIRO');
            
            // Header colonne (riga 2)
            $headers = ['N.', 'COMP.', 'GRADO', 'COGNOME', 'NOME', 
                        'TIRI APPR. CONS.', 'TIRI APPR. SCAD.', 
                        'MANT. AL CONS.', 'MANT. AL SCAD.', 
                        'MANT. AC CONS.', 'MANT. AC SCAD.'];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            $excelService->applyHeaderStyle($sheet, 'A2:K2');
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
                
                // Tiri Approntamento (6 mesi)
                $excelService->addScadenzaRow($sheet, $row, 'F', $scadenza?->tiri_approntamento_data_conseguimento, 6, 'mesi');
                
                // Mantenimento Arma Lunga (6 mesi)
                $excelService->addScadenzaRow($sheet, $row, 'H', $scadenza?->mantenimento_arma_lunga_data_conseguimento, 6, 'mesi');
                
                // Mantenimento Arma Corta (6 mesi)
                $excelService->addScadenzaRow($sheet, $row, 'J', $scadenza?->mantenimento_arma_corta_data_conseguimento, 6, 'mesi');
                
                $row++;
            }
            
            // Stile dati generali
            if ($row > 3) {
                $excelService->applyDataStyle($sheet, 'A3:E' . ($row - 1));
            }
            
            // Larghezze colonne
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(16);
            $sheet->getColumnDimension('C')->setWidth(14);
            $sheet->getColumnDimension('D')->setWidth(18);
            $sheet->getColumnDimension('E')->setWidth(16);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(15);
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(15);
            
            // Legenda
            $excelService->addLegenda($sheet, $row + 1);
            
            // Data generazione
            $excelService->addGenerationInfo($sheet, $row + 3);
            
            // Freeze header
            $excelService->freezeHeader($sheet, 2);
            
            // Salva
            $filename = 'Scadenze_Poligoni_' . date('Y-m-d_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'poligoni_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            Log::error('Errore export Excel Poligoni', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel.');
        }
    }
}
