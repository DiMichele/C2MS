<?php

namespace App\Services;

use App\Models\OrganizationalUnit;
use App\Models\Militare;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Helper per la generazione di export multi-unità.
 * 
 * Questo service fornisce metodi per aggregare dati di diverse unità
 * organizzative e generarli in formati adatti all'export Excel/PDF.
 */
class MultiUnitExportHelper
{
    /**
     * Ottiene le unità accessibili all'utente per l'export.
     * 
     * @param User $user
     * @return Collection
     */
    public static function getAccessibleUnitsForExport(User $user): Collection
    {
        if ($user->isGlobalAdmin()) {
            return OrganizationalUnit::with('type')
                ->active()
                ->orderBy('depth')
                ->orderBy('name')
                ->get();
        }

        $unitIds = $user->getVisibleUnitIds();
        
        return OrganizationalUnit::with('type')
            ->whereIn('id', $unitIds)
            ->active()
            ->orderBy('depth')
            ->orderBy('name')
            ->get();
    }

    /**
     * Raggruppa i militari per unità organizzativa.
     * 
     * @param Collection $militari
     * @return Collection Mappa unità -> militari
     */
    public static function groupByUnit(Collection $militari): Collection
    {
        return $militari->groupBy(function ($militare) {
            return $militare->organizational_unit_id ?? 'legacy_' . $militare->compagnia_id;
        })->map(function ($group, $key) {
            // Trova il nome dell'unità/compagnia
            if (str_starts_with($key, 'legacy_')) {
                $compagniaId = str_replace('legacy_', '', $key);
                $compagnia = \App\Models\Compagnia::find($compagniaId);
                $unitName = $compagnia?->nome ?? 'Senza compagnia';
                $unitType = 'legacy';
            } else {
                $unit = OrganizationalUnit::find($key);
                $unitName = $unit?->name ?? 'Unità non trovata';
                $unitType = $unit?->type?->name ?? 'N/D';
            }

            return [
                'unit_id' => $key,
                'unit_name' => $unitName,
                'unit_type' => $unitType,
                'militari' => $group,
                'count' => $group->count(),
            ];
        });
    }

    /**
     * Calcola le statistiche aggregate per l'export.
     * 
     * @param Collection $dataByUnit
     * @return array
     */
    public static function calculateAggregateStats(Collection $dataByUnit): array
    {
        $totals = [
            'units' => $dataByUnit->count(),
            'total_militari' => 0,
            'per_unit' => [],
        ];

        foreach ($dataByUnit as $unitData) {
            $totals['total_militari'] += $unitData['count'];
            $totals['per_unit'][$unitData['unit_name']] = $unitData['count'];
        }

        return $totals;
    }

    /**
     * Genera l'intestazione del foglio Excel per export multi-unità.
     * 
     * @param string $title Titolo del report
     * @param string $date Data del report
     * @param array $units Unità incluse nel report
     * @return array Righe dell'intestazione
     */
    public static function generateExcelHeader(string $title, string $date, array $units = []): array
    {
        $header = [
            [$title],
            ['Data: ' . $date],
            [''],
        ];

        if (!empty($units)) {
            $header[] = ['Unità incluse: ' . implode(', ', $units)];
            $header[] = [''];
        }

        return $header;
    }

    /**
     * Genera un separatore per unità nel foglio Excel.
     * 
     * @param string $unitName Nome dell'unità
     * @param int $count Numero di record
     * @return array
     */
    public static function generateUnitSeparator(string $unitName, int $count): array
    {
        return [
            [''],
            ["=== {$unitName} ({$count} record) ==="],
            [''],
        ];
    }

    /**
     * Genera il footer con totali aggregati.
     * 
     * @param array $stats Statistiche aggregate
     * @return array
     */
    public static function generateExcelFooter(array $stats): array
    {
        $footer = [
            [''],
            ['=== RIEPILOGO TOTALE ==='],
            [''],
        ];

        foreach ($stats['per_unit'] as $unitName => $count) {
            $footer[] = ["{$unitName}: {$count}"];
        }

        $footer[] = [''];
        $footer[] = ["TOTALE GENERALE: {$stats['total_militari']}"];

        return $footer;
    }

    /**
     * Verifica se l'utente può esportare dati di un'unità specifica.
     * 
     * @param User $user
     * @param int|null $unitId
     * @return bool
     */
    public static function canExportUnit(User $user, ?int $unitId): bool
    {
        if (!$unitId) {
            return true; // Export globale
        }

        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $user->canAccessUnit($unitId);
    }

    /**
     * Prepara i dati per l'export con indicazione dell'unità.
     * 
     * @param mixed $model Record da esportare
     * @return array Dati arricchiti con info unità
     */
    public static function enrichWithUnitInfo($model): array
    {
        $data = $model->toArray();

        // Aggiungi informazioni sull'unità
        $data['_unit_name'] = $model->organizationalUnit?->name 
            ?? $model->compagnia?->nome 
            ?? 'N/D';

        $data['_unit_type'] = $model->organizationalUnit?->type?->name ?? 'Legacy';

        $data['_is_active_unit'] = $model->organizational_unit_id === activeUnitId();

        return $data;
    }
}
