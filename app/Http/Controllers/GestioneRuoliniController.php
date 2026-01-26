<?php

namespace App\Http\Controllers;

use App\Models\TipoServizio;
use App\Models\ConfigurazioneRuolino;
use App\Models\CompagniaSetting;
use App\Services\CompagniaSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Controller per la gestione delle configurazioni dei ruolini PER COMPAGNIA
 * 
 * Permette di configurare quali impegni CPT rendono un militare
 * presente o assente nei ruolini giornalieri.
 * 
 * OGNI COMPAGNIA può avere le proprie regole.
 * Il frontend NON passa mai la compagnia - è determinata dal backend.
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
        $compagniaId = $user->compagnia_id;

        // Admin globali possono vedere/modificare qualsiasi compagnia
        $isGlobalAdmin = $user->hasRole('admin') || $user->hasRole('amministratore');
        
        // Se admin e richiesta compagnia specifica
        if ($isGlobalAdmin && $request->filled('compagnia_id')) {
            $compagniaId = $request->compagnia_id;
        }
        
        // Se admin globale senza compagnia assegnata, seleziona la prima disponibile
        if ($isGlobalAdmin && !$compagniaId) {
            $primaCompagnia = \App\Models\Compagnia::orderBy('nome')->first();
            if ($primaCompagnia) {
                $compagniaId = $primaCompagnia->id;
            }
        }

        if (!$compagniaId) {
            return redirect()->route('dashboard')
                ->with('error', 'Devi essere assegnato a una compagnia per gestire i ruolini.');
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
        
        // Recupera le configurazioni PER LA COMPAGNIA CORRENTE
        $configurazioni = ConfigurazioneRuolino::withoutGlobalScopes()
            ->where('compagnia_id', $compagniaId)
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

        // Lista compagnie per admin
        $compagnie = $isGlobalAdmin ? \App\Models\Compagnia::orderBy('nome')->get() : collect();

        // Compagnia corrente
        $compagniaCorrente = \App\Models\Compagnia::find($compagniaId);

        return view('gestione-ruolini.index', compact(
            'tipiServizio', 
            'configurazioni', 
            'defaultStato',
            'isGlobalAdmin',
            'compagnie',
            'compagniaCorrente',
            'compagniaId'
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
