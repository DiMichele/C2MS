{{--
|--------------------------------------------------------------------------
| Pagina per la gestione dei corsi lavoratori
|--------------------------------------------------------------------------
--}}

@extends('layouts.app')

@section('title', 'Corsi Lavoratori')

@section('content')
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['ruolo', 'certificati_registrati', 'stato_file', 'valido', 'in_scadenza', 'scaduti', 'cert_4h', 'cert_8h', 'cert_preposti', 'cert_dirigenti'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<div class="container-fluid py-4">
    <!-- Header Minimal Solo Titolo -->
    <div class="text-center mb-4">
        <h1 class="page-title">Corsi Lavoratori</h1>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Gestione filtri migliorata -->
        <div>
            <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}">
                <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
                <span id="toggleFiltersText">
                    {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
                </span>
            </button>
        </div>
        
        <!-- Campo di ricerca -->
        <div class="search-container" style="position: relative; width: 320px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
            <input type="text" 
                   id="searchMilitare" 
                   class="form-control" 
                   placeholder="Cerca militare..." 
                   aria-label="Cerca militare" 
                   style="padding-left: 40px; border-radius: 20px;"
                   data-search-type="militare"
                   data-target-container="certificatiTableBody">
        </div>
        
        <div>
            <span class="badge bg-primary">{{ isset($militari) ? $militari->count() : 0 }} militari</span>
        </div>
    </div>
                    
    <!-- Filtri con sezione migliorata -->
    <div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
        @include('components.filters.filter-corsi')
    </div>

    <div class="row mt-3">
        <div class="col">
            @include('components.tables.table-corsi-lavoratori')

            @if(isset($militari) && $militari->count() > 0)
                <div class="pagination-container">
                    {{ $militari->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // Inizializza i tooltip dei certificati
        if (typeof initializeCertificateTooltips === 'function') {
            initializeCertificateTooltips();
        }
        
        // Inizializza il modulo di autosave per le note
        if (typeof C2MS !== 'undefined' && typeof C2MS.Autosave !== 'undefined') {
            C2MS.Autosave.init();
        }
    });
</script>
@endpush
