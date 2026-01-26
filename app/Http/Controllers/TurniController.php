<?php

namespace App\Http\Controllers;

use App\Services\TurniService;
use App\Models\Militare;
use App\Models\ServizioTurno;
use App\Models\ServizioTurnoSettimana;
use App\Models\CompagniaSetting;
use App\Models\TipoServizio;
use App\Models\AssegnazioneTurno;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\ExcelStyleService;
use Illuminate\Support\Facades\Log;

class TurniController extends Controller
{
    protected $turniService;

    public function __construct(TurniService $turniService)
    {
        $this->turniService = $turniService;
    }

    /**
     * Mostra la vista settimanale dei servizi
     */
    public function index(Request $request)
    {
        // Ottieni la data dalla query string o usa oggi
        $data = $request->has('data') 
            ? Carbon::parse($request->data) 
            : Carbon::now();

        // Ottieni tutti i dati per la settimana
        $dati = $this->turniService->getDatiSettimana($data);
        $turnoId = $dati['turno']->id;

        // Carica le impostazioni specifiche per questa settimana
        $impostazioniSettimana = ServizioTurnoSettimana::where('turno_settimanale_id', $turnoId)
            ->get()
            ->keyBy('servizio_turno_id');

        // Prepara i dati dei servizi con le impostazioni per settimana
        $serviziConImpostazioni = [];
        foreach (TipoServizio::perCategoria('servizio') as $tipoServizio) {
            $servizioTurno = ServizioTurno::where('sigla_cpt', $tipoServizio->codice)->first();
            $impostazione = $servizioTurno ? $impostazioniSettimana->get($servizioTurno->id) : null;
            
            $serviziConImpostazioni[] = [
                'tipo_servizio' => $tipoServizio,
                'servizio_turno' => $servizioTurno,
                // Usa impostazioni specifiche per settimana, altrimenti fallback ai valori globali
                'num_posti' => $impostazione ? $impostazione->num_posti : ($servizioTurno->num_posti ?? 0),
                'smontante_cpt' => $impostazione ? $impostazione->smontante_cpt : ($servizioTurno->smontante_cpt ?? false),
            ];
        }

        return view('servizi.turni.index', array_merge($dati, [
            'dataRiferimento' => $data->copy(),
            'comandanteCompagnia' => $this->getComandanteCompagniaName(),
            'cptServizi' => TipoServizio::perCategoria('servizio'),
            'serviziTurnoBySigla' => ServizioTurno::all()->keyBy('sigla_cpt'),
            'serviziConImpostazioni' => $serviziConImpostazioni,
        ]));
    }

    /**
     * API: Verifica disponibilità militare per una data
     */
    public function checkDisponibilita(Request $request)
    {
        $request->validate([
            'militare_id' => 'required|exists:militari,id',
            'data' => 'required|date',
            'exclude_activity_id' => 'nullable|exists:board_activities,id', // Attività da escludere
        ]);

        $militare = Militare::find($request->militare_id);
        $disponibilita = $militare->isDisponibile($request->data, $request->exclude_activity_id);

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
     */
    public function rimuovi(Request $request)
    {
        $request->validate([
            'assegnazione_id' => 'required|exists:assegnazioni_turno,id',
        ]);

        $risultato = $this->turniService->rimuoviAssegnazione($request->assegnazione_id);

        return response()->json($risultato);
    }

    /**
     * API: Recupera le assegnazioni per una cella specifica (servizio + data)
     * Usato per aggiornare la UI senza ricaricare la pagina
     */
    public function getAssegnazioni(Request $request)
    {
        $request->validate([
            'servizio_id' => 'required|exists:servizi_turno,id',
            'data' => 'required|date',
            'turno_id' => 'required|exists:turni_settimanali,id',
        ]);

        try {
            $assegnazioni = AssegnazioneTurno::where('servizio_turno_id', $request->servizio_id)
                ->where('data_servizio', $request->data)
                ->where('turno_settimanale_id', $request->turno_id)
                ->with(['militare.grado'])
                ->get();

            $assegnazioniFormatted = $assegnazioni->map(function ($assegnazione) {
                return [
                    'id' => $assegnazione->id,
                    'militare_id' => $assegnazione->militare_id,
                    'cognome' => $assegnazione->militare->cognome ?? '',
                    'nome' => $assegnazione->militare->nome ?? '',
                    'grado_sigla' => $assegnazione->militare->grado->sigla ?? '',
                ];
            });

            return response()->json([
                'success' => true,
                'assegnazioni' => $assegnazioniFormatted,
            ]);

        } catch (\Exception $e) {
            Log::error('Errore getAssegnazioni', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero delle assegnazioni.',
            ], 500);
        }
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
     * Aggiorna il nome del comandante di compagnia per l'export
     */
    public function aggiornaComandante(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:120',
        ]);

        $settings = CompagniaSetting::getForCurrentUser();

        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'Compagnia non trovata per l\'utente corrente.',
            ], 422);
        }

        $settings->setSetting('turni.comandante_compagnia', trim($request->nome));
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Comandante aggiornato con successo.',
        ]);
    }

    /**
     * Aggiorna impostazioni servizio (posti + smontante)
     */
    public function aggiornaImpostazioniServizio(Request $request)
    {
        $request->validate([
            'sigla_cpt' => 'required|string|max:10',
            'num_posti' => 'required|integer|min:0|max:20',
            'smontante_cpt' => 'sometimes|boolean',
        ]);

        $siglaCpt = strtoupper(trim($request->sigla_cpt));
        $numPosti = (int) $request->num_posti;
        $smontante = $request->boolean('smontante_cpt', false);

        $tipoServizio = TipoServizio::where('codice', $siglaCpt)
            ->where('categoria', 'servizio')
            ->first();

        if (!$tipoServizio) {
            return response()->json([
                'success' => false,
                'message' => 'Sigla CPT non valida o non appartenente ai servizi.',
            ], 422);
        }

        $servizio = ServizioTurno::firstOrCreate(
            ['sigla_cpt' => $siglaCpt],
            [
                'nome' => $tipoServizio->descrizione ?: $tipoServizio->nome,
                'codice' => $siglaCpt,
                'num_posti' => 0,
                'tipo' => 'singolo',
                'ordine' => 0,
                'attivo' => true,
            ]
        );

        $maxAssegnazioni = AssegnazioneTurno::where('servizio_turno_id', $servizio->id)
            ->selectRaw('COUNT(*) as tot')
            ->groupBy('data_servizio')
            ->orderByDesc('tot')
            ->first();

        if ($maxAssegnazioni && $maxAssegnazioni->tot > $numPosti) {
            return response()->json([
                'success' => false,
                'message' => 'Non puoi ridurre i posti sotto il numero di assegnazioni già presenti.',
            ], 422);
        }

        $servizio->update([
            'nome' => $tipoServizio->descrizione ?: $tipoServizio->nome,
            'sigla_cpt' => $siglaCpt,
            'codice' => $siglaCpt,
            'num_posti' => $numPosti,
            'tipo' => $numPosti > 1 ? 'multiplo' : 'singolo',
            'smontante_cpt' => $smontante,
            'attivo' => $numPosti > 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Impostazioni aggiornate.',
        ]);
    }

    /**
     * Aggiorna impostazioni servizi in batch per una specifica settimana
     * I posti sono ora specifici per settimana, non globali
     */
    public function aggiornaImpostazioniBatch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'turno_id' => 'required|exists:turni_settimanali,id',
            'servizi' => 'required|array|min:1',
            'servizi.*.sigla_cpt' => 'required|string|max:20',
            'servizi.*.num_posti' => 'required|integer|min:0|max:20',
            'servizi.*.smontante_cpt' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $turnoId = $request->input('turno_id');
            $serviziPayload = $request->input('servizi', []);
            
            foreach ($serviziPayload as $item) {
                $siglaCpt = strtoupper(trim($item['sigla_cpt'] ?? ''));
                $numPosti = (int) ($item['num_posti'] ?? 0);
                $smontante = filter_var($item['smontante_cpt'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (empty($siglaCpt)) {
                    continue;
                }

                $tipoServizio = TipoServizio::where('codice', $siglaCpt)
                    ->where('categoria', 'servizio')
                    ->first();

                if (!$tipoServizio) {
                    Log::warning("Sigla CPT non trovata: {$siglaCpt}");
                    continue;
                }

                // Assicurati che esista il ServizioTurno base
                $servizio = ServizioTurno::firstOrCreate(
                    ['sigla_cpt' => $siglaCpt],
                    [
                        'nome' => $tipoServizio->descrizione ?: $tipoServizio->nome,
                        'codice' => $siglaCpt,
                        'num_posti' => 0,
                        'tipo' => 'singolo',
                        'ordine' => $tipoServizio->ordine ?? 0,
                        'attivo' => true,
                    ]
                );

                // Verifica assegnazioni esistenti per QUESTA settimana
                if ($numPosti > 0) {
                    $turno = \App\Models\TurnoSettimanale::find($turnoId);
                    $maxAssegnazioni = AssegnazioneTurno::where('servizio_turno_id', $servizio->id)
                        ->where('turno_settimanale_id', $turnoId)
                        ->whereBetween('data_servizio', [$turno->data_inizio, $turno->data_fine])
                        ->selectRaw('data_servizio, COUNT(*) as tot')
                        ->groupBy('data_servizio')
                        ->orderByDesc('tot')
                        ->first();

                    if ($maxAssegnazioni && $maxAssegnazioni->tot > $numPosti) {
                        return response()->json([
                            'success' => false,
                            'message' => "Non puoi ridurre i posti a {$numPosti}: ci sono già {$maxAssegnazioni->tot} assegnazioni per {$siglaCpt} in questa settimana.",
                        ], 422);
                    }
                }

                // Salva le impostazioni specifiche per questa settimana nella tabella pivot
                ServizioTurnoSettimana::updateOrCreate(
                    [
                        'turno_settimanale_id' => $turnoId,
                        'servizio_turno_id' => $servizio->id,
                    ],
                    [
                        'num_posti' => $numPosti,
                        'smontante_cpt' => $smontante,
                        'attivo' => $numPosti > 0,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Impostazioni salvate per questa settimana.',
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornaImpostazioniBatch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Genera lo spreadsheet con i turni
     */
    private function generaSpreadsheet($data)
    {
        $dati = $this->turniService->getDatiSettimana($data);
        
        // Usa servizio per stili Excel
        $excelService = new ExcelStyleService();
        $spreadsheet = $excelService->createSpreadsheet('Turni Settimanali');
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Turni');

        // RIGA 1-3: Intestazione con stile navy SUGECO
        $sheet->setCellValue('A1', "11° REGGIMENTO TRASMISSIONI");
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F2FF']]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
        
        $sheet->setCellValue('A2', 'Battaglione Trasmissioni "LEONESSA"');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        $sheet->setCellValue('A3', '124^ Compagnia');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => ExcelStyleService::NAVY_LIGHT]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Riga vuota
        $sheet->getRowDimension(4)->setRowHeight(5);

        // RIGA 5: Header giorni settimana
        $sheet->setCellValue('A5', 'TIPO DI SERVIZIO');
        
        $col = 'B';
        foreach ($dati['giorniSettimana'] as $giorno) {
            $sheet->setCellValue($col . '5', strtoupper($giorno['giorno_settimana']));
            $col++;
        }

        // RIGA 6: Date (formato gg/mm/yyyy)
        $col = 'B';
        foreach ($dati['giorniSettimana'] as $giorno) {
            $sheet->setCellValue($col . '6', $giorno['giorno_num']);
            
            // Evidenzia weekend in rosso
            $dataGiorno = $giorno['data'];
            if ($dataGiorno->isWeekend()) {
                $sheet->getStyle($col . '5:' . $col . '6')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFCDD2']],
                    'font' => ['color' => ['rgb' => 'C62828']]
                ]);
            }
            $col++;
        }

        // Stile header con colore navy SUGECO
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => ExcelStyleService::NAVY_LIGHT]
                ]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => ExcelStyleService::NAVY]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A5:H6')->applyFromArray($headerStyle);
        $sheet->getRowDimension(5)->setRowHeight(25);
        $sheet->getRowDimension(6)->setRowHeight(25);

        // Popola servizi e assegnazioni
        $row = 7;
        $altRowColor = false;
        foreach ($dati['serviziTurno'] as $servizio) {
            $maxPosti = max($servizio->num_posti, 1);
            $startRow = $row;
            
            for ($posto = 0; $posto < $maxPosti; $posto++) {
                // Nome servizio solo alla prima riga
                if ($posto === 0) {
                    $sheet->setCellValue('A' . $row, $servizio->nome);
                    $sheet->getStyle('A' . $row)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']]
                    ]);
                    
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
                        $sheet->getStyle($col . $row)->applyFromArray([
                            'font' => ['size' => 10, 'color' => ['rgb' => ExcelStyleService::NAVY]],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']]
                        ]);
                    } else {
                        $sheet->setCellValue($col . $row, '-');
                        $sheet->getStyle($col . $row)->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CCCCCC'));
                    }
                    
                    $sheet->getStyle($col . $row)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);
                    
                    $col++;
                }
                
                // Bordi e righe alternate
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => ExcelStyleService::BORDER_GRAY]
                        ]
                    ]
                ];
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
                $sheet->getRowDimension($row)->setRowHeight(22);
                
                $row++;
            }
            
            $altRowColor = !$altRowColor;
        }

        // Firma con stile migliorato
        $row += 2;
        $sheet->setCellValue('F' . $row, "IL COMANDANTE LA COMPAGNIA");
        $sheet->mergeCells('F' . $row . ':H' . $row);
        $sheet->getStyle('F' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => ExcelStyleService::NAVY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        
        $row++;
        $sheet->setCellValue('F' . $row, $this->getComandanteCompagniaName());
        $sheet->mergeCells('F' . $row . ':H' . $row);
        $sheet->getStyle('F' . $row)->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '6c757d']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ]);
        
        // Data generazione
        $row += 2;
        $excelService->addGenerationInfo($sheet, $row);

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
     * Nome del comandante di compagnia (fallback default)
     */
    private function getComandanteCompagniaName(): string
    {
        $default = 'Cap. t.(tlm.) RN Mattia CACCAMO';
        $settings = CompagniaSetting::getForCurrentUser();
        $nome = $settings?->getSetting('turni.comandante_compagnia');

        if (is_string($nome) && trim($nome) !== '') {
            return trim($nome);
        }

        return $default;
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

