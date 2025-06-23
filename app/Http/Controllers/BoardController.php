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

use App\Models\BoardActivity;
use App\Models\BoardColumn;
use App\Models\ActivityAttachment;
use App\Models\Militare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Controller per la gestione della bacheca (board) delle attività
 * 
 * Questo controller gestisce le operazioni CRUD sulle attività della bacheca,
 * inclusa la gestione delle colonne, drag & drop, allegati e militari associati.
 * 
 * @package App\Http\Controllers
 * @version 1.0
 */
class BoardController extends Controller
{
    /**
     * Mostra la vista principale della bacheca
     * 
     * @return \Illuminate\View\View Vista della bacheca con colonne e attività
     */
    public function index()
    {
        $columns = BoardColumn::with(['activities.militari', 'activities.attachments'])
            ->orderBy('order')
            ->get();
            
        return view('board.index', compact('columns'));
    }

    /**
     * Mostra la vista calendario delle attività
     * 
     * @return \Illuminate\View\View Vista calendario con le attività ordinate per data
     */
    public function calendar()
    {
        $activities = BoardActivity::with(['militari', 'column'])
            ->orderBy('start_date')
            ->get();
            
        return view('board.calendar', compact('activities'));
    }

    /**
     * Mostra i dettagli di una specifica attività
     * 
     * @param BoardActivity $activity Attività da visualizzare
     * @return \Illuminate\View\View Vista dettagli dell'attività
     */
    public function show(BoardActivity $activity)
    {
        $activity->load(['militari', 'attachments', 'creator', 'column']);
        return view('board.activities.show', compact('activity'));
    }

    /**
     * Aggiorna la posizione di un'attività tramite drag & drop
     * 
     * @param Request $request Richiesta con dati di posizionamento
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function updatePosition(Request $request)
    {
        try {
            // Estrai dati dalla richiesta, supportando sia JSON che form data
            $data = $request->json()->all() ?: $request->all();
            
            $validated = $this->validatePositionData($data);
            
            $activity = BoardActivity::findOrFail($validated['activity_id']);
            
            $activity->update([
                'column_id' => $validated['column_id'],
                'order' => $validated['order']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Posizione aggiornata con successo'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi: ' . $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento posizione attività', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false, 
                'message' => 'Errore interno del server'
            ], 500);
        }
    }
    
    /**
     * Aggiorna le date di un'attività
     * 
     * @param Request $request Richiesta con nuove date
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function updateDates(Request $request)
    {
        try {
            $validated = $request->validate([
                'activity_id' => 'required|exists:board_activities,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);
            
            $activity = BoardActivity::findOrFail($validated['activity_id']);
            
            $activity->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Date aggiornate con successo'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento date attività', [
                'error' => $e->getMessage(),
                'activity_id' => $request->get('activity_id')
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore interno del server'
            ], 500);
        }
    }

    /**
     * Crea una nuova attività
     * 
     * @param Request $request Richiesta con dati della nuova attività
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'column_id' => 'required|exists:board_columns,id',
                'militari' => 'nullable|array',
                'militari.*' => 'exists:militari,id'
            ]);

            DB::beginTransaction();

            $activity = BoardActivity::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'column_id' => $validated['column_id'],
                'created_by' => auth()->id(),
                'order' => $this->getNextOrderForColumn($validated['column_id'])
            ]);

            if (!empty($validated['militari'])) {
                $activity->militari()->attach($validated['militari']);
            }

            DB::commit();

            return redirect()->route('board.activities.show', $activity)
                ->with('success', 'Attività creata con successo');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore creazione attività', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la creazione dell\'attività'])
                ->withInput();
        }
    }
    
    /**
     * Aggiorna un'attività esistente
     * 
     * @param Request $request Richiesta con dati aggiornati
     * @param BoardActivity $activity Attività da aggiornare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function update(Request $request, BoardActivity $activity)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'column_id' => 'required|exists:board_columns,id',
                'militari' => 'nullable|array',
                'militari.*' => 'exists:militari,id'
            ]);

            DB::beginTransaction();

            $activity->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'column_id' => $validated['column_id']
            ]);

            // Aggiorna militari associati
            if (isset($validated['militari'])) {
                $activity->militari()->sync($validated['militari']);
            } else {
                $activity->militari()->detach();
            }

            DB::commit();

            return redirect()->route('board.activities.show', $activity)
                ->with('success', 'Attività aggiornata con successo');
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore aggiornamento attività', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'aggiornamento dell\'attività'])
                ->withInput();
        }
    }
    
    /**
     * Salvataggio automatico dei dati dell'attività
     * 
     * @param Request $request Richiesta con dati da salvare
     * @param BoardActivity $activity Attività da aggiornare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function autoSave(Request $request, BoardActivity $activity)
    {
        try {
            $field = $request->input('field');
            $value = $request->input('value');
            
            // Campi consentiti per l'autosalvataggio
            $allowedFields = ['title', 'description', 'start_date', 'end_date', 'column_id'];
            
            if (!in_array($field, $allowedFields)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Campo non consentito'
                ], 400);
            }
            
            // Validazione specifica per campo
            $this->validateFieldValue($field, $value);
            
            $activity->update([$field => $value]);
            
            // Ricarica l'attività con le relazioni per restituire dati aggiornati
            $activity->load(['column']);
            
            return response()->json([
                'success' => true,
                'message' => 'Salvato automaticamente',
                'updated_at' => $activity->updated_at->format('d/m/Y H:i'),
                'activity' => [
                    'title' => $activity->title,
                    'description' => $activity->description,
                    'start_date' => $activity->start_date->format('d/m/Y'),
                    'end_date' => $activity->end_date ? $activity->end_date->format('d/m/Y') : null,
                    'column' => $activity->column
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore autosalvataggio attività', [
                'activity_id' => $activity->id,
                'field' => $request->input('field'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio'
            ], 500);
        }
    }

    /**
     * Associa un militare a un'attività
     * 
     * @param Request $request Richiesta con ID del militare
     * @param BoardActivity $activity Attività a cui associare il militare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function attachMilitare(Request $request, BoardActivity $activity)
    {
        try {
            $validated = $request->validate([
                'militare_id' => 'required|exists:militari,id'
            ]);
            
            // Carica il militare con tutte le relazioni necessarie
            $militare = Militare::with(['grado', 'plotone', 'polo'])->findOrFail($validated['militare_id']);
            
            // Verifica se il militare è già associato
            if ($activity->militari()->where('militare_id', $militare->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il militare è già associato a questa attività'
                ], 400);
            }
            
            $activity->militari()->attach($militare->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Militare associato con successo',
                'militare' => [
                    'id' => $militare->id,
                    'nome' => $militare->nome,
                    'cognome' => $militare->cognome,
                    'grado' => [
                        'id' => $militare->grado->id,
                        'nome' => $militare->grado->nome,
                        'abbreviazione' => $militare->grado->abbreviazione
                    ],
                    'plotone' => $militare->plotone ? [
                        'id' => $militare->plotone->id,
                        'nome' => $militare->plotone->nome
                    ] : null,
                    'polo' => $militare->polo ? [
                        'id' => $militare->polo->id,
                        'nome' => $militare->polo->nome
                    ] : null
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore associazione militare', [
                'activity_id' => $activity->id,
                'militare_id' => $request->get('militare_id'),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'associazione'
            ], 500);
        }
    }

    /**
     * Rimuove l'associazione di un militare da un'attività
     * 
     * @param Request $request Richiesta HTTP
     * @param BoardActivity $activity Attività da cui rimuovere il militare
     * @param Militare $militare Militare da rimuovere
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function detachMilitare(Request $request, BoardActivity $activity, Militare $militare)
    {
        try {
            $activity->militari()->detach($militare->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Militare rimosso con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore rimozione militare', [
                'activity_id' => $activity->id,
                'militare_id' => $militare->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione'
            ], 500);
        }
    }

    /**
     * Carica un allegato per un'attività
     * 
     * @param Request $request Richiesta con file allegato
     * @param BoardActivity $activity Attività a cui allegare il file
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function attachFile(Request $request, BoardActivity $activity)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'name' => 'nullable|string|max:255'
            ]);
            
            $file = $request->file('file');
            $originalName = $request->input('name') ?: $file->getClientOriginalName();
            
            // Salva il file
            $path = $file->store('board_attachments', 'private');
            
            // Crea record allegato
            $attachment = ActivityAttachment::create([
                'activity_id' => $activity->id,
                'filename' => $originalName,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'File allegato con successo',
                'attachment' => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'file_size' => $attachment->file_size
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Errore caricamento allegato', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il caricamento del file'
            ], 500);
        }
    }

    /**
     * Elimina un allegato
     * 
     * @param ActivityAttachment $attachment Allegato da eliminare
     * @return \Illuminate\Http\JsonResponse Risposta JSON con esito operazione
     */
    public function deleteAttachment(ActivityAttachment $attachment)
    {
        try {
            // Elimina il file fisico
            if (Storage::disk('private')->exists($attachment->file_path)) {
                Storage::disk('private')->delete($attachment->file_path);
            }
            
            // Elimina il record
            $attachment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Allegato eliminato con successo'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore eliminazione allegato', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione'
            ], 500);
        }
    }

    /**
     * Elimina un'attività
     * 
     * @param BoardActivity $activity Attività da eliminare
     * @return \Illuminate\Http\RedirectResponse Redirect con messaggio di successo o errore
     */
    public function destroy(BoardActivity $activity)
    {
        try {
            DB::beginTransaction();
            
            // Elimina allegati fisici
            foreach ($activity->attachments as $attachment) {
                if (Storage::disk('private')->exists($attachment->file_path)) {
                    Storage::disk('private')->delete($attachment->file_path);
                }
            }
            
            // Elimina l'attività (cascade eliminerà relazioni)
            $activity->delete();
            
            DB::commit();
            
            return redirect()->route('board.index')
                ->with('success', 'Attività eliminata con successo');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore eliminazione attività', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione dell\'attività']);
        }
    }

    /**
     * Valida i dati di posizionamento per il drag & drop
     * 
     * @param array $data Dati da validare
     * @return array Dati validati
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validatePositionData($data)
    {
        $validator = validator($data, [
            'activity_id' => 'required|exists:board_activities,id',
            'column_id' => 'required|exists:board_columns,id',
            'order' => 'required|integer|min:0'
        ]);

        return $validator->validated();
    }

    /**
     * Valida il valore di un campo specifico
     * 
     * @param string $field Nome del campo
     * @param mixed $value Valore da validare
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateFieldValue($field, $value)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'column_id' => 'required|exists:board_columns,id'
        ];

        if (isset($rules[$field])) {
            validator([$field => $value], [$field => $rules[$field]])->validate();
        }
    }

    /**
     * Ottiene il prossimo numero d'ordine per una colonna
     * 
     * @param int $columnId ID della colonna
     * @return int Prossimo numero d'ordine
     */
    private function getNextOrderForColumn($columnId)
    {
        return BoardActivity::where('column_id', $columnId)->max('order') + 1;
    }
} 
