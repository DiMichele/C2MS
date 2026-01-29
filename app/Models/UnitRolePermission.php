<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modello per i permessi per ruolo su unità organizzative.
 * 
 * Permette di assegnare permessi specifici a ruoli su specifiche unità della gerarchia,
 * con supporto per l'ereditarietà ai nodi figli.
 *
 * @property int $id
 * @property int $role_id
 * @property int|null $unit_id
 * @property int $permission_id
 * @property bool $inherit_to_children
 * @property string $access_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UnitRolePermission extends Model
{
    protected $table = 'unit_role_permissions';

    protected $fillable = [
        'role_id',
        'unit_id',
        'permission_id',
        'inherit_to_children',
        'access_type',
    ];

    protected $casts = [
        'inherit_to_children' => 'boolean',
    ];

    protected $attributes = [
        'inherit_to_children' => true,
        'access_type' => 'grant',
    ];

    // =========================================================================
    // COSTANTI
    // =========================================================================

    public const ACCESS_GRANT = 'grant';
    public const ACCESS_DENY = 'deny';

    // =========================================================================
    // RELAZIONI
    // =========================================================================

    /**
     * Ruolo a cui si applica il permesso.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Unità organizzativa su cui si applica (null = globale).
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'unit_id');
    }

    /**
     * Permesso specifico.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo permessi concessi (grant).
     */
    public function scopeGranted(Builder $query): Builder
    {
        return $query->where('access_type', self::ACCESS_GRANT);
    }

    /**
     * Solo permessi negati (deny).
     */
    public function scopeDenied(Builder $query): Builder
    {
        return $query->where('access_type', self::ACCESS_DENY);
    }

    /**
     * Solo permessi ereditabili.
     */
    public function scopeInheritable(Builder $query): Builder
    {
        return $query->where('inherit_to_children', true);
    }

    /**
     * Solo permessi globali (unit_id = null).
     */
    public function scopeGlobal(Builder $query): Builder
    {
        return $query->whereNull('unit_id');
    }

    /**
     * Solo permessi su unità specifiche.
     */
    public function scopeOnUnit(Builder $query, int $unitId): Builder
    {
        return $query->where('unit_id', $unitId);
    }

    /**
     * Filtra per ruolo.
     */
    public function scopeForRole(Builder $query, int $roleId): Builder
    {
        return $query->where('role_id', $roleId);
    }

    /**
     * Filtra per permesso.
     */
    public function scopeForPermission(Builder $query, int|string $permission): Builder
    {
        if (is_string($permission)) {
            return $query->whereHas('permission', fn($q) => $q->where('name', $permission));
        }
        
        return $query->where('permission_id', $permission);
    }

    // =========================================================================
    // METODI
    // =========================================================================

    /**
     * Verifica se questo è un permesso concesso.
     */
    public function isGrant(): bool
    {
        return $this->access_type === self::ACCESS_GRANT;
    }

    /**
     * Verifica se questo è un permesso negato.
     */
    public function isDeny(): bool
    {
        return $this->access_type === self::ACCESS_DENY;
    }

    /**
     * Verifica se si applica a tutte le unità.
     */
    public function isGlobal(): bool
    {
        return $this->unit_id === null;
    }

    /**
     * Verifica se il permesso si applica a una specifica unità,
     * considerando l'ereditarietà.
     */
    public function appliesToUnit(OrganizationalUnit $unit): bool
    {
        // Permesso globale: si applica a tutto
        if ($this->isGlobal()) {
            return true;
        }

        // Permesso sulla stessa unità
        if ($this->unit_id === $unit->id) {
            return true;
        }

        // Se ereditabile, verifica se l'unità è discendente
        if ($this->inherit_to_children) {
            return $unit->isDescendantOf($this->unit);
        }

        return false;
    }
}
