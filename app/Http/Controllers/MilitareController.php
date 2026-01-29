<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Controllers
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Militare;
use App\Models\Grado;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Ruolo;
use App\Models\Mansione;
use App\Models\RuoloCertificati;
use App\Services\MilitareService;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\ExcelStyleService;

/**
 * Controller per la gestione dei militari
 * 
 * Questo controller gestisce tutte le operazioni CRUD sui militari,
 * delegando la logica business al MilitareService per mantenere
 * il controller snello e focalizzato sulla gestione delle richieste HTTP.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class MilitareController extends Controller
{
    /**
     * Servizio per la gestione dei militari
     * 
     * @var MilitareService
     */
    protected $militareService;

    /**
     * Costruttore del controller
     * 
     * @param MilitareService $militareService Servizio per la gestione dei militari
     */
    public function __construct(MilitareService $militareService)
    {
        $this->militareService = $militareService;
    }

    /**
     * Mostra l'elenco dei militari con filtri e paginazione
     * 
     * @param Request $request Richiesta HTTP con eventuali filtri
     * @return \Illuminate\View\View Vista con l'elenco dei militari
     */
    public function index(Request $request)
    {
        try {
            // Delega al service la logica di ricerca e filtri
            $result = $this->militareService->getFilteredMilitari($request);
            
            return view('militare.index', $result);
            
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento dell\'elenco militari', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);
            
            return redirect()->route('dashboard')
                ->withErrors(['error' => 'Errore nel caricamento dell\'elenco militari']);
        }
    }

    /**
     * Mostra i dettagli di un militare specifico
     * 
     * @param Militare $militare Modello del militare da visualizzare
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse Vista dettagli o redirect in caso di errore
     */
    public function show(Militare $militare)
    {
        try {
            // Carica tutte le relazioni necessarie per la pagina dettaglio completa
            $militare->load([
                'grado', 
                'plotone', 
                'polo', 
                'mansione', 
                'ruolo',
                'compagnia',
                'scadenza',
                'teatriOperativi',
                'activities.column',
                'patenti',
            ]);
            
            // Ottieni la valutazione del militare (se esiste e la tabella esiste)
            $valutazioneUtente = null;
            if (\App\Models\MilitareValutazione::tableExists()) {
                $valutazioneUtente = \App\Models\MilitareValutazione::where('militare_id', $militare->id)->first();
            }
            
            // Prepara i dati del calendario per il mese corrente
            $calendarioData = $this->preparaCalendarioMilitare($militare);
            
            return view('militare.show', compact('militare', 'valutazioneUtente', 'calendarioData'));
            
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento del militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('anagrafica.index')
                ->withErrors(['error' => 'Errore nel caricamento del militare: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Prepara i dati del calendario per il mese corrente di un militare
     * 
     * @param Militare $militare
     * @return array
     */
    private function preparaCalendarioMilitare(Militare $militare): array
    {
        $anno = now()->year;
        $mese = now()->month;
        $giorniNelMese = now()->daysInMonth;
        
        // Ottieni la pianificazione mensile
        $pianificazioneMensile = \App\Models\PianificazioneMensile::where('mese', $mese)
            ->where('anno', $anno)
            ->first();
        
        // Ottieni le pianificazioni del militare per questo mese
        $pianificazioni = [];
        if ($pianificazioneMensile) {
            $pianificazioniQuery = \App\Models\PianificazioneGiornaliera::where('militare_id', $militare->id)
                ->where('pianificazione_mensile_id', $pianificazioneMensile->id)
                ->with(['tipoServizio', 'tipoServizio.codiceGerarchia'])
                ->get();
            
            foreach ($pianificazioniQuery as $p) {
                if (!isset($pianificazioni[$p->giorno])) {
                    $pianificazioni[$p->giorno] = [];
                }
                $pianificazioni[$p->giorno][] = $p;
            }
        }
        
        // Mappa colori per tipologie
        $coloriAttivita = [
            'T.O.' => '#dc3545', 'EXE' => '#ffc107', 'EX' => '#ffc107',
            'CAT' => '#28a745', 'CATT' => '#28a745', 'CRS' => '#007bff',
            'CORSO' => '#007bff', 'SI' => '#6c757d', 'STB' => '#fd7e14',
            'OP' => '#dc3545', 'LIC' => '#17a2b8', 'MAL' => '#e83e8c', 'RIP' => '#6f42c1',
        ];
        
        // Prepara calendario
        $calendarioMese = [];
        $giorniItaliani = ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'];
        
        for ($giorno = 1; $giorno <= $giorniNelMese; $giorno++) {
            $data = \Carbon\Carbon::createFromDate($anno, $mese, $giorno);
            
            $impegniGiorno = [];
            if (isset($pianificazioni[$giorno])) {
                foreach ($pianificazioni[$giorno] as $p) {
                    if ($p->tipoServizio) {
                        $codice = $p->tipoServizio->codice;
                        $colore = $coloriAttivita[strtoupper($codice)] 
                            ?? $p->tipoServizio->codiceGerarchia->colore_badge 
                            ?? '#6c757d';
                        
                        $impegniGiorno[] = [
                            'codice' => $codice,
                            'descrizione' => $p->tipoServizio->nome,
                            'colore' => $colore,
                        ];
                    }
                }
            }
            
            $calendarioMese[$giorno] = [
                'giorno' => $giorno,
                'data' => $data,
                'nome_giorno' => $giorniItaliani[$data->dayOfWeek],
                'is_weekend' => $data->isWeekend(),
                'is_today' => $data->isToday(),
                'impegni' => $impegniGiorno,
                'ha_impegno' => count($impegniGiorno) > 0,
            ];
        }
        
        // Statistiche
        $totaleImpegni = collect($calendarioMese)->where('ha_impegno', true)->count();
        
        return [
            'anno' => $anno,
            'mese' => $mese,
            'nomeMese' => $this->getNomeMese($mese),
            'calendarioMese' => $calendarioMese,
            'primoGiornoSettimana' => \Carbon\Carbon::create($anno, $mese, 1)->dayOfWeek == 0 ? 6 : \Carbon\Carbon::create($anno, $mese, 1)->dayOfWeek - 1,
            'statistiche' => [
                'totale_impegni' => $totaleImpegni,
                'giorni_liberi' => $giorniNelMese - $totaleImpegni,
                'percentuale_impegno' => $giorniNelMese > 0 ? round(($totaleImpegni / $giorniNelMese) * 100) : 0,
            ],
        ];
    }
    
    /**
     * Ottiene il nome del mese in italiano
     */
    private function getNomeMese(int $mese): string
    {
        $nomiMesi = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
        ];
        return $nomiMesi[$mese] ?? '';
    }

    /**
     * Mostra il form per creare un nuovo militare
     * 
     * @return \Illuminate\View\View Vista del form di creazione
     */
    public function create()
    {
        $data = $this->militareService->getFormData();
        
        return view('militare.form', $data);
    }

    /**
     * Memorizza un nuovo militare nel database
     * 
     * @param Request $request Richiesta HTTP con i dati del militare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function store(Request $request)
    {
        try {
            $militare = $this->militareService->createMilitare($request->all());
            
            // Registra la creazione nel log di audit
            AuditService::logCreate($militare, "Creato militare: {$militare->cognome} {$militare->nome}");
            
            return redirect()->route('anagrafica.index')
                ->with('success', 'Militare creato con successo!');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Errori di validazione - mostra messaggi user-friendly
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Illuminate\Database\QueryException $e) {
            // Errori database - traduci in messaggi comprensibili
            Log::error('Errore database nella creazione del militare', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            
            $message = $this->translateDatabaseError($e);
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $message]);
                
        } catch (\Exception $e) {
            Log::error('Errore nella creazione del militare', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Si è verificato un errore durante la creazione del militare. Riprova.']);
        }
    }
    
    /**
     * Traduce gli errori del database in messaggi user-friendly
     */
    private function translateDatabaseError(\Illuminate\Database\QueryException $e): string
    {
        $message = $e->getMessage();
        
        // Codice fiscale nullo o duplicato
        if (str_contains($message, 'codice_fiscale') && str_contains($message, 'cannot be null')) {
            return 'Il codice fiscale è obbligatorio. Compila tutti i campi anagrafici per calcolarlo automaticamente.';
        }
        
        if (str_contains($message, 'codice_fiscale') && str_contains($message, 'Duplicate entry')) {
            return 'Questo codice fiscale è già presente nel sistema. Verifica i dati inseriti.';
        }
        
        // Vincoli di chiave esterna
        if (str_contains($message, 'foreign key constraint')) {
            return 'Uno dei valori selezionati non è valido. Verifica i campi del form.';
        }
        
        // Errore generico
        return 'Si è verificato un errore durante il salvataggio. Verifica i dati e riprova.';
    }

    /**
     * Mostra il form per modificare un militare esistente
     * 
     * @param Militare $militare Modello del militare da modificare
     * @return \Illuminate\View\View Vista del form di modifica
     */
    public function edit(Militare $militare)
    {
        $data = $this->militareService->getFormData($militare);
        
        return view('militare.form', $data);
    }

    /**
     * Aggiorna un militare esistente
     * 
     * @param Request $request Richiesta HTTP con i dati aggiornati
     * @param int $id ID del militare da aggiornare
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse Risposta JSON per AJAX o redirect per form
     */
    public function update(Request $request, $id)
    {
        try {
            // SICUREZZA: Usa query scoped - il Global Scope filtra già per compagnia
            // Il militare sarà trovato solo se:
            // 1. Appartiene alla compagnia dell'utente (owner)
            // 2. È acquisito tramite attività della compagnia dell'utente
            $militare = Militare::findOrFail($id);
            
            // VERIFICA PERMESSI via Policy (single source of truth)
            // La policy verifica che sia owner E abbia permesso anagrafica.edit
            $this->authorize('update', $militare);
            
            // Salva i valori originali per l'audit
            $oldValues = $militare->getOriginal();
            
            if ($request->ajax() || $request->wantsJson()) {
                $result = $this->militareService->updateMilitareAjax($id, $request->all());
                
                // Registra la modifica nel log di audit
                $militare->refresh();
                AuditService::logUpdate($militare, $oldValues, $militare->toArray());
                
                return response()->json($result);
            }
            
            $this->militareService->updateMilitare($id, $request->all());
            
            // Registra la modifica nel log di audit
            $militare->refresh();
            AuditService::logUpdate($militare, $oldValues, $militare->toArray());
            
            return redirect()->route('anagrafica.show', $id)
                ->with('success', 'Militare aggiornato con successo!');
                
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Gestione centralizzata del 403 da Policy
            Log::warning('Tentativo di modifica militare non autorizzato (policy denied)', [
                'user_id' => auth()->id(),
                'militare_id' => $id,
            ]);
            
            $message = 'Non hai i permessi per modificare questo militare. I militari acquisiti sono in sola lettura.';
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 403);
            }
            
            return redirect()->back()->with('error', $message);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Il militare non esiste o non è visibile per questo utente (Global Scope)
            Log::warning('Militare non trovato o non accessibile', [
                'user_id' => auth()->id(),
                'militare_id' => $id,
            ]);
            
            $message = 'Militare non trovato o non hai i permessi per accedervi.';
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 404);
            }
            
            return redirect()->route('anagrafica.index')->with('error', $message);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', collect($e->errors())->flatten()->all())
                ], 422);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors($e->errors());
                
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Errore database nell\'aggiornamento del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $message = $this->translateDatabaseError($e);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $message]);
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'aggiornamento.'
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.']);
        }
    }

    /**
     * Elimina un militare dal database
     * 
     * @param Militare $militare Il militare da eliminare (model binding)
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy(Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('delete', $militare);
            
            Log::info('Tentativo di eliminazione militare', [
                'militare_id' => $militare->id,
                'militare_nome' => $militare->cognome . ' ' . $militare->nome
            ]);
            
            // Registra l'eliminazione PRIMA di eliminare (per avere i dati)
            $militareNome = "{$militare->cognome} {$militare->nome}";
            AuditService::logDelete($militare, "Eliminato militare: {$militareNome}");
            
            $result = $this->militareService->deleteMilitare($militare);
            
            if (!$result) {
                Log::error('Eliminazione militare fallita - servizio ha ritornato false', [
                    'militare_id' => $militare->id
                ]);
                
                return redirect()->route('anagrafica.index')
                    ->withErrors(['error' => 'Impossibile eliminare il militare. Controlla i log per i dettagli.']);
            }
            
            Log::info('Militare eliminato con successo', ['militare_id' => $militare->id]);
            
            return redirect()->route('anagrafica.index')
                ->with('success', 'Militare eliminato con successo.');
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione del militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('anagrafica.index')
                ->withErrors(['error' => 'Errore durante l\'eliminazione: ' . $e->getMessage()]);
        }
    }

    /**
     * Ricerca militari tramite AJAX
     * 
     * @param Request $request Richiesta HTTP con i parametri di ricerca
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i risultati della ricerca
     */
    public function search(Request $request)
    {
        try {
            $results = $this->militareService->searchMilitari($request->get('query', ''));
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nella ricerca militari', [
                'query' => $request->get('query'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nella ricerca: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna le note di un militare
     * 
     * @param Request $request Richiesta HTTP con le nuove note
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function updateNotes(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $oldNotes = $militare->note;
            $this->militareService->updateNotes($militare, $request->get('note'));
            
            // Registra la modifica nel log di audit
            AuditService::logUpdate(
                $militare,
                ['note' => $oldNotes],
                ['note' => $request->get('note')],
                "Aggiornate note del militare {$militare->cognome} {$militare->nome}"
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Note aggiornate con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento delle note', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memorizza una nuova valutazione per un militare
     * 
     * @param Request $request Richiesta HTTP con i dati della valutazione
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function storeValutazione(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $this->militareService->saveValutazione($militare, $request->all());
            
            return redirect()->route('anagrafica.show', $militare->id)
                ->with('success', 'Valutazione salvata con successo!');
                
        } catch (\Exception $e) {
            Log::error('Errore nel salvataggio della valutazione', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore nel salvataggio: ' . $e->getMessage()]);
        }
    }

    /**
     * Aggiorna un singolo campo della valutazione tramite AJAX
     * 
     * @param Request $request Richiesta HTTP con il campo e valore
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function updateValutazioneField(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $result = $this->militareService->updateValutazioneField(
                $militare,
                $request->get('field'),
                $request->get('value')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del campo valutazione', [
                'militare_id' => $militare->id,
                'field' => $request->get('field'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene la foto del militare
     * 
     * @param int $id ID del militare
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse Immagine o risposta di errore
     */
    public function getFoto($id)
    {
        try {
            return $this->militareService->getFoto($id);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            // Restituisce un'immagine SVG di errore invece di JSON
            $svg = '<svg width="150" height="150" xmlns="http://www.w3.org/2000/svg">
                        <rect width="150" height="150" fill="#dc3545"/>
                        <text x="75" y="75" font-family="Arial" font-size="12" fill="white" text-anchor="middle" dy=".3em">
                            Errore caricamento
                        </text>
                    </svg>';
                    
            return response($svg, 500, [
                'Content-Type' => 'image/svg+xml',
                'Cache-Control' => 'no-cache',
            ]);
        }
    }

    /**
     * Carica una nuova foto per il militare
     * 
     * @param Request $request Richiesta HTTP con il file della foto
     * @param int $id ID del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function uploadFoto(Request $request, $id)
    {
        try {
            // Validazione del file
            $request->validate([
                'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);
            
            // Trova il militare
            $militare = Militare::findOrFail($id);
            
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            // Carica la foto
            $result = $this->militareService->uploadFoto($militare, $request->file('foto'));
            
            return response()->json([
                'success' => true,
                'message' => 'Foto caricata con successo!',
                'data' => $result
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'upload della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina la foto del militare
     * 
     * @param int $id ID del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con il risultato
     */
    public function deleteFoto($id)
    {
        try {
            // Trova il militare
            $militare = Militare::findOrFail($id);
            
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            // Elimina la foto
            $result = $this->militareService->deleteFoto($militare);
            
            return response()->json([
                'success' => true,
                'message' => 'Foto eliminata con successo!'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione della foto', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottiene i dati del militare per API
     * 
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i dati del militare
     */
    public function getApiData(Militare $militare)
    {
        try {
            $data = $this->militareService->getApiData($militare->id);
            
            return response()->json($data);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero dati API militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dati'
            ], 500);
        }
    }

    /**
     * Esporta i militari in Excel con i filtri applicati
     * 
     * @param Request $request Richiesta HTTP
     * @return \Illuminate\Http\Response File Excel
     */
    public function exportExcel(Request $request)
    {
        try {
            Log::info('Export Excel Anagrafica iniziato');
            
            // Ottieni i militari filtrati usando lo stesso servizio dell'index
            $data = $this->militareService->getFilteredMilitari($request);
            $militari = $data['militari'];
            
            // Se sono stati passati IDs specifici, filtra per quelli
            if ($request->filled('ids')) {
                $ids = explode(',', $request->ids);
                $militari = $militari->whereIn('id', $ids);
            }
            
            Log::info('Militari recuperati: ' . $militari->count());
            
            // Usa il servizio per stili Excel
            $excelService = new ExcelStyleService();
            $spreadsheet = $excelService->createSpreadsheet('Anagrafica Militari');
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Anagrafica Militari');
            
            // Titolo principale
            $excelService->applyTitleStyle($sheet, 'A1:O1', 'ANAGRAFICA MILITARI');
            
            // Intestazioni colonne (riga 2)
            $headers = ['Compagnia', 'Grado', 'Cognome', 'Nome', 'Codice Fiscale', 'Plotone', 'Ufficio', 
                        'Incarico', 'Patenti', 'NOS', 'Anzianità', 'Data Nascita', 
                        'Email Istituzionale', 'Cellulare', 'Istituti'];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '2', $header);
                $col++;
            }
            
            $excelService->applyHeaderStyle($sheet, 'A2:O2');
            $sheet->getRowDimension('2')->setRowHeight(30);
            
            // Dati dei militari (dalla riga 3)
            $row = 3;
            foreach ($militari as $militare) {
                // Compagnia
                $compagniaValue = '';
                if ($militare->compagnia_id) {
                    $comp = $militare->compagnia;
                    $compagniaValue = $comp ? ($comp->numero ?? $comp->nome) : '';
                }
                
                $sheet->setCellValue('A' . $row, $compagniaValue);
                $sheet->setCellValue('B' . $row, $militare->grado->sigla ?? '');
                $sheet->setCellValue('C' . $row, strtoupper($militare->cognome));
                $sheet->setCellValue('D' . $row, strtoupper($militare->nome));
                $sheet->setCellValue('E' . $row, $militare->codice_fiscale ?? '-');
                $sheet->setCellValue('F' . $row, $militare->plotone->nome ?? '-');
                $sheet->setCellValue('G' . $row, $militare->polo->nome ?? '-');
                $sheet->setCellValue('H' . $row, $militare->mansione->nome ?? '-');
                
                // Patenti - mostra tutte le patenti separate da spazio
                $patenti = $militare->patenti->pluck('categoria')->toArray();
                $sheet->setCellValue('I' . $row, !empty($patenti) ? implode(' ', $patenti) : '-');
                
                // NOS con colore in base al valore
                $nosValue = $militare->nos_status ? ucfirst($militare->nos_status) : '-';
                $sheet->setCellValue('J' . $row, $nosValue);
                if ($militare->nos_status === 'si') {
                    $excelService->applyBadgeStyle($sheet, 'J' . $row, 'success');
                } elseif ($militare->nos_status === 'no') {
                    $excelService->applyBadgeStyle($sheet, 'J' . $row, 'danger');
                }
                
                $sheet->setCellValue('K' . $row, $militare->anzianita ? (is_object($militare->anzianita) ? $militare->anzianita->format('d/m/Y') : $militare->anzianita) : '-');
                $sheet->setCellValue('L' . $row, $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '-');
                $sheet->setCellValue('M' . $row, $militare->email_istituzionale ?? '-');
                $sheet->setCellValue('N' . $row, $militare->telefono ?? '-');
                
                // Istituti - mostra tutti gli istituti separati da virgola
                $istituti = $militare->istituti ?? [];
                $sheet->setCellValue('O' . $row, !empty($istituti) ? implode(', ', $istituti) : '-');
                
                $row++;
            }
            
            // Applica stili dati
            if ($row > 3) {
                $excelService->applyDataStyle($sheet, 'A3:O' . ($row - 1));
                $excelService->applyAlternateRowColors($sheet, 3, $row - 1, 'A', 'O');
            }
            
            // Imposta larghezza colonne
            $columnWidths = [
                'A' => 14,  // Compagnia
                'B' => 14,  // Grado
                'C' => 20,  // Cognome
                'D' => 18,  // Nome
                'E' => 18,  // Codice Fiscale
                'F' => 20,  // Plotone
                'G' => 28,  // Ufficio
                'H' => 28,  // Incarico
                'I' => 14,  // Patenti
                'J' => 10,  // NOS
                'K' => 14,  // Anzianità
                'L' => 14,  // Data Nascita
                'M' => 35,  // Email
                'N' => 16,  // Cellulare
                'O' => 30   // Istituti
            ];
            
            foreach ($columnWidths as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            
            // Data generazione
            $excelService->addGenerationInfo($sheet, $row + 1);
            
            // Freeze header
            $excelService->freezeHeader($sheet, 2);
            
            // Area di stampa
            $excelService->setPrintArea($sheet, 'O', $row - 1);
            
            // Crea il writer e salva
            $writer = new Xlsx($spreadsheet);
            $filename = 'Anagrafica_Militari_' . now()->format('Y-m-d_His') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), 'anagrafica_');
            $writer->save($tempFile);
            
            // Registra l'esportazione nel log di audit
            AuditService::logExport('anagrafica_militari', $militari->count(), "Esportazione anagrafica militari in Excel");
            
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'export Excel anagrafica', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()->with('error', 'Errore durante l\'esportazione del file Excel.');
        }
    }

    /**
     * Aggiorna un singolo campo di un militare via AJAX
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Modello del militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function updateField(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $field = $request->input('field');
            $value = $request->input('value');
            
            if (is_null($field)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non specificato'
                ], 400);
            }
            
            // Mapping dei campi frontend -> database
            $fieldMapping = [
                'compagnia' => 'compagnia_id',
                'grado' => 'grado_id',
                'plotone' => 'plotone_id',
                'ufficio' => 'polo_id',
                'incarico' => 'mansione_id',
            ];
            
            // Applica il mapping se necessario
            $dbField = $fieldMapping[$field] ?? $field;
            
            // Lista dei campi consentiti per l'aggiornamento (nomi database)
            $allowedFields = [
                'compagnia_id', 'grado_id', 'cognome', 'nome', 'plotone_id', 
                'polo_id', 'mansione_id', 'ruolo_id', 'nos_status', 
                'data_nascita', 'codice_fiscale', 'email', 'telefono', 'note',
                'email_istituzionale', 'anzianita'
            ];
            
            if (!in_array($dbField, $allowedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo non consentito: ' . $dbField
                ], 400);
            }
            
            // Converti il valore in null se è stringa vuota o "0" per i campi nullable
            $nullableFields = ['compagnia_id', 'grado_id', 'plotone_id', 'polo_id', 'mansione_id', 'ruolo_id'];
            if (in_array($dbField, $nullableFields)) {
                if ($value === '' || $value === null || $value === '0' || $value === 0) {
                    $value = null;
                }
            }
            
            // Converti in intero per i campi ID (solo se non è null)
            $idFields = ['compagnia_id', 'grado_id', 'plotone_id', 'polo_id', 'mansione_id', 'ruolo_id'];
            if (in_array($dbField, $idFields) && $value !== null) {
                // Converti in intero, gestendo anche stringhe numeriche
                $intValue = is_numeric($value) ? (int) $value : null;
                
                if ($intValue === null || $intValue <= 0) {
                    // Se il valore non è un numero valido, imposta null
                    $value = null;
                } else {
                    $value = $intValue;
                    
                    // Valida che il valore esista nella tabella di riferimento (solo per foreign keys)
                    // Nota: per compagnia_id, la validazione è opzionale perché la view usa un array hardcoded
                    if ($dbField === 'compagnia_id') {
                        // Verifica se la compagnia esiste, ma non blocca se non esiste (per compatibilità)
                        $compagnia = \App\Models\Compagnia::find($value);
                        if (!$compagnia) {
                            Log::warning('Compagnia non trovata nel database', [
                                'compagnia_id' => $value,
                                'militare_id' => $militare->id
                            ]);
                            // Non bloccare, permettere il salvataggio comunque
                            // Il database gestirà il foreign key constraint se necessario
                        }
                    } elseif ($dbField === 'grado_id') {
                        if (!\App\Models\Grado::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Grado non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'plotone_id') {
                        if (!\App\Models\Plotone::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Plotone non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'polo_id') {
                        if (!\App\Models\Polo::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Polo non valido'
                            ], 400);
                        }
                    } elseif ($dbField === 'mansione_id') {
                        if (!\App\Models\Mansione::find($value)) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Mansione non valida'
                            ], 400);
                        }
                    }
                }
            }
            
            // Se cambia la compagnia, azzera il plotone (perché non appartiene più alla compagnia)
            if ($dbField === 'compagnia_id') {
                $oldCompagniaId = $militare->compagnia_id;
                // Confronta usando == per gestire null correttamente
                if ($value != $oldCompagniaId) {
                    $militare->plotone_id = null;
                }
            }
            
            // Salva il valore originale per l'audit
            $oldValue = $militare->$dbField;
            
            // Aggiorna il campo
            try {
                $militare->$dbField = $value;
                $militare->save();
                
                // Registra la modifica nel log di audit
                AuditService::logUpdate(
                    $militare,
                    [$dbField => $oldValue],
                    [$dbField => $value],
                    "Modificato campo '{$field}' del militare {$militare->cognome} {$militare->nome}"
                );
            } catch (\Illuminate\Database\QueryException $e) {
                // Se c'è un errore di foreign key, prova a gestirlo
                if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                    Log::error('Foreign key constraint violation', [
                        'field' => $dbField,
                        'value' => $value,
                        'militare_id' => $militare->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e; // Rilancia per essere gestito dal catch esterno
                }
                throw $e;
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo',
                'plotone_reset' => ($dbField === 'compagnia_id') // Indica se il plotone è stato resettato
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Errore database nell\'aggiornamento campo militare', [
                'militare_id' => $militare->id,
                'field' => $request->input('field'),
                'value' => $request->input('value'),
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings()
            ]);
            
            $errorMessage = 'Errore durante l\'aggiornamento';
            // Se è un errore di foreign key constraint, fornisci un messaggio più chiaro
            if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
                $errorMessage = 'Il valore selezionato non è valido o non esiste nel database';
            } elseif (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                $errorMessage = 'Violazione di vincolo di integrità: il valore non è valido';
            }
            
            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_detail' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento campo militare', [
                'militare_id' => $militare->id,
                'field' => $request->input('field'),
                'value' => $request->input('value'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage(),
                'error_detail' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Aggiorna il valore di un campo custom per un militare
     */
    public function updateCampoCustom(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $nomeCampo = $request->input('nome_campo');
            $valore = $request->input('valore');
            
            $success = $militare->setValoreCampoCustom($nomeCampo, $valore);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campo custom non trovato'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento campo custom', [
                'militare_id' => $militare->id,
                'nome_campo' => $request->input('nome_campo'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento'
            ], 500);
        }
    }

    /**
     * Ottiene i plotoni filtrati per compagnia
     * 
     * @param Request $request Richiesta HTTP
     * @return \Illuminate\Http\JsonResponse Risposta JSON con i plotoni
     */
    public function getPlotoniPerCompagnia(Request $request)
    {
        try {
            $compagniaId = $request->input('compagnia_id');
            
            if (!$compagniaId) {
                // Se non c'è compagnia, restituisci tutti i plotoni
                $plotoni = Plotone::orderBy('nome')->get();
            } else {
                // Filtra per compagnia
                $plotoni = Plotone::where('compagnia_id', $compagniaId)
                    ->orderBy('nome')
                    ->get();
            }
            
            return response()->json([
                'success' => true,
                'plotoni' => $plotoni
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nel recupero plotoni per compagnia', [
                'compagnia_id' => $request->input('compagnia_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei plotoni'
            ], 500);
        }
    }

    /**
     * Aggiunge una patente a un militare
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Militare (model binding)
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function addPatente(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $patente = $request->input('patente');
            
            // Verifica che la patente sia valida
            if (!in_array($patente, ['2', '3', '4', '5', '6'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patente non valida'
                ], 400);
            }
            
            // Verifica se la patente esiste già
            $exists = $militare->patenti()->where('categoria', $patente)->exists();
            
            if ($exists) {
                return response()->json([
                    'success' => true,
                    'message' => 'Patente già presente'
                ]);
            }
            
            // Crea la patente
            $patenteCreata = $militare->patenti()->create([
                'categoria' => $patente,
                'tipo' => 'MIL',
                'data_ottenimento' => now(),
                'data_scadenza' => now()->addYears(10)
            ]);
            
            // Registra l'aggiunta patente nel log di audit
            AuditService::log(
                'update',
                "Aggiunta patente cat. {$patente} al militare {$militare->cognome} {$militare->nome}",
                $militare,
                ['patente_aggiunta' => $patente]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Patente aggiunta con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiunta patente', [
                'militare_id' => $militare->id,
                'patente' => $request->input('patente'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiunta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rimuove una patente da un militare
     * 
     * @param Request $request Richiesta HTTP
     * @param Militare $militare Militare (model binding)
     * @return \Illuminate\Http\JsonResponse Risposta JSON
     */
    public function removePatente(Request $request, Militare $militare)
    {
        try {
            // VERIFICA PERMESSI via Policy (single source of truth)
            $this->authorize('update', $militare);
            
            $patente = $request->input('patente');
            
            // Elimina la patente
            $deleted = $militare->patenti()->where('categoria', $patente)->delete();
            
            // Registra la rimozione patente nel log di audit
            if ($deleted > 0) {
                AuditService::log(
                    'update',
                    "Rimossa patente cat. {$patente} dal militare {$militare->cognome} {$militare->nome}",
                    $militare,
                    ['patente_rimossa' => $patente]
                );
            }
            
            return response()->json([
                'success' => true,
                'message' => $deleted > 0 ? 'Patente rimossa con successo' : 'Patente non trovata'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore nella rimozione patente', [
                'militare_id' => $militare->id,
                'patente' => $request->input('patente'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione: ' . $e->getMessage()
            ], 500);
        }
    }
}
