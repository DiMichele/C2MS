@extends('layouts.app')
@section('title', 'Forza Effettiva - C2MS')

@section('content')
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['grado_id', 'plotone_id', 'polo_id', 'presenza'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Forza Effettiva</h1>
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
    
    <div class="search-container" style="position: relative; width: 320px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchMilitare" 
            class="form-control" 
            data-search-type="militare"
            data-target-container="militariTableBody"
            placeholder="Cerca militare..." 
            aria-label="Cerca militare" 
            style="padding-left: 40px; border-radius: 20px;">
    </div>
    
    <div>
        <span class="badge bg-primary">{{ $militari->count() }} militari</span>
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
            <form id="filtroForm" action="{{ route('militare.index') }}" method="GET">
                <div class="row">
                    {{-- Filtro Presenza --}}
                    <div class="col-md-3">
                        <label for="presenza" class="form-label">
                            <i class="fas fa-calendar-check me-1"></i> Presenza
                        </label>
                        <div class="select-wrapper">
                            <select name="presenza" id="presenza" class="form-select filter-select {{ request()->filled('presenza') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="Presente" {{ request('presenza') == 'Presente' ? 'selected' : '' }}>Presenti oggi</option>
                                <option value="Assente" {{ request('presenza') == 'Assente' ? 'selected' : '' }}>Assenti oggi</option>
                            </select>
                            @if(request()->filled('presenza'))
                                <span class="clear-filter" data-filter="presenza" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Grado --}}
                    <div class="col-md-3">
                        <label for="grado_id" class="form-label">
                            <i class="fas fa-medal me-1"></i> Grado
                        </label>
                        <div class="select-wrapper">
                            <select name="grado_id" id="grado_id" class="form-select filter-select {{ request()->filled('grado_id') ? 'applied' : '' }}">
                                <option value="">Tutti i gradi</option>
                                @foreach($gradi as $grado)
                                    <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                        {{ $grado->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('grado_id'))
                                <span class="clear-filter" data-filter="grado_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Plotone --}}
                    <div class="col-md-3">
                        <label for="plotone_id" class="form-label">
                            <i class="fas fa-users me-1"></i> Plotone
                        </label>
                        <div class="select-wrapper">
                            <select name="plotone_id" id="plotone_id" class="form-select filter-select {{ request()->filled('plotone_id') ? 'applied' : '' }}">
                                <option value="">Tutti i plotoni</option>
                                @foreach($plotoni as $plotone)
                                    <option value="{{ $plotone->id }}" {{ request('plotone_id') == $plotone->id ? 'selected' : '' }}>
                                        {{ $plotone->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('plotone_id'))
                                <span class="clear-filter" data-filter="plotone_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Polo --}}
                    <div class="col-md-3">
                        <label for="polo_id" class="form-label">
                            <i class="fas fa-building me-1"></i> Polo
                        </label>
                        <div class="select-wrapper">
                            <select name="polo_id" id="polo_id" class="form-select filter-select {{ request()->filled('polo_id') ? 'applied' : '' }}">
                                <option value="">Tutti i poli</option>
                                @foreach($poli as $polo)
                                    <option value="{{ $polo->id }}" {{ request('polo_id') == $polo->id ? 'selected' : '' }}>
                                        {{ $polo->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('polo_id'))
                                <span class="clear-filter" data-filter="polo_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('militare.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table contenente i militari -->
<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered align-middle">
        <thead>
            <tr>
                <th scope="col" style="width: 60px">Presenza</th>
                <th scope="col" style="width: 100px">Grado</th>
                <th scope="col">Nominativo</th>
                <th scope="col">Plotone</th>
                <th scope="col">Polo</th>
                <th scope="col" class="col-note">Note</th>
            </tr>
        </thead>
        <tbody id="militariTableBody">
            @forelse($militari as $m)
                <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}">
                    <td class="text-center">
                        @php
                            $isPresente = ($m->presenzaOggi && $m->presenzaOggi->stato === 'Presente');
                        @endphp
                        <div class="presence-circle {{ $isPresente ? 'presente' : 'assente' }}" 
                             data-label="{{ $isPresente ? 'Presente' : 'Assente' }}"
                             role="img" 
                             aria-label="Stato: {{ $isPresente ? 'Presente' : 'Assente' }}"></div>
                    </td>
                    <td>
                        {{ $m->grado->nome ?? 'N/A' }}
                    </td>
                    <td>
                        <a href="{{ route('militare.show', $m->id) }}" class="link-name">
                            {{ $m->cognome }} {{ $m->nome }}
                        </a>
                    </td>
                    <td>
                        @if($m->plotone)
                            <span class="entity-text">{{ $m->plotone->nome }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($m->polo)
                            <span class="entity-text">{{ $m->polo->nome }}</span>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <div class="note-wrapper">
                            <textarea 
                                class="auto-save-notes" 
                                data-militare-id="{{ $m->id }}" 
                                data-field="note" 
                                data-autosave-url="{{ route('militare.update', $m->id) }}"
                                data-autosave-field="note"
                                placeholder="Inserisci le note..." 
                                aria-label="Note per {{ $m->cognome }} {{ $m->nome }}"
                            >{{ $m->note ?? '' }}</textarea>
                            <div class="save-indicator d-none" data-tooltip="Salvataggio in corso"><i class="fas fa-sync-alt fa-spin"></i></div>
                            <div class="save-status-text d-none"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun militare trovato.</p>
                            <a href="{{ route('militare.index') }}" class="btn btn-outline-primary mt-2">
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
<a href="{{ route('militare.create') }}" class="fab" data-tooltip="Aggiungi Militare" aria-label="Aggiungi Militare">
    <i class="fas fa-plus"></i>
</a>

<!-- Modal per conferma eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel">Conferma Eliminazione</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center mb-3">
            <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
            <div>
                <p class="mb-1">Sei sicuro di voler eliminare <strong id="militare-to-delete"></strong>?</p>
                <p class="text-danger mb-0 small">
                    <i class="fas fa-info-circle me-1"></i> Questa azione non può essere annullata.
                </p>
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Annulla
        </button>
        <form id="deleteForm" action="" method="POST" style="display: inline;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger" id="confirmDeleteBtn">
                <i class="fas fa-trash-alt me-1"></i> Elimina
            </button>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<!-- Script per inizializzazione moduli -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Forza l'inizializzazione dei filtri se non già fatto
    if (window.C2MS && window.C2MS.Filters) {
        window.C2MS.Filters.init();
    }
    
    // Inizializza il nuovo sistema di ricerca
    if (window.C2MS && window.C2MS.Search) {
        window.C2MS.Search.init();
    }
});
</script>
<!-- File JavaScript per pagina militare -->
<script src="{{ asset('js/militare.js') }}"></script>
@endpush
