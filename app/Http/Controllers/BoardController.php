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

use App\Models\BoardActivity;
use App\Models\BoardColumn;
use App\Models\Militare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
    public function index(Request $request)
    {
        // Vista unica per tutto il Battaglione - nessun filtro per compagnia
        // Carica tutte le colonne con tutte le attività
        $columns = BoardColumn::with(['activities' => function($query) {
                $query->with(['militari', 'compagniaMounting']);
            }])
            ->orderBy('order')
            ->get();
        
        // Ottieni tutte le compagnie per la selezione nel form
        $compagnie = \App\Models\Compagnia::orderBy('nome')->get();
            
        return view('board.index', compact('columns', 'compagnie'));
    }

    /**
     * Mostra la vista calendario delle attività
     * 
     * @return \Illuminate\View\View Vista calendario con le attività ordinate per data
     */
    public function calendar()
    {
        $activities = BoardActivity::with([
                'militari.grado',
                'militari.compagnia',
                'militari.plotone',
                'column',
                'compagniaMounting'
            ])
            ->orderBy('start_date')
            ->get()
            ->map(function (BoardActivity $activity) {
                $activity->militari_payload = $activity->militari->map(function ($militare) {
                    $grado = optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '';
                    $nomeCompleto = trim($grado . ' ' . $militare->cognome . ' ' . $militare->nome);

                    return [
                        'id' => $militare->id,
                        'nome' => $nomeCompleto,
                        'compagnia' => optional($militare->compagnia)->nome,
                        'plotone' => optional($militare->plotone)->nome,
                    ];
                })->values();

                return $activity;
            });
            
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
        $activity->load(['militari', 'creator', 'column']);
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
            
            $activity = BoardActivity::with('militari')->findOrFail($validated['activity_id']);
            
            // Verifica se la colonna è cambiata
            $colonnaModificata = $activity->column_id != $validated['column_id'];
            $militariIds = $activity->militari->pluck('id')->toArray();
            
            if ($colonnaModificata && !empty($militariIds)) {
                DB::beginTransaction();
                
                // Rimuovi le vecchie pianificazioni CPT con la vecchia tipologia
                $this->rimuoviDaCPT($activity, $militariIds);
                
                Log::info('Attività spostata - rimosso CPT vecchio', [
                    'activity_id' => $activity->id,
                    'old_column_id' => $activity->column_id,
                    'new_column_id' => $validated['column_id']
                ]);
            }
            
            $activity->update([
                'column_id' => $validated['column_id'],
                'order' => $validated['order']
            ]);
            
            if ($colonnaModificata && !empty($militariIds)) {
                // Aggiungi le nuove pianificazioni CPT con la nuova tipologia
                $activity->refresh(); // Ricarica per avere la nuova colonna
                $this->sincronizzaConCPT($activity, $militariIds);
                
                Log::info('Attività spostata - aggiunto CPT nuovo', [
                    'activity_id' => $activity->id,
                    'new_column_id' => $validated['column_id']
                ]);
                
                DB::commit();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Posizione aggiornata con successo' . ($colonnaModificata ? ' e CPT sincronizzato' : '')
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            if (isset($colonnaModificata) && $colonnaModificata) {
                DB::rollBack();
            }
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi: ' . $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            if (isset($colonnaModificata) && $colonnaModificata) {
                DB::rollBack();
            }
            
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
            
            $activity = BoardActivity::with('militari')->findOrFail($validated['activity_id']);
            
            // Salva le date vecchie
            $oldStartDate = $activity->start_date;
            $oldEndDate = $activity->end_date;
            
            DB::beginTransaction();
            
            // Rimuovi le vecchie date dal CPT
            $militariIds = $activity->militari->pluck('id')->toArray();
            if (!empty($militariIds)) {
                $this->rimuoviDaCPT($activity, $militariIds, $oldStartDate, $oldEndDate);
            }
            
            // Aggiorna le date
            $activity->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);
            
            // Sincronizza con le nuove date
            if (!empty($militariIds)) {
                $activity->refresh();
                $this->sincronizzaConCPT($activity, $militariIds);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Date aggiornate e CPT sincronizzato con successo'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
            
        } catch (\Exception $e) {
            DB::rollBack();
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
                'compagnia_id' => 'nullable|exists:compagnie,id',
                'compagnia_mounting_id' => 'required|exists:compagnie,id',
                'sigla_cpt_suggerita' => 'nullable|string|max:20',
                'militari' => 'nullable',
            ]);
            
            // Gestisci militari: può essere un array o una stringa separata da virgola
            $militariIds = [];
            if (!empty($validated['militari'])) {
                if (is_array($validated['militari'])) {
                    // Se è già un array, prendi gli ID (potrebbero essere stringhe separate da virgola in un singolo elemento)
                    foreach ($validated['militari'] as $item) {
                        if (is_string($item) && strpos($item, ',') !== false) {
                            // Stringa con più ID separati da virgola
                            $militariIds = array_merge($militariIds, array_filter(array_map('trim', explode(',', $item))));
                        } else {
                            $militariIds[] = $item;
                        }
                    }
                } else {
                    // Se è una stringa, splitta per virgola
                    $militariIds = array_filter(array_map('trim', explode(',', $validated['militari'])));
                }
            }
            
            // Valida che gli ID dei militari esistano
            if (!empty($militariIds)) {
                $existingMilitari = \App\Models\Militare::whereIn('id', $militariIds)->pluck('id')->toArray();
                $militariIds = array_intersect($militariIds, $existingMilitari);
            }

            DB::beginTransaction();

            // Ottieni l'ID dell'utente autenticato o del primo utente disponibile
            $userId = auth()->id() ?? \App\Models\User::first()->id;
            
            $activity = BoardActivity::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => ($validated['end_date'] ?? null) ?: $validated['start_date'], // Se non specificata, usa start_date
                'column_id' => $validated['column_id'],
                'compagnia_id' => $validated['compagnia_mounting_id'], // Mantieni per compatibilità
                'compagnia_mounting_id' => $validated['compagnia_mounting_id'],
                'sigla_cpt_suggerita' => $validated['sigla_cpt_suggerita'] ?? null,
                'created_by' => $userId,
                'order' => $this->getNextOrderForColumn($validated['column_id'])
            ]);

            if (!empty($militariIds)) {
                $activity->militari()->attach($militariIds);
                
                // Sincronizza automaticamente con il CPT
                $this->sincronizzaConCPT($activity, $militariIds);
            }

            DB::commit();

            return redirect()->route('board.index')
                ->with('success', 'Attività creata con successo e sincronizzata con il CPT!');
                
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
                ->withErrors(['error' => 'Si è verificato un errore durante la creazione dell\'attività. Riprova.'])
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
                'compagnia_mounting_id' => 'required|exists:compagnie,id',
            ]);

            DB::beginTransaction();

            // Salva i dati vecchi per confronto
            $oldStartDate = $activity->start_date;
            $oldEndDate = $activity->end_date;
            $oldColumnId = $activity->column_id;
            $oldTitle = $activity->title;
            $dateModificate = ($oldStartDate != $validated['start_date']) || ($oldEndDate != $validated['end_date']);
            $colonnaModificata = ($oldColumnId != $validated['column_id']);
            $titoloModificato = ($oldTitle != $validated['title']);

            // Se sono cambiate le date, la colonna o il titolo, aggiorna il CPT
            $militariAttuali = $activity->militari->pluck('id')->toArray();
            if (($dateModificate || $colonnaModificata || $titoloModificato) && !empty($militariAttuali)) {
                // Rimuovi le vecchie pianificazioni con i dati vecchi
                $this->rimuoviDaCPT($activity, $militariAttuali, $oldStartDate, $oldEndDate);
                Log::info('Rimosse vecchie pianificazioni CPT per attività modificata', [
                    'activity_id' => $activity->id,
                    'old_dates' => $oldStartDate . ' - ' . $oldEndDate,
                    'old_column_id' => $oldColumnId,
                    'old_title' => $oldTitle
                ]);
            }

            $activity->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => ($validated['end_date'] ?? null) ?: $validated['start_date'], // Se non specificata, usa start_date
                'column_id' => $validated['column_id'],
                'compagnia_id' => $validated['compagnia_mounting_id'],
                'compagnia_mounting_id' => $validated['compagnia_mounting_id']
            ]);

            // Se erano cambiate date, colonna o titolo, ricrea le pianificazioni con i nuovi dati
            if (($dateModificate || $colonnaModificata || $titoloModificato) && !empty($militariAttuali)) {
                $activity->refresh(); // Ricarica per avere i nuovi dati
                $this->sincronizzaConCPT($activity, $militariAttuali);
                Log::info('Ricreate pianificazioni CPT con nuovi dati', [
                    'activity_id' => $activity->id,
                    'new_dates' => $activity->start_date . ' - ' . $activity->end_date,
                    'new_column_id' => $activity->column_id,
                    'new_title' => $activity->title
                ]);
            }

            DB::commit();

            return redirect()->route('board.activities.show', $activity)
                ->with('success', 'Attività aggiornata con successo e sincronizzata con il CPT!');
                
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
                ->withErrors(['error' => 'Si è verificato un errore durante l\'aggiornamento dell\'attività. Riprova.'])
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
                'militare_id' => 'required|exists:militari,id',
                'force' => 'nullable|boolean' // Per forzare l'aggiunta anche con conflitti
            ]);
            
            // Carica il militare con tutte le relazioni necessarie
            $militare = Militare::with(['grado', 'plotone', 'polo', 'compagnia'])->findOrFail($validated['militare_id']);
            
            // Verifica se il militare è già associato
            if ($activity->militari()->where('militare_id', $militare->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Questo militare è già assegnato a questa attività'
                ], 400);
            }
            
            // **CONTROLLO DISPONIBILITÀ OBBLIGATORIO**
            // Verifica disponibilità per ogni giorno dell'attività
            $startDate = \Carbon\Carbon::parse($activity->start_date);
            $endDate = $activity->end_date ? \Carbon\Carbon::parse($activity->end_date) : $startDate->copy();
            
            $conflitti = [];
            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $disponibilita = $militare->isDisponibile($date->format('Y-m-d'));
                if (!$disponibilita['disponibile']) {
                    $conflitti[] = [
                        'data' => $date->format('d/m/Y'),
                        'motivo' => $disponibilita['motivo'],
                        'tipo' => $disponibilita['tipo']
                    ];
                }
            }
            
            // Se ci sono conflitti e non è forzato, restituisci errore
            if (!empty($conflitti) && !($validated['force'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'has_conflicts' => true,
                    'message' => 'Il militare ha conflitti di disponibilità',
                    'conflicts' => $conflitti,
                    'militare' => [
                        'id' => $militare->id,
                        'nome_completo' => optional($militare->grado)->abbreviazione . ' ' . $militare->cognome . ' ' . $militare->nome
                    ]
                ], 409);
            }
            
            // Associa il militare
            $activity->militari()->attach($militare->id);
            
            // Sincronizza con CPT
            $this->sincronizzaConCPT($activity, [$militare->id]);
            
            return response()->json([
                'success' => true,
                'message' => 'Militare aggiunto con successo all\'attività e sincronizzato con il CPT!',
                'militare' => [
                    'id' => $militare->id,
                    'nome' => $militare->nome,
                    'cognome' => $militare->cognome,
                    'grado' => $militare->grado ? [
                        'id' => $militare->grado->id,
                        'nome' => $militare->grado->nome,
                        'abbreviazione' => $militare->grado->abbreviazione
                    ] : null,
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
            DB::beginTransaction();
            
            // Rimuovi il militare dall'attività
            $activity->militari()->detach($militare->id);
            
            // Rimuovi dal CPT
            $this->rimuoviDaCPT($activity, [$militare->id]);
            
            DB::commit();
            
            Log::info('Militare rimosso da attività e CPT', [
                'activity_id' => $activity->id,
                'militare_id' => $militare->id,
                'militare_nome' => $militare->cognome . ' ' . $militare->nome
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Militare rimosso con successo dall\'attività e dal CPT'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore rimozione militare', [
                'activity_id' => $activity->id,
                'militare_id' => $militare->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la rimozione del militare'
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
            
            // PRIMA rimuovi dal CPT tutte le pianificazioni associate
            $militariIds = $activity->militari->pluck('id')->toArray();
            if (!empty($militariIds)) {
                $this->rimuoviDaCPT($activity, $militariIds);
                Log::info('Rimossi dal CPT tutti i giorni dell\'attività eliminata', [
                    'activity_id' => $activity->id,
                    'activity_title' => $activity->title,
                    'militari_count' => count($militariIds)
                ]);
            }
            
            // Elimina l'attività (cascade eliminerà relazioni)
            $activity->delete();
            
            DB::commit();
            
            return redirect()->route('board.index')
                ->with('success', 'Attività eliminata definitivamente e rimossa dal CPT!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore eliminazione attività', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante l\'eliminazione dell\'attività. Riprova.']);
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
    
    /**
     * Sincronizza l'attività della board con il CPT
     * Crea automaticamente voci CPT per i militari assegnati all'attività
     * 
     * @param BoardActivity $activity Attività della board
     * @param array $militariIds Array di ID dei militari assegnati
     * @return void
     */
    private function sincronizzaConCPT(BoardActivity $activity, array $militariIds)
    {
        try {
            // Ottieni la colonna dell'attività
            $column = BoardColumn::find($activity->column_id);
            
            // Determina il codice CPT da usare
            $codiceCPT = null;
            
            // 1. Priorità alla sigla suggerita dall'attività
            if (!empty($activity->sigla_cpt_suggerita)) {
                $codiceCPT = strtoupper($activity->sigla_cpt_suggerita);
                Log::info('Usando sigla CPT suggerita', [
                    'sigla' => $codiceCPT,
                    'activity_id' => $activity->id,
                    'activity_title' => $activity->title
                ]);
            } else {
                // 2. Fallback al mapping predefinito basato sulla colonna
                $mappingCPT = [
                    'servizi-isolati' => 'S.I.',    // Servizi Isolati
                    'cattedre' => 'Cattedra',       // Cattedre
                    'corsi' => 'Corso',             // Corsi
                    'esercitazioni' => 'EXE',       // Esercitazioni
                    'operazioni' => 'T.O.',         // Teatro Operativo
                    'stand-by' => null              // Non richiede CPT
                ];
                
                if ($column && isset($mappingCPT[$column->slug])) {
                    $codiceCPT = $mappingCPT[$column->slug];
                }
                
                if (!$codiceCPT) {
                    Log::info('Colonna non richiede sincronizzazione CPT', [
                        'column_slug' => $column->slug ?? 'unknown',
                        'activity_id' => $activity->id
                    ]);
                    return;
                }
            }
            
            // Trova il tipo servizio CPT corrispondente
            $tipoServizio = \App\Models\TipoServizio::where('codice', $codiceCPT)
                ->where('attivo', true)
                ->first();
            
            if (!$tipoServizio) {
                Log::warning('Codice CPT non trovato nei tipi servizio attivi', [
                    'codice' => $codiceCPT,
                    'activity_id' => $activity->id,
                    'activity_title' => $activity->title,
                    'suggerimento' => 'Verifica che il codice CPT esista in Gestionale > Gestione CPT'
                ]);
                return;
            }
            
            // Crea le date dall'inizio alla fine dell'attività
            $startDate = \Carbon\Carbon::parse($activity->start_date);
            $endDate = $activity->end_date ? \Carbon\Carbon::parse($activity->end_date) : $startDate->copy();
            
            // Per ogni militare assegnato
            foreach ($militariIds as $militareId) {
                // Per ogni giorno dell'attività
                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    // Trova o crea la pianificazione mensile
                    $pianificazioneMensile = \App\Models\PianificazioneMensile::firstOrCreate(
                        [
                            'mese' => $currentDate->month,
                            'anno' => $currentDate->year,
                        ],
                        [
                            'nome' => $currentDate->translatedFormat('F Y'),
                            'stato' => 'attiva',
                            'data_creazione' => $currentDate->format('Y-m-d'),
                        ]
                    );
                    
                    // Verifica se esiste già un impegno IDENTICO (stesso tipo servizio) per evitare duplicati
                    $esisteGia = \App\Models\PianificazioneGiornaliera::where([
                        'pianificazione_mensile_id' => $pianificazioneMensile->id,
                        'militare_id' => $militareId,
                        'giorno' => $currentDate->day,
                        'tipo_servizio_id' => $tipoServizio->id,
                    ])->exists();
                    
                    // Se non esiste già lo stesso tipo di impegno, crealo (permette impegni multipli diversi)
                    if (!$esisteGia) {
                        \App\Models\PianificazioneGiornaliera::create([
                            'pianificazione_mensile_id' => $pianificazioneMensile->id,
                            'militare_id' => $militareId,
                            'giorno' => $currentDate->day,
                            'tipo_servizio_id' => $tipoServizio->id,
                            'note' => "{$column->name}: {$activity->title}"
                        ]);
                    }
                    
                    $currentDate->addDay();
                }
            }
            
            Log::info('Attività sincronizzata con CPT', [
                'activity_id' => $activity->id,
                'activity_title' => $activity->title,
                'codice_cpt' => $codiceCPT,
                'militari_count' => count($militariIds),
                'giorni' => $startDate->diffInDays($endDate) + 1
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore sincronizzazione CPT da Board', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Rimuove un'attività dal CPT
     * 
     * @param BoardActivity $activity Attività della board
     * @param array $militariIds Array di ID dei militari
     * @param \Carbon\Carbon|null $startDate Data inizio (default: usa activity->start_date)
     * @param \Carbon\Carbon|null $endDate Data fine (default: usa activity->end_date)
     * @return void
     */
    private function rimuoviDaCPT(BoardActivity $activity, array $militariIds, $startDate = null, $endDate = null)
    {
        try {
            // Usa le date dell'attività se non specificate
            $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : \Carbon\Carbon::parse($activity->start_date);
            $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : ($activity->end_date ? \Carbon\Carbon::parse($activity->end_date) : $startDate->copy());
            
            // Trova la colonna per creare la nota corretta
            $column = BoardColumn::find($activity->column_id);
            $notaIdentificativa = $column ? "{$column->name}: {$activity->title}" : "Attività Board: {$activity->title}";
            
            // Per ogni militare
            foreach ($militariIds as $militareId) {
                // Per ogni giorno dell'attività
                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    // Trova la pianificazione mensile
                    $pianificazioneMensile = \App\Models\PianificazioneMensile::where('mese', $currentDate->month)
                        ->where('anno', $currentDate->year)
                        ->first();
                    
                    if ($pianificazioneMensile) {
                        // Elimina le pianificazioni giornaliere che corrispondono a questa attività
                        $deleted = \App\Models\PianificazioneGiornaliera::where('pianificazione_mensile_id', $pianificazioneMensile->id)
                            ->where('militare_id', $militareId)
                            ->where('giorno', $currentDate->day)
                            ->where('note', $notaIdentificativa)
                            ->delete();
                        
                        if ($deleted > 0) {
                            Log::debug('Rimossa pianificazione CPT', [
                                'militare_id' => $militareId,
                                'data' => $currentDate->format('Y-m-d'),
                                'activity_title' => $activity->title
                            ]);
                        }
                    }
                    
                    $currentDate->addDay();
                }
            }
            
            Log::info('Attività rimossa dal CPT', [
                'activity_id' => $activity->id,
                'activity_title' => $activity->title,
                'militari_count' => count($militariIds),
                'periodo' => $startDate->format('Y-m-d') . ' - ' . $endDate->format('Y-m-d')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Errore rimozione attività da CPT', [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Esporta i dettagli di una singola attività in Excel
     *
     * @param BoardActivity $activity
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportActivity(BoardActivity $activity)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Imposta il titolo
        $sheet->setTitle('Dettagli Attività');
        
        // Header
        $sheet->setCellValue('A1', 'DETTAGLI ATTIVITÀ');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Informazioni base
        $row = 3;
        $sheet->setCellValue("A{$row}", 'Titolo:')->setCellValue("B{$row}", $activity->title);
        $row++;
        $sheet->setCellValue("A{$row}", 'Descrizione:')->setCellValue("B{$row}", $activity->description ?? '-');
        $row++;
        $sheet->setCellValue("A{$row}", 'Categoria:')->setCellValue("B{$row}", $activity->column->name ?? '-');
        $row++;
        $sheet->setCellValue("A{$row}", 'Compagnia Mounting:')->setCellValue("B{$row}", $activity->compagniaMounting->nome ?? '-');
        $row++;
        $sheet->setCellValue("A{$row}", 'Data Inizio:')->setCellValue("B{$row}", $activity->start_date->format('d/m/Y'));
        $row++;
        $sheet->setCellValue("A{$row}", 'Data Fine:')->setCellValue("B{$row}", $activity->end_date ? $activity->end_date->format('d/m/Y') : '-');
        $row++;
        
        // Stili per le informazioni base
        $sheet->getStyle("A3:A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A3:B{$row}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A3:B{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Militari coinvolti
        $row += 2;
        $sheet->setCellValue("A{$row}", 'MILITARI COINVOLTI');
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        $row++;
        $sheet->setCellValue("A{$row}", 'Compagnia')
              ->setCellValue("B{$row}", 'Grado')
              ->setCellValue("C{$row}", 'Cognome')
              ->setCellValue("D{$row}", 'Nome');
        $sheet->getStyle("A{$row}:D{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:D{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$row}:D{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Ordina militari per compagnia e grado
        $militari = $activity->militari->sortBy(function($m) {
            $compagniaOrdine = 999;
            if ($m->compagnia) {
                if ($m->compagnia->nome == '110') $compagniaOrdine = 1;
                elseif ($m->compagnia->nome == '124') $compagniaOrdine = 2;
                elseif ($m->compagnia->nome == '127') $compagniaOrdine = 3;
            }
            $gradoOrdine = -1 * (optional($m->grado)->ordine ?? 0);
            return [$compagniaOrdine, $gradoOrdine, $m->cognome, $m->nome];
        });
        
        foreach ($militari as $militare) {
            $row++;
            $sheet->setCellValue("A{$row}", $militare->compagnia ? $militare->compagnia->nome : '-')
                  ->setCellValue("B{$row}", optional($militare->grado)->abbreviazione ?? '-')
                  ->setCellValue("C{$row}", $militare->cognome)
                  ->setCellValue("D{$row}", $militare->nome);
        }
        
        // Bordi e allineamento per la tabella militari
        $lastRow = $row;
        $sheet->getStyle("A" . ($lastRow - $militari->count()) . ":D{$lastRow}")
            ->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A" . ($lastRow - $militari->count()) . ":D{$lastRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Imposta larghezza colonne
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        
        // Genera il file
        $filename = 'Attivita_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $activity->title) . '_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
    
    /**
     * Esporta l'intera board con tutte le attività in Excel
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportBoard()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Imposta il titolo
        $sheet->setTitle('Board Attività');
        
        // Header
        $sheet->setCellValue('A1', 'BOARD ATTIVITÀ - VISTA COMPLETA');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0A2342');
        $sheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Header tabella
        $row = 3;
        $sheet->setCellValue("A{$row}", 'Categoria')
              ->setCellValue("B{$row}", 'Titolo')
              ->setCellValue("C{$row}", 'Compagnia Mounting')
              ->setCellValue("D{$row}", 'Data Inizio')
              ->setCellValue("E{$row}", 'Data Fine')
              ->setCellValue("F{$row}", 'N° Militari')
              ->setCellValue("G{$row}", 'Militari Coinvolti');
        
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:G{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle("A{$row}:G{$row}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        
        // Ottieni tutte le attività ordinate per colonna
        $activities = BoardActivity::with(['column', 'compagniaMounting', 'militari.grado', 'militari.compagnia'])
            ->orderBy('column_id')
            ->orderBy('start_date')
            ->get();
        
        foreach ($activities as $activity) {
            $row++;
            
            // Ordina militari
            $militari = $activity->militari->sortBy(function($m) {
                $compagniaOrdine = 999;
                if ($m->compagnia) {
                    if ($m->compagnia->nome == '110') $compagniaOrdine = 1;
                    elseif ($m->compagnia->nome == '124') $compagniaOrdine = 2;
                    elseif ($m->compagnia->nome == '127') $compagniaOrdine = 3;
                }
                $gradoOrdine = -1 * (optional($m->grado)->ordine ?? 0);
                return [$compagniaOrdine, $gradoOrdine, $m->cognome, $m->nome];
            });
            
            $militariNomi = $militari->map(function($m) {
                $compagnia = $m->compagnia ? "[{$m->compagnia->nome}] " : '';
                return $compagnia . (optional($m->grado)->abbreviazione ?? '') . ' ' . $m->cognome . ' ' . $m->nome;
            })->join("\n");
            
            $sheet->setCellValue("A{$row}", $activity->column->name ?? '-')
                  ->setCellValue("B{$row}", $activity->title)
                  ->setCellValue("C{$row}", $activity->compagniaMounting->nome ?? '-')
                  ->setCellValue("D{$row}", $activity->start_date->format('d/m/Y'))
                  ->setCellValue("E{$row}", $activity->end_date ? $activity->end_date->format('d/m/Y') : '-')
                  ->setCellValue("F{$row}", $activity->militari->count())
                  ->setCellValue("G{$row}", $militariNomi ?: '-');
            
            $sheet->getStyle("G{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A{$row}:G{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A{$row}:G{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
        
        // Imposta larghezza colonne
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(50);
        
        // Genera il file
        $filename = 'Board_Attivita_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        $temp_file = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($temp_file);
        
        return response()->download($temp_file, $filename)->deleteFileAfterSend(true);
    }
} 
