<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Amministratore Sistema',
                'username' => 'admin.sistema',
                'email' => 'admin@sige.it',
                'password' => 'admin123',
                'role_type' => 'amministratore',
            ],
            [
                'name' => 'Mario Rossi',
                'username' => 'mario.rossi',
                'email' => 'comandante@sige.it',
                'password' => 'coman123',
                'role_type' => 'comandante',
            ],
            [
                'name' => 'Luigi Bianchi',
                'username' => 'luigi.bianchi',
                'email' => 'operatore@sige.it',
                'password' => 'oper123',
                'role_type' => 'ufficio_compagnia',
            ],
        ];

        foreach ($users as $userData) {
            // Verifica se l'utente esiste già
            if (User::where('email', $userData['email'])->exists()) {
                $this->command->info("⚠️  Utente {$userData['email']} già esistente. Saltato.");
                continue;
            }

            User::create([
                'name' => $userData['name'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
                'role_type' => $userData['role_type'] ?? null,
                'compagnia_id' => $userData['compagnia_id'] ?? null,
            ]);

            $this->command->info("✅ Creato utente: {$userData['name']}");
            $this->command->info("   Username: {$userData['username']}");
            $this->command->info("   Password: {$userData['password']}");
        }

        $this->command->info('');
        $this->command->info('📋 CREDENZIALI DI ACCESSO:');
        $this->command->info('================================');
        foreach ($users as $userData) {
            $this->command->info("Nome: {$userData['name']}");
            $this->command->info("Username: {$userData['username']}");
            $this->command->info("Password: {$userData['password']}");
            $this->command->info('--------------------------------');
        }
    }
}
