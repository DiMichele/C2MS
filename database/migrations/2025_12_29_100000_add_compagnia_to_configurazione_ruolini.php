<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge la segregazione per compagnia alla configurazione ruolini.
     * Ogni compagnia può avere le proprie regole per determinare presenza/assenza.
     */
    public function up(): void
    {
        // 1. Rimuovi il vincolo unique esistente
        Schema::table('configurazione_ruolini', function (Blueprint $table) {
            $table->dropUnique(['tipo_servizio_id']);
        });

        // 2. Aggiungi la colonna compagnia_id
        Schema::table('configurazione_ruolini', function (Blueprint $table) {
            $table->foreignId('compagnia_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('compagnie')
                  ->onDelete('cascade');
            
            // Nuovo indice unique composto: ogni compagnia può avere una sola configurazione per tipo servizio
            $table->unique(['compagnia_id', 'tipo_servizio_id'], 'config_ruolini_compagnia_tipo_unique');
            
            // Indice per query veloci per compagnia
            $table->index('compagnia_id', 'config_ruolini_compagnia_idx');
        });

        // 3. Crea la tabella compagnia_settings per impostazioni generali (estensibile)
        Schema::create('compagnia_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compagnia_id')
                  ->unique()
                  ->constrained('compagnie')
                  ->onDelete('cascade');
            
            // Impostazioni JSON flessibili per future estensioni
            $table->json('settings')->nullable();
            
            // Cache delle regole ruolini (per performance)
            $table->json('ruolini_cache')->nullable();
            
            // Timestamp ultimo aggiornamento cache
            $table->timestamp('cache_updated_at')->nullable();
            
            $table->timestamps();
        });

        // 4. Migra le configurazioni esistenti assegnandole a tutte le compagnie
        $this->migrateExistingConfigurations();
    }

    /**
     * Migra le configurazioni esistenti (globali) a tutte le compagnie
     */
    private function migrateExistingConfigurations(): void
    {
        $compagnie = DB::table('compagnie')->pluck('id');
        $configurazioniEsistenti = DB::table('configurazione_ruolini')
            ->whereNull('compagnia_id')
            ->get();

        if ($configurazioniEsistenti->isEmpty() || $compagnie->isEmpty()) {
            return;
        }

        // Per ogni compagnia, copia le configurazioni globali
        foreach ($compagnie as $compagniaId) {
            foreach ($configurazioniEsistenti as $config) {
                // Verifica se esiste già una configurazione per questa compagnia/tipo
                $exists = DB::table('configurazione_ruolini')
                    ->where('compagnia_id', $compagniaId)
                    ->where('tipo_servizio_id', $config->tipo_servizio_id)
                    ->exists();

                if (!$exists) {
                    DB::table('configurazione_ruolini')->insert([
                        'compagnia_id' => $compagniaId,
                        'tipo_servizio_id' => $config->tipo_servizio_id,
                        'stato_presenza' => $config->stato_presenza,
                        'note' => $config->note,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Rimuovi le configurazioni globali (senza compagnia_id)
        DB::table('configurazione_ruolini')->whereNull('compagnia_id')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi tabella impostazioni
        Schema::dropIfExists('compagnia_settings');

        // Rimuovi colonna e indici dalla configurazione ruolini
        Schema::table('configurazione_ruolini', function (Blueprint $table) {
            $table->dropForeign(['compagnia_id']);
            $table->dropUnique('config_ruolini_compagnia_tipo_unique');
            $table->dropIndex('config_ruolini_compagnia_idx');
            $table->dropColumn('compagnia_id');
            
            // Ripristina indice unique originale
            $table->unique('tipo_servizio_id');
        });
    }
};

