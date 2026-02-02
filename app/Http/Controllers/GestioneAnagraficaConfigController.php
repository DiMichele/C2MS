<?php

namespace App\Http\Controllers;

use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Mansione;
use App\Models\Compagnia;
use App\Models\ConfigurazioneCampoAnagrafica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class GestioneAnagraficaConfigController extends Controller
{
    /**
     * Mostra la pagina di gestione configurazione anagrafica
     * 
     * I dati sono automaticamente filtrati per unità organizzativa grazie al
     * trait BelongsToOrganizationalUnit sui modelli Plotone, Polo e Mansione.
     */
    public function index(Request $request)
    {
        // Lo scope OrganizationalUnitScope filtra automaticamente per unità attiva
        $plotoni = Plotone::with('compagnia')->orderBy('nome')->get();
        
        // Filtrati automaticamente per unità attiva
        $uffici = Polo::orderBy('nome')->get();
        
        // Filtrati automaticamente per unità attiva
        $incarichi = Mansione::orderBy('nome')->get();
        
        // Recupera le compagnie per il form di creazione plotoni
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Carica i campi dell'unità organizzativa attiva (configurazione per unità)
        $unitId = activeUnitId();
        ConfigurazioneCampoAnagrafica::ensureSystemFieldsForUnit($unitId);
        $campi = ConfigurazioneCampoAnagrafica::forUnit($unitId)->ordinati()->get();
        
        // RIMOSSO: Normalizzazione ordine automatica ad ogni accesso
        // La normalizzazione era problematica perché modificava il database ad ogni page load.
        
        return view('gestione-anagrafica-config.index', compact('plotoni', 'uffici', 'incarichi', 'compagnie', 'campi'));
    }

    // ==================== PLOTONI ====================
    
    public function storePlotone(Request $request)
    {
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plotoni', 'nome')->where('organizational_unit_id', $unitId),
            ],
            'compagnia_id' => 'required|exists:compagnie,id'
        ]);

        try {
            // organizational_unit_id viene assegnato automaticamente dal trait
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
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('plotoni', 'nome')
                    ->where('organizational_unit_id', $unitId)
                    ->ignore($id),
            ],
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
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('poli', 'nome')->where('organizational_unit_id', $unitId),
            ],
        ]);

        try {
            // organizational_unit_id viene assegnato automaticamente dal trait
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
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('poli', 'nome')
                    ->where('organizational_unit_id', $unitId)
                    ->ignore($id),
            ],
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
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mansioni', 'nome')->where('organizational_unit_id', $unitId),
            ],
        ]);

        try {
            // organizational_unit_id viene assegnato automaticamente dal trait
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
        $unitId = activeUnitId();
        
        $validated = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mansioni', 'nome')
                    ->where('organizational_unit_id', $unitId)
                    ->ignore($id),
            ],
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

    /**
     * Aggiorna l'ordine degli incarichi dopo drag & drop
     */
    public function updateOrderIncarichi(Request $request)
    {
        $validated = $request->validate([
            'ordini' => 'required|array',
            'ordini.*' => 'required|integer|exists:mansioni,id'
        ]);

        try {
            foreach ($validated['ordini'] as $ordine => $id) {
                Mansione::where('id', $id)->update(['ordine' => $ordine]);
            }

            return response()->json(['success' => true, 'message' => 'Ordine aggiornato con successo']);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento ordine incarichi', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'aggiornamento dell\'ordine.'
            ], 500);
        }
    }
}
