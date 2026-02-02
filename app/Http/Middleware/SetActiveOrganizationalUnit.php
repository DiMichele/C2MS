<?php

namespace App\Http\Middleware;

use App\Models\OrganizationalUnit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware per gestire l'unità organizzativa attiva dell'utente.
 * 
 * Questo middleware:
 * - Legge l'unità attiva dalla sessione
 * - Se non impostata, usa l'unità primaria dell'utente
 * - Verifica che l'utente abbia accesso all'unità
 * - Imposta l'unità nel container Laravel per accesso globale
 * - Nasconde il selettore nelle pagine globali (admin, configurazioni)
 */
class SetActiveOrganizationalUnit
{
    /**
     * Route prefixes considerate "globali" dove il selettore unità NON deve apparire.
     * In queste pagine l'utente vede tutti i dati a cui ha accesso, non filtrati per unità.
     */
    protected array $globalRoutes = [
        // Admin
        'admin.audit-logs',
        'admin.users',
        'admin.permissions',
        'admin.roles',
        'admin.create',
        'admin.edit',
        'admin.store',
        'admin.update',
        'admin.destroy',
        'admin.reset-password',
        // Gerarchia organizzativa
        'gerarchia.',
        // Pagine di configurazione/gestione (mostrano tutti i dati dell'unità selezionata ma il selettore non è necessario)
        'gestione-poligoni.',
        'gestione-idoneita.',
        'gestione-spp.',
        // PEFO (mostra tutti i militari accessibili, filtrati per permessi)
        'pefo.',
        // Organigramma (vista read-only della gerarchia)
        'organigramma.',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Se non autenticato, passa al prossimo middleware
        if (!$user) {
            return $next($request);
        }

        // Leggi l'unità attiva dalla sessione
        $activeUnitId = session('active_unit_id');

        // Se non impostata, usa l'unità dell'utente
        if (!$activeUnitId) {
            $activeUnitId = $this->getDefaultUnitId($user);
            
            if ($activeUnitId) {
                session(['active_unit_id' => $activeUnitId]);
            }
        }

        // Verifica accesso all'unità
        if ($activeUnitId && !$user->canAccessUnit($activeUnitId)) {
            // L'utente non ha più accesso a questa unità, reset a unità valida
            $activeUnitId = $this->getDefaultUnitId($user);
            session(['active_unit_id' => $activeUnitId]);
            
            Log::info('Reset unità attiva per utente - accesso negato', [
                'user_id' => $user->id,
                'new_active_unit_id' => $activeUnitId,
            ]);
        }

        // Carica l'unità e impostala nel container
        $activeUnit = null;
        if ($activeUnitId) {
            $activeUnit = OrganizationalUnit::with('type')->find($activeUnitId);
        }

        // Registra l'istanza nel container per accesso globale
        app()->instance('active_unit', $activeUnit);
        app()->instance('active_unit_id', $activeUnitId);

        // Aggiungi le unità accessibili per la view con logica intelligente
        $unitSelectorData = $this->prepareUnitSelectorData($user);
        view()->share('accessibleUnits', $unitSelectorData['units']);
        view()->share('activeUnit', $activeUnit);
        
        // Nasconde il selettore per le pagine globali (admin, configurazioni)
        $isGlobalPage = $this->isGlobalRoute($request);
        view()->share('showUnitSelector', $isGlobalPage ? false : $unitSelectorData['showSelector']);
        view()->share('unitSelectorMode', $isGlobalPage ? 'hidden' : $unitSelectorData['mode']);
        view()->share('isGlobalPage', $isGlobalPage);

        return $next($request);
    }

    /**
     * Ottiene l'ID dell'unità di default per l'utente.
     */
    protected function getDefaultUnitId($user): ?int
    {
        // Prima prova l'unità diretta dell'utente
        if ($user->organizational_unit_id) {
            return $user->organizational_unit_id;
        }

        // Poi prova l'unità primaria dalle assegnazioni
        $primaryUnit = $user->getPrimaryUnit();
        if ($primaryUnit) {
            return $primaryUnit->id;
        }

        // Infine, prendi la prima unità accessibile
        $visibleUnitIds = $user->getVisibleUnitIds();
        if (!empty($visibleUnitIds)) {
            return $visibleUnitIds[0];
        }

        // Fallback: prova a mappare dalla compagnia legacy
        if ($user->compagnia_id) {
            $legacyUnit = OrganizationalUnit::where('legacy_compagnia_id', $user->compagnia_id)->first();
            if ($legacyUnit) {
                return $legacyUnit->id;
            }
        }

        return null;
    }

    /**
     * Prepara i dati per il selettore unità con logica intelligente.
     * 
     * LOGICA:
     * 1. Se l'utente ha accesso a unità di macro-entità DIVERSE (es. Battaglione Leonessa + Battaglione Tonale)
     *    → Mostra battaglioni con compagnie indentate sotto ('hierarchical')
     * 2. Se l'utente ha accesso a PIÙ compagnie della STESSA macro-entità
     *    → Mostra solo le compagnie senza il battaglione ('flat')
     * 3. Se l'utente ha accesso a UNA SOLA compagnia
     *    → Selettore NASCOSTO completamente ('hidden')
     */
    protected function prepareUnitSelectorData($user): array
    {
        $visibleUnitIds = $user->getVisibleUnitIds();
        
        if (empty($visibleUnitIds)) {
            return ['units' => collect(), 'showSelector' => false, 'mode' => 'hidden'];
        }

        // Se admin, mostra struttura completa gerarchica
        if ($user->isGlobalAdmin()) {
            return $this->buildHierarchicalSelector();
        }

        // Trova le compagnie accessibili (depth=2 tipicamente)
        $accessibleCompanies = OrganizationalUnit::with(['type', 'parent'])
            ->active()
            ->whereIn('id', $visibleUnitIds)
            ->where('depth', 2) // Compagnie
            ->orderBy('name')
            ->get();
        
        // Trova anche le unità standalone a depth=1 che non hanno figli (es. "Comando alla Sede")
        $standaloneUnits = OrganizationalUnit::with('type')
            ->active()
            ->whereIn('id', $visibleUnitIds)
            ->where('depth', 1)
            ->whereDoesntHave('children', function ($q) {
                $q->active()->where('depth', 2);
            })
            ->orderBy('name')
            ->get();
        
        // Conta totale unità accessibili
        $totalAccessible = $accessibleCompanies->count() + $standaloneUnits->count();
        
        // Se solo 1 unità → nascosto
        if ($totalAccessible <= 1) {
            $allUnits = $accessibleCompanies->merge($standaloneUnits);
            return [
                'units' => $allUnits,
                'showSelector' => false,
                'mode' => 'hidden'
            ];
        }
        
        // Verifica se le compagnie appartengono a macro-entità diverse
        $parentIds = $accessibleCompanies->pluck('parent_id')->unique();
        
        // Se ci sono standalone units O multiple macro-entità → struttura gerarchica
        if ($standaloneUnits->count() > 0 || $parentIds->count() > 1) {
            return $this->buildHierarchicalSelectorForUser($accessibleCompanies, $parentIds, $standaloneUnits);
        } else {
            // STESSA macro-entità, nessuna standalone → mostra solo le compagnie
            return [
                'units' => $accessibleCompanies,
                'showSelector' => true,
                'mode' => 'flat'
            ];
        }
    }
    
    /**
     * Costruisce selettore gerarchico per admin (mostra tutto).
     */
    protected function buildHierarchicalSelector(): array
    {
        $units = collect();
        
        // Prendi tutte le unità a depth=1 (battaglioni, sezioni standalone, etc.)
        $topLevelUnits = OrganizationalUnit::with('type')
            ->active()
            ->where('depth', 1)
            ->orderBy('name')
            ->get();
        
        foreach ($topLevelUnits as $topUnit) {
            // Verifica se ha figli a depth=2
            $compagnie = OrganizationalUnit::with('type')
                ->active()
                ->where('parent_id', $topUnit->id)
                ->where('depth', 2)
                ->orderBy('name')
                ->get();
            
            $hasChildren = $compagnie->count() > 0;
            
            // Aggiungi l'unità top-level
            $topUnit->is_header = true;
            // Se non ha figli (es. "Comando alla Sede"), è cliccabile direttamente
            $topUnit->is_clickable_header = !$hasChildren;
            $topUnit->indent_level = 0;
            $units->push($topUnit);
            
            // Aggiungi le compagnie sotto l'unità (se esistono)
            foreach ($compagnie as $compagnia) {
                $compagnia->is_header = false;
                $compagnia->is_clickable_header = false;
                $compagnia->indent_level = 1;
                $units->push($compagnia);
            }
        }
        
        return [
            'units' => $units,
            'showSelector' => $units->count() > 1,
            'mode' => 'hierarchical'
        ];
    }
    
    /**
     * Costruisce selettore gerarchico per utente con accesso a più macro-entità.
     * 
     * @param \Illuminate\Support\Collection $accessibleCompanies Compagnie accessibili (depth=2)
     * @param \Illuminate\Support\Collection $parentIds ID delle macro-entità parent
     * @param \Illuminate\Support\Collection|null $standaloneUnits Unità standalone senza figli (es. "Comando alla Sede")
     */
    protected function buildHierarchicalSelectorForUser($accessibleCompanies, $parentIds, $standaloneUnits = null): array
    {
        $units = collect();
        $standaloneUnits = $standaloneUnits ?? collect();
        
        // Prendi i battaglioni parent (che hanno compagnie sotto)
        $battaglioni = OrganizationalUnit::with('type')
            ->active()
            ->whereIn('id', $parentIds)
            ->orderBy('name')
            ->get();
        
        foreach ($battaglioni as $battaglione) {
            // Controlla se questo battaglione ha compagnie accessibili sotto di sé
            $compagnieBattaglione = $accessibleCompanies->where('parent_id', $battaglione->id);
            $hasAccessibleSubordinates = $compagnieBattaglione->count() > 0;
            
            // Aggiungi il battaglione come header
            // Se non ha subordinate accessibili, deve essere cliccabile
            $battaglione->is_header = true;
            $battaglione->is_clickable_header = !$hasAccessibleSubordinates;
            $battaglione->indent_level = 0;
            $units->push($battaglione);
            
            // Aggiungi solo le compagnie accessibili sotto questo battaglione
            foreach ($compagnieBattaglione as $compagnia) {
                $compagnia->is_header = false;
                $compagnia->is_clickable_header = false;
                $compagnia->indent_level = 1;
                $units->push($compagnia);
            }
        }
        
        // Aggiungi le unità standalone (es. "Comando alla Sede") - sono SEMPRE cliccabili
        foreach ($standaloneUnits as $standalone) {
            // Le unità standalone vengono mostrate come header cliccabili
            $standalone->is_header = true;
            $standalone->is_clickable_header = true;
            $standalone->indent_level = 0;
            $units->push($standalone);
        }
        
        // Ordina per nome per avere un ordine alfabetico coerente
        $units = $units->sortBy('name')->values();
        
        return [
            'units' => $units,
            'showSelector' => true,
            'mode' => 'hierarchical'
        ];
    }

    /**
     * Ottiene le macro unità accessibili all'utente per il dropdown header.
     * @deprecated Usare prepareUnitSelectorData() invece
     */
    protected function getAccessibleUnits($user): \Illuminate\Support\Collection
    {
        return $this->prepareUnitSelectorData($user)['units'];
    }

    /**
     * Verifica se la route corrente è una pagina globale.
     * 
     * Le pagine globali mostrano tutti i dati accessibili all'utente
     * invece di filtrare per unità organizzativa attiva.
     */
    protected function isGlobalRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();
        
        if (!$routeName) {
            return false;
        }
        
        foreach ($this->globalRoutes as $globalRoute) {
            // Supporta sia match esatto che prefisso (con punto finale)
            if ($routeName === $globalRoute || str_starts_with($routeName, $globalRoute)) {
                return true;
            }
        }
        
        return false;
    }
}
