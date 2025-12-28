<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoIdoneita;

class TipiIdoneitaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipiIdoneita = [
            [
                'codice' => 'pefo',
                'nome' => 'PEFO',
                'descrizione' => 'Profilo di Efficienza Fisica Operativa',
                'durata_mesi' => 12,
                'ordine' => 1,
                'attivo' => true
            ],
            [
                'codice' => 'idoneita_mans',
                'nome' => 'Idoneità Mansione',
                'descrizione' => 'Idoneità alla mansione specifica',
                'durata_mesi' => 12,
                'ordine' => 2,
                'attivo' => true
            ],
            [
                'codice' => 'idoneita_smi',
                'nome' => 'Idoneità SMI',
                'descrizione' => 'Idoneità Servizio Militare Incondizionato',
                'durata_mesi' => 12,
                'ordine' => 3,
                'attivo' => true
            ],
            [
                'codice' => 'idoneita_guida',
                'nome' => 'Idoneità alla Guida',
                'descrizione' => 'Idoneità alla guida di mezzi militari',
                'durata_mesi' => 24,
                'ordine' => 4,
                'attivo' => true
            ],
            [
                'codice' => 'visita_medica_periodica',
                'nome' => 'Visita Medica Periodica',
                'descrizione' => 'Visita medica periodica obbligatoria',
                'durata_mesi' => 12,
                'ordine' => 5,
                'attivo' => true
            ]
        ];

        foreach ($tipiIdoneita as $tipo) {
            TipoIdoneita::updateOrCreate(
                ['codice' => $tipo['codice']],
                $tipo
            );
        }

        $this->command->info('Creati/Aggiornati ' . count($tipiIdoneita) . ' tipi di idoneità.');
    }
}

