@extends('layouts.app')

@section('title', 'Gestione Ruoli')

@section('styles')
<style>
/* Stili come anagrafica - identici alla pagina utenti */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
}

.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Colonna fissa (prima colonna) - RUOLO */
.table td.sticky-col,
.table th.sticky-col {
    position: sticky;
    left: 0;
    z-index: 5;
    border-right: 2px solid rgba(10, 35, 66, 0.25) !important;
}

.table thead th.sticky-col {
    z-index: 15;
    background-color: #0a2342 !important;
}

/* Assicura che l'header resti sempre sopra */
.table thead {
    z-index: 10;
}

.table thead th {
    background-color: #0a2342 !important;
}

/* Background corretto per colonna sticky */
.table tbody tr:nth-of-type(odd) td.sticky-col {
    background-color: #ffffff;
}

.table tbody tr:nth-of-type(even) td.sticky-col {
    background-color: #fafafa;
}

/* Checkbox styling */
.permission-form .form-check {
    margin: 0 !important;
    padding: 2px 0;
}

/* Tooltip styling per icone */
.icon-permission {
    cursor: pointer;
    font-size: 1.2rem;
    transition: transform 0.2s ease;
}

.icon-permission:hover {
    transform: scale(1.15);
}
</style>
@endsection

@section('content')
<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Ruoli</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input type="text" 
               id="searchRole" 
               class="form-control" 
               placeholder="Cerca ruolo..."
               style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Badge e azioni su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge bg-primary">{{ $roles->count() }} ruoli</span>
    </div>
    
    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary" style="border-radius: 6px !important;">
        <i class="fas fa-plus me-2"></i>
        Nuovo Ruolo
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@php
            // Raggruppa i permessi per pagina
            $pageGroups = [];
            foreach ($permissions as $perm) {
                // Escludi permessi admin.* dalla visualizzazione
                if (str_starts_with($perm->name, 'admin.')) {
                    continue;
                }
                
                $pageName = preg_replace('/\.(view|edit)$/', '', $perm->name);
                if (!isset($pageGroups[$pageName])) {
                    $pageGroups[$pageName] = [
                        'category' => $perm->category,
                        'display_name' => ucfirst(str_replace(['_', '.'], ' ', $pageName)),
                        'permissions' => []
                    ];
                }
                $pageGroups[$pageName]['permissions'][] = $perm;
            }
            
            // Ordina per categoria e nome
            uasort($pageGroups, function($a, $b) {
                if ($a['category'] === $b['category']) {
                    return strcmp($a['display_name'], $b['display_name']);
                }
                return strcmp($a['category'], $b['category']);
            });
        @endphp

        <div class="table-container" style="position: relative; overflow: auto;">
            <table class="table table-sm table-bordered mb-0">
                <thead style="background: #0a2342; position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th class="sticky-col" style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">
                            <i class="fas fa-user-tag me-2"></i>
                            RUOLO
                        </th>
                        @foreach($pageGroups as $pageName => $pageData)
                        <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">
                            {{ strtoupper($pageData['display_name']) }}
                        </th>
                        @endforeach
                        <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">
                            ELIMINA
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    @php
                        $isProtectedRole = ($role->name === 'amministratore');
                    @endphp
                    <tr>
                        <td class="sticky-col" style="border: 1px solid rgba(10, 35, 66, 0.2); background-color: {{ $isProtectedRole ? 'rgba(220, 53, 69, 0.05)' : 'white' }};">
                            <strong>{{ $role->display_name }}</strong>
                            @if($isProtectedRole)
                            <div class="mt-1">
                                <span class="badge bg-danger" style="font-size: 0.65rem;">
                                    <i class="fas fa-lock me-1"></i>PROTETTO
                                </span>
                            </div>
                            @endif
                        </td>
                        @foreach($pageGroups as $pageName => $pageData)
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2); text-align: center; background-color: {{ $isProtectedRole ? 'rgba(220, 53, 69, 0.03)' : 'white' }};">
                            <form action="{{ route('admin.roles.permissions.update', $role) }}" 
                                  method="POST" 
                                  class="permission-form"
                                  data-role-id="{{ $role->id }}"
                                  data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                                @csrf
                                
                                @php
                                    $viewPerm = collect($pageData['permissions'])->firstWhere('type', 'read');
                                    $editPerm = collect($pageData['permissions'])->firstWhere('type', 'write');
                                @endphp
                                
                                <div class="d-flex justify-content-center gap-3">
                                    @if($viewPerm)
                                    <div class="form-check mb-0">
                                        <input class="form-check-input permission-checkbox" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $viewPerm->id }}"
                                               id="perm-{{ $role->id }}-{{ $viewPerm->id }}"
                                               data-role-id="{{ $role->id }}"
                                               @if($role->permissions->contains($viewPerm->id)) checked @endif
                                               @if($isProtectedRole) disabled @endif
                                               style="display: none;">
                                        <label class="form-check-label icon-permission {{ $isProtectedRole ? 'protected-permission' : '' }}" 
                                               for="perm-{{ $role->id }}-{{ $viewPerm->id }}" 
                                               title="{{ $isProtectedRole ? 'Ruolo protetto - Tutti i permessi abilitati' : ($role->permissions->contains($viewPerm->id) ? 'Disabilita' : 'Abilita') . ' Lettura - ' . $pageData['display_name'] }}"
                                               data-enabled-text="Disabilita Lettura - {{ $pageData['display_name'] }}"
                                               data-disabled-text="Abilita Lettura - {{ $pageData['display_name'] }}"
                                               style="{{ $isProtectedRole ? 'cursor: not-allowed; opacity: 0.7;' : '' }}">
                                            <i class="fas fa-eye" style="color: {{ $role->permissions->contains($viewPerm->id) || $isProtectedRole ? '#0dcaf0' : '#ccc' }};"></i>
                                        </label>
                                    </div>
                                    @endif
                                    
                                    @if($editPerm)
                                    <div class="form-check mb-0">
                                        <input class="form-check-input permission-checkbox" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $editPerm->id }}"
                                               id="perm-{{ $role->id }}-{{ $editPerm->id }}"
                                               data-role-id="{{ $role->id }}"
                                               @if($role->permissions->contains($editPerm->id)) checked @endif
                                               @if($isProtectedRole) disabled @endif
                                               style="display: none;">
                                        <label class="form-check-label icon-permission {{ $isProtectedRole ? 'protected-permission' : '' }}" 
                                               for="perm-{{ $role->id }}-{{ $editPerm->id }}" 
                                               title="{{ $isProtectedRole ? 'Ruolo protetto - Tutti i permessi abilitati' : ($role->permissions->contains($editPerm->id) ? 'Disabilita' : 'Abilita') . ' Modifica - ' . $pageData['display_name'] }}"
                                               data-enabled-text="Disabilita Modifica - {{ $pageData['display_name'] }}"
                                               data-disabled-text="Abilita Modifica - {{ $pageData['display_name'] }}"
                                               style="{{ $isProtectedRole ? 'cursor: not-allowed; opacity: 0.7;' : '' }}">
                                            <i class="fas fa-edit" style="color: {{ $role->permissions->contains($editPerm->id) || $isProtectedRole ? '#ffc107' : '#ccc' }};"></i>
                                        </label>
                                    </div>
                                    @endif
                                </div>
                            </form>
                        </td>
                        @endforeach
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2); text-align: center;">
                            @php
                                // Solo il ruolo "amministratore" non può essere eliminato
                                $isSystemRole = ($role->name === 'amministratore');
                                $usersCount = $role->users()->count();
                            @endphp
                            
                            @if(!$isSystemRole)
                                <form action="{{ route('admin.roles.destroy', $role) }}" 
                                      method="POST" 
                                      class="d-inline delete-role-form"
                                      data-role-name="{{ $role->display_name }}"
                                      data-users-count="{{ $usersCount }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger delete-role-btn" 
                                            title="Elimina ruolo">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @else
                                <span class="text-muted" title="Ruolo di sistema non eliminabile">
                                    <i class="fas fa-lock"></i>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

<!-- Modale Conferma Eliminazione Ruolo -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="warningMessage" class="alert alert-warning d-none mb-3">
                    <i class="fas fa-users me-2"></i>
                    <strong>ATTENZIONE:</strong> <span id="usersCountText"></span>
                </div>
                <p class="mb-0">
                    Sei sicuro di voler eliminare definitivamente il ruolo <strong id="roleNameText"></strong>?
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annulla
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gestione modale eliminazione ruolo
document.addEventListener('DOMContentLoaded', function() {
    let formToSubmit = null;
    const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    
    // Gestione click pulsanti eliminazione
    document.querySelectorAll('.delete-role-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-role-form');
            const roleName = form.dataset.roleName;
            const usersCount = parseInt(form.dataset.usersCount);
            
            // Aggiorna contenuto modale
            document.getElementById('roleNameText').textContent = roleName;
            
            // Mostra/nascondi warning utenti
            const warningDiv = document.getElementById('warningMessage');
            if (usersCount > 0) {
                const userText = usersCount === 1 ? 'utente ha' : 'utenti hanno';
                document.getElementById('usersCountText').textContent = 
                    `Ci sono ${usersCount} ${userText} questo ruolo. Gli utenti rimarranno senza ruolo.`;
                warningDiv.classList.remove('d-none');
            } else {
                warningDiv.classList.add('d-none');
            }
            
            formToSubmit = form;
            modal.show();
        });
    });
    
    // Conferma eliminazione
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });
});

// Gestione salvataggio permessi
document.addEventListener('DOMContentLoaded', function() {
    // Blocca click su permessi protetti
    document.querySelectorAll('.protected-permission').forEach(label => {
        label.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Mostra alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="fas fa-lock me-2"></i>
                <strong>Ruolo Protetto:</strong> L'Amministratore ha automaticamente tutti i permessi e non può essere modificato.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => alertDiv.remove(), 5000);
            
            return false;
        });
    });
    
    // Raccogli tutti i form per ruolo
    const formsByRole = {};
    
    document.querySelectorAll('.permission-form').forEach(form => {
        const roleId = form.dataset.roleId;
        const isProtected = form.dataset.protected === 'true';
        
        if (!formsByRole[roleId]) {
            formsByRole[roleId] = {
                forms: [],
                pendingSubmit: false,
                isProtected: isProtected
            };
        }
        formsByRole[roleId].forms.push(form);
    });
    
    // Gestisci click sui checkbox
    document.querySelectorAll('.permission-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Aggiorna colore icona e tooltip
            const label = this.nextElementSibling;
            const icon = label.querySelector('i');
            if (this.checked) {
                if (icon.classList.contains('fa-eye')) {
                    icon.style.color = '#0dcaf0';
                } else {
                    icon.style.color = '#ffc107';
                }
                label.setAttribute('title', label.getAttribute('data-enabled-text'));
            } else {
                icon.style.color = '#ccc';
                label.setAttribute('title', label.getAttribute('data-disabled-text'));
            }
            
            const roleId = this.dataset.roleId;
            const roleData = formsByRole[roleId];
            
            // BLOCCA il submit per i ruoli protetti
            if (roleData && roleData.isProtected) {
                console.log('Tentativo di modifica ruolo protetto bloccato');
                return;
            }
            
            if (roleData.pendingSubmit) {
                return; // Evita submit multipli
            }
            
            roleData.pendingSubmit = true;
            
            // Mostra loading
            showLoading('Salvataggio in corso...');
            
            // Raccogli tutti i permessi selezionati per questo ruolo
            const selectedPermissions = [];
            roleData.forms.forEach(f => {
                f.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
                    selectedPermissions.push(cb.value);
                });
            });
            
            // Prendi il primo form per il submit
            const firstForm = roleData.forms[0];
            const formData = new FormData();
            formData.append('_token', firstForm.querySelector('[name="_token"]').value);
            selectedPermissions.forEach(permId => {
                formData.append('permissions[]', permId);
            });
            
            // Submit via AJAX
            fetch(firstForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                hideLoading();
                showSuccess('Permessi aggiornati!');
                roleData.pendingSubmit = false;
            })
            .catch(error => {
                hideLoading();
                showError('Errore durante il salvataggio');
                roleData.pendingSubmit = false;
            });
        });
    });
});

function showLoading(message) {
    const existing = document.getElementById('loading-toast');
    if (existing) existing.remove();
    
    const toast = document.createElement('div');
    toast.id = 'loading-toast';
    toast.className = 'alert alert-info position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg';
    toast.style.zIndex = '9999';
    toast.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${message}`;
    document.body.appendChild(toast);
}

function hideLoading() {
    const toast = document.getElementById('loading-toast');
    if (toast) toast.remove();
}

function showSuccess(message) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-success position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg';
    toast.style.zIndex = '9999';
    toast.innerHTML = `<i class="fas fa-check-circle me-2"></i>${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 2000);
}

function showError(message) {
    const toast = document.createElement('div');
    toast.className = 'alert alert-danger position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg';
    toast.style.zIndex = '9999';
    toast.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.5s';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// Auto-hide success messages dopo 5 secondi
setTimeout(() => {
    document.querySelectorAll('.alert-success').forEach(alert => {
        if (alert.id !== 'loading-toast') {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    });
}, 5000);

// Funzionalità di ricerca ruolo
document.getElementById('searchRole').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        const roleNameCell = row.querySelector('.sticky-col strong');
        if (roleNameCell) {
            const roleName = roleNameCell.textContent.toLowerCase();
            
            if (roleName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>
@endpush

