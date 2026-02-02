<?php

namespace App\Http\Controllers;

use App\Models\TipoServizio;
use App\Models\ConfigurazioneRuolino;
use App\Models\CompagniaSetting;
use App\Models\OrganizationalUnit;
use App\Services\CompagniaSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Controller per la gestione delle configurazioni dei ruolini PER UNITÀ ORGANIZZATIVA
 * 
 * Permette di configurare quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri.
 * 
 * OGNI UNITÀ ORGANIZZATIVA può avere le proprie regole.
 * Il frontend NON passa mai l'unità - è determinata dal backend.
 * 
 * MIGRAZIONE MULTI-TENANCY: Ora usa organizational_unit_id invece di compagnia_id.
 */
class GestioneRuoliniController extends Controller
{
    /**
     * Ottiene il service con contesto corretto per l'utente corrente
     */
    private function getSettingsService(): CompagniaSettingsService
    {
        return CompagniaSettingsService::forCurrentUser();
    }

    /**
     * Mostra la pagina di gestione configurazione ruolini
     */
    public function index(Request $request)
    {
        // Verifica autorizzazione
        $this->authorize('viewAny', CompagniaSetting::class);

        $user = auth()->user();
        
        // MULTI-TENANCY: Usa organizational_unit_id invece di compagnia_id
        $unitId = activeUnitId();
        $compagniaId = $user->compagnia_id; // Fallback legacy

        // Admin globali possono vedere/modificare qualsiasi unità
        $isGlobalAdmin = $user->hasRole('admin') || $user->hasRole('amministratore');
        
        // Se admin e richiesta unità specifica
        if ($isGlobalAdmin && $request->filled('unit_id')) {
            $unitId = $request->unit_id;
        }
        
        // Se admin globale senza unità attiva, seleziona la prima disponibile
        if ($isGlobalAdmin && !$unitId) {
            $primaUnit = OrganizationalUnit::active()
                ->where('depth', 1) // Macro unità
                ->orderBy('name')
                ->first();
            if ($primaUnit) {
                $unitId = $primaUnit->id;
            }
        }

        // Fallback su compagnia_id se organizational_unit_id non disponibile
        if (!$unitId && $compagniaId) {
            // Trova l'OrganizationalUnit corrispondente alla compagnia
            $unit = OrganizationalUnit::where('legacy_compagnia_id', $compagniaId)->first();
            if ($unit) {
                $unitId = $unit->id;
            }
        }

        if (!$unitId) {
            return redirect()->route('dashboard')
                ->with('error', 'Devi essere assegnato a un\'unità organizzativa per gestire i ruolini.');
        }

        // Recupera SOLO i tipi di servizio ATTIVI che esistono ANCHE in codici_servizio_gerarchia
        $tipiServizioQuery = TipoServizio::where('tipi_servizio.attivo', true)
            ->join('codici_servizio_gerarchia', function($join) {
                $join->on('tipi_servizio.codice', '=', 'codici_servizio_gerarchia.codice')
                     ->where('codici_servizio_gerarchia.attivo', true);
            })
            ->select('tipi_servizio.*')
            ->orderBy('tipi_servizio.ordine')
            ->orderBy('tipi_servizio.codice');
        
        // Applica filtri categoria
        if ($request->filled('categoria') && $request->categoria != 'tutte') {
            $tipiServizioQuery->where('tipi_servizio.categoria', $request->categoria);
        }
        
        $tipiServizio = $tipiServizioQuery->get();
        
        // Recupera le configurazioni PER L'UNITÀ CORRENTE
        $configurazioni = ConfigurazioneRuolino::withoutGlobalScopes()
            ->where(function($q) use ($unitId, $compagniaId) {
                // Prima cerca per organizational_unit_id, poi fallback su compagnia_id
                $q->where('organizational_unit_id', $unitId);
                if ($compagniaId) {
                    $q->orWhere('compagnia_id', $compagniaId);
                }
            })
            ->whereHas('tipoServizio', function($query) {
                $query->where('attivo', true);
            })
            ->get()
            ->keyBy('tipo_servizio_id');
        
        // Applica filtro stato lato server se necessario
        if ($request->filled('stato') && $request->stato != 'tutti') {
            $tipiServizio = $tipiServizio->filter(function($tipo) use ($configurazioni, $request) {
                $config = $configurazioni->get($tipo->id);
                $statoPresenza = $config ? $config->stato_presenza : 'assente';
                return $statoPresenza === $request->stato;
            });
        }

        // Ottieni impostazioni generali (default stato)
        $compagniaSetting = CompagniaSetting::where('compagnia_id', $compagniaId)->first();
        $defaultStato = $compagniaSetting?->getDefaultStato() ?? 'assente';

        // Lista unità per admin
        $units = $isGlobalAdmin ? OrganizationalUnit::active()
            ->where('depth', 1)
            ->with('type')
            ->orderBy('name')
            ->get() : collect();

        // Unità corrente
        $unitCorrente = OrganizationalUnit::find($unitId);

        // Legacy: mantieni compagnia per retrocompatibilità view
        $compagnie = $isGlobalAdmin ? \App\Models\Compagnia::orderBy('nome')->get() : collect();
        $compagniaCorrente = \App\Models\Compagnia::find($compagniaId);

        return view('gestione-ruolini.index', compact(
            'tipiServizio', 
            'configurazioni', 
            'defaultStato',
            'isGlobalAdmin',
            'compagnie',
            'compagniaCorrente',
            'compagniaId',
            'units',
            'unitCorrente',
            'unitId'
        ));
    }

    /**
     * Aggiorna la configurazione di un tipo di servizio
     */
    public function update(Request $request, $tipoServizioId)
    {
        // Verifica autorizzazione
        $this->authorize('manageRuolini', CompagniaSetting::class);

        $request->validate([
            'stato_presenza' => 'required|in:presente,assente',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            $this->getSettingsService()->updateServizioConfig(
                $tipoServizioId,
                $request->stato_presenza,
                $request->note
            );

            return response()->json([
                'success' => true,
                'message' => 'Configurazione aggiornata con successo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna multiple configurazioni in batch
     */
    public function updateBatch(Request $request)
    {
        // Verifica autorizzazione
        $this->authorize('manageRuolini', CompagniaSetting::class);

        $request->validate([
            'configurazioni' => 'required|array',
            'configurazioni.*.tipo_servizio_id' => 'required|exists:tipi_servizio,id',
            'configurazioni.*.stato_presenza' => 'required|in:presente,assente',
            'configurazioni.*.note' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $this->getSettingsService()->updateBatch($request->configurazioni);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tutte le configurazioni sono state aggiornate con successo'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna lo stato di default (presente/assente)
     */
    public function updateDefaultStato(Request $request)
    {
        // Verifica autorizzazione
        $this->authorize('manageRuolini', CompagniaSetting::class);

        $request->validate([
            'default_stato' => 'required|in:presente,assente'
        ]);

        try {
            $this->getSettingsService()->updateDefaultStato($request->default_stato);

            return response()->json([
                'success' => true,
                'message' => 'Stato di default aggiornato con successo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina una configurazione (torna alla logica default)
     */
    public function destroy($tipoServizioId)
    {
        // Verifica autorizzazione
        $this->authorize('manageRuolini', CompagniaSetting::class);

        try {
            $this->getSettingsService()->removeServizioConfig($tipoServizioId);

            return response()->json([
                'success' => true,
                'message' => 'Configurazione rimossa, verrà usata la logica di default'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Ottiene le regole ruolini per la compagnia dell'utente
     */
    public function getRules()
    {
        if (!$this->getSettingsService()->hasValidContext()) {
            return response()->json([
                'success' => false,
                'message' => 'Utente non assegnato a una compagnia'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'rules' => $this->getSettingsService()->getRuoliniRules()
        ]);
    }
}
