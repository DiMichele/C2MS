@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid ruolini-page">
    <!-- Header con titolo -->
    <div class="text-center mb-3">
        <h1 class="page-title">Ruolini</h1>
    </div>

    <!-- Selettore Data -->
    <div class="ruolini-date-section mb-3">
        <div class="ruolini-date-controls">
            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-chevron-left"></i>
            </a>

            <input type="date" id="dataSelect" class="form-control"
                   value="{{ $dataSelezionata }}" onchange="cambiaData()">

            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
               class="btn btn-outline-secondary">
                <i class="fas fa-chevron-right"></i>
            </a>

            @if(!$dataObj->isToday())
                <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId, 'ufficio_id' => $ufficioId])) }}"
                   class="btn btn-primary">
                    Oggi
                </a>
            @endif
        </div>
        <div class="ruolini-date-label">
            {{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}
        </div>
    </div>

    <!-- Campo di ricerca centrato -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 400px;">
            <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; pointer-events: none; z-index: 5;"></i>
            <input type="text" 
                   id="searchMilitare" 
                   class="form-control" 
                   placeholder="Cerca militare..." 
                   aria-label="Cerca militare"
                   style="padding-left: 40px; border-radius: 20px; box-shadow: 0 3px 10px rgba(0,0,0,0.06);">
        </div>
    </div>

    <!-- Selettori inline: Compagnia, Plotone, Ufficio -->
    <div class="ruolini-filters-inline mb-4">
        <div class="ruolini-filter-item">
            <label for="compagniaSelect">Compagnia</label>
            @if($canChangeCompagnia ?? false)
            <select id="compagniaSelect" class="form-select form-select-sm">
                <option value="">Tutte</option>
                @foreach($compagnie as $compagnia)
                    <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                        {{ $compagnia->nome }}
                    </option>
                @endforeach
            </select>
            @else
            <select id="compagniaSelect" class="form-select form-select-sm" disabled>
                @foreach($compagnie as $compagnia)
                    @if($compagniaId == $compagnia->id)
                    <option value="{{ $compagnia->id }}" selected>{{ $compagnia->nome }}</option>
                    @endif
                @endforeach
            </select>
            <input type="hidden" id="compagniaIdHidden" value="{{ $compagniaId }}">
            @endif
        </div>

        <div class="ruolini-filter-item">
            <label for="plotoneSelect">Plotone</label>
            <select id="plotoneSelect" class="form-select form-select-sm">
                <option value="">Tutti</option>
                @foreach($plotoni as $plotone)
                    <option value="{{ $plotone->id }}" 
                            data-compagnia-id="{{ $plotone->compagnia_id }}"
                            {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                        {{ $plotone->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="ruolini-filter-item">
            <label for="ufficioSelect">Ufficio</label>
            <select id="ufficioSelect" class="form-select form-select-sm">
                <option value="">Tutti</option>
                @foreach($uffici as $ufficio)
                    <option value="{{ $ufficio->id }}" {{ $ufficioId == $ufficio->id ? 'selected' : '' }}>
                        {{ $ufficio->nome }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @php
        $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
        $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
        $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
    @endphp

    <!-- Riepilogo semplificato: Forza, Presenti, Assenti -->
    <div class="ruolini-summary-boxes mb-4" id="riepilogoTotali">
        <div class="ruolini-box ruolini-box-forza">
            <div class="ruolini-box-label">Forza Effettiva</div>
            <div class="ruolini-box-value forza-value">{{ $forzaEffettiva }}</div>
        </div>
        <div class="ruolini-box ruolini-box-presenti">
            <div class="ruolini-box-label">Presenti</div>
            <div class="ruolini-box-value presenti-value">{{ $totalePresenti }}</div>
        </div>
        <div class="ruolini-box ruolini-box-assenti">
            <div class="ruolini-box-label">Assenti</div>
            <div class="ruolini-box-value assenti-value">{{ $totaleAssenti }}</div>
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
                                <table class="sugeco-table ruolini-detail-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center ruolini-col-num">#</th>
                                            <th class="ruolini-col-grade">Grado</th>
                                            <th class="ruolini-col-cognome">Cognome</th>
                                            <th class="ruolini-col-nome">Nome</th>
                                            <th class="ruolini-col-plotone">Plotone</th>
                                            <th class="ruolini-col-ufficio">Ufficio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categorie[$categoria]['presenti'] as $i => $item)
                                        <tr class="ruolini-row ruolini-row-presente" 
                                            data-categoria="{{ Str::slug($categoria) }}"
                                            data-compagnia-id="{{ $item['militare']->compagnia_id ?? $item['militare']->plotone->compagnia_id ?? '' }}"
                                            data-plotone-id="{{ $item['militare']->plotone_id ?? '' }}"
                                            data-polo-id="{{ $item['militare']->polo_id ?? '' }}">
                                            <td class="text-center text-muted row-number">{{ $i + 1 }}</td>
                                            <td class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                            <td>
                                                <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                                   class="ruolini-link">{{ $item['militare']->cognome }}</a>
                                            </td>
                                            <td>{{ $item['militare']->nome }}</td>
                                            <td class="text-muted">{{ $item['militare']->plotone->nome ?? '-' }}</td>
                                            <td class="text-muted">{{ $item['militare']->polo->nome ?? '-' }}</td>
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
                                <table class="sugeco-table ruolini-detail-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center ruolini-col-num">#</th>
                                            <th class="ruolini-col-grade">Grado</th>
                                            <th class="ruolini-col-cognome">Cognome</th>
                                            <th class="ruolini-col-nome">Nome</th>
                                            <th class="ruolini-col-plotone">Plotone</th>
                                            <th class="ruolini-col-ufficio">Ufficio</th>
                                            <th class="ruolini-col-motivo">Motivazione</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categorie[$categoria]['assenti'] as $i => $item)
                                        <tr class="ruolini-row ruolini-row-assente" 
                                            data-categoria="{{ Str::slug($categoria) }}"
                                            data-compagnia-id="{{ $item['militare']->compagnia_id ?? $item['militare']->plotone->compagnia_id ?? '' }}"
                                            data-plotone-id="{{ $item['militare']->plotone_id ?? '' }}"
                                            data-polo-id="{{ $item['militare']->polo_id ?? '' }}">
                                            <td class="text-center text-muted row-number">{{ $i + 1 }}</td>
                                            <td class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                            <td>
                                                <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                                   class="ruolini-link">{{ $item['militare']->cognome }}</a>
                                            </td>
                                            <td>{{ $item['militare']->nome }}</td>
                                            <td class="text-muted">{{ $item['militare']->plotone->nome ?? '-' }}</td>
                                            <td class="text-muted">{{ $item['militare']->polo->nome ?? '-' }}</td>
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

/* Sezione Data */
.ruolini-date-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.ruolini-date-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    justify-content: center;
}

.ruolini-date-controls .form-control {
    width: 160px;
    border-radius: 6px;
    text-align: center;
    font-weight: 500;
}

.ruolini-date-controls .btn {
    border-radius: 6px;
    padding: 0.375rem 0.75rem;
}

.ruolini-date-label {
    font-size: 0.9rem;
    color: var(--gray-600);
    font-weight: 500;
}

/* Filtri inline */
.ruolini-filters-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    align-items: flex-end;
    padding: 16px 24px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid var(--border-color);
}

.ruolini-filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.ruolini-filter-item label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-600);
    font-weight: 600;
}

.ruolini-filter-item .form-select {
    min-width: 160px;
    border-radius: 6px;
    padding-right: 32px;
    text-overflow: ellipsis;
}

/* Box Riepilogo Semplificato */
.ruolini-summary-boxes {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.ruolini-box {
    min-width: 140px;
    padding: 20px 28px;
    border-radius: 14px;
    text-align: center;
    box-shadow: 0 2px 12px rgba(15, 23, 42, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ruolini-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
}

.ruolini-box-forza {
    background: linear-gradient(135deg, #0a2342 0%, #1a3a5c 100%);
}

.ruolini-box-forza .ruolini-box-label {
    color: rgba(255, 255, 255, 0.8);
}

.ruolini-box-forza .ruolini-box-value {
    color: #fff;
}

.ruolini-box-presenti {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #28a745;
}

.ruolini-box-presenti .ruolini-box-label {
    color: #155724;
}

.ruolini-box-presenti .ruolini-box-value {
    color: #155724;
}

.ruolini-box-assenti {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border: 1px solid #dc3545;
}

.ruolini-box-assenti .ruolini-box-label {
    color: #721c24;
}

.ruolini-box-assenti .ruolini-box-value {
    color: #721c24;
}

.ruolini-box-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    margin-bottom: 6px;
}

.ruolini-box-value {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
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
    padding: 14px 16px;
    border-bottom: none;
    text-transform: uppercase;
    letter-spacing: 1px;
    background: transparent;
    font-size: 0.75rem;
}

.ruolini-column-header-success,
.ruolini-column-header-danger {
    background: transparent;
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

/* Stili tabelle dettaglio */
.ruolini-detail-table {
    font-size: 0.85rem;
    width: 100%;
    table-layout: auto;
}

.ruolini-detail-table th,
.ruolini-detail-table td {
    padding: 10px 12px !important;
}

.ruolini-col-num {
    width: 45px;
    text-align: center;
}

.ruolini-col-grade {
    width: 80px;
}

.ruolini-col-cognome {
    width: 20%;
}

.ruolini-col-nome {
    width: 18%;
}

.ruolini-col-plotone {
    width: 18%;
}

.ruolini-col-ufficio {
    width: 18%;
}

.ruolini-col-motivo {
    width: auto;
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
        width: 140px;
    }

    .ruolini-filters-inline {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .ruolini-filter-item {
        width: 100%;
    }

    .ruolini-filter-item .form-select {
        width: 100%;
    }

    .ruolini-summary-boxes {
        gap: 12px;
    }

    .ruolini-box {
        min-width: 100px;
        padding: 16px 20px;
    }

    .ruolini-box-value {
        font-size: 1.8rem;
    }

    .ruolini-box-label {
        font-size: 0.7rem;
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
    
    // Inizializza filtri client-side
    initClientSideFilters();
    
    // Applica i filtri iniziali (se presenti nell'URL o nei select)
    applicaFiltri();
    
    // Inizializza la ricerca militari
    initRuoliniSearch();
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
    // Filtra le opzioni del plotone e applica i filtri client-side
    const compagniaId = document.getElementById('compagniaSelect')?.value || getCompagniaId();
    aggiornaOpzioniPlotone(compagniaId);
    applicaFiltri();
}

/**
 * Inizializza i filtri client-side per compagnia, plotone e ufficio
 */
function initClientSideFilters() {
    const compagniaSelect = document.getElementById('compagniaSelect');
    const plotoneSelect = document.getElementById('plotoneSelect');
    const ufficioSelect = document.getElementById('ufficioSelect');
    
    // Compagnia - solo se abilitato (admin)
    if (compagniaSelect && !compagniaSelect.disabled) {
        compagniaSelect.removeAttribute('onchange');
        compagniaSelect.addEventListener('change', function() {
            // Filtra le opzioni del plotone per la compagnia selezionata
            aggiornaOpzioniPlotone(this.value);
            // Applica i filtri
            applicaFiltri();
        });
    }
    
    // Plotone
    if (plotoneSelect) {
        plotoneSelect.removeAttribute('onchange');
        plotoneSelect.addEventListener('change', applicaFiltri);
    }
    
    // Ufficio
    if (ufficioSelect) {
        ufficioSelect.removeAttribute('onchange');
        ufficioSelect.addEventListener('change', applicaFiltri);
    }
    
    // Inizializza opzioni plotone in base alla compagnia attuale
    if (compagniaSelect) {
        aggiornaOpzioniPlotone(compagniaSelect.value || getCompagniaId());
    }
}

/**
 * Aggiorna le opzioni visibili del select plotone in base alla compagnia
 */
function aggiornaOpzioniPlotone(compagniaId) {
    const plotoneSelect = document.getElementById('plotoneSelect');
    if (!plotoneSelect) return;
    
    const options = plotoneSelect.querySelectorAll('option');
    const placeholderOption = plotoneSelect.querySelector('option[value=""]');
    
    // Se non c'è compagnia selezionata, disabilita il select
    if (!compagniaId) {
        plotoneSelect.disabled = true;
        plotoneSelect.value = '';
        if (placeholderOption) {
            placeholderOption.textContent = 'Seleziona compagnia';
        }
        // Nascondi tutte le opzioni tranne il placeholder
        options.forEach(option => {
            if (option.value) {
                option.style.display = 'none';
            }
        });
        return;
    }
    
    // Compagnia selezionata: abilita il select
    plotoneSelect.disabled = false;
    if (placeholderOption) {
        placeholderOption.textContent = 'Tutti';
    }
    
    options.forEach(option => {
        if (!option.value) {
            // Opzione placeholder - sempre visibile
            return;
        }
        
        const optCompagniaId = option.dataset.compagniaId;
        
        if (optCompagniaId === compagniaId) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
    
    // Se il plotone selezionato non appartiene più alla compagnia, resetta
    if (plotoneSelect.value) {
        const selectedOption = plotoneSelect.querySelector(`option[value="${plotoneSelect.value}"]`);
        if (selectedOption && selectedOption.style.display === 'none') {
            plotoneSelect.value = '';
        }
    }
}

/**
 * Applica i filtri client-side senza ricaricare la pagina
 */
function applicaFiltri() {
    const compagniaId = getCompagniaId();
    const plotoneId = document.getElementById('plotoneSelect').value;
    const ufficioId = document.getElementById('ufficioSelect').value;
    
    // Seleziona tutte le righe filtrables
    const rows = document.querySelectorAll('.ruolini-row');
    
    // Contatori per categoria
    const conteggi = {
        'ufficiali': { presenti: 0, assenti: 0 },
        'sottufficiali': { presenti: 0, assenti: 0 },
        'graduati': { presenti: 0, assenti: 0 },
        'volontari': { presenti: 0, assenti: 0 }
    };
    
    rows.forEach(row => {
        const rowCompagniaId = row.dataset.compagniaId || '';
        const rowPlotoneId = row.dataset.plotoneId || '';
        const rowPoloId = row.dataset.poloId || '';
        const categoria = row.dataset.categoria;
        const isPresente = row.classList.contains('ruolini-row-presente');
        
        let show = true;
        
        // Filtro compagnia
        if (compagniaId && rowCompagniaId !== compagniaId) {
            show = false;
        }
        
        // Filtro plotone
        if (show && plotoneId && rowPlotoneId !== plotoneId) {
            show = false;
        }
        
        // Filtro ufficio
        if (show && ufficioId && rowPoloId !== ufficioId) {
            show = false;
        }
        
        // Mostra/nascondi riga
        row.style.display = show ? '' : 'none';
        
        // Aggiorna conteggi
        if (show && categoria && conteggi[categoria]) {
            if (isPresente) {
                conteggi[categoria].presenti++;
            } else {
                conteggi[categoria].assenti++;
            }
        }
    });
    
    // Aggiorna numerazione righe e conteggi UI
    aggiornaNumeriRighe();
    aggiornaConteggiUI(conteggi);
    aggiornaTabellaRiepilogo(conteggi);
    aggiornaMessaggiVuoti(conteggi);
    
    // Aggiorna URL senza ricaricare
    aggiornaURL(compagniaId, plotoneId, ufficioId);
}

/**
 * Aggiorna i numeri progressivi delle righe visibili
 */
function aggiornaNumeriRighe() {
    // Per ogni tabella (presenti e assenti per categoria)
    document.querySelectorAll('.ruolini-table-scroll tbody').forEach(tbody => {
        let counter = 1;
        tbody.querySelectorAll('tr.ruolini-row').forEach(row => {
            if (row.style.display !== 'none') {
                const numCell = row.querySelector('.row-number');
                if (numCell) {
                    numCell.textContent = counter++;
                }
            }
        });
    });
}

/**
 * Aggiorna i conteggi nelle pills degli accordion
 */
function aggiornaConteggiUI(conteggi) {
    const categorieMap = {
        'ufficiali': 'Ufficiali',
        'sottufficiali': 'Sottufficiali',
        'graduati': 'Graduati',
        'volontari': 'Volontari'
    };
    
    for (const [slug, nome] of Object.entries(categorieMap)) {
        const accordion = document.getElementById('ruolini-' + slug);
        if (!accordion) continue;
        
        const header = accordion.previousElementSibling;
        if (!header) continue;
        
        const totale = conteggi[slug].presenti + conteggi[slug].assenti;
        
        // Aggiorna subtitle "Totale: X"
        const subtitle = header.querySelector('small.text-muted');
        if (subtitle) {
            subtitle.textContent = 'Totale: ' + totale;
        }
        
        // Aggiorna pills
        const pillPresenti = header.querySelector('.ruolini-pill-success');
        const pillAssenti = header.querySelector('.ruolini-pill-danger');
        
        if (pillPresenti) {
            pillPresenti.textContent = conteggi[slug].presenti + ' presenti';
        }
        if (pillAssenti) {
            pillAssenti.textContent = conteggi[slug].assenti + ' assenti';
        }
        
        // Nascondi/mostra accordion se vuoto
        const accordionCard = accordion.closest('.ruolini-accordion');
        if (accordionCard) {
            accordionCard.style.display = totale > 0 ? '' : 'none';
        }
    }
}

/**
 * Aggiorna i box riepilogo con i nuovi conteggi
 */
function aggiornaTabellaRiepilogo(conteggi) {
    let totalePresenti = 0;
    let totaleAssenti = 0;
    let forzaEffettiva = 0;
    
    // Calcola i totali da tutte le categorie
    for (const [slug, catData] of Object.entries(conteggi)) {
        totalePresenti += catData.presenti;
        totaleAssenti += catData.assenti;
        forzaEffettiva += catData.presenti + catData.assenti;
    }
    
    // Aggiorna i box
    const riepilogo = document.getElementById('riepilogoTotali');
    if (!riepilogo) return;
    
    const forzaCell = riepilogo.querySelector('.forza-value');
    const presentiCell = riepilogo.querySelector('.presenti-value');
    const assentiCell = riepilogo.querySelector('.assenti-value');
    
    if (forzaCell) forzaCell.textContent = forzaEffettiva;
    if (presentiCell) presentiCell.textContent = totalePresenti;
    if (assentiCell) assentiCell.textContent = totaleAssenti;
}

/**
 * Aggiorna i messaggi "Nessun presente/assente"
 */
function aggiornaMessaggiVuoti(conteggi) {
    const categorieMap = {
        'ufficiali': 'Ufficiali',
        'sottufficiali': 'Sottufficiali',
        'graduati': 'Graduati',
        'volontari': 'Volontari'
    };
    
    for (const [slug, nome] of Object.entries(categorieMap)) {
        const accordion = document.getElementById('ruolini-' + slug);
        if (!accordion) continue;
        
        // Trova le colonne presenti e assenti
        const columns = accordion.querySelectorAll('.col-lg-6');
        
        columns.forEach((col, idx) => {
            const isPresenti = idx === 0;
            const count = isPresenti ? conteggi[slug].presenti : conteggi[slug].assenti;
            
            const tableWrapper = col.querySelector('.table-responsive');
            let emptyMsg = col.querySelector('.ruolini-empty');
            
            if (count === 0) {
                // Nascondi tabella, mostra messaggio vuoto
                if (tableWrapper) tableWrapper.style.display = 'none';
                
                if (!emptyMsg) {
                    emptyMsg = document.createElement('div');
                    emptyMsg.className = 'ruolini-empty';
                    emptyMsg.textContent = isPresenti ? 'Nessun presente' : 'Nessun assente';
                    col.appendChild(emptyMsg);
                } else {
                    emptyMsg.style.display = '';
                }
            } else {
                // Mostra tabella, nascondi messaggio
                if (tableWrapper) tableWrapper.style.display = '';
                if (emptyMsg) emptyMsg.style.display = 'none';
            }
        });
    }
}

/**
 * Aggiorna l'URL con i parametri dei filtri senza ricaricare
 */
function aggiornaURL(compagniaId, plotoneId, ufficioId) {
    const url = new URL(window.location);
    
    if (compagniaId) {
        url.searchParams.set('compagnia_id', compagniaId);
    } else {
        url.searchParams.delete('compagnia_id');
    }
    
    if (plotoneId) {
        url.searchParams.set('plotone_id', plotoneId);
    } else {
        url.searchParams.delete('plotone_id');
    }
    
    if (ufficioId) {
        url.searchParams.set('ufficio_id', ufficioId);
    } else {
        url.searchParams.delete('ufficio_id');
    }
    
    window.history.replaceState({}, '', url);
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

// ============================================
// RICERCA MILITARI
// ============================================

/**
 * Inizializza la ricerca militari
 */
function initRuoliniSearch() {
    const searchInput = document.getElementById('searchMilitare');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Debounce
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => {
            searchRuolini(query);
        }, 150);
    });
    
    // Chiudi dropdown cliccando fuori
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            hideSearchDropdown();
        }
    });
}

/**
 * Cerca militari nei ruolini
 */
function searchRuolini(query) {
    // Pulisci evidenziazioni precedenti
    clearSearchHighlights();
    hideSearchDropdown();
    
    if (!query || query.length === 0) {
        return;
    }
    
    const filter = query.toLowerCase();
    const rows = document.querySelectorAll('.ruolini-row');
    const foundMilitari = [];
    
    rows.forEach(row => {
        // Salta righe nascoste dai filtri
        if (row.style.display === 'none') return;
        
        const linkElement = row.querySelector('.ruolini-link');
        const nomeCell = row.querySelector('td:nth-child(4)');
        
        if (linkElement) {
            // Cognome è nel link, nome nella quarta cella
            const cognome = linkElement.textContent.trim().toLowerCase();
            const nome = nomeCell ? nomeCell.textContent.trim().toLowerCase() : '';
            const fullText = cognome + ' ' + nome;
            
            if (matchesInitials(fullText, filter)) {
                const categoria = row.dataset.categoria;
                const isPresente = row.classList.contains('ruolini-row-presente');
                const gradoCell = row.querySelector('td:nth-child(2)');
                const plotoneCell = row.querySelector('td:nth-child(5)');
                const ufficioCell = row.querySelector('td:nth-child(6)');
                
                foundMilitari.push({
                    element: row,
                    cognome: linkElement.textContent.trim(),
                    nome: nomeCell ? nomeCell.textContent.trim() : '',
                    grado: gradoCell ? gradoCell.textContent.trim() : '',
                    categoria: categoria,
                    status: isPresente ? 'Presente' : 'Assente',
                    plotone: plotoneCell ? plotoneCell.textContent.trim() : '',
                    ufficio: ufficioCell ? ufficioCell.textContent.trim() : '',
                    href: linkElement.getAttribute('href')
                });
            }
        }
    });
    
    showSearchDropdown(foundMilitari, query);
}

/**
 * Controlla se il testo corrisponde alle iniziali cercate
 */
function matchesInitials(text, filter) {
    const words = text.split(/\s+/).filter(word => word.length > 1);
    
    // Cerca se qualsiasi parola inizia con il filtro
    for (let i = 0; i < words.length; i++) {
        if (words[i].startsWith(filter)) {
            return true;
        }
    }
    
    // Se il filtro ha almeno 2 caratteri, controlla anche le iniziali combinate
    if (filter.length >= 2) {
        for (let i = 0; i < words.length - 1; i++) {
            const initials1 = words[i].charAt(0) + words[i + 1].charAt(0);
            const initials2 = words[i + 1].charAt(0) + words[i].charAt(0);
            
            if (initials1.startsWith(filter) || initials2.startsWith(filter)) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Mostra il dropdown con i risultati della ricerca
 */
function showSearchDropdown(foundMilitari, query) {
    hideSearchDropdown();
    
    const searchContainer = document.querySelector('.search-container');
    if (!searchContainer) return;
    
    const dropdown = document.createElement('div');
    dropdown.id = 'ruolini-search-dropdown';
    dropdown.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0 0 12px 12px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        z-index: 1000;
        max-height: 350px;
        overflow-y: auto;
    `;
    
    if (foundMilitari.length === 0) {
        dropdown.innerHTML = `
            <div style="padding: 20px; text-align: center; color: #666;">
                <i class="fas fa-search fa-2x mb-2" style="color: #ccc;"></i>
                <div style="font-weight: 600; margin-bottom: 4px;">Nessun militare trovato</div>
                <div style="font-size: 0.85em;">Nessun risultato per "${query}"</div>
            </div>
        `;
        searchContainer.appendChild(dropdown);
        return;
    }
    
    // Header
    const header = document.createElement('div');
    header.style.cssText = `
        padding: 12px 16px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
        color: #495057;
        font-size: 0.9em;
    `;
    header.textContent = foundMilitari.length === 1 ? '1 militare trovato' : `${foundMilitari.length} militari trovati`;
    dropdown.appendChild(header);
    
    // Risultati
    foundMilitari.slice(0, 10).forEach(militare => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.style.cssText = `
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
        `;
        
        const statusColor = militare.status === 'Presente' ? '#16a34a' : '#dc3545';
        const statusBg = militare.status === 'Presente' ? 'rgba(22, 163, 74, 0.1)' : 'rgba(220, 53, 69, 0.1)';
        const fullName = militare.cognome + ' ' + militare.nome;
        
        item.innerHTML = `
            <div style="width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #0A2342 0%, #1A3A5F 100%); color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-weight: 600; font-size: 0.8em;">
                ${getInitials(fullName)}
            </div>
            <div style="flex: 1;">
                <div style="font-weight: 600; color: #0A2342; margin-bottom: 2px;">${militare.grado} ${militare.cognome} ${militare.nome}</div>
                <div style="font-size: 0.8em; color: #6c757d;">${capitalizeFirst(militare.categoria)} • ${militare.plotone || '-'} • ${militare.ufficio || '-'}</div>
            </div>
            <span style="padding: 4px 10px; border-radius: 12px; font-size: 0.75em; font-weight: 600; background: ${statusBg}; color: ${statusColor};">
                ${militare.status}
            </span>
        `;
        
        // Hover
        item.addEventListener('mouseenter', () => item.style.backgroundColor = '#f8f9fa');
        item.addEventListener('mouseleave', () => item.style.backgroundColor = '');
        
        // Click
        item.addEventListener('click', () => {
            selectMilitareFromSearch(militare);
        });
        
        dropdown.appendChild(item);
    });
    
    searchContainer.appendChild(dropdown);
}

/**
 * Nasconde il dropdown di ricerca
 */
function hideSearchDropdown() {
    const existing = document.getElementById('ruolini-search-dropdown');
    if (existing) existing.remove();
}

/**
 * Seleziona un militare dal dropdown e lo evidenzia
 */
function selectMilitareFromSearch(militare) {
    hideSearchDropdown();
    clearSearchHighlights();
    
    // Trova l'accordion della categoria e aprilo se chiuso
    const accordionId = 'ruolini-' + militare.categoria;
    const accordion = document.getElementById(accordionId);
    
    if (accordion && !accordion.classList.contains('show')) {
        // Apri l'accordion
        const bsCollapse = new bootstrap.Collapse(accordion, { toggle: true });
    }
    
    // Evidenzia il militare dopo un breve delay per permettere l'apertura
    setTimeout(() => {
        highlightMilitare(militare.element);
        
        // Scroll verso il militare
        militare.element.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'center' 
        });
    }, 300);
}

/**
 * Evidenzia temporaneamente un militare
 */
function highlightMilitare(row) {
    row.classList.add('militare-highlighted');
    row.style.cssText += `
        background: linear-gradient(135deg, #faf6f0, #f5f0e6) !important;
        border: 2px solid #d4a574 !important;
        border-radius: 8px !important;
        box-shadow: 0 3px 12px rgba(212, 165, 116, 0.25) !important;
        transform: scale(1.02) !important;
        transition: all 0.3s ease !important;
    `;
    
    // Rimuovi evidenziazione dopo 3 secondi
    setTimeout(() => {
        clearSearchHighlights();
    }, 3000);
}

/**
 * Pulisce tutte le evidenziazioni della ricerca
 */
function clearSearchHighlights() {
    document.querySelectorAll('.militare-highlighted').forEach(row => {
        row.classList.remove('militare-highlighted');
        row.style.background = '';
        row.style.border = '';
        row.style.borderRadius = '';
        row.style.boxShadow = '';
        row.style.transform = '';
        row.style.transition = '';
    });
}

/**
 * Ottiene le iniziali da un nome completo
 */
function getInitials(fullName) {
    const parts = fullName.trim().split(' ');
    if (parts.length >= 2) {
        return parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
    }
    return parts[0] ? parts[0].charAt(0).toUpperCase() : '?';
}

/**
 * Capitalizza la prima lettera di una stringa
 */
function capitalizeFirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
</script>
@endsection
