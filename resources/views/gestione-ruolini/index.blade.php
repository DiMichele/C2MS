@extends('layouts.app')

@section('title', 'Gestione Ruolini - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header con info Compagnia -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-1">Gestione Ruolini</h1>
            <p class="text-muted mb-0">
                <i class="fas fa-building me-1"></i>
                Configurazione per: <strong class="text-primary">{{ $compagniaCorrente->nome ?? 'N/D' }}</strong>
            </p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            @if($isGlobalAdmin && $compagnie->isNotEmpty())
            <!-- Selettore Compagnia per Admin -->
            <div class="admin-compagnia-selector">
                <label class="form-label mb-1 small text-muted">
                    <i class="fas fa-shield-alt me-1"></i>Visualizza come:
                </label>
                <select id="compagniaSelector" class="form-select form-select-sm" style="min-width: 200px;">
                    @foreach($compagnie as $comp)
                    <option value="{{ $comp->id }}" {{ $compagniaId == $comp->id ? 'selected' : '' }}>
                        {{ $comp->nome }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <!-- Badge Compagnia -->
            <div class="compagnia-badge bg-primary text-white px-3 py-2 rounded">
                <i class="fas fa-flag me-1"></i>
                {{ $compagniaCorrente->nome ?? 'N/D' }}
            </div>
        </div>
    </div>

    <!-- Card Impostazioni Generali -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Impostazioni Generali Ruolini</h6>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label fw-bold">
                        <i class="fas fa-question-circle me-1 text-info"></i>
                        Stato di default (quando un servizio non è configurato):
                    </label>
                    <p class="text-muted small mb-2">
                        Questa impostazione definisce se un militare con un servizio NON esplicitamente configurato 
                        viene considerato presente o assente nel ruolino.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <select id="defaultStatoSelect" class="form-select" style="max-width: 250px;">
                            <option value="assente" {{ ($defaultStato ?? 'assente') === 'assente' ? 'selected' : '' }}>
                                ✗ Assente (default)
                            </option>
                            <option value="presente" {{ ($defaultStato ?? 'assente') === 'presente' ? 'selected' : '' }}>
                                ✓ Presente
                            </option>
                        </select>
                        <span id="defaultStatoFeedback" class="text-success" style="display: none;">
                            <i class="fas fa-check-circle"></i> Salvato
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        // Check if any filters are active
        $activeFilters = [];
        if(request()->filled('categoria') && request('categoria') != 'tutte') $activeFilters[] = 'categoria';
        if(request()->filled('stato') && request('stato') != 'tutti') $activeFilters[] = 'stato';
        $hasActiveFilters = count($activeFilters) > 0;
    @endphp

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 500px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
            <input 
                type="text" 
                id="searchServizio" 
                class="form-control" 
                placeholder="Cerca per codice o nome servizio..." 
                aria-label="Cerca servizio" 
                autocomplete="off"
                style="padding-left: 40px; border-radius: 6px !important;">
        </div>
    </div>

    <!-- Barra filtri -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <button type="button" id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
            <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
            <span id="toggleFiltersText">
                {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
            </span>
        </button>
        
        <div>
            <span class="badge bg-secondary" id="visibleCount"></span>
        </div>
    </div>

    <!-- Sezione Filtri -->
    <div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
        <div class="filter-card mb-4">
            <div class="filter-card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-filter me-2"></i> Filtri avanzati
                </div>
            </div>
            <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('gestione-ruolini.index') }}" method="GET">
                <div class="row">
                    <!-- Filtro Categoria -->
                    <div class="col-md-4">
                        <label for="categoria" class="form-label">
                            <i class="fas fa-tag me-1"></i> Categoria
                        </label>
                        <div class="select-wrapper">
                            <select name="categoria" id="categoriaFilter" class="form-select filter-select {{ (request('categoria') && request('categoria') != 'tutte') ? 'applied' : '' }}">
                                <option value="tutte" {{ (request('categoria', 'tutte') == 'tutte') ? 'selected' : '' }}>Tutte le categorie</option>
                                @php
                                    $categorie = $tipiServizio->pluck('categoria')->unique()->sort()->values();
                                @endphp
                                @foreach($categorie as $cat)
                                    <option value="{{ $cat }}" {{ request('categoria') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            @if(request('categoria') && request('categoria') != 'tutte')
                                <span class="clear-filter" data-filter="categoria" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>

                    <!-- Filtro Stato -->
                    <div class="col-md-4">
                        <label for="stato" class="form-label">
                            <i class="fas fa-check-circle me-1"></i> Stato nei Ruolini
                        </label>
                        <div class="select-wrapper">
                            <select name="stato" id="statoFilter" class="form-select filter-select {{ (request('stato') && request('stato') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('stato', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti gli stati</option>
                                <option value="presente" {{ request('stato') == 'presente' ? 'selected' : '' }}>Presente</option>
                                <option value="assente" {{ request('stato') == 'assente' ? 'selected' : '' }}>Assente</option>
                            </select>
                            @if(request('stato') && request('stato') != 'tutti')
                                <span class="clear-filter" data-filter="stato" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if(count($activeFilters) > 0)
                    <a href="{{ route('gestione-ruolini.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Tabella Configurazione -->
    <div class="table-container-ruolini">
        <table class="table table-hover mb-0 ruolini-table" id="ruoliniTable">
            <thead>
                <tr>
                    <th style="width: 150px;">CODICE CPT</th>
                            <th>NOME SERVIZIO</th>
                    <th style="width: 150px;">CATEGORIA</th>
                    <th style="width: 250px;">STATO NEI RUOLINI</th>
                        </tr>
                    </thead>
            <tbody id="ruoliniTableBody">
                        @forelse($tipiServizio as $tipo)
                        @php
                            $config = $configurazioni->get($tipo->id);
                    $statoPresenza = $config ? $config->stato_presenza : 'assente';
                        @endphp
                <tr data-tipo-id="{{ $tipo->id }}" 
                    data-codice="{{ strtolower($tipo->codice) }}" 
                    data-nome="{{ strtolower($tipo->nome) }}"
                    data-categoria="{{ $tipo->categoria }}"
                    data-stato="{{ $statoPresenza }}">
                    <td>
                        <span class="badge-cpt" style="background-color: {{ $tipo->colore_badge }} !important;">
                                    {{ $tipo->codice }}
                                </span>
                            </td>
                            <td><strong>{{ $tipo->nome }}</strong></td>
                            <td>
                        <span class="badge bg-light text-dark">{{ $tipo->categoria }}</span>
                            </td>
                            <td>
                        <div class="stato-selector-wrapper">
                            <select class="stato-select" 
                                    data-tipo-id="{{ $tipo->id }}"
                                    autocomplete="off">
                                <option value="assente" {{ $statoPresenza === 'assente' ? 'selected' : '' }}>
                                    ✗ Assente
                                </option>
                                <option value="presente" {{ $statoPresenza === 'presente' ? 'selected' : '' }}>
                                    ✓ Presente
                                </option>
                            </select>
                        </div>
                            </td>
                        </tr>
                        @empty
                <tr id="noResults">
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Nessun tipo di servizio disponibile
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
    </div>
</div>

<script>
(function() {
    'use strict';
    
    // Attendi che il DOM sia completamente caricato
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        // Verifica CSRF token
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta) {
            console.error('CSRF token meta tag not found!');
            return;
        }
        const csrfToken = csrfMeta.getAttribute('content');
        
        const searchInput = document.getElementById('searchServizio');
        const categoriaFilter = document.getElementById('categoriaFilter');
        const statoFilter = document.getElementById('statoFilter');
        const tbody = document.getElementById('ruoliniTableBody');
        const toggleFiltersBtn = document.getElementById('toggleFilters');
        const filtersContainer = document.getElementById('filtersContainer');
        const toggleFiltersText = document.getElementById('toggleFiltersText');
        const visibleCount = document.getElementById('visibleCount');

        // Salvataggio automatico al cambio del select
        document.querySelectorAll('.stato-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const tipoId = this.dataset.tipoId;
                const statoPresenza = this.value;
                const selectElement = this;
                const row = this.closest('tr');
                
                // Aggiorna il data-stato della riga per i filtri
                row.dataset.stato = statoPresenza;
                
                // Costruisci URL
                const updateUrl = '{{ url("gestione-ruolini") }}' + '/' + tipoId;
                
                // Prepara i dati
                const payload = {
                    stato_presenza: statoPresenza,
                    note: null
                };
                
                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(function(response) {
                    if (!response.ok) {
                        return response.text().then(function(text) {
                            console.error('Response body:', text);
                            throw new Error('HTTP error! status: ' + response.status);
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Usa utility globale per feedback visivo
                        window.SUGECO.showSaveFeedback(selectElement, true, 2000);
                    } else {
                        // Mostra bordo rosso in caso di errore
                        window.SUGECO.showSaveFeedback(selectElement, false, 2000);
                    }
                })
                .catch(function(error) {
                    console.error('Errore salvataggio:', error);
                    // Mostra bordo rosso in caso di errore
                    window.SUGECO.showSaveFeedback(selectElement, false, 2000);
                });
            });
        });

        // Funzione per aggiornare il contatore
        function updateVisibleCount() {
            if (!tbody || !visibleCount) return;
            const rows = tbody.querySelectorAll('tr[data-tipo-id]');
            const visible = Array.from(rows).filter(row => row.style.display !== 'none').length;
            visibleCount.textContent = `${visible} servizi`;
        }

        // Funzione di filtro e ricerca
        function filterTable() {
            if (!searchInput || !tbody) return;
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            const categoriaValue = categoriaFilter ? categoriaFilter.value : 'tutte';
            const statoValue = statoFilter ? statoFilter.value : 'tutti';
            const rows = tbody.querySelectorAll('tr[data-tipo-id]');
            let visibleRows = 0;

            rows.forEach(row => {
                const codice = (row.dataset.codice || '').toLowerCase();
                const nome = (row.dataset.nome || '').toLowerCase();
                const categoria = row.dataset.categoria || '';
                const stato = row.dataset.stato || '';

                // Check ricerca
                const matchSearch = !searchTerm || 
                                    codice.indexOf(searchTerm) !== -1 || 
                                    nome.indexOf(searchTerm) !== -1;

                // Check categoria
                const matchCategoria = categoriaValue === 'tutte' || categoria === categoriaValue;

                // Check stato
                const matchStato = statoValue === 'tutti' || stato === statoValue;

                // Mostra/nascondi riga
                if (matchSearch && matchCategoria && matchStato) {
                    row.style.display = '';
                    visibleRows++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Mostra messaggio se nessun risultato
            let noResults = tbody.querySelector('#noResults');
            const hasDataRows = rows.length > 0;
            
            if (visibleRows === 0 && hasDataRows) {
                if (!noResults) {
                    const tr = document.createElement('tr');
                    tr.id = 'noResults';
                    tr.innerHTML = `
                        <td colspan="4" class="text-center text-muted py-5">
                            <i class="fas fa-search fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Nessun risultato trovato</p>
                        </td>
                    `;
                    tbody.appendChild(tr);
                } else {
                    noResults.style.display = '';
                }
            } else if (noResults && visibleRows > 0) {
                noResults.style.display = 'none';
            }
            
            // Aggiorna contatore
            updateVisibleCount();
        }

        // Event listeners per ricerca e filtri
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            searchInput.addEventListener('keyup', filterTable);
        }
        if (categoriaFilter) {
            categoriaFilter.addEventListener('change', filterTable);
        }
        if (statoFilter) {
            statoFilter.addEventListener('change', filterTable);
        }

        // NOTA: Il toggle filtri è gestito dal JavaScript globale in app.blade.php
        // Non serve implementarlo qui per evitare conflitti

        // Clear single filter
        document.querySelectorAll('.clear-filter').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.dataset.filter;
                const select = document.getElementById(filter + 'Filter');
                if (select) {
                    select.value = filter === 'categoria' ? 'tutte' : 'tutti';
                    filterTable();
                }
            });
        });
        
        // Aggiorna contatore iniziale
        updateVisibleCount();
        
        // ==========================================
        // GESTIONE COMPAGNIA E DEFAULT STATO
        // ==========================================
        
        // Selettore compagnia per admin
        const compagniaSelector = document.getElementById('compagniaSelector');
        if (compagniaSelector) {
            compagniaSelector.addEventListener('change', function() {
                const compagniaId = this.value;
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('compagnia_id', compagniaId);
                window.location.href = currentUrl.toString();
            });
        }
        
        // Salvataggio default stato
        const defaultStatoSelect = document.getElementById('defaultStatoSelect');
        const defaultStatoFeedback = document.getElementById('defaultStatoFeedback');
        
        if (defaultStatoSelect) {
            defaultStatoSelect.addEventListener('change', function() {
                const stato = this.value;
                const selectElement = this;
                
                fetch('{{ route("gestione-ruolini.update-default-stato") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ default_stato: stato })
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Mostra feedback
                        if (defaultStatoFeedback) {
                            defaultStatoFeedback.style.display = 'inline';
                            setTimeout(function() {
                                defaultStatoFeedback.style.display = 'none';
                            }, 2000);
                        }
                        window.SUGECO.showSaveFeedback(selectElement, true, 2000);
                    } else {
                        window.SUGECO.showSaveFeedback(selectElement, false, 2000);
                    }
                })
                .catch(function(error) {
                    console.error('Errore salvataggio default stato:', error);
                    window.SUGECO.showSaveFeedback(selectElement, false, 2000);
                });
            });
        }
    }
})();
</script>

<style>
/* Fix globale per badge trasparenti */
.ruolini-table .badge-cpt,
.ruolini-table .badge-cpt * {
    opacity: 1 !important;
    filter: none !important;
    -webkit-filter: none !important;
}

/* Container tabella */
.table-container-ruolini {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

/* Tabella Ruolini */
.ruolini-table {
    margin: 0 !important;
    background: white;
}

.ruolini-table thead {
    background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%);
    position: sticky;
    top: 0;
    z-index: 10;
}

.ruolini-table thead th {
    color: #fff;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border: none;
    vertical-align: middle;
}

.ruolini-table tbody {
    background: white;
}

.ruolini-table tbody tr {
    border-bottom: 1px solid #e9ecef;
    transition: all 0.2s ease;
    background: white;
}

.ruolini-table tbody tr:hover {
    background-color: #f8f9fa !important;
    transform: translateX(2px);
}

.ruolini-table tbody td {
    padding: 1rem;
    vertical-align: middle;
    background: transparent;
}

/* Badge CPT Migliorato - FIXATO OPACITÀ */
.badge-cpt {
    display: inline-block !important;
    padding: 0.5rem 1rem !important;
    font-size: 0.95rem !important;
    font-weight: 700 !important;
    border-radius: 6px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15) !important;
    min-width: 80px !important;
    text-align: center !important;
    color: #fff !important;
    opacity: 1 !important;
    filter: none !important;
    -webkit-filter: none !important;
}

/* Selettore Stato Migliorato */
.stato-selector-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stato-select {
    appearance: none;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 0.5rem 2.5rem 0.5rem 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: #495057;
    cursor: pointer;
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23495057' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 12px;
    min-width: 160px;
}

.stato-select:hover {
    border-color: #0A2342;
    box-shadow: 0 2px 8px rgba(10, 35, 66, 0.1);
}

.stato-select:focus {
    outline: none;
    border-color: #0A2342;
    box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
}

.stato-select option {
    padding: 0.5rem;
    font-weight: 500;
}

/* Filtri Section - Usa il CSS globale da filters.css */
/* Aggiustamenti specifici per gestione-ruolini se necessari */
.filter-section {
    margin-bottom: 1.5rem;
}

.filter-card-header {
    background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%);
    color: white;
    padding: 1rem;
    font-weight: 600;
    font-size: 0.95rem;
}

.select-wrapper {
    position: relative;
}

.clear-filter {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #dc3545;
    font-size: 1.2rem;
    z-index: 10;
    padding: 5px;
    transition: all 0.2s;
}

.clear-filter:hover {
    color: #c82333;
    transform: translateY(-50%) scale(1.2);
}

.filter-select.applied {
    border-color: #0A2342;
    background-color: #f0f8ff;
}

/* Responsive */
@media (max-width: 768px) {
    .ruolini-table thead th {
        padding: 0.75rem 0.5rem;
        font-size: 0.7rem;
    }
    
    .ruolini-table tbody td {
        padding: 0.75rem 0.5rem;
    }
    
    .badge-cpt {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        min-width: 60px;
    }
    
    .stato-select {
        min-width: 130px;
        font-size: 0.8rem;
        padding: 0.4rem 2rem 0.4rem 0.75rem;
    }
    
    .stato-selector-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .search-container {
        width: 100% !important;
        margin-bottom: 1rem;
    }
}
</style>
@endsection
