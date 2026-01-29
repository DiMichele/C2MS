<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rimuove i permessi legacy non più utilizzati
     */
    public function up(): void
    {
        $legacyPermissions = [
            // Permessi vecchi con nomi non standard
            'board.view_all_militari',
            'edit_own_company',
            'manage_all_users',
            'manage_own_company_users',
            'view_all_companies',
            'view_all_users',
            'view_own_company',
            
            // Permessi duplicati o deprecati
            'corsi_lavoratori.edit',
            'corsi_lavoratori.view',
            'gestione_ruolini.edit',
            'gestione_ruolini.view',
            'poligono.edit', // duplicato di poligoni.edit
            'poligono.view', // duplicato di poligoni.view
        ];

        // Rimuovi prima le associazioni nella tabella pivot
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $legacyPermissions)
            ->pluck('id');
        
        if ($permissionIds->isNotEmpty()) {
            DB::table('permission_role')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        // Poi rimuovi i permessi
        DB::table('permissions')
            ->whereIn('name', $legacyPermissions)
            ->delete();
    }

    /**
     * Reverse the migrations - ricrea i permessi legacy se necessario
     */
    public function down(): void
    {
        // Non ripristiniamo i permessi legacy
    }
};
