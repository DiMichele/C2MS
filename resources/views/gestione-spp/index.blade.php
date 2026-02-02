@extends('layouts.app')

@section('title', 'Gestione SPP - SUGECO')

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

/* Filter label style */
.filter-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
    margin-bottom: 4px;
}
</style>
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione SPP</h1>
    </div>

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 500px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
            <input 
                type="text" 
                id="searchCorso" 
                class="form-control" 
                placeholder="Cerca corso..." 
                aria-label="Cerca corso" 
                autocomplete="off"
                style="padding-left: 40px; border-radius: 6px !important;">
            <button type="button" id="clearSearch" class="btn-clear-search" style="display: none;" title="Pulisci ricerca">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Filtri inline (stile CPT) -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            <!-- Filtro Tipo Corso -->
            <div>
                <label for="tipoFilter" class="filter-label d-block">Tipo Corso</label>
                <select id="tipoFilter" class="form-select form-select-sm" style="min-width: 200px; border-radius: 6px !important;">
                    <option value="">Tutti i tipi</option>
                    <option value="formazione">Corsi di Formazione</option>
                    <option value="accordo_stato_regione">Corsi Accordo Stato Regione</option>
                </select>
            </div>
            
            <!-- Pulsante Rimuovi filtri (appare quando filtri attivi) -->
            <div id="resetFiltersContainer" style="display: none; align-self: flex-end;">
                <button type="button" id="resetAllFilters" class="btn btn-danger btn-sm" style="border-radius: 6px !important;">
                    <i class="fas fa-times-circle me-1"></i>Rimuovi filtri
                </button>
            </div>
        </div>
        
        <div>
            {{-- Spazio per eventuali pulsanti azione --}}
        </div>
    </div>

    <!-- Tabella Corsi -->
    <div class="table-container-ruolini" style="max-width: 1200px; margin: 0 auto;">
        <table class="sugeco-table" id="corsiTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Nome Corso</th>
                    <th>Tipo</th>
                    <th>Durata (anni)</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="corsiTableBody">
                @forelse($corsi as $corso)
                <tr data-corso-id="{{ $corso->id }}" 
                    data-id="{{ $corso->id }}"
                    data-nome="{{ $corso->nome_corso }}"
                    data-tipo="{{ $corso->tipo }}">
                    <td class="text-center drag-handle" style="cursor: move;">
                        <i class="fas fa-grip-vertical text-muted"></i>
                    </td>
                    <td><strong>{{ $corso->nome_corso }}</strong></td>
                    <td>
                        @if($corso->tipo === 'formazione')
                            Formazione
                        @else
                            Accordo Stato Regione
                        @endif
                    </td>
                    <td>
                        <input type="number" 
                               class="form-control durata-input" 
                               data-corso-id="{{ $corso->id }}"
                               value="{{ $corso->durata_anni }}" 
                               min="0"
                               placeholder="0 = Nessuna scadenza"
                               style="width: 120px; margin: 0 auto; text-align: center;">
                        @if($corso->durata_anni == 0)
                        <small class="text-muted d-block">Nessuna scadenza</small>
                        @endif
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-corso-btn" 
                                data-corso-id="{{ $corso->id }}"
                                data-nome="{{ $corso->nome_corso }}"
                                data-tipo="{{ $corso->tipo }}"
                                data-durata="{{ $corso->durata_anni }}"
                                title="Modifica corso">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-corso-btn" 
                                data-corso-id="{{ $corso->id }}"
                                data-nome="{{ $corso->nome_corso }}"
                                title="Elimina corso">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <p class="mb-0">Nessun corso configurato</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createCorsoModal" data-tooltip="Nuovo Corso" aria-label="Nuovo Corso">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal Creazione Corso -->
<div class="modal fade" id="createCorsoModal" tabindex="-1" aria-labelledby="createCorsoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="createCorsoModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nuovo Corso SPP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createCorsoForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome_corso" class="form-label">
                            Nome Corso <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome_corso" 
                               name="nome_corso" 
                               placeholder="es. Carrellista, RLS, ecc." 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo" class="form-label">
                            Tipo Corso <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Seleziona tipo...</option>
                            <option value="formazione">Corsi di Formazione</option>
                            <option value="accordo_stato_regione">Corsi Accordo Stato Regione</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="durata_anni" class="form-label">
                            Durata Validità (anni) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="durata_anni" 
                               name="durata_anni" 
                               value="5" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Crea Corso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Corso -->
<div class="modal fade" id="editCorsoModal" tabindex="-1" aria-labelledby="editCorsoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="editCorsoModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifica Corso SPP
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCorsoForm">
                @csrf
                <input type="hidden" id="edit_corso_id" name="corso_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nome_corso" class="form-label">
                            Nome Corso <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_nome_corso" 
                               name="nome_corso" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tipo" class="form-label">
                            Tipo Corso <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_tipo" name="tipo" required>
                            <option value="formazione">Corsi di Formazione</option>
                            <option value="accordo_stato_regione">Corsi Accordo Stato Regione</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_durata_anni" class="form-label">
                            Durata Validità (anni) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="edit_durata_anni" 
                               name="durata_anni" 
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
        
        const searchInput = document.getElementById('searchCorso');
        const tbody = document.getElementById('corsiTableBody');

        // Salvataggio durata
        document.querySelectorAll('.durata-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const corsoId = this.dataset.corsoId;
                const durataAnni = parseInt(this.value);
                const inputElement = this;
                
                if (durataAnni < 0) {
                    alert('La durata non può essere negativa');
                    return;
                }
                
                const updateUrl = '{{ url("gestione-spp") }}' + '/' + corsoId;
                
                fetch(updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        durata_anni: durataAnni
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

        // Gestione form creazione corso
        const createForm = document.getElementById('createCorsoForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    nome_corso: formData.get('nome_corso'),
                    tipo: formData.get('tipo'),
                    durata_anni: parseInt(formData.get('durata_anni'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
                
                fetch('{{ route("gestione-spp.store") }}', {
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createCorsoModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea Corso';
                });
            });
        }
        
        // Gestione pulsanti modifica
        document.querySelectorAll('.edit-corso-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const corsoId = this.dataset.corsoId;
                const nome = this.dataset.nome;
                const tipo = this.dataset.tipo;
                const durata = this.dataset.durata;
                
                document.getElementById('edit_corso_id').value = corsoId;
                document.getElementById('edit_nome_corso').value = nome;
                document.getElementById('edit_tipo').value = tipo;
                document.getElementById('edit_durata_anni').value = durata;
                
                const modal = new bootstrap.Modal(document.getElementById('editCorsoModal'));
                modal.show();
            });
        });
        
        // Gestione form modifica corso
        const editForm = document.getElementById('editCorsoForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const corsoId = document.getElementById('edit_corso_id').value;
                const formData = new FormData(this);
                const data = {
                    nome_corso: formData.get('nome_corso'),
                    tipo: formData.get('tipo'),
                    durata_anni: parseInt(formData.get('durata_anni'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
                
                fetch('{{ url("gestione-spp") }}' + '/' + corsoId + '/edit', {
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editCorsoModal'));
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
        document.querySelectorAll('.delete-corso-btn').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const corsoId = this.dataset.corsoId;
                const nome = this.dataset.nome;
                
                // Usa il sistema di conferma unificato
                const confirmed = await SUGECO.Confirm.show({
                    title: 'Conferma Eliminazione',
                    message: `Eliminare il corso "${nome}"? Verranno eliminate anche tutte le scadenze associate.`,
                    type: 'danger',
                    confirmText: 'Elimina'
                });
                
                if (!confirmed) return;
                
                // Esegui eliminazione
                fetch('{{ url("gestione-spp") }}' + '/' + corsoId, {
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

        // Ricerca e filtri
        const clearSearchBtn = document.getElementById('clearSearch');
        const resetFiltersContainer = document.getElementById('resetFiltersContainer');
        const resetAllFiltersBtn = document.getElementById('resetAllFilters');
        const tipoFilter = document.getElementById('tipoFilter');
        
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            searchInput.addEventListener('keyup', filterTable);
        }
        
        if (tipoFilter) {
            tipoFilter.addEventListener('change', filterTable);
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
                if (tipoFilter) tipoFilter.value = '';
                filterTable();
            });
        }

        function filterTable() {
            if (!tbody) return;
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const tipoValue = tipoFilter ? tipoFilter.value : '';
            const rows = tbody.querySelectorAll('tr[data-corso-id]');
            let visibili = 0;

            rows.forEach(function(row) {
                const nome = (row.dataset.nome || '').toLowerCase();
                const tipo = row.dataset.tipo || '';

                const matchRicerca = !searchTerm || nome.includes(searchTerm);
                const matchTipo = !tipoValue || tipo === tipoValue;

                if (matchRicerca && matchTipo) {
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
            
            // Aggiorna visibilità e contenuto pulsante reset filtri
            const hasFilters = searchTerm || tipoValue;
            if (resetFiltersContainer) {
                resetFiltersContainer.style.display = hasFilters ? 'block' : 'none';
            }
            
            // Evidenzia select quando ha un valore
            if (tipoFilter) {
                tipoFilter.style.borderColor = tipoValue ? '#198754' : '';
                tipoFilter.style.boxShadow = tipoValue ? '0 0 0 2px rgba(25, 135, 84, 0.25)' : '';
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
                    fetch('{{ route("gestione-spp.update-order") }}', {
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

.badge-cpt {
    display: inline-block;
    padding: 6px 12px;
    font-weight: 700;
    border-radius: 4px;
    font-size: 0.9rem;
    background-color: #0a2342;
    color: white;
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

.form-check-input {
    width: 3rem;
    height: 1.5rem;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}
--}}
@endsection

