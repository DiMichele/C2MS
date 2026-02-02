<?php

namespace App\Scopes;

use App\Models\OrganizationalUnit;
use App\Models\UnitAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Global Scope per filtrare i modelli in base alle unità organizzative visibili all'utente.
 * 
 * Questo scope sostituisce gradualmente CompagniaScope, supportando la nuova
 * struttura gerarchica delle unità organizzative.
 * 
 * Funzionamento:
 * 1. Admin globali: vedono tutto (nessun filtro)
 * 2. Utenti normali: vedono solo i record associati alle unità a cui hanno accesso
 * 3. Il filtro considera l'ereditarietà (accesso a un'unità = accesso ai discendenti)
 * 4. Per i Militari: include anche quelli "acquired" tramite attività (cross-unità)
 * 
 * VISIBILITÀ CROSS-UNITÀ (per Militari):
 * Un utente può vedere un militare di un'altra unità se:
 * - Il militare è stato assegnato a un'attività di un'unità visibile all'utente
 * - Questo permette collaborazione inter-unità senza compromettere la segregazione
 */
class OrganizationalUnitScope implements Scope
{
    /**
     * Cache statica per le unità visibili (per richiesta).
     */
    private static array $unitCache = [];
    
    /**
     * Cache statica per le compagnie legacy (per richiesta).
     */
    private static array $legacyCache = [];

    /**
     * Applica lo scope al query builder.
     * 
     * NOTA IMPORTANTE: Rispetta l'unità attiva anche per gli admin.
     * Quando un utente (anche admin) seleziona un'unità dal dropdown,
     * vede solo i dati di quell'unità e dei suoi discendenti.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Se non c'è utente autenticato, blocca tutto
        if (!$user) {
            $builder->whereRaw('1 = 0');
            return;
        }

        // PRIORITÀ: Rispetta l'unità attiva selezionata (anche per admin)
        $activeUnitId = activeUnitId();
        
        if ($activeUnitId) {
            // Filtra per unità attiva + suoi discendenti
            $unitIdsToShow = $this->getUnitWithDescendants($activeUnitId);
            $this->applyFilter($builder, $model, $unitIdsToShow);
            return;
        }

        // FALLBACK: Se non c'è unità attiva
        // Admin globali senza unità attiva vedono tutto
        if ($this->isGlobalAdmin($user)) {
            return;
        }

        // Utenti normali senza unità attiva: usa le unità visibili
        $visibleUnitIds = $this->getVisibleUnitIds($user);

        // Se non ha unità visibili, usa fallback legacy
        if (empty($visibleUnitIds)) {
            // Prova con le compagnie legacy
            $legacyCompagniaIds = $this->getLegacyCompagniaIds($user);
            if (empty($legacyCompagniaIds)) {
                $builder->whereRaw('1 = 0');
                return;
            }
            
            // Applica filtro legacy
            $this->applyLegacyFilter($builder, $model, $legacyCompagniaIds);
            return;
        }

        // Applica il filtro in base al tipo di modello
        $this->applyFilter($builder, $model, $visibleUnitIds);
    }
    
    /**
     * Ottiene l'ID dell'unità attiva + tutti i suoi discendenti.
     * Usa la closure table per efficienza.
     */
    protected function getUnitWithDescendants(int $unitId): array
    {
        $cacheKey = 'unit_descendants_' . $unitId;
        
        if (isset(self::$unitCache[$cacheKey])) {
            return self::$unitCache[$cacheKey];
        }
        
        try {
            // Usa la closure table per ottenere tutti i discendenti
            $descendantIds = DB::table('unit_closure')
                ->where('ancestor_id', $unitId)
                ->pluck('descendant_id')
                ->toArray();
            
            // Assicurati che l'unità stessa sia inclusa
            $descendantIds = array_unique(array_merge([$unitId], $descendantIds));
            
            self::$unitCache[$cacheKey] = $descendantIds;
            return $descendantIds;
        } catch (\Exception $e) {
            // Fallback: solo l'unità stessa
            return [$unitId];
        }
    }

    /**
     * Verifica se l'utente è un admin globale.
     */
    protected function isGlobalAdmin($user): bool
    {
        // Verifica ruoli admin
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
                return true;
            }
        }

        // Verifica metodo hasGlobalVisibility se esiste
        if (method_exists($user, 'hasGlobalVisibility') && $user->hasGlobalVisibility()) {
            return true;
        }

        return false;
    }

    /**
     * Ottiene gli ID delle unità visibili all'utente.
     * Include le unità direttamente assegnate e tutti i loro discendenti.
     * 
     * Usa caching per evitare query ripetute nella stessa richiesta.
     */
    protected function getVisibleUnitIds($user): array
    {
        $cacheKey = 'unit_' . $user->id;

        if (isset(self::$unitCache[$cacheKey])) {
            return self::$unitCache[$cacheKey];
        }

        $directUnitIds = [];

        // 1. Unità assegnate direttamente all'utente
        try {
            $directUnitIds = UnitAssignment::where('assignable_type', get_class($user))
                ->where('assignable_id', $user->id)
                ->active()
                ->pluck('unit_id')
                ->toArray();
        } catch (\Exception $e) {
            // Tabella potrebbe non esistere durante migrazione
        }

        // 2. Unità associate ai ruoli dell'utente (via unit_role_permissions)
        $roleUnitIds = [];
        try {
            $roleUnitIds = DB::table('unit_role_permissions')
                ->join('role_user', 'unit_role_permissions.role_id', '=', 'role_user.role_id')
                ->where('role_user.user_id', $user->id)
                ->whereNotNull('unit_role_permissions.unit_id')
                ->where('unit_role_permissions.access_type', 'grant')
                ->distinct()
                ->pluck('unit_role_permissions.unit_id')
                ->toArray();
        } catch (\Exception $e) {
            // Tabella potrebbe non esistere durante migrazione
        }

        // 3. Se l'utente ha una compagnia legacy, trova l'unità corrispondente
        if (!empty($user->compagnia_id)) {
            try {
                $legacyUnitIds = OrganizationalUnit::where('legacy_compagnia_id', $user->compagnia_id)
                    ->pluck('id')
                    ->toArray();
                $directUnitIds = array_merge($directUnitIds, $legacyUnitIds);
            } catch (\Exception $e) {
                // Tabella potrebbe non esistere
            }
        }

        // Combina tutte le unità dirette
        $baseUnitIds = array_unique(array_merge($directUnitIds, $roleUnitIds));

        if (empty($baseUnitIds)) {
            self::$unitCache[$cacheKey] = [];
            return [];
        }

        // 4. Espandi per includere tutti i discendenti (via closure table)
        try {
            $visibleIds = DB::table('unit_closure')
                ->whereIn('ancestor_id', $baseUnitIds)
                ->pluck('descendant_id')
                ->toArray();

            $visibleIds = array_unique($visibleIds);
        } catch (\Exception $e) {
            $visibleIds = $baseUnitIds;
        }

        self::$unitCache[$cacheKey] = $visibleIds;
        return $visibleIds;
    }

    /**
     * Ottiene gli ID delle compagnie legacy visibili (fallback).
     */
    protected function getLegacyCompagniaIds($user): array
    {
        $cacheKey = 'legacy_' . $user->id;

        if (isset(self::$legacyCache[$cacheKey])) {
            return self::$legacyCache[$cacheKey];
        }

        // Usa il metodo esistente se disponibile
        if (method_exists($user, 'getVisibleCompagnieIds')) {
            $ids = $user->getVisibleCompagnieIds();
            self::$legacyCache[$cacheKey] = $ids;
            return $ids;
        }

        // Fallback alla compagnia dell'utente
        $ids = $user->compagnia_id ? [$user->compagnia_id] : [];
        self::$legacyCache[$cacheKey] = $ids;
        return $ids;
    }

    /**
     * Applica il filtro appropriato in base al modello.
     */
    protected function applyFilter(Builder $builder, Model $model, array $visibleUnitIds): void
    {
        $table = $model->getTable();

        // Gestione speciale per il modello Militare (include acquired)
        if ($table === 'militari') {
            $this->applyMilitareFilter($builder, $visibleUnitIds);
            return;
        }

        // Verifica se il modello ha una colonna organizational_unit_id
        if ($this->hasColumn($model, 'organizational_unit_id')) {
            // Segregazione stretta: ogni macro-entità vede SOLO i propri dati.
            // I record con organizational_unit_id null non sono mostrati (devono essere assegnati a un'unità).
            $builder->whereIn("{$table}.organizational_unit_id", $visibleUnitIds);
            return;
        }

        // Verifica se il modello è OrganizationalUnit stesso
        if ($model instanceof OrganizationalUnit) {
            $builder->whereIn("{$table}.id", $visibleUnitIds);
            return;
        }

        // Verifica se il modello ha una relazione con unit_assignments
        if ($this->hasAssignmentsRelation($model)) {
            $builder->whereHas('organizationalUnits', function ($q) use ($visibleUnitIds) {
                $q->whereIn('organizational_units.id', $visibleUnitIds);
            });
            return;
        }

        // Fallback: se il modello ha compagnia_id, usa la logica legacy
        $this->applyLegacyFallback($builder, $model, $visibleUnitIds);
    }

    /**
     * Applica il filtro per il modello Militare.
     * Include sia i militari delle unità visibili che quelli "acquired" tramite attività.
     * 
     * PERFORMANCE: Usa EXISTS invece di IN per migliori prestazioni.
     */
    protected function applyMilitareFilter(Builder $builder, array $visibleUnitIds): void
    {
        // Ottieni anche le compagnie legacy mappate
        $legacyCompagniaIds = $this->getLegacyCompagniaIdsFromUnits($visibleUnitIds);

        $builder->where(function ($query) use ($visibleUnitIds, $legacyCompagniaIds) {
            // 1. Militari delle unità organizzative visibili (nuova gerarchia)
            $query->whereIn('militari.organizational_unit_id', $visibleUnitIds);
            
            // 2. Militari delle compagnie legacy visibili (retrocompatibilità)
            if (!empty($legacyCompagniaIds)) {
                $query->orWhereIn('militari.compagnia_id', $legacyCompagniaIds);
            }
            
            // 3. Militari acquired tramite partecipazione ad attività
            // delle unità visibili (nuova gerarchia)
            $query->orWhereExists(function ($subquery) use ($visibleUnitIds) {
                $subquery->select(DB::raw(1))
                    ->from('activity_militare as am')
                    ->join('board_activities as ba', 'am.activity_id', '=', 'ba.id')
                    ->whereColumn('am.militare_id', 'militari.id')
                    ->whereIn('ba.organizational_unit_id', $visibleUnitIds);
            });
            
            // 4. Militari acquired tramite attività delle compagnie legacy
            if (!empty($legacyCompagniaIds)) {
                $query->orWhereExists(function ($subquery) use ($legacyCompagniaIds) {
                    $subquery->select(DB::raw(1))
                        ->from('activity_militare as am')
                        ->join('board_activities as ba', 'am.activity_id', '=', 'ba.id')
                        ->whereColumn('am.militare_id', 'militari.id')
                        ->whereIn('ba.compagnia_id', $legacyCompagniaIds);
                });
            }
        });
    }

    /**
     * Applica filtro legacy (basato su compagnia_id).
     */
    protected function applyLegacyFilter(Builder $builder, Model $model, array $compagnieIds): void
    {
        $table = $model->getTable();
        
        // Gestione speciale per Militare
        if ($table === 'militari') {
            $builder->where(function ($query) use ($compagnieIds) {
                $query->whereIn('militari.compagnia_id', $compagnieIds)
                    ->orWhereExists(function ($subquery) use ($compagnieIds) {
                        $subquery->select(DB::raw(1))
                            ->from('activity_militare as am')
                            ->join('board_activities as ba', 'am.activity_id', '=', 'ba.id')
                            ->whereColumn('am.militare_id', 'militari.id')
                            ->whereIn('ba.compagnia_id', $compagnieIds);
                    });
            });
            return;
        }
        
        // Filtro standard
        if ($this->hasColumn($model, 'compagnia_id')) {
            $builder->whereIn("{$table}.compagnia_id", $compagnieIds);
        }
    }

    /**
     * Fallback legacy quando il modello non ha organizational_unit_id ma ha compagnia_id.
     */
    protected function applyLegacyFallback(Builder $builder, Model $model, array $visibleUnitIds): void
    {
        $table = $model->getTable();
        
        if (!$this->hasColumn($model, 'compagnia_id')) {
            return;
        }

        $legacyCompagniaIds = $this->getLegacyCompagniaIdsFromUnits($visibleUnitIds);

        if (!empty($legacyCompagniaIds)) {
            $builder->whereIn("{$table}.compagnia_id", $legacyCompagniaIds);
        } else {
            // Nessuna mappatura legacy, blocca
            $builder->whereRaw('1 = 0');
        }
    }

    /**
     * Ottiene gli ID delle compagnie legacy dalle unità visibili.
     */
    protected function getLegacyCompagniaIdsFromUnits(array $unitIds): array
    {
        if (empty($unitIds)) {
            return [];
        }

        try {
            return OrganizationalUnit::whereIn('id', $unitIds)
                ->whereNotNull('legacy_compagnia_id')
                ->pluck('legacy_compagnia_id')
                ->unique()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Verifica se il modello ha una determinata colonna.
     */
    protected function hasColumn(Model $model, string $column): bool
    {
        return in_array($column, $model->getFillable());
    }

    /**
     * Verifica se il modello ha una relazione con le unità organizzative.
     */
    protected function hasAssignmentsRelation(Model $model): bool
    {
        return method_exists($model, 'organizationalUnits');
    }

    /**
     * Pulisce la cache statica (utile per testing).
     */
    public static function clearCache(): void
    {
        self::$unitCache = [];
        self::$legacyCache = [];
    }
}
