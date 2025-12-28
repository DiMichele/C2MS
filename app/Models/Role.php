<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * SUGECO: Modello Role con supporto segregazione compagnia
 * 
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property int|null $compagnia_id
 * @property bool $is_global Indica se il ruolo ha visibilità globale
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'compagnia_id',
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
     */
    public function compagnia()
    {
        return $this->belongsTo(Compagnia::class);
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
     */
    public function scopeForCompagnia($query, int $compagniaId)
    {
        return $query->where(function($q) use ($compagniaId) {
            $q->where('compagnia_id', $compagniaId)
              ->orWhere('is_global', true);
        });
    }
}
