@extends('layouts.app')

@section('title', 'Manutenzione')

@section('content')
<div class="error-page">
    <div class="error-content">
        <div class="error-code">503</div>
        <div class="error-divider"></div>
        <h1 class="error-title">Manutenzione in Corso</h1>
        <p class="error-message">
            Servizio temporaneamente non disponibile.<br>
            Per assistenza, contattare l'amministratore di sistema.
        </p>
    </div>
</div>

<style>
.error-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 180px);
    padding: 40px 20px;
}

.error-content {
    text-align: center;
    max-width: 400px;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.error-code {
    font-size: 100px;
    font-weight: 700;
    color: #64748b;
    line-height: 1;
    letter-spacing: -3px;
    opacity: 0.85;
}

.error-divider {
    width: 50px;
    height: 3px;
    background: #64748b;
    margin: 16px auto 20px;
    border-radius: 2px;
    opacity: 0.6;
}

.error-title {
    font-size: 18px;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 10px;
}

.error-message {
    font-size: 14px;
    color: #718096;
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 480px) {
    .error-code {
        font-size: 72px;
    }
}
</style>
@endsection
