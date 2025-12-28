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
 * - Gli utenti vedano SOLO i dati della propria compagnia
 * - Gli utenti vedano anche i militari "acquisiti" tramite attività (read-only)
 * - Il filtro NON sia bypassabile dal frontend
 * - Il filtro sia applicato a TUTTE le query (select, update, delete)
 * 
 * VISIBILITÀ CROSS-COMPAGNIA:
 * Un utente della Compagnia A può vedere un militare della Compagnia B se:
 * - Il militare è stato assegnato a un'attività della Compagnia A
 * - In questo caso il militare è visibile ma NON modificabile (read-only)
 * 
 * SICUREZZA: Questo scope è il pilastro della segregazione dati.
 * Non può essere disattivato se non con `withoutGlobalScope()` che
 * richiede privilegi speciali (solo admin/sistema).
 * 
 * @package App\Scopes
 * @version 2.0
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
        
        // Ottieni la compagnia dell'utente
        $compagniaId = $user->compagnia_id;
        
        // Se l'utente non ha una compagnia assegnata, non può vedere nulla
        if (!$compagniaId) {
            $builder->whereRaw('1 = 0');
            return;
        }
        
        // Applica filtro specifico per modello
        $this->applyCompagniaFilter($builder, $model, $compagniaId);
    }
    
    /**
     * Applica il filtro compagnia in base al tipo di modello
     * 
     * @param Builder $builder
     * @param Model $model
     * @param int $compagniaId
     */
    protected function applyCompagniaFilter(Builder $builder, Model $model, int $compagniaId): void
    {
        $table = $model->getTable();
        
        // Gestione speciale per il modello Militare (include acquisiti)
        if ($table === 'militari') {
            $this->applyMilitareFilter($builder, $compagniaId);
            return;
        }
        
        // Filtro standard per altri modelli
        $column = $this->getCompagniaColumn($model);
        if ($column) {
            $builder->where($table . '.' . $column, $compagniaId);
        }
    }
    
    /**
     * Applica il filtro per il modello Militare
     * Include sia i militari della compagnia che quelli "acquisiti" tramite attività
     * 
     * @param Builder $builder
     * @param int $compagniaId
     */
    protected function applyMilitareFilter(Builder $builder, int $compagniaId): void
    {
        $builder->where(function ($query) use ($compagniaId) {
            // 1. Militari della propria compagnia (owner)
            $query->where('militari.compagnia_id', $compagniaId);
            
            // 2. Militari acquisiti tramite partecipazione ad attività della compagnia
            $query->orWhereIn('militari.id', function ($subquery) use ($compagniaId) {
                $subquery->select('am.militare_id')
                    ->from('activity_militare as am')
                    ->join('board_activities as ba', 'am.activity_id', '=', 'ba.id')
                    ->where('ba.compagnia_id', $compagniaId);
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
        
        // Verifica il tipo di ruolo
        if ($user->role_type === 'admin' || $user->role_type === 'amministratore') {
            return true;
        }
        
        // Verifica se ha il permesso speciale di visibilità globale
        if (method_exists($user, 'hasPermission')) {
            if ($user->hasPermission('view_all_companies')) {
                return true;
            }
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
