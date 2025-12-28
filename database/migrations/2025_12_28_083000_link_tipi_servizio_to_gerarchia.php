<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collega automaticamente i tipi servizio ai codici gerarchia
 * basandosi sulla corrispondenza tra il nome del tipo servizio
 * e l'attivita_specifica del codice gerarchia
 */
return new class extends Migration
{
    public function up(): void
    {
        // Ottieni tutti i codici gerarchia
        $codiciGerarchia = DB::table('codici_servizio_gerarchia')
            ->select('id', 'attivita_specifica')
            ->get()
            ->keyBy(function($item) {
                return strtoupper(trim($item->attivita_specifica));
            });
        
        // Aggiorna i tipi servizio
        $tipiServizio = DB::table('tipi_servizio')->get();
        
        foreach ($tipiServizio as $tipo) {
            $nomeNormalizzato = strtoupper(trim($tipo->nome));
            
            // Cerca corrispondenza esatta
            if (isset($codiciGerarchia[$nomeNormalizzato])) {
                DB::table('tipi_servizio')
                    ->where('id', $tipo->id)
                    ->update(['codice_gerarchia_id' => $codiciGerarchia[$nomeNormalizzato]->id]);
                continue;
            }
            
            // Cerca corrispondenza parziale
            foreach ($codiciGerarchia as $key => $gerarchia) {
                if (str_contains($nomeNormalizzato, $key) || str_contains($key, $nomeNormalizzato)) {
                    DB::table('tipi_servizio')
                        ->where('id', $tipo->id)
                        ->update(['codice_gerarchia_id' => $gerarchia->id]);
                    break;
                }
            }
        }
        
        // Log dei tipi servizio senza collegamento
        $senzaCollegamento = DB::table('tipi_servizio')
            ->whereNull('codice_gerarchia_id')
            ->count();
        
        if ($senzaCollegamento > 0) {
            \Log::info("Tipi servizio senza collegamento gerarchia: {$senzaCollegamento}. Verrà usato il colore di default.");
        }
    }

    public function down(): void
    {
        // Rimuovi tutti i collegamenti
        DB::table('tipi_servizio')->update(['codice_gerarchia_id' => null]);
    }
};

