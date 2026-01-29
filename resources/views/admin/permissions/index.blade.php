@extends('layouts.app')

@section('title', 'Gestione Ruoli e Permessi')

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
    width: calc(50% - 0.4rem);
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    border-radius: 50px;
    transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 4px 15px rgba(10, 35, 66, 0.25);
}

.toggle-slider.pos-1 { transform: translateX(0); }
.toggle-slider.pos-2 { transform: translateX(100%); }

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
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Ruoli e Permessi</h1>
</div>

<!-- Toggle per le 2 schermate -->
<div class="d-flex justify-content-center mb-4">
    <div class="toggle-wrapper">
        <div id="toggleSlider" class="toggle-slider pos-1"></div>
        <div id="permissionsBtn" class="toggle-option active" onclick="switchTab('permissions')">
            <i class="fas fa-key"></i> Permessi Pagine
        </div>
        <div id="visibilityBtn" class="toggle-option" onclick="switchTab('visibility')">
            <i class="fas fa-building"></i> Visibilità Militari
        </div>
    </div>
</div>

<!-- Barra ricerca e azioni -->
<div class="d-flex justify-content-center align-items-center gap-3 mb-4 flex-wrap">
    <div class="permissions-search">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="searchRole" placeholder="Cerca ruolo...">
    </div>
    
    <a href="{{ route('admin.roles.create') }}" class="btn-action-header">
        <i class="fas fa-plus me-2"></i>Nuovo Ruolo
    </a>
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
        'idoneita' => 'Idoneità',
        'impieghi' => 'Organici',
        'organigramma' => 'Organigramma',
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

<!-- TAB 1: Permessi Pagine -->
<div id="permissionsCard" class="permissions-card">
    <div class="permissions-table-wrapper">
        <table class="permissions-table">
            <thead>
                <tr>
                    <th>Ruolo</th>
                    @foreach($pagePermissions as $pageName => $pageData)
                    <th title="{{ $pageData['display_name'] }}">
                        {{ $pageData['display_name'] }}
                    </th>
                    @endforeach
                    <th style="width: 60px;"><i class="fas fa-trash-alt"></i></th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                @php
                    $isProtectedRole = in_array($role->name, ['admin', 'amministratore']);
                @endphp
                <tr data-role-name="{{ strtolower($role->display_name) }}">
                    <td>
                        <div class="d-flex flex-column">
                            <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                            <div class="d-flex gap-1 mt-1 flex-wrap">
                                @if($isProtectedRole)
                                <span class="role-badge protected">
                                    <i class="fas fa-lock"></i> Protetto
                                </span>
                                @endif
                                @if($role->is_global)
                                <span class="role-badge global">
                                    <i class="fas fa-globe"></i> Globale
                                </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    
                    @foreach($pagePermissions as $pageName => $pageData)
                    <td>
                        <div class="permission-icons">
                            @if($pageData['view'])
                            <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                   title="Visualizza"
                                   data-role-id="{{ $role->id }}"
                                   data-permission-id="{{ $pageData['view']->id }}"
                                   data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                <i class="fas fa-eye" style="color: {{ $role->permissions->contains($pageData['view']->id) || $isProtectedRole ? '#0dcaf0' : '#ccc' }};"></i>
                            </label>
                            @else
                            <span style="width: 22px;"></span>
                            @endif
                            
                            @if($pageData['edit'])
                            <label class="icon-permission {{ $isProtectedRole ? 'disabled' : '' }}" 
                                   title="Modifica"
                                   data-role-id="{{ $role->id }}"
                                   data-permission-id="{{ $pageData['edit']->id }}"
                                   data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                <i class="fas fa-edit" style="color: {{ $role->permissions->contains($pageData['edit']->id) || $isProtectedRole ? '#ffc107' : '#ccc' }};"></i>
                            </label>
                            @else
                            <span style="width: 22px;"></span>
                            @endif
                        </div>
                    </td>
                    @endforeach
                    
                    <td>
                        @if(!$isProtectedRole)
                            <form action="{{ route('admin.roles.destroy', $role) }}" 
                                  method="POST" 
                                  class="d-inline delete-role-form"
                                  data-role-name="{{ $role->display_name }}"
                                  data-users-count="{{ $role->users()->count() }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn delete delete-role-btn" title="Elimina ruolo">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @else
                            <span class="text-muted" title="Non eliminabile" style="opacity: 0.4;">
                                <i class="fas fa-lock"></i>
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- TAB 2: Visibilità Militari -->
<div id="visibilityCard" class="permissions-card hidden">
    <!-- Legenda -->
    <div class="visibility-legend">
        <div class="legend-item">
            <span class="legend-chip active"><i class="fas fa-eye"></i> Visibile</span>
            <span>= Il ruolo può vedere i militari di questa compagnia</span>
        </div>
        <div class="legend-item">
            <span class="legend-chip inactive"><i class="fas fa-eye-slash"></i> Nascosto</span>
            <span>= Il ruolo NON può vedere i militari di questa compagnia</span>
        </div>
    </div>
    
    <table class="visibility-table">
        <thead>
            <tr>
                <th style="width: 200px;"><i class="fas fa-user-tag me-2"></i>Ruolo</th>
                <th><i class="fas fa-building me-2"></i>Compagnie Visibili (clicca per attivare/disattivare)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
            @php
                $isProtectedRole = in_array($role->name, ['admin', 'amministratore']);
                $roleCompagnieIds = $role->compagnieVisibili->pluck('id')->toArray();
            @endphp
            <tr data-role-name="{{ strtolower($role->display_name) }}">
                <td>
                    <div class="d-flex flex-column">
                        <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                        @if($isProtectedRole)
                        <span class="role-badge protected mt-1">
                            <i class="fas fa-lock"></i> Tutte
                        </span>
                        @endif
                    </div>
                </td>
                <td>
                    <div class="company-chips" data-role-id="{{ $role->id }}" data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                        @foreach($compagnie as $compagnia)
                        @php
                            $isActive = in_array($compagnia->id, $roleCompagnieIds) || $isProtectedRole;
                        @endphp
                        <div class="company-chip {{ $isActive ? 'active' : 'inactive' }} {{ $isProtectedRole ? 'disabled' : '' }}"
                             data-compagnia-id="{{ $compagnia->id }}"
                             data-role-id="{{ $role->id }}">
                            <i class="chip-icon fas {{ $isActive ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                            {{ $compagnia->nome }}
                        </div>
                        @endforeach
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal conferma eliminazione gestito da SUGECO.Confirm -->

<!-- Dati per JavaScript -->
<script>
    window.permissionsData = {
        updatePermissionsUrl: "{{ url('/admin/permessi/ruoli') }}",
        updateVisibilityUrl: "{{ url('/admin/ruoli') }}",
        csrfToken: "{{ csrf_token() }}",
        rolePermissions: @json($roles->mapWithKeys(fn($r) => [$r->id => $r->permissions->pluck('id')->toArray()]))
    };
</script>
@endsection

@push('scripts')
<script>
let currentTab = 'permissions';

function switchTab(tab) {
    currentTab = tab;
    
    document.getElementById('permissionsCard').classList.toggle('hidden', tab !== 'permissions');
    document.getElementById('visibilityCard').classList.toggle('hidden', tab !== 'visibility');
    
    document.getElementById('permissionsBtn').classList.toggle('active', tab === 'permissions');
    document.getElementById('visibilityBtn').classList.toggle('active', tab === 'visibility');
    
    const slider = document.getElementById('toggleSlider');
    slider.classList.remove('pos-1', 'pos-2');
    slider.classList.add(tab === 'permissions' ? 'pos-1' : 'pos-2');
    
    document.getElementById('searchRole').value = '';
    filterRoles('');
}

function filterRoles(term) {
    const searchTerm = term.toLowerCase();
    document.querySelectorAll('[data-role-name]').forEach(row => {
        const visible = row.dataset.roleName.includes(searchTerm);
        row.style.display = visible ? '' : 'none';
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

async function updateRoleVisibility(roleId, compagniaIds) {
    const formData = new FormData();
    formData.append('_token', window.permissionsData.csrfToken);
    compagniaIds.forEach(id => formData.append('compagnie[]', id));
    
    try {
        const response = await fetch(`${window.permissionsData.updateVisibilityUrl}/${roleId}/compagnie`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
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
    
    // FIX RACE CONDITION: Flag per prevenire click multipli su compagnie
    let isSavingVisibility = false;
    
    // Company chips click handler
    document.querySelectorAll('.company-chip').forEach(chip => {
        chip.addEventListener('click', async function() {
            // Protezione anti-doppio click
            if (isSavingVisibility) {
                return;
            }
            
            const container = this.closest('.company-chips');
            
            if (container.dataset.protected === 'true') {
                showToast('Ruolo protetto - non modificabile', 'warning');
                return;
            }
            
            const roleId = container.dataset.roleId;
            const compagniaId = this.dataset.compagniaId;
            const wasActive = this.classList.contains('active');
            const iconEl = this.querySelector('.chip-icon');
            
            // Blocca ulteriori click
            isSavingVisibility = true;
            container.style.pointerEvents = 'none';
            container.style.opacity = '0.7';
            
            // Calcola nuove compagnie SENZA modificare l'UI
            const activeCompanies = [];
            container.querySelectorAll('.company-chip.active').forEach(c => {
                if (c !== this || !wasActive) {
                    activeCompanies.push(c.dataset.compagniaId);
                }
            });
            if (!wasActive) {
                activeCompanies.push(compagniaId);
            }
            
            showToast('Salvataggio...', 'info');
            
            const success = await updateRoleVisibility(roleId, activeCompanies);
            
            // Riabilita click
            isSavingVisibility = false;
            container.style.pointerEvents = '';
            container.style.opacity = '';
            
            if (success) {
                // Aggiorna UI solo dopo successo
                this.classList.toggle('active');
                this.classList.toggle('inactive');
                iconEl.classList.toggle('fa-eye');
                iconEl.classList.toggle('fa-eye-slash');
            }
            // Se fallisce, l'UI rimane invariata (stato originale)
        });
    });
    
    // Search
    document.getElementById('searchRole').addEventListener('input', function() {
        filterRoles(this.value);
    });
});
</script>
@endpush
