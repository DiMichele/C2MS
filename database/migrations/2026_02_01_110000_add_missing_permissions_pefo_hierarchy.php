<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aggiunge i permessi mancanti per:
 * - PEFO (gestione prenotazioni)
 * - Gerarchia (assignments e permissions)
 */
return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            // PEFO
            ['name' => 'pefo.view', 'display_name' => 'Visualizza PEFO', 'description' => 'Permette di visualizzare le prenotazioni PEFO', 'category' => 'pefo'],
            ['name' => 'pefo.edit', 'display_name' => 'Modifica PEFO', 'description' => 'Permette di gestire le prenotazioni PEFO', 'category' => 'pefo'],
            
            // Gerarchia - Assignments
            ['name' => 'gerarchia_assignments.view', 'display_name' => 'Visualizza Assegnazioni Gerarchia', 'description' => 'Permette di visualizzare le assegnazioni nella gerarchia organizzativa', 'category' => 'admin'],
            ['name' => 'gerarchia_assignments.edit', 'display_name' => 'Modifica Assegnazioni Gerarchia', 'description' => 'Permette di modificare le assegnazioni nella gerarchia organizzativa', 'category' => 'admin'],
            
            // Gerarchia - Permissions
            ['name' => 'gerarchia_permissions.view', 'display_name' => 'Visualizza Permessi Gerarchia', 'description' => 'Permette di visualizzare i permessi della gerarchia organizzativa', 'category' => 'admin'],
            ['name' => 'gerarchia_permissions.edit', 'display_name' => 'Modifica Permessi Gerarchia', 'description' => 'Permette di modificare i permessi della gerarchia organizzativa', 'category' => 'admin'],
            
            // Gestione CPT (per coerenza)
            ['name' => 'gestione_cpt.view', 'display_name' => 'Visualizza Gestione CPT', 'description' => 'Permette di visualizzare la gestione dei codici CPT', 'category' => 'admin'],
            ['name' => 'gestione_cpt.edit', 'display_name' => 'Modifica Gestione CPT', 'description' => 'Permette di modificare la gestione dei codici CPT', 'category' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            // Inserisci solo se non esiste già
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'category' => $permission['category'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Assegna tutti i permessi al ruolo admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if ($adminRole) {
            $permissionIds = DB::table('permissions')
                ->whereIn('name', array_column($permissions, 'name'))
                ->pluck('id');
            
            foreach ($permissionIds as $permId) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $adminRole->id,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionNames = [
            'pefo.view', 'pefo.edit',
            'gerarchia_assignments.view', 'gerarchia_assignments.edit',
            'gerarchia_permissions.view', 'gerarchia_permissions.edit',
            'gestione_cpt.view', 'gestione_cpt.edit',
        ];

        // Rimuovi le associazioni
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->pluck('id');
        
        DB::table('permission_role')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        // Rimuovi i permessi
        DB::table('permissions')
            ->whereIn('name', $permissionNames)
            ->delete();
    }
};
