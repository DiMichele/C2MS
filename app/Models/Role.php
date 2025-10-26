<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'compagnia_id',
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
}
