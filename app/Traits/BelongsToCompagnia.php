<?php

namespace App\Traits;

use App\Scopes\CompagniaScope;
use Illuminate\Support\Facades\Auth;

/**
 * SUGECO: Trait per Segregazione Dati per Compagnia
 * 
 * Questo trait deve essere usato da TUTTI i modelli che contengono
 * dati sensibili da segregare per compagnia.
 * 
 * Funzionalità:
 * - Applica automaticamente il Global Scope CompagniaScope
 * - Imposta automaticamente la compagnia_id alla creazione
 * - Fornisce metodi helper per query con/senza scope
 * 
 * IMPORTANTE: Una volta applicato, il filtro è SEMPRE attivo.
 * Solo gli admin possono bypassarlo usando withoutGlobalScope().
 * 
 * @package App\Traits
 * @version 1.0
 * @author Michele Di Gennaro
 */
trait BelongsToCompagnia
{
    /**
     * Boot del trait - Registra il Global Scope
     * 
     * Questo metodo viene chiamato automaticamente quando il modello
     * viene inizializzato. Registra il CompagniaScope che verrà
     * applicato a TUTTE le query.
     */
    protected static function bootBelongsToCompagnia(): void
    {
        // Registra il Global Scope
        static::addGlobalScope(new CompagniaScope());
        
        // Auto-imposta compagnia_id alla creazione
        static::creating(function ($model) {
            if (Auth::check() && empty($model->compagnia_id)) {
                $user = Auth::user();
                if ($user->compagnia_id && !$model->isDirty('compagnia_id')) {
                    $model->compagnia_id = $user->compagnia_id;
                }
            }
        });
    }
    
    /**
     * Relazione con la compagnia
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function compagnia()
    {
        return $this->belongsTo(\App\Models\Compagnia::class, 'compagnia_id');
    }
    
    /**
     * Scope per query senza filtro compagnia (SOLO PER ADMIN)
     * 
     * ATTENZIONE: Questo metodo bypassa la segregazione!
     * Usare SOLO in contesti amministrativi verificati.
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withoutCompagniaScope()
    {
        // Verifica che l'utente sia admin prima di permettere il bypass
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('admin') || $user->hasRole('amministratore') || 
                $user->hasPermission('view_all_companies')) {
                return static::withoutGlobalScope(CompagniaScope::class);
            }
        }
        
        // Se non è admin, restituisce query normale (con scope)
        return static::query();
    }
    
    /**
     * Scope per filtrare per una specifica compagnia (SOLO PER ADMIN)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $compagniaId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerCompagnia($query, int $compagniaId)
    {
        // Solo admin possono specificare una compagnia diversa
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
                return $query->withoutGlobalScope(CompagniaScope::class)
                             ->where($this->getTable() . '.compagnia_id', $compagniaId);
            }
        }
        
        // Non-admin: ignora il parametro e usa la compagnia dell'utente
        return $query;
    }
    
    /**
     * Verifica se il record appartiene alla compagnia dell'utente corrente
     * 
     * @return bool
     */
    public function belongsToCurrentUserCompagnia(): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        
        // Admin vedono tutto
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return true;
        }
        
        return $this->compagnia_id === $user->compagnia_id;
    }
    
    /**
     * Verifica se l'utente corrente può accedere a questo record
     * 
     * @return bool
     */
    public function isAccessibleByCurrentUser(): bool
    {
        return $this->belongsToCurrentUserCompagnia();
    }
}

