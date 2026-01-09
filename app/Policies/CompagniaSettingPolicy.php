<?php

namespace App\Policies;

use App\Models\CompagniaSetting;
use App\Models\ConfigurazioneRuolino;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Policy per le impostazioni di compagnia
 * 
 * Controlla chi può visualizzare e modificare le impostazioni
 * di configurazione ruolini e altre impostazioni per compagnia.
 */
class CompagniaSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Admin globali possono fare tutto
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            return true;
        }

        return null;
    }

    /**
     * Verifica se l'utente può visualizzare le impostazioni
     */
    public function viewAny(User $user): bool
    {
        // Deve avere una compagnia assegnata
        if (!$user->compagnia_id) {
            return false;
        }

        // Deve avere il permesso di gestire ruolini o essere admin di compagnia
        return $user->hasPermission('gestione_ruolini.view') ||
               $user->hasPermission('manage_own_company') ||
               $user->hasPermission('edit_own_company');
    }

    /**
     * Verifica se l'utente può visualizzare le impostazioni di una compagnia specifica
     */
    public function view(User $user, CompagniaSetting $setting): bool
    {
        // Può vedere solo le impostazioni della propria compagnia
        return $user->compagnia_id === $setting->compagnia_id;
    }

    /**
     * Verifica se l'utente può modificare le impostazioni
     */
    public function update(User $user, CompagniaSetting $setting): bool
    {
        // Deve essere della stessa compagnia
        if ($user->compagnia_id !== $setting->compagnia_id) {
            return false;
        }

        // Deve avere il permesso di modificare ruolini o essere admin di compagnia
        return $user->hasPermission('gestione_ruolini.edit') ||
               $user->hasPermission('manage_own_company');
    }

    /**
     * Verifica se l'utente può modificare le configurazioni ruolini
     */
    public function manageRuolini(User $user): bool
    {
        // Deve avere una compagnia assegnata
        if (!$user->compagnia_id) {
            return false;
        }

        return $user->hasPermission('gestione_ruolini.edit') ||
               $user->hasPermission('manage_own_company');
    }

    /**
     * Verifica se l'utente può modificare una configurazione ruolino specifica
     */
    public function updateRuolino(User $user, ConfigurazioneRuolino $ruolino): bool
    {
        // Deve essere della stessa compagnia
        return $user->compagnia_id === $ruolino->compagnia_id;
    }
}

