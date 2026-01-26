@extends('layouts.app')

@section('title', 'Codici CPT')

@section('content')
<style>
/* Stili specifici per questa pagina */
/* (Stili base tabelle in table-standard.css) */

.form-control, .form-select {
    border-radius: 0 !important;
}

.filter-select {
    border-radius: 0 !important;
}

/* Badge con colori CPT esatti */
.codice-badge {
    display: inline-block;
    padding: 6px 12px;
    font-weight: 700;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 60px;
    text-align: center;
    font-family: 'Courier New', monospace;
}

/* 
 * Colonne tabella CPT
 * Le larghezze sono flessibili per non tagliare il testo
 * L'ordine delle colonne è sincronizzato con COLONNE_TABELLA nel controller
 */
.sugeco-table th,
.sugeco-table td {
    white-space: normal !important;
    word-wrap: break-word;
}

.sugeco-table th:nth-child(1),
.sugeco-table td:nth-child(1) {
    /* Colonna Codice */
    width: 100px;
    min-width: 80px;
    text-align: center;
}

.sugeco-table th:nth-child(2),
.sugeco-table td:nth-child(2) {
    /* Colonna Descrizione - si espande per contenere tutto il testo */
    width: auto;
    min-width: 250px;
    text-align: left !important;
}

.sugeco-table th:nth-child(3),
.sugeco-table td:nth-child(3) {
    /* Colonna Tipo Impiego */
    width: 180px;
    min-width: 150px;
}

.sugeco-table th:nth-child(4),
.sugeco-table td:nth-child(4) {
    /* Colonna Stato */
    width: 100px;
    min-width: 80px;
}

.sugeco-table th:nth-child(5),
.sugeco-table td:nth-child(5) {
    /* Colonna Azioni */
    width: 120px;
    min-width: 110px;
}
</style>

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['impiego', 'attivo'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
    
    // SINGLE SOURCE OF TRUTH: Colonne tabella dal controller
    // Se modifichi le colonne nel controller, si aggiornano automaticamente qui e nell'export Excel
    $colonneTabella = \App\Http\Controllers\GestioneCptController::getColonneTabella();
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">CODICI CPT</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchCodice" 
            class="form-control" 
            data-search-type="codice"
            data-target-container="codiciTableBody"
            placeholder="Cerca codice..." 
            aria-label="Cerca codice" 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e azioni su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
            <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
            <span id="toggleFiltersText">
                {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
            </span>
        </button>
    </div>
    
    <div>
        <a href="{{ route('codici-cpt.create') }}" class="btn btn-success" style="border-radius: 6px !important;">
            <i class="fas fa-plus me-1"></i>Nuovo Codice
        </a>
    </div>
</div>

<!-- Messaggi -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Filtri con sezione migliorata -->
<div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-filter me-2"></i> Filtri avanzati
            </div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('codici-cpt.index') }}" method="GET">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro Impiego --}}
                    <div class="col-md-6">
                        <label for="impiego" class="form-label">
                            <i class="fas fa-tasks me-1"></i> Tipo Impiego
                        </label>
                        <div class="select-wrapper">
                            <select name="impiego" id="impiego" class="form-select filter-select {{ request()->filled('impiego') ? 'applied' : '' }}">
                                <option value="">Tutti i tipi</option>
                                @foreach($impieghi as $impiego)
                                    <option value="{{ $impiego }}" {{ request('impiego') == $impiego ? 'selected' : '' }}>
                                        {{ str_replace('_', ' ', ucfirst(strtolower($impiego))) }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('impiego'))
                                <span class="clear-filter" data-filter="impiego" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Stato --}}
                    <div class="col-md-6">
                        <label for="attivo" class="form-label">
                            <i class="fas fa-toggle-on me-1"></i> Stato
                        </label>
                        <div class="select-wrapper">
                            <select name="attivo" id="attivo" class="form-select filter-select {{ request()->filled('attivo') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="1" {{ request('attivo') === '1' ? 'selected' : '' }}>Solo Attivi</option>
                                <option value="0" {{ request('attivo') === '0' ? 'selected' : '' }}>Solo Inattivi</option>
                            </select>
                            @if(request()->filled('attivo'))
                                <span class="clear-filter" data-filter="attivo" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Pulsanti reset --}}
                <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top">
                    <a href="{{ route('codici-cpt.index') }}" class="btn btn-outline-secondary" id="resetAllFilters" style="border-radius: 6px !important;">
                        <i class="fas fa-redo me-1"></i> Reset filtri
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- 
    TABELLA CODICI CPT
    Le colonne sono definite in GestioneCptController::COLONNE_TABELLA
    per mantenere sincronizzazione automatica con l'export Excel
--}}
<div class="table-responsive">
    <table class="sugeco-table">
        <thead>
            <tr>
                {{-- Colonne da SINGLE SOURCE OF TRUTH --}}
                @foreach($colonneTabella as $key => $config)
                    <th>{{ $config['header'] }}</th>
                @endforeach
                {{-- Colonna Azioni (solo vista, non in export) --}}
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody id="codiciTableBody">
            @forelse($codici as $codice)
                <tr data-searchable>
                    {{-- Colonne da SINGLE SOURCE OF TRUTH --}}
                    @foreach($colonneTabella as $key => $config)
                        @switch($config['tipo'])
                            @case('badge_colorato')
                                <td>
                                    @php
                                        // Calcola contrasto testo automatico
                                        $hex = ltrim($codice->colore_badge, '#');
                                        $r = hexdec(substr($hex, 0, 2));
                                        $g = hexdec(substr($hex, 2, 2));
                                        $b = hexdec(substr($hex, 4, 2));
                                        $luminosita = ($r * 299 + $g * 587 + $b * 114) / 1000;
                                        $testoColore = $luminosita > 128 ? '#000' : '#fff';
                                    @endphp
                                    <span class="codice-badge" 
                                          style="background-color: {{ $codice->colore_badge }}; color: {{ $testoColore }};">
                                        {{ $codice->{$config['campo']} }}
                                    </span>
                                </td>
                                @break
                            
                            @case('impiego')
                                <td>
                                    <small class="text-muted">
                                        {{ str_replace('_', ' ', ucfirst(strtolower($codice->{$config['campo']}))) }}
                                    </small>
                                </td>
                                @break
                            
                            @case('stato')
                                <td class="text-center">
                                    @if($codice->{$config['campo']})
                                        <span class="badge bg-success">Attivo</span>
                                    @else
                                        <span class="badge bg-secondary">Inattivo</span>
                                    @endif
                                </td>
                                @break
                            
                            @default
                                <td>{{ $codice->{$config['campo']} }}</td>
                        @endswitch
                    @endforeach
                    
                    {{-- Colonna Azioni (solo vista) --}}
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('codici-cpt.edit', $codice) }}" 
                               class="btn btn-outline-primary"
                               data-bs-toggle="tooltip" 
                               title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('codici-cpt.toggle', $codice) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn btn-outline-{{ $codice->attivo ? 'warning' : 'success' }}"
                                        data-bs-toggle="tooltip" 
                                        title="{{ $codice->attivo ? 'Disattiva' : 'Attiva' }}">
                                    <i class="fas fa-{{ $codice->attivo ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" 
                                    class="btn btn-outline-danger"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteModal{{ $codice->id }}"
                                    title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <!-- Modal Elimina -->
                        <div class="modal fade" id="deleteModal{{ $codice->id }}" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <h5 class="modal-title">Conferma Eliminazione</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="mb-2">Sei sicuro di voler eliminare il codice <strong>{{ $codice->codice }}</strong>?</p>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Questa azione non può essere annullata.
                                        </p>
                                    </div>
                                    <div class="modal-footer border-0">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                                        <form action="{{ route('codici-cpt.destroy', $codice) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger">Elimina</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- colspan dinamico basato sul numero di colonne + 1 (azioni) --}}
                    <td colspan="{{ count($colonneTabella) + 1 }}" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nessun codice trovato</h5>
                        <p class="text-muted mb-3">Inizia creando il tuo primo codice CPT</p>
                        <a href="{{ route('codici-cpt.create') }}" class="btn btn-success">
                            <i class="fas fa-plus"></i> Crea Codice
                        </a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Floating Button Export Excel -->
<a href="{{ route('codici-cpt.export') }}" class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza il sistema di filtri SUGECO (auto-submit)
    if (typeof SUGECO !== 'undefined' && typeof SUGECO.Filters !== 'undefined') {
        SUGECO.Filters.init();
    }
    
    // Inizializza la ricerca
    if (typeof SUGECO !== 'undefined' && typeof SUGECO.Search !== 'undefined') {
        SUGECO.Search.init();
    }
    
    // Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
