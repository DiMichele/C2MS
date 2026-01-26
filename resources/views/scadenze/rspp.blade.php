@extends('layouts.app')

@section('title', 'Corsi di Formazione SPP')

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
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">CORSI DI FORMAZIONE SPP</h1>
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
        <span class="badge-legenda badge-legenda-mancante"><i class="fas fa-minus-circle"></i> Non presente</span>
    </div>
</div>

<!-- Sezione Filtri (come Anagrafica) -->
<div id="filtersContainer" class="filter-section" style="display: none;">
    <div class="filter-card mb-4">
        <div class="filter-card-header">Filtri avanzati</div>
        <div class="card-body p-3">
            <form id="filtroForm" onsubmit="return false;">
                <div class="row mb-3">
                    @foreach($corsi as $corso)
                    <div class="col-md-3">
                        <label class="form-label">{{ $corso->nome_corso }}:</label>
                        <select name="{{ $corso->codice_corso }}" class="form-select filter-select">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="mancante">Non presente</option>
                        </select>
                    </div>
                    @endforeach
                </div>
            </form>
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
                @foreach($corsi as $corso)
                <th>{{ $corso->nome_corso }}</th>
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
                
                @foreach($corsi as $corso)
                @php
                    $scadenza = $item[$corso->codice_corso];
                    $classeColore = match($scadenza['stato']) {
                        'valido' => 'scadenza-valido',
                        'in_scadenza' => 'scadenza-in-scadenza',
                        'scaduto' => 'scadenza-scaduto',
                        'mancante' => 'scadenza-mancante',
                        default => 'scadenza-mancante'
                    };
                    
                    // Se durata = 0 e c'è¨ data conseguimento, mostra "Nessuna scadenza"
                    if ($scadenza['durata'] == 0 && $scadenza['data_conseguimento']) {
                        $testoData = 'Nessuna scadenza';
                    } else {
                        $testoData = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('d/m/Y') : 'Non presente';
                    }
                    
                    $dataScadenzaISO = $scadenza['data_scadenza'] ? $scadenza['data_scadenza']->format('Y-m-d') : '';
                @endphp
                
                <td class="scadenza-cell {{ $classeColore }}" 
                    data-militare-id="{{ $item['militare']->id }}"
                    data-corso-id="{{ $corso->id }}"
                    data-corso-nome="{{ $corso->nome_corso }}"
                    data-data-conseguimento="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
                    data-data-scadenza="{{ $dataScadenzaISO }}"
                    data-durata="{{ $scadenza['durata'] ?? $corso->durata_anni }}"
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
</div>

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay" onclick="closeScadenzaModal()"></div>

<!-- Modal Modifica Scadenza -->
<div class="scadenza-modal" id="scadenzaModal">
    <div class="modal-header-custom">
        <h5 id="modalTitle">Modifica Scadenza</h5>
    </div>
    <div class="modal-militare-info" id="modalMilitareInfo"></div>
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

// Funzione per applicare tutti i filtri
function applicaFiltri() {
    const searchTerm = document.getElementById('searchMilitare').value.toLowerCase();
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    
    // Leggi tutti i filtri dalle select dinamicamente
    const filterSelects = document.querySelectorAll('.filter-select');
    const filtriAttivi = {};
    filterSelects.forEach(select => {
        const name = select.getAttribute('name');
        const value = select.value;
        if (value) {
            filtriAttivi[name] = value;
        }
    });
    
    let visibili = 0;
    rows.forEach(row => {
        const militareNome = row.getAttribute('data-militare-nome');
        if (!militareNome) return;
        
        // Check ricerca
        const matchRicerca = !searchTerm || militareNome.toLowerCase().includes(searchTerm);
        
        // Check filtri per ogni campo
        let matchFiltri = true;
        const celle = row.querySelectorAll('.scadenza-cell');
        
        // Verifica ogni filtro attivo
        for (const [filtroKey, filtroValue] of Object.entries(filtriAttivi)) {
            // Trova la cella che corrisponde al filtro
            let cellaMatch = null;
            for (let i = 0; i < celle.length; i++) {
                const cell = celle[i];
                const corsoId = cell.getAttribute('data-corso-id');
                // Usa data-corso-id per abbinare, ma se non esiste prova con l'indice
                const cellaCorso = cell.closest('td');
                // Controlla se questa cella appartiene al corso giusto
                if (cellaCorso) {
                    const allCells = Array.from(row.querySelectorAll('.scadenza-cell'));
                    const cellIndex = allCells.indexOf(cell);
                    
                    // Cerca se questa è¨ la cella giusta confrontando con i filtri
                    // Basandoci sull'ordine delle colonne nella thead
                    const thead = document.querySelector('#scadenzeTable thead tr');
                    const headers = Array.from(thead.querySelectorAll('th'));
                    // Salta le prime 4 colonne (Compagnia, Grado, Cognome, Nome)
                    const corsoHeaders = headers.slice(4);
                    
                    if (cellIndex < corsoHeaders.length) {
                        const corsoHeader = corsoHeaders[cellIndex]?.textContent.trim();
                        const selectLabel = document.querySelector(`select[name="${filtroKey}"]`)?.previousElementSibling?.textContent.replace(':', '').trim();
                        
                        if (corsoHeader === selectLabel) {
                            cellaMatch = cell;
                            break;
                        }
                    }
                }
            }
            
            if (cellaMatch) {
                const statoCell = cellaMatch.getAttribute('data-stato');
                if (statoCell !== filtroValue) {
                    matchFiltri = false;
                    break;
                }
            }
        }
        
        // Mostra/nascondi riga
        if (matchRicerca && matchFiltri) {
            row.style.display = '';
            visibili++;
        } else {
            row.style.display = 'none';
        }
    });
    
    }

// Ricerca live
document.getElementById('searchMilitare').addEventListener('input', applicaFiltri);

// Applica filtri quando cambiano le select
document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function(e) {
        e.preventDefault();
        e.stopPropagation();
        applicaFiltri();
    });
});

// Impedisci il submit del form filtri (deve essere tutto client-side)
const filtroForm = document.getElementById('filtroForm');
if (filtroForm) {
    filtroForm.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
}

// Inizializza tooltip per le celle scadenze
document.querySelectorAll('.scadenza-cell').forEach(cell => {
    const dataConseguimento = cell.getAttribute('data-data-conseguimento');
    const durata = parseInt(cell.getAttribute('data-durata'));
    const corsoNome = cell.getAttribute('data-corso-nome');
    
    let tooltipText = `<strong>${corsoNome}</strong><br>`;
    
    if (dataConseguimento) {
        const dataObj = new Date(dataConseguimento);
        const dataFormatted = dataObj.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
        tooltipText += `Data Conseguimento: ${dataFormatted}<br>`;
        
        if (durata === 0) {
            tooltipText += `Durata validitè : Nessuna scadenza`;
        } else {
            tooltipText += `Durata validitè : ${durata} ${durata === 1 ? 'anno' : 'anni'}`;
        }
    } else {
        tooltipText += `Non presente`;
    }
    
    // Usa Bootstrap tooltip
    new bootstrap.Tooltip(cell, {
        html: true,
        title: tooltipText,
        placement: 'top',
        trigger: 'hover'
    });
});

function openScadenzaModal(cell) {
    @cannot('scadenze.edit')
    return;
    @endcannot
    
    currentCell = cell;
    const corsoNome = cell.getAttribute('data-corso-nome');
    const dataConseguimento = cell.getAttribute('data-data-conseguimento');
    const dataScadenza = cell.getAttribute('data-data-scadenza');
    const durata = parseInt(cell.getAttribute('data-durata'));
    
    const row = cell.closest('tr');
    const militareNome = row.getAttribute('data-militare-nome');
    
    document.getElementById('modalTitle').textContent = `Modifica ${corsoNome}`;
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
    const corsoId = currentCell.getAttribute('data-corso-id');
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
    
    fetch(`${baseUrl}/spp/corsi-di-formazione/${militareId}/update-singola`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            corso_id: corsoId,
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
    
    // Gestisci il caso "nessuna scadenza" (durata = 0)
    if (durata === 0 && dataConseguimento) {
        cell.setAttribute('data-data-scadenza', '');
        cell.textContent = 'Nessuna scadenza';
        cell.classList.add('scadenza-valido');
    } else if (scadenza.data_scadenza) {
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
        
        // Aggiungi la classe corretta per lo stato
        const classeStato = 'scadenza-' + scadenza.stato.replace('_', '-');
        cell.classList.add(classeStato);
    } else {
        cell.setAttribute('data-data-scadenza', '');
        cell.textContent = 'Non presente';
        cell.classList.add('scadenza-mancante');
    }
    
    // Ripristina opacità
    cell.style.opacity = '1';
    
    // Aggiorna il tooltip se presente
    const tooltipInstance = bootstrap.Tooltip.getInstance(cell);
    if (tooltipInstance) {
        tooltipInstance.dispose();
    }
    
    // Ricrea il tooltip con i nuovi dati
    const corsoNome = cell.getAttribute('data-corso-nome');
    let tooltipText = `<strong>${corsoNome}</strong><br>`;
    
    if (dataConseguimento) {
        const dataObj = new Date(dataConseguimento);
        const dataFormatted = dataObj.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit', year: 'numeric' });
        tooltipText += `Data Conseguimento: ${dataFormatted}<br>`;
        
        if (durata === 0) {
            tooltipText += `Durata validità: Nessuna scadenza`;
        } else {
            tooltipText += `Durata validità: ${durata} ${durata === 1 ? 'anno' : 'anni'}`;
        }
    } else {
        tooltipText += `Non presente`;
    }
    
    new bootstrap.Tooltip(cell, {
        html: true,
        title: tooltipText,
        placement: 'top',
        trigger: 'hover'
    });
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
    
    const baseUrl = window.location.origin + '/SUGECO/public/spp/corsi-di-formazione/export-excel';
    if (militareIds.length > 0 && militareIds.length < document.querySelectorAll('#scadenzeTable tbody tr').length) {
        window.location.href = baseUrl + '?ids=' + militareIds.join(',');
    } else {
        window.location.href = baseUrl;
    }
});

</script>
@endpush


