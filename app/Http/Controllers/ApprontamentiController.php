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
use App\Models\TipoServizio;
use App\Models\OreMcmMilitare;
use App\Models\ScadenzaMilitare;
use App\Models\ConfigColonnaApprontamento;
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
use App\Services\PrenotazioneApprontamentoService;

class ApprontamentiController extends Controller
{
    /**
     * Service per la gestione delle prenotazioni
     */
    protected PrenotazioneApprontamentoService $prenotazioneService;

    /**
     * Costruttore
     */
    public function __construct(PrenotazioneApprontamentoService $prenotazioneService)
    {
        $this->prenotazioneService = $prenotazioneService;
    }

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
        $filtri = $request->only(array_keys(ScadenzaApprontamento::getColonne()));
        if (!empty(array_filter($filtri)) && $militari->isNotEmpty()) {
            $militari = $this->applicaFiltri($militari, $filtri);
        }

        // Verifica se l'utente può modificare le scadenze
        $canEdit = auth()->user()->hasPermission('approntamenti.edit') 
                || auth()->user()->hasPermission('admin.access');

        $colonne = ScadenzaApprontamento::getColonne();

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
            
            $valoreRaw = $scadenza->$campo ?? '';
            // Assicura che valore_raw sia sempre una stringa
            if (!is_string($valoreRaw)) {
                $valoreRaw = '';
            }
            
            return [
                'valore' => $scadenza->getValoreFormattato($campo),
                'valore_raw' => $valoreRaw,
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
            if (!array_key_exists($campo, ScadenzaApprontamento::getColonne())) {
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

        $colonne = ConfigColonnaApprontamento::orderBy('ordine')->get();
        
        return view('approntamenti.gestione', compact('colonne'));
    }

    /**
     * Crea una nuova colonna approntamento
     */
    public function storeColonna(Request $request)
    {
        $request->validate([
            'label' => 'required|string|max:100',
            'scadenza_mesi' => 'nullable|integer|min:1|max:120',
        ]);

        // Genera campo_db dal label (slug)
        $campoDb = \Illuminate\Support\Str::slug($request->label, '_');
        
        // Verifica unicità
        if (ConfigColonnaApprontamento::where('campo_db', $campoDb)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Esiste già una colonna con questo nome'
            ], 422);
        }

        $colonna = ConfigColonnaApprontamento::create([
            'campo_db' => $campoDb,
            'label' => $request->label,
            'scadenza_mesi' => $request->scadenza_mesi,
            'fonte' => 'approntamenti',
            'campo_sorgente' => null,
            'attivo' => true,
            'ordine' => ConfigColonnaApprontamento::getNextOrdine(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Colonna creata con successo',
            'colonna' => $colonna
        ]);
    }

    /**
     * Aggiorna una colonna approntamento
     */
    public function updateColonna(Request $request, $id)
    {
        $colonna = ConfigColonnaApprontamento::findOrFail($id);

        $request->validate([
            'label' => 'required|string|max:100',
            'scadenza_mesi' => 'nullable|integer|min:1|max:120',
        ]);

        $colonna->update([
            'label' => $request->label,
            'scadenza_mesi' => $request->scadenza_mesi ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Colonna aggiornata con successo',
            'colonna' => $colonna->fresh()
        ]);
    }

    /**
     * Elimina una colonna approntamento
     */
    public function destroyColonna($id)
    {
        $colonna = ConfigColonnaApprontamento::findOrFail($id);

        // Non permettere eliminazione di colonne condivise con scadenze_militari
        if ($colonna->fonte === 'scadenze_militari') {
            return response()->json([
                'success' => false,
                'message' => 'Non è possibile eliminare colonne condivise con il sistema SPP'
            ], 422);
        }

        $colonna->delete();

        return response()->json([
            'success' => true,
            'message' => 'Colonna eliminata con successo'
        ]);
    }

    /**
     * Toggle attivo/disattivo colonna
     */
    public function toggleColonna($id)
    {
        $colonna = ConfigColonnaApprontamento::findOrFail($id);
        $colonna->update(['attivo' => !$colonna->attivo]);

        return response()->json([
            'success' => true,
            'message' => $colonna->attivo ? 'Colonna attivata' : 'Colonna disattivata',
            'attivo' => $colonna->attivo
        ]);
    }

    /**
     * Aggiorna l'ordine delle colonne
     */
    public function updateOrdine(Request $request)
    {
        $request->validate([
            'ordine' => 'required|array',
            'ordine.*' => 'integer|exists:config_colonne_approntamenti,id'
        ]);

        foreach ($request->ordine as $index => $id) {
            ConfigColonnaApprontamento::where('id', $id)->update(['ordine' => $index + 1]);
        }

        ConfigColonnaApprontamento::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Ordine aggiornato con successo'
        ]);
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
        $filtri = $request->only(array_keys(ScadenzaApprontamento::getColonne()));
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
        foreach (array_keys(ScadenzaApprontamento::getColonne()) as $campo) {
            $statsCattedre[$campo] = ['fatto' => 0, 'mancante' => 0, 'label' => $colonneLabels[$campo] ?? $campo];
        }
        foreach ($militari as $militare) {
            foreach (array_keys(ScadenzaApprontamento::getColonne()) as $campo) {
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
            foreach (array_keys(ScadenzaApprontamento::getColonne()) as $campo) {
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
     * Propone militari per una prenotazione cattedra (unificato per tutte le cattedre incluso MCM)
     * Restituisce sia i disponibili che i non disponibili con motivazione
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
            $isMcm = ($cattedra === 'mcm');

            // Verifica che la cattedra sia valida
            if (!array_key_exists($cattedra, ScadenzaApprontamento::getColonne())) {
                return $this->errorResponse('Cattedra non valida', 400);
            }

            // Ottieni il teatro con i militari assegnati
            $teatro = TeatroOperativo::with(['militari' => function($query) {
                $query->with(['grado', 'scadenzaApprontamento', 'scadenza']);
            }])->find($teatroId);

            if (!$teatro) {
                return $this->errorResponse(self::ERROR_MESSAGES['teatro_not_found'], 404);
            }

            $militariIds = $teatro->militari->pluck('id')->toArray();

            if (empty($militariIds)) {
                return $this->successResponse('Nessun militare assegnato a questo teatro operativo', [
                    'militari_disponibili' => [],
                    'militari_non_disponibili' => [],
                    'totale_disponibili' => 0,
                    'totale_proposti' => 0,
                    'is_mcm' => $isMcm,
                    'ore_giornata' => $isMcm ? OreMcmMilitare::calcolaOrePerData($data) : null
                ]);
            }

            // Ottieni i militari con le relazioni necessarie
            $militari = Militare::with(['grado', 'scadenzaApprontamento', 'scadenza'])
                ->whereIn('id', $militariIds)
                ->get();

            // Prenotazioni esistenti
            $prenotazioniEsistenti = PrenotazioneApprontamento::where('teatro_operativo_id', $teatroId)
                ->where('cattedra', $cattedra)
                ->where('stato', 'prenotato')
                ->pluck('militare_id')
                ->toArray();

            $militariDisponibili = [];
            $militariNonDisponibili = [];

            foreach ($militari as $militare) {
                // Dati base del militare
                $datiCampo = self::getValoreCampo($militare, $cattedra);
                $disponibilita = $this->getDisponibilitaMilitare($militare, $data);
                
                $datiMilitare = [
                    'id' => $militare->id,
                    'grado' => $militare->grado->sigla ?? '-',
                    'grado_ordine' => $militare->grado->ordine ?? 0,
                    'cognome' => $militare->cognome,
                    'nome' => $militare->nome,
                    'anzianita' => $militare->anzianita ? Carbon::parse($militare->anzianita)->format('d/m/Y') : '-',
                    'anzianita_raw' => $militare->anzianita ?? '9999-12-31',
                    'stato_cattedra' => $datiCampo['stato'],
                    'valore_cattedra' => $datiCampo['valore']
                ];

                // Per MCM, aggiungi info ore
                if ($isMcm) {
                    $dettagliMcm = OreMcmMilitare::getDettagliMcmMilitare($militare->id);
                    $datiMilitare['ore_svolte'] = $dettagliMcm['ore_svolte'];
                    $datiMilitare['ore_rimanenti'] = $dettagliMcm['ore_rimanenti'];
                    $datiMilitare['percentuale_mcm'] = $dettagliMcm['percentuale'];
                    $datiMilitare['mcm_completato'] = $dettagliMcm['completato'];
                    $datiMilitare['idoneita_smi_valida'] = $this->haIdoneitaSmiValida($militare);
                    $datiMilitare['idoneita_to_valida'] = $this->haIdoneitaToValida($militare);
                }

                // Verifica disponibilità
                $motivoNonDisponibile = null;

                // 1. Cattedra già valida?
                if (in_array($datiCampo['stato'], ['valido', 'in_scadenza'])) {
                    $motivoNonDisponibile = 'Cattedra già valida';
                }
                // 2. Per MCM: ha già completato?
                elseif ($isMcm && ($datiMilitare['mcm_completato'] ?? false)) {
                    $motivoNonDisponibile = 'MCM già completato (40 ore)';
                }
                // 3. Per MCM: idoneità SMI o T.O. valida?
                elseif ($isMcm && !$this->haIdoneitaValidaPerMcm($militare)) {
                    $motivoNonDisponibile = 'Idoneità SMI o T.O. non valida';
                }
                // 4. Già prenotato?
                elseif (in_array($militare->id, $prenotazioniEsistenti)) {
                    $motivoNonDisponibile = 'Già prenotato per questa cattedra';
                }
                // 5. Impegnato nel CPT?
                elseif (!$disponibilita['disponibile']) {
                    $motivoNonDisponibile = $disponibilita['motivo'] ?? 'Impegnato';
                    $datiMilitare['codice_impegno'] = $disponibilita['codice'];
                }
                // 6. Per MCM: già ha sessione pianificata?
                elseif ($isMcm && OreMcmMilitare::haSessionePerData($militare->id, $data)) {
                    $motivoNonDisponibile = 'Sessione MCM già pianificata per questa data';
                }
                // 7. Per C-IED pratico: requisito AWARENESS C-IED
                elseif ($cattedra === 'cied_pratico') {
                    $requisitoAwareness = $this->haRequisitoAwarenessCied($militare, $data, $teatroId);
                    if (!$requisitoAwareness['valido']) {
                        $motivoNonDisponibile = $requisitoAwareness['motivo'];
                    }
                }

                if ($motivoNonDisponibile) {
                    $datiMilitare['motivo_non_disponibile'] = $motivoNonDisponibile;
                    $militariNonDisponibili[] = $datiMilitare;
                } else {
                    $militariDisponibili[] = $datiMilitare;
                }
            }

            // Ordina disponibili per grado (decrescente) e anzianità
            usort($militariDisponibili, function($a, $b) {
                if ($a['grado_ordine'] !== $b['grado_ordine']) {
                    return $b['grado_ordine'] <=> $a['grado_ordine'];
                }
                return $a['anzianita_raw'] <=> $b['anzianita_raw'];
            });

            // Ordina non disponibili allo stesso modo
            usort($militariNonDisponibili, function($a, $b) {
                if ($a['grado_ordine'] !== $b['grado_ordine']) {
                    return $b['grado_ordine'] <=> $a['grado_ordine'];
                }
                return $a['anzianita_raw'] <=> $b['anzianita_raw'];
            });

            // Limita i disponibili alla quantità richiesta
            $militariProposti = array_slice($militariDisponibili, 0, $quantita);

            return $this->successResponse('Ricerca completata', [
                'militari_disponibili' => $militariProposti,
                'militari_non_disponibili' => $militariNonDisponibili,
                'totale_disponibili' => count($militariDisponibili),
                'totale_proposti' => count($militariProposti),
                'totale_non_disponibili' => count($militariNonDisponibili),
                'is_mcm' => $isMcm,
                'ore_giornata' => $isMcm ? OreMcmMilitare::calcolaOrePerData($data) : null,
                'data_formattata' => $data->format('d/m/Y'),
                'giorno_settimana' => $data->locale('it')->dayName
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
        $risultato = $this->getDisponibilitaMilitare($militare, $data);
        return $risultato['disponibile'];
    }

    /**
     * Verifica la disponibilità di un militare e restituisce anche il motivo dell'impegno
     * 
     * @return array ['disponibile' => bool, 'motivo' => string|null, 'codice' => string|null]
     */
    private function getDisponibilitaMilitare(Militare $militare, Carbon $data): array
    {
        // Trova la pianificazione mensile per quel mese/anno
        $pianificazioneMensile = PianificazioneMensile::where('mese', $data->month)
            ->where('anno', $data->year)
            ->first();

        if (!$pianificazioneMensile) {
            return ['disponibile' => true, 'motivo' => null, 'codice' => null];
        }

        // Cerca pianificazione giornaliera per il militare in quella data
        $pianificazioneGiornaliera = PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
            ->where('militare_id', $militare->id)
            ->where('giorno', $data->day)
            ->whereNotNull('tipo_servizio_id')
            ->with('tipoServizio')
            ->first();

        if (!$pianificazioneGiornaliera) {
            return ['disponibile' => true, 'motivo' => null, 'codice' => null];
        }

        $tipoServizio = $pianificazioneGiornaliera->tipoServizio;
        if ($tipoServizio) {
            // Codici che indicano indisponibilità
            $codiciAssenza = ['LS', 'LO', 'LM', 'LC', 'RMD', 'IS', 'TIR', 'TRAS', 'TO', 'MIS', 'MAL', 'PERM', 'RIP', 'LIC', 'CONGEDO'];
            
            if (in_array($tipoServizio->codice, $codiciAssenza)) {
                return [
                    'disponibile' => false,
                    'motivo' => $tipoServizio->descrizione ?? $tipoServizio->codice,
                    'codice' => $tipoServizio->codice
                ];
            }
            
            // Se ha un servizio assegnato, non è libero
            return [
                'disponibile' => false,
                'motivo' => $tipoServizio->descrizione ?? $tipoServizio->codice,
                'codice' => $tipoServizio->codice
            ];
        }

        return ['disponibile' => true, 'motivo' => null, 'codice' => null];
    }

    /**
     * Salva le prenotazioni per una cattedra
     * Sincronizza automaticamente con CPT e Board Attività
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
            if (!array_key_exists($validated['cattedra'], ScadenzaApprontamento::getColonne())) {
                return $this->errorResponse('Cattedra non valida', 400);
            }

            // Usa il service per creare le prenotazioni (gestisce anche CPT e Board)
            $result = $this->prenotazioneService->creaPrenotazione(
                $validated['teatro_id'],
                $validated['cattedra'],
                Carbon::parse($validated['data']),
                $validated['militari_ids'],
                auth()->id()
            );

            if ($result['success']) {
                return $this->successResponse($result['message'], ['count' => $result['count']]);
            } else {
                return $this->errorResponse($result['message'], 500);
            }

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore salvaPrenotazione', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Sincronizza una prenotazione cattedra con il CPT
     */
    private function sincronizzaPrenotazioneConCPT(string $cattedraLabel, Carbon $data, array $militariIds)
    {
        try {
            // Trova il tipo servizio "Cattedra" o crea uno generico
            $tipoServizio = TipoServizio::where('codice', 'Cattedra')
                ->where('attivo', true)
                ->first();
            
            if (!$tipoServizio) {
                // Prova con codice alternativo
                $tipoServizio = TipoServizio::where('codice', 'CATT')
                    ->where('attivo', true)
                    ->first();
            }
            
            if (!$tipoServizio) {
                Log::warning('Tipo servizio Cattedra non trovato per sincronizzazione CPT', [
                    'cattedra' => $cattedraLabel,
                    'suggerimento' => 'Crea un tipo servizio con codice "Cattedra" in Gestionale > Gestione CPT'
                ]);
                return;
            }

            // Trova o crea la pianificazione mensile
            $pianificazioneMensile = PianificazioneMensile::firstOrCreate(
                [
                    'mese' => $data->month,
                    'anno' => $data->year,
                ],
                [
                    'nome' => $data->translatedFormat('F Y'),
                    'stato' => 'attiva',
                    'data_creazione' => $data->format('Y-m-d'),
                ]
            );

            // Per ogni militare, crea la pianificazione giornaliera
            foreach ($militariIds as $militareId) {
                // Verifica se esiste già
                $esisteGia = PianificazioneGiornaliera::where([
                    'pianificazione_mensile_id' => $pianificazioneMensile->id,
                    'militare_id' => $militareId,
                    'giorno' => $data->day,
                ])->where('note', 'like', "%{$cattedraLabel}%")->exists();

                if (!$esisteGia) {
                    PianificazioneGiornaliera::create([
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'militare_id' => $militareId,
                        'giorno' => $data->day,
                        'tipo_servizio_id' => $tipoServizio->id,
                        'note' => "Cattedra: {$cattedraLabel}"
                    ]);
                }
            }

            Log::info('Prenotazione sincronizzata con CPT', [
                'cattedra' => $cattedraLabel,
                'data' => $data->format('Y-m-d'),
                'militari_count' => count($militariIds)
            ]);

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione CPT da prenotazione', [
                'cattedra' => $cattedraLabel,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sincronizza una prenotazione cattedra con la Board Attività
     */
    private function sincronizzaPrenotazioneConBoard(string $cattedraLabel, Carbon $data, array $militariIds, int $teatroId)
    {
        try {
            // Trova la colonna "Cattedre" nella board
            $columnaCattedre = BoardColumn::where('slug', 'cattedre')->first();
            
            if (!$columnaCattedre) {
                // Prova con nome
                $columnaCattedre = BoardColumn::where('name', 'like', '%cattedre%')->first();
            }
            
            if (!$columnaCattedre) {
                Log::warning('Colonna Cattedre non trovata nella Board', [
                    'cattedra' => $cattedraLabel,
                    'suggerimento' => 'Crea una colonna con slug "cattedre" nella Board'
                ]);
                return;
            }

            // Verifica se esiste già un'attività per questa cattedra e data
            $titolo = "{$cattedraLabel} - Approntamento";
            
            $attivitaEsistente = BoardActivity::where('title', $titolo)
                ->where('start_date', $data->format('Y-m-d'))
                ->first();

            if ($attivitaEsistente) {
                // Aggiungi i militari all'attività esistente
                $nuoviMilitari = array_diff($militariIds, $attivitaEsistente->militari()->pluck('militare_id')->toArray());
                if (!empty($nuoviMilitari)) {
                    $attivitaEsistente->militari()->attach($nuoviMilitari);
                    Log::info('Militari aggiunti ad attività Board esistente', [
                        'activity_id' => $attivitaEsistente->id,
                        'nuovi_militari' => count($nuoviMilitari)
                    ]);
                }
            } else {
                // Crea una nuova attività
                $teatro = TeatroOperativo::find($teatroId);
                
                // Determina la compagnia mounting (usa la prima compagnia dei militari) - mantenuto per legacy
                $primoMilitare = Militare::find($militariIds[0]);
                $compagniaMountingId = $primoMilitare?->compagnia_id ?? 1;

                // organizational_unit_id viene assegnato automaticamente dal trait BelongsToOrganizationalUnit
                $activity = BoardActivity::create([
                    'title' => $titolo,
                    'description' => "Cattedra approntamento per " . ($teatro->nome ?? 'Teatro Operativo'),
                    'start_date' => $data->format('Y-m-d'),
                    'end_date' => $data->format('Y-m-d'),
                    'column_id' => $columnaCattedre->id,
                    'compagnia_id' => $compagniaMountingId, // Mantenuto per compatibilità legacy
                    'compagnia_mounting_id' => $compagniaMountingId, // Mantenuto per compatibilità legacy
                    'sigla_cpt_suggerita' => 'Cattedra',
                    'created_by' => auth()->id() ?? 1,
                    'order' => BoardActivity::where('column_id', $columnaCattedre->id)->max('order') + 1
                ]);

                // Associa i militari
                $activity->militari()->attach($militariIds);

                Log::info('Creata attività Board per prenotazione', [
                    'activity_id' => $activity->id,
                    'titolo' => $titolo,
                    'militari_count' => count($militariIds)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione Board da prenotazione', [
                'cattedra' => $cattedraLabel,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            $colonneVisibili = $teatro->getColonneVisibili(ScadenzaApprontamento::getColonne());

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

    // ==========================================
    // METODI MCM - Gestione Ore e Prenotazioni
    // ==========================================

    /**
     * Propone militari per una prenotazione MCM multi-giorno
     * 
     * Requisiti per MCM:
     * - 40 ore totali per completamento
     * - Lun-Gio: 8 ore/giorno, Ven: 4 ore/giorno
     * - Idoneità SMI o T.O. valida
     * - Libero nel CPT per tutte le date selezionate
     */
    public function proponiPrenotazioneMcm(Request $request)
    {
        try {
            $validated = $request->validate([
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'date' => 'required|array|min:1',
                'date.*' => 'required|date',
                'quantita' => 'required|integer|min:1|max:100'
            ], [
                'teatro_id.required' => 'Seleziona un Teatro Operativo',
                'teatro_id.exists' => 'Teatro Operativo non valido',
                'date.required' => 'Seleziona almeno una data',
                'date.min' => 'Seleziona almeno una data',
                'date.*.date' => 'Formato data non valido',
                'quantita.required' => 'Specifica il numero di militari',
                'quantita.min' => 'Il numero minimo è 1',
                'quantita.max' => 'Il numero massimo è 100'
            ]);

            $teatroId = $validated['teatro_id'];
            $date = array_map(fn($d) => Carbon::parse($d), $validated['date']);
            $quantita = $validated['quantita'];

            // Calcola ore totali per le date selezionate
            $oreTotaliPianificate = OreMcmMilitare::calcolaOreTotaliPerDate($date);

            // Ottieni il teatro con i militari assegnati
            $teatro = TeatroOperativo::with(['militari' => function($query) {
                $query->with(['grado', 'scadenzaApprontamento', 'scadenza']);
            }])->find($teatroId);

            if (!$teatro) {
                return $this->errorResponse(self::ERROR_MESSAGES['teatro_not_found'], 404);
            }

            $militariIds = $teatro->militari->pluck('id')->toArray();

            if (empty($militariIds)) {
                return $this->successResponse('Nessun militare assegnato a questo teatro operativo', [
                    'militari' => [],
                    'totale_disponibili' => 0,
                    'totale_proposti' => 0,
                    'ore_pianificate' => $oreTotaliPianificate,
                    'ore_richieste' => OreMcmMilitare::ORE_TOTALI_RICHIESTE
                ]);
            }

            // Ottieni i militari con le relazioni necessarie
            $militari = Militare::with(['grado', 'scadenzaApprontamento', 'scadenza'])
                ->whereIn('id', $militariIds)
                ->get();

            // Filtra: solo chi NON ha MCM completato (ore < 40)
            $militariSenzaMcmCompleto = $militari->filter(function($militare) {
                return !OreMcmMilitare::haCompletatoMcm($militare->id);
            });

            // Filtra: solo chi ha idoneità SMI o T.O. valida
            $militariConIdoneita = $militariSenzaMcmCompleto->filter(function($militare) {
                return $this->haIdoneitaValidaPerMcm($militare);
            });

            // Filtra: solo chi è libero nel CPT in TUTTE le date selezionate
            $militariLiberi = $militariConIdoneita->filter(function($militare) use ($date) {
                foreach ($date as $data) {
                    if (!$this->isMilitareLiberoNelCPT($militare, $data)) {
                        return false;
                    }
                }
                return true;
            });

            // Filtra: escludi chi ha già sessioni MCM pianificate per le stesse date
            $militariDisponibili = $militariLiberi->filter(function($militare) use ($date) {
                foreach ($date as $data) {
                    if (OreMcmMilitare::haSessionePerData($militare->id, $data)) {
                        return false;
                    }
                }
                return true;
            });

            // Ordina per:
            // 1. Ore rimanenti (chi ha più ore da fare viene prima)
            // 2. Grado (decrescente)
            // 3. Anzianità (più anziani prima)
            $militariOrdinati = $militariDisponibili->sortBy([
                fn($a, $b) => OreMcmMilitare::getOreRimanentiMilitare($b->id) <=> OreMcmMilitare::getOreRimanentiMilitare($a->id),
                fn($a, $b) => ($b->grado->ordine ?? 0) <=> ($a->grado->ordine ?? 0),
                fn($a, $b) => ($a->anzianita ?? '9999-12-31') <=> ($b->anzianita ?? '9999-12-31')
            ])->take($quantita)->values();

            // Prepara i dati per la risposta
            $risultato = $militariOrdinati->map(function($militare) {
                $dettagliMcm = OreMcmMilitare::getDettagliMcmMilitare($militare->id);
                
                return [
                    'id' => $militare->id,
                    'grado' => $militare->grado->sigla ?? '-',
                    'grado_ordine' => $militare->grado->ordine ?? 0,
                    'cognome' => $militare->cognome,
                    'nome' => $militare->nome,
                    'anzianita' => $militare->anzianita ? Carbon::parse($militare->anzianita)->format('d/m/Y') : '-',
                    'ore_svolte' => $dettagliMcm['ore_svolte'],
                    'ore_rimanenti' => $dettagliMcm['ore_rimanenti'],
                    'percentuale_mcm' => $dettagliMcm['percentuale'],
                    'idoneita_smi_valida' => $this->haIdoneitaSmiValida($militare),
                    'idoneita_to_valida' => $this->haIdoneitaToValida($militare)
                ];
            });

            return $this->successResponse('Ricerca completata', [
                'militari' => $risultato,
                'totale_disponibili' => $militariDisponibili->count(),
                'totale_proposti' => $risultato->count(),
                'ore_pianificate' => $oreTotaliPianificate,
                'ore_richieste' => OreMcmMilitare::ORE_TOTALI_RICHIESTE,
                'date_selezionate' => array_map(fn($d) => $d->format('d/m/Y'), $date),
                'dettaglio_ore_giornaliere' => array_map(function($d) {
                    return [
                        'data' => $d->format('d/m/Y'),
                        'giorno' => $d->locale('it')->dayName,
                        'ore' => OreMcmMilitare::calcolaOrePerData($d)
                    ];
                }, $date)
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore proponiPrenotazioneMcm', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Salva le prenotazioni MCM multi-giorno
     */
    public function salvaPrenotazioneMcm(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'teatro_id' => 'required|exists:teatri_operativi,id',
                'date' => 'required|array|min:1',
                'date.*' => 'required|date',
                'militari_ids' => 'required|array|min:1',
                'militari_ids.*' => 'exists:militari,id',
                'note' => 'nullable|string|max:500'
            ], [
                'teatro_id.required' => 'Teatro Operativo richiesto',
                'date.required' => 'Seleziona almeno una data',
                'date.min' => 'Seleziona almeno una data',
                'militari_ids.required' => 'Seleziona almeno un militare',
                'militari_ids.min' => 'Seleziona almeno un militare'
            ]);

            $teatroId = $validated['teatro_id'];
            $date = array_map(fn($d) => Carbon::parse($d), $validated['date']);
            $militariIds = $validated['militari_ids'];
            $note = $validated['note'] ?? null;

            DB::beginTransaction();

            $sessioniCreate = 0;
            $giàPianificate = 0;
            $errori = [];

            foreach ($militariIds as $militareId) {
                // Verifica che il militare abbia idoneità valida
                $militare = Militare::with(['scadenza'])->find($militareId);
                if (!$militare || !$this->haIdoneitaValidaPerMcm($militare)) {
                    $errori[] = "Militare ID {$militareId}: idoneità SMI o T.O. non valida";
                    continue;
                }

                // Verifica che non abbia già completato MCM
                if (OreMcmMilitare::haCompletatoMcm($militareId)) {
                    $errori[] = "{$militare->cognome} {$militare->nome}: MCM già completato";
                    continue;
                }

                foreach ($date as $data) {
                    // Verifica che non esista già una sessione per quella data
                    if (OreMcmMilitare::haSessionePerData($militareId, $data)) {
                        $giàPianificate++;
                        continue;
                    }

                    // Calcola le ore per quella giornata
                    $ore = OreMcmMilitare::calcolaOrePerData($data);
                    
                    // Non pianificare weekend (0 ore)
                    if ($ore === 0) {
                        continue;
                    }

                    OreMcmMilitare::create([
                        'militare_id' => $militareId,
                        'teatro_operativo_id' => $teatroId,
                        'data' => $data,
                        'ore' => $ore,
                        'stato' => OreMcmMilitare::STATO_PIANIFICATO,
                        'note' => $note,
                        'created_by' => auth()->id()
                    ]);
                    $sessioniCreate++;
                }
            }

            DB::commit();

            $message = "Sessioni MCM create: {$sessioniCreate}";
            if ($giàPianificate > 0) {
                $message .= " ({$giàPianificate} già pianificate)";
            }
            if (!empty($errori)) {
                $message .= ". Attenzione: " . implode('; ', $errori);
            }

            return $this->successResponse($message, [
                'sessioni_create' => $sessioniCreate,
                'gia_pianificate' => $giàPianificate,
                'errori' => $errori
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvaPrenotazioneMcm', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Ottiene i dettagli MCM di un militare
     */
    public function getDettagliMcmMilitare(Request $request, $militareId)
    {
        try {
            $militare = Militare::with(['grado', 'scadenza'])->find($militareId);
            
            if (!$militare) {
                return $this->errorResponse(self::ERROR_MESSAGES['militare_not_found'], 404);
            }

            $teatroId = $request->get('teatro_id');
            $dettagli = OreMcmMilitare::getDettagliMcmMilitare($militareId, $teatroId);
            
            // Aggiungi info idoneità
            $dettagli['idoneita'] = [
                'smi_valida' => $this->haIdoneitaSmiValida($militare),
                'to_valida' => $this->haIdoneitaToValida($militare),
                'puo_fare_mcm' => $this->haIdoneitaValidaPerMcm($militare)
            ];
            
            $dettagli['militare'] = [
                'id' => $militare->id,
                'grado' => $militare->grado->sigla ?? '-',
                'cognome' => $militare->cognome,
                'nome' => $militare->nome
            ];

            return $this->successResponse('Dettagli MCM recuperati', $dettagli);

        } catch (\Exception $e) {
            Log::error('Errore getDettagliMcmMilitare', [
                'militare_id' => $militareId,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Conferma una sessione MCM (da pianificato a completato)
     */
    public function confermaSessioneMcm(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'sessione_id' => 'required|exists:ore_mcm_militari,id'
            ]);

            $sessione = OreMcmMilitare::find($validated['sessione_id']);
            
            if ($sessione->isCompletata()) {
                return $this->errorResponse('Sessione già completata', 400);
            }

            if ($sessione->isAnnullata()) {
                return $this->errorResponse('Sessione annullata', 400);
            }

            $sessione->completa();

            // Verifica se il militare ha completato MCM
            $dettagli = OreMcmMilitare::getDettagliMcmMilitare($sessione->militare_id);
            
            // Se ha completato le 40 ore, aggiorna anche la data MCM in scadenze_approntamenti
            if ($dettagli['completato']) {
                ScadenzaApprontamento::updateOrCreate(
                    ['militare_id' => $sessione->militare_id],
                    ['mcm' => Carbon::now()->format('Y-m-d')]
                );
            }

            return $this->successResponse('Sessione MCM confermata', [
                'sessione_id' => $sessione->id,
                'ore_totali' => $dettagli['ore_svolte'],
                'mcm_completato' => $dettagli['completato']
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore confermaSessioneMcm', [
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Annulla una sessione MCM
     */
    public function annullaSessioneMcm(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'sessione_id' => 'required|exists:ore_mcm_militari,id'
            ]);

            $sessione = OreMcmMilitare::find($validated['sessione_id']);
            
            if ($sessione->isAnnullata()) {
                return $this->errorResponse('Sessione già annullata', 400);
            }

            $sessione->annulla();

            return $this->successResponse('Sessione MCM annullata', [
                'sessione_id' => $sessione->id
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore annullaSessioneMcm', [
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Calcola le ore per un set di date (utility endpoint)
     */
    public function calcolaOreMcm(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|array|min:1',
                'date.*' => 'required|date'
            ]);

            $date = array_map(fn($d) => Carbon::parse($d), $validated['date']);
            
            $dettaglio = array_map(function($d) {
                return [
                    'data' => $d->format('Y-m-d'),
                    'data_formattata' => $d->format('d/m/Y'),
                    'giorno' => $d->locale('it')->dayName,
                    'ore' => OreMcmMilitare::calcolaOrePerData($d)
                ];
            }, $date);

            $oreTotali = array_sum(array_column($dettaglio, 'ore'));

            return $this->successResponse('Ore calcolate', [
                'ore_totali' => $oreTotali,
                'ore_richieste' => OreMcmMilitare::ORE_TOTALI_RICHIESTE,
                'dettaglio' => $dettaglio
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        }
    }

    // ==========================================
    // METODI HELPER PER MCM
    // ==========================================

    /**
     * Verifica se un militare ha idoneità SMI valida
     */
    private function haIdoneitaSmiValida(Militare $militare): bool
    {
        $scadenza = $militare->scadenza;
        if (!$scadenza) {
            return false;
        }

        $stato = $scadenza->verificaStato('idoneita_smi');
        return in_array($stato, ['valido', 'in_scadenza']);
    }

    /**
     * Verifica se un militare ha idoneità T.O. valida
     */
    private function haIdoneitaToValida(Militare $militare): bool
    {
        $scadenza = $militare->scadenza;
        if (!$scadenza) {
            return false;
        }

        $stato = $scadenza->verificaStato('idoneita_to');
        return in_array($stato, ['valido', 'in_scadenza']);
    }

    /**
     * Verifica se un militare ha idoneità valida per MCM (SMI O T.O.)
     */
    private function haIdoneitaValidaPerMcm(Militare $militare): bool
    {
        return $this->haIdoneitaSmiValida($militare) || $this->haIdoneitaToValida($militare);
    }

    /**
     * Esporta in Excel l'elenco dei militari proposti per una prenotazione
     * Ordinati per grado (decrescente) e anzianità
     */
    public function exportPropostaMilitari(Request $request)
    {
        $request->validate([
            'teatro_id' => 'required|exists:teatri_operativi,id',
            'cattedra' => 'required|string',
            'data' => 'required|date',
            'militari' => 'required|string' // JSON string con i dati dei militari
        ]);

        $teatroId = $request->teatro_id;
        $cattedra = $request->cattedra;
        $data = Carbon::parse($request->data);
        $militariData = json_decode($request->militari, true);
        $isMcm = ($cattedra === 'mcm');

        // Ottieni il teatro
        $teatro = TeatroOperativo::find($teatroId);
        $nomeCattedra = ScadenzaApprontamento::getLabels()[$cattedra] ?? $cattedra;

        // Ordina i militari per grado e anzianità
        usort($militariData, function($a, $b) {
            if (($a['grado_ordine'] ?? 0) !== ($b['grado_ordine'] ?? 0)) {
                return ($b['grado_ordine'] ?? 0) <=> ($a['grado_ordine'] ?? 0);
            }
            return ($a['anzianita_raw'] ?? '9999-12-31') <=> ($b['anzianita_raw'] ?? '9999-12-31');
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prenotazione ' . substr($nomeCattedra, 0, 20));

        // Stili
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ];

        // Titolo
        $sheet->setCellValue('A1', 'PRENOTAZIONE CATTEDRA');
        $lastCol = $isMcm ? 'H' : 'F';
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Info
        $sheet->setCellValue('A3', 'Teatro Operativo:');
        $sheet->setCellValue('B3', $teatro->nome ?? '-');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        
        $sheet->setCellValue('A4', 'Cattedra:');
        $sheet->setCellValue('B4', $nomeCattedra);
        $sheet->getStyle('A4')->getFont()->setBold(true);
        
        $sheet->setCellValue('A5', 'Data:');
        $sheet->setCellValue('B5', $data->format('d/m/Y') . ' (' . $data->locale('it')->dayName . ')');
        $sheet->getStyle('A5')->getFont()->setBold(true);
        
        $sheet->setCellValue('A6', 'Totale Militari:');
        $sheet->setCellValue('B6', count($militariData));
        $sheet->getStyle('A6')->getFont()->setBold(true);

        if ($isMcm) {
            $sheet->setCellValue('A7', 'Ore Giornata:');
            $sheet->setCellValue('B7', OreMcmMilitare::calcolaOrePerData($data));
            $sheet->getStyle('A7')->getFont()->setBold(true);
        }

        // Header tabella
        $row = $isMcm ? 9 : 8;
        $headers = ['N.', 'Grado', 'Cognome', 'Nome', 'Anzianità', 'Stato Cattedra'];
        if ($isMcm) {
            $headers = array_merge($headers, ['Ore Svolte', 'Ore Rimanenti']);
        }
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(25);

        // Dati
        $dataRow = $row + 1;
        $numero = 1;
        foreach ($militariData as $m) {
            $col = 'A';
            $sheet->setCellValue($col++ . $dataRow, $numero);
            $sheet->setCellValue($col++ . $dataRow, $m['grado'] ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $m['cognome'] ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $m['nome'] ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $m['anzianita'] ?? '-');
            $sheet->setCellValue($col++ . $dataRow, ucfirst(str_replace('_', ' ', $m['stato_cattedra'] ?? '-')));
            
            if ($isMcm) {
                $sheet->setCellValue($col++ . $dataRow, ($m['ore_svolte'] ?? 0) . '/40');
                $sheet->setCellValue($col++ . $dataRow, $m['ore_rimanenti'] ?? 40);
            }
            
            // Stile riga alternata
            $bgColor = ($numero % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
            $sheet->getStyle("A{$dataRow}:{$lastCol}{$dataRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]]
            ]);
            
            $dataRow++;
            $numero++;
        }

        // Auto-size colonne
        foreach (range('A', $lastCol) as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Timestamp
        $sheet->setCellValue('A' . ($dataRow + 1), 'Generato il ' . date('d/m/Y H:i'));
        $sheet->getStyle('A' . ($dataRow + 1))->getFont()->setItalic(true)->setSize(9);

        // Output
        $writer = new Xlsx($spreadsheet);
        $filename = 'Prenotazione_' . preg_replace('/[^a-zA-Z0-9]/', '_', $nomeCattedra) . '_' . $data->format('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    // ==========================================
    // GESTIONE PRENOTAZIONI ATTIVE
    // ==========================================

    /**
     * Ottiene tutte le prenotazioni attive per un teatro operativo
     */
    public function getPrenotazioniAttive(Request $request)
    {
        try {
            $teatroId = $request->get('teatro_id');
            
            if (!$teatroId) {
                return $this->errorResponse('Teatro Operativo richiesto', 400);
            }

            // Ottieni prenotazioni attive raggruppate per cattedra
            $prenotazioni = PrenotazioneApprontamento::where('teatro_operativo_id', $teatroId)
                ->where('stato', 'prenotato')
                ->with(['militare.grado', 'creatore'])
                ->orderBy('cattedra')
                ->orderBy('data_prenotazione')
                ->get();

            // Raggruppa per cattedra
            $perCattedra = [];
            $labels = ScadenzaApprontamento::getLabels();
            
            foreach ($prenotazioni as $p) {
                $cattedra = $p->cattedra;
                if (!isset($perCattedra[$cattedra])) {
                    $perCattedra[$cattedra] = [
                        'cattedra' => $cattedra,
                        'label' => $labels[$cattedra] ?? $cattedra,
                        'militari' => [],
                        'count' => 0
                    ];
                }
                
                $perCattedra[$cattedra]['militari'][] = [
                    'id' => $p->id,
                    'militare_id' => $p->militare_id,
                    'grado' => $p->militare->grado->sigla ?? '-',
                    'grado_ordine' => $p->militare->grado->ordine ?? 0,
                    'cognome' => $p->militare->cognome,
                    'nome' => $p->militare->nome,
                    'data_prenotazione' => $p->data_prenotazione ? Carbon::parse($p->data_prenotazione)->format('d/m/Y') : '-',
                    'data_prenotazione_raw' => $p->data_prenotazione,
                    'creato_da' => $p->creatore ? $p->creatore->name : '-',
                    'creato_il' => $p->created_at ? $p->created_at->format('d/m/Y H:i') : '-'
                ];
                $perCattedra[$cattedra]['count']++;
            }

            // Ordina militari per grado e anzianità all'interno di ogni cattedra
            foreach ($perCattedra as &$gruppo) {
                usort($gruppo['militari'], function($a, $b) {
                    if ($a['grado_ordine'] !== $b['grado_ordine']) {
                        return $b['grado_ordine'] <=> $a['grado_ordine'];
                    }
                    return $a['cognome'] <=> $b['cognome'];
                });
            }

            return $this->successResponse('Prenotazioni caricate', [
                'prenotazioni' => array_values($perCattedra),
                'totale' => $prenotazioni->count(),
                'totale_cattedre' => count($perCattedra)
            ]);

        } catch (\Exception $e) {
            Log::error('Errore getPrenotazioniAttive', ['error' => $e->getMessage()]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Conferma più prenotazioni in blocco
     */
    public function confermaPrenotazioniMultiple(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'prenotazione_ids' => 'required|array|min:1',
                'prenotazione_ids.*' => 'exists:prenotazioni_approntamenti,id',
                'data_effettiva' => 'required'
            ]);

            $valoreDaSalvare = $this->parseValore($validated['data_effettiva']);
            
            if ($valoreDaSalvare === false) {
                return $this->errorResponse(self::ERROR_MESSAGES['invalid_date_format'], 400);
            }

            DB::beginTransaction();

            $confermate = 0;
            $errori = [];

            foreach ($validated['prenotazione_ids'] as $prenotazioneId) {
                $prenotazione = PrenotazioneApprontamento::find($prenotazioneId);
                
                if (!$prenotazione || $prenotazione->stato !== 'prenotato') {
                    continue;
                }

                $militare = Militare::find($prenotazione->militare_id);
                if (!$militare) {
                    $errori[] = "Militare non trovato per prenotazione {$prenotazioneId}";
                    continue;
                }

                $cattedra = $prenotazione->cattedra;

                // Aggiorna la scadenza approntamento
                $scadenza = ScadenzaApprontamento::firstOrCreate(
                    ['militare_id' => $militare->id],
                    ['teatro_operativo' => null]
                );

                // Se è una colonna condivisa, aggiorna scadenze_militari
                if (ScadenzaApprontamento::isColonnaCondivisa($cattedra)) {
                    $campoSorgente = ScadenzaApprontamento::getCampoSorgente($cattedra);
                    $scadenzaMilitare = ScadenzaMilitare::firstOrCreate(['militare_id' => $militare->id]);
                    $scadenzaMilitare->$campoSorgente = $valoreDaSalvare;
                    $scadenzaMilitare->save();
                } else {
                    $scadenza->$cattedra = $valoreDaSalvare;
                    $scadenza->save();
                }

                // Aggiorna lo stato della prenotazione
                $prenotazione->stato = 'confermato';
                $prenotazione->data_conferma = now();
                $prenotazione->confirmed_by = auth()->id();
                $prenotazione->save();

                $confermate++;
            }

            DB::commit();

            $message = "{$confermate} prenotazioni confermate";
            if (count($errori) > 0) {
                $message .= " (" . count($errori) . " errori)";
            }

            return $this->successResponse($message, [
                'confermate' => $confermate,
                'errori' => $errori
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore confermaPrenotazioniMultiple', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Annulla una prenotazione
     * Rimuove automaticamente dal CPT e dalla Board tramite il Service
     */
    public function annullaPrenotazione(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'prenotazione_id' => 'required|exists:prenotazioni_approntamenti,id'
            ]);

            $prenotazione = PrenotazioneApprontamento::find($validated['prenotazione_id']);
            
            if (!$prenotazione) {
                return $this->errorResponse('Prenotazione non trovata', 404);
            }

            if ($prenotazione->stato !== 'prenotato') {
                return $this->errorResponse('Questa prenotazione non può essere annullata', 400);
            }

            // Usa il service per eliminare (gestisce anche CPT e Board)
            $result = $this->prenotazioneService->eliminaPrenotazione($prenotazione);

            if ($result['success']) {
                return $this->successResponse($result['message']);
            } else {
                return $this->errorResponse($result['message'], 500);
            }

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore annullaPrenotazione', ['error' => $e->getMessage()]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Rimuove una prenotazione dal CPT
     */
    private function rimuoviPrenotazioneDaCPT(PrenotazioneApprontamento $prenotazione)
    {
        try {
            $data = Carbon::parse($prenotazione->data_prenotazione);
            $labels = ScadenzaApprontamento::getLabels();
            $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;

            $pianificazioneMensile = PianificazioneMensile::where('mese', $data->month)
                ->where('anno', $data->year)
                ->first();

            if ($pianificazioneMensile) {
                PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                    ->where('militare_id', $prenotazione->militare_id)
                    ->where('giorno', $data->day)
                    ->where('note', 'like', "%{$cattedraLabel}%")
                    ->delete();

                Log::info('Prenotazione rimossa dal CPT', [
                    'militare_id' => $prenotazione->militare_id,
                    'data' => $data->format('Y-m-d'),
                    'cattedra' => $cattedraLabel
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Errore rimozione prenotazione da CPT', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Rimuove un militare da un'attività Board (se esiste)
     */
    private function rimuoviMilitareDaBoard(PrenotazioneApprontamento $prenotazione)
    {
        try {
            $data = Carbon::parse($prenotazione->data_prenotazione);
            $labels = ScadenzaApprontamento::getLabels();
            $cattedraLabel = $labels[$prenotazione->cattedra] ?? $prenotazione->cattedra;
            $titolo = "{$cattedraLabel} - Approntamento";

            $attivita = BoardActivity::where('title', $titolo)
                ->where('start_date', $data->format('Y-m-d'))
                ->first();

            if ($attivita) {
                // Rimuovi solo il militare dall'attività
                $attivita->militari()->detach($prenotazione->militare_id);
                
                // Se non ci sono più militari, elimina l'attività
                if ($attivita->militari()->count() === 0) {
                    $attivita->delete();
                    Log::info('Attività Board eliminata (nessun militare rimasto)', [
                        'activity_id' => $attivita->id
                    ]);
                } else {
                    Log::info('Militare rimosso da attività Board', [
                        'activity_id' => $attivita->id,
                        'militare_id' => $prenotazione->militare_id
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Errore rimozione militare da Board', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Conferma una singola prenotazione (cattedra svolta)
     */
    public function confermaPrenotazioneSingola(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'prenotazione_id' => 'required|exists:prenotazioni_approntamenti,id',
                'data_effettiva' => 'required'
            ]);

            $prenotazione = PrenotazioneApprontamento::find($validated['prenotazione_id']);
            
            if (!$prenotazione) {
                return $this->errorResponse('Prenotazione non trovata', 404);
            }

            if ($prenotazione->stato !== 'prenotato') {
                return $this->errorResponse('Questa prenotazione non può essere confermata', 400);
            }

            $valoreDaSalvare = $this->parseValore($validated['data_effettiva']);
            
            if ($valoreDaSalvare === false) {
                return $this->errorResponse(self::ERROR_MESSAGES['invalid_date_format'], 400);
            }

            $militare = Militare::find($prenotazione->militare_id);
            if (!$militare) {
                return $this->errorResponse('Militare non trovato', 404);
            }

            $cattedra = $prenotazione->cattedra;

            DB::beginTransaction();

            // Aggiorna la scadenza approntamento
            $scadenza = ScadenzaApprontamento::firstOrCreate(
                ['militare_id' => $militare->id],
                ['teatro_operativo' => null]
            );

            // Se è una colonna condivisa, aggiorna scadenze_militari
            if (ScadenzaApprontamento::isColonnaCondivisa($cattedra)) {
                $campoSorgente = ScadenzaApprontamento::getCampoSorgente($cattedra);
                $scadenzaMilitare = ScadenzaMilitare::firstOrCreate(['militare_id' => $militare->id]);
                $scadenzaMilitare->$campoSorgente = $valoreDaSalvare;
                $scadenzaMilitare->save();
            } else {
                $scadenza->$cattedra = $valoreDaSalvare;
                $scadenza->save();
            }

            // Aggiorna lo stato della prenotazione
            $prenotazione->stato = 'confermato';
            $prenotazione->data_conferma = now();
            $prenotazione->confirmed_by = auth()->id();
            $prenotazione->save();

            DB::commit();

            return $this->successResponse('Prenotazione confermata con successo');

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore confermaPrenotazioneSingola', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Verifica la disponibilità dei militari per una data specifica
     */
    public function verificaDisponibilita(Request $request)
    {
        try {
            $validated = $request->validate([
                'prenotazione_ids' => 'required|array|min:1',
                'prenotazione_ids.*' => 'exists:prenotazioni_approntamenti,id',
                'data' => 'required|date'
            ]);

            $data = Carbon::parse($validated['data']);
            $conflitti = [];

            foreach ($validated['prenotazione_ids'] as $prenotazioneId) {
                $prenotazione = PrenotazioneApprontamento::with('militare.grado')->find($prenotazioneId);
                
                if (!$prenotazione || !$prenotazione->militare) {
                    continue;
                }

                $militare = $prenotazione->militare;
                $disponibilita = $this->getDisponibilitaMilitare($militare, $data);

                if (!$disponibilita['disponibile']) {
                    $conflitti[] = [
                        'militare_id' => $militare->id,
                        'grado' => $militare->grado->sigla ?? '',
                        'cognome' => $militare->cognome,
                        'nome' => $militare->nome,
                        'motivo' => $disponibilita['motivo'],
                        'codice' => $disponibilita['codice']
                    ];
                }
            }

            return $this->successResponse('Verifica completata', [
                'conflitti' => $conflitti,
                'has_conflitti' => count($conflitti) > 0
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore verificaDisponibilita', ['error' => $e->getMessage()]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Modifica la data di più prenotazioni (per tutta la cattedra)
     * Propaga le modifiche anche a CPT e Board tramite il Service
     */
    public function modificaPrenotazioneMultipla(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'prenotazione_ids' => 'required|array|min:1',
                'prenotazione_ids.*' => 'exists:prenotazioni_approntamenti,id',
                'data_prenotazione' => 'required|date'
            ]);

            $nuovaData = Carbon::parse($validated['data_prenotazione']);
            $modificate = 0;
            $errori = [];

            foreach ($validated['prenotazione_ids'] as $prenotazioneId) {
                $prenotazione = PrenotazioneApprontamento::find($prenotazioneId);
                
                if ($prenotazione && $prenotazione->stato === 'prenotato') {
                    // Usa il service per modificare (propaga a CPT e Board)
                    $result = $this->prenotazioneService->modificaPrenotazione($prenotazione, [
                        'data_prenotazione' => $nuovaData->format('Y-m-d')
                    ]);
                    
                    if ($result['success']) {
                        $modificate++;
                    } else {
                        $errori[] = $result['message'];
                    }
                }
            }

            $message = "{$modificate} prenotazioni aggiornate con successo";
            if (!empty($errori)) {
                $message .= " (" . count($errori) . " errori)";
            }

            return $this->successResponse($message);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore modificaPrenotazioneMultipla', ['error' => $e->getMessage()]);
            return $this->errorResponse(self::ERROR_MESSAGES['generic_error'], 500);
        }
    }

    /**
     * Conferma più prenotazioni in blocco (per tutta la cattedra)
     * Se data_effettiva non è specificata, usa la data di prenotazione
     * Usa il Service per aggiornare le scadenze
     */
    public function confermaPrenotazioneMultipla(Request $request)
    {
        if (!$this->canEdit()) {
            return $this->errorResponse(self::ERROR_MESSAGES['unauthorized'], 403);
        }

        try {
            $validated = $request->validate([
                'prenotazione_ids' => 'required|array|min:1',
                'prenotazione_ids.*' => 'exists:prenotazioni_approntamenti,id',
                'data_effettiva' => 'nullable|string'
            ]);

            $confermate = 0;
            $errori = [];

            foreach ($validated['prenotazione_ids'] as $prenotazioneId) {
                $prenotazione = PrenotazioneApprontamento::find($prenotazioneId);
                
                if (!$prenotazione || $prenotazione->stato !== 'prenotato') {
                    continue;
                }

                // Usa data_effettiva se fornita, altrimenti usa la data di prenotazione
                $dataEffettiva = !empty($validated['data_effettiva']) 
                    ? $validated['data_effettiva'] 
                    : ($prenotazione->data_prenotazione ? Carbon::parse($prenotazione->data_prenotazione)->format('d/m/Y') : null);

                // Usa il service per confermare
                $result = $this->prenotazioneService->confermaPrenotazione(
                    $prenotazione, 
                    $dataEffettiva,
                    auth()->id()
                );

                if ($result['success']) {
                    $confermate++;
                } else {
                    $errori[] = $result['message'];
                }
            }

            $message = "{$confermate} prenotazioni confermate";
            if (count($errori) > 0) {
                $message .= " (" . count($errori) . " errori)";
            }

            return $this->successResponse($message, [
                'confermate' => $confermate,
                'errori' => $errori
            ]);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->errors()[array_key_first($e->errors())][0], 422);
        } catch (\Exception $e) {
            Log::error('Errore confermaPrenotazioneMultipla', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse(self::ERROR_MESSAGES['save_error'], 500);
        }
    }

    /**
     * Esporta le prenotazioni attive in Excel
     */
    public function exportPrenotazioniExcel(Request $request)
    {
        $teatroId = $request->get('teatro_id');
        $cattedra = $request->get('cattedra'); // opzionale, per filtrare una cattedra
        
        if (!$teatroId) {
            abort(400, 'Teatro Operativo richiesto');
        }

        $teatro = TeatroOperativo::find($teatroId);
        if (!$teatro) {
            abort(404, 'Teatro non trovato');
        }

        $query = PrenotazioneApprontamento::where('teatro_operativo_id', $teatroId)
            ->where('stato', 'prenotato')
            ->with(['militare.grado']);

        if ($cattedra) {
            $query->where('cattedra', $cattedra);
        }

        $prenotazioni = $query->get();

        // Ordina per grado e anzianità
        $prenotazioni = $prenotazioni->sortBy([
            fn($a, $b) => ($b->militare->grado->ordine ?? 0) <=> ($a->militare->grado->ordine ?? 0),
            fn($a, $b) => ($a->militare->anzianita ?? '9999-12-31') <=> ($b->militare->anzianita ?? '9999-12-31')
        ])->values();

        $labels = ScadenzaApprontamento::getLabels();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prenotazioni Attive');

        // Stili
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
        ];
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A2342']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]
        ];

        // Titolo
        $sheet->setCellValue('A1', 'PRENOTAZIONI ATTIVE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        // Info
        $sheet->setCellValue('A3', 'Teatro Operativo:');
        $sheet->setCellValue('B3', $teatro->nome);
        $sheet->getStyle('A3')->getFont()->setBold(true);
        
        if ($cattedra) {
            $sheet->setCellValue('A4', 'Cattedra:');
            $sheet->setCellValue('B4', $labels[$cattedra] ?? $cattedra);
            $sheet->getStyle('A4')->getFont()->setBold(true);
        }
        
        $sheet->setCellValue('A5', 'Totale Prenotazioni:');
        $sheet->setCellValue('B5', $prenotazioni->count());
        $sheet->getStyle('A5')->getFont()->setBold(true);

        // Header tabella
        $headers = ['N.', 'Grado', 'Cognome', 'Nome', 'Cattedra', 'Data Prenotazione'];
        $row = 7;
        
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $sheet->getStyle($col . $row)->applyFromArray($headerStyle);
            $col++;
        }
        $sheet->getRowDimension($row)->setRowHeight(25);

        // Dati
        $dataRow = $row + 1;
        $numero = 1;
        foreach ($prenotazioni as $p) {
            $col = 'A';
            $sheet->setCellValue($col++ . $dataRow, $numero);
            $sheet->setCellValue($col++ . $dataRow, $p->militare->grado->sigla ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $p->militare->cognome ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $p->militare->nome ?? '-');
            $sheet->setCellValue($col++ . $dataRow, $labels[$p->cattedra] ?? $p->cattedra);
            $sheet->setCellValue($col++ . $dataRow, $p->data_prenotazione ? Carbon::parse($p->data_prenotazione)->format('d/m/Y') : '-');
            
            // Stile riga alternata
            $bgColor = ($numero % 2 == 0) ? 'F8F9FA' : 'FFFFFF';
            $sheet->getStyle("A{$dataRow}:F{$dataRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DEE2E6']]]
            ]);
            
            $dataRow++;
            $numero++;
        }

        // Auto-size colonne
        foreach (range('A', 'F') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        // Timestamp
        $sheet->setCellValue('A' . ($dataRow + 1), 'Generato il ' . date('d/m/Y H:i'));
        $sheet->getStyle('A' . ($dataRow + 1))->getFont()->setItalic(true)->setSize(9);

        // Output
        $writer = new Xlsx($spreadsheet);
        $filename = 'Prenotazioni_' . preg_replace('/[^a-zA-Z0-9]/', '_', $teatro->nome) . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    /**
     * Verifica se un militare ha il prerequisito per C-IED pratico
     * (deve avere AWARENESS C-IED fatto o prenotato con data precedente)
     */
    private function haRequisitoAwarenessCied(Militare $militare, Carbon $dataPrenotazione, int $teatroId): array
    {
        // 1. Verifica se ha già AWARENESS C-IED valido
        $datiAwareness = self::getValoreCampo($militare, 'awareness_cied');
        if (in_array($datiAwareness['stato'], ['valido', 'in_scadenza'])) {
            return ['valido' => true, 'motivo' => null];
        }

        // 2. Verifica se ha una prenotazione AWARENESS C-IED con data precedente
        $prenotazioneAwareness = PrenotazioneApprontamento::where('militare_id', $militare->id)
            ->where('teatro_operativo_id', $teatroId)
            ->where('cattedra', 'awareness_cied')
            ->whereIn('stato', ['prenotato', 'confermato'])
            ->where('data_prenotazione', '<', $dataPrenotazione)
            ->first();

        if ($prenotazioneAwareness) {
            return ['valido' => true, 'motivo' => null];
        }

        return [
            'valido' => false,
            'motivo' => 'Requisito: AWARENESS C-IED non effettuato o non prenotato prima di questa data'
        ];
    }
}
