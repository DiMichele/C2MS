<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Completa i collegamenti tra tipi_servizio e codici_servizio_gerarchia.
     * Per ogni tipo servizio senza collegamento, crea un codice gerarchia corrispondente.
     */
    public function up(): void
    {
        // Definisci i colori di default per categoria
        $coloriCategorie = [
            'S-' => '#007bff',      // Servizi: blu
            'SI-' => '#17a2b8',     // Servizi isolati: ciano
            'APS' => '#28a745',     // Attività sanitarie: verde
            'AL-' => '#6f42c1',     // Abilitazioni/Lezioni: viola
            'AC-' => '#fd7e14',     // Attività corsi: arancione
            'NULL' => '#6c757d',    // Disponibilità: grigio
            'default' => '#20c997' // Default: teal
        ];

        // Trova tutti i tipi servizio senza collegamento
        $tipiSenzaCollegamento = DB::table('tipi_servizio')
            ->whereNull('codice_gerarchia_id')
            ->get();

        foreach ($tipiSenzaCollegamento as $tipo) {
            // Determina il colore in base al prefisso del codice
            $colore = $coloriCategorie['default'];
            foreach ($coloriCategorie as $prefix => $color) {
                if (str_starts_with($tipo->codice, $prefix)) {
                    $colore = $color;
                    break;
                }
            }

            // Determina la categoria impiego
            $impiego = 'PRESENTE_SERVIZIO';
            if (str_starts_with($tipo->codice, 'NULL')) {
                $impiego = 'DISPONIBILE';
            } elseif (str_starts_with($tipo->codice, 'AL-') || str_starts_with($tipo->codice, 'AC-')) {
                $impiego = 'DISPONIBILE_ESIGENZA';
            } elseif (str_starts_with($tipo->codice, 'APS')) {
                $impiego = 'DISPONIBILE_ESIGENZA';
            }

            // Verifica se esiste già un codice gerarchia con questo nome
            $esistente = DB::table('codici_servizio_gerarchia')
                ->where('attivita_specifica', $tipo->nome)
                ->first();

            if ($esistente) {
                // Collega al codice esistente
                DB::table('tipi_servizio')
                    ->where('id', $tipo->id)
                    ->update(['codice_gerarchia_id' => $esistente->id]);
            } else {
                // Crea nuovo codice gerarchia
                $nuovoId = DB::table('codici_servizio_gerarchia')->insertGetId([
                    'codice' => $tipo->codice,
                    'impiego' => $impiego,
                    'attivita_specifica' => $tipo->nome,
                    'colore_badge' => $colore,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Collega il tipo servizio al nuovo codice
                DB::table('tipi_servizio')
                    ->where('id', $tipo->id)
                    ->update(['codice_gerarchia_id' => $nuovoId]);
            }
        }

        // Assicurati che il tipo "DISPONIBILITA' TOT" usi un colore neutro
        DB::table('codici_servizio_gerarchia')
            ->where('attivita_specifica', "DISPONIBILITA' TOT")
            ->update(['colore_badge' => '#6c757d', 'impiego' => 'DISPONIBILE']);
    }

    public function down(): void
    {
        // Rimuovi i codici gerarchia creati da questa migrazione
        // (quelli che non hanno un impiego predefinito standard)
        $tipiServizio = DB::table('tipi_servizio')
            ->whereNotNull('codice_gerarchia_id')
            ->get();

        foreach ($tipiServizio as $tipo) {
            $codice = DB::table('codici_servizio_gerarchia')
                ->where('id', $tipo->codice_gerarchia_id)
                ->first();

            // Verifica se questo codice è stato creato dalla migrazione
            // (quelli con attivita_specifica uguale al nome del tipo)
            if ($codice && $codice->attivita_specifica === $tipo->nome) {
                DB::table('tipi_servizio')
                    ->where('id', $tipo->id)
                    ->update(['codice_gerarchia_id' => null]);
            }
        }
    }
};

