@extends('layouts.app')
@section('title', 'Anagrafica - SUGECO')

@section('content')
<style>
/* ========================================
   STILI MILITARI ACQUISITI (READ-ONLY)
   ======================================== */
.acquired-militare {
    background-color: rgba(23, 162, 184, 0.08) !important;
}

.acquired-militare td {
    position: relative;
}

.acquired-militare td::after {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #17a2b8;
}

.acquired-militare td:first-child::after {
    border-radius: 3px 0 0 3px;
}

.acquired-militare:hover {
    background-color: rgba(23, 162, 184, 0.15) !important;
}

/* Badge per militari acquisiti */
.acquired-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #17a2b8;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 6px;
}

/* ========================================
   Stili specifici per questa pagina
   (Stili base tabelle in table-standard.css)
   ======================================== */

/* Uniforma gli stili dei form controls */
.form-control, .form-select {
    border-radius: 0 !important;
}

/* Stili per i filtri come nel CPT */
.filter-select {
    border-radius: 0 !important;
}

/* Override altezza max per questa pagina (più elementi nell'header) */
.sugeco-table-wrapper {
    max-height: calc(100vh - 300px);
}

/* Larghezze specifiche per colonne anagrafica (override) */
.sugeco-table-wrapper .sugeco-table th:nth-child(2),
.sugeco-table-wrapper .sugeco-table td:nth-child(2) { min-width: 180px; } /* Grado - più largo */

.sugeco-table-wrapper .sugeco-table th:nth-child(3),
.sugeco-table-wrapper .sugeco-table td:nth-child(3) { min-width: 180px; } /* Cognome - più largo */

/* Colonne con input speciali (override per anagrafica) */
.sugeco-table-wrapper .sugeco-table td input[type="email"] {
    min-width: 240px;
}

/* Patenti container */
.sugeco-table-wrapper .sugeco-table td .patenti-container {
    min-width: 140px;
}

/* Istituti container più largo */
.sugeco-table-wrapper .sugeco-table td .istituti-container {
    min-width: 320px;
}

/* Ridimensiona le icone nei pulsanti azioni */
.btn-sm i.fas {
    font-size: 0.85rem;
}

/* Stili minimal per le patenti */
.patenti-container {
    padding: 2px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.patenti-row {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.patenti-container .form-check {
    padding-left: 0;
    margin-bottom: 0 !important;
}

.patenti-container .form-check-input {
    width: 14px;
    height: 14px;
    border: 1px solid #adb5bd;
    border-radius: 2px;
    margin-right: 3px;
    transition: all 0.15s ease;
}

.patenti-container .form-check-input:checked {
    background-color: #0a2342;
    border-color: #0a2342;
}

.patenti-container .form-check-label {
    color: #6c757d;
    user-select: none;
    font-size: 0.8rem;
    line-height: 1.2;
}

.patenti-container .form-check-input:checked + .form-check-label {
    color: #0a2342;
}
</style>
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['compagnia', 'plotone_id', 'grado_id', 'polo_id', 'mansione_id', 'nos_status', 'email_istituzionale', 'telefono', 'presenza', 'compleanno'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Anagrafica</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchMilitare" 
            class="form-control" 
            data-search-type="militare"
            data-target-container="militariTableBody"
            placeholder="Cerca militare..." 
            aria-label="Cerca militare" 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e azioni su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
        <span id="toggleFiltersText">
            {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
        </span>
    </button>
    
    @can('anagrafica.create')
    <a href="{{ route('anagrafica.create') }}" class="btn btn-success" style="border-radius: 6px !important;">
        <i class="fas fa-plus me-2"></i>Nuovo Militare
    </a>
    @endcan
</div>

<!-- Filtri con sezione migliorata -->
<div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>Filtri avanzati</div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('anagrafica.index') }}" method="GET" class="filter-local">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro Compagnia --}}
                    <div class="col-md-3">
                        <label for="compagnia" class="form-label small mb-1">Compagnia</label>
                        <div class="select-wrapper">
                            <select name="compagnia" id="filter_compagnia" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutte</option>
                                @foreach($compagnie as $compagnia)
                                    <option value="{{ $compagnia->id }}">
                                        {{ $compagnia->numero ?? $compagnia->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="clear-filter" data-filter="compagnia" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro Plotone --}}
                    <div class="col-md-3">
                        <label for="plotone_id" class="form-label small mb-1">Plotone</label>
                        <div class="select-wrapper">
                            <select name="plotone_id" id="filter_plotone_id" class="form-select form-select-sm filter-select" data-nosubmit="true" disabled title="Seleziona prima una compagnia">
                                <option value="">Seleziona compagnia</option>
                                @foreach($plotoni as $plotone)
                                    <option value="{{ $plotone->id }}" data-compagnia-id="{{ $plotone->compagnia_id }}">
                                        {{ $plotone->nome }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="clear-filter" data-filter="plotone_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro Grado --}}
                    <div class="col-md-3">
                        <label for="grado_id" class="form-label small mb-1">Grado</label>
                        <div class="select-wrapper">
                            <select name="grado_id" id="filter_grado_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                @foreach($gradi as $grado)
                                    <option value="{{ $grado->id }}">{{ $grado->nome }}</option>
                                @endforeach
                            </select>
                            <span class="clear-filter" data-filter="grado_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro Ufficio (Polo) --}}
                    <div class="col-md-3">
                        <label for="polo_id" class="form-label small mb-1">Ufficio</label>
                        <div class="select-wrapper">
                            <select name="polo_id" id="filter_polo_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                @foreach($poli as $polo)
                                    <option value="{{ $polo->id }}">{{ $polo->nome }}</option>
                                @endforeach
                            </select>
                            <span class="clear-filter" data-filter="polo_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                </div>
                
                {{-- Seconda riga filtri --}}
                <div class="row">
                    {{-- Filtro Incarico (Mansione) --}}
                    <div class="col-md-3">
                        <label for="mansione_id" class="form-label small mb-1">Incarico</label>
                        <div class="select-wrapper">
                            <select name="mansione_id" id="filter_mansione_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                @foreach($mansioni as $mansione)
                                    <option value="{{ $mansione->id }}">{{ $mansione->nome }}</option>
                                @endforeach
                            </select>
                            <span class="clear-filter" data-filter="mansione_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro NOS --}}
                    <div class="col-md-3">
                        <label for="nos_status" class="form-label small mb-1">NOS</label>
                        <div class="select-wrapper">
                            <select name="nos_status" id="filter_nos_status" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                <option value="si">SI</option>
                                <option value="no">NO</option>
                                <option value="da richiedere">Da Richiedere</option>
                                <option value="non previsto">Non Previsto</option>
                                <option value="in attesa">In Attesa</option>
                            </select>
                            <span class="clear-filter" data-filter="nos_status" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro Email Istituzionale --}}
                    <div class="col-md-3">
                        <label for="email_istituzionale" class="form-label small mb-1">Email Istituzionale</label>
                        <div class="select-wrapper">
                            <select name="email_istituzionale" id="filter_email_istituzionale" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutte</option>
                                <option value="registrata">Registrata</option>
                                <option value="non_registrata">Non Registrata</option>
                            </select>
                            <span class="clear-filter" data-filter="email_istituzionale" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                    
                    {{-- Filtro Cellulare --}}
                    <div class="col-md-3">
                        <label for="telefono" class="form-label small mb-1">Cellulare</label>
                        <div class="select-wrapper">
                            <select name="telefono" id="filter_telefono" class="form-select form-select-sm filter-select" data-nosubmit="true">
                                <option value="">Tutti</option>
                                <option value="registrato">Registrato</option>
                                <option value="non_registrato">Non Registrato</option>
                            </select>
                            <span class="clear-filter" data-filter="telefono" title="Rimuovi filtro" style="display: none;">&times;</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    <button type="button" class="btn btn-danger btn-sm reset-all-filters" style="display: none;">
                        Rimuovi tutti i filtri (0)
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
                @foreach($campiCustom as $campo)
                <th>{{ $campo->etichetta }}</th>
                @endforeach
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody id="militariTableBody">
            @forelse($militari as $m)
                @php
                    // PERFORMANCE: Usa attributi calcolati in query con withVisibilityFlags() (evita N+1)
                    // NOTA: Se vedi errori qui, assicurati che il controller usi Militare::withVisibilityFlags()
                    $isAcquired = (bool) ($m->is_acquired ?? false);
                    $isOwner = (bool) ($m->is_owner ?? true);
                    $isReadOnly = !$isOwner || !auth()->user()->can('anagrafica.edit');
                @endphp
                <tr id="militare-{{ $m->id }}" 
                    class="militare-row {{ $isAcquired ? 'acquired-militare' : '' }}" 
                    data-militare-id="{{ $m->id }}" 
                    data-update-url="{{ route('anagrafica.update-field', $m->id) }}"
                    data-read-only="{{ $isReadOnly ? 'true' : 'false' }}"
                    data-compagnia-id="{{ $m->compagnia_id ?? '' }}"
                    data-plotone-id="{{ $m->plotone_id ?? '' }}"
                    data-grado-id="{{ $m->grado_id ?? '' }}"
                    data-polo-id="{{ $m->polo_id ?? '' }}"
                    data-mansione-id="{{ $m->mansione_id ?? '' }}"
                    data-nos-status="{{ $m->nos_status ?? '' }}"
                    data-email="{{ $m->email_istituzionale ? '1' : '0' }}"
                    data-telefono="{{ $m->telefono ? '1' : '0' }}"
                    @if($isAcquired) title="Militare acquisito - Sola lettura" @endif>
                    @foreach($campiCustom as $campo)
                        @include('militare.partials._campo_anagrafica', [
                            'militare' => $m,
                            'campo' => $campo,
                            'gradi' => $gradi,
                            'plotoni' => $plotoni,
                            'poli' => $poli,
                            'compagnie' => $compagnie ?? collect()
                        ])
                    @endforeach
                    
                     <td class="text-center">
                         <div class="d-flex justify-content-center gap-1">
                             <a href="{{ route('anagrafica.show', $m->id) }}" class="btn btn-sm btn-outline-primary" title="Visualizza">
                                 <i class="fas fa-eye"></i>
                             </a>
                             @can('anagrafica.edit')
                             <a href="{{ route('anagrafica.edit', $m->id) }}" class="btn btn-sm btn-outline-warning" title="Modifica">
                                 <i class="fas fa-edit"></i>
                             </a>
                             @endcan
                             @can('anagrafica.delete')
                             <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $m->id }})" title="Elimina">
                                 <i class="fas fa-trash"></i>
                             </button>
                             @endcan
                         </div>
                     </td>
                </tr>
            @empty
                @include('components.no-results', ['showButton' => $hasActiveFilters, 'buttonUrl' => route('anagrafica.index')])
            @endforelse
        </tbody>
    </table>
</div>


<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcelBtn" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

<!-- Modal per conferma eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="mb-4">
          <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="fas fa-trash-alt text-danger" style="font-size: 2rem;"></i>
          </div>
        </div>
        
        <h4 class="mb-3">Eliminare questo militare?</h4>
        <p class="text-muted mb-2">Stai per eliminare:</p>
        <h5 class="fw-bold mb-4" id="militare-to-delete"></h5>
        
        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 mb-4">
          <small><i class="fas fa-exclamation-circle me-1"></i> Questa azione Ã¨ irreversibile</small>
        </div>
        
        <div class="d-grid gap-2">
          <form id="deleteForm" action="" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger w-100 mb-2">
                <i class="fas fa-trash-alt me-2"></i>SÃ¬, Elimina
              </button>
          </form>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<!-- Script per inizializzazione moduli -->
<script>
// Funzione per confermare l'eliminazione del militare
function confirmDelete(militareId) {
    // Ottieni i dati del militare dalla riga
    const row = document.getElementById('militare-' + militareId);
    if (!row) {
        console.error('Riga militare non trovata:', militareId);
        return;
    }
    
    const cognome = row.querySelector('a.link-name').textContent.trim();
    const grado = row.querySelector('select[data-field="grado_id"] option:checked').textContent.trim();
    
    // Imposta il nome del militare nel modal
    document.getElementById('militare-to-delete').textContent = grado + ' ' + cognome;
    
    // Imposta l'action del form di eliminazione con la rotta corretta
    const deleteForm = document.getElementById('deleteForm');
    const deleteUrl = '{{ url("anagrafica") }}/' + militareId;
    deleteForm.action = deleteUrl;
    
    // Mostra il modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Forza l'inizializzazione dei filtri se non già fatto
    if (window.SUGECO && window.SUGECO.Filters) {
        window.SUGECO.Filters.init();
    }
    
    // Inizializza il nuovo sistema di ricerca
    if (window.SUGECO && window.SUGECO.Search) {
        window.SUGECO.Search.init();
    }
    
    // ============================
    // GESTIONE FILTRI AJAX
    // ============================
    
    const compagniaSelect = document.getElementById('filter_compagnia');
    const plotoneSelect = document.getElementById('filter_plotone_id');
    const allFilterSelects = document.querySelectorAll('.filter-ajax');
    const militariTableBody = document.getElementById('militariTableBody');
    
    // Funzione per filtrare i plotoni in base alla compagnia selezionata
    function updatePlotoniFilter(compagniaId) {
        const options = plotoneSelect.querySelectorAll('option');
        let hasVisibleOptions = false;
        
        options.forEach(option => {
            if (option.value === '') {
                // Aggiorna il placeholder
                if (!compagniaId) {
                    option.textContent = 'Seleziona prima una compagnia';
                } else {
                    option.textContent = 'Tutti i plotoni';
                }
                option.style.display = '';
                return;
            }
            
            const optionCompagniaId = option.getAttribute('data-compagnia-id');
            
            if (!compagniaId || optionCompagniaId == compagniaId) {
                option.style.display = '';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
            }
        });
        
        // Abilita/disabilita il select
        plotoneSelect.disabled = !compagniaId;
        
        // Se il plotone selezionato non appartiene alla compagnia, resetta
        if (compagniaId && plotoneSelect.value) {
            const selectedOption = plotoneSelect.querySelector(`option[value="${plotoneSelect.value}"]`);
            if (selectedOption && selectedOption.getAttribute('data-compagnia-id') != compagniaId) {
                plotoneSelect.value = '';
            }
        }
    }
    
    // Listener per cambio compagnia
    if (compagniaSelect) {
        compagniaSelect.addEventListener('change', function() {
            updatePlotoniFilter(this.value);
            applyFiltersClientSide();
        });
        
        // Inizializza stato plotoni al caricamento
        updatePlotoniFilter(compagniaSelect.value);
    }
    
    // Funzione per applicare i filtri lato client (usa data-attributes)
    function applyFiltersClientSide() {
        const rows = militariTableBody.querySelectorAll('tr.militare-row');
        const filters = {};
        
        // Raccogli tutti i filtri attivi
        allFilterSelects.forEach(select => {
            const name = select.name;
            const value = select.value;
            if (value) {
                filters[name] = value;
            }
        });
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            // Filtro Compagnia (usa data-attribute)
            if (show && filters.compagnia) {
                if (row.dataset.compagniaId !== filters.compagnia) {
                    show = false;
                }
            }
            
            // Filtro Plotone (usa data-attribute)
            if (show && filters.plotone_id) {
                if (row.dataset.plotoneId !== filters.plotone_id) {
                    show = false;
                }
            }
            
            // Filtro Grado (usa data-attribute)
            if (show && filters.grado_id) {
                if (row.dataset.gradoId !== filters.grado_id) {
                    show = false;
                }
            }
            
            // Filtro Ufficio/Polo (usa data-attribute)
            if (show && filters.polo_id) {
                if (row.dataset.poloId !== filters.polo_id) {
                    show = false;
                }
            }
            
            // Filtro Incarico/Mansione (usa data-attribute)
            if (show && filters.mansione_id) {
                if (row.dataset.mansioneId !== filters.mansione_id) {
                    show = false;
                }
            }
            
            // Filtro NOS (usa data-attribute)
            if (show && filters.nos_status) {
                if (row.dataset.nosStatus !== filters.nos_status) {
                    show = false;
                }
            }
            
            // Filtro Email Istituzionale (usa data-attribute)
            if (show && filters.email_istituzionale) {
                const hasEmail = row.dataset.email === '1';
                if (filters.email_istituzionale === 'registrata' && !hasEmail) {
                    show = false;
                } else if (filters.email_istituzionale === 'non_registrata' && hasEmail) {
                    show = false;
                }
            }
            
            // Filtro Telefono (usa data-attribute)
            if (show && filters.telefono) {
                const hasTel = row.dataset.telefono === '1';
                if (filters.telefono === 'registrato' && !hasTel) {
                    show = false;
                } else if (filters.telefono === 'non_registrato' && hasTel) {
                    show = false;
                }
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // Mostra/nascondi riga "nessun risultato"
        let noResultsRow = militariTableBody.querySelector('.no-results-row');
        if (visibleCount === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="20" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-users-slash fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun militare trovato</p>
                            <p class="text-muted mb-3">Prova a modificare i criteri di ricerca o i filtri applicati.</p>
                            <button type="button" class="btn btn-outline-primary mt-2" onclick="resetAllFilters()">
                                Rimuovi tutti i filtri
                            </button>
                        </div>
                    </td>
                `;
                militariTableBody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else {
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
        
        // Aggiorna URL senza ricaricare
        updateUrlParams(filters);
        
        // Aggiorna UI filtri (clear buttons, applied class, rimuovi tutti)
        updateFiltersUI();
    }
    
    // Funzione per aggiornare l'URL con i parametri dei filtri
    function updateUrlParams(filters) {
        const url = new URL(window.location);
        
        // Rimuovi tutti i parametri dei filtri
        ['compagnia', 'plotone_id', 'grado_id', 'polo_id', 'mansione_id', 'nos_status', 'email_istituzionale', 'telefono'].forEach(param => {
            url.searchParams.delete(param);
        });
        
        // Aggiungi i filtri attivi
        Object.entries(filters).forEach(([key, value]) => {
            if (value) {
                url.searchParams.set(key, value);
            }
        });
        
        window.history.replaceState({}, '', url);
    }
    
    // Funzione per aggiornare la UI dei filtri attivi
    function updateFiltersUI() {
        let activeCount = 0;
        
        allFilterSelects.forEach(select => {
            const wrapper = select.closest('.select-wrapper');
            const clearBtn = wrapper?.querySelector('.clear-filter');
            
            if (select.value) {
                activeCount++;
                select.classList.add('applied');
                if (clearBtn) clearBtn.style.display = '';
            } else {
                select.classList.remove('applied');
                if (clearBtn) clearBtn.style.display = 'none';
            }
        });
        
        // Aggiorna pulsante "Rimuovi tutti"
        const removeAllBtn = document.querySelector('.reset-all-filters');
        if (removeAllBtn) {
            if (activeCount > 0) {
                removeAllBtn.style.display = '';
                removeAllBtn.textContent = `Rimuovi tutti i filtri (${activeCount})`;
            } else {
                removeAllBtn.style.display = 'none';
            }
        }
    }
    
    // Funzione globale per resettare i filtri
    window.resetAllFilters = function() {
        allFilterSelects.forEach(select => {
            select.value = '';
            select.classList.remove('applied');
        });
        updatePlotoniFilter('');
        applyFiltersClientSide();
    };
    
    // Listener per tutti i filtri
    allFilterSelects.forEach(select => {
        if (select.id !== 'filter_compagnia') { // Compagnia già gestita
            select.addEventListener('change', applyFiltersClientSide);
        }
    });
    
    // Listener per clear buttons singoli
    document.querySelectorAll('#filtroForm .clear-filter').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const filterName = this.dataset.filter;
            const select = document.getElementById('filter_' + filterName) || 
                          document.querySelector(`[name="${filterName}"]`);
            
            if (select) {
                select.value = '';
                select.classList.remove('applied');
                
                // Se è compagnia, reset anche plotone
                if (filterName === 'compagnia') {
                    updatePlotoniFilter('');
                }
                
                applyFiltersClientSide();
            }
            
            this.style.display = 'none';
        });
    });
    
    // Listener per pulsante "Rimuovi tutti i filtri"
    const removeAllBtn = document.querySelector('.reset-all-filters');
    if (removeAllBtn) {
        removeAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            resetAllFilters();
        });
    }
    
    // Previeni submit del form
    const filtroForm = document.getElementById('filtroForm');
    if (filtroForm) {
        filtroForm.addEventListener('submit', e => e.preventDefault());
    }
    
    // Inizializza UI filtri al caricamento
    updateFiltersUI();
    
    // Export Excel con filtri attivi - esporta solo righe visibili
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Raccoglie gli ID dei militari attualmente visibili
            const visibleRows = militariTableBody.querySelectorAll('tr.militare-row:not([style*="display: none"])');
            const militareIds = [];
            
            visibleRows.forEach(row => {
                const id = row.getAttribute('data-militare-id');
                if (id) militareIds.push(id);
            });
            
            const baseUrl = '{{ route("anagrafica.export-excel") }}';
            const totalRows = militariTableBody.querySelectorAll('tr.militare-row').length;
            
            // Se ci sono filtri attivi (meno righe visibili del totale), passa gli IDs
            if (militareIds.length > 0 && militareIds.length < totalRows) {
                window.location.href = baseUrl + '?ids=' + militareIds.join(',');
            } else {
                // Nessun filtro attivo, esporta tutto
                window.location.href = baseUrl;
            }
        });
    }
    
    // Gestione editing inline dei campi
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('editable-field')) {
            const field = e.target.getAttribute('data-field');
            const militareId = e.target.getAttribute('data-militare-id');
            const value = e.target.value;
            
            // Invia la modifica via AJAX (usa la rotta dalla riga per evitare 404)
            const row = e.target.closest('tr.militare-row');
            const updateUrl = row ? row.getAttribute('data-update-url') : null;
            if (!updateUrl) {
                console.error('URL aggiornamento non trovato sulla riga');
                return;
            }
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    field: field,
                    value: value
                })
            })
            .then(async response => {
                // Gestione specifica per 403 (permessi mancanti)
                if (response.status === 403) {
                    const data = await response.json();
                    throw new Error(data.message || 'Non hai i permessi per eseguire questa azione');
                }
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `Errore HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostra un feedback visivo
                    e.target.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        e.target.style.backgroundColor = '';
                    }, 1000);
                    
                    // Se Ã¨ stata cambiata la compagnia, resetta e filtra i plotoni
                    if (field === 'compagnia' && data.plotone_reset) {
                        const rowId = e.target.getAttribute('data-row-id');
                        const plotoneSelect = document.querySelector(`.plotone-select[data-row-id="${rowId}"]`);
                        if (plotoneSelect) {
                            plotoneSelect.value = ''; // Resetta il plotone
                            filterPlotoniByCompagnia(plotoneSelect, value); // Filtra i plotoni per la nuova compagnia
                        }
                    }
                } else {
                    // Feedback visivo negativo
                    e.target.style.backgroundColor = '#f8d7da';
                    setTimeout(() => {
                        e.target.style.backgroundColor = '';
                    }, 2000);
                    
                    // Messaggio non invasivo (solo console)
                    }
            })
            .catch(error => {
                console.error('Errore:', error);
                
                // Feedback visivo per errore
                e.target.style.backgroundColor = '#f8d7da';
                setTimeout(() => {
                    e.target.style.backgroundColor = '';
                }, 2000);
                
                // Mostra il messaggio di errore se disponibile
                if (error.message) {
                    console.error('Dettaglio errore:', error.message);
                }
            });
        }
        
        // Gestione patenti checkbox
        if (e.target.classList.contains('patente-checkbox')) {
            const militareId = e.target.getAttribute('data-militare-id');
            const patente = e.target.getAttribute('data-patente');
            const isChecked = e.target.checked;
            const checkbox = e.target;
            const formCheck = checkbox.closest('.form-check');
            
            const url = '{{ url("anagrafica") }}/' + militareId + '/patenti/' + (isChecked ? 'add' : 'remove');
            
            // Feedback visivo immediato
            formCheck.style.transition = 'transform 0.2s ease';
            formCheck.style.transform = 'scale(1.05)';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    patente: patente
                })
            })
            .then(response => {
                // Gestione specifica per 403 (permessi mancanti)
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Non hai i permessi per eseguire questa azione');
                    });
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Feedback minimal - solo ripristina scala
                    formCheck.style.transform = 'scale(1)';
                } else {
                    // Ripristina lo stato precedente in caso di errore
                    checkbox.checked = !isChecked;
                    formCheck.style.transform = 'scale(1)';
                    
                    // Messaggio non invasivo (solo console)
                    }
            })
            .catch(error => {
                console.error('Errore durante aggiornamento patente:', error);
                
                // Ripristina lo stato precedente
                checkbox.checked = !isChecked;
                formCheck.style.transform = 'scale(1)';
                
                // Non mostrare piÃ¹ alert, solo console per debugging
            });
        }
        
        // Gestione istituti checkbox
        if (e.target.classList.contains('istituto-input')) {
            const militareId = e.target.getAttribute('data-militare-id');
            const istituto = e.target.value;
            const istitutiContainer = e.target.closest('.istituti-container');
            const checkboxes = istitutiContainer.querySelectorAll('.istituto-input');
            
            // Raccogli tutti gli istituti selezionati
            const istitutiSelezionati = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            
            // Trova la riga per l'URL
            const row = e.target.closest('tr.militare-row');
            const updateUrl = row ? row.getAttribute('data-update-url') : null;
            if (!updateUrl) {
                console.error('URL aggiornamento non trovato sulla riga');
                return;
            }
            
            // Feedback visivo
            istitutiContainer.style.opacity = '0.6';
            
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    field: 'istituti',
                    value: istitutiSelezionati
                })
            })
            .then(response => {
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Non hai i permessi per eseguire questa azione');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Feedback visivo positivo
                    istitutiContainer.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        istitutiContainer.style.backgroundColor = '';
                        istitutiContainer.style.opacity = '1';
                    }, 1000);
                } else {
                    // Feedback visivo negativo
                    istitutiContainer.style.backgroundColor = '#f8d7da';
                    setTimeout(() => {
                        istitutiContainer.style.backgroundColor = '';
                        istitutiContainer.style.opacity = '1';
                    }, 2000);
                    }
            })
            .catch(error => {
                console.error('Errore:', error);
                istitutiContainer.style.backgroundColor = '#f8d7da';
                setTimeout(() => {
                    istitutiContainer.style.backgroundColor = '';
                    istitutiContainer.style.opacity = '1';
                }, 2000);
            });
        }
    });
    
    // Funzione per filtrare i plotoni in base alla compagnia
    function filterPlotoniByCompagnia(plotoneSelect, compagniaId) {
        const options = plotoneSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') {
                // Mantieni sempre l'opzione vuota "--"
                option.style.display = '';
                return;
            }
            
            const optionCompagniaId = option.getAttribute('data-compagnia-id');
            
            if (!compagniaId || optionCompagniaId == compagniaId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    // Inizializza i filtri dei plotoni all'avvio
    document.querySelectorAll('.plotone-select').forEach(plotoneSelect => {
        const rowId = plotoneSelect.getAttribute('data-row-id');
        const compagniaSelect = document.querySelector(`.compagnia-select[data-row-id="${rowId}"]`);
        
        if (compagniaSelect) {
            const compagniaId = compagniaSelect.value;
            if (compagniaId) {
                filterPlotoniByCompagnia(plotoneSelect, compagniaId);
            }
        }
    });
    
    // ============================
    // GESTIONE CAMPI CUSTOM DINAMICI
    // ============================
    
    // Listener per campi custom (select, input, checkbox, textarea)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('campo-custom-field')) {
            const militareId = e.target.dataset.militareId;
            const nomeCampo = e.target.dataset.campoNome;
            let valore;
            
            // Gestisci checkbox
            if (e.target.type === 'checkbox') {
                // Se ci sono piÃ¹ checkbox per lo stesso campo (checkbox multipli con opzioni)
                const allCheckboxes = document.querySelectorAll(`.campo-custom-field[data-campo-nome="${nomeCampo}"][data-militare-id="${militareId}"]`);
                
                if (allCheckboxes.length > 1) {
                    // Raccogli tutti i valori selezionati
                    const valoriSelezionati = Array.from(allCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);
                    valore = valoriSelezionati.join(',');
                } else {
                    // Checkbox singolo
                    valore = e.target.checked ? '1' : '0';
                }
            } else {
                valore = e.target.value;
            }
            
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch(`{{ url('anagrafica') }}/${militareId}/update-campo-custom`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome_campo: nomeCampo,
                    valore: valore
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Feedback visivo con bordo verde
                    window.SUGECO.showSaveFeedback(e.target, true, 2000);
                } else {
                    // Feedback visivo con bordo rosso
                    window.SUGECO.showSaveFeedback(e.target, false, 2000);
                    console.error('Errore salvataggio campo custom:', data.message);
                }
            })
            .catch(error => {
                window.SUGECO.showSaveFeedback(e.target, false, 2000);
                console.error('Errore:', error);
            });
        }
    });
});
</script>
<!-- File JavaScript per pagina militare -->
<script src="{{ asset('js/militare.js') }}"></script>
@endpush

