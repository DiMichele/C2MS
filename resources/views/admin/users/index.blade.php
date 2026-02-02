@extends('layouts.app')

@section('title', 'Gestione Utenti')

@section('styles')
<style>
/* Stili specifici per questa pagina */
/* (Stili base tabelle in table-standard.css) */

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
    <table class="sugeco-table">
        <thead>
            <tr>
                <th>NOME</th>
                <th>USERNAME</th>
                <th>RUOLO</th>
                <th><i class="fas fa-sitemap me-1"></i>UNITÀ</th>
                <th>COMPAGNIA</th>
                <th>CREATO</th>
                <th>AZIONI</th>
            </tr>
        </thead>
        <tbody>
                    @forelse($users as $user)
                    <tr>
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
                        <td>
                            @if($user->compagnia)
                                <span class="badge bg-info">{{ $user->compagnia->nome }}</span>
                            @else
                                <span class="badge bg-success">
                                    <i class="fas fa-globe me-1"></i>Tutte
                                </span>
                            @endif
                        </td>
                        <td>{{ $user->created_at->format('d/m/Y') }}</td>
                        <td>
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-3x mb-3 d-block"></i>
                            Nessun utente presente
                        </td>
                    </tr>
                    @endforelse
        </tbody>
    </table>
</div>

<!-- Modal conferma reset e eliminazione gestiti da SUGECO.Confirm -->
@endsection

@push('scripts')
<script>
// Gestione conferme con sistema unificato SUGECO.Confirm
document.addEventListener('DOMContentLoaded', function() {
    // Reset Password
    document.querySelectorAll('.reset-password-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('.reset-password-form');
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
    
    // Eliminazione Utente
    document.querySelectorAll('.delete-user-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            const form = this.closest('.delete-user-form');
            const userName = form.dataset.userName;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare definitivamente l'utente ${userName}? Questa azione è irreversibile!`);
            
            if (confirmed) {
                form.submit();
            }
        });
    });
});

// Auto-hide success messages dopo 5 secondi
setTimeout(() => {
    document.querySelectorAll('.alert-success').forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);

// FIX: Funzionalità di ricerca utente estesa a tutti i campi visibili
document.getElementById('searchUser').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('table tbody tr');
    
    rows.forEach(row => {
        // Prendi nome, username, ruolo e compagnia
        const nameCell = row.querySelector('td:nth-child(1)');
        const usernameCell = row.querySelector('td:nth-child(2)');
        const roleCell = row.querySelector('td:nth-child(3)');
        const compagniaCell = row.querySelector('td:nth-child(4)');
        
        if (nameCell && usernameCell) {
            const name = nameCell.textContent.toLowerCase();
            const username = usernameCell.textContent.toLowerCase();
            const role = roleCell ? roleCell.textContent.toLowerCase() : '';
            const compagnia = compagniaCell ? compagniaCell.textContent.toLowerCase() : '';
            
            // Cerca in tutti i campi
            if (name.includes(searchTerm) || 
                username.includes(searchTerm) || 
                role.includes(searchTerm) || 
                compagnia.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>
@endpush

