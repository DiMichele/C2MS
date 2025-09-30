<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compagnia;
use App\Models\Plotone;
use App\Models\Polo;

class CompagniaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crea la compagnia principale
        $compagnia = Compagnia::create([
            'nome' => '124^ Compagnia'
        ]);

        // Crea i plotoni
        $plotoni = [
            '1° Plotone',
            '2° Plotone', 
            '3° Plotone',
            '4° Plotone'
        ];

        foreach ($plotoni as $index => $nomeplotone) {
            Plotone::create([
                'nome' => $nomeplotone,
                'compagnia_id' => $compagnia->id
            ]);
        }

        // Crea i poli
        $poli = [
            'Polo Comando',
            'Polo Logistico',
            'Polo Tecnico',
            'Polo Sanitario'
        ];

        foreach ($poli as $index => $nomePolo) {
            Polo::create([
                'nome' => $nomePolo,
                'compagnia_id' => $compagnia->id
            ]);
        }
    }
}
