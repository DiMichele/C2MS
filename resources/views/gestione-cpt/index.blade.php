@extends('layouts.app')

@section('title', 'Codici CPT')

@section('content')
<style>
/* Stili uniformi come le altre pagine */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
}

.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

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
</style>

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['macro_attivita', 'impiego', 'attivo'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
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
    <div class="d-flex gap-2">
        <a href="{{ route('codici-cpt.export') }}" class="btn btn-outline-success" style="border-radius: 6px !important;">
            <i class="fas fa-file-excel me-1"></i>Esporta Excel
        </a>
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
                    {{-- Filtro Categoria --}}
                    <div class="col-md-4">
                        <label for="macro_attivita" class="form-label">
                            <i class="fas fa-folder me-1"></i> Categoria
                        </label>
                        <div class="select-wrapper">
                            <select name="macro_attivita" id="macro_attivita" class="form-select filter-select {{ request()->filled('macro_attivita') ? 'applied' : '' }}">
                                <option value="">Tutte le categorie</option>
                                @foreach($macroAttivita as $macro)
                                    <option value="{{ $macro }}" {{ request('macro_attivita') == $macro ? 'selected' : '' }}>
                                        {{ $macro }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('macro_attivita'))
                                <span class="clear-filter" data-filter="macro_attivita" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Impiego --}}
                    <div class="col-md-4">
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
                    <div class="col-md-4">
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

<!-- Tabella Unica -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
            <tr>
                <th style="width: 120px;">Codice</th>
                <th style="width: 150px;">Categoria</th>
                <th>Descrizione</th>
                <th style="width: 180px;">Tipo Impiego</th>
                <th style="width: 80px;" class="text-center">Stato</th>
                <th style="width: 140px;" class="text-center">Azioni</th>
            </tr>
        </thead>
        <tbody id="codiciTableBody">
            @forelse($codici as $codice)
                <tr class="{{ !$codice->attivo ? 'table-secondary opacity-50' : '' }}" data-searchable>
                    <td>
                        <span class="codice-badge" 
                              style="background-color: {{ $codice->colore_badge }}; 
                                     color: {{ in_array($codice->colore_badge, ['#ffff00', '#ffc000']) ? '#000' : '#fff' }};">
                            {{ $codice->codice }}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $codice->macro_attivita ?: '-' }}</span>
                    </td>
                    <td>{{ $codice->attivita_specifica }}</td>
                    <td>
                        <small class="text-muted">
                            {{ str_replace('_', ' ', ucfirst(strtolower($codice->impiego))) }}
                        </small>
                    </td>
                    <td class="text-center">
                        @if($codice->attivo)
                            <span class="badge bg-success">Attivo</span>
                        @else
                            <span class="badge bg-secondary">Inattivo</span>
                        @endif
                    </td>
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
                    <td colspan="6" class="text-center py-5">
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
