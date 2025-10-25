<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Assicurati che tutti i servizi turno siano presenti nei tipi_servizio con colore verde
        $serviziTurno = [
            [
                'codice' => 'G-BTG',
                'nome' => 'GRADUATO DI BTG',
                'colore_badge' => '#00b050', // Verde
                'categoria' => 'servizio',
                'descrizione' => 'Graduato di Battaglione',
                'ordine' => 100
            ],
            [
                'codice' => 'S-SI',
                'nome' => 'SERVIZIO INTERNO',
                'colore_badge' => '#00b050', // Verde
                'categoria' => 'servizio',
                'descrizione' => 'Servizio Interno Generico',
                'ordine' => 101
            ],
            [
                'codice' => 'CG',
                'nome' => 'CONDUTTORE GUARDIA',
                'colore_badge' => '#00b050', // Verde
                'categoria' => 'servizio',
                'descrizione' => 'Servizio Conduttore Guardia',
                'ordine' => 102
            ],
            [
                'codice' => 'PDT1',
                'nome' => 'PIAN DEL TERMINE',
                'colore_badge' => '#00b050', // Verde
                'categoria' => 'servizio',
                'descrizione' => 'Vigilanza Pian del Termine',
                'ordine' => 103
            ],
        ];

        foreach ($serviziTurno as $servizio) {
            DB::table('tipi_servizio')->updateOrInsert(
                ['codice' => $servizio['codice']],
                [
                    'nome' => $servizio['nome'],
                    'colore_badge' => $servizio['colore_badge'],
                    'categoria' => $servizio['categoria'],
                    'descrizione' => $servizio['descrizione'],
                    'ordine' => $servizio['ordine'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non rimuoviamo i servizi in caso di rollback
    }
};

