<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SUGECO: Modello Role con supporto segregazione per unità organizzativa
 * 
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property int|null $compagnia_id @deprecated Usare organizational_unit_id
 * @property int|null $organizational_unit_id Unità organizzativa di appartenenza del ruolo
 * @property bool $is_global Indica se il ruolo ha visibilità globale
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'compagnia_id', // @deprecated
        'organizational_unit_id',
        'is_global',
    ];
    
    protected $casts = [
        'is_global' => 'boolean',
    ];

    /**
     * Utenti che hanno questo ruolo
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Permessi associati a questo ruolo
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')
            ->withTimestamps();
    }
    
    /**
     * Compagnia del ruolo (se specifico per compagnia)
     * @deprecated Usare organizationalUnit()
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
    }
    
    /**
     * Unità organizzativa di appartenenza del ruolo
     * Sostituisce compagnia() per la gestione multi-tenancy
     */
    public function organizationalUnit()
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }
    
    /**
     * Compagnie visibili per questo ruolo (tabella pivot role_compagnia_visibility)
     * @deprecated Usare visibleUnits()
     */
    public function compagnieVisibili(): BelongsToMany
    {
        return $this->belongsToMany(Compagnia::class, 'role_compagnia_visibility', 'role_id', 'compagnia_id')
            ->withTimestamps();
    }
    
    /**
     * Unità organizzative visibili per questo ruolo
     */
    public function visibleUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            OrganizationalUnit::class,
            'role_visible_units',
            'role_id',
            'organizational_unit_id'
        )->withTimestamps();
    }
    
    /**
     * Permessi specifici per unità organizzativa
     */
    public function unitPermissions(): HasMany
    {
        return $this->hasMany(RoleUnitPermission::class);
    }
    
    /**
     * Ottiene il livello di accesso per una specifica unità e permesso
     */
    public function getPermissionForUnit(string $unitId, string $permissionName): ?string
    {
        $unitPerm = $this->unitPermissions()
            ->where('organizational_unit_id', $unitId)
            ->whereHas('permission', fn($q) => $q->where('name', $permissionName))
            ->first();
        
        return $unitPerm?->access_level;
    }
    
    /**
     * Verifica se il ruolo può vedere una specifica unità organizzativa
     */
    public function canViewUnit(string $unitId): bool
    {
        // Admin e amministratore vedono sempre tutto
        if (in_array($this->name, ['admin', 'amministratore'])) {
            return true;
        }
        
        return $this->visibleUnits()->where('organizational_units.id', $unitId)->exists();
    }
    
    /**
     * Ottiene gli ID delle unità visibili per questo ruolo
     */
    public function getVisibleUnitIds(): array
    {
        return $this->visibleUnits()->pluck('organizational_units.id')->toArray();
    }
    
    /**
     * Sincronizza le unità visibili per questo ruolo
     */
    public function syncVisibleUnits(array $unitIds): void
    {
        $this->visibleUnits()->sync($unitIds);
    }
    
    /**
     * Ottiene gli ID delle compagnie visibili per questo ruolo
     */
    public function getCompagnieVisibiliIds(): array
    {
        return $this->compagnieVisibili()->pluck('compagnie.id')->toArray();
    }
    
    /**
     * Verifica se il ruolo può vedere una specifica compagnia
     */
    public function canViewCompagnia(int $compagniaId): bool
    {
        // Admin e amministratore vedono sempre tutto
        if (in_array($this->name, ['admin', 'amministratore'])) {
            return true;
        }
        
        return $this->compagnieVisibili()->where('compagnie.id', $compagniaId)->exists();
    }
    
    /**
     * Sincronizza le compagnie visibili per questo ruolo
     */
    public function syncCompagnieVisibili(array $compagniaIds): void
    {
        $this->compagnieVisibili()->sync($compagniaIds);
    }

    /**
     * Verifica se il ruolo ha un determinato permesso
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Assegna un permesso al ruolo
     */
    public function givePermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    /**
     * Rimuove un permesso dal ruolo
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission->id);
    }
    
    /**
     * Verifica se questo è un ruolo amministrativo con visibilità globale
     */
    public function isAdminRole(): bool
    {
        return $this->is_global || in_array($this->name, ['admin', 'amministratore']);
    }
    
    /**
     * Ottiene i permessi raggruppati per categoria
     */
    public function getPermissionsByCategory(): array
    {
        return $this->permissions->groupBy('category')->toArray();
    }
    
    /**
     * Scope per ruoli globali
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }
    
    /**
     * Scope per ruoli di una specifica compagnia
     * @deprecated Usare scopeForUnit()
     */
    public function scopeForCompagnia($query, int $compagniaId)
    {
        return $query->where(function($q) use ($compagniaId) {
            $q->where('compagnia_id', $compagniaId)
              ->orWhere('is_global', true);
        });
    }
    
    /**
     * Scope per ruoli di una specifica unità organizzativa
     * Include i ruoli globali (is_global = true)
     * 
     * @param int|null $unitId ID dell'unità organizzativa
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUnit($query, ?int $unitId)
    {
        if (!$unitId) {
            return $query->where('is_global', true);
        }
        
        return $query->where(function($q) use ($unitId) {
            $q->where('organizational_unit_id', $unitId)
              ->orWhere('is_global', true);
        });
    }
    
    /**
     * Scope per ruoli accessibili all'utente corrente
     * Considera l'unità attiva nella sessione
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccessibleByCurrentUser($query)
    {
        $activeUnitId = activeUnitId();
        
        if (!$activeUnitId) {
            // Se nessuna unità attiva, mostra solo ruoli globali
            return $query->where('is_global', true);
        }
        
        // Ruoli dell'unità attiva + ruoli globali
        return $query->where(function($q) use ($activeUnitId) {
            $q->where('organizational_unit_id', $activeUnitId)
              ->orWhere('is_global', true);
        });
    }
    
    /**
     * Verifica se il ruolo appartiene a una specifica unità o è globale
     * 
     * @param int $unitId
     * @return bool
     */
    public function belongsToUnit(int $unitId): bool
    {
        return $this->is_global || $this->organizational_unit_id === $unitId;
    }
}
