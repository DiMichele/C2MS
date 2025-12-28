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
        // Crea tabella per i tipi di idoneità
        if (!Schema::hasTable('tipi_idoneita')) {
            Schema::create('tipi_idoneita', function (Blueprint $table) {
                $table->id();
                $table->string('codice', 100)->unique()
                    ->comment('Codice univoco del tipo idoneità');
                $table->string('nome', 255)
                    ->comment('Nome del tipo di idoneità');
                $table->text('descrizione')->nullable()
                    ->comment('Descrizione dettagliata');
                $table->integer('durata_mesi')->default(12)
                    ->comment('Durata di validità in mesi (0 = nessuna scadenza)');
                $table->boolean('attivo')->default(true)
                    ->comment('Se il tipo di idoneità è attivo');
                $table->integer('ordine')->unsigned()->default(0)
                    ->comment('Ordine di visualizzazione');
                $table->timestamps();

                $table->index('attivo');
                $table->index('ordine');
            });
        }

        // Crea tabella per le scadenze idoneità
        if (!Schema::hasTable('scadenze_idoneita')) {
            Schema::create('scadenze_idoneita', function (Blueprint $table) {
                $table->id();
                $table->foreignId('militare_id')->constrained('militari')->onDelete('cascade')
                    ->comment('Militare a cui si riferisce la scadenza');
                $table->foreignId('tipo_idoneita_id')->constrained('tipi_idoneita')->onDelete('cascade')
                    ->comment('Tipo di idoneità');
                $table->date('data_conseguimento')->nullable()
                    ->comment('Data ultimo conseguimento/rinnovo');
                $table->timestamps();

                // Indici
                $table->unique(['militare_id', 'tipo_idoneita_id'], 'idx_militare_tipo_idoneita');
                $table->index('data_conseguimento');
            });
        }

        // Migra i dati esistenti dalla tabella scadenze_militari
        $this->migraDatiScadenzeIdoneita();
    }

    /**
     * Migra i dati esistenti dalla tabella scadenze_militari
     */
    private function migraDatiScadenzeIdoneita(): void
    {
        // Inserisci i tipi di idoneità base
        $tipiIdoneita = [
            ['codice' => 'pefo', 'nome' => 'PEFO', 'descrizione' => 'Profilo di Efficienza Fisica Operativa', 'durata_mesi' => 12, 'ordine' => 1],
            ['codice' => 'idoneita_mans', 'nome' => 'Idoneità Mansione', 'descrizione' => 'Idoneità alla mansione specifica', 'durata_mesi' => 12, 'ordine' => 2],
            ['codice' => 'idoneita_smi', 'nome' => 'Idoneità SMI', 'descrizione' => 'Idoneità Servizio Militare Incondizionato', 'durata_mesi' => 12, 'ordine' => 3],
        ];

        foreach ($tipiIdoneita as $tipo) {
            DB::table('tipi_idoneita')->insertOrIgnore([
                'codice' => $tipo['codice'],
                'nome' => $tipo['nome'],
                'descrizione' => $tipo['descrizione'],
                'durata_mesi' => $tipo['durata_mesi'],
                'ordine' => $tipo['ordine'],
                'attivo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Recupera i tipi idoneità appena creati
        $pefo = DB::table('tipi_idoneita')->where('codice', 'pefo')->first();
        $idoneitaMans = DB::table('tipi_idoneita')->where('codice', 'idoneita_mans')->first();
        $idoneitaSmi = DB::table('tipi_idoneita')->where('codice', 'idoneita_smi')->first();

        if (!$pefo || !$idoneitaMans || !$idoneitaSmi) {
            return;
        }

        // Verifica se esiste la tabella scadenze_militari
        if (!Schema::hasTable('scadenze_militari')) {
            return;
        }

        // Recupera tutte le scadenze militari
        $scadenzeMilitari = DB::table('scadenze_militari')->get();

        foreach ($scadenzeMilitari as $scadenza) {
            // Migra PEFO
            if (isset($scadenza->pefo_data_conseguimento) && $scadenza->pefo_data_conseguimento) {
                DB::table('scadenze_idoneita')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_idoneita_id' => $pefo->id,
                    'data_conseguimento' => $scadenza->pefo_data_conseguimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migra Idoneità Mansione
            if (isset($scadenza->idoneita_mans_data_conseguimento) && $scadenza->idoneita_mans_data_conseguimento) {
                DB::table('scadenze_idoneita')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_idoneita_id' => $idoneitaMans->id,
                    'data_conseguimento' => $scadenza->idoneita_mans_data_conseguimento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Migra Idoneità SMI
            if (isset($scadenza->idoneita_smi_data_conseguimento) && $scadenza->idoneita_smi_data_conseguimento) {
                DB::table('scadenze_idoneita')->insertOrIgnore([
                    'militare_id' => $scadenza->militare_id,
                    'tipo_idoneita_id' => $idoneitaSmi->id,
                    'data_conseguimento' => $scadenza->idoneita_smi_data_conseguimento,
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
        Schema::dropIfExists('scadenze_idoneita');
        Schema::dropIfExists('tipi_idoneita');
    }
};

