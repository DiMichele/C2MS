<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration per aggiungere i permessi relativi alla gerarchia organizzativa.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'gerarchia.view',
                'display_name' => 'Visualizza Gerarchia',
                'description' => 'Permette di visualizzare la struttura gerarchica organizzativa',
                'category' => 'gerarchia',
                'type' => 'read',
            ],
            [
                'name' => 'gerarchia.edit',
                'display_name' => 'Modifica Gerarchia',
                'description' => 'Permette di creare, modificare e spostare unità nella gerarchia',
                'category' => 'gerarchia',
                'type' => 'write',
            ],
            [
                'name' => 'gerarchia.delete',
                'display_name' => 'Elimina Unità',
                'description' => 'Permette di eliminare unità dalla gerarchia',
                'category' => 'gerarchia',
                'type' => 'delete',
            ],
            [
                'name' => 'gerarchia.assignments',
                'display_name' => 'Gestisci Assegnazioni',
                'description' => 'Permette di assegnare/rimuovere membri dalle unità',
                'category' => 'gerarchia',
                'type' => 'write',
            ],
            [
                'name' => 'gerarchia.permissions',
                'display_name' => 'Gestisci Permessi Unità',
                'description' => 'Permette di configurare i permessi per ruolo sulle unità',
                'category' => 'gerarchia',
                'type' => 'write',
            ],
        ];

        $now = now();

        foreach ($permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'type' => $permission['type'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Assegna i permessi di visualizzazione a tutti i ruoli esistenti
        $viewPermission = DB::table('permissions')->where('name', 'gerarchia.view')->first();
        
        if ($viewPermission) {
            $roles = DB::table('roles')->pluck('id');
            
            foreach ($roles as $roleId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $viewPermission->id,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Assegna tutti i permessi di gerarchia ai ruoli admin
        $adminRoles = DB::table('roles')
            ->whereIn('name', ['admin', 'amministratore'])
            ->pluck('id');

        $allHierarchyPermissions = DB::table('permissions')
            ->where('category', 'gerarchia')
            ->pluck('id');

        foreach ($adminRoles as $roleId) {
            foreach ($allHierarchyPermissions as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Rimuovi i permessi della gerarchia
        $permissionNames = [
            'gerarchia.view',
            'gerarchia.edit',
            'gerarchia.delete',
            'gerarchia.assignments',
            'gerarchia.permissions',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');

        // Rimuovi le assegnazioni
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        
        // Rimuovi i permessi
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
