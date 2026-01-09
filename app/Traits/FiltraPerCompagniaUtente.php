<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @deprecated Questo trait è DEPRECATO. Usa il Global Scope CompagniaScope invece.
 * 
 * Il Global Scope (BelongsToCompagnia) filtra automaticamente per owner + acquired.
 * Questo trait filtrava SOLO per owner, tagliando fuori i militari "acquired".
 * 
 * I metodi di questo trait ora sono no-op per retrocompatibilità.
 * 
 * @see \App\Scopes\CompagniaScope
 * @see \App\Traits\BelongsToCompagnia
 */
trait FiltraPerCompagniaUtente
{
    /**
     * @deprecated NON USARE - Il Global Scope CompagniaScope filtra già automaticamente.
     * Questo metodo ora è un no-op per retrocompatibilità.
     * 
     * @param Builder $query
     * @return Builder
     */
    protected function applicaFiltroCompagniaUtente(Builder $query): Builder
    {
        // DEPRECATO: Il Global Scope CompagniaScope filtra già per owner + acquired
        // Restituisce la query senza modifiche
        return $query;
    }
    
    /**
     * Verifica se l'utente può vedere un militare specifico
     * 
     * @param int $militareCompagniaId
     * @return bool
     */
    protected function puoVedereMilitare(int $militareCompagniaId): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        // Admin e amministratori vedono tutto
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return true;
        }
        
        // Se l'utente non ha compagnia assegnata, vede tutto (per retrocompatibilità)
        if (!$user->compagnia_id) {
            return true;
        }
        
        // L'utente vede solo i militari della sua compagnia
        return $user->compagnia_id === $militareCompagniaId;
    }
    
    /**
     * Ottiene l'ID della compagnia dell'utente corrente
     * 
     * @return int|null
     */
    protected function getCompagniaUtenteId(): ?int
    {
        $user = Auth::user();
        
        if (!$user) {
            return null;
        }
        
        // Admin e amministratori non hanno restrizioni
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return null;
        }
        
        return $user->compagnia_id;
    }
    
    /**
     * Verifica se l'utente è admin
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        $user = Auth::user();
        
        if (!$user) {
            return false;
        }
        
        return $user->hasRole('admin') || $user->hasRole('amministratore');
    }
}

