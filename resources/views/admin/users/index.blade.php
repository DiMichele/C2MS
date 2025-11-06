@extends('layouts.app')

@section('title', 'Gestione Utenti')

@section('styles')
<style>
/* Stili come anagrafica */
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

.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
}

.link-name:hover {
    color: #0a2342;
    text-decoration: none;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}
</style>
@endsection

@section('content')
<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Utenti</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input type="text" 
               id="searchUser" 
               class="form-control" 
               placeholder="Cerca utente..."
               style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Badge e azioni su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge bg-primary">{{ $users->count() }} utenti</span>
    </div>
    
    <a href="{{ route('admin.create') }}" class="btn btn-primary" style="border-radius: 6px !important;">
        <i class="fas fa-plus me-2"></i>
        Nuovo Utente
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

<div class="table-container" style="position: relative; overflow: auto;">
    <table class="table table-sm table-bordered mb-0">
        <thead style="background: #0a2342; position: sticky; top: 0; z-index: 10;">
            <tr>
                <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">NOME</th>
                <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">USERNAME</th>
                <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">RUOLO</th>
                <th style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">CREATO</th>
                <th width="220" style="border: 1px solid rgba(10, 35, 66, 0.2); font-weight: 600; padding: 12px 8px; color: white;">AZIONI</th>
            </tr>
        </thead>
        <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2);">
                            <strong>{{ $user->name }}</strong>
                            @if($user->must_change_password)
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-exclamation-triangle"></i> Cambio password
                                </span>
                            @endif
                        </td>
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2);"><code>{{ $user->username ?? 'N/A' }}</code></td>
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2);">
                            @foreach($user->roles as $role)
                                {{ $role->display_name }}@if(!$loop->last), @endif
                            @endforeach
                        </td>
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2);">{{ $user->created_at->format('d/m/Y') }}</td>
                        <td style="border: 1px solid rgba(10, 35, 66, 0.2);">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.edit', $user) }}" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Modifica utente">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('admin.reset-password', $user) }}" 
                                      method="POST" 
                                      class="d-inline reset-password-form"
                                      data-user-name="{{ $user->name }}">
                                    @csrf
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-warning reset-password-btn" 
                                            title="Reset password a 11Reggimento">
                                        <i class="fas fa-key"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.destroy', $user) }}" 
                                      method="POST" 
                                      class="d-inline delete-user-form"
                                      data-user-name="{{ $user->name }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger delete-user-btn" 
                                            title="Elimina utente">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3 d-block"></i>
                            Nessun utente presente
                        </td>
                    </tr>
                    @endforelse
        </tbody>
    </table>
</div>

<!-- Modale Conferma Reset Password -->
<div class="modal fade" id="confirmResetModal" tabindex="-1" aria-labelledby="confirmResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="confirmResetModalLabel">
                    <i class="fas fa-key me-2"></i>
                    Conferma Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Sei sicuro di voler resettare la password di <strong id="resetUserNameText"></strong> a <code>11Reggimento</code>?
                </p>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    L'utente dovrà cambiare la password al prossimo accesso.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annulla
                </button>
                <button type="button" class="btn btn-warning" id="confirmResetBtn">
                    <i class="fas fa-key me-2"></i>Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modale Conferma Eliminazione Utente -->
<div class="modal fade" id="confirmDeleteUserModal" tabindex="-1" aria-labelledby="confirmDeleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    Sei sicuro di voler eliminare definitivamente l'utente <strong id="deleteUserNameText"></strong>?
                </p>
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Attenzione:</strong> Questa azione è irreversibile!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Annulla
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn">
                    <i class="fas fa-trash me-2"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gestione modali conferma
document.addEventListener('DOMContentLoaded', function() {
    let formToSubmit = null;
    
    // Modale Reset Password
    const resetModal = new bootstrap.Modal(document.getElementById('confirmResetModal'));
    document.querySelectorAll('.reset-password-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.reset-password-form');
            const userName = form.dataset.userName;
            
            document.getElementById('resetUserNameText').textContent = userName;
            formToSubmit = form;
            resetModal.show();
        });
    });
    
    document.getElementById('confirmResetBtn').addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });
    
    // Modale Eliminazione Utente
    const deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteUserModal'));
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-user-form');
            const userName = form.dataset.userName;
            
            document.getElementById('deleteUserNameText').textContent = userName;
            formToSubmit = form;
            deleteModal.show();
        });
    });
    
    document.getElementById('confirmDeleteUserBtn').addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });
});

// Auto-hide success messages dopo 5 secondi
setTimeout(() => {
    document.querySelectorAll('.alert-success').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// Funzionalità di ricerca utente
document.getElementById('searchUser').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        // Prendi nome e username
        const nameCell = row.querySelector('td:nth-child(1)');
        const usernameCell = row.querySelector('td:nth-child(2)');
        
        if (nameCell && usernameCell) {
            const name = nameCell.textContent.toLowerCase();
            const username = usernameCell.textContent.toLowerCase();
            
            if (name.includes(searchTerm) || username.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>
@endpush

