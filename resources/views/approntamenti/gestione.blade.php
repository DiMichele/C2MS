@extends('layouts.app')

@section('title', 'Gestione Approntamenti - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Approntamenti</h1>
        <p class="text-muted">Configurazione delle colonne e scadenze per la pagina Approntamenti</p>
    </div>

    <!-- Info Card -->
    <div class="alert alert-info mb-4" style="max-width: 900px; margin: 0 auto;">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Nota:</strong> Le colonne degli approntamenti sono predefinite. Puoi visualizzare le colonne disponibili e le relative scadenze (se configurate). Per modificare i valori, vai alla pagina <a href="{{ route('approntamenti.index') }}">Approntamenti</a> e clicca sulla cella desiderata.
    </div>

    <!-- Tabella Colonne -->
    <div class="table-container-ruolini" style="max-width: 900px; margin: 0 auto;">
        <table class="sugeco-table" id="colonneTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome Colonna</th>
                    <th>Campo DB</th>
                    <th>Scadenza</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody id="colonneTableBody">
                @php
                    $durate = [
                        'rspp_4h' => 60,       
                        'rspp_8h' => 60,       
                        'rspp_preposti' => 24, 
                        'bls' => 24,           
                    ];
                    $index = 1;
                @endphp
                @foreach($colonne as $campo => $label)
                <tr>
                    <td class="text-center text-muted">{{ $index++ }}</td>
                    <td><strong>{{ $label }}</strong></td>
                    <td><code>{{ $campo }}</code></td>
                    <td class="text-center">
                        @if(isset($durate[$campo]))
                            <span class="badge bg-primary">
                                {{ $durate[$campo] }} mesi ({{ $durate[$campo] / 12 }} anni)
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success">Attivo</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Legenda -->
    <div class="mt-4" style="max-width: 900px; margin: 0 auto;">
        <div class="card border-0 shadow-sm">
            <div class="card-header" style="background: #0a2342; color: white;">
                <i class="fas fa-palette me-2"></i>Legenda Colori
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="color-sample me-2" style="background-color: #d4edda; width: 30px; height: 20px; border-radius: 4px;"></span>
                            <span>Valido</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="color-sample me-2" style="background-color: #fff3cd; width: 30px; height: 20px; border-radius: 4px;"></span>
                            <span>In scadenza (30gg)</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="color-sample me-2" style="background-color: #ffcccc; width: 30px; height: 20px; border-radius: 4px;"></span>
                            <span>Scaduto</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="color-sample me-2" style="background-color: #e2e3e5; width: 30px; height: 20px; border-radius: 4px;"></span>
                            <span>Non richiesto</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="mt-4" style="max-width: 900px; margin: 0 auto;">
        <div class="row">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <h3 class="text-primary mb-1">{{ count($colonne) }}</h3>
                        <small class="text-muted">Colonne Totali</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <h3 class="text-success mb-1">{{ count($durate) }}</h3>
                        <small class="text-muted">Con Scadenza</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <h3 class="text-secondary mb-1">{{ count($colonne) - count($durate) }}</h3>
                        <small class="text-muted">Solo Data</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pulsante Vai agli Approntamenti -->
    <div class="text-center mt-4">
        <a href="{{ route('approntamenti.index') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-arrow-right me-2"></i>Vai agli Approntamenti
        </a>
    </div>
</div>

{{-- 
.table-container-ruolini {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin: 0;
}

code {
    background-color: #f1f3f5;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85rem;
    color: #495057;
}
--}}
@endsection
