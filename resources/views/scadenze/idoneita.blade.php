@extends('layouts.app')

@section('title', 'Idoneità Sanitarie')

@section('content')
<style>
/* Stili per scadenza-prenotato sono in table-standard.css */

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
        <span id="toggleFiltersText">Mostra filtri</span>
    </button>
    
    <div class="legenda-scadenze">
        <span class="badge-legenda badge-legenda-valido"><i class="fas fa-check-circle"></i> Valido</span>
        <span class="badge-legenda badge-legenda-in-scadenza"><i class="fas fa-exclamation-triangle"></i> In Scadenza</span>
        <span class="badge-legenda badge-legenda-scaduto"><i class="fas fa-times-circle"></i> Scaduto</span>
        <span class="badge-legenda badge-legenda-prenotato"><i class="fas fa-calendar-check"></i> Prenotato</span>
        <span class="badge-legenda badge-legenda-mancante"><i class="fas fa-minus-circle"></i> Non presente</span>
    </div>
</div>

<!-- Sezione Filtri -->
<div id="filtersContainer" class="filter-section" style="display: none;">
    <div class="filter-card mb-4">
        <div class="filter-card-header">
            Filtri avanzati
        </div>
        <div class="card-body p-3">
            <div class="row mb-3">
                {{-- Filtro Compagnia (server-side) --}}
                @if(isset($compagnie) && count($compagnie) > 0)
                <div class="col-md-3">
                    <label class="form-label">Compagnia:</label>
                    <form id="compagniaForm" method="GET" action="{{ route('idoneita.index') }}" style="margin: 0;">
                        <select name="compagnia_id" class="form-select filter-select" onchange="this.form.submit()">
                            <option value="">Tutte le compagnie</option>
                            @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}" {{ request('compagnia_id') == $compagnia->id ? 'selected' : '' }}>
                                {{ $compagnia->nome }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                </div>
                @endif
                
                {{-- Filtri Stato (client-side JavaScript) --}}
                <div class="col-md-2">
                    <label class="form-label">Idoneità Mansione:</label>
                    <select class="form-select filter-select filter-stato" data-campo="idoneita_mansione">
                        <option value="">Tutti</option>
                        <option value="valido">Valido</option>
                        <option value="in_scadenza">In Scadenza</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="prenotato">Prenotato</option>
                        <option value="mancante">Non presente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Idoneità SMI:</label>
                    <select class="form-select filter-select filter-stato" data-campo="idoneita_smi">
                        <option value="">Tutti</option>
                        <option value="valido">Valido</option>
                        <option value="in_scadenza">In Scadenza</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="prenotato">Prenotato</option>
                        <option value="mancante">Non presente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Idoneità T.O.:</label>
                    <select class="form-select filter-select filter-stato" data-campo="idoneita_to">
                        <option value="">Tutti</option>
                        <option value="valido">Valido</option>
                        <option value="in_scadenza">In Scadenza</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="prenotato">Prenotato</option>
                        <option value="mancante">Non presente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">ECG:</label>
                    <select class="form-select filter-select filter-stato" data-campo="ecg">
                        <option value="">Tutti</option>
                        <option value="valido">Valido</option>
                        <option value="in_scadenza">In Scadenza</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="prenotato">Prenotato</option>
                        <option value="mancante">Non presente</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Prelievi:</label>
                    <select class="form-select filter-select filter-stato" data-campo="prelievi">
                        <option value="">Tutti</option>
                        <option value="valido">Valido</option>
                        <option value="in_scadenza">In Scadenza</option>
                        <option value="scaduto">Scaduto</option>
                        <option value="prenotato">Prenotato</option>
                        <option value="mancante">Non presente</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="resetFiltriStato()" title="Reset filtri stato">
                        Reset
                    </button>
                </div>
            </div>
            
            {{-- Contatore risultati filtrati --}}
            <div class="row">
                <div class="col-12">
                    <small class="text-muted" id="filtroContatore">
                        Visualizzati: <span id="contatoreVisibili">{{ count($data) }}</span> / {{ count($data) }} militari
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabella con scroll orizzontale e verticale con navigazione -->
<div class="sugeco-table-nav-container" data-table-nav="auto">
    <div class="sugeco-table-wrapper">
        <table class="sugeco-table" id="scadenzeTable">
        <thead>
            <tr>
                <th>Compagnia</th>
                <th>Grado</th>
                <th>Cognome</th>
                <th>Nome</th>
                <th>Teatro Operativo</th>
                <th>Idoneità Mansione</th>
                <th>Idoneità SMI</th>
                <th>Idoneità T.O.</th>
                <th>ECG</th>
                <th>Prelievi</th>
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
                <td class="teatro-operativo-cell">
                    @if(count($item['teatro_operativo']) > 0)
                        @foreach($item['teatro_operativo'] as $to)
                            <div class="to-badge" title="{{ $to['title'] }}&#10;Dal: {{ \Carbon\Carbon::parse($to['start_date'])->format('d/m/Y') }}{{ $to['end_date'] ? ' al ' . \Carbon\Carbon::parse($to['end_date'])->format('d/m/Y') : '' }}">
                                <span class="badge bg-danger" style="font-size: 0.75rem; white-space: nowrap;">
                                    {{ Str::limit($to['title'], 25) }}
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
                
                @foreach(['idoneita_mansione', 'idoneita_smi', 'idoneita_to', 'ecg', 'prelievi'] as $campo)
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
                        'idoneita_to' => 'idoneita_to_data_conseguimento',
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
    const durata = parseInt(currentCell.getAttribute('data-durata') || 1);
    
    // Salva riferimento per ripristinare dopo errore
    const cellToUpdate = currentCell;
    
    // Feedback visivo - aggiungi spinner
    const originalContent = cellToUpdate.innerHTML;
    cellToUpdate.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    cellToUpdate.style.opacity = '0.7';
    
    // Chiudi subito il modal
    closeScadenzaModal();
    
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
            return response.json().then(err => {
                throw new Error(err.message || `Errore HTTP ${response.status}`);
            }).catch(() => {
                throw new Error(`Errore HTTP ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.scadenza) {
            // Aggiorna la cella in-place senza reload
            updateCellFromResponse(cellToUpdate, data.scadenza, nuovaData, durata);
            
            // Mostra toast di successo
            showToast('Scadenza aggiornata con successo', 'success');
        } else {
            throw new Error(data.message || 'Errore durante l\'aggiornamento');
        }
    })
    .catch(error => {
        console.error('Errore completo:', error);
        // Ripristina contenuto originale
        cellToUpdate.innerHTML = originalContent;
        cellToUpdate.style.opacity = '1';
        showToast('Errore: ' + error.message, 'error');
    });
}

/**
 * Aggiorna la cella con i dati della risposta senza ricaricare la pagina
 */
function updateCellFromResponse(cell, scadenza, dataConseguimento, durata) {
    // Rimuovi tutte le classi di stato precedenti
    cell.classList.remove('scadenza-valido', 'scadenza-in-scadenza', 'scadenza-scaduto', 'scadenza-mancante', 'scadenza-prenotato');
    
    // Aggiorna attributi data
    cell.setAttribute('data-data-conseguimento', dataConseguimento || '');
    cell.setAttribute('data-stato', scadenza.stato);
    
    // Verifica se è una prenotazione (data conseguimento nel futuro)
    const isPrenotato = dataConseguimento && new Date(dataConseguimento) > new Date();
    
    if (isPrenotato) {
        cell.setAttribute('data-stato', 'prenotato');
        cell.classList.add('scadenza-prenotato');
    }
    
    if (scadenza.data_scadenza) {
        // Formatta la data per la visualizzazione (dd/mm/yyyy)
        const dataScadenza = new Date(scadenza.data_scadenza);
        const dataScadenzaFormatted = dataScadenza.toLocaleDateString('it-IT', {
            day: '2-digit',
            month: '2-digit', 
            year: 'numeric'
        });
        const dataScadenzaISO = dataScadenza.toISOString().split('T')[0];
        
        cell.setAttribute('data-data-scadenza', dataScadenzaISO);
        cell.textContent = dataScadenzaFormatted;
        
        // Aggiungi la classe corretta per lo stato (se non già prenotato)
        if (!isPrenotato) {
            const classeStato = 'scadenza-' + scadenza.stato.replace('_', '-');
            cell.classList.add(classeStato);
        }
    } else {
        cell.setAttribute('data-data-scadenza', '');
        cell.textContent = 'Non presente';
        if (!isPrenotato) {
            cell.classList.add('scadenza-mancante');
        }
    }
    
    // Ripristina opacità
    cell.style.opacity = '1';
}

/**
 * Mostra un toast di notifica
 */
function showToast(message, type = 'info') {
    // Usa il sistema toast se disponibile, altrimenti fallback
    if (window.SUGECO && window.SUGECO.Toast) {
        window.SUGECO.Toast.show(message, type);
    } else if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        // Fallback: crea un toast semplice
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 250px; animation: fadeIn 0.3s;';
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>${message}`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Chiudi modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScadenzaModal();
    }
});

// Export Excel con filtri attivi
document.getElementById('exportExcel').addEventListener('click', function() {
    const visibleRows = document.querySelectorAll('#scadenzeTable tbody tr:not([style*="display: none"])');
    const militareIds = [];
    visibleRows.forEach(row => {
        const id = row.getAttribute('data-militare-id');
        if (id) militareIds.push(id);
    });
    
    const baseUrl = window.location.origin + '/SUGECO/public/idoneita/export-excel';
    if (militareIds.length > 0 && militareIds.length < document.querySelectorAll('#scadenzeTable tbody tr').length) {
        window.location.href = baseUrl + '?ids=' + militareIds.join(',');
    } else {
        window.location.href = baseUrl;
    }
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
    let visibili = 0;
    
    rows.forEach(row => {
        let visible = true;
        
        // Mappa indici celle per campo (aggiornata con colonna T.O.)
        const campiMap = {
            'idoneita_mansione': 5,
            'idoneita_smi': 6,
            'idoneita_to': 7,
            'ecg': 8,
            'prelievi': 9
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
        if (visible) visibili++;
    });
    
    // Aggiorna contatore
    const contatoreEl = document.getElementById('contatoreVisibili');
    if (contatoreEl) {
        contatoreEl.textContent = visibili;
    }
}

// Reset filtri stato (client-side)
function resetFiltriStato() {
    document.querySelectorAll('.filter-stato').forEach(select => {
        select.value = '';
    });
    // Riapplica per mostrare tutti
    applicaFiltriStato();
}

// Reset filtri completo (anche compagnia - server-side)
function resetFiltri() {
    // Reset filtri stato
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
