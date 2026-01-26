<?php

namespace App\Http\Controllers;

use App\Models\Militare;
use App\Models\ScadenzaApprontamento;
use App\Models\BoardActivity;
use App\Models\BoardColumn;
use App\Models\TeatroOperativo;
use App\Models\PrenotazioneApprontamento;
use App\Models\PianificazioneMensile;
use App\Models\PianificazioneGiornaliera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ApprontamentiController extends Controller
{
    /**
     * Messaggi di errore personalizzati
     */
    private const ERROR_MESSAGES = [
        'unauthorized' => 'Non hai i permessi per eseguire questa operazione',
        'teatro_not_found' => 'Teatro Operativo non trovato',
        'militare_not_found' => 'Militare non trovato',
        'invalid_field' => 'Campo non valido',
        'invalid_date_format' => 'Formato data non valido. Usa dd/mm/yyyy o "NR" per Non Richiesto',
        'save_error' => 'Errore durante il salvataggio',
        'generic_error' => 'Si è verificato un errore. Riprova più tardi.'
    ];

    /**
     * Mostra la pagina degli approntamenti
     */
    public function index(Request $request)
    {
        // Ottieni la colonna "operazioni" (Teatro Operativo) dalla board
        $colonnaTO = BoardColumn::where('slug', 'operazioni')->first();
        
        // Ottieni tutte le attività T.O. attive o future
        $approntamentiAttivi = collect();
        if ($colonnaTO) {
            $approntamentiAttivi = BoardActivity::where('column_id', $colonnaTO->id)
                ->where(function($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', Carbon::today());
                })
                ->with('militari')
                ->orderBy('start_date')
                ->get();
        }
        
        // Teatri Operativi da Organici - Carica TUTTI i teatri attivi (senza filtro su militari)
        $teatriOperativi = TeatroOperativo::attivi()
            ->with(['militari' => function($query) {
                $query->with(['grado', 'compagnia', 'scadenzaApprontamento']);
            }])
            ->orderBy('nome')
            ->get();
        
        // Approntamento selezionato
        $approntamentoSelezionato = null;
        $teatroSelezionato = null;
        $militari = collect();
        
        // Check se è selezionato "tutti" - mostra tutti i militari di tutti i teatri
        if ($request->get('teatro_id') === 'tutti') {
            // Raccogli tutti i militari da tutti i teatri operativi
            $militariIds = [];
            foreach ($teatriOperativi as $teatro) {
                $militariIds = array_merge($militariIds, $teatro->militari->pluck('id')->toArray());
            }
            $militariIds = array_unique($militariIds);
            
            if (!empty($militariIds)) {
                $militari = Militare::withVisibilityFlags()
                    ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                    ->whereIn('id', $militariIds)
                    ->orderBy('cognome')
                    ->orderBy('nome')
                    ->get();
            }
        }
        // Check se è selezionato un Teatro Operativo specifico
        elseif ($request->filled('teatro_id')) {
            $teatroSelezionato = $teatriOperativi->firstWhere('id', $request->teatro_id);
            
            if ($teatroSelezionato) {
                // Mostra TUTTI i militari assegnati (anche quelli in bozza)
                $militariIds = $teatroSelezionato->militari->pluck('id')->toArray();
                
                if (!empty($militariIds)) {
                    $militari = Militare::withVisibilityFlags()
                        ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                        ->whereIn('id', $militariIds)
                        ->orderBy('cognome')
                        ->orderBy('nome')
                        ->get();
                }
            }
        }
        // Altrimenti controlla se è selezionato un approntamento dalla Board
        elseif ($request->filled('approntamento_id')) {
            $approntamentoSelezionato = $approntamentiAttivi->firstWhere('id', $request->approntamento_id);
            
            if ($approntamentoSelezionato) {
                // Ottieni i militari assegnati a questo approntamento
                $militariIds = $approntamentoSelezionato->militari->pluck('id')->toArray();
                
                if (!empty($militariIds)) {
                    $militari = Militare::withVisibilityFlags()
                        ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                        ->whereIn('id', $militariIds)
                        ->orderBy('cognome')
                        ->orderBy('nome')
                        ->get();
                }
            }
        }

        // Applica i filtri stato se presenti
        $filtri = $request->only(array_keys(ScadenzaApprontamento::COLONNE));
        if (!empty(array_filter($filtri)) && $militari->isNotEmpty()) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        // Verifica se l'utente può modificare le scadenze
        $canEdit = auth()->user()->hasPermission('approntamenti.edit') 
                || auth()->user()->hasPermission('admin.access');

        $colonne = ScadenzaApprontamento::COLONNE;

        return view('approntamenti.index', compact(
            'approntamentiAttivi', 
            'teatriOperativi',
            'approntamentoSelezionato', 
            'teatroSelezionato',
            'militari', 
            'filtri', 
            'canEdit', 
            'colonne'
        ));
    }

    /**
     * Ottiene il valore formattato di un campo
     */
    public static function getValoreCampo($militare, string $campo): array
    {
        if (ScadenzaApprontamento::isColonnaCondivisa($campo)) {
            // Legge da scadenze_militari
            $scadenza = $militare->scadenza;
            $campoSorgente = ScadenzaApprontamento::getCampoSorgente($campo);
            
            if (!$scadenza) {
                return [
                    'valore' => '-',
                    'valore_raw' => '',
                    'stato' => 'non_presente',
                    'colore' => 'background-color: #f8f9fa; color: #6c757d;',
                    'scadenza' => '-',
                    'fonte' => 'scadenze_militari',
                    'readonly' => true,
                ];
            }
            
            $campoData = $campoSorgente . '_data_conseguimento';
            $dataConseguimento = $scadenza->$campoData;
            
            return [
                'valore' => $dataConseguimento ? Carbon::parse($dataConseguimento)->format('d/m/Y') : '-',
                'valore_raw' => $dataConseguimento ? Carbon::parse($dataConseguimento)->format('Y-m-d') : '',
                'stato' => $scadenza->verificaStato($campoSorgente),
                'colore' => $scadenza->getColore($campoSorgente),
                'scadenza' => $scadenza->formatScadenza($campoSorgente),
                'fonte' => 'scadenze_militari',
                'readonly' => true,
            ];
        } else {
            // Legge da scadenze_approntamenti
            $scadenza = $militare->scadenzaApprontamento;
            
            if (!$scadenza) {
                return [
                    'valore' => '-',
                    'valore_raw' => '',
                    'stato' => 'non_presente',
                    'colore' => 'background-color: #f8f9fa; color: #6c757d;',
                    'scadenza' => '-',
                    'fonte' => 'approntamenti',
                    'readonly' => false,
                ];
            }
            
            return [
                'valore' => $scadenza->getValoreFormattato($campo),
                'valore_raw' => $scadenza->$campo ?? '',
                'stato' => $scadenza->verificaStato($campo),
                'colore' => $scadenza->getColore($campo),
                'scadenza' => $scadenza->formatScadenza($campo),
                'fonte' => 'approntamenti',
                'readonly' => false,
            ];
        }
    }

    /**
     * Applica i filtri alla collection di militari
     */
    private function applicaFiltri($militari, array $filtri)
    {
        return $militari->filter(function ($militare) use ($filtri) {
            foreach ($filtri as $campo => $valore) {
                if (empty($valore) || $valore === 'tutti') {
                    continue;
                }

                $datiCampo = self::getValoreCampo($militare, $campo);
                $stato = $datiCampo['stato'];

                switch ($valore) {
                    case 'validi':
                        if ($stato !== 'valido') return false;
                        break;
                    case 'in_scadenza':
                        if ($stato !== 'in_scadenza') return false;
                        break;
                    case 'scaduti':
                        if ($stato !== 'scaduto' && $stato !== 'non_presente') return false;
                        break;
                    case 'non_richiesti':
                        if ($stato !== 'non_richiesto') return false;
                        break;
                }
            }

            return true;
        });
    }

    /**
     * Aggiorna una singola cella
     */
    public function updateSingola(Request $request, $militareId)
    {
        // Verifica permessi
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            // Validazione input
            $campo = $request->input('campo');
            $valore = $request->input('valore');

            if (!$campo) {
                return $this->errorResponse('Campo richiesto', 400);
            }

            // Verifica militare
            $militare = Militare::find($militareId);
            if (!$militare) {
                return $this->errorResponse(self::ERROR_MESSAGES['militare_not_found'], 404);
            }

            // Valida il campo
            if (!array_key_exists($campo, ScadenzaApprontamento::COLONNE)) {
                return $this->errorResponse(self::ERROR_MESSAGES['invalid_field'], 400);
            }

            // Gestisce il valore
            $valoreDaSalvare = $this->parseValore($valore);
            
            if ($valoreDaSalvare === false) {
                return $this->errorResponse(self::ERROR_MESSAGES['invalid_date_format'], 400);
            }

            // Crea o aggiorna il record in una transazione
            DB::beginTransaction();
            
            $scadenza = ScadenzaApprontamento::updateOrCreate(
                ['militare_id' => $militareId],
                [$campo => $valoreDaSalvare]
            );

            DB::commit();

            // Calcola i dati aggiornati
            return $this->successResponse('Valore aggiornato con successo', [
                'valore' => $scadenza->getValoreFormattato($campo),
                'stato' => $scadenza->verificaStato($campo),
                'colore' => $scadenza->getColore($campo),
                'scadenza' => $scadenza->formatScadenza($campo)
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore updateSingola Approntamenti', [
                'militare_id' => $militareId,
                'campo' => $campo ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Verifica se l'utente può modificare
     */
    private function canEdit(): bool
    {
        return auth()->user()->hasPermission('approntamenti.edit') 
            || auth()->user()->hasPermission('admin.access');
    }

    /**
     * Parse del valore (data o NR)
     * @return string|null|false - false se formato non valido
     */
    private function parseValore(?string $valore)
    {
        if (empty($valore)) {
            return null;
        }

        $valore = trim($valore);
        
        // Check per Non Richiesto
        if (strtoupper($valore) === 'NR' || strtolower($valore) === 'non richiesto') {
            return 'NR';
        }

        // Prova formato italiano dd/mm/yyyy
        try {
            $data = Carbon::createFromFormat('d/m/Y', $valore);
            if ($data && $data->format('d/m/Y') === $valore) {
                return $data->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Continua con altri formati
        }

        // Prova formato ISO yyyy-mm-dd
        try {
            $data = Carbon::createFromFormat('Y-m-d', $valore);
            if ($data && $data->format('Y-m-d') === $valore) {
                return $data->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Continua
        }

        // Prova parse generico
        try {
            $data = Carbon::parse($valore);
            return $data->format('Y-m-d');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Risposta di successo standardizzata
     */
    private function successResponse(string $message, array $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message
        ], $data));
    }

    /**
     * Risposta di errore standardizzata
     */
    private function errorResponse(string $message, int $statusCode = 500): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Pagina di gestione approntamenti (admin)
     */
    public function gestione()
    {
        if (!auth()->user()->hasPermission('admin.access')) {
            abort(403, 'Non hai i permessi per accedere a questa pagina');
        }

        $colonne = ScadenzaApprontamento::COLONNE;
        
        return view('approntamenti.gestione', compact('colonne'));
    }

    /**
     * Export Excel - "Pronti all'impiego [nome teatro]"
     */
    public function exportExcel(Request $request)
    {
        $militari = collect();
        $nomeTeatro = 'Nessun Teatro';
        
        // Check se è selezionato "tutti" - export tutti i militari di tutti i teatri
        if ($request->get('teatro_id') === 'tutti') {
            $teatriOperativi = TeatroOperativo::attivi()->with(['militari'])->get();
            $militariIds = [];
            foreach ($teatriOperativi as $teatro) {
                $militariIds = array_merge($militariIds, $teatro->militari->pluck('id')->toArray());
            }
            $militariIds = array_unique($militariIds);
            $nomeTeatro = 'Tutti i Teatri Operativi';
            
            if (!empty($militariIds)) {
                $militari = Militare::withVisibilityFlags()
                    ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                    ->whereIn('id', $militariIds)
                    ->orderBy('cognome')
                    ->orderBy('nome')
                    ->get();
            }
        }
        // Check se è selezionato un Teatro Operativo specifico
        elseif ($request->filled('teatro_id')) {
            $teatro = TeatroOperativo::with(['militari'])->find($request->teatro_id);
            
            if ($teatro) {
                // Export TUTTI i militari assegnati (anche quelli in bozza)
                $militariIds = $teatro->militari->pluck('id')->toArray();
                $nomeTeatro = $teatro->nome;
                
                if (!empty($militariIds)) {
                    $militari = Militare::withVisibilityFlags()
                        ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                        ->whereIn('id', $militariIds)
                        ->orderBy('cognome')
                        ->orderBy('nome')
                        ->get();
                }
            }
        }
        // Altrimenti check se è selezionato un approntamento dalla Board
        elseif ($request->filled('approntamento_id')) {
            $colonnaTO = BoardColumn::where('slug', 'operazioni')->first();
            
            if ($colonnaTO) {
                $approntamentoSelezionato = BoardActivity::where('column_id', $colonnaTO->id)
                    ->where('id', $request->approntamento_id)
                    ->with('militari')
                    ->first();
                
                if ($approntamentoSelezionato) {
                    $militariIds = $approntamentoSelezionato->militari->pluck('id')->toArray();
                    $nomeTeatro = $approntamentoSelezionato->title;
                    
                    if (!empty($militariIds)) {
                        $militari = Militare::withVisibilityFlags()
                            ->with(['scadenzaApprontamento', 'scadenza', 'grado', 'polo', 'compagnia'])
                            ->whereIn('id', $militariIds)
                            ->orderBy('cognome')
                            ->orderBy('nome')
                            ->get();
                    }
                }
            }
        }

        // Applica filtri se presenti
        $filtri = $request->only(array_keys(ScadenzaApprontamento::COLONNE));
        if (!empty(array_filter($filtri)) && $militari->isNotEmpty()) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        $spreadsheet = new Spreadsheet();
        $colonneLabels = ScadenzaApprontamento::getLabels();
        $totaleMilitari = count($militari);
        $totaleCattedre = count($colonneLabels);
        
        // Pre-calcola statistiche per ogni militare
        $militariConCattedre = [];
        foreach ($militari as $militare) {
            $cattedreMancanti = [];
            $cattedreFatte = 0;
            
            foreach ($colonneLabels as $campo => $label) {
                $datiCampo = self::getValoreCampo($militare, $campo);
                if ($datiCampo['stato'] == 'valido' || $datiCampo['stato'] == 'in_scadenza') {
                    $cattedreFatte++;
                } else {
                    $cattedreMancanti[] = $label;
                }
            }
            
            $percentuale = $totaleCattedre > 0 ? round(($cattedreFatte / $totaleCattedre) * 100) : 0;
            
            $militariConCattedre[] = [
                'militare' => $militare,
                'cattedreMancanti' => $cattedreMancanti,
                'cattedreFatte' => $cattedreFatte,
                'percentuale' => $percentuale
            ];
        }
        
        // Calcola statistiche per cattedra
        $statsCattedre = [];
        foreach (array_keys(ScadenzaApprontamento::COLONNE) as $campo) {
            $statsCattedre[$campo] = ['fatto' => 0, 'mancante' => 0, 'label' => $colonneLabels[$campo] ?? $campo];
        }
        foreach ($militari as $militare) {
            foreach (array_keys(ScadenzaApprontamento::COLONNE) as $campo) {
                $datiCampo = self::getValoreCampo($militare, $campo);
                if ($datiCampo['stato'] == 'valido' || $datiCampo['stato'] == 'in_scadenza') {
                    $statsCattedre[$campo]['fatto']++;
                } else {
                    $statsCattedre[$campo]['mancante']++;
                }
            }
        }
        
        // Ordina statistiche per percentuale (meno complete prima)
        uasort($statsCattedre, function($a, $b) use ($totaleMilitari) {
            $percA = $totaleMilitari > 0 ? ($a['fatto'] / $totaleMilitari) : 0;
            $percB = $totaleMilitari > 0 ? ($b['fatto'] / $totaleMilitari) : 0;
            return $percA <=> $percB;
        });
        
        // Stili comuni
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0A2342']]]
        ];
        
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        
        $subtitleStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'D4AF37']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        
        // =========================================
        // FOGLIO 1: STATISTICHE CATTEDRE (senza colonna Stato)
        // =========================================
        $statsSheet = $spreadsheet->getActiveSheet();
        $statsSheet->setTitle('Statistiche Cattedre');
        
        // Header elegante
        $statsSheet->setCellValue('A1', 'STATISTICHE APPRONTAMENTO');
        $statsSheet->mergeCells('A1:E1');
        $statsSheet->getStyle('A1')->applyFromArray($titleStyle);
        $statsSheet->getRowDimension(1)->setRowHeight(40);
        
        $statsSheet->setCellValue('A2', strtoupper($nomeTeatro));
        $statsSheet->mergeCells('A2:E2');
        $statsSheet->getStyle('A2')->applyFromArray($subtitleStyle);
        $statsSheet->getRowDimension(2)->setRowHeight(25);
        
        $statsSheet->setCellValue('A3', 'Generato il ' . date('d/m/Y') . ' alle ' . date('H:i') . ' - Totale militari: ' . $totaleMilitari);
        $statsSheet->mergeCells('A3:E3');
        $statsSheet->getStyle('A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6C757D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Header tabella statistiche (senza Stato)
        $statsSheet->setCellValue('A5', 'N.');
        $statsSheet->setCellValue('B5', 'Cattedra');
        $statsSheet->setCellValue('C5', 'Completate');
        $statsSheet->setCellValue('D5', 'Mancanti');
        $statsSheet->setCellValue('E5', '% Compl.');
        $statsSheet->getStyle('A5:E5')->applyFromArray($headerStyle);
        $statsSheet->getRowDimension(5)->setRowHeight(25);
        
        // Dati statistiche
        $statsRow = 6;
        $numero = 1;
        foreach ($statsCattedre as $campo => $stat) {
            $percentuale = $totaleMilitari > 0 ? round(($stat['fatto'] / $totaleMilitari) * 100, 1) : 0;
            
            $statsSheet->setCellValue('A' . $statsRow, $numero);
            $statsSheet->setCellValue('B' . $statsRow, $stat['label']);
            $statsSheet->setCellValue('C' . $statsRow, $stat['fatto']);
            $statsSheet->setCellValue('D' . $statsRow, $stat['mancante']);
            $statsSheet->setCellValue('E' . $statsRow, $percentuale . '%');
            
            // Colore percentuale in base al valore
            if ($percentuale >= 80) {
                $bgColor = 'D4EDDA';
                $textColor = '155724';
            } elseif ($percentuale >= 50) {
                $bgColor = 'FFF3CD';
                $textColor = '856404';
            } else {
                $bgColor = 'F8D7DA';
                $textColor = '721C24';
            }
            
            // Stile riga alternata
            $rowBgColor = ($numero % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
            $statsSheet->getStyle('A' . $statsRow . ':E' . $statsRow)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ]);
            
            // Cella percentuale colorata
            $statsSheet->getStyle('E' . $statsRow)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'font' => ['bold' => true, 'color' => ['rgb' => $textColor]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]);
            
            $statsSheet->getStyle('A' . $statsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $statsSheet->getStyle('C' . $statsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $statsSheet->getStyle('D' . $statsRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $statsRow++;
            $numero++;
        }
        
        // Larghezze colonne statistiche
        $statsSheet->getColumnDimension('A')->setWidth(6);
        $statsSheet->getColumnDimension('B')->setWidth(40);
        $statsSheet->getColumnDimension('C')->setWidth(14);
        $statsSheet->getColumnDimension('D')->setWidth(14);
        $statsSheet->getColumnDimension('E')->setWidth(14);
        $statsSheet->freezePane('A6');
        
        // =========================================
        // FOGLIO 2: RIEPILOGO SITUAZIONE (senza colonna Stato, con Cattedre Effettuate)
        // =========================================
        $riepilogoSheet = $spreadsheet->createSheet();
        $riepilogoSheet->setTitle('Riepilogo Situazione');
        
        // Header elegante
        $riepilogoSheet->setCellValue('A1', 'RIEPILOGO SITUAZIONE MILITARI');
        $riepilogoSheet->mergeCells('A1:F1');
        $riepilogoSheet->getStyle('A1')->applyFromArray($titleStyle);
        $riepilogoSheet->getRowDimension(1)->setRowHeight(40);
        
        $riepilogoSheet->setCellValue('A2', strtoupper($nomeTeatro));
        $riepilogoSheet->mergeCells('A2:F2');
        $riepilogoSheet->getStyle('A2')->applyFromArray($subtitleStyle);
        $riepilogoSheet->getRowDimension(2)->setRowHeight(25);
        
        $riepilogoSheet->setCellValue('A3', 'Generato il ' . date('d/m/Y') . ' alle ' . date('H:i'));
        $riepilogoSheet->mergeCells('A3:F3');
        $riepilogoSheet->getStyle('A3')->applyFromArray([
            'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '6C757D']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        
        // Header tabella (con Cattedre Effettuate, senza Stato)
        $riepilogoSheet->setCellValue('A5', 'N.');
        $riepilogoSheet->setCellValue('B5', 'Grado');
        $riepilogoSheet->setCellValue('C5', 'Cognome');
        $riepilogoSheet->setCellValue('D5', 'Nome');
        $riepilogoSheet->setCellValue('E5', 'Cattedre Effettuate');
        $riepilogoSheet->setCellValue('F5', 'Cattedre Mancanti');
        $riepilogoSheet->getStyle('A5:F5')->applyFromArray($headerStyle);
        $riepilogoSheet->getRowDimension(5)->setRowHeight(25);
        
        // Ordina militari per percentuale (meno pronti prima)
        usort($militariConCattedre, function($a, $b) {
            return $a['percentuale'] <=> $b['percentuale'];
        });
        
        // Calcola le cattedre effettuate per ogni militare
        $datiMilitariCompleti = [];
        foreach ($militariConCattedre as $item) {
            $militare = $item['militare'];
            $cattedreEffettuate = [];
            
            foreach ($colonneLabels as $campo => $label) {
                $datiCampo = self::getValoreCampo($militare, $campo);
                if ($datiCampo['stato'] == 'valido' || $datiCampo['stato'] == 'in_scadenza') {
                    $cattedreEffettuate[] = $label;
                }
            }
            
            $datiMilitariCompleti[] = [
                'militare' => $militare,
                'cattedreMancanti' => $item['cattedreMancanti'],
                'cattedreEffettuate' => $cattedreEffettuate,
                'percentuale' => $item['percentuale']
            ];
        }
        
        // Dati militari
        $row = 6;
        $numero = 1;
        foreach ($datiMilitariCompleti as $item) {
            $militare = $item['militare'];
            $cattedreMancanti = $item['cattedreMancanti'];
            $cattedreEffettuate = $item['cattedreEffettuate'];
            
            $riepilogoSheet->setCellValue('A' . $row, $numero);
            $riepilogoSheet->setCellValue('B' . $row, $militare->grado->sigla ?? '-');
            $riepilogoSheet->setCellValue('C' . $row, $militare->cognome);
            $riepilogoSheet->setCellValue('D' . $row, $militare->nome);
            
            // Cattedre effettuate
            if (count($cattedreEffettuate) > 0) {
                $riepilogoSheet->setCellValue('E' . $row, implode(', ', $cattedreEffettuate));
            } else {
                $riepilogoSheet->setCellValue('E' . $row, '-');
            }
            $riepilogoSheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            
            // Cattedre mancanti
            if (count($cattedreMancanti) > 0) {
                $riepilogoSheet->setCellValue('F' . $row, implode(', ', $cattedreMancanti));
            } else {
                $riepilogoSheet->setCellValue('F' . $row, '✓ TUTTE COMPLETE');
            }
            $riepilogoSheet->getStyle('F' . $row)->getAlignment()->setWrapText(true);
            
            // Stile riga
            $rowBgColor = ($numero % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
            $riepilogoSheet->getStyle('A' . $row . ':F' . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rowBgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
            ]);
            
            // Cella cattedre effettuate colorata verde
            if (count($cattedreEffettuate) > 0) {
                $riepilogoSheet->getStyle('E' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                    'font' => ['color' => ['rgb' => '155724']]
                ]);
            }
            
            // Cella cattedre mancanti colorata
            if (count($cattedreMancanti) > 0) {
                $riepilogoSheet->getStyle('F' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
                    'font' => ['color' => ['rgb' => '856404']]
                ]);
            } else {
                $riepilogoSheet->getStyle('F' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                    'font' => ['bold' => true, 'color' => ['rgb' => '155724']]
                ]);
            }
            
            $riepilogoSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            // Altezza riga dinamica: calcola in base alla lunghezza del testo
            // Colonne E e F hanno larghezza 45 (~50 caratteri per riga)
            $testoEffettuate = count($cattedreEffettuate) > 0 ? implode(', ', $cattedreEffettuate) : '-';
            $testoMancanti = count($cattedreMancanti) > 0 ? implode(', ', $cattedreMancanti) : '✓';
            
            $charPerRiga = 50; // caratteri approssimativi per riga in colonna larga 45
            $righeEffettuate = max(1, ceil(strlen($testoEffettuate) / $charPerRiga));
            $righeMancanti = max(1, ceil(strlen($testoMancanti) / $charPerRiga));
            $righeNecessarie = max($righeEffettuate, $righeMancanti);
            
            // 15 pixel per riga di testo, minimo 18
            $altezza = max(18, $righeNecessarie * 15);
            $riepilogoSheet->getRowDimension($row)->setRowHeight($altezza);
            
            $row++;
            $numero++;
        }
        
        // Larghezze colonne riepilogo
        $riepilogoSheet->getColumnDimension('A')->setWidth(5);
        $riepilogoSheet->getColumnDimension('B')->setWidth(10);
        $riepilogoSheet->getColumnDimension('C')->setWidth(18);
        $riepilogoSheet->getColumnDimension('D')->setWidth(16);
        $riepilogoSheet->getColumnDimension('E')->setWidth(45);
        $riepilogoSheet->getColumnDimension('F')->setWidth(45);
        $riepilogoSheet->freezePane('A6');
        
        // =========================================
        // FOGLIO 3: DETTAGLIO COMPLETO (come tabella pagina)
        // =========================================
        $dettaglioSheet = $spreadsheet->createSheet();
        $dettaglioSheet->setTitle('Dettaglio Completo');
        
        // Header elegante
        $dettaglioSheet->setCellValue('A1', 'DETTAGLIO COMPLETO APPRONTAMENTO');
        $lastCol = chr(ord('A') + 2 + count($colonneLabels));
        $dettaglioSheet->mergeCells('A1:' . $lastCol . '1');
        $dettaglioSheet->getStyle('A1')->applyFromArray($titleStyle);
        $dettaglioSheet->getRowDimension(1)->setRowHeight(40);
        
        $dettaglioSheet->setCellValue('A2', strtoupper($nomeTeatro) . ' - Generato il ' . date('d/m/Y'));
        $dettaglioSheet->mergeCells('A2:' . $lastCol . '2');
        $dettaglioSheet->getStyle('A2')->applyFromArray($subtitleStyle);
        $dettaglioSheet->getRowDimension(2)->setRowHeight(25);
        
        // Header tabella
        $headers = ['Grado', 'Cognome', 'Nome'];
        foreach ($colonneLabels as $campo => $label) {
            $headers[] = $label;
        }
        
        $col = 'A';
        foreach ($headers as $header) {
            $dettaglioSheet->setCellValue($col . '4', $header);
            $dettaglioSheet->getStyle($col . '4')->applyFromArray($headerStyle);
            $col++;
        }
        $dettaglioSheet->getRowDimension(4)->setRowHeight(25);
        
        // Dati con colori
        $row = 5;
        foreach ($militari as $militare) {
            $col = 'A';
            
            // Grado, Cognome, Nome
            $dettaglioSheet->setCellValue($col++ . $row, $militare->grado->sigla ?? '-');
            $dettaglioSheet->setCellValue($col++ . $row, $militare->cognome);
            $dettaglioSheet->setCellValue($col++ . $row, $militare->nome);
            
            // Colonne approntamento con date e colori
            foreach (array_keys(ScadenzaApprontamento::COLONNE) as $campo) {
                $datiCampo = self::getValoreCampo($militare, $campo);
                $dettaglioSheet->setCellValue($col . $row, $datiCampo['valore']);
                
                // Colori in base allo stato
                $bgColor = match($datiCampo['stato']) {
                    'scaduto' => 'F8D7DA',
                    'in_scadenza' => 'FFF3CD',
                    'valido' => 'D4EDDA',
                    'non_richiesto' => 'E2E3E5',
                    default => 'F8F9FA'
                };
                $textColor = match($datiCampo['stato']) {
                    'scaduto' => '721C24',
                    'in_scadenza' => '856404',
                    'valido' => '155724',
                    default => '6C757D'
                };
                
                $dettaglioSheet->getStyle($col . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                    'font' => ['color' => ['rgb' => $textColor]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]]
                ]);
                
                $col++;
            }
            
            // Bordi per le prime 3 colonne
            $dettaglioSheet->getStyle('A' . $row . ':C' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]]
            ]);
            
            $row++;
        }
        
        // Auto-size colonne
        foreach (range('A', $dettaglioSheet->getHighestColumn()) as $col) {
            $dettaglioSheet->getColumnDimension($col)->setAutoSize(true);
        }
        $dettaglioSheet->freezePane('D5');
        
        // Torna al primo foglio (Statistiche)
        $spreadsheet->setActiveSheetIndex(0);

        // Output - Nome file: "Pronti all'impiego [nome teatro]"
        $writer = new Xlsx($spreadsheet);
        $filename = 'Pronti_all_impiego_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nomeTeatro) . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * Propone militari per una prenotazione cattedra
     * Filtra per: liberi nel CPT alla data, senza cattedra valida, ordinati per grado e anzianità
     */
    public function proponiPrenotazione(Request $request)
    {
        try {
            $validated = $request->validate([
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'cattedra' => 'required|string',
                'data' => 'required|date',
                'quantita' => 'required|integer|min:1|max:100'
            ], [
                'teatro_id.required' => 'Seleziona un Teatro Operativo',
                'teatro_id.exists' => 'Teatro Operativo non valido',
                'cattedra.required' => 'Seleziona una cattedra',
                'data.required' => 'Seleziona una data',
                'data.date' => 'Formato data non valido',
                'quantita.required' => 'Specifica il numero di militari',
                'quantita.min' => 'Il numero minimo è 1',
                'quantita.max' => 'Il numero massimo è 100'
            ]);

            $teatroId = $validated['teatro_id'];
            $cattedra = $validated['cattedra'];
            $data = Carbon::parse($validated['data']);
            $quantita = $validated['quantita'];

            // Verifica che la cattedra sia valida
            if (!array_key_exists($cattedra, ScadenzaApprontamento::COLONNE)) {
                return $this->errorResponse('Cattedra non valida', 400);
            }

            // Ottieni il teatro con i militari assegnati
            $teatro = TeatroOperativo::with(['militari' => function($query) {
                $query->with(['grado', 'scadenzaApprontamento']);
            }])->find($teatroId);

            if (!$teatro) {
                return $this->errorResponse(self::ERROR_MESSAGES['teatro_not_found'], 404);
            }

            $militariIds = $teatro->militari->pluck('id')->toArray();

            if (empty($militariIds)) {
                return $this->successResponse('Nessun militare assegnato a questo teatro operativo', [
                    'militari' => [],
                    'totale_disponibili' => 0,
                    'totale_proposti' => 0
                ]);
            }

            // Ottieni i militari con le relazioni necessarie
            $militari = Militare::with(['grado', 'scadenzaApprontamento'])
                ->whereIn('id', $militariIds)
                ->get();

            // Filtra: solo chi NON ha la cattedra valida
            $militariSenzaCattedra = $militari->filter(function($militare) use ($cattedra) {
                $datiCampo = self::getValoreCampo($militare, $cattedra);
                return in_array($datiCampo['stato'], ['non_presente', 'scaduto']);
            });

            // Filtra: solo chi è libero nel CPT nella data specificata
            $militariLiberi = $militariSenzaCattedra->filter(function($militare) use ($data) {
                return $this->isMilitareLiberoNelCPT($militare, $data);
            });

            // Escludi chi ha già una prenotazione attiva per questa cattedra/teatro
            $prenotazioniEsistenti = PrenotazioneApprontamento::where('teatro_operativo_id', $teatroId)
                ->where('cattedra', $cattedra)
                ->where('stato', 'prenotato')
                ->pluck('militare_id')
                ->toArray();

            $militariDisponibili = $militariLiberi->filter(function($militare) use ($prenotazioniEsistenti) {
                return !in_array($militare->id, $prenotazioniEsistenti);
            });

            // Ordina per grado (ordine decrescente) e poi per anzianità (più anziani prima)
            $militariOrdinati = $militariDisponibili->sortBy([
                fn($a, $b) => ($b->grado->ordine ?? 0) <=> ($a->grado->ordine ?? 0),
                fn($a, $b) => ($a->anzianita ?? '9999-12-31') <=> ($b->anzianita ?? '9999-12-31')
            ])->take($quantita)->values();

            // Prepara i dati per la risposta
            $risultato = $militariOrdinati->map(function($militare) use ($cattedra) {
                $datiCampo = self::getValoreCampo($militare, $cattedra);
                return [
                    'id' => $militare->id,
                    'grado' => $militare->grado->sigla ?? '-',
                    'grado_ordine' => $militare->grado->ordine ?? 0,
                    'cognome' => $militare->cognome,
                    'nome' => $militare->nome,
                    'anzianita' => $militare->anzianita ? Carbon::parse($militare->anzianita)->format('d/m/Y') : '-',
                    'stato_cattedra' => $datiCampo['stato'],
                    'valore_cattedra' => $datiCampo['valore']
                ];
            });

            return $this->successResponse('Ricerca completata', [
                'militari' => $risultato,
                'totale_disponibili' => $militariDisponibili->count(),
                'totale_proposti' => $risultato->count()
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore proponiPrenotazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Verifica se un militare è libero nel CPT per una data specifica
     */
    private function isMilitareLiberoNelCPT(Militare $militare, Carbon $data): bool
    {
        // Trova la pianificazione mensile per quel mese/anno
        $pianificazioneMensile = PianificazioneMensile::where('mese', $data->month)
            ->where('anno', $data->year)
            ->first();

        if (!$pianificazioneMensile) {
            // Nessuna pianificazione esistente = libero
            return true;
        }

        // Cerca pianificazione giornaliera per il militare in quella data
        $pianificazioneGiornaliera = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
            ->where('militare_id', $militare->id)
            ->where('giorno', $data->day)
            ->whereNotNull('tipo_servizio_id')
            ->first();

        // Se non c'è pianificazione o non ha tipo_servizio, è libero
        if (!$pianificazioneGiornaliera) {
            return true;
        }

        // Se ha un tipo_servizio, verifica se è un codice che indica assenza
        $tipoServizio = $pianificazioneGiornaliera->tipoServizio;
        if ($tipoServizio) {
            $codiciAssenza = ['LS', 'LO', 'LM', 'LC', 'RMD', 'IS', 'TIR', 'TRAS', 'TO', 'MIS'];
            if (in_array($tipoServizio->codice, $codiciAssenza)) {
                return false; // Non disponibile
            }
        }

        return true;
    }

    /**
     * Salva le prenotazioni per una cattedra
     */
    public function salvaPrenotazione(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'cattedra' => 'required|string',
                'data' => 'required|date',
                'militari_ids' => 'required|array|min:1',
                'militari_ids.*' => 'exists:militari,id'
            ], [
                'teatro_id.required' => 'Teatro Operativo richiesto',
                'cattedra.required' => 'Cattedra richiesta',
                'data.required' => 'Data richiesta',
                'militari_ids.required' => 'Seleziona almeno un militare',
                'militari_ids.min' => 'Seleziona almeno un militare'
            ]);

            // Verifica cattedra valida
            if (!array_key_exists($validated['cattedra'], ScadenzaApprontamento::COLONNE)) {
                return $this->errorResponse('Cattedra non valida', 400);
            }

            $teatroId = $validated['teatro_id'];
            $cattedra = $validated['cattedra'];
            $data = Carbon::parse($validated['data']);
            $militariIds = $validated['militari_ids'];

            DB::beginTransaction();

            $prenotazioniCreate = 0;
            $giàPrenotati = 0;

            foreach ($militariIds as $militareId) {
                // Verifica che non esista già una prenotazione attiva
                $esistente = PrenotazioneApprontamento::where('militare_id', $militareId)
                    ->where('teatro_operativo_id', $teatroId)
                    ->where('cattedra', $cattedra)
                    ->where('stato', 'prenotato')
                    ->exists();

                if (!$esistente) {
                    PrenotazioneApprontamento::create([
                        'militare_id' => $militareId,
                        'teatro_operativo_id' => $teatroId,
                        'cattedra' => $cattedra,
                        'data_prenotazione' => $data,
                        'stato' => 'prenotato',
                        'created_by' => auth()->id()
                    ]);
                    $prenotazioniCreate++;
                } else {
                    $giàPrenotati++;
                }
            }

            DB::commit();

            $message = "Prenotazioni create: {$prenotazioniCreate}";
            if ($giàPrenotati > 0) {
                $message .= " ({$giàPrenotati} già prenotati)";
            }

            return $this->successResponse($message, ['count' => $prenotazioniCreate]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvaPrenotazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Conferma una prenotazione e aggiorna la data della cattedra
     */
    public function confermaPrenotazione(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'militare_id' => 'required|exists:militari,id',
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'cattedra' => 'required|string',
                'data_effettiva' => 'required'
            ], [
                'militare_id.required' => 'Militare richiesto',
                'teatro_id.required' => 'Teatro Operativo richiesto',
                'cattedra.required' => 'Cattedra richiesta',
                'data_effettiva.required' => 'Data effettiva richiesta'
            ]);

            $militareId = $validated['militare_id'];
            $teatroId = $validated['teatro_id'];
            $cattedra = $validated['cattedra'];
            
            // Parse del valore (può essere data o NR)
            $valoreDaSalvare = $this->parseValore($validated['data_effettiva']);
            
            if ($valoreDaSalvare === false) {
                return $this->errorResponse(self::ERROR_MESSAGES['invalid_date_format'], 400);
            }

            DB::beginTransaction();

            // Trova e conferma la prenotazione
            $prenotazione = PrenotazioneApprontamento::where('militare_id', $militareId)
                ->where('teatro_operativo_id', $teatroId)
                ->where('cattedra', $cattedra)
                ->where('stato', 'prenotato')
                ->first();

            if ($prenotazione) {
                $prenotazione->conferma();
            }

            // Aggiorna la scadenza approntamento del militare
            $scadenza = ScadenzaApprontamento::updateOrCreate(
                ['militare_id' => $militareId],
                [$cattedra => $valoreDaSalvare]
            );

            DB::commit();

            return $this->successResponse('Partecipazione confermata con successo', [
                'valore' => $scadenza->getValoreFormattato($cattedra),
                'stato' => $scadenza->verificaStato($cattedra),
                'colore' => $scadenza->getColore($cattedra)
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore confermaPrenotazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Ottiene le statistiche delle cattedre per un teatro
     */
    public function getStatisticheCattedre(Request $request)
    {
        try {
            $teatroId = $request->teatro_id;
            
            if (!$teatroId) {
                return $this->errorResponse('Teatro ID richiesto', 400);
            }

            $teatro = TeatroOperativo::with(['militari'])->find($teatroId);
            
            if (!$teatro) {
                return $this->errorResponse(self::ERROR_MESSAGES['teatro_not_found'], 404);
            }

            $militariIds = $teatro->militari->pluck('id')->toArray();
            $totaleMilitari = count($militariIds);

            if ($totaleMilitari === 0) {
                return $this->successResponse('Statistiche calcolate', [
                    'statistiche' => [],
                    'totale_militari' => 0
                ]);
            }

            $militari = Militare::with(['scadenzaApprontamento', 'scadenza'])
                ->whereIn('id', $militariIds)
                ->get();

            // Pre-carica le prenotazioni attive per evitare N+1 query
            $prenotazioniAttive = PrenotazioneApprontamento::where('teatro_operativo_id', $teatroId)
                ->where('stato', 'prenotato')
                ->get()
                ->groupBy('cattedra')
                ->map(function($items) {
                    return $items->pluck('militare_id')->toArray();
                });

            // Ottieni le colonne visibili per questo teatro
            $colonneVisibili = $teatro->getColonneVisibili(ScadenzaApprontamento::COLONNE);

            $statistiche = [];
            foreach ($colonneVisibili as $campo => $config) {
                if ($campo === 'teatro_operativo') continue;

                $prenotatiPerCampo = $prenotazioniAttive->get($campo, []);
                
                $fatte = 0;
                $daFare = 0;
                $prenotate = 0;
                $nonRichieste = 0;

                foreach ($militari as $militare) {
                    $datiCampo = self::getValoreCampo($militare, $campo);
                    $stato = $datiCampo['stato'];

                    if (in_array($militare->id, $prenotatiPerCampo)) {
                        $prenotate++;
                    } elseif (in_array($stato, ['valido', 'in_scadenza'])) {
                        $fatte++;
                    } elseif ($stato === 'non_richiesto') {
                        $nonRichieste++;
                    } else {
                        $daFare++;
                    }
                }

                $statistiche[$campo] = [
                    'label' => $config['label'] ?? $campo,
                    'fatte' => $fatte,
                    'da_fare' => $daFare,
                    'prenotate' => $prenotate,
                    'non_richieste' => $nonRichieste,
                    'totale' => $totaleMilitari
                ];
            }

            return $this->successResponse('Statistiche calcolate', [
                'statistiche' => $statistiche,
                'totale_militari' => $totaleMilitari
            ]);

        } catch (\Exception $e) {
            Log::error('Errore getStatisticheCattedre', [
                'teatro_id' => $request->teatro_id ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Salva la configurazione delle colonne per un teatro
     */
    public function saveConfigColonne(Request $request)
    {
        if (!auth()->user()->hasPermission('admin.access')) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'config' => 'required|array'
            ], [
                'teatro_id.required' => 'Teatro Operativo richiesto',
                'config.required' => 'Configurazione richiesta'
            ]);

            $teatro = TeatroOperativo::find($validated['teatro_id']);
            
            if (!$teatro) {
                return $this->errorResponse(self::ERROR_MESSAGES['teatro_not_found'], 404);
            }

            // Valida la struttura della configurazione
            foreach ($validated['config'] as $campo => $settings) {
                if (!is_array($settings) || !isset($settings['visibile']) || !isset($settings['richiesta'])) {
                    return $this->errorResponse('Struttura configurazione non valida', 400);
                }
            }

            $teatro->config_colonne = $validated['config'];
            $teatro->save();

            return $this->successResponse('Configurazione salvata con successo');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore saveConfigColonne', [
                'teatro_id' => $request->teatro_id ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Ottiene la prenotazione attiva per un militare/cattedra/teatro
     */
    public static function getPrenotazioneAttiva($militareId, $cattedra, $teatroId): ?PrenotazioneApprontamento
    {
        return PrenotazioneApprontamento::where('militare_id', $militareId)
            ->where('teatro_operativo_id', $teatroId)
            ->where('cattedra', $cattedra)
            ->where('stato', 'prenotato')
            ->first();
    }

    /**
     * Esporta in Excel la proposta di militari per prenotazione
     */
    public function exportProposta(Request $request)
    {
        $request->validate([
            'teatro_id' => 'required|exists:teatri_operativi,id',
            'cattedra' => 'required|string',
            'data' => 'required|date',
            'militari_ids' => 'required|string'
        ]);

        $teatroId = $request->teatro_id;
        $cattedra = $request->cattedra;
        $data = Carbon::parse($request->data);
        $militariIds = explode(',', $request->militari_ids);

        // Ottieni il teatro
        $teatro = TeatroOperativo::find($teatroId);
        $nomeCattedra = ScadenzaApprontamento::getLabels()[$cattedra] ?? $cattedra;

        // Ottieni i militari ordinati per grado e anzianità
        $militari = Militare::with(['grado'])
            ->whereIn('id', $militariIds)
            ->get()
            ->sortBy([
                fn($a, $b) => ($b->grado->ordine ?? 0) <=> ($a->grado->ordine ?? 0),
                fn($a, $b) => ($a->anzianita ?? '9999-12-31') <=> ($b->anzianita ?? '9999-12-31')
            ])
            ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Proposta ' . substr($nomeCattedra, 0, 20));

        // Titolo
        $sheet->setCellValue('A1', 'PROPOSTA PRENOTAZIONE CATTEDRA');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);

        // Info
        $sheet->setCellValue('A3', 'Teatro Operativo:');
        $sheet->setCellValue('B3', $teatro->nome);
        $sheet->setCellValue('A4', 'Cattedra:');
        $sheet->setCellValue('B4', $nomeCattedra);
        $sheet->setCellValue('A5', 'Data Prevista:');
        $sheet->setCellValue('B5', $data->format('d/m/Y'));
        $sheet->setCellValue('A6', 'Totale Militari:');
        $sheet->setCellValue('B6', count($militari));

        // Header tabella
        $headers = ['N.', 'Grado', 'Cognome', 'Nome', 'Anzianità'];
        $col = 'A';
        $row = 8;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0A2342']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            $col++;
        }

        // Dati
        $row = 9;
        $numero = 1;
        foreach ($militari as $militare) {
            $sheet->setCellValue('A' . $row, $numero);
            $sheet->setCellValue('B' . $row, $militare->grado->sigla ?? '-');
            $sheet->setCellValue('C' . $row, $militare->cognome);
            $sheet->setCellValue('D' . $row, $militare->nome);
            $sheet->setCellValue('E' . $row, $militare->anzianita ? Carbon::parse($militare->anzianita)->format('d/m/Y') : '-');
            
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
            
            $row++;
            $numero++;
        }

        // Auto-size colonne
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $writer = new Xlsx($spreadsheet);
        $filename = 'Proposta_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nomeCattedra) . '_' . $data->format('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
