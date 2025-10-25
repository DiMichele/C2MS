@extends('layouts.app')
@section('title', 'Gestione Eventi - C2MS')

@section('content')
<style>
/* Effetto hover sulle righe */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

/* Sfondo leggermente off-white per la tabella */
.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

/* Bordi leggermente più scuri dell'hover */
.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Stili per i link */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
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
</style>
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['tipologia', 'data_inizio', 'data_fine', 'localita'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Eventi</h1>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Gestione filtri migliorata -->
    <div>
        <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}">
            <i class="fas fa-filter me-2"></i> 
            <span id="toggleFiltersText">
                {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
            </span>
        </button>
    </div>
    
    <div class="search-container" style="position: relative; width: 320px; z-index: 1000;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
        <input type="text" id="searchEvento" class="form-control" placeholder="Cerca evento..." aria-label="Cerca evento" style="padding-left: 40px; border-radius: 20px;">
        <div id="searchSuggestions" class="d-none" style="position: absolute; top: calc(100% + 5px); left: 0; right: 0; background: white; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.15); z-index: 1050; max-height: 350px; overflow-y: auto; border: 1px solid rgba(0,0,0,0.1);"></div>
    </div>
    
    <div>
        <span class="badge bg-primary">{{ $eventi->count() }} eventi</span>
    </div>
</div>

<!-- Filtri con sezione migliorata -->
<div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-filter me-2"></i> Filtri avanzati
            </div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('eventi.index') }}" method="GET">
                <div class="row">
                    {{-- Filtro Tipologia --}}
                    <div class="col-md-3">
                        <label for="tipologia" class="form-label">
                            <i class="fas fa-tag me-1"></i> Tipologia
                        </label>
                        <div class="select-wrapper">
                            <select name="tipologia" id="tipologia" class="form-select filter-select {{ request()->filled('tipologia') ? 'applied' : '' }}">
                                <option value="">Tutte le tipologie</option>
                                <option value="Corso" {{ request('tipologia') == 'Corso' ? 'selected' : '' }}>Corso</option>
                                <option value="Missione" {{ request('tipologia') == 'Missione' ? 'selected' : '' }}>Missione</option>
                                <option value="Esercitazione" {{ request('tipologia') == 'Esercitazione' ? 'selected' : '' }}>Esercitazione</option>
                            </select>
                            @if(request()->filled('tipologia'))
                                <span class="clear-filter" data-filter="tipologia" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Data Inizio --}}
                    <div class="col-md-3">
                        <label for="data_inizio" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i> Data Inizio
                        </label>
                        <div class="select-wrapper">
                            <input type="date" name="data_inizio" id="data_inizio" class="form-control filter-select {{ request()->filled('data_inizio') ? 'applied' : '' }}" value="{{ request('data_inizio') }}">
                            @if(request()->filled('data_inizio'))
                                <span class="clear-filter" data-filter="data_inizio" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Data Fine --}}
                    <div class="col-md-3">
                        <label for="data_fine" class="form-label">
                            <i class="fas fa-calendar-alt me-1"></i> Data Fine
                        </label>
                        <div class="select-wrapper">
                            <input type="date" name="data_fine" id="data_fine" class="form-control filter-select {{ request()->filled('data_fine') ? 'applied' : '' }}" value="{{ request('data_fine') }}">
                            @if(request()->filled('data_fine'))
                                <span class="clear-filter" data-filter="data_fine" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Località --}}
                    <div class="col-md-3">
                        <label for="localita" class="form-label">
                            <i class="fas fa-map-marker-alt me-1"></i> Località
                        </label>
                        <div class="select-wrapper">
                            <input type="text" name="localita" id="localita" class="form-control filter-select {{ request()->filled('localita') ? 'applied' : '' }}" value="{{ request('localita') }}" placeholder="Inserisci località">
                            @if(request()->filled('localita'))
                                <span class="clear-filter" data-filter="localita" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('eventi.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table contenente gli eventi -->
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered align-middle">
        <thead>
            <tr>
                <th scope="col">Tipologia</th>
                <th scope="col">Nome</th>
                <th scope="col">Data Inizio</th>
                <th scope="col">Data Fine</th>
                <th scope="col">Località</th>
                <th scope="col">Grado</th>
                <th scope="col">Militare</th>
                <th scope="col" style="width: 120px">Azioni</th>
            </tr>
        </thead>
        <tbody id="eventiTableBody">
            @forelse($eventi as $evento)
                <tr id="evento-{{ $evento->id }}" class="evento-row" data-evento-id="{{ $evento->id }}">
                    <td>{{ $evento->tipologia }}</td>
                    <td>{{ $evento->nome }}</td>
                    <td>{{ $evento->data_inizio }}</td>
                    <td>{{ $evento->data_fine }}</td>
                    <td>{{ $evento->localita }}</td>
                    <td><strong>{{ $evento->militare->grado->nome }}</strong></td>
                    <td>
                        <a href="{{ route('anagrafica.show', $evento->militare->id) }}" class="link-name">
                            {{ $evento->militare->cognome }} {{ $evento->militare->nome }}
                        </a>
                    </td>
                    <td>
                        <div class="d-flex justify-content-center action-buttons">
                            <button type="button" class="action-btn delete" data-tooltip="Elimina" aria-label="Elimina"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-evento-id="{{ $evento->id }}"
                                    data-evento-name="{{ $evento->nome }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun evento trovato.</p>
                            <a href="{{ route('eventi.index') }}" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Floating Action Button -->
<a href="{{ route('eventi.create') }}" class="fab" data-tooltip="Aggiungi Evento" aria-label="Aggiungi Evento">
    <i class="fas fa-plus"></i>
</a>

<!-- Modal per conferma eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Conferma eliminazione</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Sei sicuro di voler eliminare l'evento <span id="eventoName" class="fw-bold"></span>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
        <form id="deleteForm" action="" method="POST">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger">Elimina</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione del pulsante filtri
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const filtersContainer = document.getElementById('filtersContainer');
    const toggleFiltersText = document.getElementById('toggleFiltersText');

    if (toggleFiltersBtn && filtersContainer && toggleFiltersText) {
        toggleFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Toggle delle classi
            const isVisible = filtersContainer.classList.toggle('visible');
            
            // Aggiorna il testo e la classe del pulsante
            if (isVisible) {
                toggleFiltersText.textContent = 'Nascondi filtri';
                toggleFiltersBtn.classList.add('active');
            } else {
                toggleFiltersText.textContent = 'Mostra filtri';
                toggleFiltersBtn.classList.remove('active');
            }
        });
    }
    
    // Gestione filtri individuali
    const clearFilterButtons = document.querySelectorAll('.clear-filter');
    
    if (clearFilterButtons.length > 0) {
        clearFilterButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const filterName = this.dataset.filter;
                const select = document.getElementById(filterName);
                
                if (select) {
                    select.value = '';
                    select.classList.remove('applied');
                    
                    // Aggiorna URL senza il parametro del filtro
                    const url = new URL(window.location.href);
                    url.searchParams.delete(filterName);
                    window.location.href = url.toString();
                }
            });
        });
    }
    
    // Gestione submit automatico al cambio di filtro
    const filterSelects = document.querySelectorAll('.filter-select');
    const filtroForm = document.getElementById('filtroForm');
    
    if (filterSelects.length > 0 && filtroForm) {
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                // Aggiungi classe di animazione
                this.classList.add('is-changing');
                
                // Piccolo ritardo per permettere l'animazione
                setTimeout(() => {
                    this.classList.remove('is-changing');
                    filtroForm.submit();
                }, 300);
            });
            
            // Focus effect
            select.addEventListener('focus', function() {
                this.classList.add('is-focusing');
            });
            
            select.addEventListener('blur', function() {
                this.classList.remove('is-focusing');
            });
        });
    }
    
    // Gestione della modal di eliminazione
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            // Pulsante che ha attivato la modal
            const button = event.relatedTarget;
            
            // Estrai info dall'attributo data-*
            const eventoId = button.getAttribute('data-evento-id');
            const eventoName = button.getAttribute('data-evento-name');
            
            // Aggiorna la modal
            const modalTitle = deleteModal.querySelector('.modal-title');
            const eventoNameSpan = document.getElementById('eventoName');
            const deleteForm = document.getElementById('deleteForm');
            
            modalTitle.textContent = 'Elimina Evento';
            eventoNameSpan.textContent = eventoName;
            deleteForm.action = `/eventi/${eventoId}`;
        });
    }
});
</script>
@endsection
