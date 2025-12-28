<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Compagnia;
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
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = \App\Models\Permission::orderBy('category')->orderBy('name')->get();
        
        // Raggruppa permessi per categoria
        $permissionsByCategory = $permissions->groupBy('category');
        
        return view('admin.permissions.index', compact('roles', 'permissions', 'permissionsByCategory'));
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
            'password' => Hash::make('11Reggimento'),
            'must_change_password' => true,
        ]);

        // Assegna ruolo
        $user->assignRole($role);

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

        $user->update([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'compagnia_id' => $validated['compagnia_id'] ?? null,
        ]);

        // Aggiorna ruolo
        $user->roles()->sync([$validated['role_id']]);

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
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "Utente {$name} eliminato con successo!");
    }

    /**
     * Reset password utente
     */
    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('11Reggimento'),
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Password di {$user->name} resettata a: 11Reggimento");
    }

    /**
     * Aggiorna i permessi di un ruolo
     */
    public function updatePermissions(Request $request, Role $role)
    {
        // PROTEGGI I RUOLI ADMIN E AMMINISTRATORE - Non possono essere modificati
        if ($role->name === 'admin' || $role->name === 'amministratore') {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Il ruolo ' . $role->display_name . ' è protetto e non può essere modificato. Ha automaticamente tutti i permessi.');
        }
        
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $permissions = $request->input('permissions', []);
        $role->permissions()->sync($permissions);

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

        return redirect()->route('admin.permissions.index')
            ->with('success', "Ruolo {$role->display_name} creato con successo!");
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
        $usersCount = $role->users()->count();
        
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
