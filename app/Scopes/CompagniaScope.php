<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * SUGECO: Global Scope per Segregazione Dati per Compagnia
 * 
 * Questo scope viene applicato AUTOMATICAMENTE a tutte le query
 * sui modelli che lo utilizzano. Garantisce che:
 * - Gli utenti vedano SOLO i dati della propria compagnia
 * - Il filtro NON sia bypassabile dal frontend
 * - Il filtro sia applicato a TUTTE le query (select, update, delete)
 * 
 * SICUREZZA: Questo scope è il pilastro della segregazione dati.
 * Non può essere disattivato se non con `withoutGlobalScope()` che
 * richiede privilegi speciali (solo admin/sistema).
 * 
 * @package App\Scopes
 * @version 1.0
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
            // Forza una condizione impossibile per non restituire nulla
            $builder->whereRaw('1 = 0');
            return;
        }
        
        $user = Auth::user();
        
        // Gli admin/amministratori vedono tutto
        if ($this->isAdminUser($user)) {
            return; // Nessun filtro applicato
        }
        
        // Ottieni la compagnia dell'utente
        $compagniaId = $user->compagnia_id;
        
        // Se l'utente non ha una compagnia assegnata, non può vedere nulla
        if (!$compagniaId) {
            $builder->whereRaw('1 = 0');
            return;
        }
        
        // Determina la colonna da usare per il filtro
        $column = $this->getCompagniaColumn($model);
        
        if ($column) {
            $builder->where($model->getTable() . '.' . $column, $compagniaId);
        }
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

