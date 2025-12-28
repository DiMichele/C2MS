<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait per filtrare automaticamente i risultati per compagnia dell'utente
 * 
 * Se l'utente non è admin/amministratore e ha una compagnia_id assegnata,
 * i risultati verranno filtrati per mostrare solo i militari della sua compagnia.
 */
trait FiltraPerCompagniaUtente
{
    /**
     * Applica il filtro compagnia alla query dei militari
     * 
     * @param Builder $query
     * @return Builder
     */
    protected function applicaFiltroCompagniaUtente(Builder $query): Builder
    {
        $user = Auth::user();
        
        // Se non c'è utente autenticato, non filtrare
        if (!$user) {
            return $query;
        }
        
        // Admin e amministratori vedono tutto
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return $query;
        }
        
        // Se l'utente ha una compagnia assegnata, filtra per quella compagnia
        if ($user->compagnia_id) {
            $query->where('compagnia_id', $user->compagnia_id);
        }
        
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

