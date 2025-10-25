<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Pulisci tabelle
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('permission_role')->truncate();
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
        
        // PERSONALE - Scadenze
        $permissions['scadenze.view'] = Permission::create(['name' => 'scadenze.view', 'display_name' => 'Visualizza Scadenze', 'category' => 'personale', 'type' => 'read']);
        $permissions['scadenze.edit'] = Permission::create(['name' => 'scadenze.edit', 'display_name' => 'Modifica Scadenze', 'category' => 'personale', 'type' => 'write']);
        
        // PERSONALE - Organigramma
        $permissions['organigramma.view'] = Permission::create(['name' => 'organigramma.view', 'display_name' => 'Visualizza Organigramma', 'category' => 'personale', 'type' => 'read']);
        
        // BOARD
        $permissions['board.view'] = Permission::create(['name' => 'board.view', 'display_name' => 'Visualizza Board', 'category' => 'board', 'type' => 'read']);
        $permissions['board.edit'] = Permission::create(['name' => 'board.edit', 'display_name' => 'Modifica Board', 'category' => 'board', 'type' => 'write']);
        
        // SERVIZI - Turni
        $permissions['turni.view'] = Permission::create(['name' => 'turni.view', 'display_name' => 'Visualizza Turni', 'category' => 'servizi', 'type' => 'read']);
        $permissions['turni.edit'] = Permission::create(['name' => 'turni.edit', 'display_name' => 'Modifica Turni', 'category' => 'servizi', 'type' => 'write']);
        
        // SERVIZI - Trasparenza
        $permissions['trasparenza.view'] = Permission::create(['name' => 'trasparenza.view', 'display_name' => 'Visualizza Trasparenza', 'category' => 'servizi', 'type' => 'read']);
        
        // ADMIN
        $permissions['admin.access'] = Permission::create(['name' => 'admin.access', 'display_name' => 'Accesso Pannello Admin', 'category' => 'admin', 'type' => 'write']);
        $permissions['admin.users'] = Permission::create(['name' => 'admin.users', 'display_name' => 'Gestione Utenti', 'category' => 'admin', 'type' => 'write']);
        
        $this->command->info('✅ ' . count($permissions) . ' permessi creati');
        $this->command->info('');
        $this->command->info('👥 Creazione ruoli...');
        
        // RUOLO: Amministratore
        $admin = Role::create([
            'name' => 'admin',
            'display_name' => 'Amministratore',
            'description' => 'Accesso completo al pannello admin, può gestire utenti e ruoli. NON può accedere alle altre sezioni operative.'
        ]);
        $admin->permissions()->attach([
            $permissions['admin.access']->id,
            $permissions['admin.users']->id,
        ]);
        
        // RUOLO: Comandante
        $comandante = Role::create([
            'name' => 'comandante',
            'display_name' => 'Comandante',
            'description' => 'Accesso completo a tutte le sezioni tranne Admin. Può visualizzare e modificare tutto.'
        ]);
        $comandante->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['cpt.edit']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['anagrafica.edit']->id,
            $permissions['scadenze.view']->id,
            $permissions['scadenze.edit']->id,
            $permissions['organigramma.view']->id,
            $permissions['board.view']->id,
            $permissions['board.edit']->id,
            $permissions['turni.view']->id,
            $permissions['turni.edit']->id,
            $permissions['trasparenza.view']->id,
        ]);
        
        // RUOLO: Furiere
        $furiere = Role::create([
            'name' => 'furiere',
            'display_name' => 'Furiere',
            'description' => 'Accesso a Personale e Servizi con permessi di lettura e scrittura.'
        ]);
        $furiere->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['cpt.edit']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['anagrafica.edit']->id,
            $permissions['scadenze.view']->id,
            $permissions['scadenze.edit']->id,
            $permissions['organigramma.view']->id,
            $permissions['turni.view']->id,
            $permissions['turni.edit']->id,
            $permissions['trasparenza.view']->id,
        ]);
        
        // RUOLO: RSSP
        $rssp = Role::create([
            'name' => 'rssp',
            'display_name' => 'RSSP',
            'description' => 'Responsabile Sicurezza: accesso solo alla pagina Scadenze con permessi di modifica.'
        ]);
        $rssp->permissions()->attach([
            $permissions['scadenze.view']->id,
            $permissions['scadenze.edit']->id,
        ]);
        
        // RUOLO: Operatore
        $operatore = Role::create([
            'name' => 'operatore',
            'display_name' => 'Operatore',
            'description' => 'Accesso a Dashboard, Personale e Servizi con permessi di lettura e scrittura limitati.'
        ]);
        $operatore->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['scadenze.view']->id,
            $permissions['organigramma.view']->id,
            $permissions['board.view']->id,
            $permissions['turni.view']->id,
            $permissions['trasparenza.view']->id,
        ]);
        
        // RUOLO: Visualizzatore
        $visualizzatore = Role::create([
            'name' => 'visualizzatore',
            'display_name' => 'Visualizzatore',
            'description' => 'Accesso in sola lettura a tutte le sezioni (tranne Admin).'
        ]);
        $visualizzatore->permissions()->attach([
            $permissions['dashboard.view']->id,
            $permissions['cpt.view']->id,
            $permissions['ruolini.view']->id,
            $permissions['anagrafica.view']->id,
            $permissions['scadenze.view']->id,
            $permissions['organigramma.view']->id,
            $permissions['board.view']->id,
            $permissions['turni.view']->id,
            $permissions['trasparenza.view']->id,
        ]);
        
        $this->command->info('✅ 6 ruoli creati');
        $this->command->info('');
        $this->command->info('📋 RIEPILOGO RUOLI:');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('👑 Admin: Solo pannello Admin');
        $this->command->info('⭐ Comandante: Tutto tranne Admin');
        $this->command->info('📝 Furiere: Personale + Servizi (R/W)');
        $this->command->info('🛡️  RSSP: Solo Scadenze (R/W)');
        $this->command->info('🔧 Operatore: Dashboard + Personale + Servizi (R)');
        $this->command->info('👁️  Visualizzatore: Tutto in sola lettura');
    }
}
