<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Role;
use Illuminate\Console\Command;

class FixUserRoles extends Command
{
    protected $signature = 'users:fix-roles';
    protected $description = 'Corregge e assegna i ruoli agli utenti esistenti';

    public function handle()
    {
        $this->info('🔧 Correzione ruoli utenti...');
        $this->newLine();

        $users = User::all();

        if ($users->isEmpty()) {
            $this->warn('⚠️  Nessun utente trovato.');
            return 0;
        }

        foreach ($users as $user) {
            $this->info("Verifico utente: {$user->name} (ID: {$user->id})");

            // Rimuovi tutti i ruoli esistenti
            $user->roles()->detach();

            // Determina il ruolo corretto in base a role_type
            $roleName = null;

            if ($user->role_type === 'amministratore') {
                $roleName = 'amministratore';
            } elseif ($user->role_type === 'comandante') {
                $roleName = 'comandante';
            } elseif ($user->role_type === 'rssp') {
                $roleName = 'rssp';
            } elseif ($user->role_type === 'ufficio_compagnia' && $user->compagnia_id) {
                $roleName = 'ufficio_compagnia_' . $user->compagnia_id;
            }

            if ($roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->assignRole($role);
                    $this->info("  ✅ Assegnato ruolo: {$role->display_name}");
                } else {
                    $this->error("  ❌ Ruolo '{$roleName}' non trovato!");
                }
            } else {
                $this->warn("  ⚠️  Nessun role_type valido per questo utente");
            }
        }

        $this->newLine();
        $this->info('✅ Correzione completata!');
        $this->newLine();

        // Mostra riepilogo
        $this->info('📋 RIEPILOGO:');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        foreach (User::with('roles')->get() as $user) {
            $roles = $user->roles->pluck('display_name')->join(', ');
            $this->line("  {$user->name}: {$roles}");
        }

        return 0;
    }
}
