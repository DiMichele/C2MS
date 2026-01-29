<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SUGECO: Global Scope per Segregazione Dati per Compagnia
 * 
 * Questo scope viene applicato AUTOMATICAMENTE a tutte le query
 * sui modelli che lo utilizzano. Garantisce che:
 * - Gli utenti vedano SOLO i dati delle compagnie configurate per i loro ruoli
 * - Gli utenti vedano anche i militari "acquisiti" tramite attività (read-only)
 * - Il filtro NON sia bypassabile dal frontend
 * - Il filtro sia applicato a TUTTE le query (select, update, delete)
 * 
 * VISIBILITÀ BASATA SU RUOLI:
 * La visibilità delle compagnie è ora configurata per ruolo tramite la tabella
 * `role_compagnia_visibility`. Ogni ruolo può vedere una o più compagnie.
 * 
 * VISIBILITÀ CROSS-COMPAGNIA:
 * Un utente può vedere un militare di una compagnia diversa se:
 * - Il suo ruolo ha quella compagnia configurata nella visibilità
 * - Il militare è stato assegnato a un'attività di una compagnia visibile
 * 
 * SICUREZZA: Questo scope è il pilastro della segregazione dati.
 * Non può essere disattivato se non con `withoutGlobalScope()` che
 * richiede privilegi speciali (solo admin/sistema).
 * 
 * @package App\Scopes
 * @version 3.0
 * @author Michele Di Gennaro
 */
class CompagniaScope implements Scope
{
    /**
     * Applica lo scope alla query.
     * 
     * @param Builder $builder Query builder
     * @param Model $model Modello su cui applicare lo scope
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Se non c'è un utente autenticato, blocca tutto
        if (!Auth::check()) {
            $builder->whereRaw('1 = 0');
            return;
        }
        
        $user = Auth::user();
        
        // Gli admin/amministratori vedono tutto
        if ($this->isAdminUser($user)) {
            return;
        }
        
        // Ottieni le compagnie visibili dall'utente (basato sui ruoli)
        $compagnieIds = $this->getVisibleCompagnieIds($user);
        
        // Se l'utente non ha compagnie visibili, non può vedere nulla
        if (empty($compagnieIds)) {
            $builder->whereRaw('1 = 0');
            return;
        }
        
        // Applica filtro specifico per modello
        $this->applyCompagniaFilter($builder, $model, $compagnieIds);
    }
    
    /**
     * Ottiene gli ID delle compagnie visibili per l'utente
     * basandosi sulla tabella role_compagnia_visibility
     * 
     * @param \App\Models\User $user
     * @return array
     */
    protected function getVisibleCompagnieIds($user): array
    {
        // Usa il metodo del model User se disponibile
        if (method_exists($user, 'getVisibleCompagnieIds')) {
            return $user->getVisibleCompagnieIds();
        }
        
        // Fallback: query diretta alla tabella pivot
        $roleIds = DB::table('role_user')
            ->where('user_id', $user->id)
            ->pluck('role_id');
        
        if ($roleIds->isEmpty()) {
            // Fallback alla compagnia dell'utente se non ha ruoli
            return $user->compagnia_id ? [$user->compagnia_id] : [];
        }
        
        return DB::table('role_compagnia_visibility')
            ->whereIn('role_id', $roleIds)
            ->pluck('compagnia_id')
            ->unique()
            ->toArray();
    }
    
    /**
     * Applica il filtro compagnia in base al tipo di modello
     * 
     * @param Builder $builder
     * @param Model $model
     * @param array $compagnieIds
     */
    protected function applyCompagniaFilter(Builder $builder, Model $model, array $compagnieIds): void
    {
        $table = $model->getTable();
        
        // Gestione speciale per il modello Militare (include acquisiti)
        if ($table === 'militari') {
            $this->applyMilitareFilter($builder, $compagnieIds);
            return;
        }
        
        // Filtro standard per altri modelli
        $column = $this->getCompagniaColumn($model);
        if ($column) {
            $builder->whereIn($table . '.' . $column, $compagnieIds);
        }
    }
    
    /**
     * Applica il filtro per il modello Militare
     * Include sia i militari delle compagnie visibili che quelli "acquisiti" tramite attività
     * 
     * PERFORMANCE: Usa EXISTS invece di IN per migliori prestazioni su dataset grandi
     * 
     * @param Builder $builder
     * @param array $compagnieIds
     */
    protected function applyMilitareFilter(Builder $builder, array $compagnieIds): void
    {
        $builder->where(function ($query) use ($compagnieIds) {
            // 1. Militari delle compagnie visibili (owner)
            $query->whereIn('militari.compagnia_id', $compagnieIds);
            
            // 2. Militari acquisiti tramite partecipazione ad attività delle compagnie visibili
            // NOTA: EXISTS è più efficiente di IN per subquery correlate
            $query->orWhereExists(function ($subquery) use ($compagnieIds) {
                $subquery->select(DB::raw(1))
                    ->from('activity_militare as am')
                    ->join('board_activities as ba', 'am.activity_id', '=', 'ba.id')
                    ->whereColumn('am.militare_id', 'militari.id')
                    ->whereIn('ba.compagnia_id', $compagnieIds);
            });
        });
    }
    
    /**
     * Verifica se l'utente è un amministratore con visibilità globale
     * 
     * @param \App\Models\User $user
     * @return bool
     */
    protected function isAdminUser($user): bool
    {
        // Verifica ruoli amministrativi
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
                return true;
            }
        }
        
        // Verifica il tipo di ruolo (fallback)
        if (property_exists($user, 'role_type') && 
            in_array($user->role_type, ['admin', 'amministratore'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Determina quale colonna usare per il filtro compagnia
     * 
     * @param Model $model
     * @return string|null
     */
    protected function getCompagniaColumn(Model $model): ?string
    {
        // Se il modello specifica una colonna custom
        if (property_exists($model, 'compagniaColumn')) {
            return $model->compagniaColumn;
        }
        
        // Controlla se esiste la colonna compagnia_id
        if (in_array('compagnia_id', $model->getFillable()) || 
            \Schema::hasColumn($model->getTable(), 'compagnia_id')) {
            return 'compagnia_id';
        }
        
        return null;
    }
}
