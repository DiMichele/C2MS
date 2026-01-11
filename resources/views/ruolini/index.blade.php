@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="ruolini-page">
    <!-- Header con sfondo -->
    <header class="ruolini-header">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Ruolini</h1>
                    <p class="mb-0 opacity-75">{{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}</p>
                </div>
                <button onclick="exportRuoliniExcel()" class="btn btn-light">
                    <i class="fas fa-file-excel me-2 text-success"></i>Esporta
                </button>
            </div>
        </div>
    </header>

    <div class="container-fluid px-4 py-4">
        <!-- Barra controlli -->
        <nav class="controls-bar mb-4">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <!-- Navigazione data -->
                <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                   class="btn-nav">&larr;</a>
                
                <input type="date" id="dataSelect" class="date-input" 
                       value="{{ $dataSelezionata }}" onchange="cambiaData()">
                
                <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                   class="btn-nav">&rarr;</a>
                
                @if(!$dataObj->isToday())
                    <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                       class="btn-today">Oggi</a>
                @endif
                
                <span class="separator"></span>
                
                <!-- Filtri -->
                <select id="compagniaSelect" class="filter-select" onchange="applicaFiltri()">
                    <option value="">Tutte le compagnie</option>
                    @foreach($compagnie as $compagnia)
                        <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                            {{ $compagnia->nome }}
                        </option>
                    @endforeach
                </select>
                
                <select id="plotoneSelect" class="filter-select" onchange="applicaFiltri()">
                    <option value="">Tutti i plotoni</option>
                    @foreach($plotoni as $plotone)
                        <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                            {{ $plotone->nome }}
                        </option>
                    @endforeach
                </select>
                
                @if($compagniaId || $plotoneId)
                    <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}" class="btn-reset">Reset</a>
                @endif
            </div>
        </nav>

        @php
            $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
            $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
            $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
            $percentualePresenti = $forzaEffettiva > 0 ? round(($totalePresenti / $forzaEffettiva) * 100) : 0;
        @endphp

        <!-- Stats boxes inline -->
        <section class="stats-row mb-4">
            <div class="stat-box">
                <span class="stat-label">Forza Effettiva</span>
                <span class="stat-value">{{ $forzaEffettiva }}</span>
            </div>
            <div class="stat-box presenti">
                <span class="stat-label">Presenti</span>
                <span class="stat-value">{{ $totalePresenti }}</span>
                <span class="stat-percent">{{ $percentualePresenti }}%</span>
            </div>
            <div class="stat-box assenti">
                <span class="stat-label">Assenti</span>
                <span class="stat-value">{{ $totaleAssenti }}</span>
                <span class="stat-percent">{{ 100 - $percentualePresenti }}%</span>
            </div>
            
            <!-- Mini breakdown -->
            <div class="stat-breakdown">
                @foreach(['Uff.' => 'Ufficiali', 'Sott.' => 'Sottufficiali', 'Grad.' => 'Graduati', 'Vol.' => 'Volontari'] as $abbr => $cat)
                    @if($totali[$cat]['totale'] > 0)
                    <div class="breakdown-item">
                        <span class="cat-name">{{ $abbr }}</span>
                        <span class="cat-present text-success">{{ $totali[$cat]['presenti'] }}</span>
                        <span class="cat-sep">/</span>
                        <span class="cat-absent text-danger">{{ $totali[$cat]['assenti'] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </section>

        <!-- Dettaglio per categoria -->
        @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $categoria)
            @if($totali[$categoria]['totale'] > 0)
            <section class="categoria-section mb-4">
                <header class="categoria-header">
                    <h2>{{ $categoria }}</h2>
                    <div class="categoria-counts">
                        <span class="count-present">{{ $totali[$categoria]['presenti'] }} presenti</span>
                        <span class="count-absent">{{ $totali[$categoria]['assenti'] }} assenti</span>
                    </div>
                </header>
                
                <div class="categoria-grid">
                    <!-- Presenti -->
                    <div class="lista-panel presenti">
                        <h3 class="panel-title">Presenti</h3>
                        @if(count($categorie[$categoria]['presenti']) > 0)
                        <table class="lista-table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-grado">Grado</th>
                                    <th class="col-nome">Cognome Nome</th>
                                    <th class="col-plotone">Plotone</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorie[$categoria]['presenti'] as $i => $item)
                                <tr>
                                    <td class="col-num">{{ $i + 1 }}</td>
                                    <td class="col-grado">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                    <td class="col-nome">
                                        <a href="{{ route('anagrafica.show', $item['militare']->id) }}">
                                            {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                        </a>
                                    </td>
                                    <td class="col-plotone">{{ $item['militare']->plotone->nome ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="empty-msg">Nessun presente</p>
                        @endif
                    </div>
                    
                    <!-- Assenti -->
                    <div class="lista-panel assenti">
                        <h3 class="panel-title">Assenti</h3>
                        @if(count($categorie[$categoria]['assenti']) > 0)
                        <table class="lista-table">
                            <thead>
                                <tr>
                                    <th class="col-num">#</th>
                                    <th class="col-grado">Grado</th>
                                    <th class="col-nome">Cognome Nome</th>
                                    <th class="col-motivo">Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorie[$categoria]['assenti'] as $i => $item)
                                <tr>
                                    <td class="col-num">{{ $i + 1 }}</td>
                                    <td class="col-grado">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                    <td class="col-nome">
                                        <a href="{{ route('anagrafica.show', $item['militare']->id) }}">
                                            {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                        </a>
                                    </td>
                                    <td class="col-motivo">
                                        @foreach($item['impegni'] as $impegno)
                                        <span class="motivo-badge" style="background: {{ $impegno['colore'] }}" title="{{ $impegno['descrizione'] }}">
                                            {{ $impegno['codice'] }}
                                        </span>
                                        @endforeach
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <p class="empty-msg">Nessun assente</p>
                        @endif
                    </div>
                </div>
            </section>
            @endif
        @endforeach
    </div>
</div>

<style>
:root {
    --navy: #0f172a;
    --navy-light: #1e293b;
    --green: #10b981;
    --green-bg: #ecfdf5;
    --red: #ef4444;
    --red-bg: #fef2f2;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-900: #111827;
    --radius: 10px;
}

.ruolini-page {
    background: var(--gray-50);
    min-height: 100vh;
}

/* Header */
.ruolini-header {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    color: white;
    padding: 24px 0;
}

.ruolini-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
}

.ruolini-header .btn-light {
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
}

/* Controls bar */
.controls-bar {
    background: white;
    padding: 12px 16px;
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.btn-nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    color: var(--gray-600);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.15s;
}

.btn-nav:hover {
    background: var(--gray-200);
    color: var(--gray-900);
}

.date-input {
    height: 36px;
    padding: 0 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--gray-900);
}

.date-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.1);
}

.btn-today {
    height: 36px;
    padding: 0 14px;
    background: var(--navy);
    color: white;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.15s;
}

.btn-today:hover {
    background: var(--navy-light);
    color: white;
}

.separator {
    width: 1px;
    height: 24px;
    background: var(--gray-200);
}

.filter-select {
    height: 36px;
    padding: 0 32px 0 12px;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--gray-600);
    background: white url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") right 8px center/16px no-repeat;
    cursor: pointer;
    min-width: 160px;
}

.filter-select:focus {
    outline: none;
    border-color: var(--navy);
}

.btn-reset {
    height: 36px;
    padding: 0 12px;
    background: transparent;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--gray-600);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}

.btn-reset:hover {
    background: var(--gray-100);
    color: var(--gray-900);
}

/* Stats row */
.stats-row {
    display: flex;
    align-items: stretch;
    gap: 16px;
    flex-wrap: wrap;
}

.stat-box {
    background: white;
    padding: 20px 24px;
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    min-width: 140px;
}

.stat-box.presenti {
    border-left: 4px solid var(--green);
    background: linear-gradient(135deg, white 0%, var(--green-bg) 100%);
}

.stat-box.assenti {
    border-left: 4px solid var(--red);
    background: linear-gradient(135deg, white 0%, var(--red-bg) 100%);
}

.stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
    font-weight: 600;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gray-900);
    line-height: 1.2;
}

.stat-box.presenti .stat-value { color: var(--green); }
.stat-box.assenti .stat-value { color: var(--red); }

.stat-percent {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--gray-400);
}

.stat-breakdown {
    display: flex;
    align-items: center;
    gap: 20px;
    background: white;
    padding: 16px 24px;
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    margin-left: auto;
}

.breakdown-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
}

.cat-name {
    font-weight: 600;
    color: var(--gray-600);
}

.cat-present, .cat-absent {
    font-weight: 700;
}

.cat-sep {
    color: var(--gray-300);
}

/* Categoria section */
.categoria-section {
    background: white;
    border-radius: var(--radius);
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.categoria-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--gray-100);
}

.categoria-header h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
}

.categoria-counts {
    display: flex;
    gap: 16px;
    font-size: 0.85rem;
    font-weight: 500;
}

.count-present { color: var(--green); }
.count-absent { color: var(--red); }

.categoria-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
}

@media (max-width: 992px) {
    .categoria-grid {
        grid-template-columns: 1fr;
    }
}

.lista-panel {
    padding: 0;
    max-height: 400px;
    overflow-y: auto;
}

.lista-panel.presenti {
    border-right: 1px solid var(--gray-100);
}

@media (max-width: 992px) {
    .lista-panel.presenti {
        border-right: none;
        border-bottom: 1px solid var(--gray-100);
    }
}

.panel-title {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 10px 16px;
    margin: 0;
    position: sticky;
    top: 0;
    z-index: 1;
}

.lista-panel.presenti .panel-title {
    background: var(--green-bg);
    color: var(--green);
}

.lista-panel.assenti .panel-title {
    background: var(--red-bg);
    color: var(--red);
}

.lista-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.lista-table th {
    text-align: left;
    padding: 8px 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-400);
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-100);
    position: sticky;
    top: 32px;
    z-index: 1;
}

.lista-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--gray-100);
    vertical-align: middle;
}

.lista-table tbody tr:hover {
    background: var(--gray-50);
}

.lista-table .col-num {
    width: 40px;
    color: var(--gray-400);
    text-align: center;
}

.lista-table .col-grado {
    width: 70px;
    font-weight: 600;
}

.lista-table .col-nome a {
    color: var(--gray-900);
    text-decoration: none;
    font-weight: 500;
}

.lista-table .col-nome a:hover {
    color: var(--navy);
    text-decoration: underline;
}

.lista-table .col-plotone,
.lista-table .col-motivo {
    width: 120px;
}

.lista-table .col-plotone {
    color: var(--gray-400);
    font-size: 0.8rem;
}

.motivo-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    color: white;
    margin-right: 4px;
    cursor: help;
}

.empty-msg {
    text-align: center;
    color: var(--gray-400);
    padding: 40px 20px;
    font-size: 0.9rem;
    margin: 0;
}

/* Scrollbar */
.lista-panel::-webkit-scrollbar {
    width: 6px;
}

.lista-panel::-webkit-scrollbar-track {
    background: var(--gray-100);
}

.lista-panel::-webkit-scrollbar-thumb {
    background: var(--gray-300);
    border-radius: 3px;
}

.lista-panel::-webkit-scrollbar-thumb:hover {
    background: var(--gray-400);
}

@media (max-width: 768px) {
    .stats-row {
        flex-direction: column;
    }
    
    .stat-breakdown {
        margin-left: 0;
        width: 100%;
        justify-content: space-around;
    }
    
    .controls-bar {
        overflow-x: auto;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(function(el) {
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
