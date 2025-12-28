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
   STILI TABELLA ORIGINALI
   ======================================== */
/* Effetto hover sulle righe come nel CPT */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

/* Assicura che l'hover funzioni anche con le celle inline */
.table tbody tr:hover td {
    background-color: transparent !important;
}

/* Bordi squadrati per le celle come nel CPT */
.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
}

/* Uniforma gli stili dei form controls */
.form-control, .form-select {
    border-radius: 0 !important;
}

/* Stili per i filtri come nel CPT */
.filter-select {
    border-radius: 0 !important;
}

/* Assicura che la tabella abbia lo stesso comportamento del CPT */
.table-container {
    overflow-x: auto !important;
    overflow-y: auto !important;
}

.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed !important;
}

/* Sfondo leggermente off-white per la tabella */
.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

/* Bordi leggermente piÃ¹ scuri dell'hover */
.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Stili per i link come nel CPT */
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
    foreach(['compagnia', 'plotone_id', 'grado_id', 'polo_id', 'mansione_id', 'nos_status', 'ruolo_id', 'email_istituzionale', 'telefono', 'presenza', 'compleanno'] as $filter) {
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
        <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
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
            <div>
                <i class="fas fa-filter me-2"></i> Filtri avanzati
            </div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('anagrafica.index') }}" method="GET">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro Compagnia --}}
                    <div class="col-md-3">
                        <label for="compagnia" class="form-label">
                            <i class="fas fa-flag me-1"></i> Compagnia
                        </label>
                        <div class="select-wrapper">
                            <select name="compagnia" id="compagnia" class="form-select filter-select {{ request()->filled('compagnia') ? 'applied' : '' }}">
                                <option value="">Tutte le compagnie</option>
                                @foreach($compagnie as $compagnia)
                                    <option value="{{ $compagnia->id }}" {{ request('compagnia') == $compagnia->id ? 'selected' : '' }}>
                                        {{ $compagnia->numero ?? $compagnia->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('compagnia'))
                                <span class="clear-filter" data-filter="compagnia" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
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
                    
                    {{-- Filtro Ufficio (Polo) --}}
                    <div class="col-md-3">
                        <label for="polo_id" class="form-label">
                            <i class="fas fa-building me-1"></i> Ufficio
                        </label>
                        <div class="select-wrapper">
                            <select name="polo_id" id="polo_id" class="form-select filter-select {{ request()->filled('polo_id') ? 'applied' : '' }}">
                                <option value="">Tutti gli uffici</option>
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
                
                {{-- Seconda riga filtri --}}
                <div class="row">
                    {{-- Filtro Incarico (Mansione) --}}
                    <div class="col-md-3">
                        <label for="mansione_id" class="form-label">
                            <i class="fas fa-briefcase me-1"></i> Incarico
                        </label>
                        <div class="select-wrapper">
                            <select name="mansione_id" id="mansione_id" class="form-select filter-select {{ request()->filled('mansione_id') ? 'applied' : '' }}">
                                <option value="">Tutti gli incarichi</option>
                                @foreach($mansioni as $mansione)
                                    <option value="{{ $mansione->id }}" {{ request('mansione_id') == $mansione->id ? 'selected' : '' }}>
                                        {{ $mansione->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('mansione_id'))
                                <span class="clear-filter" data-filter="mansione_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro NOS --}}
                    <div class="col-md-3">
                        <label for="nos_status" class="form-label">
                            <i class="fas fa-check-circle me-1"></i> NOS
                        </label>
                        <div class="select-wrapper">
                            <select name="nos_status" id="nos_status" class="form-select filter-select {{ request()->filled('nos_status') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="si" {{ request('nos_status') == 'si' ? 'selected' : '' }}>SI</option>
                                <option value="no" {{ request('nos_status') == 'no' ? 'selected' : '' }}>NO</option>
                                <option value="da richiedere" {{ request('nos_status') == 'da richiedere' ? 'selected' : '' }}>Da Richiedere</option>
                                <option value="non previsto" {{ request('nos_status') == 'non previsto' ? 'selected' : '' }}>Non Previsto</option>
                                <option value="in attesa" {{ request('nos_status') == 'in attesa' ? 'selected' : '' }}>In Attesa</option>
                            </select>
                            @if(request()->filled('nos_status'))
                                <span class="clear-filter" data-filter="nos_status" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Email Istituzionale --}}
                    <div class="col-md-3">
                        <label for="email_istituzionale" class="form-label">
                            <i class="fas fa-envelope me-1"></i> Email Istituzionale
                        </label>
                        <div class="select-wrapper">
                            <select name="email_istituzionale" id="email_istituzionale" class="form-select filter-select {{ request()->filled('email_istituzionale') ? 'applied' : '' }}">
                                <option value="">Tutte</option>
                                <option value="registrata" {{ request('email_istituzionale') == 'registrata' ? 'selected' : '' }}>Registrata</option>
                                <option value="non_registrata" {{ request('email_istituzionale') == 'non_registrata' ? 'selected' : '' }}>Non Registrata</option>
                            </select>
                            @if(request()->filled('email_istituzionale'))
                                <span class="clear-filter" data-filter="email_istituzionale" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Cellulare --}}
                    <div class="col-md-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone me-1"></i> Cellulare
                        </label>
                        <div class="select-wrapper">
                            <select name="telefono" id="telefono" class="form-select filter-select {{ request()->filled('telefono') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="registrato" {{ request('telefono') == 'registrato' ? 'selected' : '' }}>Registrato</option>
                                <option value="non_registrato" {{ request('telefono') == 'non_registrato' ? 'selected' : '' }}>Non Registrato</option>
                            </select>
                            @if(request()->filled('telefono'))
                                <span class="clear-filter" data-filter="telefono" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Terza riga filtri (Ruolo) --}}
                <div class="row mb-3">
                    {{-- Filtro Ruolo --}}
                    <div class="col-md-3">
                        <label for="ruolo_id" class="form-label">
                            <i class="fas fa-user-tag me-1"></i> Ruolo
                        </label>
                        <div class="select-wrapper">
                            <select name="ruolo_id" id="ruolo_id" class="form-select filter-select {{ request()->filled('ruolo_id') ? 'applied' : '' }}">
                                <option value="">Tutti i ruoli</option>
                                @foreach($ruoli as $ruolo)
                                    <option value="{{ $ruolo->id }}" {{ request('ruolo_id') == $ruolo->id ? 'selected' : '' }}>
                                        {{ $ruolo->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('ruolo_id'))
                                <span class="clear-filter" data-filter="ruolo_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('anagrafica.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table contenente i militari -->
<!-- Tabella con intestazione fissa e scroll -->
@php
    // Calcola la larghezza totale della tabella basandosi sui campi attivi
    $larghezzeColonne = [
        'compagnia' => 160,
        'grado' => 200,
        'cognome' => 230,
        'nome' => 170,
        'plotone' => 190,
        'ufficio' => 190,
        'incarico' => 210,
        'patenti' => 180,
        'nos' => 140,
        'anzianita' => 180,  // Aumentata per mostrare date complete
        'data_nascita' => 180,  // Aumentata per mostrare date complete
        'email_istituzionale' => 270,
        'telefono' => 210,
        'codice_fiscale' => 200,
        'istituti' => 350,
    ];
    $larghezzaTotale = 150; // Azioni
    foreach($campiCustom as $campo) {
        $larghezzaTotale += $larghezzeColonne[$campo->nome_campo] ?? 180;
    }
@endphp
<div class="table-container" style="position: relative; height: 600px; overflow: auto; overflow-x: auto;">
    <!-- Intestazione fissa -->
     <div class="table-header-fixed" style="position: sticky; top: 0; z-index: 10; background: white;">
         <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: {{ $larghezzaTotale }}px; min-width: {{ $larghezzaTotale }}px;">
             <colgroup>
                 @foreach($campiCustom as $campo)
                 <col style="width:{{ $larghezzeColonne[$campo->nome_campo] ?? 180 }}px">
                 @endforeach
                 <col style="width:150px">
             </colgroup>
            <thead class="table-dark" style="user-select:none;">
                <tr>
                    @foreach($campiCustom as $campo)
                    <th class="text-center">{{ $campo->etichetta }}</th>
                    @endforeach
                    <th class="text-center">Azioni</th>
                </tr>
            </thead>
        </table>
    </div>
    
    <!-- Corpo scrollabile -->
     <div class="table-body-scroll">
         <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: {{ $larghezzaTotale }}px; min-width: {{ $larghezzaTotale }}px;">
             <colgroup>
                 @foreach($campiCustom as $campo)
                 <col style="width:{{ $larghezzeColonne[$campo->nome_campo] ?? 180 }}px">
                 @endforeach
                 <col style="width:150px">
             </colgroup>
            <tbody id="militariTableBody">
            @forelse($militari as $m)
                @php
                    $isAcquired = $m->isAcquiredBy(auth()->user());
                    $isReadOnly = $m->isReadOnlyFor(auth()->user());
                @endphp
                <tr id="militare-{{ $m->id }}" 
                    class="militare-row {{ $isAcquired ? 'acquired-militare' : '' }}" 
                    data-militare-id="{{ $m->id }}" 
                    data-update-url="{{ route('anagrafica.update-field', $m->id) }}"
                    data-read-only="{{ $isReadOnly ? 'true' : 'false' }}"
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
                <tr>
                    <td colspan="14" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun militare trovato.</p>
                            <a href="{{ route('anagrafica.index') }}" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri
                            </a>
                        </div>
                    </td>
                </tr>
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
    // Forza l'inizializzazione dei filtri se non giÃ  fatto
    if (window.SUGECO && window.SUGECO.Filters) {
        window.SUGECO.Filters.init();
    }
    
    // Inizializza il nuovo sistema di ricerca
    if (window.SUGECO && window.SUGECO.Search) {
        window.SUGECO.Search.init();
    }
    
    // Export Excel
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Raccoglie tutti i parametri del form attuale
            const form = document.getElementById('filtroForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            // Aggiunge tutti i parametri del form
            for (let [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Crea l'URL per l'export
            const exportUrl = '{{ route("anagrafica.export-excel") }}?' + params.toString();
            
            // Redirect per scaricare il file (stesso meccanismo del CPT)
            window.location.href = exportUrl;
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

