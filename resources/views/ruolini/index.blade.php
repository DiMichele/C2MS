@extends('layouts.app')

@section('title', 'Ruolini - C2MS')

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

    <!-- Riepilogo Statistico per Categoria -->
    <div class="row mb-4">
        @foreach(['Ufficiali' => 'danger', 'Sottufficiali' => 'warning', 'Graduati' => 'info', 'Volontari' => 'secondary'] as $categoria => $colore)
            <div class="col-md-3">
                <div class="card border-{{ $colore }} shadow-sm">
                    <div class="card-header bg-{{ $colore }} text-white">
                        <h6 class="mb-0 fw-bold">{{ $categoria }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-center flex-fill">
                                <div class="fs-4 fw-bold text-success">{{ $totali[$categoria]['presenti'] }}</div>
                                <small class="text-muted">Presenti</small>
                            </div>
                            <div class="vr"></div>
                            <div class="text-center flex-fill">
                                <div class="fs-4 fw-bold text-danger">{{ $totali[$categoria]['assenti'] }}</div>
                                <small class="text-muted">Assenti</small>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="text-center">
                            <small class="text-muted">Totale: <strong>{{ $totali[$categoria]['totale'] }}</strong></small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

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
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    margin: 0;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--navy);
}

/* Card styling */
.card {
    border-radius: 8px !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
    font-weight: 600;
}

/* Tabelle */
.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.08) !important;
}

.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.15) !important;
}

/* Badge impegni */
.impegno-badge {
    font-size: 0.7rem;
    padding: 3px 6px;
    font-weight: 600;
    cursor: help;
    border-radius: 4px !important;
    white-space: nowrap;
}

/* Link militari */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
    font-weight: 500;
}

.link-name:hover {
    color: #0a2342;
    text-decoration: none;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}

/* Vertical divider */
.vr {
    width: 1px;
    background-color: rgba(0,0,0,0.1);
    margin: 0 1rem;
    height: 3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .col-md-6 {
        margin-bottom: 1rem;
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
</script>
@endsection
