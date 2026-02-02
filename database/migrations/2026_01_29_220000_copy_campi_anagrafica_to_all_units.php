<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Copia la configurazione colonne anagrafica dall'unità che le possiede
     * a tutte le altre unità di livello battaglione (depth=1), così ogni
     * battaglione (Leonessa, Tonale, ecc.) ha le stesse colonne.
     */
    public function up(): void
    {
        // Unità che hanno almeno una riga (la "sorgente" è la prima per ID)
        $sourceUnitId = DB::table('configurazione_campi_anagrafica')
            ->whereNotNull('organizational_unit_id')
            ->orderBy('organizational_unit_id')
            ->value('organizational_unit_id');

        if (!$sourceUnitId) {
            return;
        }

        $sourceRows = DB::table('configurazione_campi_anagrafica')
            ->where('organizational_unit_id', $sourceUnitId)
            ->get();

        if ($sourceRows->isEmpty()) {
            return;
        }

        // Tutte le unità di livello battaglione (depth=1) attive
        $targetUnitIds = DB::table('organizational_units')
            ->where('depth', 1)
            ->where('is_active', true)
            ->where('id', '!=', $sourceUnitId)
            ->pluck('id');

        $now = now();

        foreach ($targetUnitIds as $targetUnitId) {
            $exists = DB::table('configurazione_campi_anagrafica')
                ->where('organizational_unit_id', $targetUnitId)
                ->exists();

            if ($exists) {
                continue; // già ha configurazione
            }

            foreach ($sourceRows as $row) {
                DB::table('configurazione_campi_anagrafica')->insert([
                    'organizational_unit_id' => $targetUnitId,
                    'nome_campo' => $row->nome_campo,
                    'etichetta' => $row->etichetta,
                    'tipo_campo' => $row->tipo_campo,
                    'opzioni' => $row->opzioni,
                    'ordine' => $row->ordine,
                    'attivo' => $row->attivo,
                    'obbligatorio' => $row->obbligatorio,
                    'descrizione' => $row->descrizione,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse: non rimuoviamo le copie (non reversibile in modo sicuro).
     */
    public function down(): void
    {
        // Intenzionalmente vuoto
    }
};
