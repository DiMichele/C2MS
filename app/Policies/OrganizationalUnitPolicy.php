<?php

namespace App\Policies;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy per l'autorizzazione sulle unità organizzative.
 */
class OrganizationalUnitPolicy
{
    use HandlesAuthorization;

    /**
     * Determina se l'utente può visualizzare la lista delle unità.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('gerarchia.view') || $user->hasRole('admin');
    }

    /**
     * Determina se l'utente può visualizzare un'unità specifica.
     */
    public function view(User $user, OrganizationalUnit $unit): bool
    {
        // Admin possono vedere tutto
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return true;
        }

        // Verifica permesso base
        if (!$user->hasPermission('gerarchia.view')) {
            return false;
        }

        // Verifica se l'utente ha accesso a questa unità o ai suoi antenati
        return $this->hasAccessToUnit($user, $unit);
    }

    /**
     * Determina se l'utente può creare nuove unità.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('gerarchia.edit') || $user->hasRole('admin');
    }

    /**
     * Determina se l'utente può aggiornare un'unità.
     */
    public function update(User $user, OrganizationalUnit $unit): bool
    {
        // Admin possono modificare tutto
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return true;
        }

        // Verifica permesso base
        if (!$user->hasPermission('gerarchia.edit')) {
            return false;
        }

        // Verifica se l'utente ha accesso a questa unità
        return $this->hasAccessToUnit($user, $unit);
    }

    /**
     * Determina se l'utente può eliminare un'unità.
     */
    public function delete(User $user, OrganizationalUnit $unit): bool
    {
        // Solo admin possono eliminare
        if (!$user->hasRole('admin') && !$user->hasRole('amministratore')) {
            return false;
        }

        // Verifica permesso specifico
        if (!$user->hasPermission('gerarchia.delete')) {
            return false;
        }

        return true;
    }

    /**
     * Determina se l'utente può spostare un'unità.
     */
    public function move(User $user, OrganizationalUnit $unit): bool
    {
        return $this->update($user, $unit);
    }

    /**
     * Verifica se l'utente ha accesso a un'unità.
     */
    protected function hasAccessToUnit(User $user, OrganizationalUnit $unit): bool
    {
        // Ottieni le unità a cui l'utente ha accesso diretto
        $directUnitIds = $user->unitAssignments()
            ->active()
            ->pluck('unit_id')
            ->toArray();

        // Se non ha assegnazioni, usa la logica legacy
        if (empty($directUnitIds)) {
            return $this->hasAccessViaLegacy($user, $unit);
        }

        // Verifica se l'unità target è tra quelle accessibili (inclusi discendenti)
        $accessibleUnitIds = \DB::table('unit_closure')
            ->whereIn('ancestor_id', $directUnitIds)
            ->pluck('descendant_id')
            ->toArray();

        return in_array($unit->id, $accessibleUnitIds);
    }

    /**
     * Verifica accesso usando la logica legacy (compagnia_id).
     */
    protected function hasAccessViaLegacy(User $user, OrganizationalUnit $unit): bool
    {
        // Se l'unità ha una compagnia legacy
        if ($unit->legacy_compagnia_id) {
            if (method_exists($user, 'canAccessCompagnia')) {
                return $user->canAccessCompagnia($unit->legacy_compagnia_id);
            }
        }

        // Fallback: verifica se l'utente è nella stessa compagnia
        if ($user->compagnia_id && $unit->legacy_compagnia_id) {
            return $user->compagnia_id === $unit->legacy_compagnia_id;
        }

        // Se nessuna condizione soddisfatta, nega l'accesso
        return false;
    }
}
