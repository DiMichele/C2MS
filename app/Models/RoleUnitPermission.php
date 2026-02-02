<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modello per i permessi specifici per ruolo-unità organizzativa.
 * 
 * Permette di definire permessi granulari per ogni combinazione di:
 * - Ruolo
 * - Unità Organizzativa
 * - Permesso (azione)
 * 
 * Con livelli di accesso: view, edit, admin
 * 
 * @property int $id
 * @property int $role_id
 * @property string $organizational_unit_id
 * @property int $permission_id
 * @property string $access_level
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RoleUnitPermission extends Model
{
    protected $table = 'role_unit_permissions';
    
    protected $fillable = [
        'role_id',
        'organizational_unit_id',
        'permission_id',
        'access_level',
    ];
    
    protected $casts = [
        'role_id' => 'integer',
        'organizational_unit_id' => 'integer',
        'permission_id' => 'integer',
    ];

    /**
     * Ruolo associato
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
    
    /**
     * Unità organizzativa associata
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }
    
    /**
     * Permesso associato
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
    
    /**
     * Verifica se il livello di accesso permette la modifica
     */
    public function canEdit(): bool
    {
        return in_array($this->access_level, ['edit', 'admin']);
    }
    
    /**
     * Verifica se il livello di accesso è admin
     */
    public function isAdmin(): bool
    {
        return $this->access_level === 'admin';
    }
    
    /**
     * Scope per filtrare per ruolo
     */
    public function scopeForRole($query, int $roleId)
    {
        return $query->where('role_id', $roleId);
    }
    
    /**
     * Scope per filtrare per unità
     */
    public function scopeForUnit($query, string $unitId)
    {
        return $query->where('organizational_unit_id', $unitId);
    }
    
    /**
     * Scope per filtrare per permesso
     */
    public function scopeForPermission($query, int $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }
}
