@extends('layouts.app')

@section('title', 'PEFO - Prove Efficienza Fisica Operativa')

@section('content')
<style>
/* Stili specifici per pagina PEFO */

/* Toggle Section */
.toggle-section {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.btn-toggle {
    padding: 10px 24px;
    border: 2px solid #0a2342;
    background: white;
    color: #0a2342;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-toggle:hover {
    background: #f8f9fa;
}

.btn-toggle.active {
    background: #0a2342;
    color: white;
}

/* Card Prenotazione */
.prenotazione-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
    transition: transform 0.2s ease;
}

.prenotazione-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.prenotazione-header {
    background: #0a2342;
    color: white;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.prenotazione-header h4 {
    margin: 0;
    font-size: 1.1rem;
}

.prenotazione-body {
    padding: 20px;
}

.prenotazione-footer {
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.prenotazione-stato {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.prenotazione-stato.prenotato {
    background: #cce5ff;
    color: #004085;
}

.prenotazione-stato.confermato {
    background: #d4edda;
    color: #155724;
}

.prenotazione-stato.annullato {
    background: #e2e3e5;
    color: #6c757d;
}

/* Tabella militari in prenotazione */
.militari-list {
    max-height: 300px;
    overflow-y: auto;
}

.militari-list table {
    margin-bottom: 0;
}

.militari-list th, .militari-list td {
    padding: 8px 12px;
    font-size: 0.9rem;
}

.btn-rimuovi-militare {
    color: #dc3545;
    cursor: pointer;
    padding: 2px 8px;
    border: none;
    background: none;
}

.btn-rimuovi-militare:hover {
    color: #a71d2a;
}

/* Modal custom */
.pefo-modal {
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
    min-width: 600px;
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
}

.pefo-modal.show {
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

.modal-body-custom {
    margin-bottom: 20px;
}

.modal-footer-custom {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

/* Tabella selezione militari */
.militari-select-table {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.militari-select-table table {
    margin-bottom: 0;
}

.militari-select-table tr.selected {
    background: #e3f2fd;
}

/* Cella stato PEFO */
.stato-pefo-cell {
    padding: 4px 12px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stato-pefo-cell:hover {
    transform: scale(1.02);
}

.stato-pefo-valido {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border-left: 4px solid #28a745;
}


.stato-pefo-scaduto {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Stili per militari confermati/da confermare nelle prenotazioni */
.militare-confermato {
    background: #d4edda !important;
}

.militare-confermato td {
    background: transparent !important;
}

.militare-da-confermare {
    background: #cce5ff !important;
}

.militare-da-confermare td {
    background: transparent !important;
}

.badge-confermato {
    background-color: #28a745;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.badge-da-confermare {
    background-color: #007bff;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
}

.btn-conferma-militare {
    background: #28a745;
    color: white;
    border: none;
    padding: 4px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
}

.btn-conferma-militare:hover {
    background: #218838;
}

.btn-conferma-tutti {
    background: #28a745;
    color: white;
    border: none;
    padding: 6px 16px;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 10px;
}

.btn-conferma-tutti:hover {
    background: #218838;
}

/* Stili per card collassabili */
.prenotazione-card.collapsed .prenotazione-body-collapsible {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease-out;
}

.prenotazione-card.expanded .prenotazione-body-collapsible {
    max-height: 2000px;
    overflow: visible;
    transition: max-height 0.3s ease-in;
}

.prenotazione-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid #dee2e6;
}

.prenotazione-card.collapsed .prenotazione-header {
    border-radius: 8px;
    border-bottom: none;
}

.prenotazione-header:hover {
    background: #e9ecef;
}

.prenotazione-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.prenotazione-header-left h4 {
    margin: 0;
    font-size: 1.1rem;
}

.prenotazione-header-right {
    display: flex;
    gap: 8px;
}

.expand-icon {
    transition: transform 0.3s ease;
    color: #6c757d;
}

.badge-militari {
    background: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: normal;
}

.prenotazione-body-collapsible {
    padding: 0 15px;
}

.prenotazione-card.expanded .prenotazione-body-collapsible {
    padding: 15px;
}

.stato-pefo-mancante {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #6c757d;
    border-left: 4px solid #adb5bd;
    font-style: italic;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Badge info prenotazione */
.prenotazione-info {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.prenotazione-info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
}

.prenotazione-info-item i {
    color: #0a2342;
}

/* Link militare */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
}

.link-name:hover {
    color: #0a2342;
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

<!-- Header con titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">PEFO - PROVE EFFICIENZA FISICA OPERATIVA</h1>
</div>

<!-- Toggle tra sezioni -->
<div class="d-flex justify-content-center mb-4">
    <div class="toggle-section">
        <button type="button" id="btnTabellaMilitari" class="btn-toggle active" onclick="showSection('tabella')">
            <i class="fas fa-table me-2"></i>Tabella Militari
        </button>
        <button type="button" id="btnPrenotazioni" class="btn-toggle" onclick="showSection('prenotazioni')">
            <i class="fas fa-calendar-alt me-2"></i>Prenotazioni PEFO
        </button>
    </div>
</div>

<!-- ================================ -->
<!-- SEZIONE TABELLA MILITARI -->
<!-- ================================ -->
<div id="sezioneTabellaContent">
    <!-- Barra ricerca -->
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

    <!-- Filtri e Legenda -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
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

    <!-- Sezione Filtri -->
    <div id="filtersContainer" class="filter-section" style="display: none;">
        <div class="filter-card mb-4">
            <div class="filter-card-header">Filtri avanzati</div>
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Compagnia:</label>
                        <select id="filterCompagnia" class="form-select filter-select">
                            <option value="">Tutte</option>
                            @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}">{{ $compagnia->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Grado:</label>
                        <select id="filterGrado" class="form-select filter-select">
                            <option value="">Tutti</option>
                            @foreach($gradi as $grado)
                            <option value="{{ $grado->id }}">{{ $grado->nome }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Unità:</label>
                        <select id="filterUnita" class="form-select filter-select">
                            <option value="">Tutte</option>
                            @foreach($unita as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Stato Agility:</label>
                        <select id="filterStatoAgility" class="form-select filter-select">
                            <option value="">Tutti</option>
                            <option value="valido">Valido (anno corrente)</option>
                            <option value="scaduto">Scaduto (anni precedenti)</option>
                            <option value="mancante">Non presente</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Stato Resistenza:</label>
                        <select id="filterStatoResistenza" class="form-select filter-select">
                            <option value="">Tutti</option>
                            <option value="valido">Valido (anno corrente)</option>
                            <option value="scaduto">Scaduto (anni precedenti)</option>
                            <option value="mancante">Non presente</option>
                        </select>
                    </div>
                </div>
                
                <!-- Pulsante Rimuovi tutti i filtri -->
                <div id="resetFiltersContainer" class="d-flex justify-content-center mt-3" style="display: none;">
                    <button type="button" id="resetAllFilters" class="btn btn-danger btn-sm" style="border-radius: 6px !important;">
                        <i class="fas fa-times-circle me-1"></i>Rimuovi tutti i filtri
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella Militari -->
    <div class="sugeco-table-nav-container" data-table-nav="auto">
        <div class="sugeco-table-wrapper">
            <table class="sugeco-table" id="tabellaMilitari">
                <thead>
                    <tr>
                        <th>Unità</th>
                        <th>Compagnia</th>
                        <th>Grado</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>Agility</th>
                        <th>Resistenza</th>
                    </tr>
                </thead>
                <tbody id="tabellaMilitariBody">
                    <!-- Popolato via JavaScript -->
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin me-2"></i>Caricamento...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================================ -->
<!-- SEZIONE PRENOTAZIONI -->
<!-- ================================ -->
<div id="sezionePrenotazioniContent" style="display: none;">
    <!-- Header con pulsante crea -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span id="countPrenotazioni" class="text-muted"></span>
        </div>
        @if($canGestisciPrenotazioni)
        <button type="button" class="btn btn-success" onclick="openCreaPrenotazioneModal()">
            <i class="fas fa-plus me-2"></i>Nuova Prenotazione
        </button>
        @endif
    </div>

    <!-- Lista Prenotazioni -->
    <div id="listaPrenotazioni">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin me-2"></i>Caricamento prenotazioni...
        </div>
    </div>
</div>

<!-- ================================ -->
<!-- MODALS -->
<!-- ================================ -->

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>

<!-- Modal Modifica Data PEFO (Agility o Resistenza) -->
<div class="pefo-modal" id="modalModificaPefo">
    <div class="modal-header-custom">
        <h5 id="modalTitoloPefo">Modifica Data</h5>
    </div>
    <div class="modal-militare-info" id="modalMilitareInfo" style="background: #f8f9fa; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-weight: 600;"></div>
    <div class="modal-body-custom">
        <input type="hidden" id="modalMilitareId">
        <input type="hidden" id="modalTipoPefo">
        <div class="form-group">
            <label for="modalDataPefo" class="form-label" id="modalLabelData">Data:</label>
            <input type="date" id="modalDataPefo" class="form-control">
            <small class="text-muted">Lascia vuoto per rimuovere la data</small>
        </div>
    </div>
    <div class="modal-footer-custom">
        <button type="button" class="btn btn-secondary" onclick="closeAllModals()">Annulla</button>
        <button type="button" class="btn btn-success" onclick="saveDataPefo()">Salva</button>
    </div>
</div>

<!-- Modal Crea Prenotazione -->
<div class="pefo-modal" id="modalCreaPrenotazione">
    <div class="modal-header-custom">
        <h5>Nuova Prenotazione PEFO</h5>
    </div>
    <div class="modal-body-custom">
        <div class="form-group mb-3">
            <label for="inputTipoProva" class="form-label">Tipo Prova: <span class="text-danger">*</span></label>
            <select id="inputTipoProva" class="form-control" required>
                <option value="">Seleziona...</option>
                <option value="agility">Agility</option>
                <option value="resistenza">Resistenza</option>
            </select>
        </div>
        <div class="form-group mb-3">
            <label for="inputDataPrenotazione" class="form-label">Data: <span class="text-danger">*</span></label>
            <input type="date" id="inputDataPrenotazione" class="form-control" required>
        </div>
    </div>
    <div class="modal-footer-custom">
        <button type="button" class="btn btn-secondary" onclick="closeAllModals()">Annulla</button>
        <button type="button" class="btn btn-success" onclick="creaPrenotazione()">Crea Prenotazione</button>
    </div>
</div>

<!-- Modal Aggiungi Militari -->
<div class="pefo-modal" id="modalAggiungiMilitari" style="min-width: 800px;">
    <div class="modal-header-custom">
        <h5>Aggiungi Militari alla Prenotazione</h5>
    </div>
    <div class="modal-body-custom">
        <div class="prenotazione-info-item mb-3" id="modalPrenotazioneInfo"></div>
        
        <!-- Ricerca -->
        <div class="mb-3">
            <input type="text" id="searchMilitariDisponibili" class="form-control" placeholder="Cerca per cognome o nome...">
        </div>
        
        <!-- Filtro compagnia -->
        <div class="mb-3">
            <select id="filterCompagniaModal" class="form-select">
                <option value="">Tutte le compagnie</option>
                @foreach($compagnie as $compagnia)
                <option value="{{ $compagnia->id }}">{{ $compagnia->nome }}</option>
                @endforeach
            </select>
        </div>
        
        <!-- Tabella militari disponibili -->
        <div class="militari-select-table">
            <table class="table table-hover table-sm" id="tabellaMilitariDisponibili">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAllMilitari"></th>
                        <th>Grado</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>Data Nascita</th>
                        <th>Età</th>
                        <th>Compagnia</th>
                    </tr>
                </thead>
                <tbody id="tabellaMilitariDisponibiliBody">
                    <tr>
                        <td colspan="7" class="text-center">Caricamento...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="mt-3 text-muted">
            <small><span id="countSelezionati">0</span> militari selezionati</small>
        </div>
    </div>
    <div class="modal-footer-custom">
        <button type="button" class="btn btn-secondary" onclick="closeAllModals()">Annulla</button>
        <button type="button" class="btn btn-success" onclick="aggiungiMilitariSelezionati()">
            <i class="fas fa-plus me-1"></i>Aggiungi Selezionati
        </button>
    </div>
</div>

<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

@endsection

@push('scripts')
<script src="{{ asset('js/pefo.js') }}"></script>
<script>
// Inizializzazione
document.addEventListener('DOMContentLoaded', function() {
    // Imposta data minima per prenotazione a oggi
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('inputDataPrenotazione').setAttribute('min', today);
    
    // Carica militari iniziali
    loadMilitari();
    
    // Permessi
    window.canEdit = {{ $canEdit ? 'true' : 'false' }};
    window.canGestisciPrenotazioni = {{ $canGestisciPrenotazioni ? 'true' : 'false' }};
});
</script>
@endpush
