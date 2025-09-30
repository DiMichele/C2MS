<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Migration per popolare i tipi di servizio basati sui codici del file Excel
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Popolare la tabella tipi_servizio con i codici trovati nel file Excel
        $tipiServizio = [
            [
                'codice' => 'TO',
                'nome' => 'Turno Ordinario',
                'descrizione' => 'Servizio normale in sede',
                'colore_badge' => '#28a745',
                'categoria' => 'servizio',
                'ordine' => 1
            ],
            [
                'codice' => 'lo',
                'nome' => 'Libero Ordinario',
                'descrizione' => 'Giorno di riposo ordinario',
                'colore_badge' => '#17a2b8',
                'categoria' => 'permesso',
                'ordine' => 2
            ],
            [
                'codice' => 'S-UI',
                'nome' => 'Servizio - Unità Interna',
                'descrizione' => 'Servizio presso unità interna',
                'colore_badge' => '#ffc107',
                'categoria' => 'servizio',
                'ordine' => 3
            ],
            [
                'codice' => 'p',
                'nome' => 'Permesso',
                'descrizione' => 'Permesso giornaliero',
                'colore_badge' => '#fd7e14',
                'categoria' => 'permesso',
                'ordine' => 4
            ],
            [
                'codice' => 'S-UP',
                'nome' => 'Servizio - Unità Periferica',
                'descrizione' => 'Servizio presso unità periferica',
                'colore_badge' => '#6f42c1',
                'categoria' => 'servizio',
                'ordine' => 5
            ],
            [
                'codice' => 'S-CG',
                'nome' => 'Servizio - Comando Generale',
                'descrizione' => 'Servizio presso comando generale',
                'colore_badge' => '#e83e8c',
                'categoria' => 'servizio',
                'ordine' => 6
            ],
            [
                'codice' => 'S-SG',
                'nome' => 'Servizio - Stato Generale',
                'descrizione' => 'Servizio presso stato generale',
                'colore_badge' => '#20c997',
                'categoria' => 'servizio',
                'ordine' => 7
            ],
            [
                'codice' => 'MCM',
                'nome' => 'Missione Contro Mine',
                'descrizione' => 'Servizio specializzato contro mine',
                'colore_badge' => '#dc3545',
                'categoria' => 'missione',
                'ordine' => 8
            ],
            [
                'codice' => 'C-IED',
                'nome' => 'Counter-IED',
                'descrizione' => 'Operazioni counter-IED',
                'colore_badge' => '#6c757d',
                'categoria' => 'missione',
                'ordine' => 9
            ],
            [
                'codice' => 'L',
                'nome' => 'Lunedì',
                'descrizione' => 'Indicatore giorno Lunedì',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 10
            ],
            [
                'codice' => 'M',
                'nome' => 'Martedì/Mercoledì',
                'descrizione' => 'Indicatore giorno Martedì o Mercoledì',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 11
            ],
            [
                'codice' => 'G',
                'nome' => 'Giovedì',
                'descrizione' => 'Indicatore giorno Giovedì',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 12
            ],
            [
                'codice' => 'V',
                'nome' => 'Venerdì',
                'descrizione' => 'Indicatore giorno Venerdì',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 13
            ],
            [
                'codice' => 'S',
                'nome' => 'Sabato',
                'descrizione' => 'Indicatore giorno Sabato',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 14
            ],
            [
                'codice' => 'D',
                'nome' => 'Domenica',
                'descrizione' => 'Indicatore giorno Domenica',
                'colore_badge' => '#007bff',
                'categoria' => 'servizio',
                'ordine' => 15
            ]
        ];

        foreach ($tipiServizio as $tipo) {
            DB::table('tipi_servizio')->insert(array_merge($tipo, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Svuotare la tabella tipi_servizio
        DB::table('tipi_servizio')->truncate();
    }
};
