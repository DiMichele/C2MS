<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Militare;

class CreateMilitariDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'militari:create-directories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea le cartelle personalizzate per tutti i militari esistenti';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creazione cartelle per militari esistenti...');
        
        $militari = Militare::all();
        $created = 0;
        $errors = 0;
        
        $progressBar = $this->output->createProgressBar($militari->count());
        $progressBar->start();
        
        foreach ($militari as $militare) {
            try {
                $militare->createMilitareDirectories();
                $created++;
            } catch (\Exception $e) {
                $this->error("\nErrore per militare {$militare->getNomeCompleto()}: " . $e->getMessage());
                $errors++;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        
        $this->newLine(2);
        $this->info("Processo completato!");
        $this->info("Cartelle create: {$created}");
        
        if ($errors > 0) {
            $this->warn("Errori riscontrati: {$errors}");
        }
        
        return Command::SUCCESS;
    }
} 