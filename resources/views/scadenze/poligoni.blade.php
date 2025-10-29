@extends('layouts.app')

@section('title', 'Poligoni - Tiri e Mantenimento')

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

/* Colonna sticky per militare */
.table th:first-child,
.table td:first-child {
    position: sticky;
    left: 0;
    background-color: #e9ecef;
    z-index: 5;
    font-weight: 600;
}

.table thead th:first-child {
    background-color: #0a2342 !important;
    z-index: 15;
}

.table tbody tr:nth-of-type(odd) td:first-child {
    background-color: #ffffff;
}

.table tbody tr:nth-of-type(even) td:first-child {
    background-color: #fafafa;
}

.table tbody tr:hover td:first-child {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

/* Celle scadenze - Solo data colorata */
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

/* Colori scadenze */
.scadenza-valido {
    background-color: #d4edda;
    color: #155724;
}

.scadenza-in-scadenza {
    background-color: #fff3cd;
    color: #856404;
}

.scadenza-scaduto {
    background-color: #f8d7da;
    color: #721c24;
}

.scadenza-mancante {
    background-color: #e9ecef;
    color: #6c757d;
}

/* Modal hover per modifica */
.scadenza-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 9999;
    min-width: 400px;
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
}

.modal-body-custom {
    margin-bottom: 20px;
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
    <h1 class="page-title">POLIGONI - TIRI E MANTENIMENTO</h1>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge bg-primary">{{ $data->count() }} militari</span>
    </div>
    <div class="d-flex gap-2">
        <span class="badge" style="background-color: #d4edda; color: #155724;"><i class="fas fa-check"></i> Valido</span>
        <span class="badge" style="background-color: #fff3cd; color: #856404;"><i class="fas fa-exclamation-triangle"></i> In Scadenza (≤30gg)</span>
        <span class="badge" style="background-color: #f8d7da; color: #721c24;"><i class="fas fa-times"></i> Scaduto</span>
        <span class="badge" style="background-color: #e9ecef; color: #6c757d;"><i class="fas fa-minus"></i> Mancante</span>
    </div>
</div>

<!-- Tabella con scroll orizzontale -->
<div class="table-responsive" style="max-height: 70vh; overflow: auto;">
    <table class="table table-sm table-bordered table-hover mb-0">
        <thead style="position: sticky; top: 0; z-index: 10;">
            <tr style="background-color: #0a2342; color: white;">
                <th style="min-width: 200px;">Militare</th>
                <th style="min-width: 160px;">Tiri di Approntamento</th>
                <th style="min-width: 180px;">Mantenimento Arma Lunga</th>
                <th style="min-width: 180px;">Mantenimento Arma Corta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr data-militare-id="{{ $item['militare']->id }}">
                <td>
                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                        {{ $item['militare']->grado->abbreviazione ?? '' }} 
                        {{ $item['militare']->cognome }} 
                        {{ $item['militare']->nome }}
                    </a>
                </td>
                
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
                @endphp
                
                <td class="scadenza-cell {{ $classeColore }}" 
                    data-militare-id="{{ $item['militare']->id }}"
                    data-campo="{{ $campoDB }}"
                    data-campo-nome="{{ ucwords(str_replace('_', ' ', $campo)) }}"
                    data-data-conseguimento="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
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
    <div class="modal-body-custom">
        <label for="modalDataConseguimento">Data Conseguimento:</label>
        <input type="date" id="modalDataConseguimento" class="form-control">
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

function openScadenzaModal(cell) {
    @cannot('scadenze.edit')
    return;
    @endcannot
    
    currentCell = cell;
    const campoNome = cell.getAttribute('data-campo-nome');
    const dataConseguimento = cell.getAttribute('data-data-conseguimento');
    
    document.getElementById('modalTitle').textContent = `Modifica ${campoNome}`;
    document.getElementById('modalDataConseguimento').value = dataConseguimento;
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('scadenzaModal').classList.add('show');
}

function closeScadenzaModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.getElementById('scadenzaModal').classList.remove('show');
    currentCell = null;
}

function saveScadenza() {
    if (!currentCell) return;
    
    const militareId = currentCell.getAttribute('data-militare-id');
    const campo = currentCell.getAttribute('data-campo');
    const nuovaData = document.getElementById('modalDataConseguimento').value;
    
    // Feedback visivo
    currentCell.style.opacity = '0.5';
    
    fetch(`/scadenze/poligoni/${militareId}/update-singola`, {
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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Errore durante l\'aggiornamento');
            currentCell.style.opacity = '1';
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore durante l\'aggiornamento');
        currentCell.style.opacity = '1';
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
    window.location.href = '/scadenze/poligoni/export-excel';
});
</script>
@endpush
