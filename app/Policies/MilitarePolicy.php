<?php

namespace App\Policies;

use App\Models\Militare;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy per la gestione dei permessi sui Militari.
 * 
 * Implementa la logica read-only cross-unit:
 * - Un utente può vedere militari di tutte le unità a cui ha accesso
 * - Un utente può modificare solo i militari dell'unità attiva
 * - Admin possono fare tutto
 */
class MilitarePolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può visualizzare l'elenco dei militari.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('anagrafica.view');
    }

    /**
     * Determina se l'utente può visualizzare un militare specifico.
     * 
     * L'utente può vedere militari di tutte le unità accessibili.
     */
    public function view(User $user, Militare $militare): bool
    {
        // Admin vedono tutto
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Verifica permesso base
        if (!$user->hasPermission('anagrafica.view')) {
            return false;
        }

        // Verifica se il militare appartiene a un'unità accessibile
        return $this->canAccessMilitare($user, $militare);
    }

    /**
     * Determina se l'utente può creare un nuovo militare.
     * 
     * Il militare sarà automaticamente assegnato all'unità attiva.
     */
    public function create(User $user): bool
    {
        // Admin possono creare
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Deve avere permesso di modifica e un'unità attiva
        return $user->hasPermission('anagrafica.edit') && activeUnitId() !== null;
    }

    /**
     * Determina se l'utente può modificare un militare.
     * 
     * L'utente può modificare solo militari dell'unità attiva.
     */
    public function update(User $user, Militare $militare): bool
    {
        // Admin possono modificare tutto
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Verifica permesso base
        if (!$user->hasPermission('anagrafica.edit')) {
            return false;
        }

        // Può modificare solo se il militare appartiene all'unità attiva
        return $this->isInActiveUnit($militare);
    }

    /**
     * Determina se l'utente può eliminare un militare.
     */
    public function delete(User $user, Militare $militare): bool
    {
        // Admin possono eliminare
        if ($user->isGlobalAdmin()) {
            return true;
        }

        // Verifica permesso base
        if (!$user->hasPermission('anagrafica.delete')) {
            return false;
        }

        // Può eliminare solo se il militare appartiene all'unità attiva
        return $this->isInActiveUnit($militare);
    }

    /**
     * Verifica se l'utente può accedere ai dati di un militare (view).
     */
    protected function canAccessMilitare(User $user, Militare $militare): bool
    {
        // Se il militare ha un'unità organizzativa, verifica l'accesso
        if ($militare->organizational_unit_id) {
            return $user->canAccessUnit($militare->organizational_unit_id);
        }

        // Fallback legacy: verifica accesso tramite compagnia
        if ($militare->compagnia_id) {
            return $user->canAccessCompagnia($militare->compagnia_id);
        }

        // Militare senza unità/compagnia - accesso consentito per admin
        return $user->isGlobalAdmin();
    }

    /**
     * Verifica se un militare appartiene all'unità attiva.
     */
    protected function isInActiveUnit(Militare $militare): bool
    {
        $activeUnitId = activeUnitId();

        if (!$activeUnitId) {
            return false;
        }

        return $militare->organizational_unit_id === $activeUnitId;
    }

    /**
     * Helper: verifica se il modello è read-only per l'utente (di altra unità).
     */
    public function isReadOnly(User $user, Militare $militare): bool
    {
        // Admin: mai read-only
        if ($user->isGlobalAdmin()) {
            return false;
        }

        // Read-only se appartiene a un'altra unità
        return !$this->isInActiveUnit($militare);
    }
}
