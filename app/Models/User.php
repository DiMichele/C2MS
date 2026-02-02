<?php

/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * 
 * Questo file fa parte del sistema SUGECO per la gestione militare digitale.
 * 
 * @package    SUGECO
 * @subpackage Models
 * @version    2.1.0
 * @author     Michele Di Gennaro
 * @copyright 2025 Michele Di Gennaro
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modello per gli utenti del sistema
 * 
 * Questo modello rappresenta gli utenti che possono accedere al sistema SUGECO.
 * Estende la classe Authenticatable di Laravel per fornire funzionalità
 * di autenticazione e autorizzazione.
 * 
 * @package App\Models
 * @version 1.0
 * 
 * @property int $id ID univoco dell'utente
 * @property string $name Nome completo dell'utente
 * @property string $email Indirizzo email (univoco)
 * @property \Illuminate\Support\Carbon|null $email_verified_at Data verifica email
 * @property string $password Password hashata
 * @property string|null $remember_token Token per "ricordami"
 * @property \Illuminate\Support\Carbon|null $created_at Data creazione
 * @property \Illuminate\Support\Carbon|null $updated_at Data ultimo aggiornamento
 * 
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Gli attributi che sono mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'codice_fiscale',
        'compagnia_id',
        'organizational_unit_id', // Nuova gerarchia organizzativa
        'role_type',
        'password',
        'must_change_password',
        'last_password_change',
    ];

    /**
     * Gli attributi che dovrebbero essere nascosti per la serializzazione.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Gli attributi che dovrebbero essere cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_password_change' => 'datetime',
        'password' => 'hashed',
        'must_change_password' => 'boolean',
    ];

    /**
     * Compagnia dell'utente
     */
    public function compagnia()
    {
        return $this->belongsTo(\App\Models\Compagnia::class);
    }

    /**
     * Unità organizzativa primaria dell'utente (nuova gerarchia).
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(\App\Models\OrganizationalUnit::class, 'organizational_unit_id');
    }

    /**
     * Ruoli dell'utente
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Verifica se l'utente ha un determinato ruolo
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Verifica se l'utente ha uno qualsiasi dei ruoli specificati
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * Verifica se l'utente ha un determinato permesso
     */
    public function hasPermission(string $permissionName): bool
    {
        // L'admin e l'amministratore hanno SEMPRE tutti i permessi
        if ($this->hasRole('admin') || $this->hasRole('amministratore')) {
            return true;
        }
        
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Assegna un ruolo all'utente
     */
    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Rimuove un ruolo dall'utente
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role->id);
    }

    /**
     * Verifica se l'utente è amministratore
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('amministratore');
    }

    /**
     * Verifica se l'utente è un amministratore globale (può vedere tutte le compagnie)
     * 
     * @return bool
     */
    public function isGlobalAdmin(): bool
    {
        return $this->isAdmin() || $this->hasGlobalVisibility();
    }

    /**
     * Verifica se l'utente ha visibilità globale su tutte le compagnie
     * Ora si basa sulla tabella role_compagnia_visibility
     * 
     * @return bool
     */
    public function hasGlobalVisibility(): bool
    {
        // Admin e amministratori hanno sempre visibilità globale
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verifica se l'utente può vedere TUTTE le compagnie tramite i suoi ruoli
        $totalCompagnie = \App\Models\Compagnia::count();
        $visibleCompagnie = count($this->getVisibleCompagnieIds());
        
        return $visibleCompagnie >= $totalCompagnie;
    }
    
    /**
     * Ottiene l'ID della compagnia dell'utente
     * Restituisce null se l'utente ha visibilità globale
     * 
     * @return int|null
     */
    public function getCompagniaIdForScope(): ?int
    {
        if ($this->hasGlobalVisibility()) {
            return null; // Nessun filtro
        }
        
        return $this->compagnia_id;
    }
    
    /**
     * Verifica se l'utente può accedere ai dati di una specifica compagnia
     * Usa la tabella role_compagnia_visibility per determinare l'accesso
     * 
     * @param int $compagniaId
     * @return bool
     */
    public function canAccessCompagnia(int $compagniaId): bool
    {
        // Admin e amministratori vedono sempre tutto
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verifica nella tabella di visibilità per ogni ruolo dell'utente
        foreach ($this->roles as $role) {
            if ($role->canViewCompagnia($compagniaId)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica se l'utente può modificare i dati di una specifica compagnia
     * 
     * @param int $compagniaId
     * @return bool
     */
    public function canEditCompagnia(int $compagniaId): bool
    {
        // Admin possono modificare tutto
        if ($this->isAdmin()) {
            return true;
        }
        
        // Deve avere accesso alla compagnia
        if (!$this->canAccessCompagnia($compagniaId)) {
            return false;
        }
        
        // Verifica permesso di modifica anagrafica
        return $this->hasPermission('anagrafica.edit');
    }
    
    /**
     * Ottiene le compagnie visibili all'utente basandosi sulla tabella role_compagnia_visibility
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleCompagnie()
    {
        // Admin e amministratori vedono tutte le compagnie
        if ($this->isAdmin()) {
            return \App\Models\Compagnia::orderBy('nome')->get();
        }
        
        // Raccogli gli ID delle compagnie visibili da tutti i ruoli dell'utente
        $compagniaIds = collect();
        foreach ($this->roles as $role) {
            $roleCompagniaIds = $role->getCompagnieVisibiliIds();
            $compagniaIds = $compagniaIds->merge($roleCompagniaIds);
        }
        
        $compagniaIds = $compagniaIds->unique()->toArray();
        
        if (empty($compagniaIds)) {
            return collect();
        }
        
        return \App\Models\Compagnia::whereIn('id', $compagniaIds)->orderBy('nome')->get();
    }
    
    /**
     * Ottiene gli ID delle compagnie visibili all'utente
     * 
     * @return array
     */
    public function getVisibleCompagnieIds(): array
    {
        // Admin e amministratori vedono tutte le compagnie
        if ($this->isAdmin()) {
            return \App\Models\Compagnia::pluck('id')->toArray();
        }
        
        // Raccogli gli ID delle compagnie visibili da tutti i ruoli dell'utente
        $compagniaIds = collect();
        foreach ($this->roles as $role) {
            $roleCompagniaIds = $role->getCompagnieVisibiliIds();
            $compagniaIds = $compagniaIds->merge($roleCompagniaIds);
        }
        
        return $compagniaIds->unique()->toArray();
    }

    /**
     * Ottieni tutti i permessi dell'utente
     */
    public function getAllPermissions()
    {
        $permissions = collect();
        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }
        return $permissions->unique('id');
    }
    
    /**
     * Ottiene un array di tutti i nomi dei permessi dell'utente
     * 
     * @return array
     */
    public function getPermissionNames(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    // =========================================================================
    // GERARCHIA ORGANIZZATIVA
    // =========================================================================

    /**
     * Assegnazioni alle unità organizzative.
     */
    public function unitAssignments()
    {
        return $this->morphMany(\App\Models\UnitAssignment::class, 'assignable');
    }

    /**
     * Unità organizzative a cui l'utente è assegnato.
     */
    public function organizationalUnits()
    {
        return $this->morphToMany(
            \App\Models\OrganizationalUnit::class,
            'assignable',
            'unit_assignments',
            'assignable_id',
            'unit_id'
        )
            ->withPivot(['role', 'is_primary', 'start_date', 'end_date', 'notes'])
            ->withTimestamps();
    }

    /**
     * Ottiene l'unità primaria dell'utente.
     * 
     * @return \App\Models\OrganizationalUnit|null
     */
    public function getPrimaryUnit(): ?\App\Models\OrganizationalUnit
    {
        $assignment = $this->unitAssignments()
            ->where('is_primary', true)
            ->active()
            ->first();

        return $assignment?->unit;
    }

    /**
     * Verifica se l'utente ha un permesso su un'unità specifica.
     * 
     * @param string $permission Nome del permesso
     * @param \App\Models\OrganizationalUnit|int $unit Unità o ID
     * @return bool
     */
    public function hasPermissionOnUnit(string $permission, $unit): bool
    {
        $service = app(\App\Services\HierarchicalPermissionService::class);
        
        if (is_int($unit)) {
            $unit = \App\Models\OrganizationalUnit::find($unit);
        }
        
        if (!$unit) {
            return false;
        }

        return $service->hasPermissionOnUnit($this, $permission, $unit);
    }

    /**
     * Verifica se l'utente ha un permesso in una specifica unità.
     * Alias per hasPermissionOnUnit().
     * 
     * @param string $permission Nome del permesso
     * @param int $unitId ID dell'unità
     * @return bool
     */
    public function hasPermissionInUnit(string $permission, int $unitId): bool
    {
        return $this->hasPermissionOnUnit($permission, $unitId);
    }

    /**
     * Ottiene gli ID delle unità visibili all'utente nella gerarchia.
     * 
     * @return array
     */
    public function getVisibleUnitIds(): array
    {
        $service = app(\App\Services\HierarchicalPermissionService::class);
        return $service->getVisibleUnitIds($this);
    }

    /**
     * Verifica se l'utente può accedere a un'unità specifica.
     * 
     * @param \App\Models\OrganizationalUnit|int $unit
     * @return bool
     */
    public function canAccessUnit($unit): bool
    {
        if ($this->isGlobalAdmin()) {
            return true;
        }

        $unitId = $unit instanceof \App\Models\OrganizationalUnit ? $unit->id : $unit;
        
        return in_array($unitId, $this->getVisibleUnitIds());
    }

    /**
     * Assegna l'utente a un'unità organizzativa.
     * 
     * @param \App\Models\OrganizationalUnit|int $unit
     * @param string $role Ruolo nell'unità
     * @param bool $isPrimary È l'assegnazione primaria?
     * @param array $options Opzioni aggiuntive
     * @return \App\Models\UnitAssignment
     */
    public function assignToUnit($unit, string $role = 'membro', bool $isPrimary = false, array $options = []): \App\Models\UnitAssignment
    {
        $unitId = $unit instanceof \App\Models\OrganizationalUnit ? $unit->id : $unit;

        if ($isPrimary) {
            $this->unitAssignments()->update(['is_primary' => false]);
        }

        return $this->unitAssignments()->updateOrCreate(
            ['unit_id' => $unitId],
            [
                'role' => $role,
                'is_primary' => $isPrimary,
                'start_date' => $options['start_date'] ?? null,
                'end_date' => $options['end_date'] ?? null,
                'notes' => $options['notes'] ?? null,
            ]
        );
    }

    /**
     * Rimuove l'utente da un'unità organizzativa.
     * 
     * @param \App\Models\OrganizationalUnit|int $unit
     * @return bool
     */
    public function removeFromUnit($unit): bool
    {
        $unitId = $unit instanceof \App\Models\OrganizationalUnit ? $unit->id : $unit;
        
        return $this->unitAssignments()->where('unit_id', $unitId)->delete() > 0;
    }
} 
