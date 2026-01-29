<?php

namespace App\Http\Controllers;

use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Mansione;
use App\Models\Compagnia;
use App\Models\ConfigurazioneCampoAnagrafica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GestioneAnagraficaConfigController extends Controller
{
    /**
     * Mostra la pagina di gestione configurazione anagrafica
     */
    public function index(Request $request)
    {
        // Recupera tutti i plotoni con le loro compagnie
        $plotoni = Plotone::with('compagnia')->orderBy('nome')->get();
        
        // Recupera tutti gli uffici (Poli)
        $uffici = Polo::orderBy('nome')->get();
        
        // Recupera tutti gli incarichi (Mansioni)
        $incarichi = Mansione::orderBy('nome')->get();
        
        // Recupera le compagnie per il form di creazione plotoni
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Carica tutti i campi (sistema + custom) ordinati per il tab Campi
        $campi = ConfigurazioneCampoAnagrafica::ordinati()->get();
        
        // RIMOSSO: Normalizzazione ordine automatica ad ogni accesso
        // La normalizzazione era problematica perché modificava il database ad ogni page load.
        
        return view('gestione-anagrafica-config.index', compact('plotoni', 'uffici', 'incarichi', 'compagnie', 'campi'));
    }

    // ==================== PLOTONI ====================
    
    public function storePlotone(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:plotoni,nome',
            'compagnia_id' => 'required|exists:compagnie,id'
        ]);

        try {
            $plotone = Plotone::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Plotone creato con successo',
                'plotone' => $plotone->load('compagnia')
            ]);
        } catch (\Exception $e) {
            Log::error('Errore creazione plotone', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la creazione. Riprova.'
            ], 500);
        }
    }

    public function updatePlotone(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:plotoni,nome,' . $id,
            'compagnia_id' => 'required|exists:compagnie,id'
        ]);

        try {
            $plotone = Plotone::findOrFail($id);
            $plotone->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Plotone aggiornato con successo',
                'plotone' => $plotone->load('compagnia')
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento plotone', [
                'user_id' => auth()->id(),
                'plotone_id' => $id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.'
            ], 500);
        }
    }

    public function destroyPlotone($id)
    {
        try {
            // FIX N+1: Usa withCount per ottimizzare la query
            $plotone = Plotone::withCount('militari')->findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($plotone->militari_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile eliminare: ci sono militari associati a questo plotone'
                ], 400);
            }
            
            $plotone->delete();

            return response()->json([
                'success' => true,
                'message' => 'Plotone eliminato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione plotone', [
                'user_id' => auth()->id(),
                'plotone_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Riprova.'
            ], 500);
        }
    }

    // ==================== UFFICI (Poli) ====================
    
    public function storeUfficio(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:poli,nome'
        ]);

        try {
            $ufficio = Polo::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ufficio creato con successo',
                'ufficio' => $ufficio
            ]);
        } catch (\Exception $e) {
            Log::error('Errore creazione ufficio', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la creazione. Riprova.'
            ], 500);
        }
    }

    public function updateUfficio(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:poli,nome,' . $id
        ]);

        try {
            $ufficio = Polo::findOrFail($id);
            $ufficio->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Ufficio aggiornato con successo',
                'ufficio' => $ufficio
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ufficio', [
                'user_id' => auth()->id(),
                'ufficio_id' => $id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.'
            ], 500);
        }
    }

    public function destroyUfficio($id)
    {
        try {
            // FIX N+1: Usa withCount per ottimizzare la query
            $ufficio = Polo::withCount('militari')->findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($ufficio->militari_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile eliminare: ci sono militari associati a questo ufficio'
                ], 400);
            }
            
            $ufficio->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ufficio eliminato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione ufficio', [
                'user_id' => auth()->id(),
                'ufficio_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Riprova.'
            ], 500);
        }
    }

    // ==================== INCARICHI (Mansioni) ====================
    
    public function storeIncarico(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:mansioni,nome'
        ]);

        try {
            $incarico = Mansione::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Incarico creato con successo',
                'incarico' => $incarico
            ]);
        } catch (\Exception $e) {
            Log::error('Errore creazione incarico', [
                'user_id' => auth()->id(),
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante la creazione. Riprova.'
            ], 500);
        }
    }

    public function updateIncarico(Request $request, $id)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255|unique:mansioni,nome,' . $id
        ]);

        try {
            $incarico = Mansione::findOrFail($id);
            $incarico->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Incarico aggiornato con successo',
                'incarico' => $incarico
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento incarico', [
                'user_id' => auth()->id(),
                'incarico_id' => $id,
                'input' => $request->except(['_token']),
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento. Riprova.'
            ], 500);
        }
    }

    public function destroyIncarico($id)
    {
        try {
            // FIX N+1: Usa withCount per ottimizzare la query
            $incarico = Mansione::withCount('militari')->findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($incarico->militari_count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile eliminare: ci sono militari associati a questo incarico'
                ], 400);
            }
            
            $incarico->delete();

            return response()->json([
                'success' => true,
                'message' => 'Incarico eliminato con successo'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione incarico', [
                'user_id' => auth()->id(),
                'incarico_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'eliminazione. Riprova.'
            ], 500);
        }
    }
}
