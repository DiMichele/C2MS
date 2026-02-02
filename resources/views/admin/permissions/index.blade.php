@extends('layouts.app')

@section('title', 'Gestione Utenti e Ruoli')

@section('styles')
<style>
/* Toggle Container */
.toggle-wrapper {
    background-color: white;
    border-radius: 50px;
    box-shadow: var(--shadow-md);
    padding: 0.4rem;
    position: relative;
    display: inline-flex;
    transition: var(--transition-normal);
}

.toggle-wrapper:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.toggle-option {
    position: relative;
    z-index: 1;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    cursor: pointer;
    transition: var(--transition-normal);
    font-weight: 600;
    min-width: 200px;
    text-align: center;
    font-size: 0.95rem;
    font-family: 'Roboto', sans-serif;
}

.toggle-option.active {
    color: white;
}

.toggle-option:not(.active) {
    color: var(--gray-600);
}

.toggle-option:not(.active):hover {
    color: var(--navy);
}

.toggle-option i {
    margin-right: 0.5rem;
}

.toggle-slider {
    position: absolute;
    top: 0.4rem;
    left: 0.4rem;
    height: calc(100% - 0.8rem);
    width: calc(33.33% - 0.27rem);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    border-radius: 50px;
    transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 4px 15px rgba(10, 35, 66, 0.25);
}

.toggle-slider.pos-1 { transform: translateX(0); }
.toggle-slider.pos-2 { transform: translateX(100%); }
.toggle-slider.pos-3 { transform: translateX(200%); }

/* Card principale */
.permissions-card {
    background-color: white;
    border-radius: var(--border-radius-md);
    margin-bottom: 1.5rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.permissions-card.hidden {
    display: none !important;
}

/* Wrapper tabella con scroll */
.permissions-table-wrapper {
    overflow-x: auto;
    padding: 0;
}

/* Tabella permessi custom */
.permissions-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.permissions-table thead {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    position: sticky;
    top: 0;
    z-index: 10;
}

.permissions-table thead th {
    color: white;
    font-weight: 600;
    padding: 1rem 0.5rem;
    text-align: center;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    white-space: nowrap;
    border-right: 1px solid rgba(255,255,255,0.1);
    vertical-align: bottom;
}

.permissions-table thead th:first-child {
    text-align: left;
    padding-left: 1.5rem;
    min-width: 180px;
}

.permissions-table thead th:last-child {
    border-right: none;
}

.permissions-table tbody tr {
    border-bottom: 1px solid var(--border-color);
    transition: background-color 0.15s ease;
}

.permissions-table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.02);
}

.permissions-table tbody td {
    padding: 0.75rem 0.5rem;
    text-align: center;
    vertical-align: middle;
    border-right: 1px solid var(--border-color);
}

.permissions-table tbody td:first-child {
    text-align: left;
    padding-left: 1.5rem;
    border-right: 2px solid var(--gold);
}

.permissions-table tbody td:last-child {
    border-right: none;
}

/* Icone permessi */
.permission-icons {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
}

.icon-permission {
    cursor: pointer;
    font-size: 1.1rem;
    transition: all 0.2s ease;
    padding: 4px;
    border-radius: 4px;
}

.icon-permission:hover {
    transform: scale(1.15);
    background-color: rgba(10, 35, 66, 0.05);
}

.icon-permission.disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

/* Badge ruoli */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.6rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-badge.protected {
    background-color: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.role-badge.global {
    background-color: rgba(13, 202, 240, 0.1);
    color: #0891b2;
}

/* Pulsante azioni header */
.btn-action-header {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    color: white;
    border: none;
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    transition: var(--transition-normal);
    box-shadow: 0 3px 8px rgba(10, 35, 66, 0.2);
    text-decoration: none;
}

.btn-action-header:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(10, 35, 66, 0.3);
    color: white;
}

/* Search box */
.permissions-search {
    position: relative;
    width: 280px;
}

.permissions-search .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
    pointer-events: none;
}

.permissions-search input {
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border-radius: 25px;
    border: 1px solid var(--border-color);
    width: 100%;
    font-size: 0.875rem;
    transition: var(--transition-normal);
    box-shadow: var(--shadow-sm);
}

.permissions-search input:focus {
    border-color: var(--navy-light);
    box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
    outline: none;
}

/* Visibilità Compagnie */
.visibility-table {
    width: 100%;
    border-collapse: collapse;
}

.visibility-table thead {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
}

.visibility-table thead th {
    color: white;
    font-weight: 600;
    padding: 1rem 1.5rem;
    text-align: left;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.visibility-table tbody tr {
    border-bottom: 1px solid var(--border-color);
}

.visibility-table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.02);
}

.visibility-table tbody td {
    padding: 1rem 1.5rem;
    vertical-align: middle;
}

/* Chip compagnia */
.company-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.company-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
    user-select: none;
}

.company-chip.inactive {
    background: var(--gray-100);
    color: var(--gray-500);
    border-color: var(--gray-300);
}

.company-chip.inactive:hover {
    border-color: var(--navy-light);
    color: var(--navy);
}

.company-chip.active {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
    border-color: #198754;
    box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
}

.company-chip.active:hover {
    background: linear-gradient(135deg, #146c43 0%, #1aa179 100%);
}

.company-chip .chip-icon {
    font-size: 0.75rem;
}

.company-chip.disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Legenda visibilità */
.visibility-legend {
    display: flex;
    gap: 2rem;
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--border-color);
    font-size: 0.8rem;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--gray-600);
}

.legend-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 15px;
    font-size: 0.7rem;
    font-weight: 600;
}

.legend-chip.active {
    background: linear-gradient(135deg, #198754 0%, #20c997 100%);
    color: white;
}

.legend-chip.inactive {
    background: var(--gray-100);
    color: var(--gray-500);
    border: 1px solid var(--gray-300);
}

/* Macro-unit collapse accordion */
.macro-unit-group {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.macro-unit-header {
    width: 100%;
    border: none;
    padding: 0.6rem 1rem;
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
}

.macro-unit-header:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

/* Palette gradiente per unità - colori coordinati */
.macro-unit-header.depth-0 { background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); }
.macro-unit-header.depth-1 { background: linear-gradient(135deg, #2b6cb0 0%, #4299e1 100%); }
.macro-unit-header.depth-2 { background: linear-gradient(135deg, #2c7a7b 0%, #38b2ac 100%); }
.macro-unit-header.depth-3 { background: linear-gradient(135deg, #285e61 0%, #319795 100%); }

.macro-unit-header .collapse-icon {
    transition: transform 0.2s;
    font-size: 0.7rem;
}

.macro-unit-header:not(.collapsed) .collapse-icon {
    transform: rotate(180deg);
}

.macro-unit-content {
    padding: 0.5rem;
    background: #f8f9fa;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.company-chips-accordion {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* ==========================================
   ACCORDION PER TAB USERS
   ========================================== */
#usersTabAccordion .accordion-item {
    border: none;
    border-bottom: 1px solid var(--border-color);
    background: transparent;
    margin-bottom: 0.75rem;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

#usersTabAccordion .accordion-item:last-child {
    border-bottom: none;
}

#usersTabAccordion .accordion-button {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: var(--navy);
    font-weight: 600;
    padding: 1.25rem 1.5rem;
    border: none;
    box-shadow: none;
    font-size: 1rem;
    transition: all 0.3s ease;
}

#usersTabAccordion .accordion-button:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
}

#usersTabAccordion .accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #d4af37 0%, #f0c94a 100%);
    color: var(--navy);
    box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
    font-weight: 700;
}

#usersTabAccordion .accordion-button:focus {
    box-shadow: none;
    border: none;
}

#usersTabAccordion .accordion-button::after {
    transition: transform 0.3s ease;
    filter: none;
}

#usersTabAccordion .accordion-button:not(.collapsed)::after {
    filter: none;
}

#usersTabAccordion .accordion-button .badge {
    font-size: 0.75rem;
    font-weight: 600;
}

#usersTabAccordion .accordion-button:not(.collapsed) .badge {
    background-color: var(--navy) !important;
    color: white;
}

#usersTabAccordion .accordion-body {
    padding: 0;
    background: white;
    border-top: 3px solid #d4af37;
}

#usersTabAccordion .accordion-body .permissions-table-wrapper {
    max-height: 400px;
    overflow-y: auto;
}

/* Uniformare header tabelle in tutte le card */
.permissions-card .sugeco-table thead tr {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
}

.permissions-card .sugeco-table thead th {
    color: white;
    font-weight: 600;
    padding: 1rem;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    border-bottom: none;
}

/* Badge uniformi */
.permissions-card .badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem 0.65rem;
}

/* Pulsanti azioni uniformi */
.permissions-card .btn-group .btn,
.permissions-card .btn-sm {
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
}

/* ==========================================
   ACCORDION RUOLI PER MACRO-ENTITA'
   ========================================== */
.roles-accordion-item,
.roles-sub-accordion {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    border: 1px solid var(--border-color);
}

.roles-accordion-header,
.roles-sub-header {
    width: 100%;
    border: none;
    padding: 1rem 1.5rem;
    color: white;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: left;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.roles-accordion-header:hover,
.roles-sub-header:hover {
    filter: brightness(1.1);
    transform: translateY(-1px);
}

.roles-accordion-header.global,
.roles-sub-header.global {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
}

.roles-accordion-header.depth-1,
.roles-sub-header.depth-1 {
    background: linear-gradient(135deg, #2b6cb0 0%, #4299e1 100%);
}

.roles-accordion-header .collapse-icon,
.roles-sub-header .collapse-icon {
    transition: transform 0.2s ease;
    font-size: 0.75rem;
}

.roles-accordion-header:not(.collapsed) .collapse-icon,
.roles-sub-header:not(.collapsed) .collapse-icon {
    transform: rotate(180deg);
}

.roles-accordion-item .collapse,
.roles-sub-accordion .collapse {
    border-top: 3px solid #d4af37;
    background: white;
}

.roles-accordion-item .permissions-table-wrapper,
.roles-sub-accordion table {
    margin: 0;
}

.roles-sub-accordion {
    margin-bottom: 0.75rem;
}

/* Responsive */
@media (max-width: 768px) {
    .toggle-option {
        min-width: 140px;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }
    
    .permissions-search {
        width: 100%;
    }
    
    #usersTabAccordion .accordion-button {
        font-size: 0.9rem;
        padding: 1rem;
    }
    
    #usersTabAccordion .accordion-body .permissions-table-wrapper {
        max-height: 300px;
    }
    
    .roles-accordion-header,
    .roles-sub-header {
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
    }
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Utenti e Ruoli</h1>
</div>

<!-- Toggle per le 3 schermate -->
<div class="d-flex justify-content-center mb-4">
    <div class="toggle-wrapper">
        <div id="toggleSlider" class="toggle-slider pos-1"></div>
        <div id="permissionsBtn" class="toggle-option active" onclick="switchTab('permissions')">
            <i class="fas fa-key"></i> Permessi Pagine
        </div>
        <div id="visibilityBtn" class="toggle-option" onclick="switchTab('visibility')">
            <i class="fas fa-building"></i> Visibilità per ruolo
        </div>
        <div id="usersBtn" class="toggle-option" onclick="switchTab('users')">
            <i class="fas fa-users"></i> Gestione Utenti
        </div>
    </div>
</div>

<!-- Barra ricerca -->
<div class="d-flex justify-content-center mb-4">
    <div class="permissions-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchRole" placeholder="Cerca ruolo...">
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
    // Mappa nomi permessi a nomi leggibili delle pagine
    $pageNames = [
        'anagrafica' => 'Anagrafica',
        'approntamenti' => 'Approntamenti',
        'assenze' => 'Assenze',
        'board' => 'Board Attività',
        'cpt' => 'CPT',
        'dashboard' => 'Dashboard',
        'disponibilita' => 'Disponibilità',
        'eventi' => 'Eventi',
        'gerarchia_assignments' => 'Gerarchia Assign.',
        'gerarchia_permissions' => 'Gerarchia Perm.',
        'gestione_cpt' => 'Gestione CPT',
        'idoneita' => 'Idoneità',
        'impieghi' => 'Organici',
        'organigramma' => 'Organigramma',
        'pefo' => 'PEFO',
        'pianificazione' => 'Pianificazione',
        'poligoni' => 'Poligoni',
        'profile' => 'Profilo',
        'ruolini' => 'Ruolini',
        'scadenze' => 'Scadenze SPP',
        'servizi' => 'Servizi',
        'spp' => 'Corsi SPP',
        'trasparenza' => 'Trasparenza',
        'turni' => 'Turni',
    ];

    // Organizza i permessi per pagina con view/edit
    $pagePermissions = [];
    
    foreach ($permissions as $perm) {
        // Escludi permessi admin interni
        if (in_array($perm->name, ['admin.access', 'admin.users'])) {
            continue;
        }
        
        // Estrai il nome base della pagina
        $baseName = preg_replace('/\.(view|edit|create|delete)$/', '', $perm->name);
        
        if (!isset($pagePermissions[$baseName])) {
            $pagePermissions[$baseName] = [
                'name' => $baseName,
                'display_name' => $pageNames[$baseName] ?? ucfirst(str_replace(['_', '.'], ' ', $baseName)),
                'view' => null,
                'edit' => null,
            ];
        }
        
        if (str_ends_with($perm->name, '.view')) {
            $pagePermissions[$baseName]['view'] = $perm;
        } elseif (str_ends_with($perm->name, '.edit')) {
            $pagePermissions[$baseName]['edit'] = $perm;
        }
    }
    
    // Ordina alfabeticamente per nome visualizzato
    uasort($pagePermissions, fn($a, $b) => $a['display_name'] <=> $b['display_name']);
@endphp

<!-- TAB 1: Permessi Pagine - Raggruppati per macro-entità -->
<div id="permissionsCard" class="permissions-card">
    <!-- Sezione Ruoli Globali -->
    @if(isset($rolesByUnit['global']) && $rolesByUnit['global']->count() > 0)
    <div class="roles-accordion-item mb-3">
        <button class="roles-accordion-header global" type="button" 
                data-bs-toggle="collapse" data-bs-target="#globalRolesPermissions">
            <span class="d-flex align-items-center">
                <i class="fas fa-globe me-2"></i>
                Ruoli Globali
                <span class="badge bg-light text-dark ms-2">{{ $rolesByUnit['global']->count() }}</span>
            </span>
            <i class="fas fa-chevron-down collapse-icon"></i>
        </button>
        <div id="globalRolesPermissions" class="collapse show">
            <div class="permissions-table-wrapper">
                <table class="permissions-table">
                    <thead>
                        <tr>
                            <th>Ruolo</th>
                            @foreach($pagePermissions as $pageName => $pageData)
                            <th title="{{ $pageData['display_name'] }}">{{ $pageData['display_name'] }}</th>
                            @endforeach
                            <th style="width: 60px;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rolesByUnit['global'] as $role)
                        @php $isProtectedRole = in_array($role->name, ['admin', 'amministratore']); @endphp
                        <tr data-role-name="{{ strtolower($role->display_name) }}">
                            <td>
                                <div class="d-flex flex-column">
                                    <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                                    @if($isProtectedRole)
                                    <span class="role-badge protected mt-1"><i class="fas fa-lock"></i> Protetto</span>
                                    @endif
                                </div>
                            </td>
                            @foreach($pagePermissions as $pageName => $pageData)
                            <td>
                                <div class="permission-icons">
                                    @if($pageData['view'])
                                    <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                           title="Visualizza" data-role-id="{{ $role->id }}"
                                           data-permission-id="{{ $pageData['view']->id }}"
                                           data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                        <i class="fas fa-eye" style="color: {{ $role->permissions->contains($pageData['view']->id) || $isProtectedRole ? '#0dcaf0' : '#ccc' }};"></i>
                                    </label>
                                    @else<span style="width: 22px;"></span>@endif
                                    @if($pageData['edit'])
                                    <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                           title="Modifica" data-role-id="{{ $role->id }}"
                                           data-permission-id="{{ $pageData['edit']->id }}"
                                           data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                        <i class="fas fa-edit" style="color: {{ $role->permissions->contains($pageData['edit']->id) || $isProtectedRole ? '#ffc107' : '#ccc' }};"></i>
                                    </label>
                                    @else<span style="width: 22px;"></span>@endif
                                </div>
                            </td>
                            @endforeach
                            <td>
                                @if(!$isProtectedRole)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline delete-role-form"
                                      data-role-name="{{ $role->display_name }}" data-users-count="{{ $role->users()->count() }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="action-btn delete delete-role-btn" title="Elimina ruolo"><i class="fas fa-trash"></i></button>
                                </form>
                                @else
                                <span class="text-muted" title="Non eliminabile" style="opacity: 0.4;"><i class="fas fa-lock"></i></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Sezioni per ogni macro-entità -->
    @foreach($macroUnits as $macroUnit)
        @php $unitRoles = $rolesByUnit[$macroUnit->id] ?? collect(); @endphp
        @if($unitRoles->count() > 0)
        <div class="roles-accordion-item mb-3">
            <button class="roles-accordion-header depth-1" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#unit{{ $macroUnit->id }}RolesPermissions">
                <span class="d-flex align-items-center">
                    <i class="fas fa-sitemap me-2"></i>
                    {{ $macroUnit->name }}
                    <span class="badge bg-light text-dark ms-2">{{ $unitRoles->count() }}</span>
                </span>
                <i class="fas fa-chevron-down collapse-icon"></i>
            </button>
            <div id="unit{{ $macroUnit->id }}RolesPermissions" class="collapse">
                <div class="permissions-table-wrapper">
                    <table class="permissions-table">
                        <thead>
                            <tr>
                                <th>Ruolo</th>
                                @foreach($pagePermissions as $pageName => $pageData)
                                <th title="{{ $pageData['display_name'] }}">{{ $pageData['display_name'] }}</th>
                                @endforeach
                                <th style="width: 60px;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unitRoles as $role)
                            @php $isProtectedRole = in_array($role->name, ['admin', 'amministratore']); @endphp
                            <tr data-role-name="{{ strtolower($role->display_name) }}">
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                                    </div>
                                </td>
                                @foreach($pagePermissions as $pageName => $pageData)
                                <td>
                                    <div class="permission-icons">
                                        @if($pageData['view'])
                                        <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                               title="Visualizza" data-role-id="{{ $role->id }}"
                                               data-permission-id="{{ $pageData['view']->id }}"
                                               data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                            <i class="fas fa-eye" style="color: {{ $role->permissions->contains($pageData['view']->id) || $isProtectedRole ? '#0dcaf0' : '#ccc' }};"></i>
                                        </label>
                                        @else<span style="width: 22px;"></span>@endif
                                        @if($pageData['edit'])
                                        <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                               title="Modifica" data-role-id="{{ $role->id }}"
                                               data-permission-id="{{ $pageData['edit']->id }}"
                                               data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                            <i class="fas fa-edit" style="color: {{ $role->permissions->contains($pageData['edit']->id) || $isProtectedRole ? '#ffc107' : '#ccc' }};"></i>
                                        </label>
                                        @else<span style="width: 22px;"></span>@endif
                                    </div>
                                </td>
                                @endforeach
                                <td>
                                    @if(!$isProtectedRole)
                                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline delete-role-form"
                                          data-role-name="{{ $role->display_name }}" data-users-count="{{ $role->users()->count() }}">
                                        @csrf @method('DELETE')
                                        <button type="button" class="action-btn delete delete-role-btn" title="Elimina ruolo"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @else
                                    <span class="text-muted" title="Non eliminabile" style="opacity: 0.4;"><i class="fas fa-lock"></i></span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- TAB 2: Visibilità Militari - Raggruppati per macro-entità -->
<div id="visibilityCard" class="permissions-card hidden">
    <!-- Legenda -->
    <div class="visibility-legend">
        <div class="legend-item">
            <span class="legend-chip active"><i class="fas fa-eye"></i> Visibile</span>
            <span>= Il ruolo può vedere i militari di questa unità organizzativa</span>
        </div>
        <div class="legend-item">
            <span class="legend-chip inactive"><i class="fas fa-eye-slash"></i> Nascosto</span>
            <span>= Il ruolo NON può vedere i militari di questa unità</span>
        </div>
    </div>
    
    <!-- Sezione Ruoli Globali -->
    @if(isset($rolesByUnit['global']) && $rolesByUnit['global']->count() > 0)
    <div class="roles-accordion-item mb-3">
        <button class="roles-accordion-header global" type="button" 
                data-bs-toggle="collapse" data-bs-target="#globalRolesVisibility">
            <span class="d-flex align-items-center">
                <i class="fas fa-globe me-2"></i>
                Ruoli Globali
                <span class="badge bg-light text-dark ms-2">{{ $rolesByUnit['global']->count() }}</span>
            </span>
            <i class="fas fa-chevron-down collapse-icon"></i>
        </button>
        <div id="globalRolesVisibility" class="collapse show">
            <table class="visibility-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Ruolo</th>
                        <th>Unità Organizzative Visibili (clicca per attivare/disattivare)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rolesByUnit['global'] as $role)
                    @php
                        $isProtectedRole = in_array($role->name, ['admin', 'amministratore']);
                        $roleUnitIds = $role->visibleUnits->pluck('id')->toArray();
                    @endphp
                    <tr data-role-name="{{ strtolower($role->display_name) }}">
                        <td>
                            <div class="d-flex flex-column">
                                <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                                @if($isProtectedRole)
                                <span class="role-badge protected mt-1"><i class="fas fa-lock"></i> Tutte</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="company-chips-accordion" data-role-id="{{ $role->id }}" data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                {{-- Ruoli globali possono vedere tutte le macro-entità --}}
                                @foreach($macroUnits as $macroUnit)
                                @php
                                    $childUnits = $organizationalUnits->where('parent_id', $macroUnit->id);
                                    $collapseId = 'collapse-global-' . $role->id . '-' . $macroUnit->id;
                                @endphp
                                <div class="macro-unit-group mb-2">
                                    <button class="macro-unit-header collapsed depth-1" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}">
                                        <span class="d-flex align-items-center gap-2">
                                            <i class="fas fa-chevron-down collapse-icon"></i>
                                            {{ $macroUnit->name }}
                                            <span class="badge bg-light text-dark">{{ $childUnits->count() }}</span>
                                        </span>
                                    </button>
                                    <div id="{{ $collapseId }}" class="collapse">
                                        <div class="macro-unit-content">
                                            @foreach($childUnits as $childUnit)
                                            @php $childActive = in_array($childUnit->id, $roleUnitIds) || $isProtectedRole; @endphp
                                            <div class="company-chip {{ $childActive ? 'active' : 'inactive' }} {{ $isProtectedRole ? 'disabled' : '' }}"
                                                 data-unit-id="{{ $childUnit->id }}" data-role-id="{{ $role->id }}">
                                                <i class="chip-icon fas {{ $childActive ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                                {{ $childUnit->name }}
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    
    <!-- Sezioni per ogni macro-entità -->
    @foreach($macroUnits as $macroUnit)
        @php $unitRoles = $rolesByUnit[$macroUnit->id] ?? collect(); @endphp
        @if($unitRoles->count() > 0)
        <div class="roles-accordion-item mb-3">
            <button class="roles-accordion-header depth-1" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#unit{{ $macroUnit->id }}RolesVisibility">
                <span class="d-flex align-items-center">
                    <i class="fas fa-sitemap me-2"></i>
                    {{ $macroUnit->name }}
                    <span class="badge bg-light text-dark ms-2">{{ $unitRoles->count() }}</span>
                </span>
                <i class="fas fa-chevron-down collapse-icon"></i>
            </button>
            <div id="unit{{ $macroUnit->id }}RolesVisibility" class="collapse">
                <table class="visibility-table">
                    <thead>
                        <tr>
                            <th style="width: 200px;">Ruolo</th>
                            <th>Unità Visibili (solo della propria macro-entità)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unitRoles as $role)
                        @php
                            $isProtectedRole = in_array($role->name, ['admin', 'amministratore']);
                            $roleUnitIds = $role->visibleUnits->pluck('id')->toArray();
                            $childUnits = $organizationalUnits->where('parent_id', $macroUnit->id);
                        @endphp
                        <tr data-role-name="{{ strtolower($role->display_name) }}">
                            <td>
                                <div class="d-flex flex-column">
                                    <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                                </div>
                            </td>
                            <td>
                                <div class="company-chips-accordion" data-role-id="{{ $role->id }}" data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                    {{-- Ruoli di questa macro-entità vedono SOLO le unità figlie --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @forelse($childUnits as $childUnit)
                                        @php $childActive = in_array($childUnit->id, $roleUnitIds) || $isProtectedRole; @endphp
                                        <div class="company-chip {{ $childActive ? 'active' : 'inactive' }} {{ $isProtectedRole ? 'disabled' : '' }}"
                                             data-unit-id="{{ $childUnit->id }}" data-role-id="{{ $role->id }}">
                                            <i class="chip-icon fas {{ $childActive ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                            {{ $childUnit->name }}
                                        </div>
                                        @empty
                                        <span class="text-muted small">Nessuna unità figlia disponibile</span>
                                        @endforelse
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach
</div>

<!-- CARD 3: GESTIONE UTENTI -->
<div id="usersCard" class="permissions-card hidden">
    <div class="accordion" id="usersTabAccordion">
        <!-- SEZIONE RUOLI -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#rolesSection"
                        aria-expanded="true" aria-controls="rolesSection">
                    <i class="fas fa-user-tag me-2"></i> Ruoli
                    <span class="badge bg-primary ms-2">{{ $roles->count() }}</span>
                </button>
            </h2>
            <div id="rolesSection" class="accordion-collapse collapse show" 
                 data-bs-parent="#usersTabAccordion">
                <div class="accordion-body">
                    <!-- Ruoli Globali -->
                    @if(isset($rolesByUnit['global']) && $rolesByUnit['global']->count() > 0)
                    <div class="roles-sub-accordion mb-3">
                        <button class="roles-sub-header global" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#globalRolesList">
                            <span class="d-flex align-items-center">
                                <i class="fas fa-globe me-2"></i>
                                Ruoli Globali
                                <span class="badge bg-light text-dark ms-2">{{ $rolesByUnit['global']->count() }}</span>
                            </span>
                            <i class="fas fa-chevron-down collapse-icon"></i>
                        </button>
                        <div id="globalRolesList" class="collapse show">
                            <table class="sugeco-table">
                                <thead>
                                    <tr>
                                        <th>NOME RUOLO</th>
                                        <th>UTENTI</th>
                                        <th style="width: 120px;">AZIONI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rolesByUnit['global'] as $role)
                                    <tr data-role-name="{{ strtolower($role->display_name) }}">
                                        <td><strong>{{ $role->display_name }}</strong></td>
                                        <td><span class="badge bg-info">{{ $role->users->count() }} utenti</span></td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                @if(!in_array($role->name, ['admin', 'amministratore']))
                                                <button class="btn btn-outline-primary edit-role-btn-users"
                                                        data-role-id="{{ $role->id }}" data-role-name="{{ $role->display_name }}"
                                                        title="Rinomina ruolo"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-outline-danger delete-role-btn-users"
                                                        data-role-id="{{ $role->id }}" data-role-name="{{ $role->display_name }}"
                                                        title="Elimina ruolo"><i class="fas fa-trash"></i></button>
                                                @else
                                                <span class="text-muted small"><i class="fas fa-lock"></i> Protetto</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Ruoli per macro-entità -->
                    @foreach($macroUnits as $macroUnit)
                        @php $unitRoles = $rolesByUnit[$macroUnit->id] ?? collect(); @endphp
                        @if($unitRoles->count() > 0)
                        <div class="roles-sub-accordion mb-3">
                            <button class="roles-sub-header depth-1" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#unit{{ $macroUnit->id }}RolesList">
                                <span class="d-flex align-items-center">
                                    <i class="fas fa-sitemap me-2"></i>
                                    {{ $macroUnit->name }}
                                    <span class="badge bg-light text-dark ms-2">{{ $unitRoles->count() }}</span>
                                </span>
                                <i class="fas fa-chevron-down collapse-icon"></i>
                            </button>
                            <div id="unit{{ $macroUnit->id }}RolesList" class="collapse">
                                <table class="sugeco-table">
                                    <thead>
                                        <tr>
                                            <th>NOME RUOLO</th>
                                            <th>UTENTI</th>
                                            <th style="width: 120px;">AZIONI</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($unitRoles as $role)
                                        <tr data-role-name="{{ strtolower($role->display_name) }}">
                                            <td><strong>{{ $role->display_name }}</strong></td>
                                            <td><span class="badge bg-info">{{ $role->users->count() }} utenti</span></td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button class="btn btn-outline-primary edit-role-btn-users"
                                                            data-role-id="{{ $role->id }}" data-role-name="{{ $role->display_name }}"
                                                            title="Rinomina ruolo"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-outline-danger delete-role-btn-users"
                                                            data-role-id="{{ $role->id }}" data-role-name="{{ $role->display_name }}"
                                                            title="Elimina ruolo"><i class="fas fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- SEZIONE UTENTI -->
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#usersSection"
                        aria-expanded="false" aria-controls="usersSection">
                    <i class="fas fa-users me-2"></i> Utenti
                    <span class="badge bg-primary ms-2">{{ $users->count() }}</span>
                </button>
            </h2>
            <div id="usersSection" class="accordion-collapse collapse" 
                 data-bs-parent="#usersTabAccordion">
                <div class="accordion-body">
                    <div class="permissions-table-wrapper">
                        <table class="sugeco-table" id="usersTable">
                            <thead>
                                <tr>
                                    <th>NOME</th>
                                    <th>USERNAME</th>
                                    <th>RUOLO</th>
                                    <th>UNITÀ</th>
                                    <th>CREATO</th>
                                    <th style="width: 140px;">AZIONI</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                @forelse($users as $user)
                                <tr data-user-name="{{ strtolower($user->name) }}" data-username="{{ strtolower($user->username ?? '') }}">
                                    <td>
                                        <strong>{{ $user->name }}</strong>
                                        @if($user->must_change_password)
                                            <span class="badge bg-warning text-dark ms-2">
                                                <i class="fas fa-exclamation-triangle"></i> Cambio password
                                            </span>
                                        @endif
                                    </td>
                                    <td><code>{{ $user->username ?? 'N/A' }}</code></td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            {{ $role->display_name }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($user->organizationalUnit)
                                            <span class="badge" style="background-color: {{ $user->organizationalUnit->type->color ?? '#0A2342' }}; color: white;">
                                                {{ $user->organizationalUnit->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">Non assegnata</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" 
                                               class="btn btn-outline-primary edit-user-btn-tab" 
                                               title="Modifica utente"
                                               data-user-id="{{ $user->id }}"
                                               data-user-name="{{ $user->name }}"
                                               data-user-username="{{ $user->username }}"
                                               data-user-unit-id="{{ $user->organizational_unit_id }}"
                                               data-user-role-id="{{ $user->roles->first()?->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('admin.reset-password', $user) }}" 
                                                  method="POST" 
                                                  class="d-inline reset-password-form-tab"
                                                  data-user-name="{{ $user->name }}">
                                                @csrf
                                                <button type="button" 
                                                        class="btn btn-outline-warning reset-password-btn-tab" 
                                                        title="Reset password">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.destroy', $user) }}" 
                                                  method="POST" 
                                                  class="d-inline delete-user-form-tab"
                                                  data-user-name="{{ $user->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                        class="btn btn-outline-danger delete-user-btn-tab" 
                                                        title="Elimina utente">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-users fa-3x mb-3 d-block"></i>
                                        Nessun utente presente
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rinomina Ruolo -->
<div class="modal fade" id="renameRoleModal" tabindex="-1" aria-labelledby="renameRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); color: white;">
                <h5 class="modal-title" id="renameRoleModalLabel">
                    <i class="fas fa-edit me-2"></i>Rinomina Ruolo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="renameRoleForm">
                    @csrf
                    <input type="hidden" id="renameRoleId" name="role_id">
                    <div class="mb-3">
                        <label for="renameRoleDisplayName" class="form-label fw-bold">
                            Nome Visualizzato
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="renameRoleDisplayName" 
                               name="display_name" 
                               required
                               placeholder="Es. Comandante Battaglione Leonessa">
                        <div class="form-text">
                            Questo è il nome che verrà mostrato in tutte le interfacce
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annulla
                </button>
                <button type="button" class="btn btn-primary" id="confirmRenameRole">
                    <i class="fas fa-save me-1"></i>Salva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Crea Nuovo Ruolo -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-labelledby="createRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); color: white;">
                <h5 class="modal-title" id="createRoleModalLabel">
                    <i class="fas fa-user-tag me-2"></i>Crea Nuovo Ruolo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.roles.store') }}" method="POST" id="createRoleForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="newRoleDisplayName" class="form-label fw-bold">Nome Ruolo *</label>
                        <input type="text" class="form-control" id="newRoleDisplayName" name="display_name" 
                               placeholder="es: Responsabile Logistica" required>
                        <div class="form-text">Il nome che verrà mostrato in tutte le interfacce</div>
                    </div>
                    <input type="hidden" id="newRoleName" name="name" value="">
                    
                    <div class="mb-3">
                        <label for="newRoleUnitId" class="form-label fw-bold">Macro-entità di appartenenza *</label>
                        <select class="form-select" id="newRoleUnitId" name="organizational_unit_id" required>
                            <option value="" disabled selected>-- Seleziona un'entità --</option>
                            <option value="global" data-is-global="true">Globale (visibile in tutte le unità)</option>
                            @foreach($macroUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Seleziona l'entità a cui appartiene il ruolo</div>
                    </div>
                    <input type="hidden" id="newRoleIsGlobal" name="is_global" value="0">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirmCreateRole">
                        <i class="fas fa-plus me-1"></i>Crea Ruolo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Crea/Modifica Utente -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%); color: white;">
                <h5 class="modal-title" id="createUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Crea Nuovo Utente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.store') }}" method="POST" id="createUserForm">
                @csrf
                <input type="hidden" name="_method" id="userFormMethod" value="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="newUserName" class="form-label fw-bold">Nome Completo *</label>
                            <input type="text" class="form-control" id="newUserName" name="name" 
                                   placeholder="Mario Rossi" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newUserUsername" class="form-label fw-bold">Username *</label>
                            <input type="text" class="form-control" id="newUserUsername" name="username" 
                                   placeholder="mario.rossi" style="text-transform:lowercase" required>
                            <div class="form-text">Formato: nome.cognome (minuscolo)</div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info py-2 small mb-3" id="passwordInfoAlert">
                        <i class="fas fa-info-circle me-1"></i>
                        Password di default: <strong>11Reggimento</strong> (dovrà essere cambiata al primo accesso)
                    </div>
                    
                    <hr class="my-3">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="newUserUnitId" class="form-label fw-bold">Unità Organizzativa *</label>
                            <select class="form-select" id="newUserUnitId" name="organizational_unit_id" required>
                                <option value="" disabled selected>-- Seleziona un'entità --</option>
                                <option value="global">Globale (accesso a tutte le unità)</option>
                                @foreach($macroUnits as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Seleziona un'unità o "Globale" per accesso completo</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="newUserRoleId" class="form-label fw-bold">
                                Ruolo *
                                <small class="text-muted" id="userRoleLoading" style="display: none;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </small>
                            </label>
                            <select class="form-select" id="newUserRoleId" name="role_id" required disabled>
                                <option value="">-- Prima seleziona un'unità --</option>
                            </select>
                            <div class="form-text" id="userRoleHelpText">Seleziona prima l'unità organizzativa</div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="compagnia_id" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-primary" id="confirmCreateUser">
                        <i class="fas fa-save me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal conferma eliminazione gestito da SUGECO.Confirm -->

<!-- Floating Action Buttons -->
<div class="fab-container">
    <button type="button" class="fab-stacked fab-create-user" 
       data-tooltip="Nuovo Utente"
       data-bs-toggle="modal" data-bs-target="#createUserModal">
        <i class="fas fa-user-plus"></i>
    </button>
    <button type="button" class="fab-stacked fab-create-role" 
       data-tooltip="Nuovo Ruolo"
       data-bs-toggle="modal" data-bs-target="#createRoleModal">
        <i class="fas fa-user-tag"></i>
    </button>
</div>

<!-- Dati per JavaScript -->
<script>
    window.permissionsData = {
        updatePermissionsUrl: "{{ url('/admin/permessi/ruoli') }}",
        updateVisibilityUrl: "{{ url('/admin/ruoli') }}",
        renameRoleBaseUrl: "{{ url('/admin/ruoli') }}",
        csrfToken: "{{ csrf_token() }}",
        rolePermissions: @json($roles->mapWithKeys(fn($r) => [$r->id => $r->permissions->pluck('id')->toArray()]))
    };
</script>
@endsection

@push('scripts')
<script>
let currentTab = 'permissions';

function switchTab(tab, updateUrl = true) {
    currentTab = tab;
    
    // Nascondi/mostra le card
    document.getElementById('permissionsCard').classList.toggle('hidden', tab !== 'permissions');
    document.getElementById('visibilityCard').classList.toggle('hidden', tab !== 'visibility');
    document.getElementById('usersCard').classList.toggle('hidden', tab !== 'users');
    
    // Aggiorna stato attivo dei bottoni
    document.getElementById('permissionsBtn').classList.toggle('active', tab === 'permissions');
    document.getElementById('visibilityBtn').classList.toggle('active', tab === 'visibility');
    document.getElementById('usersBtn').classList.toggle('active', tab === 'users');
    
    // Sposta lo slider
    const slider = document.getElementById('toggleSlider');
    slider.classList.remove('pos-1', 'pos-2', 'pos-3');
    if (tab === 'permissions') slider.classList.add('pos-1');
    else if (tab === 'visibility') slider.classList.add('pos-2');
    else slider.classList.add('pos-3');
    
    // Cambia placeholder ricerca in base alla tab
    const searchInput = document.getElementById('searchRole');
    if (tab === 'users') {
        searchInput.placeholder = 'Cerca ruolo o utente...';
    } else {
        searchInput.placeholder = 'Cerca ruolo...';
    }
    
    // Reset ricerca
    searchInput.value = '';
    filterContent('');
    
    // Aggiorna URL per persistenza (senza ricaricare)
    if (updateUrl) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
        localStorage.setItem('permissionsActiveTab', tab);
    }
}

// Ricerca adattativa per tutte le tab
function filterContent(term) {
    const searchTerm = term.toLowerCase();
    
    if (currentTab === 'users') {
        // Cerca sia in ruoli che in utenti nella tab Users
        
        // Filtra tabella ruoli negli accordion per macro-entità
        document.querySelectorAll('#rolesSection [data-role-name]').forEach(row => {
            const roleName = row.dataset.roleName || '';
            row.style.display = roleName.includes(searchTerm) ? '' : 'none';
        });
        
        // Mostra/nascondi accordion vuoti nella sezione ruoli
        document.querySelectorAll('#rolesSection .roles-sub-accordion').forEach(accordion => {
            const visibleRows = accordion.querySelectorAll('[data-role-name]:not([style*="display: none"])');
            accordion.style.display = visibleRows.length > 0 ? '' : 'none';
        });
        
        // Filtra tabella utenti nella sezione accordion
        document.querySelectorAll('#usersTableBody tr').forEach(row => {
            const userName = row.dataset.userName || '';
            const username = row.dataset.username || '';
            const roleText = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            
            const matches = userName.includes(searchTerm) || 
                          username.includes(searchTerm) || 
                          roleText.includes(searchTerm);
            
            row.style.display = matches ? '' : 'none';
        });
    } else {
        // Filtra ruoli nelle tab Permessi e Visibilità
        filterRoles(searchTerm);
    }
}

function filterRoles(term) {
    const searchTerm = term.toLowerCase();
    
    // Filtra nelle card permissions e visibility
    document.querySelectorAll('#permissionsCard [data-role-name], #visibilityCard [data-role-name]').forEach(row => {
        const visible = row.dataset.roleName.includes(searchTerm);
        row.style.display = visible ? '' : 'none';
    });
    
    // Mostra/nascondi accordion vuoti
    document.querySelectorAll('#permissionsCard .roles-accordion-item, #visibilityCard .roles-accordion-item').forEach(accordion => {
        const visibleRows = accordion.querySelectorAll('[data-role-name]:not([style*="display: none"])');
        accordion.style.display = visibleRows.length > 0 ? '' : 'none';
    });
}

function showToast(message, type) {
    const existing = document.querySelector('.perm-toast');
    if (existing) existing.remove();
    
    const colors = { info: '#0dcaf0', success: '#198754', error: '#dc3545', warning: '#ffc107' };
    const icons = { info: 'fa-spinner fa-spin', success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' };
    
    const toast = document.createElement('div');
    toast.className = 'perm-toast alert position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg';
    toast.style.cssText = `z-index:9999; background:white; border-left:4px solid ${colors[type]}; min-width:280px;`;
    toast.innerHTML = `<i class="fas ${icons[type]} me-2" style="color:${colors[type]}"></i>${message}`;
    document.body.appendChild(toast);
    
    if (type !== 'info') {
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }
}

async function updateRolePermissions(roleId, permissionIds) {
    const formData = new FormData();
    formData.append('_token', window.permissionsData.csrfToken);
    permissionIds.forEach(id => formData.append('permissions[]', id));
    
    try {
        const response = await fetch(`${window.permissionsData.updatePermissionsUrl}/${roleId}`, {
            method: 'POST',
            body: formData
        });
        
        // FIX: Gestione errori HTTP specifica
        if (response.ok) {
            showToast('Permesso aggiornato!', 'success');
            return true;
        } else {
            // Gestisci codici HTTP specifici
            let errorMsg = 'Errore nel salvataggio';
            switch (response.status) {
                case 401:
                    errorMsg = 'Sessione scaduta. Ricarica la pagina.';
                    break;
                case 403:
                    errorMsg = 'Non hai i permessi per questa operazione';
                    break;
                case 400:
                    try {
                        const data = await response.json();
                        errorMsg = data.message || 'Ruolo protetto o dati non validi';
                    } catch (e) {
                        errorMsg = 'Ruolo protetto - non modificabile';
                    }
                    break;
                case 422:
                    errorMsg = 'Dati non validi';
                    break;
                case 500:
                    errorMsg = 'Errore del server. Riprova più tardi.';
                    break;
            }
            showToast(errorMsg, 'error');
            return false;
        }
    } catch (e) {
        showToast('Errore di connessione. Verifica la tua connessione internet.', 'error');
        return false;
    }
}

async function updateRoleVisibility(roleId, unitIds) {
    try {
        const response = await fetch(`${window.permissionsData.updateVisibilityUrl}/${roleId}/unita-visibili`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': window.permissionsData.csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ visible_units: unitIds })
        });
        
        // FIX: Gestione errori HTTP specifica
        if (!response.ok) {
            let errorMsg = 'Errore nel salvataggio';
            switch (response.status) {
                case 401:
                    errorMsg = 'Sessione scaduta. Ricarica la pagina.';
                    break;
                case 403:
                    errorMsg = 'Non hai i permessi per questa operazione';
                    break;
                case 400:
                    try {
                        const data = await response.json();
                        errorMsg = data.message || 'Ruolo protetto o dati non validi';
                    } catch (e) {
                        errorMsg = 'Ruolo protetto - non modificabile';
                    }
                    break;
                case 422:
                    errorMsg = 'Dati non validi';
                    break;
                case 500:
                    errorMsg = 'Errore del server. Riprova più tardi.';
                    break;
            }
            showToast(errorMsg, 'error');
            return false;
        }
        
        const result = await response.json();
        
        if (result.success) {
            showToast('Visibilità aggiornata!', 'success');
            return true;
        } else {
            showToast(result.message || 'Errore nel salvataggio', 'error');
            return false;
        }
    } catch (e) {
        console.error('Visibility error:', e);
        showToast('Errore di connessione. Verifica la tua connessione internet.', 'error');
        return false;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Delete handlers con sistema conferma unificato
    document.querySelectorAll('.delete-role-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('.delete-role-form');
            const roleName = form.dataset.roleName;
            const usersCount = parseInt(form.dataset.usersCount);
            
            let message = `Eliminare il ruolo "${roleName}"?`;
            if (usersCount > 0) {
                message = `Eliminare il ruolo "${roleName}"? Attenzione: ${usersCount} utent${usersCount === 1 ? 'e ha' : 'i hanno'} questo ruolo.`;
            }
            
            const confirmed = await SUGECO.Confirm.delete(message);
            if (confirmed) {
                form.submit();
            }
        });
    });
    
    // FIX RACE CONDITION: Flag per prevenire click multipli
    let isSavingPermissions = false;
    
    // Permission icons click handler
    document.querySelectorAll('.icon-permission').forEach(icon => {
        icon.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Protezione anti-doppio click
            if (isSavingPermissions) {
                return;
            }
            
            if (this.dataset.protected === 'true') {
                showToast('Ruolo protetto - non modificabile', 'warning');
                return;
            }
            
            const roleId = this.dataset.roleId;
            const permissionId = parseInt(this.dataset.permissionId);
            const iconEl = this.querySelector('i');
            const isView = iconEl.classList.contains('fa-eye');
            
            // Salva stato originale PRIMA della modifica
            const originalColor = iconEl.style.color;
            let permissions = [...window.permissionsData.rolePermissions[roleId]];
            const wasActive = permissions.includes(permissionId);
            
            // Calcola nuovi permessi
            if (wasActive) {
                permissions = permissions.filter(p => p !== permissionId);
            } else {
                permissions.push(permissionId);
            }
            
            // Blocca ulteriori click
            isSavingPermissions = true;
            this.style.pointerEvents = 'none';
            this.style.opacity = '0.5';
            
            showToast('Salvataggio...', 'info');
            
            const success = await updateRolePermissions(roleId, permissions);
            
            // Riabilita click
            isSavingPermissions = false;
            this.style.pointerEvents = '';
            this.style.opacity = '';
            
            if (success) {
                // Aggiorna UI solo dopo successo
                window.permissionsData.rolePermissions[roleId] = permissions;
                const isActive = !wasActive;
                iconEl.style.color = isActive ? (isView ? '#0dcaf0' : '#ffc107') : '#ccc';
            }
            // Se fallisce, l'UI rimane invariata (stato originale)
        });
    });
    
    // Debounce timer per raggruppare click rapidi
    let saveTimers = {};
    
    // Company chips click handler con aggiornamento ottimistico
    document.querySelectorAll('.company-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            // FIX: Usa il selettore corretto (company-chips-accordion invece di company-chips)
            const container = this.closest('.company-chips-accordion');
            
            // FIX: Controllo null-safety per evitare errore
            if (!container) {
                console.warn('Container non trovato per company-chip');
                return;
            }
            
            if (container.dataset.protected === 'true') {
                showToast('Ruolo protetto - non modificabile', 'warning');
                return;
            }
            
            const roleId = container.dataset.roleId;
            if (!roleId) {
                console.warn('roleId non trovato nel container');
                return;
            }
            
            const iconEl = this.querySelector('.chip-icon');
            
            // Aggiorna UI IMMEDIATAMENTE (ottimistico)
            this.classList.toggle('active');
            this.classList.toggle('inactive');
            if (iconEl) {
                iconEl.classList.toggle('fa-eye');
                iconEl.classList.toggle('fa-eye-slash');
            }
            
            // Cancella timer precedente per questo ruolo
            if (saveTimers[roleId]) {
                clearTimeout(saveTimers[roleId]);
            }
            
            // Debounce: invia richiesta dopo 500ms di inattività
            saveTimers[roleId] = setTimeout(async () => {
                // Raccogli TUTTE le unità attive al momento del salvataggio
                const activeUnits = [];
                container.querySelectorAll('.company-chip.active').forEach(c => {
                    if (c.dataset.unitId) {
                        activeUnits.push(c.dataset.unitId);
                    }
                });
                
                showToast('Salvataggio...', 'info');
                const success = await updateRoleVisibility(roleId, activeUnits);
                
                if (!success) {
                    showToast('Errore nel salvataggio, ricaricare la pagina', 'error');
                }
            }, 500); // Aspetta 500ms dopo l'ultimo click
        });
    });
    
    // Search adattativo per tutte le tab
    document.getElementById('searchRole').addEventListener('input', function() {
        filterContent(this.value);
    });
    
    // Reset Password handler per tab utenti
    document.querySelectorAll('.reset-password-btn-tab').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('.reset-password-form-tab');
            const userName = form.dataset.userName;
            
            const confirmed = await SUGECO.Confirm.show({
                title: 'Reset Password',
                message: `Resettare la password di ${userName} a "11Reggimento"? L'utente dovrà cambiarla al prossimo accesso.`,
                type: 'warning',
                confirmText: 'Reset Password'
            });
            
            if (confirmed) {
                form.submit();
            }
        });
    });
    
    // Delete User handler per tab utenti
    document.querySelectorAll('.delete-user-btn-tab').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('.delete-user-form-tab');
            const userName = form.dataset.userName;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare definitivamente l'utente ${userName}? Questa azione è irreversibile!`);
            
            if (confirmed) {
                form.submit();
            }
        });
    });
    
    // Edit Role handler per tab utenti (sezione ruoli)
    document.querySelectorAll('.edit-role-btn-users').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const roleId = this.dataset.roleId;
            const roleName = this.dataset.roleName;
            
            // Popola il modal
            document.getElementById('renameRoleId').value = roleId;
            document.getElementById('renameRoleDisplayName').value = roleName;
            
            // Mostra il modal
            const modal = new bootstrap.Modal(document.getElementById('renameRoleModal'));
            modal.show();
        });
    });
    
    // Conferma rinomina ruolo
    document.getElementById('confirmRenameRole').addEventListener('click', async function() {
        const roleId = document.getElementById('renameRoleId').value;
        const newDisplayName = document.getElementById('renameRoleDisplayName').value.trim();
        
        if (!newDisplayName) {
            showToast('Il nome del ruolo non può essere vuoto', 'error');
            return;
        }
        
        // Disabilita il pulsante durante il salvataggio
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
        
        try {
            // FIX: Usa l'URL base generato da Blade invece di path relativo
            const targetUrl = `${window.permissionsData.renameRoleBaseUrl}/${roleId}/rename`;
            const response = await fetch(targetUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.permissionsData.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ display_name: newDisplayName })
            });
            
            const result = await response.json();
            
            if (response.ok && result.success) {
                showToast('Ruolo rinominato con successo!', 'success');
                
                // Chiudi il modal
                bootstrap.Modal.getInstance(document.getElementById('renameRoleModal')).hide();
                
                // Ricarica la pagina dopo 1 secondo
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(result.message || 'Errore durante la rinomina', 'error');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Errore di connessione', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
        }
    });
    
    // Delete Role handler per tab utenti (sezione ruoli)
    document.querySelectorAll('.delete-role-btn-users').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const roleId = this.dataset.roleId;
            const roleName = this.dataset.roleName;
            
            const confirmed = await SUGECO.Confirm.delete(
                `Eliminare il ruolo "${roleName}"? Questa azione è irreversibile e rimuoverà il ruolo da tutti gli utenti associati.`
            );
            
            if (confirmed) {
                // Crea form per DELETE
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/ruoli/${roleId}`;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = window.permissionsData.csrfToken;
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                
                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
    
    // Inizializza tab dal URL o localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const tabFromUrl = urlParams.get('tab');
    const tabFromStorage = localStorage.getItem('permissionsActiveTab');
    const initialTab = tabFromUrl || tabFromStorage || 'permissions';
    
    if (['permissions', 'visibility', 'users'].includes(initialTab)) {
        switchTab(initialTab, false);
    }
    
    // ==========================================
    // MODAL CREA RUOLO
    // ==========================================
    
    // Genera nome tecnico dal nome visualizzato
    document.getElementById('newRoleDisplayName').addEventListener('input', function() {
        const displayName = this.value;
        const technicalName = displayName
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s]/g, '')
            .trim()
            .replace(/\s+/g, '_');
        document.getElementById('newRoleName').value = technicalName;
    });
    
    // Gestione is_global per creazione ruolo
    document.getElementById('newRoleUnitId').addEventListener('change', function() {
        const isGlobal = this.value === 'global';
        document.getElementById('newRoleIsGlobal').value = isGlobal ? '1' : '0';
    });
    
    // Intercetta submit per convertire "global" in valore vuoto
    document.getElementById('createRoleForm').addEventListener('submit', function(e) {
        const unitSelect = document.getElementById('newRoleUnitId');
        // Se è "global", cambia il valore in stringa vuota prima del submit
        if (unitSelect.value === 'global') {
            unitSelect.value = '';
        }
    });
    
    // Reset form quando si chiude il modal creazione ruolo
    document.getElementById('createRoleModal').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('createRoleForm');
        form.reset();
        document.getElementById('newRoleName').value = '';
        document.getElementById('newRoleIsGlobal').value = '0';
    });
    
    // ==========================================
    // MODAL CREA/MODIFICA UTENTE
    // ==========================================
    
    // URL base per API ruoli
    const rolesApiUrl = '{{ url("/admin/ruoli/per-unita") }}';
    const userStoreUrl = '{{ route("admin.store") }}';
    const userUpdateBaseUrl = '{{ url("/admin/utenti") }}';
    
    // Forza minuscolo per username
    document.getElementById('newUserUsername').addEventListener('input', function() {
        this.value = this.value.toLowerCase();
    });
    
    // Carica ruoli quando cambia l'unità
    document.getElementById('newUserUnitId').addEventListener('change', async function() {
        const unitId = this.value;
        const roleSelect = document.getElementById('newUserRoleId');
        const loadingIndicator = document.getElementById('userRoleLoading');
        const roleHelpText = document.getElementById('userRoleHelpText');
        
        // Se nessuna selezione valida, disabilita ruoli
        if (!unitId || unitId === '') {
            roleSelect.innerHTML = '<option value="">-- Prima seleziona un\'unità --</option>';
            roleSelect.disabled = true;
            roleHelpText.textContent = 'Seleziona prima l\'unità organizzativa';
            return;
        }
        
        roleSelect.innerHTML = '<option value="">Caricamento...</option>';
        roleSelect.disabled = true;
        loadingIndicator.style.display = 'inline';
        
        if (unitId === 'global') {
            // Unità globale: mostra solo ruoli globali
            roleSelect.innerHTML = '<option value="">-- Seleziona ruolo --</option>';
            @foreach($roles->where('is_global', true) as $role)
            roleSelect.innerHTML += '<option value="{{ $role->id }}">{{ $role->display_name }} (Globale)</option>';
            @endforeach
            roleHelpText.textContent = 'Ruoli globali disponibili';
            roleHelpText.classList.remove('text-danger');
            loadingIndicator.style.display = 'none';
            roleSelect.disabled = false;
            return;
        }
        
        try {
            const response = await fetch(`${rolesApiUrl}/${unitId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            if (!response.ok) throw new Error('Errore');
            
            const data = await response.json();
            roleSelect.innerHTML = '<option value="">-- Seleziona ruolo --</option>';
            
            if (data.roles && data.roles.length > 0) {
                // Mostra SOLO i ruoli specifici di questa unità (NON globali)
                let unitRoles = data.roles.filter(r => !r.is_global && r.organizational_unit_id == unitId);
                unitRoles.forEach(role => {
                    roleSelect.innerHTML += `<option value="${role.id}">${role.display_name}</option>`;
                });
                roleHelpText.textContent = `${unitRoles.length} ruoli disponibili per questa unità`;
                roleHelpText.classList.remove('text-danger');
            } else {
                roleHelpText.textContent = 'Nessun ruolo per questa unità';
                roleHelpText.classList.add('text-danger');
            }
        } catch (error) {
            roleHelpText.textContent = 'Errore caricamento ruoli';
            roleHelpText.classList.add('text-danger');
        } finally {
            loadingIndicator.style.display = 'none';
            roleSelect.disabled = false;
        }
    });
    
    // Intercetta submit per convertire "global" in valore vuoto
    document.getElementById('createUserForm').addEventListener('submit', function(e) {
        const unitSelect = document.getElementById('newUserUnitId');
        // Se è "global", cambia il valore in stringa vuota prima del submit
        if (unitSelect.value === 'global') {
            unitSelect.value = '';
        }
    });
    
    // Reset form quando si chiude il modal utente
    document.getElementById('createUserModal').addEventListener('hidden.bs.modal', function() {
        const form = document.getElementById('createUserForm');
        form.reset();
        form.action = userStoreUrl;
        document.getElementById('userFormMethod').value = 'POST';
        document.getElementById('createUserModalLabel').innerHTML = '<i class="fas fa-user-plus me-2"></i>Crea Nuovo Utente';
        document.getElementById('confirmCreateUser').innerHTML = '<i class="fas fa-save me-1"></i>Salva';
        document.getElementById('passwordInfoAlert').style.display = 'block';
        document.getElementById('newUserRoleId').innerHTML = '<option value="">-- Prima seleziona un\'unità --</option>';
        document.getElementById('newUserRoleId').disabled = true;
        document.getElementById('userRoleHelpText').textContent = 'Seleziona prima l\'unità organizzativa';
    });
    
    // Handler per modifica utente dalla tabella
    document.querySelectorAll('.edit-user-btn-tab').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const userId = this.dataset.userId;
            const userName = this.dataset.userName;
            const userUsername = this.dataset.userUsername;
            const userUnitId = this.dataset.userUnitId || '';
            const userRoleId = this.dataset.userRoleId;
            
            // Popola il form
            document.getElementById('newUserName').value = userName;
            document.getElementById('newUserUsername').value = userUsername;
            
            // Se unitId è vuoto, l'utente è globale
            const unitValue = userUnitId === '' ? 'global' : userUnitId;
            document.getElementById('newUserUnitId').value = unitValue;
            
            // Configura per modifica
            const form = document.getElementById('createUserForm');
            form.action = `${userUpdateBaseUrl}/${userId}`;
            document.getElementById('userFormMethod').value = 'PUT';
            document.getElementById('createUserModalLabel').innerHTML = '<i class="fas fa-user-edit me-2"></i>Modifica Utente';
            document.getElementById('confirmCreateUser').innerHTML = '<i class="fas fa-save me-1"></i>Salva Modifiche';
            document.getElementById('passwordInfoAlert').style.display = 'none';
            
            // Carica i ruoli per l'unità selezionata e poi seleziona il ruolo corrente
            const unitSelect = document.getElementById('newUserUnitId');
            unitSelect.dispatchEvent(new Event('change'));
            
            // Aspetta che i ruoli vengano caricati e poi seleziona
            setTimeout(() => {
                document.getElementById('newUserRoleId').value = userRoleId;
            }, 500);
            
            // Mostra il modal
            const modal = new bootstrap.Modal(document.getElementById('createUserModal'));
            modal.show();
        });
    });
});
</script>
@endpush
