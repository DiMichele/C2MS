@extends('layouts.app')

@section('title', 'Ruolini - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="text-center mb-3">
        <h1 class="page-title">Ruolini</h1>
    </div>

    <!-- Selettore Data con Navigazione -->
    <div class="d-flex justify-content-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <!-- Giorno Precedente -->
            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->subDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
               class="btn btn-outline-primary btn-sm" 
               style="border-radius: 6px !important;">
                <i class="fas fa-chevron-left"></i>
            </a>
            
            <!-- Selettore Data -->
            <input type="date" 
                   id="dataSelect" 
                   class="form-control form-control-sm text-center" 
                   value="{{ $dataSelezionata }}" 
                   style="width: 160px; border-radius: 6px !important; font-weight: 600;"
                   onchange="cambiaData()">
            
            <!-- Giorno Successivo -->
            <a href="{{ route('ruolini.index', array_filter(['data' => $dataObj->copy()->addDay()->format('Y-m-d'), 'compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
               class="btn btn-outline-primary btn-sm" 
               style="border-radius: 6px !important;">
                <i class="fas fa-chevron-right"></i>
            </a>
            
            <!-- Oggi -->
            @if(!$dataObj->isToday())
                <a href="{{ route('ruolini.index', array_filter(['compagnia_id' => $compagniaId, 'plotone_id' => $plotoneId])) }}" 
                   class="btn btn-primary btn-sm ms-2" 
                   style="border-radius: 6px !important;">
                    <i class="fas fa-calendar-day me-1"></i>Oggi
                </a>
            @endif
        </div>
    </div>

    <!-- Data Visualizzata -->
    <div class="text-center mb-4">
        <h5 class="text-muted">
            {{ ucfirst($dataObj->locale('it')->isoFormat('dddd D MMMM YYYY')) }}
        </h5>
    </div>
    
    <!-- Pulsante Export Excel Floating -->
    <button onclick="exportRuoliniExcel()" 
            class="btn btn-success floating-export-btn" 
            title="Esporta Excel"
            style="position: fixed; bottom: 30px; right: 30px; z-index: 999; border-radius: 50px !important; padding: 15px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
        <i class="fas fa-file-excel me-2"></i>Esporta Excel
    </button>

    <!-- Filtri Compagnia/Plotone -->
    <div class="d-flex justify-content-center mb-4">
        <div class="d-flex gap-3 align-items-center">
            <!-- Filtro Compagnia -->
            <div>
                <label for="compagniaSelect" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 500;">Compagnia</label>
                <select name="compagnia_id" id="compagniaSelect" class="form-select form-select-sm" style="width: 200px; border-radius: 6px !important;" onchange="applicaFiltri()">
                    <option value="">Tutte le compagnie</option>
                    @foreach($compagnie as $compagnia)
                        <option value="{{ $compagnia->id }}" {{ $compagniaId == $compagnia->id ? 'selected' : '' }}>
                            {{ $compagnia->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filtro Plotone -->
            <div>
                <label for="plotoneSelect" class="form-label mb-1" style="font-size: 0.9rem; font-weight: 500;">Plotone</label>
                <select name="plotone_id" id="plotoneSelect" class="form-select form-select-sm" style="width: 200px; border-radius: 6px !important;" onchange="applicaFiltri()">
                    <option value="">Tutti i plotoni</option>
                    @foreach($plotoni as $plotone)
                        <option value="{{ $plotone->id }}" {{ $plotoneId == $plotone->id ? 'selected' : '' }}>
                            {{ $plotone->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Pulsante Reset -->
            @if($compagniaId || $plotoneId)
                <div style="margin-top: 24px;">
                    <a href="{{ route('ruolini.index', ['data' => $dataSelezionata]) }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                        <i class="fas fa-times me-1"></i> Reset
                    </a>
                </div>
            @endif
        </div>
    </div>

    @php
        $forzaEffettiva = $totali['Ufficiali']['totale'] + $totali['Sottufficiali']['totale'] + $totali['Graduati']['totale'] + $totali['Volontari']['totale'];
        $totalePresenti = $totali['Ufficiali']['presenti'] + $totali['Sottufficiali']['presenti'] + $totali['Graduati']['presenti'] + $totali['Volontari']['presenti'];
        $totaleAssenti = $totali['Ufficiali']['assenti'] + $totali['Sottufficiali']['assenti'] + $totali['Graduati']['assenti'] + $totali['Volontari']['assenti'];
    @endphp

    <!-- Riepilogo Forza Effettiva Totale -->
    <div class="forza-effettiva-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-4 text-center">
                <div class="forza-stat forza-totale">
                    <div class="forza-number">{{ $forzaEffettiva }}</div>
                    <div class="forza-label">FORZA EFFETTIVA</div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="forza-stat forza-presenti">
                    <div class="forza-number">{{ $totalePresenti }}</div>
                    <div class="forza-label">PRESENTI</div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="forza-stat forza-assenti">
                    <div class="forza-number">{{ $totaleAssenti }}</div>
                    <div class="forza-label">ASSENTI</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Riepilogo Statistico per Categoria -->
    <div class="row mb-5">
        @foreach(['Ufficiali' => ['color' => '#dc3545', 'icon' => 'fa-star'], 'Sottufficiali' => ['color' => '#ffc107', 'icon' => 'fa-shield-alt'], 'Graduati' => ['color' => '#0dcaf0', 'icon' => 'fa-medal'], 'Volontari' => ['color' => '#6c757d', 'icon' => 'fa-users']] as $categoria => $config)
            @if($totali[$categoria]['totale'] > 0)
            <div class="col-md-3">
                <div class="ruolino-card" style="border-top: 4px solid {{ $config['color'] }};">
                    <div class="ruolino-header">
                        <i class="fas {{ $config['icon'] }}" style="color: {{ $config['color'] }};"></i>
                        <h6 class="ruolino-title">{{ $categoria }}</h6>
                    </div>
                    <div class="ruolino-body">
                        <div class="ruolino-stats">
                            <div class="stat-item">
                                <div class="stat-number text-success">{{ $totali[$categoria]['presenti'] }}</div>
                                <div class="stat-label">Presenti</div>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <div class="stat-number text-danger">{{ $totali[$categoria]['assenti'] }}</div>
                                <div class="stat-label">Assenti</div>
                            </div>
                        </div>
                        <div class="ruolino-total">
                            Totale: <strong>{{ $totali[$categoria]['totale'] }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    </div>
    
    <style>
    /* Forza Effettiva Card */
    .forza-effettiva-card {
        background: linear-gradient(135deg, #0a2342 0%, #1a4a7a 100%);
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 24px rgba(10, 35, 66, 0.25);
    }
    
    .forza-stat {
        padding: 1rem;
    }
    
    .forza-number {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: 0.5rem;
    }
    
    .forza-label {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .forza-totale .forza-number {
        color: #ffffff;
    }
    
    .forza-totale .forza-label {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .forza-presenti .forza-number {
        color: #4ade80;
    }
    
    .forza-presenti .forza-label {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .forza-assenti .forza-number {
        color: #f87171;
    }
    
    .forza-assenti .forza-label {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .ruolino-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .ruolino-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .ruolino-header {
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        background: #f8f9fa;
    }
    
    .ruolino-header i {
        font-size: 1.25rem;
    }
    
    .ruolino-title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #212529;
    }
    
    .ruolino-body {
        padding: 1.25rem;
    }
    
    .ruolino-stats {
        display: flex;
        justify-content: space-around;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .stat-item {
        text-align: center;
        flex: 1;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 500;
    }
    
    .stat-divider {
        width: 1px;
        height: 40px;
        background: #dee2e6;
    }
    
    .ruolino-total {
        text-align: center;
        padding-top: 1rem;
        border-top: 1px solid #dee2e6;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .ruolino-total strong {
        color: #212529;
        font-size: 1.1rem;
    }
    </style>

    <!-- Sezioni per Categoria -->
    @foreach(['Ufficiali' => 'danger', 'Sottufficiali' => 'warning', 'Graduati' => 'info', 'Volontari' => 'secondary'] as $categoria => $colore)
        @if($totali[$categoria]['totale'] > 0)
            <div class="mb-5">
                <div class="d-flex align-items-center mb-3">
                    <h3 class="section-title mb-0">
                        <i class="fas fa-star me-2 text-{{ $colore }}"></i>
                        {{ $categoria }}
                    </h3>
                </div>

                <div class="row">
                    <!-- Colonna Presenti -->
                    <div class="col-md-6">
                        <div class="card border-success shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Presenti
                                    <span class="badge bg-light text-success float-end">{{ $totali[$categoria]['presenti'] }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                @if(count($categorie[$categoria]['presenti']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;" class="text-center">#</th>
                                                    <th style="width: 100px;">GRADO</th>
                                                    <th>COGNOME</th>
                                                    <th>NOME</th>
                                                    <th style="width: 120px;">PLOTONE</th>
                                                    <th style="width: 150px;">TELEFONO</th>
                                                    <th style="width: 200px;">ISTITUTI</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($categorie[$categoria]['presenti'] as $index => $item)
                                                    <tr>
                                                        <td class="text-center">{{ $index + 1 }}</td>
                                                        <td>
                                                            <span class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                                                                {{ $item['militare']->cognome }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $item['militare']->nome }}</td>
                                                        <td><small>{{ $item['militare']->plotone->nome ?? '-' }}</small></td>
                                                        <td><small>{{ $item['militare']->telefono ?? '-' }}</small></td>
                                                        <td>
                                                            @php
                                                                $istituti = $item['militare']->istituti ?? [];
                                                            @endphp
                                                            @if(!empty($istituti))
                                                                <small>
                                                                    @foreach($istituti as $istituto)
                                                                        <span class="badge bg-info text-dark" style="font-size: 0.7rem; margin-right: 2px;">{{ $istituto }}</span>
                                                                    @endforeach
                                                                </small>
                                                            @else
                                                                <small class="text-muted">-</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>Nessun presente
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Colonna Assenti -->
                    <div class="col-md-6">
                        <div class="card border-danger shadow-sm">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Assenti
                                    <span class="badge bg-light text-danger float-end">{{ $totali[$categoria]['assenti'] }}</span>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                @if(count($categorie[$categoria]['assenti']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 50px;" class="text-center">#</th>
                                                    <th style="width: 100px;">GRADO</th>
                                                    <th>COGNOME</th>
                                                    <th>NOME</th>
                                                    <th style="width: 150px;">TELEFONO</th>
                                                    <th style="width: 200px;">ISTITUTI</th>
                                                    <th style="width: 200px;">MOTIVAZIONE</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($categorie[$categoria]['assenti'] as $index => $item)
                                                    <tr>
                                                        <td class="text-center">{{ $index + 1 }}</td>
                                                        <td>
                                                            <span class="fw-bold">{{ $item['militare']->grado->sigla ?? '' }}</span>
                                                        </td>
                                                        <td>
                                                            <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                                                                {{ $item['militare']->cognome }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $item['militare']->nome }}</td>
                                                        <td><small>{{ $item['militare']->telefono ?? '-' }}</small></td>
                                                        <td>
                                                            @php
                                                                $istituti = $item['militare']->istituti ?? [];
                                                            @endphp
                                                            @if(!empty($istituti))
                                                                <small>
                                                                    @foreach($istituti as $istituto)
                                                                        <span class="badge bg-info text-dark" style="font-size: 0.7rem; margin-right: 2px;">{{ $istituto }}</span>
                                                                    @endforeach
                                                                </small>
                                                            @else
                                                                <small class="text-muted">-</small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <div class="d-flex flex-wrap gap-1">
                                                                @foreach($item['impegni'] as $impegno)
                                                                    <span class="badge impegno-badge" 
                                                                          style="background-color: {{ $impegno['colore'] }};"
                                                                          data-bs-toggle="tooltip" 
                                                                          data-bs-placement="top" 
                                                                          title="{{ $impegno['descrizione'] }}">
                                                                        {{ $impegno['codice'] }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="p-3 text-center text-muted">
                                        <i class="fas fa-info-circle me-2"></i>Nessun assente
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>

<style>
.page-title {
    font-size: 2.5rem;
    font-weight: 800;
    color: #0a2342;
    margin: 0;
    letter-spacing: -0.5px;
    text-transform: uppercase;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0a2342;
    letter-spacing: 0.5px;
}

/* Card styling migliorato */
.card {
    border-radius: 12px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none !important;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.12) !important;
}

.card-header {
    border-radius: 0 !important;
    font-weight: 600;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card.border-success {
    box-shadow: 0 4px 16px rgba(40, 167, 69, 0.15);
}

.card.border-danger {
    box-shadow: 0 4px 16px rgba(220, 53, 69, 0.15);
}

/* Header gradient effect */
.card-header.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.card-header.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #e57373 100%) !important;
}

/* Tabelle migliorate */
.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #495057;
    padding: 0.875rem 0.75rem;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.05) !important;
}

.table tbody td {
    vertical-align: middle;
    padding: 0.75rem;
    border-color: #f0f0f0;
}

/* Badge impegni migliorato */
.impegno-badge {
    font-size: 0.7rem;
    padding: 4px 8px;
    font-weight: 600;
    cursor: help;
    border-radius: 6px !important;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.impegno-badge:hover {
    transform: scale(1.05);
}

/* Link militari migliorato */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
    font-weight: 600;
    transition: color 0.2s ease;
}

.link-name:hover {
    color: #d4af37;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background: linear-gradient(90deg, #d4af37, #f5d77f);
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}

/* Date selector migliorato */
#dataSelect {
    border: 2px solid #0a2342;
    font-weight: 700;
    transition: all 0.2s ease;
}

#dataSelect:focus {
    border-color: #d4af37;
    box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.25);
}

/* Filtri compagnia/plotone */
.form-select {
    border: 2px solid #e9ecef;
    transition: all 0.2s ease;
}

.form-select:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.15);
}

/* Floating export button migliorato */
.floating-export-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
    border: none !important;
    font-weight: 600;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.floating-export-btn:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4) !important;
}

/* Vertical divider */
.vr {
    width: 1px;
    background-color: rgba(0,0,0,0.1);
    margin: 0 1rem;
    height: 3rem;
}

/* Badge istituti */
.badge.bg-info {
    background: linear-gradient(135deg, #17a2b8 0%, #6dd5ed 100%) !important;
    font-weight: 600;
    padding: 3px 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1.5rem;
    }
    
    .page-title {
        font-size: 1.75rem;
    }
    
    .floating-export-btn {
        padding: 12px 16px !important;
        font-size: 0.85rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip di Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Funzione per cambiare data
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
    if (queryString) {
        url += '?' + queryString;
    }
    
    window.location.href = url;
}

// Funzione per applicare i filtri
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
    if (queryString) {
        url += '?' + queryString;
    }
    
    window.location.href = url;
}

// Funzione per esportare in Excel
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
