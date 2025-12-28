<?php

namespace App\Policies;

use App\Models\Militare;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * SUGECO: Policy per la gestione dei permessi sui Militari
 * 
 * Questa policy implementa la logica di autorizzazione per il modello Militare,
 * gestendo sia i militari "owner" che quelli "acquisiti" tramite attività.
 * 
 * REGOLE:
 * - Owner: L'utente della stessa compagnia può vedere E modificare
 * - Acquired: L'utente di altra compagnia può SOLO vedere (read-only)
 * - Admin: Può fare tutto
 * 
 * @package App\Policies
 * @version 1.0
 * @author Michele Di Gennaro
 */
class MilitarePolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può visualizzare qualsiasi militare.
     * (Lista militari)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('anagrafica.view') || 
               $user->hasPermission('view_own_company') ||
               $user->isAdmin();
    }

    /**
     * Determina se l'utente può visualizzare un militare specifico.
     * 
     * L'utente può vedere:
     * - Militari della propria compagnia (owner)
     * - Militari acquisiti tramite attività (acquired)
     */
    public function view(User $user, Militare $militare): bool
    {
        // Admin vedono tutto
        if ($user->isAdmin()) {
            return true;
        }
        
        // Verifica se è owner
        if ($this->isOwner($user, $militare)) {
            return true;
        }
        
        // Verifica se è acquisito
        if ($this->isAcquired($user, $militare)) {
            return true;
        }
        
        return false;
    }

    /**
     * Determina se l'utente può creare militari.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('anagrafica.edit') || $user->isAdmin();
    }

    /**
     * Determina se l'utente può modificare un militare.
     * 
     * IMPORTANTE: Solo gli owner possono modificare!
     * I militari acquisiti sono SEMPRE in sola lettura.
     */
    public function update(User $user, Militare $militare): bool
    {
        // Admin possono modificare tutto
        if ($user->isAdmin()) {
            return true;
        }
        
        // Verifica permesso di modifica
        if (!$user->hasPermission('anagrafica.edit')) {
            return false;
        }
        
        // SOLO gli owner possono modificare
        // I militari acquisiti sono read-only
        return $this->isOwner($user, $militare);
    }

    /**
     * Determina se l'utente può eliminare un militare.
     * 
     * Solo gli owner con permessi adeguati possono eliminare.
     */
    public function delete(User $user, Militare $militare): bool
    {
        // Admin possono eliminare
        if ($user->isAdmin()) {
            return true;
        }
        
        // Verifica permesso di modifica
        if (!$user->hasPermission('anagrafica.edit')) {
            return false;
        }
        
        // SOLO gli owner possono eliminare
        return $this->isOwner($user, $militare);
    }

    /**
     * Determina se l'utente può modificare il CPT di un militare.
     */
    public function updateCpt(User $user, Militare $militare): bool
    {
        // Admin possono modificare
        if ($user->isAdmin()) {
            return true;
        }
        
        // Verifica permesso CPT
        if (!$user->hasPermission('cpt.edit')) {
            return false;
        }
        
        // SOLO gli owner possono modificare il CPT
        return $this->isOwner($user, $militare);
    }

    /**
     * Verifica se l'utente è "owner" del militare.
     * Owner = stesso compagnia_id
     */
    public function isOwner(User $user, Militare $militare): bool
    {
        if (!$user->compagnia_id) {
            return false;
        }
        
        return $militare->compagnia_id === $user->compagnia_id;
    }

    /**
     * Verifica se il militare è "acquisito" dalla compagnia dell'utente.
     * Acquisito = partecipa ad almeno un'attività della compagnia dell'utente
     */
    public function isAcquired(User $user, Militare $militare): bool
    {
        if (!$user->compagnia_id) {
            return false;
        }
        
        // Se è owner, non è "acquisito" (è proprio)
        if ($this->isOwner($user, $militare)) {
            return false;
        }
        
        // Verifica se il militare partecipa ad attività della compagnia dell'utente
        return \DB::table('activity_militare')
            ->join('board_activities', 'activity_militare.activity_id', '=', 'board_activities.id')
            ->where('activity_militare.militare_id', $militare->id)
            ->where('board_activities.compagnia_id', $user->compagnia_id)
            ->exists();
    }

    /**
     * Ottiene il tipo di relazione tra utente e militare.
     * 
     * @return string 'owner', 'acquired', o 'none'
     */
    public static function getRelationType(User $user, Militare $militare): string
    {
        $policy = new self();
        
        if ($policy->isOwner($user, $militare)) {
            return 'owner';
        }
        
        if ($policy->isAcquired($user, $militare)) {
            return 'acquired';
        }
        
        return 'none';
    }

    /**
     * Verifica se il militare è modificabile dall'utente.
     * Wrapper per essere usato nei controller/view.
     */
    public static function canEdit(User $user, Militare $militare): bool
    {
        $policy = new self();
        return $policy->update($user, $militare);
    }

    /**
     * Verifica se il militare è read-only per l'utente.
     */
    public static function isReadOnly(User $user, Militare $militare): bool
    {
        $policy = new self();
        
        // Admin non ha mai read-only
        if ($user->isAdmin()) {
            return false;
        }
        
        // Se acquisito, è read-only
        if ($policy->isAcquired($user, $militare)) {
            return true;
        }
        
        // Se owner ma senza permessi di modifica, è read-only
        if ($policy->isOwner($user, $militare) && !$user->hasPermission('anagrafica.edit')) {
            return true;
        }
        
        return false;
    }
}

