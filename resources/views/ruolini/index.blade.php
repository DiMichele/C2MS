@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
        <h1 class="h3 mb-0 text-dark fw-bold">Ruolini</h1>
        
        <!-- Export Button -->
        <button onclick="exportRuoliniExcel()" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Esporta Excel
        </button>
    </div>

    <!-- Controlli Data e Filtri -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row align-items-end g-3">
                <!-- Navigazione Data -->
                <div class="col-auto">
                    <label class="form-label small text-muted mb-1">Data</label>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <input type="date" 
                               id="dataSelect" 
                               class="form-control form-control-sm" 
                               value="{{ $dataSelezionata }}" 
                               style="width: 150px;"
                               onchange="cambiaData()">
                        
                        <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        
                        @if(!$dataObj->isToday())
                            <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                               class="btn btn-primary btn-sm">
                                Oggi
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Separatore -->
                <div class="col-auto d-none d-md-block">
                    <div style="width: 1px; height: 40px; background: #dee2e6;"></div>
                </div>

                <!-- Filtro Compagnia -->
                <div class="col-auto">
                    <label for="compagniaSelect" class="form-label small text-muted mb-1">Compagnia</label>
                    <select id="compagniaSelect" class="form-select form-select-sm" style="width: 180px;" onchange="applicaFiltri()">
                        <option value="">Tutte</option>
                        @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                                {{ $compagnia->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filtro Plotone -->
                <div class="col-auto">
                    <label for="plotoneSelect" class="form-label small text-muted mb-1">Plotone</label>
                    <select id="plotoneSelect" class="form-select form-select-sm" style="width: 180px;" onchange="applicaFiltri()">
                        <option value="">Tutti</option>
                        @foreach($plotoni as $plotone)
                            <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                                {{ $plotone->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset -->
                @if($compagniaId || $plotoneId)
                    <div class="col-auto">
                        <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Reset
                        </a>
                    </div>
                @endif

                <!-- Data formattata -->
                <div class="col-auto ms-auto">
                    <span class="text-muted">
                        {{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    @php
        $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
        $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
        $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
    @endphp

    <!-- Riepilogo Totale -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center py-4">
                    <div class="text-muted small text-uppercase mb-1">Forza Effettiva</div>
                    <div class="display-5 fw-bold text-dark">{{ $forzaEffettiva }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-start border-success border-4">
                <div class="card-body text-center py-4">
                    <div class="text-muted small text-uppercase mb-1">Presenti</div>
                    <div class="display-5 fw-bold text-success">{{ $totalePresenti }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-start border-danger border-4">
                <div class="card-body text-center py-4">
                    <div class="text-muted small text-uppercase mb-1">Assenti</div>
                    <div class="display-5 fw-bold text-danger">{{ $totaleAssenti }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riepilogo per Categoria -->
    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-semibold">Riepilogo per Categoria</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Categoria</th>
                            <th class="text-center" style="width: 120px;">Totale</th>
                            <th class="text-center" style="width: 120px;">Presenti</th>
                            <th class="text-center" style="width: 120px;">Assenti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $categoria)
                            @if($totali[$categoria]['totale'] > 0)
                            <tr>
                                <td class="ps-3 fw-medium">{{ $categoria }}</td>
                                <td class="text-center">{{ $totali[$categoria]['totale'] }}</td>
                                <td class="text-center text-success fw-medium">{{ $totali[$categoria]['presenti'] }}</td>
                                <td class="text-center text-danger fw-medium">{{ $totali[$categoria]['assenti'] }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dettaglio per Categoria -->
    @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $categoria)
        @if($totali[$categoria]['totale'] > 0)
            <div class="card mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold">{{ $categoria }}</h6>
                    <div class="d-flex gap-3">
                        <span class="text-success small">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            Presenti: {{ $totali[$categoria]['presenti'] }}
                        </span>
                        <span class="text-danger small">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            Assenti: {{ $totali[$categoria]['assenti'] }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Colonna Presenti -->
                        <div class="col-md-6 border-end">
                            <div class="p-2 bg-success bg-opacity-10 border-bottom">
                                <small class="fw-semibold text-success">PRESENTI</small>
                            </div>
                            @if(count($categorie[$categoria]['presenti']) > 0)
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th style="width: 80px;">Grado</th>
                                                <th>Cognome</th>
                                                <th>Nome</th>
                                                <th style="width: 100px;">Plotone</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categorie[$categoria]['presenti'] as $index => $item)
                                                <tr>
                                                    <td class="text-muted">{{ $index + 1 }}</td>
                                                    <td><small class="fw-medium">{{ $item['militare']->grado->sigla ?? '' }}</small></td>
                                                    <td>
                                                        <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="text-decoration-none text-dark fw-medium">
                                                            {{ $item['militare']->cognome }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $item['militare']->nome }}</td>
                                                    <td><small class="text-muted">{{ $item['militare']->plotone->nome ?? '-' }}</small></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-4 text-center text-muted">
                                    <small>Nessun presente</small>
                                </div>
                            @endif
                        </div>

                        <!-- Colonna Assenti -->
                        <div class="col-md-6">
                            <div class="p-2 bg-danger bg-opacity-10 border-bottom">
                                <small class="fw-semibold text-danger">ASSENTI</small>
                            </div>
                            @if(count($categorie[$categoria]['assenti']) > 0)
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th style="width: 40px;">#</th>
                                                <th style="width: 80px;">Grado</th>
                                                <th>Cognome</th>
                                                <th>Nome</th>
                                                <th style="width: 150px;">Motivazione</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categorie[$categoria]['assenti'] as $index => $item)
                                                <tr>
                                                    <td class="text-muted">{{ $index + 1 }}</td>
                                                    <td><small class="fw-medium">{{ $item['militare']->grado->sigla ?? '' }}</small></td>
                                                    <td>
                                                        <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="text-decoration-none text-dark fw-medium">
                                                            {{ $item['militare']->cognome }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $item['militare']->nome }}</td>
                                                    <td>
                                                        @foreach($item['impegni'] as $impegno)
                                                            <span class="badge" 
                                                                  style="background-color: {{ $impegno['colore'] }}; font-size: 0.7rem;"
                                                                  title="{{ $impegno['descrizione'] }}">
                                                                {{ $impegno['codice'] }}
                                                            </span>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-4 text-center text-muted">
                                    <small>Nessun assente</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

<style>
.card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    border-bottom: 1px solid #e5e7eb;
}

.table th {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    border-bottom-width: 1px;
}

.table td {
    font-size: 0.875rem;
    vertical-align: middle;
    border-bottom: 1px solid #f3f4f6;
}

.table-hover tbody tr:hover {
    background-color: #f9fafb;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1;
}

.display-5 {
    font-size: 2.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.15);
}

.btn-success {
    background-color: #198754;
    border-color: #198754;
}

.btn-success:hover {
    background-color: #157347;
    border-color: #146c43;
}

/* Scrollbar personalizzata */
.table-responsive::-webkit-scrollbar {
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }
    
    .col-md-6.border-end {
        border-right: none !important;
        border-bottom: 1px solid #e5e7eb;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});

function cambiaData() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = document.getElementById('compagniaSelect').value;
    const plotone = document.getElementById('plotoneSelect').value;
    
    let url = '{{ route("ruolini.index") }}';
    const params = new URLSearchParams();
    
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    
    const queryString = params.toString();
    if (queryString) url += '?' + queryString;
    
    window.location.href = url;
}

function applicaFiltri() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = document.getElementById('compagniaSelect').value;
    const plotone = document.getElementById('plotoneSelect').value;
    
    let url = '{{ route("ruolini.index") }}';
    const params = new URLSearchParams();
    
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    
    const queryString = params.toString();
    if (queryString) url += '?' + queryString;
    
    window.location.href = url;
}

function exportRuoliniExcel() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = document.getElementById('compagniaSelect').value;
    const plotone = document.getElementById('plotoneSelect').value;
    
    const params = new URLSearchParams();
    
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    
    const queryString = params.toString();
    const exportUrl = '{{ route("ruolini.export-excel") }}' + (queryString ? '?' + queryString : '');
    
    window.location.href = exportUrl;
}
</script>
@endsection
