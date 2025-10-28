<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Grado;

class CleanObsoleteGradi extends Command
{
    protected $signature = 'gradi:clean-obsolete';
    protected $description = 'Rimuove gradi obsoleti che non hanno militari associati';

    public function handle()
    {
        $gradiObsoleti = [
            'Generale', // Rimosso perché troppo generico
            'Generale di Corpo d\'Armata', // Rimosso: troppo alto
            'Generale di Divisione', // Rimosso: troppo alto
            'Generale di Brigata', // Rimosso: troppo alto
            'Maresciallo Maggiore', // Non esiste più, sostituito da Primo Maresciallo
            'Caporal Maggiore Scelto',
            'Caporal Maggiore Capo',
            'Primo Caporal',
        ];

        $this->info('Ricerca gradi obsoleti...');

        foreach ($gradiObsoleti as $nomeGrado) {
            $grado = Grado::where('nome', $nomeGrado)->first();
            
            if (!$grado) {
                continue;
            }

            $militariCount = $grado->militari()->count();
            
            if ($militariCount > 0) {
                $this->warn("✗ Mantenuto '{$nomeGrado}': ha {$militariCount} militari associati");
            } else {
                $grado->delete();
                $this->info("✓ Eliminato '{$nomeGrado}': nessun militare associato");
            }
        }

        $this->info('Pulizia completata!');
        return 0;
    }
}

