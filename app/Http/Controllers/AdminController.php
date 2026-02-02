<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Compagnia;
use App\Models\OrganizationalUnit;
use App\Models\RoleUnitPermission;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * 
     * PAGINA GLOBALE: Mostra tutti gli utenti accessibili all'utente corrente.
     * - Admin globali: vedono tutti gli utenti
     * - Altri utenti: vedono utenti delle unità accessibili + utenti globali
     */
    public function usersIndex()
    {
        $currentUser = auth()->user();
        
        // Query base con relazioni
        $usersQuery = User::with(['roles', 'roles.organizationalUnit', 'compagnia', 'organizationalUnit']);
        
        // Filtro per unità accessibili (se non admin globale)
        if (!$currentUser->hasRole('admin')) {
            $accessibleUnitIds = $currentUser->getVisibleUnitIds();
            
            if (!empty($accessibleUnitIds)) {
                $usersQuery->where(function ($q) use ($accessibleUnitIds) {
                    $q->whereIn('organizational_unit_id', $accessibleUnitIds)
                      ->orWhereNull('organizational_unit_id'); // Utenti globali
                });
            } else {
                // Nessuna unità accessibile - mostra solo l'utente stesso
                $usersQuery->where('id', $currentUser->id);
            }
        }
        
        $users = $usersQuery->orderBy('name')->get();
        
        // Query ruoli - admin vede tutti, altri vedono ruoli globali + ruoli delle proprie unità
        $rolesQuery = Role::query();
        if (!$currentUser->hasRole('admin')) {
            $accessibleUnitIds = $currentUser->getVisibleUnitIds();
            $rolesQuery->where(function ($q) use ($accessibleUnitIds) {
                $q->where('is_global', true)
                  ->orWhereIn('organizational_unit_id', $accessibleUnitIds);
            });
        }
        $roles = $rolesQuery->orderBy('display_name')->get();
        
        // Query unità organizzative per il form
        $unitsQuery = OrganizationalUnit::active()->with('type');
        if (!$currentUser->hasRole('admin')) {
            $accessibleUnitIds = $currentUser->getVisibleUnitIds();
            $unitsQuery->whereIn('id', $accessibleUnitIds);
        }
        $organizationalUnits = $unitsQuery->orderBy('depth')->orderBy('name')->get();
        
        return view('admin.users.index', compact('users', 'roles', 'organizationalUnits'));
    }
    
    /**
     * Ottiene gli ID di un'unità e tutte le sue sotto-unità
     */
    protected function getUnitWithDescendants(int $unitId): array
    {
        $unit = OrganizationalUnit::find($unitId);
        if (!$unit) {
            return [$unitId];
        }
        
        return $unit->getDescendantIds(true);
    }

    /**
     * Gestione Permessi - Tabella permessi per ruolo
     * 
     * PAGINA GLOBALE: Mostra ruoli e unità accessibili all'utente corrente.
     * - Admin globali: vedono tutti i ruoli e unità
     * - Altri utenti: vedono ruoli globali + ruoli delle proprie unità
     */
    public function permissionsIndex()
    {
        $currentUser = auth()->user();
        $isAdmin = $currentUser->hasRole('admin');
        $accessibleUnitIds = $isAdmin ? [] : $currentUser->getVisibleUnitIds();
        
        // Query ruoli - admin vede tutti, altri vedono ruoli globali + ruoli delle proprie unità
        $rolesQuery = Role::with(['permissions', 'compagnieVisibili', 'visibleUnits', 'users', 'organizationalUnit']);
        if (!$isAdmin) {
            $rolesQuery->where(function ($q) use ($accessibleUnitIds) {
                $q->where('is_global', true)
                  ->orWhereIn('organizational_unit_id', $accessibleUnitIds);
            });
        }
        // Ordina per unità organizzativa e nome
        $roles = $rolesQuery->orderBy('organizational_unit_id')->orderBy('name')->get();
        
        // Raggruppa ruoli per macro-entità (is_global => 'global', altrimenti organizational_unit_id)
        $rolesByUnit = $roles->groupBy(function($role) {
            if ($role->is_global) {
                return 'global';
            }
            return $role->organizational_unit_id;
        });
        
        // Carica macro-entità (depth=1) per gli accordion
        $macroUnits = OrganizationalUnit::active()
            ->where('depth', 1)
            ->with('type')
            ->orderBy('name')
            ->get();
        
        // Permessi sono sempre tutti visibili (configurazione globale)
        $permissions = \App\Models\Permission::orderBy('category')->orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Query unità organizzative per la visibilità
        $unitsQuery = OrganizationalUnit::active()->with('type');
        if (!$isAdmin) {
            $unitsQuery->whereIn('id', $accessibleUnitIds);
        }
        $organizationalUnits = $unitsQuery->orderBy('depth')->orderBy('name')->get();
        
        // Query utenti per la terza tab "Gestione Utenti"
        $usersQuery = User::with(['roles', 'roles.organizationalUnit', 'compagnia', 'organizationalUnit']);
        if (!$isAdmin) {
            $usersQuery->where(function ($q) use ($accessibleUnitIds) {
                $q->whereIn('organizational_unit_id', $accessibleUnitIds)
                  ->orWhereNull('organizational_unit_id'); // Utenti globali
            });
        }
        $users = $usersQuery->orderBy('name')->get();
        
        // Raggruppa permessi per categoria
        $permissionsByCategory = $permissions->groupBy('category');
        
        return view('admin.permissions.index', compact(
            'roles',
            'rolesByUnit',
            'macroUnits',
            'permissions',
            'permissionsByCategory',
            'compagnie',
            'organizationalUnits',
            'users'
        ));
    }
    
    /**
     * Aggiorna unità visibili per un ruolo
     * 
     * MULTI-TENANCY: Se il ruolo appartiene a una macro-entità,
     * permette solo le unità figlie di quella macro-entità.
     */
    public function updateRoleVisibleUnits(Request $request, Role $role)
    {
        $validated = $request->validate([
            'visible_units' => 'nullable|array',
            'visible_units.*' => 'exists:organizational_units,id',
        ]);
        
        // Se il ruolo non è globale, valida che le unità siano figlie della sua macro-entità
        if (!$role->is_global && $role->organizational_unit_id) {
            $allowedUnitIds = OrganizationalUnit::where('parent_id', $role->organizational_unit_id)
                ->pluck('id')
                ->toArray();
            
            $requestedUnits = $validated['visible_units'] ?? [];
            $invalidUnits = array_diff($requestedUnits, $allowedUnitIds);
            
            if (!empty($invalidUnits)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alcune unità selezionate non sono consentite per questo ruolo'
                ], 422);
            }
        }
        
        $role->syncVisibleUnits($validated['visible_units'] ?? []);
        
        return response()->json(['success' => true, 'message' => 'Unità visibili aggiornate']);
    }
    
    /**
     * Aggiorna permessi specifici per unità
     */
    public function updateRoleUnitPermissions(Request $request, Role $role)
    {
        $validated = $request->validate([
            'unit_permissions' => 'nullable|array',
            'unit_permissions.*.unit_id' => 'required|exists:organizational_units,id',
            'unit_permissions.*.permission_id' => 'required|exists:permissions,id',
            'unit_permissions.*.access_level' => 'required|in:view,edit,admin',
        ]);
        
        DB::transaction(function () use ($role, $validated) {
            // Elimina permessi unità esistenti
            $role->unitPermissions()->delete();
            
            // Crea nuovi permessi unità
            if (!empty($validated['unit_permissions'])) {
                foreach ($validated['unit_permissions'] as $perm) {
                    RoleUnitPermission::create([
                        'role_id' => $role->id,
                        'organizational_unit_id' => $perm['unit_id'],
                        'permission_id' => $perm['permission_id'],
                        'access_level' => $perm['access_level'],
                    ]);
                }
            }
        });
        
        AuditService::log('update', $role, "Aggiornati permessi unità per ruolo: {$role->display_name}");
        
        return response()->json(['success' => true, 'message' => 'Permessi unità aggiornati']);
    }
    
    /**
     * Ottiene i permessi per unità di un ruolo (API JSON)
     */
    public function getRoleUnitPermissions(Role $role)
    {
        $unitPermissions = $role->unitPermissions()
            ->with(['unit', 'permission'])
            ->get()
            ->groupBy('organizational_unit_id');
        
        return response()->json([
            'success' => true,
            'data' => [
                'visible_units' => $role->getVisibleUnitIds(),
                'unit_permissions' => $unitPermissions,
            ]
        ]);
    }

    /**
     * Form creazione nuovo utente
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Filtra solo le macro-entità (depth=1) per la selezione utente
        $organizationalUnits = OrganizationalUnit::active()
            ->where('depth', 1)
            ->orderBy('name')
            ->get();
            
        return view('admin.create', compact('roles', 'compagnie', 'organizationalUnits'));
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
            'organizational_unit_id' => 'nullable|exists:organizational_units,id', // Multi-tenancy - può essere NULL per utenti globali
        ], [
            'name.required' => 'Il nome è obbligatorio',
            'username.required' => 'Lo username è obbligatorio',
            'username.regex' => 'Lo username deve essere nel formato: nome.cognome (tutto minuscolo)',
            'username.unique' => 'Questo username è già in uso',
            'role_id.required' => 'Seleziona un ruolo',
        ]);

        // Trova il ruolo
        $role = Role::findOrFail($validated['role_id']);

        // Gestione unità organizzativa: NULL = globale, altrimenti usa il valore selezionato
        $organizationalUnitId = !empty($validated['organizational_unit_id']) ? $validated['organizational_unit_id'] : null;

        // Genera email automaticamente basata sullo username
        $username = strtolower($validated['username']);
        $email = $username . '@sugeco.local';

        $user = User::create([
            'name' => $validated['name'],
            'username' => $username,
            'email' => $email,
            'compagnia_id' => $validated['compagnia_id'] ?? null,
            'organizational_unit_id' => $organizationalUnitId, // Multi-tenancy
            // FIX: Password spostata in configurazione per facilità di gestione
            'password' => Hash::make(config('auth.default_password')),
            'must_change_password' => true,
        ]);

        // Assegna ruolo
        $user->assignRole($role);

        // Registra la creazione nel log di audit
        AuditService::logCreate($user, "Creato utente: {$user->name} ({$user->username}) con ruolo {$role->display_name}");

        $unitInfo = $user->organizational_unit_id 
            ? " (Unità: {$user->organizationalUnit->name})" 
            : " (Accesso globale)";
        
        return redirect()->route('admin.permissions.index', ['tab' => 'users'])
            ->with('success', "Utente {$user->name} creato con successo! Username: {$user->username}{$unitInfo}");
    }

    /**
     * Form modifica utente
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $compagnie = Compagnia::orderBy('nome')->get();
        
        // Filtra solo le macro-entità (depth=1) per la selezione utente
        $organizationalUnits = OrganizationalUnit::active()
            ->where('depth', 1)
            ->orderBy('name')
            ->get();
            
        return view('admin.edit', compact('user', 'roles', 'compagnie', 'organizationalUnits'));
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
            'organizational_unit_id' => 'nullable|exists:organizational_units,id', // Multi-tenancy - può essere NULL per utenti globali
        ], [
            'username.regex' => 'Lo username deve essere nel formato: nome.cognome (tutto minuscolo)',
            'username.unique' => 'Questo username è già in uso',
        ]);

        // Trova il ruolo
        $role = Role::findOrFail($validated['role_id']);
        
        // Gestione unità organizzativa: NULL = globale
        $organizationalUnitId = !empty($validated['organizational_unit_id']) ? $validated['organizational_unit_id'] : null;

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
            'organizational_unit_id' => $organizationalUnitId, // Multi-tenancy - NULL = globale
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

        return redirect()->route('admin.permissions.index', ['tab' => 'users'])
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
                return redirect()->route('admin.permissions.index', ['tab' => 'users'])
                    ->with('error', 'Impossibile eliminare l\'ultimo amministratore!');
            }
        }

        $name = $user->name;
        $username = $user->username;
        
        // Registra l'eliminazione PRIMA di eliminare
        AuditService::logDelete($user, "Eliminato utente: {$name} ({$username})");
        
        $user->delete();

        return redirect()->route('admin.permissions.index', ['tab' => 'users'])
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

        return redirect()->route('admin.permissions.index', ['tab' => 'users'])
            ->with('success', "Password di {$user->name} resettata a: {$defaultPassword}");
    }

    /**
     * Aggiorna i permessi di un ruolo
     * 
     * MULTI-TENANCY: Utenti non-admin possono modificare solo ruoli della propria unità
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
        
        // CONTROLLO PERMESSI: utente può modificare solo ruoli della sua unità
        $currentUser = auth()->user();
        if (!$currentUser->hasRole('admin')) {
            $userUnitId = $currentUser->organizational_unit_id;
            
            // Se il ruolo non è globale e appartiene a un'altra unità, blocca
            if (!$role->is_global && $role->organizational_unit_id !== $userUnitId) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Non hai i permessi per modificare questo ruolo'
                    ], 403);
                }
                return redirect()->route('admin.permissions.index')
                    ->with('error', 'Non hai i permessi per modificare questo ruolo.');
            }
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
        
        // Carica unità organizzative per il select
        $organizationalUnits = OrganizationalUnit::active()
            ->with('type')
            ->orderBy('depth')
            ->orderBy('name')
            ->get();
        
        return view('admin.roles.create', compact('permissions', 'permissionsByCategory', 'organizationalUnits'));
    }

    /**
     * Salva nuovo ruolo
     * 
     * MULTI-TENANCY: I ruoli sono associati a una macro-entità (depth=1).
     * Solo i ruoli globali (is_global = true) non hanno unità.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles|regex:/^[a-z_]+$/',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'organizational_unit_id' => 'nullable|exists:organizational_units,id',
            'is_global' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ], [
            'name.required' => 'Il nome del ruolo è obbligatorio',
            'name.unique' => 'Questo nome ruolo è già in uso',
            'name.regex' => 'Il nome deve contenere solo lettere minuscole e underscore',
            'display_name.required' => 'Il nome visualizzato è obbligatorio',
        ]);

        // Determina se è globale basandosi sul campo is_global
        $isGlobal = $request->boolean('is_global');
        $organizationalUnitId = $validated['organizational_unit_id'] ?? null;
        
        // Se is_global è true, forza organizational_unit_id a null
        if ($isGlobal) {
            $organizationalUnitId = null;
        }
        
        // Se is_global è false, deve avere un organizational_unit_id valido
        if (!$isGlobal && empty($organizationalUnitId)) {
            return back()->withErrors([
                'organizational_unit_id' => 'Seleziona una macro-entità di appartenenza o imposta il ruolo come globale'
            ])->withInput();
        }
        
        // Validazione: se non è globale, la macro-entità deve essere depth=1
        if (!$isGlobal && $organizationalUnitId) {
            $unit = OrganizationalUnit::find($organizationalUnitId);
            if (!$unit || $unit->depth !== 1) {
                return back()->withErrors([
                    'organizational_unit_id' => 'Seleziona una macro-entità valida (Battaglione o Compagnia Comando)'
                ])->withInput();
            }
        }

        $role = Role::create([
            'name' => strtolower($validated['name']),
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? '',
            'organizational_unit_id' => $organizationalUnitId,
            'is_global' => $isGlobal,
        ]);

        // Assegna permessi
        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        // Registra la creazione nel log di audit
        $unitName = $role->organizationalUnit?->name ?? 'Globale';
        AuditService::log(
            'create',
            "Creato nuovo ruolo: {$role->display_name} (Unità: {$unitName})",
            null,
            [
                'role_name' => $role->name,
                'role_display_name' => $role->display_name,
                'organizational_unit_id' => $organizationalUnitId,
                'is_global' => $isGlobal,
                'permissions_count' => count($validated['permissions'] ?? [])
            ]
        );

        return redirect()->route('admin.permissions.index')
            ->with('success', "Ruolo {$role->display_name} creato con successo!");
    }
    
    /**
     * API: Ottiene i ruoli disponibili per una specifica unità organizzativa
     * 
     * Usato dal form creazione/modifica utente per popolare dinamicamente
     * la select dei ruoli basandosi sull'unità selezionata.
     */
    public function getRolesForUnit(int $unitId)
    {
        $roles = Role::where('organizational_unit_id', $unitId)
                     ->orWhere('is_global', true)
                     ->orderBy('display_name')
                     ->get(['id', 'name', 'display_name', 'is_global', 'organizational_unit_id']);
        
        return response()->json([
            'success' => true,
            'roles' => $roles
        ]);
    }
    
    /**
     * API: Ottiene le unità figlie di una macro-entità
     * 
     * Usato nella tab visibilità per filtrare le unità disponibili
     * per i ruoli di una specifica macro-entità.
     */
    public function getChildUnits(int $macroUnitId)
    {
        $childUnits = OrganizationalUnit::active()
            ->where('parent_id', $macroUnitId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        
        return response()->json([
            'success' => true,
            'units' => $childUnits
        ]);
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
     * Rinomina un ruolo (aggiorna solo display_name)
     */
    public function renameRole(Request $request, Role $role)
    {
        // I ruoli "admin" e "amministratore" non possono essere rinominati
        if ($role->name === 'admin' || $role->name === 'amministratore') {
            return response()->json([
                'success' => false,
                'message' => 'Il ruolo ' . $role->display_name . ' è protetto e non può essere rinominato.'
            ], 400);
        }
        
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
        ], [
            'display_name.required' => 'Il nome visualizzato è obbligatorio',
            'display_name.max' => 'Il nome visualizzato non può superare 255 caratteri',
        ]);
        
        $oldDisplayName = $role->display_name;
        $newDisplayName = $validated['display_name'];
        
        // Aggiorna il display_name
        $role->update([
            'display_name' => $newDisplayName,
        ]);
        
        // Registra la modifica nel log di audit
        AuditService::log(
            'update',
            "Rinominato ruolo da '{$oldDisplayName}' a '{$newDisplayName}'",
            null,
            [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'old_display_name' => $oldDisplayName,
                'new_display_name' => $newDisplayName,
            ]
        );
        
        return response()->json([
            'success' => true,
            'message' => "Ruolo rinominato con successo in: {$newDisplayName}",
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ]
        ]);
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
