<?php

namespace App\Http\Controllers;

use App\Models\TipoIdoneita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione della configurazione dei tipi di idoneità
 * 
 * Gestisce:
 * - Visualizzazione tipi di idoneità
 * - Creazione nuovi tipi
 * - Modifica tipi esistenti (nome, descrizione, durata)
 * - Eliminazione tipi di idoneità
 */
class GestioneIdoneitaController extends Controller
{
    /**
     * Visualizza la pagina di gestione idoneità
     */
    public function index(Request $request)
    {
        // Recupera tutti i tipi di idoneità ordinati
        $query = TipoIdoneita::query();

        // Filtra per stato attivo se richiesto
        if ($request->filled('attivo')) {
            $attivo = $request->attivo === '1';
            $query->where('attivo', $attivo);
        }

        $idoneita = $query->ordinati()->get();

        return view('gestione-idoneita.index', compact('idoneita'));
    }

    /**
     * Crea un nuovo tipo di idoneità
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:tipi_idoneita,nome',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
        ]);

        try {
            // Genera codice univoco
            $codice = \Str::slug($validated['nome'], '_');
            
            // FIX: Verifica unicità del codice con protezione anti-loop infinito
            $baseCode = $codice;
            $counter = 1;
            $maxIterations = 1000;
            while (TipoIdoneita::where('codice', $codice)->exists()) {
                $codice = $baseCode . '_' . $counter;
                $counter++;
                
                if ($counter > $maxIterations) {
                    throw new \RuntimeException('Impossibile generare un codice univoco.');
                }
            }

            // Determina ordine
            $maxOrdine = TipoIdoneita::max('ordine') ?? 0;
            
            $idoneita = TipoIdoneita::create([
                'codice' => $codice,
                'nome' => $validated['nome'],
                'descrizione' => $validated['descrizione'] ?? null,
                'durata_mesi' => $validated['durata_mesi'],
                'attivo' => true,
                'ordine' => $maxOrdine + 1,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di idoneità creato con successo',
                    'idoneita' => $idoneita
                ]);
            }

            return redirect()->route('gestione-idoneita.index')
                ->with('success', 'Tipo di idoneità creato con successo');

        } catch (\Exception $e) {
            Log::error('Errore creazione tipo idoneità', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $validated,
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante la creazione. Riprova o contatta l\'amministratore.'
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante la creazione. Riprova.'])
                ->withInput();
        }
    }

    /**
     * Aggiorna un tipo di idoneità (solo durata)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'durata_mesi' => 'required|integer|min:0'
        ]);

        try {
            $idoneita = TipoIdoneita::findOrFail($id);
            $idoneita->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tipo di idoneità aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento tipo idoneità', [
                'user_id' => auth()->id(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.'
            ], 500);
        }
    }

    /**
     * Modifica completa un tipo di idoneità
     */
    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
        ]);

        try {
            $idoneita = TipoIdoneita::findOrFail($id);
            
            // Aggiorna il codice se il nome cambia
            if ($idoneita->nome !== $validated['nome']) {
                $nuovoCodice = \Str::slug($validated['nome'], '_');
                
                // FIX: Verifica unicità del nuovo codice con protezione anti-loop infinito
                $baseCode = $nuovoCodice;
                $counter = 1;
                $maxIterations = 1000;
                while (TipoIdoneita::where('codice', $nuovoCodice)->where('id', '!=', $id)->exists()) {
                    $nuovoCodice = $baseCode . '_' . $counter;
                    $counter++;
                    
                    if ($counter > $maxIterations) {
                        throw new \RuntimeException('Impossibile generare un codice univoco.');
                    }
                }
                
                $validated['codice'] = $nuovoCodice;
            }
            
            $idoneita->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di idoneità modificato con successo',
                    'idoneita' => $idoneita
                ]);
            }

            return redirect()->route('gestione-idoneita.index')
                ->with('success', 'Tipo di idoneità modificato con successo');

        } catch (\Exception $e) {
            Log::error('Errore modifica tipo idoneità', [
                'user_id' => auth()->id(),
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante la modifica. Riprova.'
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante la modifica. Riprova.'])
                ->withInput();
        }
    }

    /**
     * Elimina un tipo di idoneità
     */
    public function destroy(Request $request, $id)
    {
        try {
            // FIX N+1: Usa withCount per ottimizzare la query
            $idoneita = TipoIdoneita::withCount('scadenzeIdoneita')->findOrFail($id);
            
            // Verifica se ci sono scadenze associate
            $countScadenze = $idoneita->scadenze_idoneita_count;
            
            if ($countScadenze > 0) {
                // Disattiva invece di eliminare se ci sono dati associati
                $idoneita->update(['attivo' => false]);
                $message = 'Tipo di idoneità disattivato (ci sono ' . $countScadenze . ' scadenze associate)';
            } else {
                $idoneita->delete();
                $message = 'Tipo di idoneità eliminato con successo';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('gestione-idoneita.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Errore eliminazione tipo idoneità', [
                'user_id' => auth()->id(),
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Si è verificato un errore durante l\'eliminazione. Potrebbe essere in uso.'
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Si è verificato un errore durante l\'eliminazione. Riprova.']);
        }
    }

    /**
     * Toggle stato attivo/disattivo
     */
    public function toggleActive(Request $request, $id)
    {
        try {
            $idoneita = TipoIdoneita::findOrFail($id);
            $idoneita->attivo = !$idoneita->attivo;
            $idoneita->save();

            return response()->json([
                'success' => true,
                'message' => $idoneita->attivo ? 'Tipo idoneità attivato' : 'Tipo idoneità disattivato',
                'attivo' => $idoneita->attivo
            ]);

        } catch (\Exception $e) {
            Log::error('Errore toggle stato tipo idoneità', [
                'user_id' => auth()->id(),
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'operazione. Riprova.'
            ], 500);
        }
    }

    /**
     * Aggiorna l'ordine dei tipi di idoneità tramite drag & drop
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'ordini' => 'required|array',
            'ordini.*' => 'required|integer|exists:tipi_idoneita,id'
        ]);

        try {
            foreach ($validated['ordini'] as $ordine => $id) {
                TipoIdoneita::where('id', $id)->update(['ordine' => $ordine]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Ordine aggiornato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ordine tipi idoneità', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'ordine'
            ], 500);
        }
    }
}

