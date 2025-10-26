@extends('layouts.app')

@section('title', 'Il Mio Profilo')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if($user->must_change_password)
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Attenzione!</strong> Devi cambiare la password prima di poter utilizzare il sistema.
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        Il Mio Profilo
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Nome:</strong></div>
                        <div class="col-sm-9">{{ $user->name }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Codice Fiscale:</strong></div>
                        <div class="col-sm-9"><code>{{ $user->codice_fiscale ?? 'N/A' }}</code></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Email:</strong></div>
                        <div class="col-sm-9">{{ $user->email ?? 'Non impostata' }}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3"><strong>Ruolo:</strong></div>
                        <div class="col-sm-9">
                            @foreach($user->roles as $role)
                                <span class="badge 
                                    @if($role->name === 'admin') bg-danger
                                    @elseif($role->name === 'comandante') bg-success
                                    @elseif($role->name === 'furiere') bg-primary
                                    @elseif($role->name === 'rssp') bg-warning text-dark
                                    @else bg-secondary
                                    @endif
                                ">
                                    {{ $role->display_name }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Ultimo cambio password:</strong></div>
                        <div class="col-sm-9">
                            {{ $user->last_password_change ? $user->last_password_change->format('d/m/Y H:i') : 'Mai' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        Cambia Password
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.change-password') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Attuale *</label>
                            <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                   id="current_password" name="current_password" required>
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nuova Password *</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            <div class="form-text">Minimo 8 caratteri</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Conferma Nuova Password *</label>
                            <input type="password" class="form-control" 
                                   id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Cambia Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

