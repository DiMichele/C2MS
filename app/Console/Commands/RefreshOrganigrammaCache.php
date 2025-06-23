<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshOrganigrammaCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'organigramma:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggiorna la cache dell\'organigramma per mostrare i dati più recenti';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🔄 Aggiornamento cache organigramma...');
        
        // Invalida la cache dell'organigramma
        Cache::forget('organigramma.compagnia');
        
        $this->info('✅ Cache organigramma aggiornata con successo!');
        $this->info('📊 L\'organigramma ora mostrerà i dati più recenti.');
        
        return 0;
    }
} 