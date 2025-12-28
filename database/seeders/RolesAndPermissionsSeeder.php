<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use App\Models\Compagnia;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Pulisci tabelle
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('permission_role')->truncate();
        \DB::table('role_user')->truncate();
        \DB::table('permissions')->truncate();
        \DB::table('roles')->truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('🔐 Creazione permessi...');
        
        // DASHBOARD
        $permissions['dashboard.view'] = Permission::create(['name' => 'dashboard.view', 'display_name' => 'Visualizza Dashboard', 'category' => 'dashboard', 'type' => 'read']);
        
        // PERSONALE - CPT
        $permissions['cpt.view'] = Permission::create(['name' => 'cpt.view', 'display_name' => 'Visualizza CPT', 'category' => 'personale', 'type' => 'read']);
        $permissions['cpt.edit'] = Permission::create(['name' => 'cpt.edit', 'display_name' => 'Modifica CPT', 'category' => 'personale', 'type' => 'write']);
        
        // PERSONALE - Ruolini
        $permissions['ruolini.view'] = Permission::create(['name' => 'ruolini.view', 'display_name' => 'Visualizza Ruolini', 'category' => 'personale', 'type' => 'read']);
        
        // PERSONALE - Anagrafica
        $permissions['anagrafica.view'] = Permission::create(['name' => 'anagrafica.view', 'display_name' => 'Visualizza Anagrafica', 'category' => 'personale', 'type' => 'read']);
        $permissions['anagrafica.edit'] = Permission::create(['name' => 'anagrafica.edit', 'display_name' => 'Modifica Anagrafica', 'category' => 'personale', 'type' => 'write']);
        
        // PERSONALE - Scadenze SPP
        $permissions['scadenze.view'] = Permission::create(['name' => 'scadenze.view', 'display_name' => 'Visualizza Scadenze SPP', 'category' => 'personale', 'type' => 'read']);
        $permissions['scadenze.edit'] = Permission::create(['name' => 'scadenze.edit', 'display_name' => 'Modifica Scadenze SPP', 'category' => 'personale', 'type' => 'write']);
        
        // PERSONALE - Organigramma
        $permissions['organigramma.view'] = Permission::create(['name' => 'organigramma.view', 'display_name' => 'Visualizza Organigramma', 'category' => 'personale', 'type' => 'read']);
        
        // PERSONALE - Assenze
        $permissions['assenze.view'] = Permission::create(['name' => 'assenze.view', 'display_name' => 'Visualizza Assenze', 'category' => 'personale', 'type' => 'read']);
        $permissions['assenze.edit'] = Permission::create(['name' => 'assenze.edit', 'display_name' => 'Modifica Assenze', 'category' => 'personale', 'type' => 'write']);
        
        // BOARD ATTIVITÀ
        $permissions['board.view'] = Permission::create(['name' => 'board.view', 'display_name' => 'Visualizza Board', 'category' => 'board', 'type' => 'read']);
        $permissions['board.edit'] = Permission::create(['name' => 'board.edit', 'display_name' => 'Modifica Board', 'category' => 'board', 'type' => 'write']);
        
        // EVENTI
        $permissions['eventi.view'] = Permission::create(['name' => 'eventi.view', 'display_name' => 'Visualizza Eventi', 'category' => 'eventi', 'type' => 'read']);
        $permissions['eventi.edit'] = Permission::create(['name' => 'eventi.edit', 'display_name' => 'Modifica Eventi', 'category' => 'eventi', 'type' => 'write']);
        
        // PIANIFICAZIONE
        $permissions['pianificazione.view'] = Permission::create(['name' => 'pianificazione.view', 'display_name' => 'Visualizza Pianificazione', 'category' => 'pianificazione', 'type' => 'read']);
        $permissions['pianificazione.edit'] = Permission::create(['name' => 'pianificazione.edit', 'display_name' => 'Modifica Pianificazione', 'category' => 'pianificazione', 'type' => 'write']);
        
        // SERVIZI (generale)
        $permissions['servizi.view'] = Permission::create(['name' => 'servizi.view', 'display_name' => 'Visualizza Servizi', 'category' => 'servizi', 'type' => 'read']);
        
        // SERVIZI - Turni
        $permissions['turni.view'] = Permission::create(['name' => 'turni.view', 'display_name' => 'Visualizza Turni', 'category' => 'servizi', 'type' => 'read']);
        $permissions['turni.edit'] = Permission::create(['name' => 'turni.edit', 'display_name' => 'Modifica Turni', 'category' => 'servizi', 'type' => 'write']);
        
        // SERVIZI - Trasparenza
        $permissions['trasparenza.view'] = Permission::create(['name' => 'trasparenza.view', 'display_name' => 'Visualizza Trasparenza', 'category' => 'servizi', 'type' => 'read']);
        
        // ADMIN - Pannello Generale
        $permissions['admin.access'] = Permission::create(['name' => 'admin.access', 'display_name' => 'Accesso Pannello Admin', 'category' => 'admin', 'type' => 'write']);
        $permissions['admin.users'] = Permission::create(['name' => 'admin.users', 'display_name' => 'Gestione Utenti', 'category' => 'admin', 'type' => 'write']);
        
        // PROFILO UTENTE
        $permissions['profile.view'] = Permission::create(['name' => 'profile.view', 'display_name' => 'Visualizza Profilo', 'category' => 'profilo', 'type' => 'read']);
        $permissions['profile.edit'] = Permission::create(['name' => 'profile.edit', 'display_name' => 'Modifica Profilo', 'category' => 'profilo', 'type' => 'write']);
        
        $this->command->info('✅ ' . count($permissions) . ' permessi creati');
        $this->command->info('');
        $this->command->info('👥 Creazione ruoli...');
        
        // RUOLO: Admin (ACCESSO COMPLETO A TUTTO)
        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Admin',
            'description' => 'Accesso completo a tutto il sistema.',
            'compagnia_id' => null
        ]);
        // Admin ha TUTTI i permessi
        $admin->permissions()->attach(array_map(fn($p) => $p->id, $permissions));
        
        // RUOLO: Amministratore (gestionale completo)
        $amministratore = Role::create([
            'name' => 'amministratore',
            'display_name' => 'Amministratore',
            'description' => 'Accesso completo a tutto il gestionale + pannello admin + modifica scadenze.',
            'compagnia_id' => null
        ]);
        $amministratore->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['cpt.edit']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['anagrafica.edit']->id,
            $permissions['scadenze.view']->id,
            $permissions['scadenze.edit']->id,
            $permissions['organigramma.view']->id,
            $permissions['assenze.view']->id,
            $permissions['assenze.edit']->id,
            $permissions['board.view']->id,
            $permissions['board.edit']->id,
            $permissions['eventi.view']->id,
            $permissions['eventi.edit']->id,
            $permissions['pianificazione.view']->id,
            $permissions['pianificazione.edit']->id,
            $permissions['servizi.view']->id,
            $permissions['turni.view']->id,
            $permissions['turni.edit']->id,
            $permissions['trasparenza.view']->id,
            $permissions['admin.access']->id,
            $permissions['admin.users']->id,
            $permissions['profile.view']->id,
            $permissions['profile.edit']->id,
        ]);
        
        // RUOLO: Comandante (globale, vede tutte le compagnie)
        $comandante = Role::create([
            'name' => 'comandante',
            'display_name' => 'Comandante',
            'description' => 'Accesso completo a tutte le compagnie (solo visualizzazione scadenze).',
            'compagnia_id' => null
        ]);
        $comandante->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['cpt.edit']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['anagrafica.edit']->id,
            $permissions['scadenze.view']->id,
            // NOTA: scadenze.edit rimosso - solo RSSP e Amministratore
            $permissions['organigramma.view']->id,
            $permissions['assenze.view']->id,
            $permissions['assenze.edit']->id,
            $permissions['board.view']->id,
            $permissions['board.edit']->id,
            $permissions['eventi.view']->id,
            $permissions['eventi.edit']->id,
            $permissions['pianificazione.view']->id,
            $permissions['pianificazione.edit']->id,
            $permissions['servizi.view']->id,
            $permissions['turni.view']->id,
            $permissions['turni.edit']->id,
            $permissions['trasparenza.view']->id,
            $permissions['profile.view']->id,
            $permissions['profile.edit']->id,
        ]);
        
        // RUOLO: RSSP (globale, vede tutte le scadenze)
        $rssp = Role::create([
            'name' => 'rssp',
            'display_name' => 'RSSP',
            'description' => 'Responsabile Sicurezza: accesso scadenze di tutte le compagnie.',
            'compagnia_id' => null
        ]);
        $rssp->permissions()->attach([
            $permissions['scadenze.view']->id,
            $permissions['scadenze.edit']->id,
        ]);
        
        // RUOLI UFFICIO COMPAGNIA per ciascuna compagnia
        $compagnie = Compagnia::all();
        $this->command->info('');
        $this->command->info('🏢 Creazione ruoli Ufficio Compagnia...');
        
        foreach ($compagnie as $compagnia) {
            $roleUfficio = Role::create([
                'name' => 'ufficio_compagnia_' . $compagnia->id,
                'display_name' => 'Ufficio ' . $compagnia->nome,
                'description' => 'Gestione completa della ' . $compagnia->nome,
                'compagnia_id' => $compagnia->id
            ]);
            
            // Permessi: tutto tranne admin e scadenze.edit, ma solo per la propria compagnia
            $roleUfficio->permissions()->attach([
                $permissions['dashboard.view']->id,
                $permissions['cpt.view']->id,
                $permissions['cpt.edit']->id,
                $permissions['ruolini.view']->id,
                $permissions['anagrafica.view']->id,
                $permissions['anagrafica.edit']->id,
                $permissions['scadenze.view']->id,
                // NOTA: scadenze.edit rimosso - solo RSSP e Amministratore
                $permissions['organigramma.view']->id,
                $permissions['assenze.view']->id,
                $permissions['assenze.edit']->id,
                $permissions['board.view']->id,
                $permissions['board.edit']->id,
                $permissions['eventi.view']->id,
                $permissions['eventi.edit']->id,
                $permissions['pianificazione.view']->id,
                $permissions['pianificazione.edit']->id,
                $permissions['servizi.view']->id,
                $permissions['turni.view']->id,
                $permissions['turni.edit']->id,
                $permissions['trasparenza.view']->id,
                $permissions['profile.view']->id,
                $permissions['profile.edit']->id,
            ]);
            
            $this->command->info('  ✓ ' . $roleUfficio->display_name);
        }
        
        $this->command->info('');
        $this->command->info('✅ Ruoli creati con successo!');
        $this->command->info('');
        $this->command->info('📋 RIEPILOGO:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👑 Admin: Pannello amministrazione');
        $this->command->info('🔧 Amministratore: Tutto + modifica scadenze');
        $this->command->info('⭐ Comandante: Tutte le compagnie (scadenze READ-ONLY)');
        $this->command->info('🛡️  RSSP: Scadenze tutte le compagnie (READ-WRITE)');
        $this->command->info('🏢 Ufficio Compagnia: Solo la propria compagnia (scadenze READ-ONLY)');
        $this->command->info('   - Ufficio 110^ Compagnia');
        $this->command->info('   - Ufficio 124^ Compagnia');
        $this->command->info('   - Ufficio 127^ Compagnia');
    }
}
