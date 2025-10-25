<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\Polo;
use App\Models\Militare;

class CompagnieCorretteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Elimina la compagnia esistente e ricrea le tre compagnie corrette
        $compagniaEsistente = Compagnia::where('nome', '124^ Compagnia Trasmissioni')->first();
        if ($compagniaEsistente) {
            // Sposta i militari esistenti alla 124^ Compagnia
            Militare::where('compagnia_id', $compagniaEsistente->id)->update(['compagnia_id' => null]);
            
            // Elimina plotoni e poli esistenti
            Plotone::where('compagnia_id', $compagniaEsistente->id)->delete();
            Polo::where('compagnia_id', $compagniaEsistente->id)->delete();
            
            // Aggiorna il nome della compagnia esistente
            $compagniaEsistente->update(['nome' => '124^ Compagnia']);
        }

        // Crea le tre compagnie
        $compagnie = [
            [
                'nome' => '110^ Compagnia',
                'plotoni' => ['1° Plotone', '2° Plotone', '3° Plotone'],
                'poli' => ['Polo Comando', 'Polo Logistico', 'Polo Tecnico']
            ],
            [
                'nome' => '124^ Compagnia',
                'plotoni' => ['1° Plotone', '2° Plotone', '3° Plotone', '4° Plotone'],
                'poli' => ['Polo Comando', 'Polo Informatico', 'Polo Radio', 'Polo Satellitare', 'Polo MGE', 'Polo Logistico', 'Polo Sanitario', 'Polo Armeria', 'Polo Fureria', 'Polo SIGE']
            ],
            [
                'nome' => '127^ Compagnia',
                'plotoni' => ['1° Plotone', '2° Plotone', '3° Plotone'],
                'poli' => ['Polo Comando', 'Polo Logistico', 'Polo Tecnico']
            ]
        ];

        foreach ($compagnie as $compagniaData) {
            // Crea o aggiorna la compagnia
            $compagnia = Compagnia::updateOrCreate(
                ['nome' => $compagniaData['nome']],
                ['nome' => $compagniaData['nome']]
            );

            // Crea i plotoni
            foreach ($compagniaData['plotoni'] as $nomePlotone) {
                Plotone::updateOrCreate(
                    [
                        'nome' => $nomePlotone,
                        'compagnia_id' => $compagnia->id
                    ],
                    [
                        'nome' => $nomePlotone,
                        'compagnia_id' => $compagnia->id
                    ]
                );
            }

            // Crea i poli
            foreach ($compagniaData['poli'] as $nomePolo) {
                Polo::updateOrCreate(
                    [
                        'nome' => $nomePolo,
                        'compagnia_id' => $compagnia->id
                    ],
                    [
                        'nome' => $nomePolo,
                        'compagnia_id' => $compagnia->id
                    ]
                );
            }
        }

        // Assegna i militari esistenti alla 124^ Compagnia
        $compagnia124 = Compagnia::where('nome', '124^ Compagnia')->first();
        if ($compagnia124) {
            Militare::whereNull('compagnia_id')->update(['compagnia_id' => $compagnia124->id]);
        }

        $this->command->info('✅ Compagnie corrette create con successo!');
        $this->command->info('📊 Create:');
        $this->command->info('   - 110^ Compagnia (3 plotoni, 3 poli)');
        $this->command->info('   - 124^ Compagnia (4 plotoni, 10 poli)');
        $this->command->info('   - 127^ Compagnia (3 plotoni, 3 poli)');
        $this->command->info('   - Militari assegnati alla 124^ Compagnia');
    }
}
