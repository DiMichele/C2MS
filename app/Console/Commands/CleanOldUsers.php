<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanOldUsers extends Command
{
    protected $signature = 'users:clean-old';
    protected $description = 'Elimina gli utenti vecchi senza role_type';

    public function handle()
    {
        $this->info('🧹 Pulizia utenti vecchi...');
        $this->newLine();

        $oldUsers = User::whereNull('role_type')->get();

        if ($oldUsers->isEmpty()) {
            $this->info('✅ Nessun utente vecchio da eliminare.');
            return 0;
        }

        $this->info("Trovati {$oldUsers->count()} utenti vecchi:");
        foreach ($oldUsers as $user) {
            $this->line("  - ID: {$user->id}, Nome: {$user->name}, Email: {$user->email}");
        }

        $this->newLine();
        
        if ($this->confirm('Vuoi eliminare questi utenti?', true)) {
            foreach ($oldUsers as $user) {
                $user->delete();
                $this->info("  ✅ Eliminato: {$user->name}");
            }
            $this->newLine();
            $this->info('✅ Pulizia completata!');
        } else {
            $this->warn('❌ Operazione annullata.');
        }

        return 0;
    }
}
