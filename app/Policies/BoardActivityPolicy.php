<?php

namespace App\Policies;

use App\Models\BoardActivity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy per la gestione dei permessi sulle attività Board.
 * 
 * Implementa la logica read-only cross-unit:
 * - Un utente può vedere attività di tutte le unità a cui ha accesso
 * - Un utente può modificare solo attività dell'unità attiva
 */
class BoardActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può visualizzare l'elenco delle attività.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('board.view');
    }

    /**
     * Determina se l'utente può visualizzare un'attività specifica.
     */
    public function view(User $user, BoardActivity $activity): bool
    {
        // Admin vedono tutto
        if ($user->isGlobalAdmin()) {
            return true;
        }

        if (!$user->hasPermission('board.view')) {
            return false;
        }

        return $this->canAccessActivity($user, $activity);
    }

    /**
     * Determina se l'utente può creare una nuova attività.
     */
    public function create(User $user): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $user->hasPermission('board.edit') && activeUnitId() !== null;
    }

    /**
     * Determina se l'utente può modificare un'attività.
     */
    public function update(User $user, BoardActivity $activity): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        if (!$user->hasPermission('board.edit')) {
            return false;
        }

        return $this->isInActiveUnit($activity);
    }

    /**
     * Determina se l'utente può eliminare un'attività.
     */
    public function delete(User $user, BoardActivity $activity): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        if (!$user->hasPermission('board.delete')) {
            return false;
        }

        return $this->isInActiveUnit($activity);
    }

    /**
     * Verifica se l'utente può accedere ai dati di un'attività.
     */
    protected function canAccessActivity(User $user, BoardActivity $activity): bool
    {
        if ($activity->organizational_unit_id) {
            return $user->canAccessUnit($activity->organizational_unit_id);
        }

        // Fallback legacy
        if ($activity->compagnia_id) {
            return $user->canAccessCompagnia($activity->compagnia_id);
        }

        return $user->isGlobalAdmin();
    }

    /**
     * Verifica se un'attività appartiene all'unità attiva.
     */
    protected function isInActiveUnit(BoardActivity $activity): bool
    {
        $activeUnitId = activeUnitId();

        if (!$activeUnitId) {
            return false;
        }

        return $activity->organizational_unit_id === $activeUnitId;
    }

    /**
     * Helper: verifica se l'attività è read-only per l'utente.
     */
    public function isReadOnly(User $user, BoardActivity $activity): bool
    {
        if ($user->isGlobalAdmin()) {
            return false;
        }

        return !$this->isInActiveUnit($activity);
    }
}
