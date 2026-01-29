<?php

namespace App\Http\Controllers;

use App\Models\ConfigurazioneCorsoSpp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione dei corsi SPP
 * 
 * Permette di configurare validità e stato attivo dei corsi
 */
class GestioneSppController extends Controller
{
    /**
     * Mostra la pagina di gestione SPP
     */
    public function index(Request $request)
    {
        // Recupera tutti i corsi ordinati per tipo e ordine
        $corsiQuery = ConfigurazioneCorsoSpp::query();
        
        // Filtro per tipo
        if ($request->filled('tipo')) {
            $corsiQuery->perTipo($request->tipo);
        }
        
        // Filtro per stato attivo
        if ($request->filled('attivo')) {
            if ($request->attivo === '1') {
                $corsiQuery->where('attivo', true);
            } else if ($request->attivo === '0') {
                $corsiQuery->where('attivo', false);
            }
        }
        
        $corsi = $corsiQuery->orderBy('tipo')->orderBy('ordine')->orderBy('nome_corso')->get();
        
        return view('gestione-spp.index', compact('corsi'));
    }

    /**
     * Crea un nuovo corso
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome_corso' => 'required|string|max:255',
            'tipo' => 'required|in:formazione,accordo_stato_regione',
            'durata_anni' => 'required|integer|min:0'
        ]);

        try {
            // Genera codice corso univoco da nome (es. "Carrellista" -> "carrellista")
            $codiceCors = \Str::slug($validated['nome_corso'], '_');
            
            // FIX: Verifica unicità del codice con protezione anti-loop infinito
            $baseCode = $codiceCors;
            $counter = 1;
            $maxIterations = 1000; // Limite massimo per evitare loop infinito
            while (ConfigurazioneCorsoSpp::where('codice_corso', $codiceCors)->exists()) {
                $codiceCors = $baseCode . '_' . $counter;
                $counter++;
                
                if ($counter > $maxIterations) {
                    throw new \RuntimeException('Impossibile generare un codice corso univoco. Contatta l\'amministratore.');
                }
            }
            
            // Ottieni ordine successivo per il tipo
            $maxOrdine = ConfigurazioneCorsoSpp::where('tipo', $validated['tipo'])->max('ordine');
            
            $corso = ConfigurazioneCorsoSpp::create([
                'codice_corso' => $codiceCors,
                'nome_corso' => $validated['nome_corso'],
                'tipo' => $validated['tipo'],
                'durata_anni' => $validated['durata_anni'],
                'attivo' => true,
                'ordine' => ($maxOrdine ?? 0) + 10
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Corso creato con successo',
                    'corso' => $corso
                ]);
            }

            return redirect()->route('gestione-spp.index')
                ->with('success', 'Corso creato con successo');

        } catch (\Exception $e) {
            Log::error('Errore creazione corso SPP', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'data' => $request->except(['_token']),
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
     * Aggiorna un corso (solo durata)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'durata_anni' => 'required|integer|min:0'
        ]);

        try {
            $corso = ConfigurazioneCorsoSpp::findOrFail($id);
            $corso->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Corso aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento corso SPP', [
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
     * Modifica completa un corso
     */
    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'nome_corso' => 'required|string|max:255',
            'tipo' => 'required|in:formazione,accordo_stato_regione',
            'durata_anni' => 'required|integer|min:0'
        ]);

        try {
            $corso = ConfigurazioneCorsoSpp::findOrFail($id);
            
            // Aggiorna il codice se il nome cambia
            if ($corso->nome_corso !== $validated['nome_corso']) {
                $nuovoCodice = \Str::slug($validated['nome_corso'], '_');
                
                // FIX: Verifica unicità del nuovo codice con protezione anti-loop infinito
                $baseCode = $nuovoCodice;
                $counter = 1;
                $maxIterations = 1000;
                while (ConfigurazioneCorsoSpp::where('codice_corso', $nuovoCodice)->where('id', '!=', $id)->exists()) {
                    $nuovoCodice = $baseCode . '_' . $counter;
                    $counter++;
                    
                    if ($counter > $maxIterations) {
                        throw new \RuntimeException('Impossibile generare un codice corso univoco. Contatta l\'amministratore.');
                    }
                }
                
                $validated['codice_corso'] = $nuovoCodice;
            }
            
            $corso->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Corso modificato con successo',
                    'corso' => $corso
                ]);
            }

            return redirect()->route('gestione-spp.index')
                ->with('success', 'Corso modificato con successo');

        } catch (\Exception $e) {
            Log::error('Errore modifica corso SPP', [
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
     * Elimina un corso
     */
    public function destroy($id)
    {
        try {
            $corso = ConfigurazioneCorsoSpp::findOrFail($id);
            
            // Le scadenze associate verranno eliminate automaticamente grazie a onDelete('cascade')
            $corso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Corso eliminato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore eliminazione corso SPP', [
                'user_id' => auth()->id(),
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Potrebbe essere in uso.'
            ], 500);
        }
    }
}
