<?php

namespace App\Traits;

use App\Models\OrganizationalUnit;
use App\Services\HierarchicalPermissionService;
use Illuminate\Support\Collection;

/**
 * Trait per controller che necessitano di integrazione con la gerarchia organizzativa.
 * 
 * Fornisce metodi helper per:
 * - Ottenere unità visibili all'utente
 * - Filtrare dati per gerarchia
 * - Verificare permessi gerarchici
 */
trait UsesOrganizationalHierarchy
{
    /**
     * Ottiene le unità organizzative visibili all'utente corrente.
     *
     * @param bool $activeOnly Solo unità attive
     * @return Collection
     */
    protected function getVisibleUnits(bool $activeOnly = true): Collection
    {
        $user = auth()->user();

        if (!$user) {
            return collect();
        }

        if ($user->isGlobalAdmin()) {
            $query = OrganizationalUnit::query();
            if ($activeOnly) {
                $query->active();
            }
            return $query->orderBy('path')->get();
        }

        $visibleIds = $user->getVisibleUnitIds();

        $query = OrganizationalUnit::whereIn('id', $visibleIds);
        if ($activeOnly) {
            $query->active();
        }

        return $query->orderBy('path')->get();
    }

    /**
     * Ottiene le unità come opzioni per select/dropdown.
     *
     * @param bool $activeOnly Solo unità attive
     * @param bool $withPath Include il path nel nome
     * @return Collection
     */
    protected function getUnitOptions(bool $activeOnly = true, bool $withPath = false): Collection
    {
        return $this->getVisibleUnits($activeOnly)->map(function ($unit) use ($withPath) {
            $name = $unit->name;
            if ($withPath && $unit->depth > 0) {
                $indent = str_repeat('—', $unit->depth) . ' ';
                $name = $indent . $name;
            }

            return [
                'id' => $unit->id,
                'uuid' => $unit->uuid,
                'name' => $name,
                'type' => $unit->type?->name,
                'depth' => $unit->depth,
            ];
        });
    }

    /**
     * Ottiene l'albero delle unità per l'utente corrente.
     *
     * @param bool $activeOnly Solo unità attive
     * @return Collection
     */
    protected function getUnitTree(bool $activeOnly = true): Collection
    {
        $service = app(\App\Services\OrganizationalHierarchyService::class);
        return $service->getTreeForUser(auth()->user(), $activeOnly);
    }

    /**
     * Verifica se l'utente può accedere a un'unità.
     *
     * @param OrganizationalUnit|int $unit
     * @return bool
     */
    protected function canAccessUnit($unit): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $user->canAccessUnit($unit);
    }

    /**
     * Verifica se l'utente ha un permesso su un'unità specifica.
     *
     * @param string $permission
     * @param OrganizationalUnit|int $unit
     * @return bool
     */
    protected function hasUnitPermission(string $permission, $unit): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        return $user->hasPermissionOnUnit($permission, $unit);
    }

    /**
     * Trova l'unità organizzativa corrispondente a una compagnia legacy.
     *
     * @param int $compagniaId
     * @return OrganizationalUnit|null
     */
    protected function findUnitByLegacyCompagnia(int $compagniaId): ?OrganizationalUnit
    {
        return OrganizationalUnit::where('legacy_compagnia_id', $compagniaId)->first();
    }

    /**
     * Trova l'unità organizzativa corrispondente a un plotone legacy.
     *
     * @param int $plotoneId
     * @return OrganizationalUnit|null
     */
    protected function findUnitByLegacyPlotone(int $plotoneId): ?OrganizationalUnit
    {
        return OrganizationalUnit::where('legacy_plotone_id', $plotoneId)->first();
    }

    /**
     * Ottiene le compagnie legacy corrispondenti alle unità visibili.
     * Utile per la transizione dal vecchio al nuovo sistema.
     *
     * @return array Array di ID compagnie
     */
    protected function getVisibleLegacyCompagnieIds(): array
    {
        return $this->getVisibleUnits()
            ->whereNotNull('legacy_compagnia_id')
            ->pluck('legacy_compagnia_id')
            ->unique()
            ->toArray();
    }

    /**
     * Prepara i dati delle unità per la vista (con statistiche).
     *
     * @param OrganizationalUnit $unit
     * @return array
     */
    protected function prepareUnitViewData(OrganizationalUnit $unit): array
    {
        $service = app(\App\Services\OrganizationalHierarchyService::class);

        return [
            'unit' => $unit,
            'breadcrumb' => $unit->getBreadcrumb(),
            'stats' => $service->getUnitStats($unit),
            'children' => $unit->activeChildren()->with('type')->get(),
            'canEdit' => $this->hasUnitPermission('gerarchia.edit', $unit),
        ];
    }
}
