@extends('layouts.app')

@section('title', $activity->title)

@section('content')
<div class="container-fluid">
    <!-- Header con Breadcrumb e Azioni -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                @if($activity->compagniaMounting)
                    <span class="badge" style="background-color: {{ $activity->compagniaMounting->colore ?? '#6c757d' }}; font-size: 0.9rem; vertical-align: middle;">
                        <i class="fas fa-flag me-1"></i>{{ $activity->compagniaMounting->nome }}
                    </span>
                @endif
                {{ $activity->title }}
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('board.index') }}">Hub Attività</a></li>
                    <li class="breadcrumb-item active">{{ $activity->title }}</li>
                </ol>
            </nav>
        </div>
            <div class="btn-group">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal">
                <i class="fas fa-edit me-1"></i>Modifica
                </button>
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash me-1"></i>Elimina
                </button>
        </div>
    </div>

    <div class="row">
        <!-- Colonna Principale -->
        <div class="col-lg-8">
            <!-- Info Attività -->
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Data Inizio</label>
                            <div><i class="fas fa-calendar me-2 text-primary"></i>{{ $activity->start_date->format('d/m/Y') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small mb-1">Data Fine</label>
                            <div><i class="fas fa-calendar-check me-2 text-primary"></i>{{ $activity->end_date ? $activity->end_date->format('d/m/Y') : 'Non specificata' }}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Descrizione</label>
                        <p class="mb-0">{{ $activity->description ?: 'Nessuna descrizione' }}</p>
                    </div>
                    <div>
                        <label class="text-muted small mb-1">Tipologia</label>
                        <div>
                            @php
                                $colors = [
                                    'servizi-isolati' => 'secondary',
                                    'esercitazioni' => 'warning',
                                    'stand-by' => 'warning',
                                    'operazioni' => 'danger',
                                    'corsi' => 'primary',
                                    'cattedre' => 'success'
                                ];
                                $color = $colors[$activity->column->slug] ?? 'primary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ $activity->column->name }}</span>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            
        <!-- Colonna Laterale - Militari -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-users me-2"></i>Militari Coinvolti ({{ $activity->militari->count() }})</h6>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addMilitareModal">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div class="card-body p-0">
                    @if($activity->militari->count() > 0)
                        <div id="militariList">
                            @php
                                // Ordina e raggruppa i militari per compagnia
                                $militariPerCompagnia = $activity->militari->sortBy(function($m) {
                                    $compagniaOrdine = 999;
                                    if ($m->compagnia) {
                                        if ($m->compagnia->nome == '110') $compagniaOrdine = 1;
                                        elseif ($m->compagnia->nome == '124') $compagniaOrdine = 2;
                                        elseif ($m->compagnia->nome == '127') $compagniaOrdine = 3;
                                    }
                                    $gradoOrdine = -1 * (optional($m->grado)->ordine ?? 0);
                                    return [$compagniaOrdine, $gradoOrdine, $m->cognome, $m->nome];
                                })->groupBy(function($m) {
                                    return $m->compagnia ? $m->compagnia->nome : 'Senza Compagnia';
                                });
                            @endphp
                            @foreach($militariPerCompagnia as $compagniaNome => $militari)
                                <div class="compagnia-group">
                                    <div class="px-3 py-2 bg-light border-bottom">
                                        <strong class="text-uppercase" style="font-size: 0.85rem; color: #0A2342; letter-spacing: 0.5px;">
                                            {{ $compagniaNome }} ({{ $militari->count() }})
                                        </strong>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        @foreach($militari as $militare)
                                <div class="list-group-item d-flex justify-content-between align-items-center py-2" data-militare-id="{{ $militare->id }}">
                                    <div class="flex-grow-1">
                                        <strong class="d-block">{{ optional($militare->grado)->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</strong>
                                        <small class="text-muted">
                                                        @if($militare->plotone){{ $militare->plotone->nome }}@endif
                                        </small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMilitare({{ $militare->id }}, '{{ optional($militare->grado)->abbreviazione }} {{ $militare->cognome }}')">
                                        <i class="fas fa-times"></i>
                                        </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-2x mb-2"></i>
                            <p class="mb-0">Nessun militare assegnato</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
            </div>
            
<!-- Modal Modifica Attività -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('board.activities.update', $activity) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifica Attività</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Compagnia Mounting *</label>
                        <select class="form-select" name="compagnia_mounting_id" required>
                            @foreach(\App\Models\Compagnia::orderBy('nome')->get() as $comp)
                                <option value="{{ $comp->id }}" {{ $activity->compagnia_mounting_id == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Titolo *</label>
                        <input type="text" class="form-control" name="title" value="{{ $activity->title }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrizione</label>
                        <textarea class="form-control" name="description" rows="3">{{ $activity->description }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Data Inizio *</label>
                            <input type="date" class="form-control" name="start_date" value="{{ $activity->start_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Data Fine</label>
                            <input type="date" class="form-control" name="end_date" value="{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : '' }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipologia *</label>
                        <select class="form-select" name="column_id" required>
                            @foreach(\App\Models\BoardColumn::orderBy('order')->get() as $col)
                                <option value="{{ $col->id }}" {{ $activity->column_id == $col->id ? 'selected' : '' }}>{{ $col->name }}</option>
                            @endforeach
                        </select>
                            </div>
                            </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Salva</button>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Aggiungi Militari (Multiplo) -->
<div class="modal fade" id="addMilitareModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Aggiungi Militari</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <!-- Step 1: Selezione Militari -->
                <div id="step1">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Seleziona Militari *</label>
                        
                        <!-- Custom Militari Selector -->
                        <div class="militari-selector-wrapper">
                            <!-- Barra di ricerca in alto -->
                            <div class="militari-search-section">
                                <input type="text" id="militari-search-modal" class="militari-search-input" placeholder="Cerca militare per nome, cognome o grado...">
                            </div>
                            
                            <!-- Badge militari selezionati -->
                            <div class="militari-selected-section" id="militari-selected-modal">
                                <span class="empty-message">Nessun militare selezionato</span>
                            </div>
                            
                            <!-- Lista militari disponibili -->
                            <div class="militari-list-section" id="militari-list-modal">
                                @php
                                    // Verifica se l'utente può vedere tutti i militari nella Board
                                    $userModal = auth()->user();
                                    $canViewAllMilitariModal = $userModal && (
                                        $userModal->isGlobalAdmin() || 
                                        $userModal->hasPermission('board.view_all_militari') ||
                                        $userModal->hasPermission('view_all_companies')
                                    );
                                    
                                    // Query con ordinamento corretto: prima compagnia (110, 124, 127), poi grado decrescente
                                    $militariModalQuery = $canViewAllMilitariModal 
                                        ? \App\Models\Militare::withoutGlobalScopes() 
                                        : \App\Models\Militare::query();
                                    
                                    $militariModal = $militariModalQuery->with(['grado', 'compagnia'])
                                        ->leftJoin('compagnie', 'militari.compagnia_id', '=', 'compagnie.id')
                                        ->leftJoin('gradi', 'militari.grado_id', '=', 'gradi.id')
                                        ->orderByRaw("CASE 
                                            WHEN compagnie.nome = '110' THEN 1
                                            WHEN compagnie.nome = '124' THEN 2
                                            WHEN compagnie.nome = '127' THEN 3
                                            ELSE 999 END")
                                        ->orderBy('gradi.ordine', 'desc')
                                        ->orderBy('militari.cognome')
                                        ->orderBy('militari.nome')
                                        ->select('militari.*')
                                        ->get()
                                        ->groupBy('compagnia.nome');
                                @endphp
                                @foreach($militariModal as $compNome => $mils)
                                  <div class="militari-group">
                                    <div class="militari-group-header">{{ $compNome }}</div>
                                    @foreach($mils as $mil)
                                      <div class="militare-item" 
                                           data-id="{{ $mil->id }}" 
                                                    data-nome="{{ optional($mil->grado)->abbreviazione }} {{ $mil->cognome }} {{ $mil->nome }}"
                                           data-gia-presente="{{ $activity->militari->contains($mil->id) ? '1' : '0' }}">
                                        <i class="fas fa-user militare-item-icon"></i>
                                        <span class="militare-item-name">{{ optional($mil->grado)->abbreviazione }} {{ $mil->cognome }} {{ $mil->nome }}</span>
                                        <i class="fas fa-check militare-item-check" style="display: none;"></i>
                                      </div>
                                    @endforeach
                                  </div>
                            @endforeach
                    </div>
                            
                            <!-- Counter -->
                            <div class="militari-counter">
                                <span id="militari-count-modal">0</span> militari selezionati
                </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-primary" id="verificaBtn">
                            <i class="fas fa-search me-1"></i>Verifica Disponibilità
                    </button>
    </div>
</div>

                <!-- Step 2: Risultati Verifica -->
                <div id="step2" class="d-none">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Verifica completata!</strong> Controlla i risultati e procedi con l'assegnazione.
</div>

                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="sugeco-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="40"><input type="checkbox" id="selectAllCheck" checked></th>
                                    <th>Militare</th>
                                    <th>Compagnia</th>
                                    <th>Stato</th>
                                    <th>Dettagli Conflitti</th>
                                </tr>
                            </thead>
                            <tbody id="risultatiVerifica">
                                <!-- Popolato dinamicamente -->
                            </tbody>
                        </table>
                </div>
                    
                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" onclick="tornaStep1()">
                            <i class="fas fa-arrow-left me-1"></i>Cambia Selezione
                    </button>
                        </div>
                    </div>
                    </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-success d-none" id="aggiungiSelezionatiBtn">
                    <i class="fas fa-check me-1"></i>Aggiungi Selezionati (<span id="countSelezionati">0</span>)
                    </button>
                </div>
        </div>
    </div>
</div>

<!-- Modal Conferma Rimozione Militare -->
<div class="modal fade" id="confirmRemoveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-minus text-warning me-2"></i>Rimuovi Militare</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <p class="mb-3">Vuoi rimuovere <strong id="removeMilitareNome"></strong> da questa attività?</p>
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Il militare sarà rimosso anche dal CPT per le date di questa attività.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" onclick="confirmaRimuoviMilitare()" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i>Conferma Rimozione
                    </button>
                </div>
        </div>
    </div>
</div>

<!-- Modal Elimina Attività -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('board.activities.destroy', $activity) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Elimina Attività</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Sei sicuro di voler eliminare l'attività <strong>{{ $activity->title }}</strong>?</p>
                    <div class="alert alert-warning mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Attenzione:</strong> Questa operazione eliminerà definitivamente l'attività e la rimuoverà dal CPT. Non può essere annullata.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Elimina Definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Toast Notifiche -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="toast" class="toast" role="alert">
        <div class="toast-header">
            <i id="toastIcon" class="fas fa-info-circle me-2"></i>
            <strong class="me-auto" id="toastTitle">Notifica</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastBody"></div>
    </div>
</div>
@endsection

@push('scripts')
<link href="{{ asset('vendor/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('vendor/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
<script src="{{ asset('vendor/js/select2.min.js') }}"></script>
<script>
const activityId = {{ $activity->id }};
const activityStartDate = '{{ $activity->start_date->format('Y-m-d') }}';
const activityEndDate = '{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : $activity->start_date->format('Y-m-d') }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let militariSelezionatiModal = new Set();
let risultatiVerifica = new Map(); // militareId => {nome, compagnia, disponibile, conflitti[]}
    
// ==========================================
// CUSTOM MILITARI SELECTOR - Modal Modifica
// ==========================================

$(document).ready(function() {
    const searchInputModal = document.getElementById('militari-search-modal');
    const selectedContainerModal = document.getElementById('militari-selected-modal');
    const listContainerModal = document.getElementById('militari-list-modal');
    const counterSpanModal = document.getElementById('militari-count-modal');
    const verificaBtnModal = document.getElementById('verificaBtn');
    
    // Pre-seleziona i militari già presenti nell'attività
    listContainerModal.querySelectorAll('.militare-item[data-gia-presente="1"]').forEach(item => {
        const id = item.dataset.id;
        militariSelezionatiModal.add(id);
    });
    
    // Funzione per aggiornare il counter
    function aggiornaCounterModal() {
        counterSpanModal.textContent = militariSelezionatiModal.size;
        verificaBtnModal.disabled = militariSelezionatiModal.size === 0;
    }
    
    // Funzione per aggiornare la sezione badge
    function aggiornaBadgesModal() {
        selectedContainerModal.innerHTML = '';
        
        if (militariSelezionatiModal.size === 0) {
            selectedContainerModal.classList.add('empty');
            selectedContainerModal.innerHTML = '<span class="empty-message">Nessun militare selezionato</span>';
            return;
        }
        
        selectedContainerModal.classList.remove('empty');
        
        militariSelezionatiModal.forEach(id => {
            const item = listContainerModal.querySelector(`[data-id="${id}"]`);
            if (item) {
                const nome = item.dataset.nome;
                const badge = document.createElement('div');
                badge.className = 'militare-badge';
                badge.innerHTML = `
                    <span class="militare-badge-name">${nome}</span>
                    <span class="militare-badge-remove" data-id="${id}">&times;</span>
                `;
                selectedContainerModal.appendChild(badge);
            }
        });
        
        // Aggiungi listener per rimuovere badge
        selectedContainerModal.querySelectorAll('.militare-badge-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                militariSelezionatiModal.delete(id);
                aggiornaInterfacciaModal();
            });
        });
    }
    
    // Funzione per aggiornare lo stato degli item nella lista
    function aggiornaListaStatiModal() {
        listContainerModal.querySelectorAll('.militare-item').forEach(item => {
            const id = item.dataset.id;
            const checkIcon = item.querySelector('.militare-item-check');
            
            if (militariSelezionatiModal.has(id)) {
                item.classList.add('selected');
                checkIcon.style.display = 'inline';
            } else {
                item.classList.remove('selected');
                checkIcon.style.display = 'none';
            }
        });
    }
    
    // Funzione per aggiornare tutta l'interfaccia
    function aggiornaInterfacciaModal() {
        aggiornaBadgesModal();
        aggiornaListaStatiModal();
        aggiornaCounterModal();
    }
    
    // Click su un militare nella lista
    if (listContainerModal) {
        listContainerModal.addEventListener('click', function(e) {
            const item = e.target.closest('.militare-item');
            if (!item || item.classList.contains('disabled')) return;
            
            const id = item.dataset.id;
            
            if (militariSelezionatiModal.has(id)) {
                militariSelezionatiModal.delete(id);
            } else {
                militariSelezionatiModal.add(id);
            }
            
            aggiornaInterfacciaModal();
        });
    }
    
    // Ricerca militari
    if (searchInputModal) {
        searchInputModal.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            let hasResults = false;
            
            listContainerModal.querySelectorAll('.militari-group').forEach(group => {
                const items = group.querySelectorAll('.militare-item');
                let groupHasResults = false;
                
                items.forEach(item => {
                    const nome = item.dataset.nome.toLowerCase();
                    
                    if (searchTerm === '' || nome.includes(searchTerm)) {
                        item.style.display = '';
                        groupHasResults = true;
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Mostra/nascondi il gruppo
                if (groupHasResults) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
            
            // Mostra messaggio se nessun risultato
            let noResultsMsg = listContainerModal.querySelector('.militari-no-results');
            
            if (!hasResults) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'militari-no-results';
                    noResultsMsg.innerHTML = '<i class="fas fa-users-slash"></i><br>Nessun militare trovato';
                    listContainerModal.appendChild(noResultsMsg);
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        });
    }
    
    // Inizializza l'interfaccia
    aggiornaInterfacciaModal();
    
    // Reset quando il modal si chiude
    $('#addMilitareModal').on('hidden.bs.modal', function() {
        militariSelezionatiModal.clear();
        // Riseleziona i militari già presenti
        listContainerModal.querySelectorAll('.militare-item[data-gia-presente="1"]').forEach(item => {
            militariSelezionatiModal.add(item.dataset.id);
        });
        searchInputModal.value = '';
        searchInputModal.dispatchEvent(new Event('input'));
        aggiornaInterfacciaModal();
    });
});

// Toast globali: gestiti da toast-system.js

// Verifica disponibilità per tutti i militari selezionati
document.getElementById('verificaBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifica in corso...';
    
    risultatiVerifica.clear();
    const listContainerModal = document.getElementById('militari-list-modal');
    
    // Verifica ogni militare
    for (const militareId of militariSelezionatiModal) {
        const item = listContainerModal.querySelector(`[data-id="${militareId}"]`);
        if (!item) continue;
        
        const nome = item.dataset.nome;
        const compagnia = item.closest('.militari-group').querySelector('.militari-group-header').textContent.trim();
        
        const conflitti = await verificaDisponibilitaMilitare(militareId);
        
        risultatiVerifica.set(militareId, {
            id: militareId,
            nome: nome,
            compagnia: compagnia,
            disponibile: conflitti.length === 0,
            conflitti: conflitti
        });
    }
    
    // Mostra risultati
    mostraRisultatiVerifica();
    
    // Passa allo step 2
    document.getElementById('step1').classList.add('d-none');
    document.getElementById('step2').classList.remove('d-none');
    document.getElementById('aggiungiSelezionatiBtn').classList.remove('d-none');
    
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-search me-1"></i>Verifica Disponibilità';
    
    aggiornaSelezione();
});

// Verifica disponibilità singolo militare
async function verificaDisponibilitaMilitare(militareId) {
    const conflitti = [];
    const start = new Date(activityStartDate);
    const end = new Date(activityEndDate);
    
    for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const dataStr = d.toISOString().split('T')[0];
        
        try {
            const response = await fetch('{{ route('servizi.turni.check-disponibilita') }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({
                    militare_id: militareId, 
                    data: dataStr,
                    exclude_activity_id: activityId // Escludi l'attività corrente dal check
                })
            });
            
            const result = await response.json();
            if (!result.disponibile) {
                conflitti.push({
                    data: new Date(dataStr).toLocaleDateString('it-IT'),
                    motivo: result.motivo
                });
            }
        } catch (error) {
            console.error('Errore verifica:', error);
        }
    }
    
    return conflitti;
}

// Mostra risultati verifica in tabella
function mostraRisultatiVerifica() {
    const tbody = document.getElementById('risultatiVerifica');
    tbody.innerHTML = '';
    
    risultatiVerifica.forEach((dati, militareId) => {
        const row = document.createElement('tr');
        row.className = dati.disponibile ? 'table-success' : 'table-warning';
        
        const statusBadge = dati.disponibile 
            ? '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Disponibile</span>'
            : '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Conflitti</span>';
        
        const dettagli = dati.disponibile 
            ? '<span class="text-muted">-</span>'
            : `<ul class="mb-0 small">${dati.conflitti.map(c => `<li>${c.data}: ${c.motivo}</li>`).join('')}</ul>`;
        
        row.innerHTML = `
            <td><input type="checkbox" class="form-check-input militare-check" data-militare-id="${militareId}" ${dati.disponibile ? 'checked' : ''}></td>
            <td><strong>${dati.nome}</strong></td>
            <td><span class="badge bg-light text-dark">${dati.compagnia}</span></td>
            <td>${statusBadge}</td>
            <td>${dettagli}</td>
        `;
        
        tbody.appendChild(row);
    });
    
    // Event listener per checkbox
    document.querySelectorAll('.militare-check').forEach(cb => {
        cb.addEventListener('change', aggiornaSelezione);
    });
    
    // Select all checkbox
    document.getElementById('selectAllCheck').addEventListener('change', function() {
        document.querySelectorAll('.militare-check').forEach(cb => {
            cb.checked = this.checked;
        });
        aggiornaSelezione();
    });
}

// Aggiorna contatore selezionati
function aggiornaSelezione() {
    const count = document.querySelectorAll('.militare-check:checked').length;
    document.getElementById('countSelezionati').textContent = count;
    document.getElementById('aggiungiSelezionatiBtn').disabled = count === 0;
}

// Torna allo step 1
function tornaStep1() {
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('aggiungiSelezionatiBtn').classList.add('d-none');
}

// Aggiungi militari selezionati
document.getElementById('aggiungiSelezionatiBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assegnazione in corso...';
    
    const checkboxes = document.querySelectorAll('.militare-check:checked');
    const militariDaAggiungere = Array.from(checkboxes).map(cb => cb.getAttribute('data-militare-id'));
    
    let aggiunti = 0;
    let errori = 0;
    
    for (const militareId of militariDaAggiungere) {
        const dati = risultatiVerifica.get(militareId);
        const force = !dati.disponibile; // Forza se ha conflitti
        
        try {
            const response = await fetch('{{ route('board.activities.attach.militare', $activity) }}', {
            method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({militare_id: militareId, force: force})
            });
            
            const result = await response.json();
            if (result.success) {
                aggiunti++;
            } else {
                errori++;
            }
        } catch (error) {
            errori++;
        }
    }
    
    // Mostra risultato solo in caso di errore
    if (errori > 0 && aggiunti === 0) {
        showToast('Errore', 'Nessun militare è stato aggiunto. Riprova.', 'error');
    } else if (aggiunti > 0 && errori > 0) {
        showToast('Attenzione', `${aggiunti} militare/i aggiunti, ${errori} non aggiunti`, 'warning');
    }
    
    // Chiudi modal e ricarica (senza delay se tutto ok)
    bootstrap.Modal.getInstance(document.getElementById('addMilitareModal')).hide();
    if (aggiunti > 0 && errori === 0) {
        location.reload();
    } else {
    setTimeout(() => location.reload(), 1000);
    }
});

// Variabili globali per il modal di conferma rimozione
let militareIdToRemove = null;
let militareNomeToRemove = '';

// Rimuovi militare - mostra modal di conferma
function removeMilitare(militareId, nome) {
    militareIdToRemove = militareId;
    militareNomeToRemove = nome;
    
    // Aggiorna il testo del modal
    document.getElementById('removeMilitareNome').textContent = nome;
    
    // Mostra il modal
    const modal = new bootstrap.Modal(document.getElementById('confirmRemoveModal'));
    modal.show();
}

// Conferma rimozione militare
async function confirmaRimuoviMilitare() {
    if (!militareIdToRemove) return;
    
    const militareId = militareIdToRemove;
    const nome = militareNomeToRemove;
    
    try {
        const response = await fetch(`{{ route('board.activities.detach.militare', ['activity' => $activity->id, 'militare' => '__ID__']) }}`.replace('__ID__', militareId), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
            body: JSON.stringify({_method: 'DELETE'})
        });
        
        const data = await response.json();
        
                if (data.success) {
            // Rimuovi elemento dalla lista
            document.querySelector(`[data-militare-id="${militareId}"]`).remove();
            
            const count = document.querySelectorAll('[data-militare-id]').length;
            document.querySelector('.card-header h6').innerHTML = `<i class="fas fa-users me-2"></i>Militari Coinvolti (${count})`;
            
            if (count === 0) {
                document.getElementById('militariList').parentElement.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-user-slash fa-2x mb-2"></i><p class="mb-0">Nessun militare assegnato</p></div>';
        }
} else {
            showToast('Errore', data.message, 'error');
        }
    } catch (error) {
        showToast('Errore', 'Errore di connessione', 'error');
    }
}

// Reset modal quando chiuso
document.getElementById('addMilitareModal').addEventListener('hidden.bs.modal', function() {
    $('#militareSelect').val(null).trigger('change');
    document.getElementById('verificaBtn').disabled = true;
    document.getElementById('step1').classList.remove('d-none');
    document.getElementById('step2').classList.add('d-none');
    document.getElementById('aggiungiSelezionatiBtn').classList.add('d-none');
    militariSelezionati = [];
    risultatiVerifica.clear();
});
</script>

<!-- Floating Button Export Excel -->
<a href="{{ route('board.activities.export', $activity) }}" class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

@endpush

@push('styles')
<style>
    .card { border: none; border-radius: 0.5rem; }
    .card:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1) !important; }
    .list-group-item { border-left: none; border-right: none; transition: background 0.2s; }
    .list-group-item:first-child { border-top: none; }
    .list-group-item:last-child { border-bottom: none; }
    .list-group-item:hover { background-color: #f8f9fa; }
    
    /* Toast migliorato - più visibile */
    #toast {
        min-width: 350px;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.3) !important;
        border-left: 4px solid;
    }
    #toast.toast-success { border-left-color: #28a745; background-color: #d4edda; }
    #toast.toast-error { border-left-color: #dc3545; background-color: #f8d7da; }
    #toast.toast-warning { border-left-color: #ffc107; background-color: #fff3cd; }
    #toast.toast-info { border-left-color: #17a2b8; background-color: #d1ecf1; }
    
    #toast .toast-header {
        background-color: transparent;
        border-bottom: none;
        font-weight: 600;
    }
    #toast.toast-success .toast-header { color: #155724; }
    #toast.toast-error .toast-header { color: #721c24; }
    #toast.toast-warning .toast-header { color: #856404; }
    #toast.toast-info .toast-header { color: #0c5460; }
    
    #toast .toast-body {
        color: inherit;
        font-size: 0.95rem;
    }
    
    /* ========================================
       SELECT2 MINIMAL SUGECO - Stile pulito e professionale
       ======================================== */
    
    /* Contenitore principale - Layout a due sezioni */
    .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 180px !important;
        padding: 0 !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 8px !important;
        background: white !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        transition: all 0.2s ease !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple:hover {
        border-color: #0b5ed7 !important;
        background: white !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple:focus-within {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 0.35rem rgba(13, 110, 253, 0.25), inset 0 1px 3px rgba(0,0,0,0.05) !important;
        background: white !important;
    }
    
    /* Contenitore interno con flexbox per layout ottimale */
    .select2-container--bootstrap-5 .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        padding: 0 !important;
        align-items: flex-start !important;
    }
    
    /* Barra di ricerca inline - SEMPRE VISIBILE E GRANDE */
    .select2-container--bootstrap-5 .select2-search--inline {
        flex: 1 1 auto !important;
        min-width: 300px !important;
        order: 999 !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field {
        min-width: 300px !important;
        width: 100% !important;
        height: 42px !important;
        padding: 10px 15px !important;
        font-size: 1rem !important;
        margin: 0 !important;
        border: 2px dashed #dee2e6 !important;
        border-radius: 0.375rem !important;
        background-color: #f8f9fa !important;
        transition: all 0.2s ease !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field:focus {
        outline: none !important;
        border-color: #0d6efd !important;
        border-style: solid !important;
        background-color: white !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field::placeholder {
        color: #6c757d !important;
        font-style: italic !important;
    }
    
    /* Badge militari selezionati - Stile professionale */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
        border: none !important;
        color: white !important;
        padding: 8px 14px !important;
        margin: 0 !important;
        border-radius: 6px !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3) !important;
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        transition: all 0.2s ease !important;
        max-width: 280px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.4) !important;
    }
    
    /* Bottone rimozione militare */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
        color: white !important;
        margin-right: 0 !important;
        font-weight: bold !important;
        font-size: 1.1rem !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 20px !important;
        height: 20px !important;
        border-radius: 50% !important;
        background: rgba(255, 255, 255, 0.2) !important;
        transition: all 0.2s ease !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
        background: rgba(255, 255, 255, 0.3) !important;
        color: #ffcccc !important;
        transform: rotate(90deg) !important;
    }
    
    /* Dropdown migliorato */
    .select2-container--bootstrap-5 .select2-dropdown {
        border: 2px solid #0d6efd !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.2) !important;
        background: white !important;
        margin-top: 4px !important;
    }
    
    /* Opzioni nel dropdown */
    .select2-container--bootstrap-5 .select2-results__option {
        padding: 10px 15px !important;
        transition: all 0.15s ease !important;
    }
    
    .select2-container--bootstrap-5 .select2-results__option--highlighted {
        background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
        color: white !important;
    }
    
    /* Gruppi nel dropdown */
    .select2-container--bootstrap-5 .select2-results__group {
        font-weight: 700 !important;
        color: #0d6efd !important;
        padding: 12px 15px 8px !important;
        font-size: 0.85rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
        background: #f8f9fa !important;
        border-bottom: 2px solid #0d6efd !important;
    }
    
    /* Scrollbar personalizzata per il contenitore */
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar {
        width: 8px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-track {
        background: #f1f1f1 !important;
        border-radius: 4px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-thumb {
        background: #0d6efd !important;
        border-radius: 4px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-thumb:hover {
        background: #0b5ed7 !important;
    }
    
    /* Stile risultati militari nel dropdown */
    .select2-result-militare {
        display: flex !important;
        align-items: center !important;
        padding: 4px 0 !important;
    }
    
    .select2-result-militare .militare-name {
        font-weight: 500 !important;
    }
    
    /* Messaggio "Nessun risultato" */
    .select2-container--bootstrap-5 .select2-results__message {
        padding: 15px !important;
        text-align: center !important;
        color: #6c757d !important;
        font-style: italic !important;
    }
    
    /* Indicatore di ricerca */
    .select2-container--bootstrap-5 .select2-search--dropdown {
        padding: 10px !important;
        background: #f8f9fa !important;
        border-bottom: 2px solid #dee2e6 !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
        border: 2px solid #dee2e6 !important;
        padding: 8px 12px !important;
        font-size: 1rem !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important;
    }
    
    /* Modal centrato */
    .modal-dialog-centered {
        display: flex;
        align-items: center;
        min-height: calc(100% - 1rem);
    }
</style>
@endpush
