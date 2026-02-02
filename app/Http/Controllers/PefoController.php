<?php

namespace App\Http\Controllers;

use App\Models\PrenotazionePefo;
use App\Models\Militare;
use App\Models\Compagnia;
use App\Models\Grado;
use App\Models\OrganizationalUnit;
use App\Services\PrenotazionePefoService;
use App\Services\ExcelStyleService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Controller per la gestione della pagina PEFO
 * 
 * Gestisce sia la tabella militari con date PEFO che le prenotazioni PEFO.
 */
class PefoController extends Controller
{
    protected PrenotazionePefoService $pefoService;

    public function __construct(PrenotazionePefoService $pefoService)
    {
        $this->pefoService = $pefoService;
    }

    /**
     * Vista principale pagina PEFO
     */
    public function index()
    {
        // Carica le compagnie per i filtri
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Carica i gradi per i filtri
        $gradi = Grado::orderBy('ordine', 'desc')->get();
        
        // Carica le unità organizzative per i filtri (battaglioni/unità principali)
        try {
            $unita = OrganizationalUnit::whereHas('type', function($q) {
                $q->where('level', '<=', 2); // Solo livelli alti (Battaglioni, Reggimenti)
            })->orderBy('name')->get();
        } catch (\Exception $e) {
            $unita = collect(); // Collection vuota se errore
        }

        // Verifica permessi - con fallback per admin
        $user = auth()->user();
        $canEdit = $user->hasPermission('pefo.edit') || $user->hasPermission('admin.access');
        $canGestisciPrenotazioni = $user->hasPermission('pefo.gestione_prenotazioni') || $user->hasPermission('admin.access');

        return view('pefo.index', compact(
            'compagnie',
            'gradi',
            'unita',
            'canEdit',
            'canGestisciPrenotazioni'
        ));
    }

    /**
     * API: Ottiene i militari con filtri
     */
    public function getMilitari(Request $request): JsonResponse
    {
        try {
            // withoutGlobalScopes per vedere tutti i militari (pagina globale)
            $query = Militare::withoutGlobalScopes()
                ->with(['grado', 'compagnia', 'organizationalUnit.parent']);

            // Filtro ricerca
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cognome', 'like', "%{$search}%")
                      ->orWhere('nome', 'like', "%{$search}%");
                });
            }

            // Filtro compagnia
            if ($request->filled('compagnia_id')) {
                $query->where('compagnia_id', $request->compagnia_id);
            }

            // Filtro grado
            if ($request->filled('grado_id')) {
                $query->where('grado_id', $request->grado_id);
            }

            // Filtro unità organizzativa
            if ($request->filled('unit_id')) {
                $unitId = $request->unit_id;
                // Include tutti i discendenti dell'unità
                $query->where(function($q) use ($unitId) {
                    $q->where('organizational_unit_id', $unitId)
                      ->orWhereHas('organizationalUnit', function($sq) use ($unitId) {
                          $sq->whereHas('ancestors', function($asq) use ($unitId) {
                              $asq->where('ancestor_id', $unitId);
                          });
                      });
                });
            }

            // Filtro stato Agility (validità per anno solare)
            $annoCorrente = Carbon::now()->year;
            if ($request->filled('stato_agility')) {
                switch ($request->stato_agility) {
                    case 'valido':
                        $query->whereNotNull('data_agility')
                              ->whereYear('data_agility', '>=', $annoCorrente);
                        break;
                    case 'mancante':
                        $query->whereNull('data_agility');
                        break;
                    case 'scaduto':
                        $query->whereNotNull('data_agility')
                              ->whereYear('data_agility', '<', $annoCorrente);
                        break;
                }
            }

            // Filtro stato Resistenza (validità per anno solare)
            if ($request->filled('stato_resistenza')) {
                switch ($request->stato_resistenza) {
                    case 'valido':
                        $query->whereNotNull('data_resistenza')
                              ->whereYear('data_resistenza', '>=', $annoCorrente);
                        break;
                    case 'mancante':
                        $query->whereNull('data_resistenza');
                        break;
                    case 'scaduto':
                        $query->whereNotNull('data_resistenza')
                              ->whereYear('data_resistenza', '<', $annoCorrente);
                        break;
                }
            }

            // Ordinamento semplice per cognome e nome
            $query->orderBy('cognome')->orderBy('nome');
            
            $militari = $query->get();

            // Formatta i dati per il frontend
            $annoCorrente = Carbon::now()->year;
            
            $data = $militari->map(function($militare) use ($annoCorrente) {
                // Calcola l'unità padre (battaglione) risalendo la gerarchia
                $unita = null;
                if ($militare->organizationalUnit) {
                    $currentUnit = $militare->organizationalUnit;
                    
                    // Risali la gerarchia fino a trovare un Battaglione o l'unità di livello più alto
                    while ($currentUnit) {
                        // Se è un Battaglione, usalo
                        if ($currentUnit->type && 
                            (stripos($currentUnit->type->name, 'battaglione') !== false || 
                             stripos($currentUnit->name, 'battaglione') !== false)) {
                            $unita = $currentUnit->name;
                            break;
                        }
                        
                        // Se ha un parent, continua a risalire
                        if ($currentUnit->parent) {
                            $currentUnit = $currentUnit->parent;
                        } else {
                            // Nessun parent trovato, usa l'unità corrente
                            $unita = $currentUnit->name;
                            break;
                        }
                    }
                    
                    // Fallback: se non trovato nulla, usa il nome dell'unità originale
                    if (!$unita) {
                        $unita = $militare->organizationalUnit->name;
                    }
                }

                // Calcola stato Agility - validità per anno solare
                $statoAgility = 'mancante';
                $annoAgility = null;
                if ($militare->data_agility) {
                    $annoAgility = $militare->data_agility->year;
                    $statoAgility = ($annoAgility >= $annoCorrente) ? 'valido' : 'scaduto';
                }

                // Calcola stato Resistenza - validità per anno solare
                $statoResistenza = 'mancante';
                $annoResistenza = null;
                if ($militare->data_resistenza) {
                    $annoResistenza = $militare->data_resistenza->year;
                    $statoResistenza = ($annoResistenza >= $annoCorrente) ? 'valido' : 'scaduto';
                }

                return [
                    'id' => $militare->id,
                    'cognome' => $militare->cognome,
                    'nome' => $militare->nome,
                    'grado' => $militare->grado ? $militare->grado->abbreviazione : '',
                    'grado_nome' => $militare->grado ? $militare->grado->nome : '',
                    'compagnia' => $militare->compagnia ? $militare->compagnia->nome : '',
                    'compagnia_id' => $militare->compagnia_id,
                    'unita' => $unita,
                    'data_nascita' => $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '',
                    'eta' => $militare->data_nascita ? $militare->data_nascita->age : null,
                    // Agility
                    'data_agility' => $militare->data_agility ? $militare->data_agility->format('d/m/Y') : null,
                    'data_agility_raw' => $militare->data_agility ? $militare->data_agility->format('Y-m-d') : null,
                    'stato_agility' => $statoAgility,
                    'anno_agility' => $annoAgility,
                    // Resistenza
                    'data_resistenza' => $militare->data_resistenza ? $militare->data_resistenza->format('d/m/Y') : null,
                    'data_resistenza_raw' => $militare->data_resistenza ? $militare->data_resistenza->format('Y-m-d') : null,
                    'stato_resistenza' => $statoResistenza,
                    'anno_resistenza' => $annoResistenza
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento dei militari: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Aggiorna la data Agility o Resistenza di un militare
     */
    public function updateDataPefo(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tipo' => 'required|in:agility,resistenza',
            'data' => 'nullable|date'
        ]);

        try {
            // Trova il militare senza Global Scopes (pagina globale)
            $militare = Militare::withoutGlobalScopes()->findOrFail($id);
            
            $tipo = $request->tipo;
            $campo = 'data_' . $tipo; // 'data_agility' o 'data_resistenza'
            
            $militare->update([
                $campo => $request->data
            ]);

            $tipoLabel = ucfirst($tipo);
            
            return response()->json([
                'success' => true,
                'message' => "Data {$tipoLabel} aggiornata con successo",
                'data' => [
                    'tipo' => $tipo,
                    'data' => $militare->$campo ? $militare->$campo->format('d/m/Y') : null,
                    'data_raw' => $militare->$campo ? $militare->$campo->format('Y-m-d') : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // PRENOTAZIONI PEFO
    // ==========================================

    /**
     * API: Ottiene le prenotazioni
     */
    public function getPrenotazioni(Request $request): JsonResponse
    {
        try {
            $query = PrenotazionePefo::with(['militari.grado'])
                ->orderBy('data_prenotazione', 'desc');

            // Filtro stato
            if ($request->filled('stato')) {
                if ($request->stato === 'attive') {
                    $query->attive();
                } else {
                    $query->where('stato', $request->stato);
                }
            }

            // Filtro tipo prova
            if ($request->filled('tipo_prova')) {
                $query->where('tipo_prova', $request->tipo_prova);
            }

            // Filtro data
            if ($request->filled('data_da')) {
                $query->where('data_prenotazione', '>=', $request->data_da);
            }
            if ($request->filled('data_a')) {
                $query->where('data_prenotazione', '<=', $request->data_a);
            }

            $prenotazioni = $query->get();

            // Formatta i dati per il frontend
            $data = $prenotazioni->map(function($prenotazione) {
                return [
                    'id' => $prenotazione->id,
                    'tipo_prova' => $prenotazione->tipo_prova,
                    'tipo_prova_label' => $prenotazione->getTipoProvaLabel(),
                    'nome' => $prenotazione->getNomeVisualizzato(),
                    'data_prenotazione' => $prenotazione->getDataFormattata(),
                    'data_prenotazione_raw' => $prenotazione->data_prenotazione->format('Y-m-d'),
                    'data_completa' => $prenotazione->getDataCompletaFormattata(),
                    'stato' => $prenotazione->stato,
                    'is_passata' => $prenotazione->isPassata(),
                    'is_futura' => $prenotazione->isFutura(),
                    'numero_militari' => $prenotazione->militari->count(),
                    'numero_militari_confermati' => $prenotazione->getNumeroMilitariConfermati(),
                    'numero_militari_da_confermare' => $prenotazione->getNumeroMilitariDaConfermare(),
                    'militari' => $prenotazione->militari->map(function($m) {
                        return [
                            'id' => $m->id,
                            'grado' => $m->grado ? $m->grado->abbreviazione : '',
                            'cognome' => $m->cognome,
                            'nome' => $m->nome,
                            'data_nascita' => $m->data_nascita ? $m->data_nascita->format('d/m/Y') : '',
                            'eta' => $m->data_nascita ? $m->data_nascita->age : null,
                            'confermato' => (bool)$m->pivot->confermato,
                            'data_conferma' => $m->pivot->data_conferma ? Carbon::parse($m->pivot->data_conferma)->format('d/m/Y H:i') : null
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'count' => $data->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento delle prenotazioni: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Crea una nuova prenotazione
     */
    public function storePrenotazione(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_prova' => 'required|in:agility,resistenza',
            'data_prenotazione' => 'required|date'
        ]);

        try {
            $prenotazione = $this->pefoService->creaPrenotazione($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Prenotazione creata con successo',
                'data' => [
                    'id' => $prenotazione->id,
                    'tipo_prova' => $prenotazione->tipo_prova,
                    'nome' => $prenotazione->getNomeVisualizzato(),
                    'data_prenotazione' => $prenotazione->getDataFormattata()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Aggiunge militari a una prenotazione
     */
    public function aggiungiMilitari(Request $request, PrenotazionePefo $prenotazione): JsonResponse
    {
        $request->validate([
            'militari_ids' => 'required|array',
            'militari_ids.*' => 'exists:militari,id'
        ]);

        try {
            $result = $this->pefoService->aggiungiMilitari($prenotazione, $request->militari_ids);

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    '%d militari aggiunti%s',
                    count($result['aggiunti']),
                    count($result['già_presenti']) > 0 ? ', ' . count($result['già_presenti']) . ' già presenti' : ''
                ),
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiunta militari: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Rimuove un militare da una prenotazione
     */
    public function rimuoviMilitare(PrenotazionePefo $prenotazione, int $militareId): JsonResponse
    {
        try {
            $this->pefoService->rimuoviMilitare($prenotazione, $militareId);

            return response()->json([
                'success' => true,
                'message' => 'Militare rimosso dalla prenotazione'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella rimozione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Conferma un singolo militare in una prenotazione
     */
    public function confermaMilitare(PrenotazionePefo $prenotazione, int $militareId): JsonResponse
    {
        try {
            $this->pefoService->confermaMilitare($prenotazione, $militareId);

            // Recupera info aggiornate del militare
            $militare = Militare::withoutGlobalScopes()->with('grado')->find($militareId);
            $pivotData = $prenotazione->militari()->where('militare_id', $militareId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Militare confermato con successo',
                'data' => [
                    'militare_id' => $militareId,
                    'confermato' => true,
                    'data_conferma' => $pivotData ? Carbon::parse($pivotData->pivot->data_conferma)->format('d/m/Y H:i') : null,
                    'numero_confermati' => $prenotazione->getNumeroMilitariConfermati(),
                    'numero_da_confermare' => $prenotazione->getNumeroMilitariDaConfermare()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella conferma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Conferma tutti i militari in una prenotazione
     */
    public function confermaAllMilitari(PrenotazionePefo $prenotazione): JsonResponse
    {
        try {
            $count = $this->pefoService->confermaAllMilitari($prenotazione);

            return response()->json([
                'success' => true,
                'message' => "{$count} militari confermati con successo",
                'data' => [
                    'militari_confermati' => $count,
                    'numero_confermati' => $prenotazione->getNumeroMilitariConfermati(),
                    'numero_da_confermare' => $prenotazione->getNumeroMilitariDaConfermare()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella conferma: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Elimina una prenotazione
     */
    public function eliminaPrenotazione(PrenotazionePefo $prenotazione): JsonResponse
    {
        try {
            $this->pefoService->eliminaPrenotazione($prenotazione);

            return response()->json([
                'success' => true,
                'message' => 'Prenotazione eliminata con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // EXPORT EXCEL
    // ==========================================

    /**
     * Export Excel tabella militari PEFO
     */
    public function exportMilitariExcel(Request $request)
    {
        try {
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Militari PEFO');
            $sheet = $spreadsheet->getActiveSheet();

            // Titolo
            $sheet->setCellValue('A1', 'ELENCO MILITARI - PEFO (AGILITY E RESISTENZA)');
            $sheet->mergeCells('A1:H1');
            $excelService->applyTitleStyle($sheet, 'A1:H1');

            // Header
            $headers = ['N.', 'UNITA\'', 'COMPAGNIA', 'GRADO', 'COGNOME', 'NOME', 'AGILITY', 'RESISTENZA'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            $excelService->applyHeaderStyle($sheet, 'A2:H2');

            // Dati
            $query = Militare::withoutGlobalScopes()
                ->with(['grado', 'compagnia', 'organizationalUnit.parent']);

            // Applica filtri se presenti (passati come query string)
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $query->whereIn('id', $ids);
            }

            $query->orderBy('cognome')->orderBy('nome');
            
            $militari = $query->get();
            $row = 3;
            $n = 1;

            foreach ($militari as $militare) {
                // Calcola l'unità padre (battaglione) risalendo la gerarchia
                $unita = '';
                if ($militare->organizationalUnit) {
                    $currentUnit = $militare->organizationalUnit;
                    
                    // Risali la gerarchia fino a trovare un Battaglione
                    while ($currentUnit) {
                        if ($currentUnit->type && 
                            (stripos($currentUnit->type->name, 'battaglione') !== false || 
                             stripos($currentUnit->name, 'battaglione') !== false)) {
                            $unita = $currentUnit->name;
                            break;
                        }
                        
                        if ($currentUnit->parent) {
                            $currentUnit = $currentUnit->parent;
                        } else {
                            $unita = $currentUnit->name;
                            break;
                        }
                    }
                    
                    if (!$unita) {
                        $unita = $militare->organizationalUnit->name;
                    }
                }

                $sheet->setCellValue('A' . $row, $n);
                $sheet->setCellValue('B' . $row, $unita);
                $sheet->setCellValue('C' . $row, $militare->compagnia ? $militare->compagnia->nome : '');
                $sheet->setCellValue('D' . $row, $militare->grado ? $militare->grado->abbreviazione : '');
                $sheet->setCellValue('E' . $row, $militare->cognome);
                $sheet->setCellValue('F' . $row, $militare->nome);
                
                // Agility
                $sheet->setCellValue('G' . $row, $militare->data_agility ? $militare->data_agility->format('d/m/Y') : 'N/A');
                if ($militare->data_agility) {
                    $annoAgility = $militare->data_agility->year;
                    $annoCorrente = Carbon::now()->year;
                    
                    if ($annoAgility >= $annoCorrente) {
                        // Valido (anno corrente o futuro)
                        $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('G' . $row)->getFill()->getStartColor()->setRGB('D4EDDA'); // Verde
                    } else {
                        // Scaduto (anno precedente)
                        $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('G' . $row)->getFill()->getStartColor()->setRGB('F8D7DA'); // Rosso
                    }
                } else {
                    $sheet->getStyle('G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle('G' . $row)->getFill()->getStartColor()->setRGB('E9ECEF'); // Grigio
                }
                
                // Resistenza
                $sheet->setCellValue('H' . $row, $militare->data_resistenza ? $militare->data_resistenza->format('d/m/Y') : 'N/A');
                if ($militare->data_resistenza) {
                    $annoResistenza = $militare->data_resistenza->year;
                    $annoCorrente = Carbon::now()->year;
                    
                    if ($annoResistenza >= $annoCorrente) {
                        // Valido (anno corrente o futuro)
                        $sheet->getStyle('H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('H' . $row)->getFill()->getStartColor()->setRGB('D4EDDA'); // Verde
                    } else {
                        // Scaduto (anno precedente)
                        $sheet->getStyle('H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                        $sheet->getStyle('H' . $row)->getFill()->getStartColor()->setRGB('F8D7DA'); // Rosso
                    }
                } else {
                    $sheet->getStyle('H' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle('H' . $row)->getFill()->getStartColor()->setRGB('E9ECEF'); // Grigio
                }

                $row++;
                $n++;
            }

            // Auto-size colonne
            foreach (range('A', 'H') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Bordi
            $lastRow = $row - 1;
            $sheet->getStyle('A2:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            // Info generazione
            $sheet->setCellValue('A' . ($row + 1), 'Generato il: ' . Carbon::now()->format('d/m/Y H:i'));
            $sheet->mergeCells('A' . ($row + 1) . ':H' . ($row + 1));

            // Freeze header
            $sheet->freezePane('A3');

            // Download
            $filename = 'militari_pefo_' . Carbon::now()->format('Y-m-d_H-i') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'pefo');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', 'Errore nell\'esportazione: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel prenotazioni PEFO
     */
    public function exportPrenotazioniExcel(Request $request)
    {
        try {
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Prenotazioni PEFO');
            $sheet = $spreadsheet->getActiveSheet();

            // Titolo
            $sheet->setCellValue('A1', 'PRENOTAZIONI PEFO');
            $sheet->mergeCells('A1:G1');
            $excelService->applyTitleStyle($sheet, 'A1:G1');

            // Ottieni prenotazioni
            $prenotazioni = PrenotazionePefo::with(['militari.grado', 'creatore'])
                ->orderBy('data_prenotazione', 'desc')
                ->get();

            $row = 3;

            foreach ($prenotazioni as $prenotazione) {
                // Header prenotazione
                $sheet->setCellValue('A' . $row, $prenotazione->nome_prenotazione);
                $sheet->setCellValue('C' . $row, 'Data: ' . $prenotazione->getDataFormattata());
                $sheet->setCellValue('E' . $row, 'Stato: ' . $prenotazione->getStatoLabel());
                $sheet->mergeCells('A' . $row . ':B' . $row);
                $sheet->mergeCells('C' . $row . ':D' . $row);
                $sheet->mergeCells('E' . $row . ':G' . $row);
                
                // Stile header prenotazione
                $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->getStartColor()->setRGB('0A2342');
                $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->getColor()->setRGB('FFFFFF');
                
                $row++;

                // Header militari
                $sheet->setCellValue('A' . $row, 'N.');
                $sheet->setCellValue('B' . $row, 'GRADO');
                $sheet->setCellValue('C' . $row, 'COGNOME');
                $sheet->setCellValue('D' . $row, 'NOME');
                $sheet->setCellValue('E' . $row, 'DATA NASCITA');
                $sheet->setCellValue('F' . $row, 'ETÀ');
                $excelService->applyHeaderStyle($sheet, 'A' . $row . ':F' . $row);
                $row++;

                // Militari della prenotazione
                $n = 1;
                foreach ($prenotazione->militari as $militare) {
                    $sheet->setCellValue('A' . $row, $n);
                    $sheet->setCellValue('B' . $row, $militare->grado ? $militare->grado->abbreviazione : '');
                    $sheet->setCellValue('C' . $row, $militare->cognome);
                    $sheet->setCellValue('D' . $row, $militare->nome);
                    $sheet->setCellValue('E' . $row, $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '');
                    $sheet->setCellValue('F' . $row, $militare->data_nascita ? $militare->data_nascita->age : '');
                    $row++;
                    $n++;
                }

                if ($prenotazione->militari->isEmpty()) {
                    $sheet->setCellValue('A' . $row, 'Nessun militare assegnato');
                    $sheet->mergeCells('A' . $row . ':F' . $row);
                    $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
                    $row++;
                }

                // Spazio tra prenotazioni
                $row++;
            }

            // Auto-size colonne
            foreach (range('A', 'G') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Info generazione
            $sheet->setCellValue('A' . $row, 'Generato il: ' . Carbon::now()->format('d/m/Y H:i'));
            $sheet->mergeCells('A' . $row . ':G' . $row);

            // Download
            $filename = 'prenotazioni_pefo_' . Carbon::now()->format('Y-m-d_H-i') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'pefo');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', 'Errore nell\'esportazione: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel singola prenotazione PEFO
     */
    public function exportSingolaPrenotazioneExcel(PrenotazionePefo $prenotazione)
    {
        try {
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Prenotazione PEFO');
            $sheet = $spreadsheet->getActiveSheet();

            // Carica i militari con relazioni
            $prenotazione->load(['militari.grado', 'militari.compagnia']);

            // Titolo con nome prenotazione
            $sheet->setCellValue('A1', strtoupper($prenotazione->getNomeVisualizzato()));
            $sheet->mergeCells('A1:G1');
            $excelService->applyTitleStyle($sheet, 'A1:G1');

            // Header
            $headers = ['N.', 'GRADO', 'COGNOME', 'NOME', 'DATA NASCITA', 'ETÀ', 'STATO'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            $excelService->applyHeaderStyle($sheet, 'A2:G2');

            // Dati militari
            $row = 3;
            $n = 1;
            foreach ($prenotazione->militari as $militare) {
                $sheet->setCellValue('A' . $row, $n);
                $sheet->setCellValue('B' . $row, $militare->grado ? $militare->grado->abbreviazione : '');
                $sheet->setCellValue('C' . $row, $militare->cognome);
                $sheet->setCellValue('D' . $row, $militare->nome);
                $sheet->setCellValue('E' . $row, $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '');
                $sheet->setCellValue('F' . $row, $militare->data_nascita ? $militare->data_nascita->age : '');
                
                // Stato con colore
                $stato = $militare->pivot->confermato ? 'Confermato' : 'Da confermare';
                $sheet->setCellValue('G' . $row, $stato);
                
                // Colora la riga in base allo stato
                if ($militare->pivot->confermato) {
                    $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->getStartColor()->setRGB('D4EDDA'); // Verde
                } else {
                    $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
                    $sheet->getStyle('A' . $row . ':G' . $row)->getFill()->getStartColor()->setRGB('CCE5FF'); // Blu
                }
                
                $row++;
                $n++;
            }

            if ($prenotazione->militari->isEmpty()) {
                $sheet->setCellValue('A' . $row, 'Nessun militare assegnato');
                $sheet->mergeCells('A' . $row . ':G' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
                $row++;
            }

            // Auto-size colonne
            foreach (range('A', 'G') as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            // Bordi
            $lastRow = $row - 1;
            if ($lastRow >= 2) {
                $sheet->getStyle('A2:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }

            // Riepilogo
            $row++;
            $sheet->setCellValue('A' . $row, 'Totale: ' . $prenotazione->militari->count() . ' militari (' . 
                $prenotazione->getNumeroMilitariConfermati() . ' confermati, ' . 
                $prenotazione->getNumeroMilitariDaConfermare() . ' da confermare)');
            $sheet->mergeCells('A' . $row . ':G' . $row);

            // Info generazione
            $row++;
            $sheet->setCellValue('A' . $row, 'Generato il: ' . Carbon::now()->format('d/m/Y H:i'));
            $sheet->mergeCells('A' . $row . ':G' . $row);

            // Download
            $tipoProva = ucfirst($prenotazione->tipo_prova);
            $dataPrenotazione = $prenotazione->data_prenotazione->format('Y-m-d');
            $filename = "pefo_{$tipoProva}_{$dataPrenotazione}.xlsx";
            $tempFile = tempnam(sys_get_temp_dir(), 'pefo');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download($tempFile, $filename)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return back()->with('error', 'Errore nell\'esportazione: ' . $e->getMessage());
        }
    }

    /**
     * API: Ottiene militari disponibili per una prenotazione
     * (esclusi quelli già assegnati)
     */
    public function getMilitariDisponibili(Request $request, PrenotazionePefo $prenotazione): JsonResponse
    {
        try {
            // IDs militari già assegnati a questa prenotazione
            $militariAssegnatiIds = $prenotazione->militari()->pluck('militare_id')->toArray();

            $query = Militare::withoutGlobalScopes()
                ->with(['grado', 'compagnia'])
                ->whereNotIn('id', $militariAssegnatiIds);

            // Filtro ricerca
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('cognome', 'like', "%{$search}%")
                      ->orWhere('nome', 'like', "%{$search}%");
                });
            }

            // Filtro compagnia
            if ($request->filled('compagnia_id')) {
                $query->where('compagnia_id', $request->compagnia_id);
            }

            $query->orderBy('cognome')->orderBy('nome');
            
            $militari = $query->limit(100)->get();

            $data = $militari->map(function($militare) {
                return [
                    'id' => $militare->id,
                    'grado' => $militare->grado ? $militare->grado->abbreviazione : '',
                    'cognome' => $militare->cognome,
                    'nome' => $militare->nome,
                    'compagnia' => $militare->compagnia ? $militare->compagnia->nome : '',
                    'data_nascita' => $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '',
                    'eta' => $militare->data_nascita ? $militare->data_nascita->age : null
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }
}
