<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi campo durata_mesi alla tabella tipi_poligono
        Schema::table('tipi_poligono', function (Blueprint $table) {
            if (!Schema::hasColumn('tipi_poligono', 'durata_mesi')) {
                $table->integer('durata_mesi')->default(6)->after('punteggio_massimo')
                    ->comment('Durata di validità in mesi (es. 6 per 6 mesi)');
            }
            if (!Schema::hasColumn('tipi_poligono', 'codice')) {
                $table->string('codice', 100)->unique()->after('id')
                    ->comment('Codice univoco del tipo poligono');
            }
            if (!Schema::hasColumn('tipi_poligono', 'ordine')) {
                $table->integer('ordine')->unsigned()->default(0)->after('attivo')
                    ->comment('Ordine di visualizzazione');
            }
        });

        // Crea tabella per le scadenze poligoni (simile a scadenze_corsi_spp)
        if (!Schema::hasTable('scadenze_poligoni')) {
            Schema::create('scadenze_poligoni', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade')
                    ->comment('Militare a cui si riferisce la scadenza');
                $table->foreignId('tipo_poligono_id')->constrained('tipi_poligono')->onDelete('cascade')
                    ->comment('Tipo di poligono');
                $table->date('data_conseguimento')->nullable()
                    ->comment('Data ultimo poligono effettuato');
                $table->timestamps();

                // Indici
                $table->unique(['militare_id', 'tipo_poligono_id'], 'idx_militare_tipo_poligono');
                $table->index('data_conseguimento');
            });
        }

        // Aggiorna tipi poligono esistenti con codici univoci e durata
        $tipiPoligono = DB::table('tipi_poligono')->get();
        
        $mapping = [
            'Tiri di Approntamento' => ['codice' => 'tiri_approntamento', 'durata' => 6, 'ordine' => 1],
            'Mantenimento Arma Lunga' => ['codice' => 'mantenimento_arma_lunga', 'durata' => 6, 'ordine' => 2],
            'Mantenimento Arma Corta' => ['codice' => 'mantenimento_arma_corta', 'durata' => 6, 'ordine' => 3],
        ];

        foreach ($tipiPoligono as $tipo) {
            $config = $mapping[$tipo->nome] ?? [
                'codice' => \Str::slug($tipo->nome, '_'),
                'durata' => 6,
                'ordine' => 99
            ];

            DB::table('tipi_poligono')
                ->where('id', $tipo->id)
                ->update([
                    'codice' => $config['codice'],
                    'durata_mesi' => $config['durata'],
                    'ordine' => $config['ordine']
                ]);
        }

        // Migra i dati esistenti da scadenze_militari a scadenze_poligoni
        $this->migraDatiScadenzePoligoni();
    }

    /**
     * Migra i dati esistenti dalla tabella scadenze_militari
     */
    private function migraDatiScadenzePoligoni(): void
    {
        // Recupera i tipi poligono
        $tiriApprontamento = DB::table('tipi_poligono')->where('codice', 'tiri_approntamento')->first();
        $mantenimentoLunga = DB::table('tipi_poligono')->where('codice', 'mantenimento_arma_lunga')->first();
        $mantenimentoCorta = DB::table('tipi_poligono')->where('codice', 'mantenimento_arma_corta')->first();

        if (!$tiriApprontamento || !$mantenimentoLunga || !$mantenimentoCorta) {
            return;
        }

        // Recupera tutte le scadenze militari
        $scadenzeMilitari = DB::table('scadenze_militari')->get();

        foreach ($scadenzeMilitari as $scadenza) {
            // Migra tiri approntamento
            if ($scadenza->tiri_approntamento_data_conseguimento) {
                DB::table('scadenze_poligoni')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_poligono_id' => $tiriApprontamento->id,
                    'data_conseguimento' => $scadenza->tiri_approntamento_data_conseguimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migra mantenimento arma lunga
            if ($scadenza->mantenimento_arma_lunga_data_conseguimento) {
                DB::table('scadenze_poligoni')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_poligono_id' => $mantenimentoLunga->id,
                    'data_conseguimento' => $scadenza->mantenimento_arma_lunga_data_conseguimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migra mantenimento arma corta
            if ($scadenza->mantenimento_arma_corta_data_conseguimento) {
                DB::table('scadenze_poligoni')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_poligono_id' => $mantenimentoCorta->id,
                    'data_conseguimento' => $scadenza->mantenimento_arma_corta_data_conseguimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scadenze_poligoni');
        
        Schema::table('tipi_poligono', function (Blueprint $table) {
            $table->dropColumn(['durata_mesi', 'codice', 'ordine']);
        });
    }
};

