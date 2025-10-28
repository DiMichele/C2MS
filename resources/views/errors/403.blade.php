@extends('layouts.app')

@section('title', 'Accesso Negato')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-shield-alt text-warning" style="font-size: 80px;"></i>
                    </div>
                    <h1 class="display-4 text-warning mb-3">Accesso Negato</h1>
                    <p class="lead text-muted mb-4">
                        {{ $exception->getMessage() ?? 'Non hai i permessi necessari per accedere a questa risorsa.' }}
                    </p>
                    
                    <div class="alert alert-light border" role="alert">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        <strong>Suggerimento:</strong> Se ritieni di dover avere accesso a questa funzionalità,
                        contatta l'amministratore di sistema per richiedere i permessi necessari.
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary me-2">
                            <i class="fas fa-home me-2"></i>Torna alla Dashboard
                        </a>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Torna Indietro
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    border-radius: 15px;
}

.btn {
    border-radius: 8px;
    padding: 10px 25px;
    font-weight: 500;
}

.alert {
    border-radius: 10px;
}
</style>
@endsection

