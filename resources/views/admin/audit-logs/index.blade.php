@extends('layouts.app')

@section('title', 'Registro Attività')

@section('content')
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['search', 'user_id', 'action', 'entity_type', 'status', 'date_from', 'date_to', 'compagnia_id'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<style>
/* Stili specifici per questa pagina */
.form-control, .form-select {
    border-radius: 0 !important;
}

.filter-select {
    border-radius: 0 !important;
}

.sugeco-table-wrapper {
    max-height: calc(100vh - 280px);
}

/* Stato Attenzione: viene usato quando un'operazione è completata ma con avvisi o anomalie non critiche */
.status-warning {
    background-color: rgba(255, 193, 7, 0.1);
}

/* Badge purple per permessi - testo bianco su sfondo viola */
.badge.bg-purple {
    background-color: #6f42c1 !important;
    color: white !important;
}
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Registro Attività</h1>
</div>

<!-- Selettore Mese/Anno -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-center">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="d-flex gap-2 align-items-center">
                @php
                    $mesiItaliani = [
                        1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                        5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                        9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                    ];
                    $meseCorrente = request('mese', now()->month);
                    $annoCorrente = request('anno', now()->year);
                @endphp
                <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px; border-radius: 6px !important;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $meseCorrente == $m ? 'selected' : '' }}>
                            {{ $mesiItaliani[$m] }}
                        </option>
                    @endfor
                </select>
                <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px; border-radius: 6px !important;">
                    @for($a = 2024; $a <= 2030; $a++)
                        <option value="{{ $a }}" {{ $annoCorrente == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endfor
                </select>
            </form>
        </div>
    </div>
</div>

<!-- Filtri e azioni su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
        <span id="toggleFiltersText">
            {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
        </span>
    </button>
    
    <a href="{{ route('admin.audit-logs.export', array_merge(request()->query(), ['mese' => $meseCorrente, 'anno' => $annoCorrente])) }}" class="btn btn-success" style="border-radius: 6px !important;">
        Esporta Excel
    </a>
</div>

<!-- Filtri con sezione migliorata -->
<div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>Filtri avanzati</div>
        </div>
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filter-local">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Ricerca testuale --}}
                    <div class="col-md-3">
                        <label for="search" class="form-label small mb-1">Cerca</label>
                        <div class="select-wrapper">
                            <input type="text" 
                                   class="form-control form-control-sm" 
                                   name="search" 
                                   id="search"
                                   value="{{ request('search') }}" 
                                   placeholder="Cerca per descrizione, utente...">
                            @if(request()->filled('search'))
                                <span class="clear-filter" data-filter="search" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Utente --}}
                    <div class="col-md-3">
                        <label for="user_id" class="form-label small mb-1">Utente</label>
                        <div class="select-wrapper">
                            <select name="user_id" id="user_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('user_id'))
                                <span class="clear-filter" data-filter="user_id" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Tipo azione --}}
                    <div class="col-md-3">
                        <label for="action" class="form-label small mb-1">Tipo Azione</label>
                        <div class="select-wrapper">
                            <select name="action" id="action" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutte</option>
                                @foreach($actions as $key => $label)
                                    <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('action'))
                                <span class="clear-filter" data-filter="action" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Tipo entità --}}
                    <div class="col-md-3">
                        <label for="entity_type" class="form-label small mb-1">Tipo Dato</label>
                        <div class="select-wrapper">
                            <select name="entity_type" id="entity_type" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                @foreach($entityTypes as $key => $label)
                                    <option value="{{ $key }}" {{ request('entity_type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('entity_type'))
                                <span class="clear-filter" data-filter="entity_type" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Seconda riga filtri --}}
                <div class="row mb-3">
                    {{-- Stato --}}
                    <div class="col-md-3">
                        <label for="status" class="form-label small mb-1">Stato</label>
                        <div class="select-wrapper">
                            <select name="status" id="status" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Successo</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Fallito</option>
                                <option value="warning" {{ request('status') == 'warning' ? 'selected' : '' }}>Attenzione</option>
                            </select>
                            @if(request()->filled('status'))
                                <span class="clear-filter" data-filter="status" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Data da --}}
                    <div class="col-md-3">
                        <label for="date_from" class="form-label small mb-1">Data da</label>
                        <div class="select-wrapper">
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   name="date_from" 
                                   id="date_from"
                                   value="{{ request('date_from') }}">
                            @if(request()->filled('date_from'))
                                <span class="clear-filter" data-filter="date_from" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Data a --}}
                    <div class="col-md-3">
                        <label for="date_to" class="form-label small mb-1">Data a</label>
                        <div class="select-wrapper">
                            <input type="date" 
                                   class="form-control form-control-sm" 
                                   name="date_to" 
                                   id="date_to"
                                   value="{{ request('date_to') }}">
                            @if(request()->filled('date_to'))
                                <span class="clear-filter" data-filter="date_to" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>

                    {{-- Compagnia --}}
                    <div class="col-md-3">
                        <label for="compagnia_id" class="form-label small mb-1">Compagnia</label>
                        <div class="select-wrapper">
                            <select name="compagnia_id" id="compagnia_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutte</option>
                                @foreach($compagnie as $compagnia)
                                    <option value="{{ $compagnia->id }}" {{ request('compagnia_id') == $compagnia->id ? 'selected' : '' }}>
                                        {{ $compagnia->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('compagnia_id'))
                                <span class="clear-filter" data-filter="compagnia_id" title="Rimuovi filtro">&times;</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-danger btn-sm reset-all-filters" style="display: {{ $hasActiveFilters ? 'block' : 'none' }};">
                        Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabella con scroll orizzontale -->
<div class="sugeco-table-wrapper">
    <table class="sugeco-table">
        <thead>
            <tr>
                <th style="width: 160px;">DATA/ORA</th>
                <th style="width: 150px;">UTENTE</th>
                <th style="width: 120px;">AZIONE</th>
                <th style="width: 120px;">PAGINA</th>
                <th>DESCRIZIONE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $log)
                @php
                    // Colore riga basato sullo stato: verde=success, rosso=failed
                    $rowClass = '';
                    $rowStyle = '';
                    if ($log->status === 'success') {
                        $rowStyle = 'background-color: rgba(25, 135, 84, 0.1);';
                    } elseif ($log->status === 'failed') {
                        $rowStyle = 'background-color: rgba(220, 53, 69, 0.15);';
                    }
                @endphp
                <tr style="{{ $rowStyle }}">
                    <td>
                        <div class="fw-semibold">{{ $log->created_at->format('d/m/Y') }}</div>
                        <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                    </td>
                    <td>
                        @if($log->user)
                            <a href="{{ route('admin.audit-logs.user', $log->user) }}" class="link-name">
                                {{ $log->user_name }}
                            </a>
                        @else
                            <span class="text-muted">{{ $log->user_name ?? 'Sistema' }}</span>
                        @endif
                        @if($log->compagnia)
                            <br><small class="text-muted">{{ $log->compagnia->nome }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $log->action_color }}">
                            {{ $log->action_label }}
                        </span>
                    </td>
                    <td>
                        @if($log->page_context)
                            <small class="text-muted">{{ $log->page_context }}</small>
                        @else
                            <small class="text-muted">-</small>
                        @endif
                    </td>
                    <td>
                        <span style="word-break: break-word; white-space: pre-wrap;">{{ $log->description }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <p class="text-muted mb-0">Nessun log trovato con i filtri selezionati</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Info count senza paginazione --}}
<div class="text-muted mt-2">
    <small>{{ $logs->count() }} attività nel mese di {{ $mesiItaliani[$meseCorrente] ?? '' }} {{ $annoCorrente }}</small>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================================
    // GESTIONE FILTRI REGISTRO ATTIVITÀ
    // =====================================================================
    
    const filtersContainer = document.getElementById('filtersContainer');
    const form = document.querySelector('form.filter-local');
    
    if (!form || !filtersContainer) return;
    
    // Gestione clear filter singolo
    document.querySelectorAll('.clear-filter').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const filter = this.dataset.filter;
            const input = form.querySelector('[name="' + filter + '"]');
            
            if (input) {
                input.value = '';
                // Submit form per applicare i filtri
                form.submit();
            }
        });
    });

    // Reset tutti i filtri
    const resetBtn = form.querySelector('.reset-all-filters');
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '{{ route("admin.audit-logs.index") }}';
        });
    }
    
    // Submit automatico al cambio filtro
    form.querySelectorAll('select.filter-select').forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
    
    // Submit su Enter nella ricerca
    const searchInput = form.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                form.submit();
            }
        });
        
        // Debounce sulla ricerca
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) {
                    form.submit();
                }
            }, 500);
        });
    }
});
</script>
@endpush
@endsection
