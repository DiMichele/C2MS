<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Militare;
use App\Models\CertificatiLavoratori;
use App\Models\Idoneita;
use App\Models\Mansione;
use App\Models\Ruolo;
use App\Services\CertificatiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Controller per la gestione dei certificati
 * 
 * Gestisce le operazioni CRUD per certificati lavoratori e idoneità
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */
class CertificatiController extends Controller
{
    /**
     * Service per la gestione dei certificati
     * 
     * @var CertificatiService
     */
    protected $certificatiService;
    
    /**
     * Costruttore del controller
     * 
     * @param CertificatiService $certificatiService
     */
    public function __construct(CertificatiService $certificatiService)
    {
        $this->certificatiService = $certificatiService;
    }
    
    /**
     * Pagina Corsi Lavoratori
     * 
     * Mostra l'elenco dei militari con i loro certificati lavoratori
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function corsiLavoratori(Request $request)
    {
        // Query di base
        $query = $this->certificatiService->buildMilitariQuery($request);
        
        // Applica filtri specifici per i corsi lavoratori
        $query = $this->certificatiService->applyCorsiLavoratoriFilters($query, $request);
        
        // Esegui la query con paginazione
        $militari = $query->paginate(25);
        
        // Recupera ruoli per i filtri
        $ruoli = Ruolo::orderBy('nome')->get();
        
        return view('certificati.corsi_lavoratori', compact('militari', 'ruoli'));
    }

    /**
     * Pagina Idoneità
     * 
     * Mostra l'elenco dei militari con le loro idoneità
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function idoneita(Request $request)
    {
        // Query di base
        $query = $this->certificatiService->buildMilitariQuery($request);
        
        // Applica filtri specifici per le idoneità
        $query = $this->certificatiService->applyIdoneitaFilters($query, $request);
        
        // Esegui la query con paginazione
        $militari = $query->paginate(25);
        
        // Recupera mansioni per i filtri
        $mansioni = Mansione::orderBy('nome')->get();
        
        return view('certificati.idoneita', compact('militari', 'mansioni'));
    }

    /**
     * Form per creare un certificato
     * 
     * @param int $militare ID del militare
     * @param string $tipo Tipo di certificato
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create($militare, $tipo)
    {
        try {
            $militareInstance = Militare::findOrFail($militare);

            return view('certificati.form', [
                'militare' => $militareInstance,
                'tipo' => $tipo
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nella creazione del form certificato', [
                'militare_id' => $militare,
                'tipo' => $tipo,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore nel caricamento del form: ' . $e->getMessage()]);
        }
    }

    /**
     * Form per modificare un certificato
     * 
     * @param int $id ID del certificato
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        try {
            $certificato = $this->certificatiService->findCertificato($id);
            $militare = Militare::findOrFail($certificato->militare_id);

            return view('certificati.edit', [
                'certificato' => $certificato,
                'militare' => $militare,
                'tipo' => $certificato->tipo
            ]);
        } catch (\Exception $e) {
            Log::error('Errore nel caricamento del certificato per modifica', [
                'certificato_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore nel caricamento del certificato: ' . $e->getMessage()]);
        }
    }

    /**
     * Upload file certificato
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first('file')
            ], 422);
        }

        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Nessun file caricato'
                ], 400);
            }

            $file = $request->file('file');
            $militareId = $request->input('militare_id');
            $tipo = $request->input('tipo');
            
            if (!$militareId || !$tipo) {
                return response()->json([
                    'success' => false,
                    'error' => 'Militare ID e tipo sono richiesti per l\'upload'
                ], 400);
            }
            
            $militare = Militare::findOrFail($militareId);
            $fileData = $this->certificatiService->saveFile($file, $militare, $tipo);
            
            return response()->json([
                'success' => true,
                'path' => $fileData['path'],
                'filename' => $fileData['filename']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore durante l\'upload del file', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Errore durante il caricamento del file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Salva un nuovo certificato
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validazione con messaggi personalizzati
        $validated = $request->validate([
            'militare_id' => 'required|exists:militari,id',
            'tipo' => 'required|string',
            'data_ottenimento' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'data_scadenza' => [
                'nullable',
                'date',
                'after:data_ottenimento',
            ],
            'ente_rilascio' => 'nullable|string|max:255',
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240' // 10MB
            ]
        ], [
            'militare_id.required' => 'Il militare è obbligatorio.',
            'militare_id.exists' => 'Il militare selezionato non esiste.',
            'tipo.required' => 'Il tipo di certificato è obbligatorio.',
            'data_ottenimento.required' => 'La data di ottenimento è obbligatoria.',
            'data_ottenimento.date' => 'La data di ottenimento deve essere una data valida.',
            'data_ottenimento.before_or_equal' => 'La data di ottenimento non può essere futura.',
            'data_scadenza.date' => 'La data di scadenza deve essere una data valida.',
            'data_scadenza.after' => 'La data di scadenza deve essere successiva alla data di ottenimento.',
            'ente_rilascio.max' => 'L\'ente di rilascio non può superare i 255 caratteri.',
            'file.file' => 'Il file caricato non è valido.',
            'file.mimes' => 'Il file deve essere in formato PDF, JPG, JPEG o PNG.',
            'file.max' => 'Il file non può superare i 10MB.'
        ]);

        try {
            DB::beginTransaction();

            // Gestisci upload file
            $filePath = null;
            if ($request->hasFile('file')) {
                $militare = Militare::findOrFail($validated['militare_id']);
                $fileData = $this->certificatiService->saveFile($request->file('file'), $militare, $validated['tipo']);
                $filePath = $fileData['path'];
            }

            // Prepara i dati per il salvataggio
            $data = [
                'militare_id' => $validated['militare_id'],
                'tipo' => $validated['tipo'],
                'data_ottenimento' => $validated['data_ottenimento'],
                'data_scadenza' => $validated['data_scadenza'],
                'ente_rilascio' => $validated['ente_rilascio'],
                'file_path' => $filePath,
            ];

            // Determina il modello da utilizzare
            $modelClass = $this->certificatiService->getCertificatoModelClass($validated['tipo']);
            $certificato = $modelClass::create($data);

            // Invalida la cache
            $this->certificatiService->invalidateCache();

            DB::commit();

            // Determina la route di reindirizzamento
            $redirectRoute = $this->certificatiService->getRedirectRoute($validated['tipo']);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Certificato salvato con successo!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Rimuovi il file se è stato caricato
            if (isset($filePath)) {
                $this->certificatiService->deleteFile($filePath);
            }

            Log::error('Errore durante il salvataggio del certificato', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Errore durante il salvataggio: ' . $e->getMessage()]);
        }
    }

    /**
     * Aggiorna un certificato esistente
     * 
     * @param Request $request
     * @param int $id ID del certificato
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $certificato = $this->certificatiService->findCertificato($id);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Certificato non trovato.']);
        }

        // Validazione
        $validated = $request->validate([
            'data_ottenimento' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'data_scadenza' => [
                'nullable',
                'date',
                'after:data_ottenimento',
            ],
            'ente_rilascio' => 'nullable|string|max:255',
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240'
            ]
        ], [
            'data_ottenimento.required' => 'La data di ottenimento è obbligatoria.',
            'data_ottenimento.date' => 'La data di ottenimento deve essere una data valida.',
            'data_ottenimento.before_or_equal' => 'La data di ottenimento non può essere futura.',
            'data_scadenza.date' => 'La data di scadenza deve essere una data valida.',
            'data_scadenza.after' => 'La data di scadenza deve essere successiva alla data di ottenimento.',
            'ente_rilascio.max' => 'L\'ente di rilascio non può superare i 255 caratteri.',
            'file.file' => 'Il file caricato non è valido.',
            'file.mimes' => 'Il file deve essere in formato PDF, JPG, JPEG o PNG.',
            'file.max' => 'Il file non può superare i 10MB.'
        ]);

        try {
            DB::beginTransaction();

            $oldFilePath = $certificato->file_path;

            // Gestisci upload nuovo file
            if ($request->hasFile('file')) {
                $militare = Militare::findOrFail($certificato->militare_id);
                $fileData = $this->certificatiService->saveFile($request->file('file'), $militare, $certificato->tipo);
                $validated['file_path'] = $fileData['path'];
                
                // Rimuovi il vecchio file
                if ($oldFilePath) {
                    $this->certificatiService->deleteFile($oldFilePath);
                }
            }

            // Aggiorna il certificato
            $certificato->update($validated);

            // Invalida la cache
            $this->certificatiService->invalidateCache();

            DB::commit();

            // Determina la route di reindirizzamento
            $redirectRoute = $this->certificatiService->getRedirectRoute($certificato->tipo);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Certificato aggiornato con successo!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Rimuovi il nuovo file se è stato caricato
            if (isset($validated['file_path'])) {
                $this->certificatiService->deleteFile($validated['file_path']);
            }

            Log::error('Errore durante l\'aggiornamento del certificato', [
                'certificato_id' => $id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()]);
        }
    }

    /**
     * Elimina un certificato
     * 
     * @param int $id ID del certificato
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $certificato = $this->certificatiService->findCertificato($id);
            $tipo = $certificato->tipo;

            // Elimina il file associato, se presente
            if ($certificato->file_path) {
                $this->certificatiService->deleteFile($certificato->file_path);
            }

            // Elimina il certificato
            $certificato->delete();
            
            // Invalida la cache
            $this->certificatiService->invalidateCache();
            
            DB::commit();

            // Determina la route di reindirizzamento
            $redirectRoute = $this->certificatiService->getRedirectRoute($tipo);

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'Certificato eliminato con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore durante l\'eliminazione del certificato', [
                'certificato_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione del certificato: ' . $e->getMessage()]);
        }
    }
}
