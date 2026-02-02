<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Sincronizza organizational_unit_id nelle configurazioni ruolini esistenti.
     * 
     * Per ogni configurazione con compagnia_id ma senza organizational_unit_id:
     * - Trova l'OrganizationalUnit corrispondente (via legacy_compagnia_id)
     * - Aggiorna il record con organizational_unit_id
     */
    public function up(): void
    {
        Log::info('SyncOrganizationalUnitIdInConfigurazioneRuolini: Inizio sincronizzazione');

        // Ottieni tutte le configurazioni con compagnia_id ma senza organizational_unit_id
        $configurazioni = DB::table('configurazione_ruolini')
            ->whereNotNull('compagnia_id')
            ->whereNull('organizational_unit_id')
            ->get();

        if ($configurazioni->isEmpty()) {
            Log::info('SyncOrganizationalUnitIdInConfigurazioneRuolini: Nessuna configurazione da sincronizzare');
            return;
        }

        Log::info("SyncOrganizationalUnitIdInConfigurazioneRuolini: Sincronizzazione di {$configurazioni->count()} configurazioni");

        $updated = 0;
        $skipped = 0;

        foreach ($configurazioni as $config) {
            // Trova l'OrganizationalUnit corrispondente
            $unit = DB::table('organizational_units')
                ->where('legacy_compagnia_id', $config->compagnia_id)
                ->first();

            if ($unit) {
                DB::table('configurazione_ruolini')
                    ->where('id', $config->id)
                    ->update([
                        'organizational_unit_id' => $unit->id,
                        'updated_at' => now(),
                    ]);
                $updated++;
            } else {
                Log::warning("SyncOrganizationalUnitIdInConfigurazioneRuolini: Nessuna unità trovata per compagnia_id={$config->compagnia_id}");
                $skipped++;
            }
        }

        Log::info("SyncOrganizationalUnitIdInConfigurazioneRuolini: Sincronizzazione completata. Aggiornate: {$updated}, Saltate: {$skipped}");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Riporta organizational_unit_id a null per le configurazioni sincronizzate
        DB::table('configurazione_ruolini')
            ->whereNotNull('organizational_unit_id')
            ->update([
                'organizational_unit_id' => null,
                'updated_at' => now(),
            ]);

        Log::info('SyncOrganizationalUnitIdInConfigurazioneRuolini: Rollback completato');
    }
};
