<?php

namespace App\Services;

use App\Models\OrganizationalUnit;
use App\Models\UnitRolePermission;
use App\Models\UnitAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service per la gestione dei permessi gerarchici.
 * 
 * Gestisce i permessi basati sulla posizione nella gerarchia organizzativa,
 * con supporto per l'ereditarietà dei permessi dai nodi padre ai figli.
 */
class HierarchicalPermissionService
{
    /**
     * Cache TTL in secondi (5 minuti)
     */
    protected const CACHE_TTL = 300;

    /**
     * Verifica se un utente ha un permesso in una specifica unità.
     * Alias per hasPermissionOnUnit, accetta anche unit ID.
     *
     * @param User $user
     * @param string $permission Nome del permesso
     * @param int $unitId ID dell'unità
     * @return bool
     */
    public function userHasPermissionInUnit(User $user, string $permission, int $unitId): bool
    {
        $unit = OrganizationalUnit::find($unitId);
        if (!$unit) {
            return false;
        }
        return $this->hasPermissionOnUnit($user, $permission, $unit);
    }

    /**
     * Verifica se un utente ha un permesso su un'unità specifica.
     *
     * @param User $user
     * @param string $permission Nome del permesso
     * @param OrganizationalUnit $unit Unità su cui verificare
     * @return bool
     */
    public function hasPermissionOnUnit(User $user, string $permission, OrganizationalUnit $unit): bool
    {
        // Admin globali hanno sempre tutti i permessi
        if ($this->isGlobalAdmin($user)) {
            return true;
        }

        // Verifica permesso diretto sull'unità
        if ($this->hasDirectPermission($user, $permission, $unit)) {
            return true;
        }

        // Verifica permessi ereditati dagli antenati
        foreach ($unit->ancestors()->get() as $ancestor) {
            if ($this->hasInheritablePermission($user, $permission, $ancestor)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se un utente ha un permesso diretto su un'unità.
     */
    public function hasDirectPermission(User $user, string $permission, OrganizationalUnit $unit): bool
    {
        return UnitRolePermission::whereHas('role', function ($q) use ($user) {
                $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
            })
            ->where('unit_id', $unit->id)
            ->whereHas('permission', fn($q) => $q->where('name', $permission))
            ->where('access_type', 'grant')
            ->exists();
    }

    /**
     * Verifica se un utente ha un permesso ereditabile su un'unità.
     */
    public function hasInheritablePermission(User $user, string $permission, OrganizationalUnit $unit): bool
    {
        return UnitRolePermission::whereHas('role', function ($q) use ($user) {
                $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
            })
            ->where('unit_id', $unit->id)
            ->whereHas('permission', fn($q) => $q->where('name', $permission))
            ->where('access_type', 'grant')
            ->where('inherit_to_children', true)
            ->exists();
    }

    /**
     * Ottiene tutte le unità su cui un utente ha un determinato permesso.
     *
     * @param User $user
     * @param string $permission
     * @return Collection<OrganizationalUnit>
     */
    public function getUnitsWithPermission(User $user, string $permission): Collection
    {
        // Admin globali vedono tutto
        if ($this->isGlobalAdmin($user)) {
            return OrganizationalUnit::active()->get();
        }

        $cacheKey = "user_{$user->id}_perm_{$permission}_units";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $permission) {
            // Unità con permesso diretto
            $directUnitIds = $this->getDirectPermissionUnitIds($user, $permission);

            // Unità con permesso ereditato (discendenti di unità con permessi ereditabili)
            $inheritedUnitIds = $this->getInheritedPermissionUnitIds($user, $permission);

            $allUnitIds = array_unique(array_merge($directUnitIds, $inheritedUnitIds));

            return OrganizationalUnit::whereIn('id', $allUnitIds)->active()->get();
        });
    }

    /**
     * Ottiene gli ID delle unità dove l'utente ha un permesso diretto.
     */
    protected function getDirectPermissionUnitIds(User $user, string $permission): array
    {
        return UnitRolePermission::whereHas('role', function ($q) use ($user) {
                $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
            })
            ->whereNotNull('unit_id')
            ->whereHas('permission', fn($q) => $q->where('name', $permission))
            ->where('access_type', 'grant')
            ->pluck('unit_id')
            ->toArray();
    }

    /**
     * Ottiene gli ID delle unità dove l'utente ha un permesso ereditato.
     */
    protected function getInheritedPermissionUnitIds(User $user, string $permission): array
    {
        // Ottieni le unità con permessi ereditabili
        $inheritableUnitIds = UnitRolePermission::whereHas('role', function ($q) use ($user) {
                $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
            })
            ->whereNotNull('unit_id')
            ->whereHas('permission', fn($q) => $q->where('name', $permission))
            ->where('access_type', 'grant')
            ->where('inherit_to_children', true)
            ->pluck('unit_id')
            ->toArray();

        if (empty($inheritableUnitIds)) {
            return [];
        }

        // Ottieni tutti i discendenti
        return DB::table('unit_closure')
            ->whereIn('ancestor_id', $inheritableUnitIds)
            ->pluck('descendant_id')
            ->toArray();
    }

    /**
     * Ottiene tutti i permessi di un utente su un'unità specifica.
     *
     * @param User $user
     * @param OrganizationalUnit $unit
     * @return Collection<string> Nomi dei permessi
     */
    public function getPermissionsOnUnit(User $user, OrganizationalUnit $unit): Collection
    {
        if ($this->isGlobalAdmin($user)) {
            return \App\Models\Permission::pluck('name');
        }

        $cacheKey = "user_{$user->id}_unit_{$unit->id}_permissions";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $unit) {
            $permissions = collect();

            // Permessi diretti sull'unità
            $directPermissions = UnitRolePermission::whereHas('role', function ($q) use ($user) {
                    $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
                })
                ->where('unit_id', $unit->id)
                ->where('access_type', 'grant')
                ->with('permission')
                ->get()
                ->pluck('permission.name');

            $permissions = $permissions->merge($directPermissions);

            // Permessi ereditati dagli antenati
            $ancestorIds = $unit->ancestors()->pluck('id')->toArray();
            
            $inheritedPermissions = UnitRolePermission::whereHas('role', function ($q) use ($user) {
                    $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
                })
                ->whereIn('unit_id', $ancestorIds)
                ->where('access_type', 'grant')
                ->where('inherit_to_children', true)
                ->with('permission')
                ->get()
                ->pluck('permission.name');

            $permissions = $permissions->merge($inheritedPermissions);

            // Permessi globali (unit_id = null)
            $globalPermissions = UnitRolePermission::whereHas('role', function ($q) use ($user) {
                    $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
                })
                ->whereNull('unit_id')
                ->where('access_type', 'grant')
                ->with('permission')
                ->get()
                ->pluck('permission.name');

            $permissions = $permissions->merge($globalPermissions);

            return $permissions->unique();
        });
    }

    /**
     * Assegna un permesso a un ruolo su un'unità.
     *
     * @param int $roleId
     * @param int $permissionId
     * @param int|null $unitId (null per globale)
     * @param bool $inheritToChildren
     * @return UnitRolePermission
     */
    public function grantPermission(
        int $roleId,
        int $permissionId,
        ?int $unitId = null,
        bool $inheritToChildren = true
    ): UnitRolePermission {
        $this->clearCache();

        return UnitRolePermission::updateOrCreate(
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'unit_id' => $unitId,
            ],
            [
                'access_type' => 'grant',
                'inherit_to_children' => $inheritToChildren,
            ]
        );
    }

    /**
     * Revoca un permesso da un ruolo su un'unità.
     *
     * @param int $roleId
     * @param int $permissionId
     * @param int|null $unitId
     * @return bool
     */
    public function revokePermission(int $roleId, int $permissionId, ?int $unitId = null): bool
    {
        $this->clearCache();

        return UnitRolePermission::where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->where('unit_id', $unitId)
            ->delete() > 0;
    }

    /**
     * Nega esplicitamente un permesso (override su permessi ereditati).
     *
     * @param int $roleId
     * @param int $permissionId
     * @param int $unitId
     * @return UnitRolePermission
     */
    public function denyPermission(int $roleId, int $permissionId, int $unitId): UnitRolePermission
    {
        $this->clearCache();

        return UnitRolePermission::updateOrCreate(
            [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'unit_id' => $unitId,
            ],
            [
                'access_type' => 'deny',
                'inherit_to_children' => false,
            ]
        );
    }

    /**
     * Ottiene tutte le unità visibili all'utente (per qualsiasi permesso).
     *
     * @param User $user
     * @return array Array di ID unità
     */
    public function getVisibleUnitIds(User $user): array
    {
        if ($this->isGlobalAdmin($user)) {
            return OrganizationalUnit::pluck('id')->toArray();
        }

        $cacheKey = "user_{$user->id}_visible_units";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $visibleIds = [];

            // Unità assegnate direttamente
            $directUnitIds = UnitAssignment::where('assignable_type', User::class)
                ->where('assignable_id', $user->id)
                ->active()
                ->pluck('unit_id')
                ->toArray();

            // Unità con permessi (di qualsiasi tipo)
            $permissionUnitIds = UnitRolePermission::whereHas('role', function ($q) use ($user) {
                    $q->whereHas('users', fn($q2) => $q2->where('users.id', $user->id));
                })
                ->whereNotNull('unit_id')
                ->where('access_type', 'grant')
                ->pluck('unit_id')
                ->toArray();

            // Unità legacy (via compagnia_id)
            if ($user->compagnia_id) {
                $legacyUnitIds = OrganizationalUnit::where('legacy_compagnia_id', $user->compagnia_id)
                    ->pluck('id')
                    ->toArray();
                $directUnitIds = array_merge($directUnitIds, $legacyUnitIds);
            }

            $baseUnitIds = array_unique(array_merge($directUnitIds, $permissionUnitIds));

            if (empty($baseUnitIds)) {
                return [];
            }

            // Espandi per includere discendenti
            $visibleIds = DB::table('unit_closure')
                ->whereIn('ancestor_id', $baseUnitIds)
                ->pluck('descendant_id')
                ->toArray();

            return array_unique($visibleIds);
        });
    }

    /**
     * Verifica se l'utente è un admin globale.
     */
    protected function isGlobalAdmin(User $user): bool
    {
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('amministratore');
        }

        if (method_exists($user, 'hasGlobalVisibility')) {
            return $user->hasGlobalVisibility();
        }

        return false;
    }

    /**
     * Svuota la cache dei permessi.
     */
    public function clearCache(?User $user = null): void
    {
        if ($user) {
            Cache::forget("user_{$user->id}_visible_units");
            // Potremmo svuotare anche altre cache specifiche dell'utente
        } else {
            // Svuota tutte le cache dei permessi
            Cache::flush(); // Attenzione: svuota TUTTA la cache
        }
    }

    /**
     * Copia i permessi da un'unità a un'altra.
     *
     * @param OrganizationalUnit $source
     * @param OrganizationalUnit $target
     * @return int Numero di permessi copiati
     */
    public function copyPermissions(OrganizationalUnit $source, OrganizationalUnit $target): int
    {
        $sourcePermissions = UnitRolePermission::where('unit_id', $source->id)->get();
        $count = 0;

        foreach ($sourcePermissions as $perm) {
            UnitRolePermission::updateOrCreate(
                [
                    'role_id' => $perm->role_id,
                    'permission_id' => $perm->permission_id,
                    'unit_id' => $target->id,
                ],
                [
                    'access_type' => $perm->access_type,
                    'inherit_to_children' => $perm->inherit_to_children,
                ]
            );
            $count++;
        }

        $this->clearCache();
        return $count;
    }
}
