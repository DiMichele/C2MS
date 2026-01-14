@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid ruolini-page">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">Ruolini</h1>
                <p class="text-muted mb-0">{{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}</p>
            </div>
        </div>
    </div>

    <!-- Controlli -->
    <div class="filter-card mb-4">
        <div class="filter-card-header">
            <span>Filtri Ruolini</span>
        </div>
        <div class="card-body">
            <div class="filter-row">
                <div class="filter-col">
                    <div class="filter-control">
                        <label for="dataSelect">Data</label>
                        <div class="ruolini-date-controls">
                            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <input type="date" id="dataSelect" class="form-control form-control-sm"
                                   value="{{ $dataSelezionata }}" onchange="cambiaData()">

                            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
                               class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>

                            @if(!$dataObj->isToday())
                                <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
                                   class="btn btn-primary btn-sm">
                                    Oggi
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="filter-col">
                    <div class="filter-control">
                        <label for="compagniaSelect">Compagnia</label>
                        @if($canChangeCompagnia ?? false)
                        <select id="compagniaSelect" class="form-select form-select-sm filter-select" onchange="cambiaCompagnia()">
                            <option value="">Tutte le compagnie</option>
                            @foreach($compagnie as $compagnia)
                                <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                                    {{ $compagnia->nome }}
                                </option>
                            @endforeach
                        </select>
                        @else
                        <select id="compagniaSelect" class="form-select form-select-sm filter-select" disabled>
                            @foreach($compagnie as $compagnia)
                                @if($compagniaId == $compagnia->id)
                                <option value="{{ $compagnia->id }}" selected>{{ $compagnia->nome }}</option>
                                @endif
                            @endforeach
                        </select>
                        <input type="hidden" id="compagniaIdHidden" value="{{ $compagniaId }}">
                        @endif
                    </div>
                </div>
            </div>
            <div class="filter-row">
                <div class="filter-col">
                    <div class="filter-control">
                        <label for="plotoneSelect">Plotone</label>
                        <select id="plotoneSelect" class="form-select form-select-sm filter-select" onchange="applicaFiltri()" {{ empty($compagniaId) ? 'disabled' : '' }}>
                            <option value="">{{ empty($compagniaId) ? 'Seleziona prima una compagnia' : 'Tutti i plotoni' }}</option>
                            @foreach($plotoni as $plotone)
                                <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                                    {{ $plotone->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-col">
                    <div class="filter-control">
                        <label for="ufficioSelect">Ufficio (polo)</label>
                        <select id="ufficioSelect" class="form-select form-select-sm filter-select" onchange="applicaFiltri()">
                            <option value="">Tutti gli uffici</option>
                            @foreach($uffici as $ufficio)
                                <option value="{{ $ufficio->id }}" {{ $ufficioId == $ufficio->id ? 'selected' : '' }}>
                                    {{ $ufficio->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            @if($compagniaId || $plotoneId || $ufficioId)
                <div class="filter-actions">
                    <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}" class="btn btn-filter btn-filter-reset">
                        <i class="fas fa-times me-1"></i>Reset filtri
                    </a>
                </div>
            @endif
        </div>
    </div>

    @php
        $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
        $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
        $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
        $percentualePresenti = $forzaEffettiva > 0 ? round(($totalePresenti / $forzaEffettiva) * 100) : 0;
    @endphp

    <!-- Statistiche principali -->
    <div class="ruolini-stats mb-4">
        <div class="ruolini-stat-card">
            <div class="ruolini-stat-label">Forza effettiva</div>
            <div class="ruolini-stat-value">{{ $forzaEffettiva }}</div>
            <div class="ruolini-stat-sub">Totale personale in forza</div>
        </div>
        <div class="ruolini-stat-card ruolini-stat-card-success">
            <div class="ruolini-stat-label">Presenti</div>
            <div class="ruolini-stat-value text-success">{{ $totalePresenti }}</div>
            <div class="ruolini-stat-sub">{{ $percentualePresenti }}% del totale</div>
        </div>
        <div class="ruolini-stat-card ruolini-stat-card-danger">
            <div class="ruolini-stat-label">Assenti</div>
            <div class="ruolini-stat-value text-danger">{{ $totaleAssenti }}</div>
            <div class="ruolini-stat-sub">{{ 100 - $percentualePresenti }}% del totale</div>
        </div>
        <div class="ruolini-stat-card ruolini-stat-summary">
            <div class="ruolini-stat-label">Riepilogo per categoria</div>
            <div class="ruolini-mini-table">
                <div class="ruolini-mini-row ruolini-mini-head">
                    <span>Categoria</span>
                    <span class="text-center">Tot</span>
                    <span class="text-center text-success">Pres</span>
                    <span class="text-center text-danger">Ass</span>
                </div>
                @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $cat)
                    @if($totali[$cat]['totale'] > 0)
                    <div class="ruolini-mini-row">
                        <span>{{ $cat }}</span>
                        <span class="text-center fw-bold">{{ $totali[$cat]['totale'] }}</span>
                        <span class="text-center fw-bold text-success">{{ $totali[$cat]['presenti'] }}</span>
                        <span class="text-center fw-bold text-danger">{{ $totali[$cat]['assenti'] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Dettaglio per categoria -->
    @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $categoria)
        @if($totali[$categoria]['totale'] > 0)
        <div class="ruolini-accordion card mb-3">
            <button class="ruolini-accordion-header collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ruolini-{{ Str::slug($categoria) }}" aria-expanded="false" aria-controls="ruolini-{{ Str::slug($categoria) }}">
                <div>
                    <h5 class="mb-1">{{ $categoria }}</h5>
                    <small class="text-muted">Totale: {{ $totali[$categoria]['totale'] }}</small>
                </div>
                <div class="ruolini-accordion-meta">
                    <span class="ruolini-pill ruolini-pill-success">{{ $totali[$categoria]['presenti'] }} presenti</span>
                    <span class="ruolini-pill ruolini-pill-danger">{{ $totali[$categoria]['assenti'] }} assenti</span>
                    <i class="fas fa-chevron-down ruolini-accordion-icon"></i>
                </div>
            </button>
            <div id="ruolini-{{ Str::slug($categoria) }}" class="collapse">
                <div class="card-body p-0">
                    <div class="row no-gutters">
                        <!-- Colonna Presenti -->
                        <div class="col-lg-6 ruolini-column-divider">
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
                        <div class="col-lg-6">
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
        </div>
        @endif
    @endforeach
</div>

<!-- Floating export button -->
<button type="button" class="fab fab-excel" id="exportExcel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

<style>
.ruolini-page {
    position: relative;
    z-index: 1;
}


.ruolini-date-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.ruolini-date-controls .form-control {
    width: 180px;
    border-radius: 8px;
}

.ruolini-date-controls .btn {
    border-radius: 8px;
}

.ruolini-stats {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.ruolini-stat-card {
    border-radius: 14px;
    border: 1px solid var(--border-color);
    background: #fff;
    padding: 16px 18px;
    box-shadow: 0 1px 8px rgba(15, 23, 42, 0.04);
}

.ruolini-stat-card-success {
    background: rgba(52, 103, 81, 0.05);
}

.ruolini-stat-card-danger {
    background: rgba(220, 53, 69, 0.05);
}

.ruolini-stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-600);
    margin-bottom: 6px;
}

.ruolini-stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.ruolini-stat-sub {
    font-size: 0.85rem;
    color: var(--gray-600);
}

.ruolini-stat-summary {
    padding: 12px 14px;
}

.ruolini-mini-table {
    display: grid;
    gap: 6px;
    margin-top: 10px;
}

.ruolini-mini-row {
    display: grid;
    grid-template-columns: 1.3fr repeat(3, 0.6fr);
    gap: 8px;
    align-items: center;
    font-size: 0.82rem;
    padding: 6px 8px;
    border-radius: 8px;
    background: rgba(148, 163, 184, 0.08);
}

.ruolini-mini-head {
    background: transparent;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.68rem;
    color: var(--gray-600);
    padding: 0 4px 4px;
}

.ruolini-accordion {
    border-radius: 14px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: 0 1px 8px rgba(15, 23, 42, 0.04);
}

.ruolini-accordion-header {
    width: 100%;
    text-align: left;
    border: none;
    background: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.ruolini-accordion-header:focus {
    outline: none;
}

.ruolini-accordion-meta {
    display: flex;
    align-items: center;
    gap: 10px;
}

.ruolini-accordion-icon {
    color: var(--gray-500);
    transition: transform 0.2s ease;
}

.ruolini-accordion-header[aria-expanded="true"] .ruolini-accordion-icon {
    transform: rotate(180deg);
}

.ruolini-pill {
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.ruolini-pill-success {
    background: rgba(52, 103, 81, 0.1);
    color: var(--success);
}

.ruolini-pill-danger {
    background: rgba(220, 53, 69, 0.1);
    color: var(--danger);
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
    max-height: none;
    overflow-y: visible;
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

@media (max-width: 768px) {
    .ruolini-accordion-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .ruolini-accordion-meta {
        flex-wrap: wrap;
    }

    .ruolini-column-divider {
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }

    .ruolini-date-controls .form-control {
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

    const exportBtn = document.getElementById('exportExcel');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportRuoliniExcel();
        });
    }
});

function getCompagniaId() {
    const select = document.getElementById('compagniaSelect');
    const hidden = document.getElementById('compagniaIdHidden');
    if (select && !select.disabled) {
        return select.value;
    }
    return hidden ? hidden.value : '';
}

function cambiaData() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = getCompagniaId();
    const plotone = document.getElementById('plotoneSelect').value;
    const ufficio = document.getElementById('ufficioSelect').value;
    
    let url = '{{ route("ruolini.index") }}';
    const params = new URLSearchParams();
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    if (ufficio) params.append('ufficio_id', ufficio);
    
    const q = params.toString();
    window.location.href = q ? url + '?' + q : url;
}

function cambiaCompagnia() {
    // Quando cambia la compagnia, resetta il plotone e ricarica la pagina
    const data = document.getElementById('dataSelect').value;
    const compagnia = document.getElementById('compagniaSelect').value;
    const ufficio = document.getElementById('ufficioSelect').value;
    
    let url = '{{ route("ruolini.index") }}';
    const params = new URLSearchParams();
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    // Non includere plotone perché deve essere resettato
    if (ufficio) params.append('ufficio_id', ufficio);
    
    const q = params.toString();
    window.location.href = q ? url + '?' + q : url;
}

function applicaFiltri() {
    cambiaData();
}

function exportRuoliniExcel() {
    const data = document.getElementById('dataSelect').value;
    const compagnia = getCompagniaId();
    const plotone = document.getElementById('plotoneSelect').value;
    const ufficio = document.getElementById('ufficioSelect').value;
    
    const params = new URLSearchParams();
    if (data) params.append('data', data);
    if (compagnia) params.append('compagnia_id', compagnia);
    if (plotone) params.append('plotone_id', plotone);
    if (ufficio) params.append('ufficio_id', ufficio);
    
    const q = params.toString();
    window.location.href = '{{ route("ruolini.export-excel") }}' + (q ? '?' + q : '');
}
</script>
@endsection
