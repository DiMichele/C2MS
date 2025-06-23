{{--
|--------------------------------------------------------------------------
| Componente Base Filtri 
|--------------------------------------------------------------------------
| Componente riutilizzabile per tutti i filtri dell'applicazione
| Parametri:
| - $formAction: URL dell'azione del form
| - $activeCount: Conteggio filtri attivi
| - $filterActive: Boolean per visualizzazione iniziale
|
| @version 1.0
| @author Michele Di Gennaro
--}}

@php
// Se non definito, inizializza activeCount
$activeCount = $activeCount ?? (request()->except('page') ? count(request()->except('page')) : 0);

// Se non definito, inizializza filterActive
$filterActive = $filterActive ?? ($activeCount > 0);
@endphp

<div class="filter-card mb-4">
    <div class="filter-card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-filter me-2"></i> Filtri avanzati
        </div>
    </div>
    <div class="card-body p-3">
        <form id="filtroForm" action="{{ $formAction ?? '#' }}" method="GET">
            <div class="row">
                {{ $slot }}
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                @if($activeCount > 0)
                <a href="{{ $formAction ?? '#' }}" class="btn btn-danger">
                    <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ $activeCount }})
                </a>
                @endif
            </div>
        </form>
    </div>
</div> 
