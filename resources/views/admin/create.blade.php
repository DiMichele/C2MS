@extends('layouts.app')

@section('title', 'Nuovo Utente')

@section('content')
<div class="container-fluid" style="position: relative; z-index: 1;">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">
                    CREA NUOVO UTENTE
                </h1>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm" style="border-radius: 0; border: none;">
                <div class="card-header" style="background: linear-gradient(135deg, #0a2342 0%, #1a3a5a 100%); color: white; border-radius: 0;">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Nuovo Utente
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" 
                                   placeholder="Mario Rossi" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                   id="username" name="username" value="{{ old('username') }}" 
                                   placeholder="mario.rossi" style="text-transform:lowercase" required>
                            <div class="form-text">Formato: nome.cognome (tutto minuscolo, senza spazi)</div>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            La password di default sarà: <strong>11Reggimento</strong>
                            <br><small>L'utente sarà invitato a cambiarla al primo accesso.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="role_id" class="form-label">Ruolo *</label>
                                <select class="form-select @error('role_id') is-invalid @enderror" 
                                        id="role_id" name="role_id" required>
                                    <option value="">Seleziona un ruolo...</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                                data-is-global="{{ $role->is_global ? 'true' : 'false' }}"
                                                {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                        <option value="{{ $compagnia->id }}" {{ old('compagnia_id') == $compagnia->id ? 'selected' : '' }}>
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
                                Crea Utente
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
// Forza minuscolo per username
document.getElementById('username').addEventListener('input', function() {
    this.value = this.value.toLowerCase();
});

// Gestione dinamica campo compagnia
document.getElementById('role_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const isGlobal = selectedOption.getAttribute('data-is-global') === 'true';
    const compagniaSelect = document.getElementById('compagnia_id');
    const compagniaWarning = document.getElementById('compagniaWarning');
    
    if (isGlobal) {
        // Ruolo globale: compagnia opzionale e mostra warning
        compagniaSelect.value = '';
        compagniaSelect.required = false;
        compagniaWarning.style.display = 'block';
    } else {
        // Ruolo normale: compagnia obbligatoria
        compagniaSelect.required = true;
        compagniaWarning.style.display = 'none';
    }
});

// Inizializza tooltips Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(t => new bootstrap.Tooltip(t));
    
    // Trigger change per impostare stato iniziale
    document.getElementById('role_id').dispatchEvent(new Event('change'));
});
</script>
@endsection
