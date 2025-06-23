<?php

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * 
 * Questo file fa parte del sistema C2MS per la gestione militare digitale.
 * 
 * @package    C2MS
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
use Illuminate\Support\Facades\Log;

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
            // Carica le relazioni necessarie (solo quelle che esistono)
            $militare->load([
                'grado', 
                'plotone', 
                'polo', 
                'mansione', 
                'ruolo',
                'certificatiLavoratori',
                'idoneita',
                'valutazioni'
                // 'assenze', // Tabella non esiste
                // 'eventi'   // Controllare se esiste
            ]);
            
            // Ottieni la valutazione del militare (se esiste)
            $valutazioneUtente = $militare->valutazioni->first();
            
            return view('militare.show', compact('militare', 'valutazioneUtente'));
            
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento del militare', [
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('militare.index')
                ->withErrors(['error' => 'Errore nel caricamento del militare: ' . $e->getMessage()]);
        }
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
            $this->militareService->createMilitare($request->all());
            
            return redirect()->route('militare.index')
                ->with('success', 'Militare creato con successo!');
                
        } catch (\Exception $e) {
            Log::error('Errore nella creazione del militare', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Errore durante la creazione: ' . $e->getMessage()]);
        }
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
            if ($request->ajax() || $request->wantsJson()) {
                $result = $this->militareService->updateMilitareAjax($id, $request->all());
                return response()->json($result);
            }
            
            $this->militareService->updateMilitare($id, $request->all());
            
            return redirect()->route('militare.show', $id)
                ->with('success', 'Militare aggiornato con successo!');
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'aggiornamento del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina un militare dal database
     * 
     * @param int $id ID del militare da eliminare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy($id)
    {
        try {
            $this->militareService->deleteMilitare($id);
            
            return redirect()->route('militare.index')
                ->with('success', 'Militare eliminato con successo.');
                
        } catch (\Exception $e) {
            Log::error('Errore nell\'eliminazione del militare', [
                'militare_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('militare.index')
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
            $this->militareService->updateNotes($militare, $request->get('note'));
            
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
            $this->militareService->saveValutazione($militare, $request->all());
            
            return redirect()->route('militare.show', $militare->id)
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
}
