<?php

namespace App\Http\Controllers;

use App\Models\ConfigurazioneCampoAnagrafica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GestioneCampiAnagraficaController extends Controller
{
    /**
     * Mostra la pagina di gestione campi anagrafica
     */
    public function index(Request $request)
    {
        $unitId = activeUnitId();
        if (!$unitId) {
            $campi = collect();
            return view('gestione-campi-anagrafica.index', compact('campi'));
        }

        // Assicurati che i campi di sistema esistano per l'unità organizzativa attiva
        ConfigurazioneCampoAnagrafica::ensureSystemFieldsForUnit($unitId);

        // Carica i campi dell'unità attiva (sistema + custom) ordinati
        $campi = ConfigurazioneCampoAnagrafica::forUnit($unitId)->ordinati()->get();

        // RIMOSSO: Normalizzazione ordine automatica ad ogni accesso
        // La normalizzazione era problematica perché modificava il database ad ogni page load.
        // Se serve normalizzare gli ordini, usare un comando Artisan dedicato:
        // php artisan campi:normalizza-ordine
        
        return view('gestione-campi-anagrafica.index', compact('campi'));
    }

    /**
     * Crea un nuovo campo
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'etichetta' => 'required|string|max:255',
                'tipo_campo' => 'required|in:text,select,date,number,textarea,checkbox,email,tel',
                'opzioni' => 'nullable|array',
                'obbligatorio' => 'nullable|boolean',
                'descrizione' => 'nullable|string',
            ]);
            
            // Gestisci opzioni: se il tipo è select o checkbox e non ci sono opzioni, imposta null
            if (($validated['tipo_campo'] === 'select' || $validated['tipo_campo'] === 'checkbox')) {
                if (empty($validated['opzioni']) || (is_array($validated['opzioni']) && count(array_filter($validated['opzioni'])) === 0)) {
                    $validated['opzioni'] = null;
                }
            } else {
                // Per altri tipi, rimuovi le opzioni
                $validated['opzioni'] = null;
            }
            
            $unitId = activeUnitId();
            if (!$unitId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seleziona un\'unità organizzativa per creare campi.'
                ], 403);
            }

            // Genera slug univoco dal nome etichetta
            $nomeCampo = Str::slug($validated['etichetta'], '_');
            
            // Assicura unicità nell'ambito dell'unità
            $counter = 1;
            $originalNomeCampo = $nomeCampo;
            while (ConfigurazioneCampoAnagrafica::where('organizational_unit_id', $unitId)->where('nome_campo', $nomeCampo)->exists()) {
                $nomeCampo = $originalNomeCampo . '_' . $counter;
                $counter++;
            }
            
            // Calcola ordine automatico (posizione successiva) per l'unità
            $maxOrdine = ConfigurazioneCampoAnagrafica::forUnit($unitId)->max('ordine') ?? 0;
            
            $campo = ConfigurazioneCampoAnagrafica::create([
                'organizational_unit_id' => $unitId,
                'nome_campo' => $nomeCampo,
                'etichetta' => $validated['etichetta'],
                'tipo_campo' => $validated['tipo_campo'],
                'opzioni' => $validated['opzioni'],
                'ordine' => $maxOrdine + 1,
                'attivo' => true,
                'obbligatorio' => $validated['obbligatorio'] ?? false,
                'descrizione' => $validated['descrizione'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campo creato con successo',
                'campo' => $campo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errore validazione campo anagrafica', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log dettagliato per debug, messaggio generico per l'utente
            Log::error('Errore creazione campo anagrafica', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la creazione del campo. Riprova o contatta l\'amministratore.'
            ], 500);
        }
    }

    /**
     * Aggiorna un campo (nome, tipo, opzioni)
     */
    public function edit(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'etichetta' => 'required|string|max:255',
                'tipo_campo' => 'required|in:text,select,date,number,textarea,checkbox,email,tel',
                'opzioni' => 'nullable|array',
                'obbligatorio' => 'nullable|boolean',
                'descrizione' => 'nullable|string',
            ]);
            
            // Gestisci opzioni: se il tipo è select o checkbox e non ci sono opzioni, imposta null
            if (($validated['tipo_campo'] === 'select' || $validated['tipo_campo'] === 'checkbox')) {
                if (empty($validated['opzioni']) || (is_array($validated['opzioni']) && count(array_filter($validated['opzioni'])) === 0)) {
                    $validated['opzioni'] = null;
                }
            } else {
                // Per altri tipi, rimuovi le opzioni
                $validated['opzioni'] = null;
            }
            
            $unitId = activeUnitId();
            if (!$unitId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seleziona un\'unità organizzativa per modificare campi.'
                ], 403);
            }
            $campo = ConfigurazioneCampoAnagrafica::forUnit($unitId)->findOrFail($id);
            
            // Definisci i campi di sistema (il nome_campo non deve mai cambiare)
            $campiSistemaNomi = [
                'compagnia', 'grado', 'cognome', 'nome', 'plotone', 'ufficio', 
                'incarico', 'patenti', 'nos', 'anzianita', 'data_nascita', 
                'email_istituzionale', 'telefono', 'codice_fiscale', 'istituti'
            ];
            
            // Se è un campo di sistema, NON cambiare mai il nome_campo
            if (in_array($campo->nome_campo, $campiSistemaNomi)) {
                // Rimuovi nome_campo dai dati validati per evitare che venga modificato
                unset($validated['nome_campo']);
            } else {
                // Per i campi custom, aggiorna il nome_campo solo se l'etichetta è cambiata
                if ($campo->etichetta !== $validated['etichetta']) {
                    $nomeCampo = Str::slug($validated['etichetta'], '_');
                    
                    // Assicura unicità
                    $counter = 1;
                    $originalNomeCampo = $nomeCampo;
                    while (ConfigurazioneCampoAnagrafica::forUnit($unitId)->where('nome_campo', $nomeCampo)->where('id', '!=', $id)->exists()) {
                        $nomeCampo = $originalNomeCampo . '_' . $counter;
                        $counter++;
                    }
                    
                    $validated['nome_campo'] = $nomeCampo;
                }
            }
            
            $campo->update($validated);
            
            // Ricarica il modello per assicurarsi che le opzioni siano correttamente castate
            $campo->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Campo aggiornato con successo',
                'campo' => $campo
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errore validazione campo anagrafica', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore di validazione',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log dettagliato per debug, messaggio generico per l'utente
            Log::error('Errore aggiornamento campo anagrafica', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova o contatta l\'amministratore.'
            ], 500);
        }
    }

    /**
     * Aggiorna solo l'ordine di un campo
     */
    public function updateOrder(Request $request, $id)
    {
        $validated = $request->validate([
            'ordine' => 'required|integer|min:0',
        ]);

        try {
            $unitId = activeUnitId();
            if (!$unitId) {
                return response()->json(['success' => false, 'message' => 'Unità non selezionata.'], 403);
            }
            $campo = ConfigurazioneCampoAnagrafica::forUnit($unitId)->findOrFail($id);
            $campo->update(['ordine' => $validated['ordine']]);

            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ordine campo', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'ordine. Riprova.'
            ], 500);
        }
    }

    /**
     * Toggle attivo/disattivo
     */
    public function toggleActive(Request $request, $id)
    {
        try {
            $unitId = activeUnitId();
            if (!$unitId) {
                return response()->json(['success' => false, 'message' => 'Unità non selezionata.'], 403);
            }
            $campo = ConfigurazioneCampoAnagrafica::forUnit($unitId)->findOrFail($id);
            $campo->update(['attivo' => !$campo->attivo]);

            return response()->json([
                'success' => true,
                'attivo' => $campo->attivo,
                'message' => 'Stato aggiornato'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore toggle attivo campo', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dello stato. Riprova.'
            ], 500);
        }
    }

    /**
     * Elimina un campo
     */
    public function destroy($id)
    {
        try {
            $unitId = activeUnitId();
            if (!$unitId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seleziona un\'unità organizzativa per eliminare campi.'
                ], 403);
            }
            $campo = ConfigurazioneCampoAnagrafica::forUnit($unitId)->findOrFail($id);
            
            // Blocca eliminazione dei campi di sistema
            if ($campo->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile eliminare un campo di sistema.'
                ], 403);
            }
            
            // Elimina anche tutti i valori associati (cascade)
            $campo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campo eliminato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione campo anagrafica', [
                'user_id' => auth()->id(),
                'campo_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Il campo potrebbe essere in uso.'
            ], 500);
        }
    }
}
