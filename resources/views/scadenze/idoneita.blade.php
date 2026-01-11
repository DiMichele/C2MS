@extends('layouts.app')

@section('title', 'Idoneità Sanitarie')

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

.scadenza-prenotato {
    background-color: #cce5ff !important;
    color: #004085 !important;
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

/* Stili filtri - come altre pagine */
.filters-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #dee2e6;
}

.filter-select {
    border-radius: 0 !important;
}

/* Colonna Teatro Operativo */
.teatro-operativo-cell {
    vertical-align: middle;
}

.to-badge {
    margin-bottom: 4px;
}

.to-badge:last-child {
    margin-bottom: 0;
}

.to-badge .badge {
    cursor: help;
}
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">IDONEITÀ SANITARIE</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchMilitare" 
            class="form-control" 
            placeholder="Cerca militare..." 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e badge su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <button id="toggleFilters" class="btn btn-primary" style="border-radius: 6px !important;">
        <i class="fas fa-filter me-2"></i> 
        <span id="toggleFiltersText">Mostra filtri</span>
    </button>
    
    <div class="d-flex gap-2 align-items-center">
        <span class="badge" style="background-color: #d4edda; color: #155724;"><i class="fas fa-check"></i> Valido</span>
        <span class="badge" style="background-color: #fff3cd; color: #856404;"><i class="fas fa-exclamation-triangle"></i> In Scadenza</span>
        <span class="badge" style="background-color: #f8d7da; color: #721c24;"><i class="fas fa-times"></i> Scaduto</span>
        <span class="badge" style="background-color: #cce5ff; color: #004085;"><i class="fas fa-calendar-alt"></i> Prenotato</span>
        <span class="badge" style="background-color: #e9ecef; color: #6c757d;"><i class="fas fa-minus"></i> Mancante</span>
    </div>
</div>

<!-- Sezione Filtri (come Anagrafica) -->
<div id="filtersContainer" class="filter-section" style="display: none;">
    <div class="filter-card mb-4">
        <div class="filter-card-header">
            <i class="fas fa-filter me-2"></i> Filtri avanzati
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" method="GET" action="{{ route('idoneita.index') }}">
                <div class="row mb-3">
                    @if(isset($compagnie) && count($compagnie) > 0)
                    <div class="col-md-3">
                        <label class="form-label">Compagnia:</label>
                        <select name="compagnia_id" class="form-select filter-select" onchange="this.form.submit()">
                            <option value="">Tutte le compagnie</option>
                            @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}" {{ request('compagnia_id') == $compagnia->id ? 'selected' : '' }}>
                                {{ $compagnia->nome }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Idoneità Mansione:</label>
                        <select name="idoneita_mansione" class="form-select filter-select filter-stato" data-campo="idoneita_mansione">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="prenotato">Prenotato</option>
                            <option value="mancante">Mancante</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Idoneità SMI:</label>
                        <select name="idoneita_smi" class="form-select filter-select filter-stato" data-campo="idoneita_smi">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="prenotato">Prenotato</option>
                            <option value="mancante">Mancante</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">ECG:</label>
                        <select name="ecg" class="form-select filter-select filter-stato" data-campo="ecg">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="prenotato">Prenotato</option>
                            <option value="mancante">Mancante</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Prelievi:</label>
                        <select name="prelievi" class="form-select filter-select filter-stato" data-campo="prelievi">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="prenotato">Prenotato</option>
                            <option value="mancante">Mancante</option>
                        </select>
                    </div>
                    <div class="col-md-9 d-flex align-items-end">
                        <button type="button" class="btn btn-secondary" onclick="resetFiltri()">
                            <i class="fas fa-times me-1"></i> Reset Filtri
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabella con scroll orizzontale e verticale -->
<div class="table-container" style="max-height: calc(100vh - 350px); overflow-x: auto; overflow-y: scroll; border: 1px solid #dee2e6; border-radius: 8px;">
    <table class="table table-sm table-bordered table-hover mb-0" id="scadenzeTable" style="min-width: 1200px;">
        <thead style="position: sticky; top: 0; z-index: 10;">
            <tr style="background-color: #0a2342; color: white;">
                <th>Compagnia</th>
                <th>Grado</th>
                <th>Cognome</th>
                <th>Nome</th>
                <th style="min-width: 200px;"><i class="fas fa-fighter-jet me-1"></i>Teatro Operativo</th>
                <th style="min-width: 150px;">Idoneità Mansione</th>
                <th style="min-width: 130px;">Idoneità SMI</th>
                <th style="min-width: 130px;">ECG</th>
                <th style="min-width: 130px;">Prelievi</th>
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
                
                {{-- Colonna Teatro Operativo --}}
                <td class="teatro-operativo-cell" style="padding: 6px 8px;">
                    @if(count($item['teatro_operativo']) > 0)
                        @foreach($item['teatro_operativo'] as $to)
                            <div class="to-badge" title="{{ $to['title'] }}&#10;Dal: {{ \Carbon\Carbon::parse($to['start_date'])->format('d/m/Y') }}{{ $to['end_date'] ? ' al ' . \Carbon\Carbon::parse($to['end_date'])->format('d/m/Y') : '' }}">
                                <span class="badge bg-danger" style="font-size: 0.75rem; white-space: nowrap;">
                                    <i class="fas fa-fighter-jet me-1"></i>{{ Str::limit($to['title'], 20) }}
                                </span>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">
                                    {{ \Carbon\Carbon::parse($to['start_date'])->format('d/m') }}
                                    @if($to['end_date'])
                                        - {{ \Carbon\Carbon::parse($to['end_date'])->format('d/m') }}
                                    @endif
                                </small>
                            </div>
                        @endforeach
                    @else
                        <span class="text-muted" style="font-size: 0.8rem;">-</span>
                    @endif
                </td>
                
                @foreach(['idoneita_mansione', 'idoneita_smi', 'ecg', 'prelievi'] as $campo)
                @php
                    $scadenza = $item[$campo];
                    
                    // Verifica se è una prenotazione (data conseguimento nel futuro)
                    $isPrenotato = $scadenza['data_conseguimento'] && $scadenza['data_conseguimento']->isFuture();
                    
                    if ($isPrenotato) {
                        $classeColore = 'scadenza-prenotato';
                        $statoEffettivo = 'prenotato';
                    } else {
                        $classeColore = match($scadenza['stato']) {
                            'valido' => 'scadenza-valido',
                            'in_scadenza' => 'scadenza-in-scadenza',
                            'scaduto' => 'scadenza-scaduto',
                            'mancante' => 'scadenza-mancante',
                            default => 'scadenza-mancante'
                        };
                        $statoEffettivo = $scadenza['stato'];
                    }
                    
                    $testoData = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('d/m/Y') : 'Non presente';
                    $campoDB = match($campo) {
                        'idoneita_mansione' => 'idoneita_mans_data_conseguimento',
                        'idoneita_smi' => 'idoneita_smi_data_conseguimento',
                        'ecg' => 'ecg_data_conseguimento',
                        'prelievi' => 'prelievi_data_conseguimento',
                        default => $campo . '_data_conseguimento'
                    };
                    $dataScadenzaISO = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('Y-m-d') : '';
                @endphp
                
                <td class="scadenza-cell {{ $classeColore }}" 
                    data-militare-id="{{ $item['militare']->id }}"
                    data-campo="{{ $campoDB }}"
                    data-campo-nome="{{ ucwords(str_replace('_', ' ', $campo)) }}"
                    data-data-conseguimento="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
                    data-data-scadenza="{{ $dataScadenzaISO }}"
                    data-durata="1"
                    data-stato="{{ $statoEffettivo }}"
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
    const filtersContainer = document.getElementById('filtersContainer');
    const toggleText = document.getElementById('toggleFiltersText');
    
    if (filtersContainer.style.display === 'none') {
        filtersContainer.style.display = 'block';
        toggleText.textContent = 'Nascondi filtri';
    } else {
        filtersContainer.style.display = 'none';
        toggleText.textContent = 'Mostra filtri';
    }
});

// Ricerca live
document.getElementById('searchMilitare').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    let visibili = 0;
    rows.forEach(row => {
        const militareNome = row.getAttribute('data-militare-nome');
        if (!militareNome) {
            return;
        }
        
        if (militareNome.toLowerCase().includes(searchTerm)) {
            row.style.display = '';
            visibili++;
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
    const durata = parseInt(cell.getAttribute('data-durata'));
    
    // Ottieni il nome del militare dalla riga
    const row = cell.closest('tr');
    const militareNome = row.getAttribute('data-militare-nome');
    
    document.getElementById('modalTitle').textContent = `Modifica ${campoNome}`;
    document.getElementById('modalMilitareInfo').textContent = militareNome;
    document.getElementById('modalDataConseguimento').value = dataConseguimento;
    document.getElementById('modalDataScadenza').value = dataScadenza;
    
    // Calcolo automatico scadenza quando cambia data conseguimento
    document.getElementById('modalDataConseguimento').onchange = function() {
        if (this.value) {
            const dataConseg = new Date(this.value);
            dataConseg.setFullYear(dataConseg.getFullYear() + durata);
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
    
    fetch(`${baseUrl}/idoneita/${militareId}/update-singola`, {
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
    window.location.href = `${baseUrl}/idoneita/export-excel`;
});

// Inizializzazione filtri
document.addEventListener('DOMContentLoaded', function() {
    // Filtri per stato
    document.querySelectorAll('.filter-stato').forEach(select => {
        select.addEventListener('change', applicaFiltriStato);
    });
});

// Applica filtri per stato scadenze
function applicaFiltriStato() {
    const filtri = {};
    document.querySelectorAll('.filter-stato').forEach(select => {
        const campo = select.dataset.campo;
        const valore = select.value;
        if (valore) {
            filtri[campo] = valore;
        }
    });
    
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    
    rows.forEach(row => {
        let visible = true;
        
        // Mappa indici celle per campo (aggiornata con colonna T.O.)
        const campiMap = {
            'idoneita_mansione': 5,
            'idoneita_smi': 6,
            'ecg': 7,
            'prelievi': 8
        };
        
        for (const [campo, statoRichiesto] of Object.entries(filtri)) {
            const cellIndex = campiMap[campo];
            const cell = row.cells[cellIndex];
            if (cell) {
                const statoCell = cell.dataset.stato;
                if (statoCell !== statoRichiesto) {
                    visible = false;
                    break;
                }
            }
        }
        
        row.style.display = visible ? '' : 'none';
    });
}

// Reset filtri
function resetFiltri() {
    document.querySelectorAll('.filter-stato').forEach(select => {
        select.value = '';
    });
    
    // Rimuovi filtro compagnia dall'URL e ricarica
    const url = new URL(window.location.href);
    url.searchParams.delete('compagnia_id');
    window.location.href = url.toString();
}
</script>
@endpush
