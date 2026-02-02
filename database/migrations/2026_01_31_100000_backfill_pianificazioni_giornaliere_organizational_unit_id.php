<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Assegna organizational_unit_id alle pianificazioni giornaliere che lo hanno null,
     * usando l'unità del militare (o la mappatura compagnia -> unità legacy).
     * Così ogni macro-entità vede solo i propri impegni CPT.
     */
    public function up(): void
    {
        // Aggiorna usando l'organizational_unit_id del militare
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('
                UPDATE pianificazioni_giornaliere pg
                INNER JOIN militari m ON pg.militare_id = m.id
                SET pg.organizational_unit_id = m.organizational_unit_id, pg.updated_at = ?
                WHERE pg.organizational_unit_id IS NULL AND m.organizational_unit_id IS NOT NULL
            ', [now()]);
        } else {
            $rows = DB::select('
                SELECT pg.id, m.organizational_unit_id AS unit_id
                FROM pianificazioni_giornaliere pg
                INNER JOIN militari m ON pg.militare_id = m.id
                WHERE pg.organizational_unit_id IS NULL AND m.organizational_unit_id IS NOT NULL
            ');
            foreach ($rows as $row) {
                DB::table('pianificazioni_giornaliere')->where('id', $row->id)
                    ->update(['organizational_unit_id' => $row->unit_id, 'updated_at' => now()]);
            }
        }

        // Per i restanti (militare senza organizational_unit_id), assegna alla prima unità con legacy_compagnia_id = militare.compagnia_id
        $rows = DB::table('pianificazioni_giornaliere as pg')
            ->join('militari as m', 'pg.militare_id', '=', 'm.id')
            ->whereNull('pg.organizational_unit_id')
            ->whereNotNull('m.compagnia_id')
            ->select('pg.id', 'm.compagnia_id')
            ->get();

        foreach ($rows as $row) {
            $unitId = DB::table('organizational_units')
                ->where('legacy_compagnia_id', $row->compagnia_id)
                ->value('id');
            if ($unitId) {
                DB::table('pianificazioni_giornaliere')
                    ->where('id', $row->id)
                    ->update(['organizational_unit_id' => $unitId, 'updated_at' => now()]);
            }
        }

        // Ultimo fallback: assegna alla prima unità di depth 1 (battaglione) per i record ancora null
        $defaultUnitId = DB::table('organizational_units')
            ->where('depth', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        if ($defaultUnitId) {
            DB::table('pianificazioni_giornaliere')
                ->whereNull('organizational_unit_id')
                ->update(['organizational_unit_id' => $defaultUnitId, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Non reversibile (non azzeriamo organizational_unit_id)
    }
};
