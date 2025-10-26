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

                        <div class="mb-3">
                            <label for="role_id" class="form-label">Ruolo *</label>
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" name="role_id" required>
                                <option value="">Seleziona un ruolo...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
</script>
@endsection
