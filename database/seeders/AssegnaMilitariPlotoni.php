<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Militare;
use App\Models\Plotone;

class AssegnaMilitariPlotoni extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plotoni = Plotone::all();
        $militari = Militare::whereNull('plotone_id')->get();

        if ($plotoni->isEmpty()) {
            $this->command->error('Nessun plotone trovato! Eseguire prima CompagniaSeeder.');
            return;
        }

        if ($militari->isEmpty()) {
            $this->command->info('Tutti i militari hanno già un plotone assegnato.');
            return;
        }

        $this->command->info("Assegnazione di {$militari->count()} militari a {$plotoni->count()} plotoni...");

        // Distribuisci i militari equamente tra i plotoni
        $militariPerPlotone = ceil($militari->count() / $plotoni->count());
        
        $militari->chunk($militariPerPlotone)->each(function ($chunk, $index) use ($plotoni) {
            $plotone = $plotoni->get($index % $plotoni->count());
            
            foreach ($chunk as $militare) {
                $militare->update(['plotone_id' => $plotone->id]);
            }
            
            $this->command->info("Assegnati {$chunk->count()} militari al {$plotone->nome}");
        });

        $this->command->info('Assegnazione completata!');
    }
}
