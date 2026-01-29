<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Aggiunge:
     * 1. Tabella pivot role_compagnia_visibility per configurare quali compagnie può vedere ogni ruolo
     * 2. Permessi mancanti per coprire tutte le pagine del sistema
     */
    public function up(): void
    {
        // 1. Crea tabella pivot per visibilità compagnie per ruolo
        Schema::create('role_compagnia_visibility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('compagnia_id')->constrained('compagnie')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['role_id', 'compagnia_id']);
        });

        // 2. Aggiungi permessi mancanti
        $missingPermissions = [
            // Anagrafica - permessi create/delete usati nelle rotte
            [
                'name' => 'anagrafica.create',
                'display_name' => 'Crea Militare',
                'description' => 'Permette di creare nuovi militari in anagrafica',
                'category' => 'personale',
                'type' => 'write',
            ],
            [
                'name' => 'anagrafica.delete',
                'display_name' => 'Elimina Militare',
                'description' => 'Permette di eliminare militari dall\'anagrafica',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Disponibilità - attualmente usa cpt.view ma merita permesso proprio
            [
                'name' => 'disponibilita.view',
                'display_name' => 'Visualizza Disponibilità',
                'description' => 'Permette di visualizzare la disponibilità del personale',
                'category' => 'personale',
                'type' => 'read',
            ],
            // Impieghi Personale - attualmente usa scadenze.view ma merita permessi propri
            [
                'name' => 'impieghi.view',
                'display_name' => 'Visualizza Impieghi/Organici',
                'description' => 'Permette di visualizzare gli impieghi del personale nei teatri operativi',
                'category' => 'personale',
                'type' => 'read',
            ],
            [
                'name' => 'impieghi.edit',
                'display_name' => 'Modifica Impieghi/Organici',
                'description' => 'Permette di modificare gli impieghi del personale nei teatri operativi',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Approntamenti - attualmente usa scadenze.view ma merita permessi propri
            [
                'name' => 'approntamenti.view',
                'display_name' => 'Visualizza Approntamenti',
                'description' => 'Permette di visualizzare lo stato degli approntamenti',
                'category' => 'personale',
                'type' => 'read',
            ],
            [
                'name' => 'approntamenti.edit',
                'display_name' => 'Modifica Approntamenti',
                'description' => 'Permette di modificare gli approntamenti e le prenotazioni',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Poligoni - permessi specifici
            [
                'name' => 'poligoni.view',
                'display_name' => 'Visualizza Poligoni',
                'description' => 'Permette di visualizzare le scadenze poligono',
                'category' => 'personale',
                'type' => 'read',
            ],
            [
                'name' => 'poligoni.edit',
                'display_name' => 'Modifica Poligoni',
                'description' => 'Permette di modificare le scadenze poligono',
                'category' => 'personale',
                'type' => 'write',
            ],
            // Idoneità - permessi specifici
            [
                'name' => 'idoneita.view',
                'display_name' => 'Visualizza Idoneità',
                'description' => 'Permette di visualizzare le idoneità sanitarie',
                'category' => 'personale',
                'type' => 'read',
            ],
            [
                'name' => 'idoneita.edit',
                'display_name' => 'Modifica Idoneità',
                'description' => 'Permette di modificare le idoneità sanitarie',
                'category' => 'personale',
                'type' => 'write',
            ],
            // SPP - permessi specifici (già esiste scadenze.view/edit ma per chiarezza)
            [
                'name' => 'spp.view',
                'display_name' => 'Visualizza Corsi SPP',
                'description' => 'Permette di visualizzare i corsi SPP e Accordo Stato Regione',
                'category' => 'personale',
                'type' => 'read',
            ],
            [
                'name' => 'spp.edit',
                'display_name' => 'Modifica Corsi SPP',
                'description' => 'Permette di modificare i corsi SPP e Accordo Stato Regione',
                'category' => 'personale',
                'type' => 'write',
            ],
        ];

        foreach ($missingPermissions as $permission) {
            // Verifica se il permesso esiste già
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
            
            if (!$exists) {
                DB::table('permissions')->insert(array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 3. Popola la tabella role_compagnia_visibility con i valori di default
        // I ruoli globali (admin, amministratore, comandante, rssp) vedono tutte le compagnie
        // I ruoli specifici per compagnia vedono solo la loro compagnia
        $globalRoles = DB::table('roles')
            ->where('is_global', true)
            ->orWhereIn('name', ['admin', 'amministratore', 'comandante', 'rssp'])
            ->pluck('id');
        
        $compagnie = DB::table('compagnie')->pluck('id');
        
        foreach ($globalRoles as $roleId) {
            foreach ($compagnie as $compagniaId) {
                DB::table('role_compagnia_visibility')->insertOrIgnore([
                    'role_id' => $roleId,
                    'compagnia_id' => $compagniaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Per i ruoli specifici di compagnia, assegna solo la loro compagnia
        $companyRoles = DB::table('roles')
            ->whereNotNull('compagnia_id')
            ->select('id', 'compagnia_id')
            ->get();
        
        foreach ($companyRoles as $role) {
            DB::table('role_compagnia_visibility')->insertOrIgnore([
                'role_id' => $role->id,
                'compagnia_id' => $role->compagnia_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_compagnia_visibility');
        
        // Rimuovi i permessi aggiunti
        DB::table('permissions')->whereIn('name', [
            'anagrafica.create',
            'anagrafica.delete',
            'disponibilita.view',
            'impieghi.view',
            'impieghi.edit',
            'approntamenti.view',
            'approntamenti.edit',
            'poligoni.view',
            'poligoni.edit',
            'idoneita.view',
            'idoneita.edit',
            'spp.view',
            'spp.edit',
        ])->delete();
    }
};
