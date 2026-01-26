@extends('layouts.app')
@section('title', 'Organici - SUGECO')

@section('content')
<style>
/* ========================================
   ORGANICI - Stile coerente con il sito
   ======================================== */

/* Header pagina */
.page-title {
    font-family: 'Oswald', sans-serif;
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--navy);
    margin: 0;
    letter-spacing: 0.5px;
}

/* Layout principale */
.organici-container {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 20px;
    min-height: calc(100vh - 200px);
}

/* ========================================
   SIDEBAR
   ======================================== */
.sidebar-panel {
    background: #fff;
    border: 1px solid var(--border-color);
}

.sidebar-title {
    background: var(--navy);
    color: #fff;
    padding: 12px 15px;
    font-family: 'Oswald', sans-serif;
    font-size: 0.85rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.nav-list { list-style: none; padding: 0; margin: 0; }

.nav-list li {
    border-bottom: 1px solid var(--border-color);
}
.nav-list li:last-child { border-bottom: none; }

.nav-list a,
.nav-list .nav-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    color: var(--gray-700);
    text-decoration: none;
    cursor: pointer;
    transition: var(--transition-fast);
    font-size: 0.9rem;
}
.nav-list a:hover,
.nav-list .nav-link:hover {
    background: var(--gray-100);
    color: var(--navy);
}
.nav-list a.active,
.nav-list .nav-link.active {
    background: var(--navy);
    color: #fff;
    font-weight: 500;
}
.nav-list .count {
    font-size: 0.75rem;
    background: rgba(0,0,0,0.1);
    padding: 2px 8px;
    border-radius: 10px;
}
.nav-list a.active .count,
.nav-list .nav-link.active .count {
    background: rgba(255,255,255,0.2);
}

/* Sezione Teatri espandibile */
.teatri-section {
    border-top: 1px solid var(--border-color);
}
.teatri-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: var(--gray-100);
    cursor: pointer;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: var(--gray-700);
    letter-spacing: 0.3px;
}
.teatri-header .arrow {
    transition: transform 0.2s;
    font-size: 0.7rem;
}
.teatri-header.collapsed .arrow { transform: rotate(-90deg); }

.teatri-list {
    max-height: 250px;
    overflow-y: auto;
    transition: max-height 0.3s;
}
.teatri-list.collapsed { max-height: 0; overflow: hidden; }

.teatro-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    font-size: 0.88rem;
    color: var(--gray-700);
    transition: var(--transition-fast);
}
.teatro-item:last-child { border-bottom: none; }
.teatro-item:hover { background: var(--gray-100); }
.teatro-item.active {
    background: var(--navy-light);
    color: #fff;
}
.teatro-item .info {
    display: flex;
    align-items: center;
    gap: 8px;
}
.teatro-item .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.teatro-item .dot.confermato { background: var(--success); }
.teatro-item .dot.bozza { background: var(--warning); }
.teatro-item .dot.vuoto { background: var(--gray-400); }

.btn-nuovo-teatro {
    display: block;
    width: calc(100% - 20px);
    margin: 10px;
    padding: 10px;
    background: var(--gold);
    color: var(--navy);
    border: none;
    font-weight: 600;
    font-size: 0.8rem;
    cursor: pointer;
    text-align: center;
    transition: var(--transition-fast);
}
.btn-nuovo-teatro:hover { background: var(--gold-dark); }

/* ========================================
   PANNELLO CONTENUTO
   ======================================== */
.content-panel {
    background: #fff;
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
}

.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    background: var(--gray-100);
}
.content-header h2 {
    font-family: 'Oswald', sans-serif;
    font-size: 1.1rem;
    font-weight: 500;
    color: var(--navy);
    margin: 0;
}
.content-actions { display: flex; gap: 10px; }

.btn-action {
    padding: 8px 14px;
    font-size: 0.8rem;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: var(--transition-fast);
}
.btn-action.primary { background: var(--navy); color: #fff; }
.btn-action.primary:hover { background: var(--navy-light); }
.btn-action.gold { background: var(--gold); color: var(--navy); }
.btn-action.gold:hover { background: var(--gold-dark); }
.btn-action.outline { background: #fff; border: 1px solid var(--border-color); color: var(--gray-700); }
.btn-action.outline:hover { background: var(--gray-100); }

/* Filtri */
.filters-row {
    display: flex;
    gap: 15px;
    padding: 12px 20px;
    background: #fff;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    align-items: flex-end;
}
.filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.filter-item label {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
}
.filter-item select,
.filter-item input {
    padding: 7px 10px;
    border: 1px solid var(--border-color);
    font-size: 0.85rem;
    min-width: 130px;
    background: #fff;
}
.filter-item select:focus,
.filter-item input:focus {
    outline: none;
    border-color: var(--navy);
}

/* Tabella */
.table-container {
    flex: 1;
    overflow: auto;
}

.sugeco-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.sugeco-table thead th {
    background: var(--navy);
    color: #fff;
    padding: 12px 14px;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    position: sticky;
    top: 0;
    z-index: 5;
    border: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sugeco-table tbody tr {
    border-bottom: 1px solid var(--border-color);
}
.sugeco-table tbody tr:hover {
    background: rgba(10, 35, 66, 0.03);
}
.sugeco-table tbody td {
    padding: 10px 14px;
    font-size: 0.88rem;
    color: var(--gray-800);
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Modal Conferma Custom */
.confirm-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}
.confirm-overlay.show {
    opacity: 1;
    visibility: visible;
}
.confirm-box {
    background: #fff;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    transform: scale(0.9);
    transition: transform 0.2s ease;
}
.confirm-overlay.show .confirm-box {
    transform: scale(1);
}
.confirm-header {
    background: var(--navy);
    color: #fff;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 1rem;
}
.confirm-body {
    padding: 25px 20px;
    font-size: 0.95rem;
    color: var(--gray-700);
    line-height: 1.5;
}
.confirm-footer {
    padding: 15px 20px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid var(--border-color);
}
.confirm-footer button {
    padding: 10px 20px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    border: none;
    transition: all 0.15s;
}
.confirm-footer .btn-cancel {
    background: var(--gray-200);
    color: var(--gray-700);
}
.confirm-footer .btn-cancel:hover {
    background: var(--gray-300);
}
.confirm-footer .btn-confirm {
    background: var(--navy);
    color: #fff;
}
.confirm-footer .btn-confirm:hover {
    background: var(--navy-light);
}

.col-grado { color: var(--text-muted); }
.col-link {
    color: var(--navy);
    text-decoration: none;
    font-weight: 600;
}
.col-link:hover { text-decoration: underline; }
.col-secondary { color: var(--text-muted); }

.col-teatro.assegnato { color: var(--navy); font-weight: 500; }
.col-teatro.non-assegnato { color: var(--gray-400); font-style: italic; }
.stato-badge {
    display: inline-block;
    font-size: 0.65rem;
    padding: 2px 6px;
    margin-left: 5px;
    font-weight: 600;
}
.stato-badge.conf { background: #d1fae5; color: #065f46; }
.stato-badge.bozza { background: #fef3c7; color: #92400e; }

/* Toggle Stato */
.stato-toggle {
    display: inline-flex;
    border: 1px solid var(--border-color);
}
.stato-toggle button {
    padding: 4px 10px;
    font-size: 0.72rem;
    border: none;
    background: #fff;
    cursor: pointer;
    transition: var(--transition-fast);
}
.stato-toggle button:first-child { border-right: 1px solid var(--border-color); }
.stato-toggle button.active-bozza { background: #fef3c7; color: #92400e; font-weight: 600; }
.stato-toggle button.active-conf { background: #d1fae5; color: #065f46; font-weight: 600; }

/* Campo Ruolo */
.input-ruolo {
    border: 1px solid transparent;
    background: transparent;
    padding: 4px 8px;
    width: 120px;
    font-size: 0.85rem;
}
.input-ruolo:hover { background: var(--gray-100); border-color: var(--border-color); }
.input-ruolo:focus { outline: none; background: #fff; border-color: var(--navy); }

/* Bottoni riga */
.btn-row {
    padding: 4px 10px;
    font-size: 0.72rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-fast);
}
.btn-row.assign { background: var(--navy); color: #fff; border: none; }
.btn-row.assign:hover { background: var(--navy-light); }
.btn-row.remove { background: #fff; color: var(--danger); border: 1px solid #fecaca; }
.btn-row.remove:hover { background: #fef2f2; }

/* Riepilogo */
.summary-bar {
    display: flex;
    gap: 30px;
    padding: 14px 20px;
    background: var(--navy);
    color: #fff;
}
.summary-item { text-align: center; }
.summary-value { font-size: 1.3rem; font-weight: 700; line-height: 1; }
.summary-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.8; margin-top: 3px; }
.summary-item.highlight .summary-value { color: var(--gold); }

/* Empty e Loading */
.empty-state,
.loading-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-muted);
}

/* Modal */
.modal-header-custom {
    background: var(--navy);
    color: #fff;
    padding: 15px 20px;
}
.modal-header-custom .modal-title { font-weight: 600; }
.modal-header-custom .btn-close { filter: invert(1); opacity: 0.8; }

.lista-militari {
    max-height: 350px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
}
.lista-mil-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid var(--border-color);
}
.lista-mil-item:last-child { border-bottom: none; }
.lista-mil-item:hover { background: var(--gray-100); }
</style>

<!-- Header pagina -->
<div class="text-center mb-4">
    <h1 class="page-title">Organici</h1>
</div>

<div class="organici-container">
    <!-- Sidebar -->
    <div class="sidebar-panel">
        <ul class="nav-list">
            <li><a class="nav-link active" id="navTutti" onclick="selezionaVista(null)">
                <span>Tutto il Personale</span>
                <span class="count" id="countTutti">0</span>
            </a></li>
            <li><a class="nav-link" id="navNonAssegnati" onclick="selezionaVista('non_assegnati')">
                <span>Non Assegnati</span>
                <span class="count" id="countNonAssegnati">0</span>
            </a></li>
        </ul>
        
        <div class="teatri-section">
            <div class="teatri-header" onclick="toggleTeatri()">
                <span>Teatri Operativi</span>
                <span class="arrow">▼</span>
            </div>
            <div class="teatri-list" id="teatriList">
                <!-- Popolato via JS -->
            </div>
            @if($canEdit)
            <button class="btn-nuovo-teatro" onclick="apriModalNuovoTeatro()">+ Nuovo Teatro</button>
            @endif
        </div>
    </div>

    <!-- Contenuto principale -->
    <div class="content-panel">
        <div class="content-header">
            <h2 id="titoloVista">Tutto il Personale</h2>
            <div class="content-actions" id="azioniVista"></div>
        </div>

        <div class="filters-row">
            <div class="filter-item">
                <label>Compagnia</label>
                <select id="filtroCompagnia" onchange="caricaMilitari()">
                    <option value="">Tutte</option>
                    @foreach($compagnie as $c)
                    <option value="{{ $c->id }}">{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Ufficio</label>
                <select id="filtroUfficio" onchange="caricaMilitari()">
                    <option value="">Tutti</option>
                    @foreach($uffici as $u)
                    <option value="{{ $u->id }}">{{ $u->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item">
                <label>Incarico</label>
                <select id="filtroIncarico" onchange="caricaMilitari()">
                    <option value="">Tutti</option>
                    @foreach($incarichi as $i)
                    <option value="{{ $i->id }}">{{ $i->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-item" id="filtroStatoWrap" style="display:none;">
                <label>Stato</label>
                <select id="filtroStato" onchange="caricaMilitari()">
                    <option value="">Tutti</option>
                    <option value="bozza">In Bozza</option>
                    <option value="confermato">Confermato</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Cerca</label>
                <input type="text" id="searchInput" placeholder="Cognome o nome..." oninput="cercaLocale()">
            </div>
        </div>

        <div class="table-container">
            <table class="sugeco-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Grado</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        <th>Compagnia</th>
                        <th>Ufficio</th>
                        <th>Incarico</th>
                        <th>Cellulare</th>
                        <th id="colTeatroHead">Teatro Operativo</th>
                        <th id="colStatoHead" style="display:none;">Stato</th>
                        <th id="colRuoloHead" style="display:none;">Ruolo T.O.</th>
                        @if($canEdit)<th style="width:80px;"></th>@endif
                    </tr>
                </thead>
                <tbody id="tabellaBody">
                    <tr><td colspan="11" class="loading-state">Caricamento...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="summary-bar" id="riepilogo">
            <div class="summary-item"><div class="summary-value" id="rTotale">-</div><div class="summary-label">Totale</div></div>
            <div class="summary-item highlight"><div class="summary-value" id="rAssegnati">-</div><div class="summary-label">Assegnati</div></div>
            <div class="summary-item"><div class="summary-value" id="rNonAssegnati">-</div><div class="summary-label">Non Assegnati</div></div>
        </div>
    </div>
</div>

<!-- Modal Nuovo Teatro -->
<div class="modal fade" id="modalNuovoTeatro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Nuovo Teatro Operativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome Teatro *</label>
                    <input type="text" class="form-control" id="nuovoTeatroNome" placeholder="Es. Kosovo, Libano...">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Inizio</label>
                        <input type="date" class="form-control" id="nuovoTeatroDataInizio">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Fine</label>
                        <input type="date" class="form-control" id="nuovoTeatroDataFine">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mounting (Compagnia di appartenenza CPT)</label>
                    <select class="form-select" id="nuovoTeatroMounting">
                        <option value="">Nessun mounting</option>
                        @foreach($compagnie as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">I militari assegnati appariranno nel CPT e nell'anagrafica di questa compagnia</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="creaTeatro()">Crea Teatro</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifica Teatro -->
<div class="modal fade" id="modalModificaTeatro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Modifica Teatro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modTeatroId">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nome Teatro *</label>
                    <input type="text" class="form-control" id="modTeatroNome">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Inizio</label>
                        <input type="date" class="form-control" id="modTeatroDataInizio">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data Fine</label>
                        <input type="date" class="form-control" id="modTeatroDataFine">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stato</label>
                    <select class="form-select" id="modTeatroStato">
                        <option value="attivo">Attivo</option>
                        <option value="pianificato">Pianificato</option>
                        <option value="sospeso">Sospeso</option>
                        <option value="completato">Completato</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mounting (Compagnia di appartenenza CPT)</label>
                    <select class="form-select" id="modTeatroMounting">
                        <option value="">Nessun mounting</option>
                        @foreach($compagnie as $c)
                        <option value="{{ $c->id }}">{{ $c->nome }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">I militari assegnati appariranno nel CPT e nell'anagrafica di questa compagnia</small>
                </div>
            </div>
            <div class="modal-footer" style="justify-content: space-between;">
                <button type="button" class="btn btn-danger" onclick="eliminaTeatro()">Elimina</button>
                <div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-primary" onclick="modificaTeatro()">Salva</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Militare -->
<div class="modal fade" id="modalAggiungiMilitare" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Aggiungi Militare a <span id="nomeTeatro"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="searchMilModal" placeholder="Cerca per cognome o nome..." oninput="filtraMilitariModal()">
                <div class="lista-militari" id="listaMilitariModal">
                    <div class="loading-state">Caricamento...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Assegna Teatro -->
<div class="modal fade" id="modalAssegnaTeatro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Assegna a Teatro Operativo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="assegnaMilitareId">
                <p id="assegnaMilitareNome" style="font-weight: 600; margin-bottom: 15px; color: var(--navy);"></p>
                <div class="mb-3">
                    <label class="form-label">Seleziona Teatro</label>
                    <select class="form-select" id="assegnaTeatroSelect"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="eseguiAssegnazione()">Assegna</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Conferma Custom -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-header" id="confirmTitle">Conferma</div>
        <div class="confirm-body" id="confirmMessage">Sei sicuro?</div>
        <div class="confirm-footer">
            <button class="btn-cancel" onclick="chiudiConferma()">Annulla</button>
            <button class="btn-confirm" id="confirmBtn">Conferma</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';
const canEdit = {{ $canEdit ? 'true' : 'false' }};
const apiBase = '{{ url("impieghi-personale/api") }}';

let teatroSelezionato = null;
let teatriData = [];
let militariData = [];
let teatriExpanded = true;

document.addEventListener('DOMContentLoaded', function() {
    caricaTeatri();
    caricaMilitari();
});

function toggleTeatri() {
    teatriExpanded = !teatriExpanded;
    document.querySelector('.teatri-header').classList.toggle('collapsed', !teatriExpanded);
    document.getElementById('teatriList').classList.toggle('collapsed', !teatriExpanded);
}

function caricaTeatri() {
    fetch(apiBase + '/teatri')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                teatriData = data.teatri;
                renderTeatriList();
            }
        });
}

function caricaMilitari() {
    const params = new URLSearchParams();
    if (teatroSelezionato) params.set('teatro_id', teatroSelezionato);
    
    const compagnia = document.getElementById('filtroCompagnia').value;
    const ufficio = document.getElementById('filtroUfficio').value;
    const incarico = document.getElementById('filtroIncarico').value;
    const stato = document.getElementById('filtroStato').value;
    
    if (compagnia) params.set('compagnia_id', compagnia);
    if (ufficio) params.set('ufficio_id', ufficio);
    if (incarico) params.set('incarico_id', incarico);
    if (stato && teatroSelezionato && teatroSelezionato !== 'non_assegnati') params.set('stato', stato);
    
    document.getElementById('tabellaBody').innerHTML = '<tr><td colspan="11" class="loading-state">Caricamento...</td></tr>';
    
    fetch(apiBase + '/militari?' + params.toString())
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                militariData = data.militari;
                renderTabella();
                renderRiepilogo(data.conteggi);
                updateNavCounts(data.conteggi);
            }
        });
}

function renderTeatriList() {
    let html = '';
    if (teatriData.length === 0) {
        html = '<div style="padding:15px;color:var(--text-muted);text-align:center;font-size:0.85rem;">Nessun teatro</div>';
    } else {
        teatriData.forEach(t => {
            const isActive = teatroSelezionato == t.id;
            html += `<div class="teatro-item ${isActive ? 'active' : ''}" onclick="selezionaVista(${t.id})">
                <span>${t.nome}</span>
                <div class="info">
                    <span class="count">${t.militari_count}</span>
                    <span class="dot ${t.stato_globale}"></span>
                </div>
            </div>`;
        });
    }
    document.getElementById('teatriList').innerHTML = html;
}

function updateNavCounts(conteggi) {
    document.getElementById('countTutti').textContent = conteggi.totale;
    document.getElementById('countNonAssegnati').textContent = conteggi.non_assegnati;
}

function renderTabella() {
    const tbody = document.getElementById('tabellaBody');
    const isVistaTeatro = teatroSelezionato && teatroSelezionato !== 'non_assegnati';
    
    document.getElementById('colTeatroHead').style.display = isVistaTeatro ? 'none' : '';
    document.getElementById('colStatoHead').style.display = isVistaTeatro ? '' : 'none';
    document.getElementById('colRuoloHead').style.display = isVistaTeatro ? '' : 'none';
    document.getElementById('filtroStatoWrap').style.display = isVistaTeatro ? '' : 'none';
    
    if (militariData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" class="empty-state">Nessun militare trovato</td></tr>';
        return;
    }
    
    let html = '';
    militariData.forEach(m => {
        html += `<tr class="mil-row" data-id="${m.id}" data-cognome="${m.cognome.toLowerCase()}" data-nome="${m.nome.toLowerCase()}">`;
        html += `<td class="col-grado">${m.grado}</td>`;
        html += `<td><a href="/SUGECO/public/anagrafica/${m.id}" class="col-link">${m.cognome}</a></td>`;
        html += `<td>${m.nome}</td>`;
        html += `<td class="col-secondary">${m.compagnia}</td>`;
        html += `<td class="col-secondary">${m.ufficio}</td>`;
        html += `<td class="col-secondary">${m.incarico}</td>`;
        html += `<td class="col-secondary">${m.telefono}</td>`;
        
        if (!isVistaTeatro) {
            if (m.teatro_nome) {
                const badgeClass = m.stato_assegnazione === 'confermato' ? 'conf' : 'bozza';
                const badgeText = m.stato_assegnazione === 'confermato' ? 'C' : 'B';
                html += `<td class="col-teatro assegnato">${m.teatro_nome}<span class="stato-badge ${badgeClass}">${badgeText}</span></td>`;
            } else {
                html += `<td class="col-teatro non-assegnato">Non assegnato</td>`;
            }
        } else {
            html += `<td style="display:none;"></td>`;
            html += '<td>';
            if (canEdit) {
                html += `<div class="stato-toggle">
                    <button class="${m.stato_assegnazione === 'bozza' ? 'active-bozza' : ''}" onclick="cambiaStato(${m.id}, 'bozza')">Bozza</button>
                    <button class="${m.stato_assegnazione === 'confermato' ? 'active-conf' : ''}" onclick="cambiaStato(${m.id}, 'confermato')">Conf.</button>
                </div>`;
            } else {
                html += `<span class="stato-badge ${m.stato_assegnazione === 'confermato' ? 'conf' : 'bozza'}">${m.stato_assegnazione}</span>`;
            }
            html += '</td>';
            html += '<td>';
            if (canEdit) {
                html += `<input type="text" class="input-ruolo" value="${m.ruolo || ''}" placeholder="Ruolo..." onchange="salvaRuolo(${m.id}, this.value)">`;
            } else {
                html += m.ruolo || '-';
            }
            html += '</td>';
        }
        
        if (canEdit) {
            html += '<td>';
            if (isVistaTeatro) {
                html += `<button class="btn-row remove" onclick="rimuoviDaTeatro(${m.id})">Rimuovi</button>`;
            } else if (!m.teatro_id) {
                html += `<button class="btn-row assign" onclick="apriModalAssegna(${m.id}, '${m.grado} ${m.cognome} ${m.nome}')">Assegna</button>`;
            } else {
                html += `<button class="btn-row remove" onclick="rimuoviDaTeatroGenerico(${m.id}, ${m.teatro_id})">Rimuovi</button>`;
            }
            html += '</td>';
        }
        html += '</tr>';
    });
    tbody.innerHTML = html;
}

function renderRiepilogo(conteggi) {
    const isVistaTeatro = teatroSelezionato && teatroSelezionato !== 'non_assegnati';
    const riepilogo = document.getElementById('riepilogo');
    
    if (isVistaTeatro) {
        riepilogo.innerHTML = `
            <div class="summary-item"><div class="summary-value">${conteggi.totale}</div><div class="summary-label">Totale</div></div>
            <div class="summary-item highlight"><div class="summary-value">${conteggi.confermati}</div><div class="summary-label">Confermati</div></div>
            <div class="summary-item"><div class="summary-value">${conteggi.bozze}</div><div class="summary-label">In Bozza</div></div>
        `;
    } else {
        riepilogo.innerHTML = `
            <div class="summary-item"><div class="summary-value">${conteggi.totale}</div><div class="summary-label">Totale</div></div>
            <div class="summary-item highlight"><div class="summary-value">${conteggi.assegnati}</div><div class="summary-label">Assegnati</div></div>
            <div class="summary-item"><div class="summary-value">${conteggi.non_assegnati}</div><div class="summary-label">Non Assegnati</div></div>
        `;
    }
}

function selezionaVista(id) {
    // Previeni reload se stessa vista
    if (id === teatroSelezionato) return;
    if (id === null && teatroSelezionato === null) return;
    if (id === 'non_assegnati' && teatroSelezionato === 'non_assegnati') return;
    
    teatroSelezionato = id;
    
    // Update nav
    document.querySelectorAll('.nav-link').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.teatro-item').forEach(el => el.classList.remove('active'));
    
    if (!id) document.getElementById('navTutti').classList.add('active');
    else if (id === 'non_assegnati') document.getElementById('navNonAssegnati').classList.add('active');
    
    // Titolo e azioni
    const titolo = document.getElementById('titoloVista');
    const azioni = document.getElementById('azioniVista');
    
    if (!id) {
        titolo.textContent = 'Tutto il Personale';
        azioni.innerHTML = '';
    } else if (id === 'non_assegnati') {
        titolo.textContent = 'Personale Non Assegnato';
        azioni.innerHTML = '';
    } else {
        const teatro = teatriData.find(t => t.id == id);
        titolo.textContent = teatro ? teatro.nome : 'Teatro';
        if (canEdit) {
            azioni.innerHTML = `
                <button class="btn-action gold" onclick="apriModalAggiungi()">+ Aggiungi Militare</button>
                <button class="btn-action primary" onclick="confermaTutti()">Conferma Tutti</button>
                <button class="btn-action outline" onclick="apriModalModificaTeatro()">Modifica</button>
            `;
        }
    }
    
    renderTeatriList();
    document.getElementById('filtroStato').value = '';
    caricaMilitari();
}

function cercaLocale() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.mil-row').forEach(row => {
        const cognome = row.dataset.cognome || '';
        const nome = row.dataset.nome || '';
        row.style.display = (cognome.includes(term) || nome.includes(term)) ? '' : 'none';
    });
}

// === OPERAZIONI TEATRI ===
function apriModalNuovoTeatro() {
    document.getElementById('nuovoTeatroNome').value = '';
    document.getElementById('nuovoTeatroDataInizio').value = '';
    document.getElementById('nuovoTeatroDataFine').value = '';
    new bootstrap.Modal(document.getElementById('modalNuovoTeatro')).show();
}

function creaTeatro() {
    const nome = document.getElementById('nuovoTeatroNome').value.trim();
    if (!nome) { alert('Inserisci il nome'); return; }
    
    fetch('{{ route("impieghi-personale.teatri.store") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            nome: nome,
            data_inizio: document.getElementById('nuovoTeatroDataInizio').value || null,
            data_fine: document.getElementById('nuovoTeatroDataFine').value || null,
            mounting_compagnia_id: document.getElementById('nuovoTeatroMounting').value || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalNuovoTeatro')).hide();
            caricaTeatri();
            teatroSelezionato = null; // reset per permettere selezione
            selezionaVista(data.teatro.id);
        } else {
            alert(data.message);
        }
    });
}

function apriModalModificaTeatro() {
    const teatro = teatriData.find(t => t.id == teatroSelezionato);
    if (!teatro) return;
    
    document.getElementById('modTeatroId').value = teatro.id;
    document.getElementById('modTeatroNome').value = teatro.nome;
    document.getElementById('modTeatroDataInizio').value = teatro.data_inizio ? teatro.data_inizio.split('/').reverse().join('-') : '';
    document.getElementById('modTeatroDataFine').value = teatro.data_fine ? teatro.data_fine.split('/').reverse().join('-') : '';
    document.getElementById('modTeatroStato').value = teatro.stato;
    document.getElementById('modTeatroMounting').value = teatro.mounting_compagnia_id || '';
    
    new bootstrap.Modal(document.getElementById('modalModificaTeatro')).show();
}

function modificaTeatro() {
    const id = document.getElementById('modTeatroId').value;
    
    fetch('{{ url("impieghi-personale/teatri") }}/' + id, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({
            nome: document.getElementById('modTeatroNome').value,
            data_inizio: document.getElementById('modTeatroDataInizio').value || null,
            data_fine: document.getElementById('modTeatroDataFine').value || null,
            stato: document.getElementById('modTeatroStato').value,
            mounting_compagnia_id: document.getElementById('modTeatroMounting').value || null
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalModificaTeatro')).hide();
            caricaTeatri();
            document.getElementById('titoloVista').textContent = document.getElementById('modTeatroNome').value;
        } else {
            alert(data.message);
        }
    });
}

function eliminaTeatro() {
    mostraConferma('Elimina Teatro', 'Eliminare questo Teatro e tutte le assegnazioni?', function() {
        fetch('{{ url("impieghi-personale/teatri") }}/' + document.getElementById('modTeatroId').value, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalModificaTeatro')).hide();
                teatroSelezionato = 'reset';
                selezionaVista(null);
                caricaTeatri();
            }
        });
    });
}

// === OPERAZIONI MILITARI ===
function apriModalAggiungi() {
    const teatro = teatriData.find(t => t.id == teatroSelezionato);
    document.getElementById('nomeTeatro').textContent = teatro ? teatro.nome : '';
    document.getElementById('searchMilModal').value = '';
    document.getElementById('listaMilitariModal').innerHTML = '<div class="loading-state">Caricamento...</div>';
    
    fetch(apiBase + '/teatro/' + teatroSelezionato + '/disponibili')
        .then(r => r.json())
        .then(data => {
            if (data.success) renderMilitariModal(data.militari);
        });
    
    new bootstrap.Modal(document.getElementById('modalAggiungiMilitare')).show();
}

function renderMilitariModal(militari) {
    if (militari.length === 0) {
        document.getElementById('listaMilitariModal').innerHTML = '<div class="empty-state">Tutti i militari sono già assegnati</div>';
        return;
    }
    let html = '';
    militari.forEach(m => {
        html += `<div class="lista-mil-item" data-cognome="${m.cognome.toLowerCase()}" data-nome="${m.nome.toLowerCase()}">
            <div><strong>${m.grado} ${m.cognome} ${m.nome}</strong><br><small style="color:var(--text-muted);">${m.compagnia}</small></div>
            <button class="btn-row assign" onclick="assegnaMilitare(${m.id})">Aggiungi</button>
        </div>`;
    });
    document.getElementById('listaMilitariModal').innerHTML = html;
}

function filtraMilitariModal() {
    const term = document.getElementById('searchMilModal').value.toLowerCase();
    document.querySelectorAll('#listaMilitariModal .lista-mil-item').forEach(item => {
        const cognome = item.dataset.cognome || '';
        const nome = item.dataset.nome || '';
        item.style.display = (cognome.includes(term) || nome.includes(term)) ? '' : 'none';
    });
}

function assegnaMilitare(militareId) {
    fetch('{{ route("impieghi-personale.militari.assegna") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ teatro_operativo_id: teatroSelezionato, militare_id: militareId, stato: 'bozza' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector(`#listaMilitariModal .lista-mil-item button[onclick="assegnaMilitare(${militareId})"]`)?.closest('.lista-mil-item')?.remove();
            caricaMilitari();
            caricaTeatri();
        } else {
            alert(data.message);
        }
    });
}

function rimuoviDaTeatro(militareId) {
    mostraConferma('Rimuovi Militare', 'Rimuovere questo militare dal Teatro?', function() {
        fetch('{{ route("impieghi-personale.militari.rimuovi") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ teatro_operativo_id: teatroSelezionato, militare_id: militareId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { caricaMilitari(); caricaTeatri(); }
        });
    });
}

function rimuoviDaTeatroGenerico(militareId, teatroId) {
    mostraConferma('Rimuovi Militare', 'Rimuovere questo militare dal Teatro?', function() {
        fetch('{{ route("impieghi-personale.militari.rimuovi") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ teatro_operativo_id: teatroId, militare_id: militareId })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { caricaMilitari(); caricaTeatri(); }
        });
    });
}

function cambiaStato(militareId, nuovoStato) {
    fetch('{{ route("impieghi-personale.militari.stato") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ teatro_operativo_id: teatroSelezionato, militare_id: militareId, stato: nuovoStato })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`tr[data-id="${militareId}"]`);
            if (row) {
                const btns = row.querySelectorAll('.stato-toggle button');
                btns.forEach(b => b.classList.remove('active-bozza', 'active-conf'));
                btns[nuovoStato === 'bozza' ? 0 : 1].classList.add(nuovoStato === 'bozza' ? 'active-bozza' : 'active-conf');
            }
            caricaTeatri();
            caricaMilitari();
        } else {
            alert(data.message);
        }
    });
}

function salvaRuolo(militareId, ruolo) {
    fetch('{{ route("impieghi-personale.militari.update") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ teatro_operativo_id: teatroSelezionato, militare_id: militareId, ruolo: ruolo })
    });
}

function confermaTutti() {
    mostraConferma('Conferma Tutti', 'Confermare tutti i militari in bozza?', function() {
        fetch('{{ route("impieghi-personale.militari.conferma-tutti") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ teatro_operativo_id: teatroSelezionato })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { caricaMilitari(); caricaTeatri(); }
        });
    });
}

// === MODAL CONFERMA CUSTOM ===
let confirmCallback = null;

function mostraConferma(titolo, messaggio, callback) {
    document.getElementById('confirmTitle').textContent = titolo;
    document.getElementById('confirmMessage').textContent = messaggio;
    confirmCallback = callback;
    document.getElementById('confirmOverlay').classList.add('show');
    document.getElementById('confirmBtn').onclick = function() {
        const cb = confirmCallback; // Salva prima di resettare
        chiudiConferma();
        if (cb) cb(); // Esegui dopo aver chiuso
    };
}

function chiudiConferma() {
    document.getElementById('confirmOverlay').classList.remove('show');
    confirmCallback = null;
}

// === ASSEGNAZIONE DA VISTA GENERALE ===
function apriModalAssegna(militareId, nomeCompleto) {
    document.getElementById('assegnaMilitareId').value = militareId;
    document.getElementById('assegnaMilitareNome').textContent = nomeCompleto;
    
    let html = '<option value="">Seleziona...</option>';
    teatriData.forEach(t => { html += `<option value="${t.id}">${t.nome}</option>`; });
    document.getElementById('assegnaTeatroSelect').innerHTML = html;
    
    new bootstrap.Modal(document.getElementById('modalAssegnaTeatro')).show();
}

function eseguiAssegnazione() {
    const militareId = document.getElementById('assegnaMilitareId').value;
    const teatroId = document.getElementById('assegnaTeatroSelect').value;
    
    if (!teatroId) { alert('Seleziona un teatro'); return; }
    
    fetch('{{ route("impieghi-personale.militari.assegna") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ teatro_operativo_id: teatroId, militare_id: militareId, stato: 'bozza' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalAssegnaTeatro')).hide();
            caricaMilitari();
            caricaTeatri();
        } else {
            alert(data.message);
        }
    });
}
</script>
@endpush
