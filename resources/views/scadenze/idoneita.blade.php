@extends('layouts.app')

@section('title', 'Idoneità Sanitarie')

@section('content')
<style>
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

/* Colonne sticky per grado, cognome, nome */
.table th:nth-child(1),
.table td:nth-child(1) {
    position: sticky;
    left: 0;
    z-index: 5;
    font-weight: 600;
    min-width: 60px !important;
}

.table th:nth-child(2),
.table td:nth-child(2) {
    position: sticky;
    left: 60px;
    z-index: 5;
    font-weight: 600;
    min-width: 120px !important;
}

.table th:nth-child(3),
.table td:nth-child(3) {
    position: sticky;
    left: 180px;
    z-index: 5;
    font-weight: 600;
    min-width: 100px !important;
}

.table thead th:nth-child(1),
.table thead th:nth-child(2),
.table thead th:nth-child(3) {
    background-color: #0a2342 !important;
    z-index: 15;
}

.table tbody tr:nth-of-type(odd) td:nth-child(1),
.table tbody tr:nth-of-type(odd) td:nth-child(2),
.table tbody tr:nth-of-type(odd) td:nth-child(3) {
    background-color: #ffffff;
}

.table tbody tr:nth-of-type(even) td:nth-child(1),
.table tbody tr:nth-of-type(even) td:nth-child(2),
.table tbody tr:nth-of-type(even) td:nth-child(3) {
    background-color: #fafafa;
}

.table tbody tr:hover td:nth-child(1),
.table tbody tr:hover td:nth-child(2),
.table tbody tr:hover td:nth-child(3) {
    background-color: rgba(10, 35, 66, 0.12) !important;
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
        <span class="badge bg-primary">{{ $data->count() }} militari</span>
        <span class="badge" style="background-color: #d4edda; color: #155724;"><i class="fas fa-check"></i> Valido</span>
        <span class="badge" style="background-color: #fff3cd; color: #856404;"><i class="fas fa-exclamation-triangle"></i> In Scadenza</span>
        <span class="badge" style="background-color: #f8d7da; color: #721c24;"><i class="fas fa-times"></i> Scaduto</span>
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
            <form id="filtroForm">
                <div class="row mb-3">
                    <div class="col-md-3">
                <label class="form-label">Idoneità Mansione:</label>
                <select name="idoneita_mansione" class="form-select filter-select">
                    <option value="">Tutti</option>
                    <option value="valido">Valido</option>
                    <option value="in_scadenza">In Scadenza</option>
                    <option value="scaduto">Scaduto</option>
                    <option value="mancante">Mancante</option>
                </select>
            </div>
                    <div class="col-md-3">
                <label class="form-label">Idoneità SMI:</label>
                <select name="idoneita_smi" class="form-select filter-select">
                    <option value="">Tutti</option>
                    <option value="valido">Valido</option>
                    <option value="in_scadenza">In Scadenza</option>
                    <option value="scaduto">Scaduto</option>
                    <option value="mancante">Mancante</option>
                </select>
            </div>
                    <div class="col-md-3">
                <label class="form-label">ECG:</label>
                <select name="ecg" class="form-select filter-select">
                    <option value="">Tutti</option>
                    <option value="valido">Valido</option>
                    <option value="in_scadenza">In Scadenza</option>
                    <option value="scaduto">Scaduto</option>
                    <option value="mancante">Mancante</option>
                </select>
            </div>
                    <div class="col-md-3">
                        <label class="form-label">Prelievi:</label>
                        <select name="prelievi" class="form-select filter-select">
                            <option value="">Tutti</option>
                            <option value="valido">Valido</option>
                            <option value="in_scadenza">In Scadenza</option>
                            <option value="scaduto">Scaduto</option>
                            <option value="mancante">Mancante</option>
                        </select>
            </div>
        </div>
    </form>
        </div>
    </div>
</div>

<!-- Tabella con scroll orizzontale -->
<div class="table-responsive" style="max-height: 70vh; overflow: auto;">
    <table class="table table-sm table-bordered table-hover mb-0" id="scadenzeTable">
        <thead style="position: sticky; top: 0; z-index: 10;">
            <tr style="background-color: #0a2342; color: white;">
                <th>Grado</th>
                <th>Cognome</th>
                <th>Nome</th>
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
                <td>{{ $item['militare']->grado->abbreviazione ?? '' }}</td>
                <td>
                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                        {{ $item['militare']->cognome }}
                    </a>
                </td>
                <td>{{ $item['militare']->nome }}</td>
                
                @foreach(['idoneita_mansione', 'idoneita_smi', 'ecg', 'prelievi'] as $campo)
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
    
    console.log('Totale righe nella tabella:', rows.length);
    console.log('Termine ricerca:', searchTerm);
    
    let visibili = 0;
    rows.forEach(row => {
        const militareNome = row.getAttribute('data-militare-nome');
        if (!militareNome) {
            console.warn('Riga senza data-militare-nome:', row);
            return;
        }
        
        if (militareNome.toLowerCase().includes(searchTerm)) {
            row.style.display = '';
            visibili++;
        } else {
            row.style.display = 'none';
        }
    });
    
    console.log('Righe visibili dopo filtro:', visibili);
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
    
    console.log('Salvataggio:', { militareId, campo, nuovaData });
    
    // Salva riferimento per ripristinare dopo errore
    const cellToUpdate = currentCell;
    
    // Feedback visivo
    cellToUpdate.style.opacity = '0.5';
    
    // URL BASE CORRETTA
    const baseUrl = window.location.origin + '/SUGECO/public';
    
    fetch(`${baseUrl}/scadenze/idoneita/${militareId}/update-singola`, {
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
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
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
    window.location.href = '/SUGECO/public/scadenze/idoneita/export-excel';
});

// DEBUG: Verifica righe al caricamento
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#scadenzeTable tbody tr');
    console.log('=== DEBUG IDONEITÀ ===');
    console.log('Righe renderizzate:', rows.length);
    console.log('Badge count:', '{{ $data->count() }}');
    
    rows.forEach((row, index) => {
        const militareNome = row.getAttribute('data-militare-nome');
        const display = window.getComputedStyle(row).display;
        if (display === 'none') {
            console.warn(`Riga ${index + 1} nascosta:`, militareNome);
        }
    });
    console.log('======================');
});
</script>
@endpush
