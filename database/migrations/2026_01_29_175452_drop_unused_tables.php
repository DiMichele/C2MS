<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Rimuove le tabelle inutilizzate per pulire il database.
     * Verifica che le tabelle siano vuote prima di eliminarle.
     */
    public function up(): void
    {
        $tabelleDaRimuovere = [
            'uffici',
            'incarichi',
            'certificati',
            'certificati_lavoratori',
            'idoneita',
            'presenze',
        ];

        foreach ($tabelleDaRimuovere as $tabella) {
            $this->rimuoviTabellaSeVuota($tabella);
        }
    }

    /**
     * Reverse the migrations.
     * 
     * Ricrea solo la tabella uffici (l'unica che esisteva)
     */
    public function down(): void
    {
        if (!Schema::hasTable('uffici')) {
            Schema::create('uffici', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('codice')->nullable();
                $table->unsignedBigInteger('polo_id')->nullable();
                $table->text('descrizione')->nullable();
                $table->boolean('attivo')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Rimuove una tabella solo se esiste e non contiene dati
     */
    private function rimuoviTabellaSeVuota(string $tabella): void
    {
        if (!Schema::hasTable($tabella)) {
            Log::info("Tabella {$tabella} non esiste, skip.");
            return;
        }

        $count = DB::table($tabella)->count();
        
        if ($count > 0) {
            Log::warning("Tabella {$tabella} contiene {$count} record, non rimossa.");
            return;
        }

        Schema::dropIfExists($tabella);
        Log::info("Tabella {$tabella} rimossa (era vuota).");
    }
};
