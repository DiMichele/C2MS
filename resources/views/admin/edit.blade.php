@extends('layouts.app')

@section('title', 'Modifica Utente')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Modifica Utente: {{ $user->name }}
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" 
                                   value="{{ old('username', $user->username) }}" 
                                   placeholder="mario.rossi" style="text-transform:lowercase" required>
                            <div class="form-text">Formato: nome.cognome (tutto minuscolo, senza spazi)</div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role_id" class="form-label">Ruolo *</label>
                                <select class="form-select @error('role_id') is-invalid @enderror" 
                                        id="role_id" name="role_id" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                            data-is-global="{{ $role->is_global ? 'true' : 'false' }}"
                                            {{ (old('role_id', $user->roles->first()?->id) == $role->id) ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                            @if($role->is_global)
                                                (Globale)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3" id="compagniaContainer">
                                <label for="compagnia_id" class="form-label">
                                    Compagnia di appartenenza *
                                    <i class="fas fa-info-circle text-muted" 
                                       data-bs-toggle="tooltip" 
                                       title="L'utente vedrà SOLO i dati della compagnia selezionata"></i>
                                </label>
                                <select class="form-select @error('compagnia_id') is-invalid @enderror" 
                                        id="compagnia_id" name="compagnia_id">
                                    <option value="">-- Tutte le compagnie (Admin) --</option>
                                    @foreach($compagnie as $compagnia)
                                        <option value="{{ $compagnia->id }}" 
                                            {{ old('compagnia_id', $user->compagnia_id) == $compagnia->id ? 'selected' : '' }}>
                                            {{ $compagnia->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text text-warning" id="compagniaWarning" style="display: none;">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Ruolo globale: l'utente avrà accesso a TUTTE le compagnie
                                </div>
                                @error('compagnia_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Salva Modifiche
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annulla
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Gestione dinamica campo compagnia
document.getElementById('role_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const isGlobal = selectedOption.getAttribute('data-is-global') === 'true';
    const compagniaSelect = document.getElementById('compagnia_id');
    const compagniaWarning = document.getElementById('compagniaWarning');
    
    if (isGlobal) {
        compagniaSelect.required = false;
        compagniaWarning.style.display = 'block';
    } else {
        compagniaSelect.required = true;
        compagniaWarning.style.display = 'none';
    }
});

// Inizializza tooltips e stato iniziale
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(t => new bootstrap.Tooltip(t));
    
    document.getElementById('role_id').dispatchEvent(new Event('change'));
});
</script>
@endsection

