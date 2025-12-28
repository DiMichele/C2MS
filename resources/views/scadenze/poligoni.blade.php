@extends('layouts.app')

@section('title', 'Poligoni - Tiri e Mantenimento')

@section('content')
<style>
/* Container tabella con scroll */
.table-container {
    position: relative;
    background: white;
}

.table-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 5px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 5px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

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

/* Colonne con larghezza minima ma senza sticky */
.table th:nth-child(1),
.table td:nth-child(1) {
    font-weight: 600;
    min-width: 120px !important;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    font-weight: 600;
    min-width: 80px !important;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    font-weight: 600;
    min-width: 140px !important;
}

.table th:nth-child(4),
.table td:nth-child(4) {
    font-weight: 600;
    min-width: 120px !important;
}

/* Celle scadenze - Data colorata con background */
.scadenza-cell {
    text-align: center;
    padding: 10px 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s;
}

.scadenza-cell:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Colori scadenze - BACKGROUND */
.scadenza-valido {
    background-color: #d4edda !important;
    color: #155724 !important;
}

.scadenza-in-scadenza {
    background-color: #fff3cd !important;
    color: #856404 !important;
}

.scadenza-scaduto {
    background-color: #f8d7da !important;
    color: #721c24 !important;
}

.scadenza-mancante {
    background-color: #e9ecef !important;
    color: #6c757d !important;
}

/* Modal hover per modifica */
.scadenza-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 9999;
    min-width: 500px;
}

.scadenza-modal.show {
    display: block;
    animation: fadeIn 0.2s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -45%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
}

.modal-overlay.show {
    display: block;
}

.modal-header-custom {
    border-bottom: 2px solid #0a2342;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-header-custom h5 {
    color: #0a2342;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
}

.modal-militare-info {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #0a2342;
    font-size: 1.05rem;
}

.modal-body-custom {
    margin-bottom: 20px;
}

.modal-body-custom .form-group {
    margin-bottom: 15px;
}

.modal-body-custom label {
    font-weight: 600;
    color: #0a2342;
    margin-bottom: 8px;
    display: block;
}

.modal-body-custom input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    font-size: 1rem;
}

.modal-body-custom input[type="date"]:focus {
    outline: none;
    border-color: #0a2342;
}

.modal-footer-custom {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-save {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
}

.btn-save:hover {
    background: #218838;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel:hover {
    background: #5a6268;
}

/* Link militare con effetto gold */
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

/* Select wrapper per filtri con X di cancellazione */
.select-wrapper {
    position: relative;
}

.select-wrapper .clear-filter {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #dc3545;
    font-size: 12px;
    z-index: 10;
}

.select-wrapper .clear-filter:hover {
    color: #c82333;
}

.filter-select.applied {
    border-color: #0a2342;
    background-color: rgba(10, 35, 66, 0.05);
}
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">POLIGONI - TIRI E MANTENIMENTO</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchInput" 
            class="form-control" 
            placeholder="Cerca militare..." 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e badge su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <button type="button" class="btn btn-primary" id="toggleFilters" style="border-radius: 6px !important;">
        <i class="fas fa-filter me-2"></i> <span id="filterToggleText">Mostra Filtri</span>
    </button>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge" style="background-color: #d4edda; color: #155724;"><i class="fas fa-check"></i> Valido</span>
        <span class="badge" style="background-color: #fff3cd; color: #856404;"><i class="fas fa-exclamation-triangle"></i> In Scadenza</span>
        <span class="badge" style="background-color: #f8d7da; color: #721c24;"><i class="fas fa-times"></i> Scaduto</span>
        <span class="badge" style="background-color: #e9ecef; color: #6c757d;"><i class="fas fa-minus"></i> Mancante</span>
    </div>
</div>

<!-- Sezione Filtri (usa lo stesso stile delle altre pagine) -->
<div class="filter-card mb-4" id="filtersSection" style="display: none;">
    <div class="filter-card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-filter me-2"></i> Filtri avanzati
        </div>
    </div>
    <div class="card-body p-3">
        <form id="filterForm" method="GET" action="{{ route('poligoni.index') }}">
            <div class="row">
                <div class="col mb-2">
                    <label for="tiri_approntamento" class="form-label small">Teatro Operativo</label>
                    <div class="select-wrapper">
                        <select name="tiri_approntamento" id="tiri_approntamento" class="form-select form-select-sm filter-select {{ request()->filled('tiri_approntamento') ? 'applied' : '' }}">
                            <option value="">Tutti</option>
                            <option value="valido" {{ request('tiri_approntamento') == 'valido' ? 'selected' : '' }}>✓ Valido</option>
                            <option value="in_scadenza" {{ request('tiri_approntamento') == 'in_scadenza' ? 'selected' : '' }}>⚠ In Scadenza</option>
                            <option value="scaduto" {{ request('tiri_approntamento') == 'scaduto' ? 'selected' : '' }}>✗ Scaduto</option>
                            <option value="mancante" {{ request('tiri_approntamento') == 'mancante' ? 'selected' : '' }}>- Mancante</option>
                        </select>
                        @if(request()->filled('tiri_approntamento'))
                            <span class="clear-filter" data-filter="tiri_approntamento" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                        @endif
                    </div>
                </div>
                
                <div class="col mb-2">
                    <label for="mantenimento_arma_lunga" class="form-label small">Mant. Arma Lunga</label>
                    <div class="select-wrapper">
                        <select name="mantenimento_arma_lunga" id="mantenimento_arma_lunga" class="form-select form-select-sm filter-select {{ request()->filled('mantenimento_arma_lunga') ? 'applied' : '' }}">
                            <option value="">Tutti</option>
                            <option value="valido" {{ request('mantenimento_arma_lunga') == 'valido' ? 'selected' : '' }}>✓ Valido</option>
                            <option value="in_scadenza" {{ request('mantenimento_arma_lunga') == 'in_scadenza' ? 'selected' : '' }}>⚠ In Scadenza</option>
                            <option value="scaduto" {{ request('mantenimento_arma_lunga') == 'scaduto' ? 'selected' : '' }}>✗ Scaduto</option>
                            <option value="mancante" {{ request('mantenimento_arma_lunga') == 'mancante' ? 'selected' : '' }}>- Mancante</option>
                        </select>
                        @if(request()->filled('mantenimento_arma_lunga'))
                            <span class="clear-filter" data-filter="mantenimento_arma_lunga" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                        @endif
                    </div>
                </div>
                
                <div class="col mb-2">
                    <label for="mantenimento_arma_corta" class="form-label small">Mant. Arma Corta</label>
                    <div class="select-wrapper">
                        <select name="mantenimento_arma_corta" id="mantenimento_arma_corta" class="form-select form-select-sm filter-select {{ request()->filled('mantenimento_arma_corta') ? 'applied' : '' }}">
                            <option value="">Tutti</option>
                            <option value="valido" {{ request('mantenimento_arma_corta') == 'valido' ? 'selected' : '' }}>✓ Valido</option>
                            <option value="in_scadenza" {{ request('mantenimento_arma_corta') == 'in_scadenza' ? 'selected' : '' }}>⚠ In Scadenza</option>
                            <option value="scaduto" {{ request('mantenimento_arma_corta') == 'scaduto' ? 'selected' : '' }}>✗ Scaduto</option>
                            <option value="mancante" {{ request('mantenimento_arma_corta') == 'mancante' ? 'selected' : '' }}>- Mancante</option>
                        </select>
                        @if(request()->filled('mantenimento_arma_corta'))
                            <span class="clear-filter" data-filter="mantenimento_arma_corta" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                        @endif
                    </div>
                </div>
            </div>
            
            @php
                $activeFilters = [];
                foreach(['tiri_approntamento', 'mantenimento_arma_lunga', 'mantenimento_arma_corta'] as $filter) {
                    if(request()->filled($filter)) $activeFilters[] = $filter;
                }
                $activeCount = count($activeFilters);
            @endphp
            
            <div class="d-flex justify-content-center mt-3">
                @if($activeCount > 0)
                <a href="{{ route('poligoni.index') }}" class="btn btn-danger">
                    <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ $activeCount }})
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Tabella con scroll orizzontale e verticale -->
<div class="table-container" style="max-height: calc(100vh - 350px); overflow-x: auto; overflow-y: scroll; border: 1px solid #dee2e6; border-radius: 8px;">
    <table class="table table-sm table-bordered table-hover mb-0" id="scadenzeTable" style="min-width: 1400px;">
        <thead style="position: sticky; top: 0; z-index: 10;">
            <tr style="background-color: #0a2342; color: white;">
                <th>Compagnia</th>
                <th>Grado</th>
                <th>Cognome</th>
                <th>Nome</th>
                <th style="min-width: 160px;">Teatro Operativo</th>
                <th style="min-width: 180px;">Mantenimento Arma Lunga</th>
                <th style="min-width: 180px;">Mantenimento Arma Corta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr data-militare-id="{{ $item['militare']->id }}" 
                data-militare-nome="{{ $item['militare']->grado->abbreviazione ?? '' }} {{ $item['militare']->cognome }} {{ $item['militare']->nome }}">
                <td><strong>{{ $item['militare']->compagnia->nome ?? 'N/A' }}</strong></td>
                <td>{{ $item['militare']->grado->abbreviazione ?? '' }}</td>
                <td>
                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                        {{ $item['militare']->cognome }}
                    </a>
                </td>
                <td>{{ $item['militare']->nome }}</td>
                
                @foreach(['tiri_approntamento', 'mantenimento_arma_lunga', 'mantenimento_arma_corta'] as $campo)
                @php
                    $scadenza = $item[$campo];
                    $classeColore = match($scadenza['stato']) {
                        'valido' => 'scadenza-valido',
                        'in_scadenza' => 'scadenza-in-scadenza',
                        'scaduto' => 'scadenza-scaduto',
                        'mancante' => 'scadenza-mancante',
                        default => 'scadenza-mancante'
                    };
                    $testoData = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('d/m/Y') : 'Non presente';
                    $campoDB = $campo . '_data_conseguimento';
                    $dataScadenzaISO = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('Y-m-d') : '';
                @endphp
                
                <td class="scadenza-cell {{ $classeColore }}" 
                    data-militare-id="{{ $item['militare']->id }}"
                    data-campo="{{ $campoDB }}"
                    data-campo-nome="{{ ucwords(str_replace('_', ' ', $campo)) }}"
                    data-data-conseguimento="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
                    data-data-scadenza="{{ $dataScadenzaISO }}"
                    data-mesi="6"
                    data-stato="{{ $scadenza['stato'] }}"
                    @can('scadenze.edit')
                    onclick="openScadenzaModal(this)"
                    @endcan
                    style="@cannot('scadenze.edit')cursor: default;@endcannot">
                    {{ $testoData }}
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay" onclick="closeScadenzaModal()"></div>

<!-- Modal Modifica Scadenza -->
<div class="scadenza-modal" id="scadenzaModal">
    <div class="modal-header-custom">
        <h5 id="modalTitle">Modifica Scadenza</h5>
    </div>
    <div class="modal-militare-info" id="modalMilitareInfo">
        <!-- Grado Cognome Nome -->
    </div>
    <div class="modal-body-custom">
        <div class="form-group">
            <label for="modalDataConseguimento">Data Conseguimento:</label>
            <input type="date" id="modalDataConseguimento" class="form-control">
        </div>
        <div class="form-group">
            <label for="modalDataScadenza">Data Scadenza:</label>
            <input type="date" id="modalDataScadenza" class="form-control">
        </div>
    </div>
    <div class="modal-footer-custom">
        <button type="button" class="btn-cancel" onclick="closeScadenzaModal()">Annulla</button>
        <button type="button" class="btn-save" onclick="saveScadenza()">Salva</button>
    </div>
</div>

<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

@endsection

@push('scripts')
<script>
let currentCell = null;

// Toggle filtri
document.getElementById('toggleFilters').addEventListener('click', function() {
    const filtersSection = document.getElementById('filtersSection');
    const toggleText = document.getElementById('filterToggleText');
    
    if (filtersSection.style.display === 'none') {
        filtersSection.style.display = 'block';
        toggleText.textContent = 'Nascondi Filtri';
        this.classList.add('active');
    } else {
        filtersSection.style.display = 'none';
        toggleText.textContent = 'Mostra Filtri';
        this.classList.remove('active');
    }
});

// Auto-submit quando cambia un filtro
document.querySelectorAll('.filter-select').forEach(function(select) {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Gestione click sulle X di cancellazione filtri individuali
document.querySelectorAll('.clear-filter').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const filterName = this.getAttribute('data-filter');
        const select = document.getElementById(filterName);
        if (select) {
            select.value = '';
            document.getElementById('filterForm').submit();
        }
    });
});

// Se ci sono filtri attivi, mostra la sezione filtri
@if(request()->filled('tiri_approntamento') || request()->filled('mantenimento_arma_lunga') || request()->filled('mantenimento_arma_corta'))
document.getElementById('filtersSection').style.display = 'block';
document.getElementById('filterToggleText').textContent = 'Nascondi Filtri';
document.getElementById('toggleFilters').classList.add('active');
@endif

// Ricerca live
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    
    rows.forEach(row => {
        const militareNome = row.getAttribute('data-militare-nome').toLowerCase();
        if (militareNome.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function openScadenzaModal(cell) {
    @cannot('scadenze.edit')
    return;
    @endcannot
    
    currentCell = cell;
    const campoNome = cell.getAttribute('data-campo-nome');
    const dataConseguimento = cell.getAttribute('data-data-conseguimento');
    const dataScadenza = cell.getAttribute('data-data-scadenza');
    const mesi = parseInt(cell.getAttribute('data-mesi'));
    
    // Ottieni il nome del militare dalla riga
    const row = cell.closest('tr');
    const militareNome = row.getAttribute('data-militare-nome');
    
    document.getElementById('modalTitle').textContent = `Modifica ${campoNome}`;
    document.getElementById('modalMilitareInfo').textContent = militareNome;
    document.getElementById('modalDataConseguimento').value = dataConseguimento;
    document.getElementById('modalDataScadenza').value = dataScadenza;
    
    // Calcolo automatico scadenza quando cambia data conseguimento (6 mesi)
    document.getElementById('modalDataConseguimento').onchange = function() {
        if (this.value) {
            const dataConseg = new Date(this.value);
            dataConseg.setMonth(dataConseg.getMonth() + mesi);
            document.getElementById('modalDataScadenza').value = dataConseg.toISOString().split('T')[0];
        }
    };
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('scadenzaModal').classList.add('show');
}

function closeScadenzaModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.getElementById('scadenzaModal').classList.remove('show');
    currentCell = null;
}

function saveScadenza() {
    if (!currentCell) {
        console.error('currentCell è null!');
        return;
    }
    
    const militareId = currentCell.getAttribute('data-militare-id');
    const campo = currentCell.getAttribute('data-campo');
    const nuovaData = document.getElementById('modalDataConseguimento').value;
    
    // Salva riferimento per ripristinare dopo errore
    const cellToUpdate = currentCell;
    
    // Feedback visivo
    cellToUpdate.style.opacity = '0.5';
    
    // URL BASE CORRETTA
    const baseUrl = window.location.origin + '/SUGECO/public';
    
    fetch(`${baseUrl}/poligoni/${militareId}/update-singola`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            campo: campo,
            data: nuovaData
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Errore durante l\'aggiornamento');
            if (cellToUpdate) cellToUpdate.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Errore completo:', error);
        alert('Errore durante l\'aggiornamento: ' + error.message);
        if (cellToUpdate) cellToUpdate.style.opacity = '1';
    });
    
    closeScadenzaModal();
}

// Chiudi modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScadenzaModal();
    }
});

// Export Excel
document.getElementById('exportExcel').addEventListener('click', function() {
    window.location.href = '/SUGECO/public/scadenze/poligoni/export-excel';
});
</script>
@endpush
