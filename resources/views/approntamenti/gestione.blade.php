@extends('layouts.app')

@section('title', 'Gestione Approntamenti - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Approntamenti</h1>
    </div>

    <!-- Pulsante Aggiungi Colonna -->
    <div class="d-flex justify-content-center mb-4">
        <button type="button" class="btn btn-success" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i>Aggiungi Colonna
        </button>
    </div>

    <!-- Info Card -->
    <div class="alert alert-info mb-4" style="max-width: 900px; margin: 0 auto;">
        Trascina le righe per riordinare le colonne. Le colonne condivise con il sistema SPP non possono essere eliminate.
    </div>

    <!-- Tabella Colonne -->
    <div class="table-container-ruolini" style="max-width: 900px; margin: 0 auto;">
        <table class="sugeco-table" id="colonneTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Nome Colonna</th>
                    <th style="width: 150px;">Scadenza</th>
                    <th style="width: 120px;">Azioni</th>
                </tr>
            </thead>
            <tbody id="colonneTableBody">
                @foreach($colonne as $colonna)
                <tr data-id="{{ $colonna->id }}" class="{{ !$colonna->attivo ? 'table-secondary' : '' }}">
                    <td class="text-center drag-handle" style="cursor: move;">
                        <i class="fas fa-grip-vertical text-muted"></i>
                    </td>
                    <td>
                        <strong>{{ $colonna->label }}</strong>
                        @if($colonna->fonte === 'scadenze_militari')
                            <i class="fas fa-link text-warning ms-2" title="Colonna condivisa con SPP"></i>
                        @endif
                        @if(!$colonna->attivo)
                            <span class="badge bg-secondary ms-2">Disattivata</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($colonna->scadenza_mesi)
                            <span class="badge bg-primary">
                                {{ $colonna->scadenza_formattata }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="openEditModal({{ $colonna->id }}, '{{ addslashes($colonna->label) }}', {{ $colonna->scadenza_mesi ?? 'null' }})" title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-{{ $colonna->attivo ? 'warning' : 'success' }}" onclick="toggleColonna({{ $colonna->id }})" title="{{ $colonna->attivo ? 'Disattiva' : 'Attiva' }}">
                                <i class="fas fa-{{ $colonna->attivo ? 'eye-slash' : 'eye' }}"></i>
                            </button>
                            @if($colonna->fonte !== 'scadenze_militari')
                            <button type="button" class="btn btn-outline-danger" onclick="deleteColonna({{ $colonna->id }}, '{{ addslashes($colonna->label) }}')" title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

<!-- Modal Aggiungi/Modifica Colonna -->
<div class="modal fade" id="colonnaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white;">
                <h5 class="modal-title" id="colonnaModalTitle">
                    <i class="fas fa-columns me-2"></i>Nuova Colonna
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="colonnaForm">
                <div class="modal-body">
                    <input type="hidden" id="colonnaId" value="">
                    
                    <div class="mb-3">
                        <label for="colonnaLabel" class="form-label fw-semibold">Nome Colonna <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="colonnaLabel" name="label" required maxlength="100" placeholder="Es: Corso ABC">
                        <div class="form-text">Il nome che apparirà nell'intestazione della tabella approntamenti</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="colonnaScadenza" class="form-label fw-semibold">Scadenza (mesi)</label>
                        <input type="number" class="form-control" id="colonnaScadenza" name="scadenza_mesi" min="1" max="120" placeholder="Es: 24">
                        <div class="form-text">Lascia vuoto se la colonna non ha una scadenza automatica</div>
                    </div>
                    
                    <div class="alert alert-secondary mb-0">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Suggerimento:</strong> Inserisci 12 per 1 anno, 24 per 2 anni, 60 per 5 anni.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success" id="btnSaveColonna">
                        <i class="fas fa-check me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal conferma eliminazione gestito da SUGECO.Confirm -->

@endsection

@push('scripts')
<!-- SortableJS locale (funziona offline) -->
<script src="{{ asset('vendor/js/sortable.min.js') }}"></script>
<script>
const ROUTES = {
    store: '{{ route("gestione-approntamenti.colonne.store") }}',
    update: '{{ route("gestione-approntamenti.colonne.update", ["id" => "__ID__"]) }}',
    destroy: '{{ route("gestione-approntamenti.colonne.destroy", ["id" => "__ID__"]) }}',
    toggle: '{{ route("gestione-approntamenti.colonne.toggle", ["id" => "__ID__"]) }}',
    ordine: '{{ route("gestione-approntamenti.colonne.ordine") }}'
};
const CSRF_TOKEN = '{{ csrf_token() }}';

let colonnaModal;

document.addEventListener('DOMContentLoaded', function() {
    colonnaModal = new bootstrap.Modal(document.getElementById('colonnaModal'));
    
    // Inizializza Sortable per drag & drop
    const tbody = document.getElementById('colonneTableBody');
    new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function(evt) {
            saveOrdine();
        }
    });
    
    // Form submit
    document.getElementById('colonnaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveColonna();
    });
});

function openAddModal() {
    document.getElementById('colonnaId').value = '';
    document.getElementById('colonnaLabel').value = '';
    document.getElementById('colonnaScadenza').value = '';
    document.getElementById('colonnaModalTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nuova Colonna';
    colonnaModal.show();
}

function openEditModal(id, label, scadenza) {
    document.getElementById('colonnaId').value = id;
    document.getElementById('colonnaLabel').value = label;
    document.getElementById('colonnaScadenza').value = scadenza || '';
    document.getElementById('colonnaModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifica Colonna';
    colonnaModal.show();
}

async function saveColonna() {
    const id = document.getElementById('colonnaId').value;
    const label = document.getElementById('colonnaLabel').value.trim();
    const scadenza = document.getElementById('colonnaScadenza').value;
    
    if (!label) {
        showToast('warning', 'Inserisci il nome della colonna');
        return;
    }
    
    const btn = document.getElementById('btnSaveColonna');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
    
    try {
        const url = id 
            ? ROUTES.update.replace('__ID__', id)
            : ROUTES.store;
        
        const response = await fetch(url, {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                label: label,
                scadenza_mesi: scadenza ? parseInt(scadenza) : null
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message);
            colonnaModal.hide();
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', data.message || 'Errore durante il salvataggio');
        }
    } catch (error) {
        showToast('error', 'Errore di comunicazione con il server');
        console.error(error);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Salva';
    }
}

async function deleteColonna(id, label) {
    // Usa il sistema di conferma unificato
    const confirmed = await SUGECO.Confirm.show({
        title: 'Conferma Eliminazione',
        message: `Eliminare la colonna "${label}"? I dati esistenti non verranno eliminati, ma la colonna non sarà più visibile.`,
        type: 'danger',
        confirmText: 'Elimina'
    });
    
    if (!confirmed) return;
    
    try {
        const response = await fetch(ROUTES.destroy.replace('__ID__', id), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', data.message || 'Errore durante l\'eliminazione');
        }
    } catch (error) {
        showToast('error', 'Errore di comunicazione con il server');
        console.error(error);
    }
}

async function toggleColonna(id) {
    try {
        const response = await fetch(ROUTES.toggle.replace('__ID__', id), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', data.message);
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('error', data.message || 'Errore');
        }
    } catch (error) {
        showToast('error', 'Errore di comunicazione con il server');
        console.error(error);
    }
}

async function saveOrdine() {
    const rows = document.querySelectorAll('#colonneTableBody tr[data-id]');
    const ordine = Array.from(rows).map(row => parseInt(row.dataset.id));
    
    try {
        const response = await fetch(ROUTES.ordine, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ ordine: ordine })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('success', 'Ordine salvato');
        }
    } catch (error) {
        showToast('error', 'Errore nel salvataggio dell\'ordine');
        console.error(error);
    }
}

function showToast(type, message) {
    if (window.SUGECO?.Toast) {
        window.SUGECO.Toast.show(type, message);
    } else {
        alert(message);
    }
}
</script>
@endpush
