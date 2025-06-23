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
        // Verifica se esiste già un utente con questa email
        if (User::where('email', 'admin@sige.it')->exists()) {
            $this->command->info('L\'utente amministratore esiste già. Salto la creazione.');
            return;
        }
        
        // Crea un utente amministratore di base
        User::create([
            'name' => 'Amministratore',
            'email' => 'admin@sige.it',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
    }
}
