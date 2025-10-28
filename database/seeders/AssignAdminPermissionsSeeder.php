<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AssignAdminPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Assegna il ruolo admin con tutti i permessi agli utenti admin
     */
    public function run(): void
    {
        // 1. Crea/aggiorna il ruolo admin
        $adminRole = DB::table('roles')->updateOrInsert(
            ['name' => 'admin'],
            [
                'display_name' => 'Amministratore',
                'description' => 'Accesso completo a tutte le funzionalità del sistema',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        
        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        
        // 2. Crea tutti i permessi se non esistono
        $permissions = [
            // Dashboard
            ['name' => 'dashboard.view', 'display_name' => 'Visualizza Dashboard', 'category' => 'dashboard', 'type' => 'read'],
            
            // Anagrafica
            ['name' => 'anagrafica.view', 'display_name' => 'Visualizza Anagrafica', 'category' => 'personale', 'type' => 'read'],
            ['name' => 'anagrafica.create', 'display_name' => 'Crea Militare', 'category' => 'personale', 'type' => 'write'],
            ['name' => 'anagrafica.edit', 'display_name' => 'Modifica Anagrafica', 'category' => 'personale', 'type' => 'write'],
            ['name' => 'anagrafica.delete', 'display_name' => 'Elimina Militare', 'category' => 'personale', 'type' => 'write'],
            
            // CPT
            ['name' => 'cpt.view', 'display_name' => 'Visualizza CPT', 'category' => 'servizi', 'type' => 'read'],
            ['name' => 'cpt.edit', 'display_name' => 'Modifica CPT', 'category' => 'servizi', 'type' => 'write'],
            
            // Turni
            ['name' => 'turni.view', 'display_name' => 'Visualizza Turni', 'category' => 'servizi', 'type' => 'read'],
            ['name' => 'turni.edit', 'display_name' => 'Modifica Turni', 'category' => 'servizi', 'type' => 'write'],
            
            // Board
            ['name' => 'board.view', 'display_name' => 'Visualizza Board', 'category' => 'board', 'type' => 'read'],
            ['name' => 'board.edit', 'display_name' => 'Modifica Board', 'category' => 'board', 'type' => 'write'],
            
            // Admin
            ['name' => 'admin.access', 'display_name' => 'Accesso Area Admin', 'category' => 'admin', 'type' => 'write'],
            ['name' => 'users.manage', 'display_name' => 'Gestione Utenti', 'category' => 'admin', 'type' => 'write'],
        ];
        
        $permissionIds = [];
        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
            
            $permissionIds[] = DB::table('permissions')->where('name', $permission['name'])->value('id');
        }
        
        // 3. Assegna tutti i permessi al ruolo admin
        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                [
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
        
        // 4. Assegna il ruolo admin agli utenti admin
        $adminUsers = ['admin', 'michele.digennaro'];
        
        foreach ($adminUsers as $username) {
            $user = DB::table('users')->where('username', $username)->first();
            
            if ($user) {
                DB::table('role_user')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'role_id' => $adminRoleId
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
                
                echo "✓ Ruolo admin assegnato all'utente '{$username}'\n";
            }
        }
        
        echo "✓ " . count($permissions) . " permessi creati/aggiornati\n";
        echo "✓ Tutti i permessi assegnati al ruolo admin\n";
    }
}
