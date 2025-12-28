<?php

namespace App\Http\Controllers;

use App\Models\TipoPoligono;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller per la gestione della configurazione dei tipi di poligono
 * 
 * Gestisce:
 * - Visualizzazione tipi di poligono
 * - Creazione nuovi tipi
 * - Modifica tipi esistenti (nome, descrizione, durata)
 * - Eliminazione tipi di poligono
 */
class GestionePoligoniController extends Controller
{
    /**
     * Visualizza la pagina di gestione poligoni
     */
    public function index(Request $request)
    {
        // Recupera tutti i tipi di poligono ordinati
        $query = TipoPoligono::query();

        // Filtra per stato attivo se richiesto
        if ($request->filled('attivo')) {
            $attivo = $request->attivo === '1';
            $query->where('attivo', $attivo);
        }

        $poligoni = $query->ordinati()->get();

        return view('gestione-poligoni.index', compact('poligoni'));
    }

    /**
     * Crea un nuovo tipo di poligono
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:tipi_poligono,nome',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
            'punteggio_minimo' => 'nullable|integer|min:0',
            'punteggio_massimo' => 'nullable|integer|min:0',
        ]);

        try {
            // Genera codice univoco
            $codice = \Str::slug($validated['nome'], '_');
            
            // Verifica unicità del codice
            $baseCode = $codice;
            $counter = 1;
            while (TipoPoligono::where('codice', $codice)->exists()) {
                $codice = $baseCode . '_' . $counter;
                $counter++;
            }

            // Determina ordine
            $maxOrdine = TipoPoligono::max('ordine') ?? 0;
            
            $poligono = TipoPoligono::create([
                'codice' => $codice,
                'nome' => $validated['nome'],
                'descrizione' => $validated['descrizione'] ?? null,
                'durata_mesi' => $validated['durata_mesi'],
                'punteggio_minimo' => $validated['punteggio_minimo'] ?? 0,
                'punteggio_massimo' => $validated['punteggio_massimo'] ?? 100,
                'attivo' => true,
                'ordine' => $maxOrdine + 1,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di poligono creato con successo',
                    'poligono' => $poligono
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', 'Tipo di poligono creato con successo');

        } catch (\Exception $e) {
            Log::error('Errore creazione tipo poligono', [
                'error' => $e->getMessage(),
                'data' => $validated
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la creazione: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la creazione'])
                ->withInput();
        }
    }

    /**
     * Aggiorna un tipo di poligono (solo durata)
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'durata_mesi' => 'required|integer|min:0'
        ]);

        try {
            $poligono = TipoPoligono::findOrFail($id);
            $poligono->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tipo di poligono aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Modifica completa un tipo di poligono
     */
    public function edit(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'durata_mesi' => 'required|integer|min:0',
            'punteggio_minimo' => 'nullable|integer|min:0',
            'punteggio_massimo' => 'nullable|integer|min:0',
        ]);

        try {
            $poligono = TipoPoligono::findOrFail($id);
            
            // Aggiorna il codice se il nome cambia
            if ($poligono->nome !== $validated['nome']) {
                $nuovoCodice = \Str::slug($validated['nome'], '_');
                
                // Verifica unicità del nuovo codice (escludendo il tipo corrente)
                $baseCode = $nuovoCodice;
                $counter = 1;
                while (TipoPoligono::where('codice', $nuovoCodice)->where('id', '!=', $id)->exists()) {
                    $nuovoCodice = $baseCode . '_' . $counter;
                    $counter++;
                }
                
                $validated['codice'] = $nuovoCodice;
            }
            
            $poligono->update($validated);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tipo di poligono modificato con successo',
                    'poligono' => $poligono
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', 'Tipo di poligono modificato con successo');

        } catch (\Exception $e) {
            Log::error('Errore modifica tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante la modifica: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante la modifica'])
                ->withInput();
        }
    }

    /**
     * Elimina un tipo di poligono
     */
    public function destroy(Request $request, $id)
    {
        try {
            $poligono = TipoPoligono::findOrFail($id);
            
            // Verifica se ci sono scadenze associate
            $countScadenze = $poligono->scadenzePoligoni()->count();
            
            if ($countScadenze > 0) {
                // Disattiva invece di eliminare se ci sono dati associati
                $poligono->update(['attivo' => false]);
                $message = 'Tipo di poligono disattivato (ci sono ' . $countScadenze . ' scadenze associate)';
            } else {
                $poligono->delete();
                $message = 'Tipo di poligono eliminato con successo';
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('gestione-poligoni.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Errore eliminazione tipo poligono', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withErrors(['error' => 'Errore durante l\'eliminazione']);
        }
    }
}

