<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Compagnia;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    /**
     * Dashboard admin - redirect a gestione utenti
     */
    public function index()
    {
        return redirect()->route('admin.users.index');
    }

    /**
     * Gestione Utenti - Lista utenti
     */
    public function usersIndex()
    {
        $users = User::with(['roles', 'compagnia'])->orderBy('name')->get();
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Gestione Permessi - Tabella permessi per ruolo
     */
    public function permissionsIndex()
    {
        $roles = Role::with(['permissions', 'compagnieVisibili'])->orderBy('name')->get();
        $permissions = \App\Models\Permission::orderBy('category')->orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Raggruppa permessi per categoria
        $permissionsByCategory = $permissions->groupBy('category');
        
        return view('admin.permissions.index', compact('roles', 'permissions', 'permissionsByCategory', 'compagnie'));
    }

    /**
     * Form creazione nuovo utente
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        return view('admin.create', compact('roles', 'compagnie'));
    }

    /**
     * Salva nuovo utente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users|regex:/^[a-z]+\.[a-z]+$/',
            'role_id' => 'required|exists:roles,id',
            'compagnia_id' => 'nullable|exists:compagnie,id',
        ], [
            'name.required' => 'Il nome è obbligatorio',
            'username.required' => 'Lo username è obbligatorio',
            'username.regex' => 'Lo username deve essere nel formato: nome.cognome (tutto minuscolo)',
            'username.unique' => 'Questo username è già in uso',
            'role_id.required' => 'Seleziona un ruolo',
        ]);

        // Verifica se il ruolo richiede una compagnia
        $role = Role::findOrFail($validated['role_id']);
        if (!$role->is_global && empty($validated['compagnia_id'])) {
            return back()->withErrors(['compagnia_id' => 'La compagnia è obbligatoria per questo ruolo.'])->withInput();
        }

        // Genera email automaticamente basata sullo username
        $username = strtolower($validated['username']);
        $email = $username . '@sugeco.local';

        $user = User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $email,
            'compagnia_id' => $validated['compagnia_id'] ?? null,
            // FIX: Password spostata in configurazione per facilità di gestione
            'password' => Hash::make(config('auth.default_password')),
            'must_change_password' => true,
        ]);

        // Assegna ruolo
        $user->assignRole($role);

        // Registra la creazione nel log di audit
        AuditService::logCreate($user, "Creato utente: {$user->name} ({$user->username}) con ruolo {$role->display_name}");

        $compagniaInfo = $user->compagnia_id ? " (Compagnia: {$user->compagnia->nome})" : " (Accesso globale)";
        
        return redirect()->route('admin.users.index')
            ->with('success', "Utente {$user->name} creato con successo! Username: {$user->username}{$compagniaInfo}");
    }

    /**
     * Form modifica utente
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        return view('admin.edit', compact('user', 'roles', 'compagnie'));
    }

    /**
     * Aggiorna utente
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id . '|regex:/^[a-z]+\.[a-z]+$/',
            'role_id' => 'required|exists:roles,id',
            'compagnia_id' => 'nullable|exists:compagnie,id',
        ], [
            'username.regex' => 'Lo username deve essere nel formato: nome.cognome (tutto minuscolo)',
            'username.unique' => 'Questo username è già in uso',
        ]);

        // Verifica se il ruolo richiede una compagnia
        $role = Role::findOrFail($validated['role_id']);
        if (!$role->is_global && empty($validated['compagnia_id'])) {
            return back()->withErrors(['compagnia_id' => 'La compagnia è obbligatoria per questo ruolo.'])->withInput();
        }

        // Salva i valori originali per l'audit
        $oldValues = $user->toArray();
        $oldRole = $user->roles->first()?->display_name ?? 'Nessuno';

        // FIX: Sincronizza email con username (come in store())
        $newUsername = strtolower($validated['username']);
        $user->update([
            'name' => $validated['name'],
            'username' => $newUsername,
            'email' => $newUsername . '@sugeco.local', // Aggiorna email automaticamente
            'compagnia_id' => $validated['compagnia_id'] ?? null,
        ]);

        // Aggiorna ruolo
        $user->roles()->sync([$validated['role_id']]);

        // Registra la modifica nel log di audit
        AuditService::logUpdate(
            $user,
            ['name' => $oldValues['name'], 'username' => $oldValues['username'], 'ruolo' => $oldRole],
            ['name' => $user->name, 'username' => $user->username, 'ruolo' => $role->display_name],
            "Modificato utente: {$user->name}"
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Utente {$user->name} aggiornato con successo!");
    }

    /**
     * Elimina utente
     */
    public function destroy(User $user)
    {
        // Previeni eliminazione dell'ultimo admin/amministratore
        if ($user->hasRole('admin') || $user->hasRole('amministratore')) {
            $adminCount = User::whereHas('roles', function($q) {
                $q->whereIn('name', ['admin', 'amministratore']);
            })->count();
            
            if ($adminCount <= 1) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Impossibile eliminare l\'ultimo amministratore!');
            }
        }

        $name = $user->name;
        $username = $user->username;
        
        // Registra l'eliminazione PRIMA di eliminare
        AuditService::logDelete($user, "Eliminato utente: {$name} ({$username})");
        
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Utente {$name} eliminato con successo!");
    }

    /**
     * Reset password utente
     */
    public function resetPassword(User $user)
    {
        // FIX: Password spostata in configurazione per facilità di gestione
        $defaultPassword = config('auth.default_password');
        
        $user->update([
            'password' => Hash::make($defaultPassword),
            'must_change_password' => true,
        ]);

        // Registra il reset password nel log di audit
        AuditService::log(
            'password_change',
            "Password resettata per l'utente {$user->name} ({$user->username}) da un amministratore",
            $user,
            ['reset_by' => auth()->user()->name]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Password di {$user->name} resettata a: {$defaultPassword}");
    }

    /**
     * Aggiorna i permessi di un ruolo
     */
    public function updatePermissions(Request $request, Role $role)
    {
        // PROTEGGI I RUOLI ADMIN E AMMINISTRATORE - Non possono essere modificati
        if ($role->name === 'admin' || $role->name === 'amministratore') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il ruolo ' . $role->display_name . ' è protetto e non può essere modificato.'
                ], 400);
            }
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Il ruolo ' . $role->display_name . ' è protetto e non può essere modificato. Ha automaticamente tutti i permessi.');
        }
        
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        // Salva i permessi attuali per l'audit
        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $permissions = $request->input('permissions', []);
        $role->permissions()->sync($permissions);

        // Ottieni i nuovi permessi per l'audit
        $role->load('permissions');
        $newPermissions = $role->permissions->pluck('name')->toArray();

        // Registra la modifica permessi nel log di audit
        AuditService::logPermissionChange($role->users->first() ?? new User(['name' => "Ruolo: {$role->display_name}"]), $oldPermissions, $newPermissions);
        
        // Registra anche come modifica al ruolo
        AuditService::log(
            'permission_change',
            "Modificati permessi del ruolo: {$role->display_name}",
            null,
            [
                'role' => $role->name,
                'old_permissions' => $oldPermissions,
                'new_permissions' => $newPermissions
            ]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Permessi aggiornati per: ' . $role->display_name
            ]);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', "Permessi aggiornati per il ruolo: {$role->display_name}");
    }

    /**
     * Form creazione nuovo ruolo
     */
    public function createRole()
    {
        $permissions = \App\Models\Permission::orderBy('category')->orderBy('name')->get();
        $permissionsByCategory = $permissions->groupBy('category');
        
        return view('admin.roles.create', compact('permissions', 'permissionsByCategory'));
    }

    /**
     * Salva nuovo ruolo
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ], [
            'name.required' => 'Il nome del ruolo è obbligatorio',
            'name.unique' => 'Questo nome ruolo è già in uso',
            'name.regex' => 'Il nome deve contenere solo lettere minuscole e underscore',
            'display_name.required' => 'Il nome visualizzato è obbligatorio',
        ]);

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? '',
        ]);

        // Assegna permessi
        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        // Registra la creazione nel log di audit
        AuditService::log(
            'create',
            "Creato nuovo ruolo: {$role->display_name}",
            null,
            [
                'role_name' => $role->name,
                'role_display_name' => $role->display_name,
                'permissions_count' => count($validated['permissions'] ?? [])
            ]
        );

        return redirect()->route('admin.permissions.index')
            ->with('success', "Ruolo {$role->display_name} creato con successo!");
    }

    /**
     * Aggiorna le compagnie visibili per un ruolo
     */
    public function updateCompanyVisibility(Request $request, Role $role)
    {
        // I ruoli admin e amministratore vedono sempre tutte le compagnie
        if ($role->name === 'admin' || $role->name === 'amministratore') {
            return response()->json([
                'success' => false,
                'message' => 'Il ruolo ' . $role->display_name . ' ha automaticamente visibilità su tutte le compagnie.'
            ], 400);
        }
        
        $request->validate([
            'compagnie' => 'array',
            'compagnie.*' => 'exists:compagnie,id'
        ]);

        // Salva le compagnie attuali per l'audit
        $oldCompagnie = $role->compagnieVisibili->pluck('nome')->toArray();

        $compagnieIds = $request->input('compagnie', []);
        $role->syncCompagnieVisibili($compagnieIds);

        // Ottieni i nuovi valori per l'audit
        $role->load('compagnieVisibili');
        $newCompagnie = $role->compagnieVisibili->pluck('nome')->toArray();

        // Registra la modifica nel log di audit
        AuditService::log(
            'permission_change',
            "Modificata visibilità compagnie per il ruolo: {$role->display_name}",
            null,
            [
                'role' => $role->name,
                'old_compagnie' => $oldCompagnie,
                'new_compagnie' => $newCompagnie
            ]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Visibilità compagnie aggiornata per: ' . $role->display_name
            ]);
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', "Visibilità compagnie aggiornata per il ruolo: {$role->display_name}");
    }

    /**
     * Elimina ruolo
     */
    public function destroyRole(Role $role)
    {
        // I ruoli "admin" e "amministratore" non possono essere eliminati
        if ($role->name === 'admin' || $role->name === 'amministratore') {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Impossibile eliminare il ruolo ' . $role->display_name . '! È un ruolo di sistema protetto.');
        }

        $name = $role->display_name;
        $roleName = $role->name;
        $usersCount = $role->users()->count();
        
        // Registra l'eliminazione PRIMA di eliminare
        AuditService::log(
            'delete',
            "Eliminato ruolo: {$name}",
            null,
            [
                'role_name' => $roleName,
                'role_display_name' => $name,
                'affected_users' => $usersCount
            ]
        );
        
        // Rimuovi il ruolo da tutti gli utenti assegnati
        $role->users()->detach();
        
        // Elimina il ruolo
        $role->delete();

        $message = "Ruolo {$name} eliminato con successo!";
        if ($usersCount > 0) {
            $message .= " {$usersCount} utent" . ($usersCount == 1 ? 'e è stato lasciato' : 'i sono stati lasciati') . " senza ruolo.";
        }

        return redirect()->route('admin.permissions.index')
            ->with('success', $message);
    }
}
