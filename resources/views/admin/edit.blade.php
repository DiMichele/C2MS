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

                        <div class="mb-3">
                            <label for="role_id" class="form-label">Ruolo *</label>
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" name="role_id" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" 
                                        {{ (old('role_id', $user->roles->first()?->id) == $role->id) ? 'selected' : '' }}>
                                        {{ $role->display_name }} - {{ $role->description }}
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
@endsection

