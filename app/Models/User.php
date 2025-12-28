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
     * Verifica se l'utente ha visibilità globale su tutte le compagnie
     * 
     * @return bool
     */
    public function hasGlobalVisibility(): bool
    {
        // Admin e amministratori hanno sempre visibilità globale
        if ($this->isAdmin()) {
            return true;
        }
        
        // Verifica permesso specifico
        return $this->hasPermission('view_all_companies');
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
     * 
     * @param int $compagniaId
     * @return bool
     */
    public function canAccessCompagnia(int $compagniaId): bool
    {
        // Visibilità globale = può accedere a tutto
        if ($this->hasGlobalVisibility()) {
            return true;
        }
        
        // Altrimenti può accedere solo alla propria compagnia
        return $this->compagnia_id === $compagniaId;
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
        
        // Verifica permesso di modifica
        if (!$this->hasPermission('edit_military')) {
            return false;
        }
        
        // Può modificare solo la propria compagnia
        return $this->compagnia_id === $compagniaId;
    }
    
    /**
     * Ottiene le compagnie visibili all'utente
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVisibleCompagnie()
    {
        if ($this->hasGlobalVisibility()) {
            return \App\Models\Compagnia::orderBy('nome')->get();
        }
        
        if ($this->compagnia_id) {
            return \App\Models\Compagnia::where('id', $this->compagnia_id)->get();
        }
        
        return collect();
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
} 
