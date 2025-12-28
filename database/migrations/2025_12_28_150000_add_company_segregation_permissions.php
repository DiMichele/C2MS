<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungi colonna is_global ai ruoli (indica se il ruolo ha visibilità globale)
        if (!Schema::hasColumn('roles', 'is_global')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_global')->default(false)->after('compagnia_id')
                    ->comment('Se true, il ruolo ha visibilità su tutte le compagnie');
            });
        }
        
        // Aggiungi colonna category ai permessi per raggrupparli
        if (!Schema::hasColumn('permissions', 'category')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('category')->default('general')->after('description')
                    ->comment('Categoria del permesso per raggruppamento UI');
            });
        }
        
        // Crea i permessi per la segregazione dati
        $segregationPermissions = [
            [
                'name' => 'view_all_companies',
                'display_name' => 'Visualizza tutte le compagnie',
                'description' => 'Permette di vedere i dati di TUTTE le compagnie (solo admin)',
                'category' => 'segregation',
            ],
            [
                'name' => 'view_own_company',
                'display_name' => 'Visualizza propria compagnia',
                'description' => 'Permette di vedere i dati della propria compagnia',
                'category' => 'segregation',
            ],
            [
                'name' => 'edit_own_company',
                'display_name' => 'Modifica propria compagnia',
                'description' => 'Permette di modificare i dati della propria compagnia',
                'category' => 'segregation',
            ],
            [
                'name' => 'manage_own_company_users',
                'display_name' => 'Gestisci utenti propria compagnia',
                'description' => 'Permette di gestire gli utenti della propria compagnia',
                'category' => 'segregation',
            ],
        ];
        
        foreach ($segregationPermissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }
        
        // Imposta i ruoli admin come globali
        Role::whereIn('name', ['admin', 'amministratore'])->update(['is_global' => true]);
        
        // Assegna il permesso view_all_companies ai ruoli admin
        $viewAllPermission = Permission::where('name', 'view_all_companies')->first();
        if ($viewAllPermission) {
            $adminRoles = Role::whereIn('name', ['admin', 'amministratore'])->get();
            foreach ($adminRoles as $role) {
                $role->permissions()->syncWithoutDetaching([$viewAllPermission->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rimuovi i permessi di segregazione
        Permission::whereIn('name', [
            'view_all_companies',
            'view_own_company',
            'edit_own_company',
            'manage_own_company_users',
        ])->delete();
        
        // Rimuovi la colonna is_global
        if (Schema::hasColumn('roles', 'is_global')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_global');
            });
        }
        
        // Rimuovi la colonna category
        if (Schema::hasColumn('permissions', 'category')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};

