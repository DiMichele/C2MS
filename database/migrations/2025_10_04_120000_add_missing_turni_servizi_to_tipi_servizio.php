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
        // Verifica e aggiungi TUTTI i servizi turno come tipi_servizio
        // Questi devono essere VERDI e nella categoria SERVIZIO
        $serviziMancanti = [
            [
                'codice' => 'NVA',
                'nome' => 'NUCLEO VIGILANZA ARMATA',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Nucleo Vigilanza Armata D\'Avanzo',
                'ordine' => 104
            ],
            [
                'codice' => 'NS-DA',
                'nome' => 'NUCLEO SORV. D\'AVANZO',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Nucleo Sorveglianza D\'Avanzo (07:30-17:00)',
                'ordine' => 105
            ],
            [
                'codice' => 'PDT',
                'nome' => 'VIGILANZA PIAN TERMINE',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Vigilanza Pian del Termine',
                'ordine' => 106
            ],
            [
                'codice' => 'AA',
                'nome' => 'ADDETTO ANTINCENDIO',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Addetto Antincendio',
                'ordine' => 107
            ],
            [
                'codice' => 'VS-CETLI',
                'nome' => 'VIGILANZA CETLI',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Vigilanza Settimanale CETLI',
                'ordine' => 108
            ],
            [
                'codice' => 'CORR',
                'nome' => 'CORRIERE',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Servizio Corriere',
                'ordine' => 109
            ],
            [
                'codice' => 'NDI',
                'nome' => 'NUCLEO DIF. IMMEDIATA',
                'colore_badge' => '#00b050',
                'categoria' => 'servizio',
                'descrizione' => 'Nucleo Difesa Immediata',
                'ordine' => 110
            ],
        ];

        foreach ($serviziMancanti as $servizio) {
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

