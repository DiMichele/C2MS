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
                'name' => 'Amministratore',
                'email' => 'admin@sige.it',
                'password' => 'admin123',
            ],
            [
                'name' => 'Comandante',
                'email' => 'comandante@sige.it',
                'password' => 'coman123',
            ],
            [
                'name' => 'Operatore',
                'email' => 'operatore@sige.it',
                'password' => 'oper123',
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
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'email_verified_at' => now(),
            ]);

            $this->command->info("✅ Creato utente: {$userData['name']} ({$userData['email']})");
            $this->command->info("   Password: {$userData['password']}");
        }

        $this->command->info('');
        $this->command->info('📋 CREDENZIALI DI ACCESSO:');
        $this->command->info('================================');
        foreach ($users as $userData) {
            $this->command->info("Email: {$userData['email']}");
            $this->command->info("Password: {$userData['password']}");
            $this->command->info('--------------------------------');
        }
    }
}
