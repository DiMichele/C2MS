@extends('layouts.app')

@section('title', 'Poligoni')

@section('content')
<style>
/* Stili specifici per questa pagina */
/* (Stili base tabelle in table-standard.css) */

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
    <h1 class="page-title">POLIGONI</h1>
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
        <span id="filterToggleText">Mostra Filtri</span>
    </button>
    <div class="legenda-scadenze">
        <span class="badge-legenda badge-legenda-valido"><i class="fas fa-check-circle"></i> Valido</span>
        <span class="badge-legenda badge-legenda-in-scadenza"><i class="fas fa-exclamation-triangle"></i> In Scadenza</span>
        <span class="badge-legenda badge-legenda-scaduto"><i class="fas fa-times-circle"></i> Scaduto</span>
        <span class="badge-legenda badge-legenda-mancante"><i class="fas fa-minus-circle"></i> Non presente</span>
    </div>
</div>

<!-- Sezione Filtri (usa lo stesso stile delle altre pagine) -->
<div class="filter-card mb-4" id="filtersSection" style="display: none;">
    <div class="filter-card-header d-flex justify-content-between align-items-center">
        <div>Filtri avanzati</div>
    </div>
    <div class="card-body p-3">
        <div class="row">
            @foreach($colonne as $index => $colonna)
            <div class="col mb-2">
                <label for="filter_{{ $colonna['codice'] }}" class="form-label small">{{ $colonna['nome'] }}</label>
                <select id="filter_{{ $colonna['codice'] }}" class="form-select form-select-sm filter-local" data-campo="{{ $colonna['codice'] }}" data-index="{{ $index }}">
                    <option value="">Tutti</option>
                    <option value="valido">Valido</option>
                    <option value="in_scadenza">In Scadenza</option>
                    <option value="scaduto">Scaduto</option>
                    <option value="mancante">Non presente</option>
                </select>
            </div>
            @endforeach
        </div>
        
        <div class="d-flex justify-content-center mt-3">
            <button type="button" id="resetAllFilters" class="btn btn-outline-danger" style="display: none;">
                Rimuovi tutti i filtri
            </button>
        </div>
        
        <div id="noResultsMessage" class="text-center py-5" style="display: none;">
            <div class="d-flex flex-column align-items-center empty-state">
                <i class="fas fa-users-slash fa-3x mb-3 text-muted"></i>
                <p class="lead mb-3">Nessun militare trovato</p>
                <p class="text-muted mb-3">Prova a modificare i criteri di ricerca o i filtri applicati.</p>
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
                @foreach($colonne as $colonna)
                <th>{{ $colonna['nome'] }}</th>
                @endforeach
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
                
                @foreach($colonne as $index => $colonna)
                @php
                    $scadenza = $item[$colonna['codice']];
                    $classeColore = match($scadenza['stato']) {
                        'valido' => 'scadenza-valido',
                        'in_scadenza' => 'scadenza-in-scadenza',
                        'scaduto' => 'scadenza-scaduto',
                        'mancante' => 'scadenza-mancante',
                        default => 'scadenza-mancante'
                    };
                    $testoData = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('d/m/Y') : 'Non presente';
                    $dataScadenzaISO = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('Y-m-d') : '';
                @endphp
                
                <td class="scadenza-cell {{ $classeColore }}" 
                    data-militare-id="{{ $item['militare']->id }}"
                    data-campo="{{ $colonna['campo_db'] ?? 'tipo_poligono_' . $colonna['id'] }}"
                    data-campo-nome="{{ $colonna['nome'] }}"
                    data-data-conseguimento="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
                    data-data-scadenza="{{ $dataScadenzaISO }}"
                    data-mesi="{{ $colonna['durata_mesi'] }}"
                    data-stato="{{ $scadenza['stato'] }}"
                    data-index="{{ $index }}"
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

// Filtraggio lato client
function applyFilters() {
    const filterSelects = document.querySelectorAll('.filter-local');
    const filters = {};
    filterSelects.forEach(select => {
        const index = select.getAttribute('data-index');
        filters[index] = select.value;
    });
    
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    let visibleCount = 0;
    let activeFilters = 0;
    
    // Conta filtri attivi
    Object.values(filters).forEach(f => { if (f) activeFilters++; });
    
    rows.forEach(row => {
        let show = true;
        
        // Filtro ricerca
        if (searchTerm) {
            const militareNome = row.getAttribute('data-militare-nome').toLowerCase();
            if (!militareNome.includes(searchTerm)) {
                show = false;
            }
        }
        
        // Filtri per stato
        if (show) {
            const cells = row.querySelectorAll('td.scadenza-cell');
            
            cells.forEach((cell) => {
                const index = cell.getAttribute('data-index');
                const filterValue = filters[index];
                if (filterValue) {
                    const statoCell = cell.getAttribute('data-stato');
                    if (statoCell !== filterValue) {
                        show = false;
                    }
                }
            });
        }
        
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Mostra/nascondi messaggio nessun risultato
    const noResultsMsg = document.getElementById('noResultsMessage');
    noResultsMsg.style.display = (visibleCount === 0) ? 'block' : 'none';
    
    // Mostra/nascondi pulsante reset filtri
    const resetBtn = document.getElementById('resetAllFilters');
    resetBtn.style.display = (activeFilters > 0 || searchTerm) ? 'inline-block' : 'none';
}

// Event listeners per i filtri locali
document.querySelectorAll('.filter-local').forEach(function(select) {
    select.addEventListener('change', applyFilters);
});

// Reset tutti i filtri
document.getElementById('resetAllFilters').addEventListener('click', function() {
    document.querySelectorAll('.filter-local').forEach(s => s.value = '');
    document.getElementById('searchInput').value = '';
    applyFilters();
});

// Ricerca live
document.getElementById('searchInput').addEventListener('input', applyFilters);

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
    const mesi = parseInt(currentCell.getAttribute('data-mesi') || 6);
    
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
            updateCellFromResponse(cellToUpdate, data.scadenza, nuovaData, mesi);
            
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
function updateCellFromResponse(cell, scadenza, dataConseguimento, mesi) {
    // Rimuovi tutte le classi di stato precedenti
    cell.classList.remove('scadenza-valido', 'scadenza-in-scadenza', 'scadenza-scaduto', 'scadenza-mancante', 'scadenza-prenotato');
    
    // Aggiorna attributi data
    cell.setAttribute('data-data-conseguimento', dataConseguimento || '');
    cell.setAttribute('data-stato', scadenza.stato);
    
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
    } else {
        cell.setAttribute('data-data-scadenza', '');
        cell.textContent = 'Non presente';
    }
    
    // Aggiungi la classe corretta per lo stato
    const classeStato = 'scadenza-' + scadenza.stato.replace('_', '-');
    cell.classList.add(classeStato);
    
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
    // Raccogli gli ID dei militari visibili
    const visibleRows = document.querySelectorAll('#scadenzeTable tbody tr:not([style*="display: none"])');
    const militareIds = [];
    visibleRows.forEach(row => {
        const id = row.getAttribute('data-militare-id');
        if (id) militareIds.push(id);
    });
    
    // Costruisci URL con i filtri
    const baseUrl = window.location.origin + '/SUGECO/public/poligoni/export-excel';
    if (militareIds.length > 0 && militareIds.length < document.querySelectorAll('#scadenzeTable tbody tr').length) {
        // Ci sono filtri attivi, invia solo gli ID visibili
        window.location.href = baseUrl + '?ids=' + militareIds.join(',');
    } else {
        // Nessun filtro, esporta tutto
        window.location.href = baseUrl;
    }
});
</script>
@endpush
