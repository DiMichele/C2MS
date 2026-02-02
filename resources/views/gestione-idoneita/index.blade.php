@extends('layouts.app')

@section('title', 'Gestione Idoneità - SUGECO')

@section('content')
<style>
/* Pulsante clear search */
.btn-clear-search {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #6c757d;
    cursor: pointer;
    padding: 4px 8px;
    z-index: 5;
}
.btn-clear-search:hover {
    color: #dc3545;
}
</style>
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Idoneità</h1>
    </div>

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 500px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
            <input 
                type="text" 
                id="searchIdoneita" 
                class="form-control" 
                placeholder="Cerca tipo di idoneità..." 
                aria-label="Cerca tipo di idoneità" 
                autocomplete="off"
                style="padding-left: 40px; border-radius: 6px !important;">
            <button type="button" id="clearSearch" class="btn-clear-search" style="display: none;" title="Pulisci ricerca">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Pulsante Rimuovi filtri (appare quando ricerca attiva) -->
    <div id="resetFiltersContainer" class="d-flex justify-content-center mb-3" style="display: none !important;">
        <button type="button" id="resetAllFilters" class="btn btn-danger btn-sm" style="border-radius: 6px !important;">
            <i class="fas fa-times-circle me-1"></i>Rimuovi filtri
        </button>
    </div>

    <!-- Tabella Tipi Idoneità -->
    <div class="table-container-ruolini" style="max-width: 900px; margin: 0 auto;">
        <table class="sugeco-table" id="idoneitaTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Nome Tipo Idoneità</th>
                    <th style="width: 150px;">Durata (mesi)</th>
                    <th style="width: 120px;">Azioni</th>
                </tr>
            </thead>
            <tbody id="idoneitaTableBody">
                @forelse($idoneita as $tipo)
                <tr data-idoneita-id="{{ $tipo->id }}" 
                    data-id="{{ $tipo->id }}"
                    data-nome="{{ $tipo->nome }}">
                    <td class="text-center drag-handle" style="cursor: move;">
                        <i class="fas fa-grip-vertical text-muted"></i>
                    </td>
                    <td><strong>{{ $tipo->nome }}</strong></td>
                    <td>
                        <input type="number" 
                               class="form-control durata-input" 
                               data-idoneita-id="{{ $tipo->id }}"
                               value="{{ $tipo->durata_mesi }}" 
                               min="0"
                               placeholder="0 = Nessuna scadenza"
                               style="width: 120px; margin: 0 auto; text-align: center;">
                        @if($tipo->durata_mesi == 0)
                        <small class="text-muted d-block text-center">Nessuna scadenza</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary edit-idoneita-btn" 
                                data-idoneita-id="{{ $tipo->id }}"
                                data-nome="{{ $tipo->nome }}"
                                data-durata="{{ $tipo->durata_mesi }}"
                                title="Modifica">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-idoneita-btn" 
                                data-idoneita-id="{{ $tipo->id }}"
                                data-nome="{{ $tipo->nome }}"
                                title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <p class="mb-0">Nessun tipo di idoneità configurato</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createIdoneitaModal" data-tooltip="Nuovo Tipo Idoneità" aria-label="Nuovo Tipo Idoneità">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal Creazione Tipo Idoneità -->
<div class="modal fade" id="createIdoneitaModal" tabindex="-1" aria-labelledby="createIdoneitaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="createIdoneitaModalLabel">Nuovo Tipo Idoneità</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createIdoneitaForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">
                            Nome Tipo Idoneità <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome" 
                               name="nome" 
                               placeholder="es. Visita Medica Annuale, Idoneità Guida, ecc." 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="durata_mesi" class="form-label">
                            Durata Validità (mesi) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="durata_mesi" 
                               name="durata_mesi" 
                               value="12" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Crea</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Tipo Idoneità -->
<div class="modal fade" id="editIdoneitaModal" tabindex="-1" aria-labelledby="editIdoneitaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="editIdoneitaModalLabel">Modifica Tipo Idoneità</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editIdoneitaForm">
                @csrf
                <input type="hidden" id="edit_idoneita_id" name="idoneita_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">
                            Nome Tipo Idoneità <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_nome" 
                               name="nome" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_durata_mesi" class="form-label">
                            Durata Validità (mesi) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="edit_durata_mesi" 
                               name="durata_mesi" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salva Modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal conferma eliminazione gestito da SUGECO.Confirm -->

<script>
(function() {
    'use strict';
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (!csrfMeta) {
            console.error('CSRF token meta tag not found!');
            return;
        }
        const csrfToken = csrfMeta.getAttribute('content');
        
        const searchInput = document.getElementById('searchIdoneita');
        const tbody = document.getElementById('idoneitaTableBody');

        // Salvataggio durata
        document.querySelectorAll('.durata-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const idoneitaId = this.dataset.idoneitaId;
                const durataMesi = parseInt(this.value);
                const inputElement = this;
                
                if (durataMesi < 0) {
                    alert('La durata non può essere negativa');
                    return;
                }
                
                const updateUrl = '{{ url("gestione-idoneita") }}' + '/' + idoneitaId;
                
                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        durata_mesi: durataMesi
                    })
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        window.SUGECO.showSaveFeedback(inputElement, true, 2000);
                    } else {
                        window.SUGECO.showSaveFeedback(inputElement, false, 2000);
                    }
                })
                .catch(function(error) {
                    console.error('Errore salvataggio:', error);
                    window.SUGECO.showSaveFeedback(inputElement, false, 2000);
                });
            });
        });

        // Gestione form creazione tipo idoneità
        const createForm = document.getElementById('createIdoneitaForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    nome: formData.get('nome'),
                    descrizione: formData.get('descrizione'),
                    durata_mesi: parseInt(formData.get('durata_mesi'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
                
                fetch('{{ route("gestione-idoneita.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(err) {
                            throw new Error(err.message || 'Errore durante la creazione');
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        // Chiudi modal e ricarica pagina
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createIdoneitaModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea Tipo Idoneità';
                });
            });
        }
        
        // Gestione pulsanti modifica
        document.querySelectorAll('.edit-idoneita-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const idoneitaId = this.dataset.idoneitaId;
                const nome = this.dataset.nome;
                const descrizione = this.dataset.descrizione || '';
                const durata = this.dataset.durata;
                
                document.getElementById('edit_idoneita_id').value = idoneitaId;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_descrizione').value = descrizione;
                document.getElementById('edit_durata_mesi').value = durata;
                
                const modal = new bootstrap.Modal(document.getElementById('editIdoneitaModal'));
                modal.show();
            });
        });
        
        // Gestione form modifica tipo idoneità
        const editForm = document.getElementById('editIdoneitaForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const idoneitaId = document.getElementById('edit_idoneita_id').value;
                const formData = new FormData(this);
                const data = {
                    nome: formData.get('nome'),
                    descrizione: formData.get('descrizione'),
                    durata_mesi: parseInt(formData.get('durata_mesi'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
                
                fetch('{{ url("gestione-idoneita") }}' + '/' + idoneitaId + '/edit', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(err) {
                            throw new Error(err.message || 'Errore durante il salvataggio');
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editIdoneitaModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva Modifiche';
                });
            });
        }
        
        // Gestione pulsanti eliminazione
        document.querySelectorAll('.delete-idoneita-btn').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const idoneitaId = this.dataset.idoneitaId;
                const nome = this.dataset.nome;
                
                // Usa il sistema di conferma unificato
                const confirmed = await SUGECO.Confirm.show({
                    title: 'Conferma Eliminazione',
                    message: `Eliminare il tipo di idoneità "${nome}"? Se ci sono scadenze associate, verrà disattivato.`,
                    type: 'danger',
                    confirmText: 'Elimina'
                });
                
                if (!confirmed) return;
                
                // Esegui eliminazione
                fetch('{{ url("gestione-idoneita") }}' + '/' + idoneitaId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(function(response) {
                    if (!response.ok) {
                        return response.json().then(function(err) {
                            throw new Error(err.message || 'Errore durante l\'eliminazione');
                        });
                    }
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(function(error) {
                    showError('Errore: ' + error.message);
                });
            });
        });

        // Ricerca
        const clearSearchBtn = document.getElementById('clearSearch');
        const resetFiltersContainer = document.getElementById('resetFiltersContainer');
        const resetAllFiltersBtn = document.getElementById('resetAllFilters');
        
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            searchInput.addEventListener('keyup', filterTable);
        }
        
        // Clear search button
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterTable();
                searchInput.focus();
            });
        }
        
        // Reset all filters
        if (resetAllFiltersBtn) {
            resetAllFiltersBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                filterTable();
            });
        }

        function filterTable() {
            if (!tbody) return;
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const rows = tbody.querySelectorAll('tr[data-idoneita-id]');
            let visibili = 0;

            rows.forEach(function(row) {
                const nome = (row.dataset.nome || '').toLowerCase();

                const matchRicerca = !searchTerm || nome.includes(searchTerm);

                if (matchRicerca) {
                    row.style.display = '';
                    visibili++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Aggiorna visibilità pulsante clear search
            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm ? 'block' : 'none';
            }
            
            // Aggiorna visibilità pulsante reset filtri
            if (resetFiltersContainer) {
                resetFiltersContainer.style.display = searchTerm ? 'flex' : 'none';
            }
        }

        // SortableJS per drag & drop riordino
        if (tbody && typeof Sortable !== 'undefined') {
            new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    const rows = tbody.querySelectorAll('tr[data-id]');
                    const ordini = [];
                    rows.forEach(function(row) {
                        ordini.push(row.dataset.id);
                    });
                    
                    // Salva il nuovo ordine
                    fetch('{{ route("gestione-idoneita.update-order") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ordini: ordini })
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            showSuccess('Ordine aggiornato!');
                        }
                    })
                    .catch(function(error) {
                        console.error('Errore salvataggio ordine:', error);
                    });
                }
            });
        }
    }
})();
</script>

{{-- 
.table-container-ruolini {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin: 0;
}

.durata-input {
    border: 2px solid #dee2e6;
    border-radius: 6px !important;
    font-weight: 600;
    transition: all 0.2s;
}

.durata-input:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.25);
}
--}}
@endsection

