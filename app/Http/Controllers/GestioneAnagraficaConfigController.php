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
        
        // Se tutti gli ordini sono multipli di 10, normalizza a 1,2,3...
        if ($campi->count() > 0 && $campi->pluck('ordine')->every(fn ($o) => $o % 10 === 0)) {
            $posizione = 1;
            foreach ($campi as $campo) {
                $campo->update(['ordine' => $posizione++]);
            }
            $campi = ConfigurazioneCampoAnagrafica::ordinati()->get();
        }
        
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
            Log::error('Errore creazione plotone', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
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
            Log::error('Errore aggiornamento plotone', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyPlotone($id)
    {
        try {
            $plotone = Plotone::findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($plotone->militari()->count() > 0) {
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
            Log::error('Errore eliminazione plotone', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
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
            Log::error('Errore creazione ufficio', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
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
            Log::error('Errore aggiornamento ufficio', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyUfficio($id)
    {
        try {
            $ufficio = Polo::findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($ufficio->militari()->count() > 0) {
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
            Log::error('Errore eliminazione ufficio', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
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
            Log::error('Errore creazione incarico', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione: ' . $e->getMessage()
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
            Log::error('Errore aggiornamento incarico', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroyIncarico($id)
    {
        try {
            $incarico = Mansione::findOrFail($id);
            
            // Verifica se ci sono militari associati
            if ($incarico->militari()->count() > 0) {
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
            Log::error('Errore eliminazione incarico', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }
}
