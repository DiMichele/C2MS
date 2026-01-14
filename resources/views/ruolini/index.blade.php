@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid ruolini-page">
    <!-- Header -->
    <div class="ruolini-hero card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h1 class="ruolini-title mb-1">Ruolini</h1>
                <p class="text-muted mb-0">{{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}</p>
            </div>
            <div class="ruolini-hero-note text-muted">
                Report presenze giornaliere
            </div>
        </div>
    </div>

    <!-- Controlli -->
    <div class="card ruolini-toolbar mb-4">
        <div class="card-body">
            <div class="ruolini-toolbar-grid">
                <div class="ruolini-toolbar-group">
                    <div class="ruolini-toolbar-label">Data</div>
                    <div class="ruolini-date-controls">
                        <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>

                        <input type="date" id="dataSelect" class="form-control form-control-sm"
                               value="{{ $dataSelezionata }}" onchange="cambiaData()">

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

                <div class="ruolini-toolbar-group">
                    <div class="ruolini-toolbar-label">Filtri</div>
                    <div class="ruolini-filter-controls">
                        <select id="compagniaSelect" class="form-select form-select-sm" onchange="applicaFiltri()">
                            <option value="">Tutte le compagnie</option>
                            @foreach($compagnie as $compagnia)
                                <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                                    {{ $compagnia->nome }}
                                </option>
                            @endforeach
                        </select>

                        <select id="plotoneSelect" class="form-select form-select-sm" onchange="applicaFiltri()">
                            <option value="">Tutti i plotoni</option>
                            @foreach($plotoni as $plotone)
                                <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                                    {{ $plotone->nome }}
                                </option>
                            @endforeach
                        </select>

                        @if($compagniaId || $plotoneId)
                            <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        @endif
                    </div>
                </div>

                <div class="ruolini-toolbar-group ruolini-toolbar-actions">
                    <button onclick="exportRuoliniExcel()" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-2"></i>Esporta Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
        $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
        $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
        $percentualePresenti = $forzaEffettiva > 0 ? round(($totalePresenti / $forzaEffettiva) * 100) : 0;
    @endphp

    <!-- Statistiche principali -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card ruolini-kpi-card h-100">
                        <div class="card-body">
                            <div class="ruolini-kpi-label">Forza effettiva</div>
                            <div class="ruolini-kpi-value text-navy">{{ $forzaEffettiva }}</div>
                            <div class="ruolini-kpi-sub">Totale personale in forza</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="card ruolini-kpi-card ruolini-kpi-success h-100">
                        <div class="card-body">
                            <div class="ruolini-kpi-label">Presenti</div>
                            <div class="ruolini-kpi-value text-success">{{ $totalePresenti }}</div>
                            <div class="ruolini-kpi-sub">{{ $percentualePresenti }}% del totale</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card ruolini-kpi-card ruolini-kpi-danger h-100">
                        <div class="card-body">
                            <div class="ruolini-kpi-label">Assenti</div>
                            <div class="ruolini-kpi-value text-danger">{{ $totaleAssenti }}</div>
                            <div class="ruolini-kpi-sub">{{ 100 - $percentualePresenti }}% del totale</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown categorie -->
        <div class="col-lg-4">
            <div class="card h-100 ruolini-summary-card">
                <div class="card-header">
                    <h6 class="mb-0">Riepilogo per categoria</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0 ruolini-summary-table">
                        <thead>
                            <tr>
                                <th class="ps-3">Categoria</th>
                                <th class="text-center">Tot</th>
                                <th class="text-center">Pres</th>
                                <th class="text-center">Ass</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $cat)
                                @if($totali[$cat]['totale'] > 0)
                                <tr>
                                    <td class="ps-3">{{ $cat }}</td>
                                    <td class="text-center fw-bold">{{ $totali[$cat]['totale'] }}</td>
                                    <td class="text-center fw-bold text-success">{{ $totali[$cat]['presenti'] }}</td>
                                    <td class="text-center fw-bold text-danger">{{ $totali[$cat]['assenti'] }}</td>
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dettaglio per categoria -->
    @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $categoria)
        @if($totali[$categoria]['totale'] > 0)
        <div class="card mb-4 ruolini-category-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">{{ $categoria }}</h5>
                    <small class="text-muted">Totale: {{ $totali[$categoria]['totale'] }}</small>
                </div>
                <div class="ruolini-category-badges">
                    <span class="badge badge-success">
                        {{ $totali[$categoria]['presenti'] }} presenti
                    </span>
                    <span class="badge badge-danger">
                        {{ $totali[$categoria]['assenti'] }} assenti
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Colonna Presenti -->
                    <div class="col-md-6 ruolini-column-divider">
                        <div class="ruolini-column-header ruolini-column-header-success">
                            <small class="fw-bold text-success">Presenti</small>
                        </div>
                        @if(count($categorie[$categoria]['presenti']) > 0)
                        <div class="table-responsive ruolini-table-scroll">
                            <table class="table table-sm table-hover mb-0 ruolini-table">
                                <thead>
                                    <tr>
                                        <th class="text-center ruolini-col-num">#</th>
                                        <th class="ruolini-col-grade">Grado</th>
                                        <th>Militare</th>
                                        <th class="ruolini-col-plotone">Plotone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categorie[$categoria]['presenti'] as $i => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                        <td>
                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                               class="ruolini-link">
                                                {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                            </a>
                                        </td>
                                        <td class="text-muted">{{ $item['militare']->plotone->nome ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="ruolini-empty">Nessun presente</div>
                        @endif
                    </div>
                    
                    <!-- Colonna Assenti -->
                    <div class="col-md-6">
                        <div class="ruolini-column-header ruolini-column-header-danger">
                            <small class="fw-bold text-danger">Assenti</small>
                        </div>
                        @if(count($categorie[$categoria]['assenti']) > 0)
                        <div class="table-responsive ruolini-table-scroll">
                            <table class="table table-sm table-hover mb-0 ruolini-table">
                                <thead>
                                    <tr>
                                        <th class="text-center ruolini-col-num">#</th>
                                        <th class="ruolini-col-grade">Grado</th>
                                        <th>Militare</th>
                                        <th class="ruolini-col-motivo">Motivazione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categorie[$categoria]['assenti'] as $i => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                        <td>
                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                               class="ruolini-link">
                                                {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                            </a>
                                        </td>
                                        <td>
                                            @foreach($item['impegni'] as $impegno)
                                            <span class="badge ruolini-impegno-badge" style="background: {{ $impegno['colore'] }};" 
                                                  data-bs-toggle="tooltip" title="{{ $impegno['descrizione'] }}">
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
                        <div class="ruolini-empty">Nessun assente</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>

<style>
.ruolini-page {
    position: relative;
    z-index: 1;
}

.ruolini-hero {
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: linear-gradient(135deg, rgba(25, 42, 68, 0.06), rgba(25, 42, 68, 0.02));
}

.ruolini-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
}

.ruolini-hero-note {
    font-size: 0.95rem;
}

.ruolini-toolbar {
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.ruolini-toolbar-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    align-items: center;
    justify-content: space-between;
}

.ruolini-toolbar-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ruolini-toolbar-label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-600);
    font-weight: 600;
}

.ruolini-date-controls,
.ruolini-filter-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.ruolini-date-controls .form-control,
.ruolini-filter-controls .form-select {
    width: 180px;
    border-radius: 8px;
}

.ruolini-date-controls .btn,
.ruolini-filter-controls .btn,
.ruolini-toolbar-actions .btn {
    border-radius: 8px;
}

.ruolini-kpi-card {
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}

.ruolini-kpi-success {
    background: rgba(52, 103, 81, 0.06);
}

.ruolini-kpi-danger {
    background: rgba(220, 53, 69, 0.06);
}

.ruolini-kpi-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-600);
    margin-bottom: 6px;
}

.ruolini-kpi-value {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.ruolini-kpi-sub {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.ruolini-summary-card {
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.ruolini-summary-card .card-header {
    background: var(--navy);
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 12px 16px;
}

.ruolini-summary-table thead {
    background: var(--gray-100);
}

.ruolini-summary-table th,
.ruolini-summary-table td {
    font-size: 0.82rem;
    padding: 10px 12px;
}

.ruolini-category-card {
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.ruolini-category-card .card-header {
    background: var(--navy);
    color: #fff;
    border-radius: 12px 12px 0 0;
    padding: 14px 18px;
}

.ruolini-category-badges .badge {
    font-size: 0.78rem;
    padding: 6px 10px;
}

.ruolini-column-divider {
    border-right: 1px solid var(--border-color);
}

.ruolini-column-header {
    padding: 10px 16px;
    border-bottom: 1px solid var(--border-color);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.ruolini-column-header-success {
    background: rgba(52, 103, 81, 0.08);
}

.ruolini-column-header-danger {
    background: rgba(220, 53, 69, 0.08);
}

.ruolini-table {
    border-collapse: separate;
}

.ruolini-table thead th {
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-600);
    border-bottom: 1px solid var(--border-color) !important;
    background: #fff;
    position: sticky;
    top: 0;
    z-index: 1;
    font-size: 0.7rem;
}

.ruolini-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--gray-200) !important;
    font-size: 0.85rem;
}

.ruolini-table-scroll {
    max-height: 350px;
    overflow-y: auto;
}

.ruolini-col-num {
    width: 40px;
}

.ruolini-col-grade {
    width: 70px;
}

.ruolini-col-plotone {
    width: 110px;
}

.ruolini-col-motivo {
    width: 140px;
}

.ruolini-link {
    color: var(--navy);
    text-decoration: none;
    font-weight: 600;
}

.ruolini-link:hover {
    color: var(--gold);
    text-decoration: underline;
}

.ruolini-impegno-badge {
    font-size: 0.7rem;
    cursor: help;
}

.ruolini-empty {
    text-align: center;
    color: var(--gray-600);
    padding: 24px;
}

.table-hover tbody tr:hover {
    background-color: var(--gray-100) !important;
}

.table-responsive::-webkit-scrollbar {
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: var(--gray-100);
}

.table-responsive::-webkit-scrollbar-thumb {
    background: var(--gray-400);
    border-radius: 3px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: var(--gray-500);
}

@media (max-width: 992px) {
    .ruolini-toolbar-grid {
        align-items: stretch;
    }

    .ruolini-toolbar-actions {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .ruolini-column-divider {
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }

    .ruolini-date-controls .form-control,
    .ruolini-filter-controls .form-select {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
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
    
    const q = params.toString();
    window.location.href = q ? url + '?' + q : url;
}

function applicaFiltri() {
    cambiaData();
}

function exportRuoliniExcel() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = document.getElementById('compagniaSelect').value;
    const plotone = document.getElementById('plotoneSelect').value;
    
    const params = new URLSearchParams();
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    
    const q = params.toString();
    window.location.href = '{{ route("ruolini.export-excel") }}' + (q ? '?' + q : '');
}
</script>
@endsection
