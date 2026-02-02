<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migrazione per aggiungere organizational_unit_id ai modelli admin
 * 
 * Questo permette l'isolamento completo delle configurazioni per unità organizzativa:
 * - Codici CPT (CodiciServizioGerarchia) - già ha il campo, ma deve diventare NOT NULL
 * - Tipi Servizio (TipoServizio)
 * - Plotoni
 * - Poli (Uffici)
 * - Mansioni (Incarichi)
 * - Prenotazioni Approntamento
 * - Teatri Operativi
 * - Scadenze Approntamento
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================================
        // 1. TIPI SERVIZIO
        // =========================================================================
        if (Schema::hasTable('tipi_servizio') && !Schema::hasColumn('tipi_servizio', 'organizational_unit_id')) {
            Schema::table('tipi_servizio', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_tipi_servizio_org_unit');
            });
        }

        // =========================================================================
        // 2. PLOTONI
        // =========================================================================
        if (Schema::hasTable('plotoni') && !Schema::hasColumn('plotoni', 'organizational_unit_id')) {
            Schema::table('plotoni', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('compagnia_id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_plotoni_org_unit');
            });
        }

        // =========================================================================
        // 3. POLI (UFFICI)
        // =========================================================================
        if (Schema::hasTable('poli') && !Schema::hasColumn('poli', 'organizational_unit_id')) {
            Schema::table('poli', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_poli_org_unit');
            });
        }

        // =========================================================================
        // 4. MANSIONI (INCARICHI)
        // =========================================================================
        if (Schema::hasTable('mansioni') && !Schema::hasColumn('mansioni', 'organizational_unit_id')) {
            Schema::table('mansioni', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_mansioni_org_unit');
            });
        }

        // =========================================================================
        // 5. PRENOTAZIONI APPRONTAMENTI
        // =========================================================================
        if (Schema::hasTable('prenotazioni_approntamenti') && !Schema::hasColumn('prenotazioni_approntamenti', 'organizational_unit_id')) {
            Schema::table('prenotazioni_approntamenti', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_prenotazioni_approntamenti_org_unit');
            });
        }

        // =========================================================================
        // 6. TEATRI OPERATIVI
        // =========================================================================
        if (Schema::hasTable('teatri_operativi') && !Schema::hasColumn('teatri_operativi', 'organizational_unit_id')) {
            Schema::table('teatri_operativi', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_teatri_operativi_org_unit');
            });
        }

        // =========================================================================
        // 7. SCADENZE APPRONTAMENTI
        // =========================================================================
        if (Schema::hasTable('scadenze_approntamenti') && !Schema::hasColumn('scadenze_approntamenti', 'organizational_unit_id')) {
            Schema::table('scadenze_approntamenti', function (Blueprint $table) {
                $table->foreignId('organizational_unit_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('organizational_units')
                    ->nullOnDelete();
                $table->index('organizational_unit_id', 'idx_scadenze_approntamenti_org_unit');
            });
        }

        // =========================================================================
        // BACKFILL DATA - Mappa compagnia_id -> organizational_unit_id
        // =========================================================================
        $this->backfillData();
    }

    /**
     * Backfill organizational_unit_id dai dati esistenti
     */
    private function backfillData(): void
    {
        Log::info('[Migration] Inizio backfill organizational_unit_id per modelli admin');

        // Mappa compagnia_id -> organizational_unit_id
        $compagniaToUnitMap = DB::table('organizational_units')
            ->whereNotNull('legacy_compagnia_id')
            ->pluck('id', 'legacy_compagnia_id')
            ->toArray();

        // Trova l'unità di default (prima unità con depth=1, ovvero Battaglione)
        $defaultUnitId = DB::table('organizational_units')
            ->where('depth', 1)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        Log::info('[Migration] Mappa compagnie trovate: ' . count($compagniaToUnitMap));
        Log::info('[Migration] Default unit ID: ' . $defaultUnitId);

        // -------------------------------------------------------------------------
        // PLOTONI: mappa da compagnia_id
        // -------------------------------------------------------------------------
        if (Schema::hasTable('plotoni') && Schema::hasColumn('plotoni', 'compagnia_id')) {
            $updated = 0;
            $plotoni = DB::table('plotoni')
                ->whereNull('organizational_unit_id')
                ->whereNotNull('compagnia_id')
                ->get();

            foreach ($plotoni as $plotone) {
                $unitId = $compagniaToUnitMap[$plotone->compagnia_id] ?? $defaultUnitId;
                if ($unitId) {
                    DB::table('plotoni')
                        ->where('id', $plotone->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Plotoni aggiornati: {$updated}");
        }

        // -------------------------------------------------------------------------
        // POLI (UFFICI): mappa da compagnia_id se esiste, altrimenti default
        // -------------------------------------------------------------------------
        if (Schema::hasTable('poli')) {
            $updated = 0;
            $poli = DB::table('poli')
                ->whereNull('organizational_unit_id')
                ->get();

            foreach ($poli as $polo) {
                $unitId = null;
                if (isset($polo->compagnia_id) && isset($compagniaToUnitMap[$polo->compagnia_id])) {
                    $unitId = $compagniaToUnitMap[$polo->compagnia_id];
                } else {
                    $unitId = $defaultUnitId;
                }
                
                if ($unitId) {
                    DB::table('poli')
                        ->where('id', $polo->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Poli (Uffici) aggiornati: {$updated}");
        }

        // -------------------------------------------------------------------------
        // MANSIONI (INCARICHI): assegna a default unit (sono tipicamente globali)
        // -------------------------------------------------------------------------
        if (Schema::hasTable('mansioni') && $defaultUnitId) {
            $updated = DB::table('mansioni')
                ->whereNull('organizational_unit_id')
                ->update(['organizational_unit_id' => $defaultUnitId]);
            Log::info("[Migration] Mansioni (Incarichi) aggiornate: {$updated}");
        }

        // -------------------------------------------------------------------------
        // TIPI SERVIZIO: sincronizza da CodiciServizioGerarchia se collegati
        // -------------------------------------------------------------------------
        if (Schema::hasTable('tipi_servizio')) {
            // Prima prova a mappare da codici_servizio_gerarchia tramite codice_gerarchia_id
            $updated = 0;
            $tipiServizio = DB::table('tipi_servizio as ts')
                ->leftJoin('codici_servizio_gerarchia as csg', 'ts.codice_gerarchia_id', '=', 'csg.id')
                ->whereNull('ts.organizational_unit_id')
                ->select('ts.id', 'csg.organizational_unit_id as csg_unit_id')
                ->get();

            foreach ($tipiServizio as $tipo) {
                $unitId = $tipo->csg_unit_id ?? $defaultUnitId;
                if ($unitId) {
                    DB::table('tipi_servizio')
                        ->where('id', $tipo->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Tipi Servizio aggiornati: {$updated}");
        }

        // -------------------------------------------------------------------------
        // PRENOTAZIONI APPRONTAMENTI: mappa da militare.organizational_unit_id
        // -------------------------------------------------------------------------
        if (Schema::hasTable('prenotazioni_approntamenti')) {
            $updated = 0;
            $prenotazioni = DB::table('prenotazioni_approntamenti as pa')
                ->leftJoin('militari as m', 'pa.militare_id', '=', 'm.id')
                ->whereNull('pa.organizational_unit_id')
                ->select('pa.id', 'm.organizational_unit_id as militare_unit_id', 'm.compagnia_id')
                ->get();

            foreach ($prenotazioni as $prenotazione) {
                $unitId = $prenotazione->militare_unit_id 
                    ?? ($compagniaToUnitMap[$prenotazione->compagnia_id] ?? null)
                    ?? $defaultUnitId;
                    
                if ($unitId) {
                    DB::table('prenotazioni_approntamenti')
                        ->where('id', $prenotazione->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Prenotazioni Approntamenti aggiornate: {$updated}");
        }

        // -------------------------------------------------------------------------
        // TEATRI OPERATIVI: mappa da compagnia_id
        // -------------------------------------------------------------------------
        if (Schema::hasTable('teatri_operativi') && Schema::hasColumn('teatri_operativi', 'compagnia_id')) {
            $updated = 0;
            $teatri = DB::table('teatri_operativi')
                ->whereNull('organizational_unit_id')
                ->get();

            foreach ($teatri as $teatro) {
                $unitId = null;
                if ($teatro->compagnia_id && isset($compagniaToUnitMap[$teatro->compagnia_id])) {
                    $unitId = $compagniaToUnitMap[$teatro->compagnia_id];
                } else {
                    $unitId = $defaultUnitId;
                }
                
                if ($unitId) {
                    DB::table('teatri_operativi')
                        ->where('id', $teatro->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Teatri Operativi aggiornati: {$updated}");
        }

        // -------------------------------------------------------------------------
        // SCADENZE APPRONTAMENTI: mappa da militare.organizational_unit_id
        // -------------------------------------------------------------------------
        if (Schema::hasTable('scadenze_approntamenti')) {
            $updated = 0;
            $scadenze = DB::table('scadenze_approntamenti as sa')
                ->leftJoin('militari as m', 'sa.militare_id', '=', 'm.id')
                ->whereNull('sa.organizational_unit_id')
                ->select('sa.id', 'm.organizational_unit_id as militare_unit_id', 'm.compagnia_id')
                ->get();

            foreach ($scadenze as $scadenza) {
                $unitId = $scadenza->militare_unit_id 
                    ?? ($compagniaToUnitMap[$scadenza->compagnia_id] ?? null)
                    ?? $defaultUnitId;
                    
                if ($unitId) {
                    DB::table('scadenze_approntamenti')
                        ->where('id', $scadenza->id)
                        ->update(['organizational_unit_id' => $unitId]);
                    $updated++;
                }
            }
            Log::info("[Migration] Scadenze Approntamenti aggiornate: {$updated}");
        }

        // -------------------------------------------------------------------------
        // CODICI SERVIZIO GERARCHIA: assegna default unit a quelli NULL (erano globali)
        // -------------------------------------------------------------------------
        if (Schema::hasTable('codici_servizio_gerarchia') && $defaultUnitId) {
            $updated = DB::table('codici_servizio_gerarchia')
                ->whereNull('organizational_unit_id')
                ->update(['organizational_unit_id' => $defaultUnitId]);
            Log::info("[Migration] Codici Servizio Gerarchia (ex-globali) aggiornati: {$updated}");
        }

        Log::info('[Migration] Backfill completato');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi le colonne aggiunte (in ordine inverso)
        
        if (Schema::hasTable('scadenze_approntamenti') && Schema::hasColumn('scadenze_approntamenti', 'organizational_unit_id')) {
            Schema::table('scadenze_approntamenti', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_scadenze_approntamenti_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('teatri_operativi') && Schema::hasColumn('teatri_operativi', 'organizational_unit_id')) {
            Schema::table('teatri_operativi', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_teatri_operativi_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('prenotazioni_approntamenti') && Schema::hasColumn('prenotazioni_approntamenti', 'organizational_unit_id')) {
            Schema::table('prenotazioni_approntamenti', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_prenotazioni_approntamenti_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('mansioni') && Schema::hasColumn('mansioni', 'organizational_unit_id')) {
            Schema::table('mansioni', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_mansioni_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('poli') && Schema::hasColumn('poli', 'organizational_unit_id')) {
            Schema::table('poli', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_poli_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('plotoni') && Schema::hasColumn('plotoni', 'organizational_unit_id')) {
            Schema::table('plotoni', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_plotoni_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }

        if (Schema::hasTable('tipi_servizio') && Schema::hasColumn('tipi_servizio', 'organizational_unit_id')) {
            Schema::table('tipi_servizio', function (Blueprint $table) {
                $table->dropForeign(['organizational_unit_id']);
                $table->dropIndex('idx_tipi_servizio_org_unit');
                $table->dropColumn('organizational_unit_id');
            });
        }
    }
};
