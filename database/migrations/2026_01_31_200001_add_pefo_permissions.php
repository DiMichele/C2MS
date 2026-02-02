<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration per aggiungere i permessi PEFO
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Permessi PEFO
        $permissions = [
            [
                'name' => 'pefo.view',
                'display_name' => 'Visualizza pagina PEFO',
                'description' => 'Permette di visualizzare la pagina PEFO con tabella militari e prenotazioni',
                'category' => 'pefo'
            ],
            [
                'name' => 'pefo.edit',
                'display_name' => 'Modifica date PEFO',
                'description' => 'Permette di modificare le date ultimo PEFO dei militari',
                'category' => 'pefo'
            ],
            [
                'name' => 'pefo.gestione_prenotazioni',
                'display_name' => 'Gestione prenotazioni PEFO',
                'description' => 'Permette di creare, confermare ed eliminare le prenotazioni PEFO',
                'category' => 'pefo'
            ]
        ];

        foreach ($permissions as $permission) {
            // Controlla se esiste già
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
            
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permission['name'],
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description'],
                    'category' => $permission['category'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Assegna tutti i permessi PEFO al ruolo Admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $pefoPermissions = DB::table('permissions')
                ->where('category', 'pefo')
                ->pluck('id');
            
            foreach ($pefoPermissions as $permissionId) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $permissionId)
                    ->exists();
                
                if (!$exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }

        // Assegna permessi PEFO al ruolo Amministratore
        $amministratoreRole = DB::table('roles')->where('name', 'amministratore')->first();
        if ($amministratoreRole) {
            $pefoPermissions = DB::table('permissions')
                ->where('category', 'pefo')
                ->pluck('id');
            
            foreach ($pefoPermissions as $permissionId) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $amministratoreRole->id)
                    ->where('permission_id', $permissionId)
                    ->exists();
                
                if (!$exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $amministratoreRole->id,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi i permessi PEFO dalle associazioni
        $permissionIds = DB::table('permissions')
            ->where('category', 'pefo')
            ->pluck('id');
        
        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();
        
        // Rimuovi i permessi
        DB::table('permissions')
            ->where('category', 'pefo')
            ->delete();
    }
};
