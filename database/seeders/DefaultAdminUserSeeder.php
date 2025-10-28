<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DefaultAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea un utente admin di default con credenziali standard
     */
    public function run(): void
    {
        // Credenziali di default
        $defaultUsers = [
            [
                'name' => 'Amministratore',
                'username' => 'admin',
                'email' => 'admin@c2ms.local',
                'password' => Hash::make('11Reggimento'),
                'email_verified_at' => now(),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Michele Di Gennaro',
                'username' => 'michele.digennaro',
                'email' => 'michele.digennaro@esercito.difesa.it',
                'password' => Hash::make('11Reggimento'),
                'email_verified_at' => now(),
                'must_change_password' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($defaultUsers as $userData) {
            // Verifica se l'utente esiste già
            $exists = DB::table('users')->where('username', $userData['username'])->exists();
            
            if (!$exists) {
                DB::table('users')->insert($userData);
                echo "✓ Utente '{$userData['username']}' creato\n";
            } else {
                // Aggiorna la password dell'utente esistente
                DB::table('users')
                    ->where('username', $userData['username'])
                    ->update([
                        'password' => $userData['password'],
                        'must_change_password' => false,
                        'updated_at' => now()
                    ]);
                echo "✓ Password utente '{$userData['username']}' aggiornata\n";
            }
        }
    }
}
