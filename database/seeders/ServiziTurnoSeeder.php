<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ServizioTurno;

class ServiziTurnoSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $servizi = [
            ['nome' => 'Guardia', 'num_posti' => 2, 'ordine' => 1],
            ['nome' => 'Piantone', 'num_posti' => 1, 'ordine' => 2],
            ['nome' => 'Servizio Comando', 'num_posti' => 1, 'ordine' => 3],
            ['nome' => 'Autista', 'num_posti' => 1, 'ordine' => 4],
            ['nome' => 'Reperibilità', 'num_posti' => 2, 'ordine' => 5],
        ];

        foreach ($servizi as $servizio) {
            ServizioTurno::updateOrCreate(
                ['nome' => $servizio['nome']],
                [
                    'num_posti' => $servizio['num_posti'],
                    'ordine' => $servizio['ordine'],
                    'attivo' => true,
                ]
            );
        }

        $this->command->info('✅ Creati ' . count($servizi) . ' servizi turno di base');
    }
}
