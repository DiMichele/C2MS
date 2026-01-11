@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid" style="position: relative; z-index: 1;">
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
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-center align-items-center flex-wrap gap-3">
                <!-- Navigazione data -->
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                       class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    
                    <input type="date" id="dataSelect" class="form-control form-control-sm" 
                           value="{{ $dataSelezionata }}" onchange="cambiaData()"
                           style="width: 150px; border-radius: 6px !important;">
                    
                    <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                       class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    
                    @if(!$dataObj->isToday())
                        <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                           class="btn btn-primary btn-sm" style="border-radius: 6px !important;">
                            Oggi
                        </a>
                    @endif
                </div>

                <span class="text-muted">|</span>

                <!-- Filtri -->
                <select id="compagniaSelect" class="form-select form-select-sm" onchange="applicaFiltri()" style="width: 180px; border-radius: 6px !important;">
                    <option value="">Tutte le compagnie</option>
                    @foreach($compagnie as $compagnia)
                        <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                            {{ $compagnia->nome }}
                        </option>
                    @endforeach
                </select>
                
                <select id="plotoneSelect" class="form-select form-select-sm" onchange="applicaFiltri()" style="width: 180px; border-radius: 6px !important;">
                    <option value="">Tutti i plotoni</option>
                    @foreach($plotoni as $plotone)
                        <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                            {{ $plotone->nome }}
                        </option>
                    @endforeach
                </select>
                
                @if($compagniaId || $plotoneId)
                    <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}" 
                       class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                        <i class="fas fa-times me-1"></i>Reset
                    </a>
                @endif

                <span class="text-muted">|</span>

                <button onclick="exportRuoliniExcel()" class="btn btn-success btn-sm" style="border-radius: 6px !important;">
                    <i class="fas fa-file-excel me-2"></i>Esporta Excel
                </button>
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
            <div class="card" style="border-radius: 8px;">
                <div class="card-body p-0">
                    <div class="d-flex">
                        <!-- Forza Effettiva -->
                        <div class="flex-fill text-center py-4 px-3" style="border-right: 1px solid var(--border-color);">
                            <div class="text-muted small text-uppercase mb-1">Forza Effettiva</div>
                            <div style="font-size: 2.5rem; font-weight: 700; color: var(--navy);">{{ $forzaEffettiva }}</div>
                        </div>
                        <!-- Presenti -->
                        <div class="flex-fill text-center py-4 px-3" style="border-right: 1px solid var(--border-color); background: rgba(52, 103, 81, 0.05);">
                            <div class="text-muted small text-uppercase mb-1">Presenti</div>
                            <div style="font-size: 2.5rem; font-weight: 700; color: var(--success);">{{ $totalePresenti }}</div>
                            <div class="small text-muted">{{ $percentualePresenti }}%</div>
                        </div>
                        <!-- Assenti -->
                        <div class="flex-fill text-center py-4 px-3" style="background: rgba(220, 53, 69, 0.05);">
                            <div class="text-muted small text-uppercase mb-1">Assenti</div>
                            <div style="font-size: 2.5rem; font-weight: 700; color: var(--danger);">{{ $totaleAssenti }}</div>
                            <div class="small text-muted">{{ 100 - $percentualePresenti }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Breakdown categorie -->
        <div class="col-lg-4">
            <div class="card h-100" style="border-radius: 8px;">
                <div class="card-header py-2" style="background: var(--navy); border-radius: 8px 8px 0 0 !important;">
                    <h6 class="mb-0 text-white small">Riepilogo per Categoria</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr style="background: var(--gray-100);">
                                <th class="ps-3" style="font-size: 0.7rem;">Categoria</th>
                                <th class="text-center" style="font-size: 0.7rem; width: 60px;">Tot</th>
                                <th class="text-center" style="font-size: 0.7rem; width: 60px;">Pres</th>
                                <th class="text-center" style="font-size: 0.7rem; width: 60px;">Ass</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(['Ufficiali', 'Sottufficiali', 'Graduati', 'Volontari'] as $cat)
                                @if($totali[$cat]['totale'] > 0)
                                <tr>
                                    <td class="ps-3" style="font-size: 0.85rem;">{{ $cat }}</td>
                                    <td class="text-center fw-bold" style="font-size: 0.85rem;">{{ $totali[$cat]['totale'] }}</td>
                                    <td class="text-center fw-bold" style="font-size: 0.85rem; color: var(--success);">{{ $totali[$cat]['presenti'] }}</td>
                                    <td class="text-center fw-bold" style="font-size: 0.85rem; color: var(--danger);">{{ $totali[$cat]['assenti'] }}</td>
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
        <div class="card mb-4" style="border-radius: 8px;">
            <div class="card-header py-3 d-flex justify-content-between align-items-center" style="background: var(--navy); border-radius: 8px 8px 0 0 !important;">
                <h5 class="mb-0 text-white" style="font-family: 'Oswald', sans-serif; font-size: 1.1rem;">
                    {{ $categoria }}
                </h5>
                <div class="d-flex gap-3">
                    <span class="badge" style="background: var(--success); font-size: 0.8rem;">
                        {{ $totali[$categoria]['presenti'] }} presenti
                    </span>
                    <span class="badge" style="background: var(--danger); font-size: 0.8rem;">
                        {{ $totali[$categoria]['assenti'] }} assenti
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <!-- Colonna Presenti -->
                    <div class="col-md-6" style="border-right: 1px solid var(--border-color);">
                        <div class="py-2 px-3" style="background: rgba(52, 103, 81, 0.1); border-bottom: 1px solid var(--border-color);">
                            <small class="fw-bold" style="color: var(--success); text-transform: uppercase; letter-spacing: 1px;">Presenti</small>
                        </div>
                        @if(count($categorie[$categoria]['presenti']) > 0)
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                                    <tr>
                                        <th style="width: 40px; font-size: 0.7rem;" class="text-center">#</th>
                                        <th style="width: 70px; font-size: 0.7rem;">Grado</th>
                                        <th style="font-size: 0.7rem;">Cognome Nome</th>
                                        <th style="width: 100px; font-size: 0.7rem;">Plotone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categorie[$categoria]['presenti'] as $i => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-bold" style="font-size: 0.85rem;">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                        <td>
                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                               class="text-decoration-none" style="color: var(--navy);">
                                                {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                            </a>
                                        </td>
                                        <td class="text-muted" style="font-size: 0.8rem;">{{ $item['militare']->plotone->nome ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center text-muted py-4">Nessun presente</div>
                        @endif
                    </div>
                    
                    <!-- Colonna Assenti -->
                    <div class="col-md-6">
                        <div class="py-2 px-3" style="background: rgba(220, 53, 69, 0.1); border-bottom: 1px solid var(--border-color);">
                            <small class="fw-bold" style="color: var(--danger); text-transform: uppercase; letter-spacing: 1px;">Assenti</small>
                        </div>
                        @if(count($categorie[$categoria]['assenti']) > 0)
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead style="position: sticky; top: 0; background: white; z-index: 1;">
                                    <tr>
                                        <th style="width: 40px; font-size: 0.7rem;" class="text-center">#</th>
                                        <th style="width: 70px; font-size: 0.7rem;">Grado</th>
                                        <th style="font-size: 0.7rem;">Cognome Nome</th>
                                        <th style="width: 130px; font-size: 0.7rem;">Motivazione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categorie[$categoria]['assenti'] as $i => $item)
                                    <tr>
                                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                                        <td class="fw-bold" style="font-size: 0.85rem;">{{ $item['militare']->grado->sigla ?? '' }}</td>
                                        <td>
                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" 
                                               class="text-decoration-none" style="color: var(--navy);">
                                                {{ $item['militare']->cognome }} {{ $item['militare']->nome }}
                                            </a>
                                        </td>
                                        <td>
                                            @foreach($item['impegni'] as $impegno)
                                            <span class="badge" style="background: {{ $impegno['colore'] }}; font-size: 0.7rem;" 
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
                        <div class="text-center text-muted py-4">Nessun assente</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>

<style>
/* Stili specifici per ruolini che usano le variabili globali */
.table th {
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--gray-600);
    border-bottom: 1px solid var(--border-color) !important;
    padding: 10px 12px;
}

.table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--gray-200) !important;
}

.table-hover tbody tr:hover {
    background-color: var(--gray-100) !important;
}

.table-hover tbody tr:hover td a {
    color: var(--gold) !important;
}

/* Link hover */
.card-body a:hover {
    text-decoration: underline !important;
}

/* Scrollbar */
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

/* Badge motivazione */
.badge[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Responsive */
@media (max-width: 768px) {
    .col-md-6[style*="border-right"] {
        border-right: none !important;
        border-bottom: 1px solid var(--border-color);
    }
    
    .d-flex.flex-fill {
        flex-direction: column;
    }
    
    .d-flex.flex-fill > div[style*="border-right"] {
        border-right: none !important;
        border-bottom: 1px solid var(--border-color);
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
