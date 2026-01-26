@extends('layouts.app')

@section('title', 'Gestione SPP - SUGECO')

@section('content')
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
        </div>
    </div>

    <!-- Filtri -->
    <div class="d-flex justify-content-start align-items-center mb-3">
        <button id="toggleFilters" class="btn btn-primary {{ request()->filled('tipo') ? 'active' : '' }}" style="border-radius: 6px !important;">
            <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
            <span id="toggleFiltersText">
                {{ request()->filled('tipo') ? 'Nascondi filtri' : 'Mostra filtri' }}
            </span>
        </button>
    </div>

    <!-- Sezione Filtri -->
    <div id="filtersContainer" class="filter-section {{ request()->filled('tipo') ? 'visible' : '' }}" style="max-width: 600px; margin: 0 auto 1.5rem auto;">
        <div class="filter-card mb-3">
            <div class="filter-card-header d-flex justify-content-between align-items-center" style="padding: 0.75rem 1rem;">
                <div style="font-size: 0.95rem;">
                    <i class="fas fa-filter me-2"></i> Filtri
                </div>
            </div>
            <div class="card-body p-2">
                <form id="filtroForm" action="{{ route('gestione-spp.index') }}" method="GET">
                    <div class="row justify-content-center">
                        <!-- Filtro Tipo -->
                        <div class="col-12">
                            <label for="tipo" class="form-label" style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                                <i class="fas fa-tag me-1"></i> Tipo Corso
                            </label>
                            <div class="select-wrapper">
                                <select name="tipo" id="tipoFilter" class="form-select form-select-sm filter-select {{ request('tipo') ? 'applied' : '' }}">
                                    <option value="">Tutti i tipi</option>
                                    <option value="formazione" {{ request('tipo') == 'formazione' ? 'selected' : '' }}>Corsi di Formazione</option>
                                    <option value="accordo_stato_regione" {{ request('tipo') == 'accordo_stato_regione' ? 'selected' : '' }}>Corsi Accordo Stato Regione</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    @if(request()->filled('tipo'))
                    <div class="d-flex justify-content-center mt-2">
                        <a href="{{ route('gestione-spp.index') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-times-circle me-1"></i> Rimuovi filtro
                        </a>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <!-- Tabella Corsi -->
    <div class="table-container-ruolini" style="max-width: 1200px; margin: 0 auto;">
        <table class="sugeco-table" id="corsiTable">
            <thead>
                <tr>
                    <th>Nome Corso</th>
                    <th>Tipo</th>
                    <th>Durata (anni)</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody id="corsiTableBody">
                @forelse($corsi as $corso)
                <tr data-corso-id="{{ $corso->id }}" 
                    data-nome="{{ $corso->nome_corso }}"
                    data-tipo="{{ $corso->tipo }}">
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
                    <td colspan="4" class="text-center text-muted py-5">
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
<button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createCorsoModal" title="Nuovo Corso">
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
                            <i class="fas fa-graduation-cap me-1"></i>Nome Corso <span class="text-danger">*</span>
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
                            <i class="fas fa-tag me-1"></i>Tipo Corso <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Seleziona tipo...</option>
                            <option value="formazione">Corsi di Formazione</option>
                            <option value="accordo_stato_regione">Corsi Accordo Stato Regione</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="durata_anni" class="form-label">
                            <i class="fas fa-clock me-1"></i>Durata Validità (anni) <span class="text-danger">*</span>
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
                            <i class="fas fa-graduation-cap me-1"></i>Nome Corso <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_nome_corso" 
                               name="nome_corso" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tipo" class="form-label">
                            <i class="fas fa-tag me-1"></i>Tipo Corso <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_tipo" name="tipo" required>
                            <option value="formazione">Corsi di Formazione</option>
                            <option value="accordo_stato_regione">Corsi Accordo Stato Regione</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_durata_anni" class="form-label">
                            <i class="fas fa-clock me-1"></i>Durata Validità (anni) <span class="text-danger">*</span>
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

<!-- Modal Conferma Eliminazione -->
<div class="modal fade" id="deleteCorsoModal" tabindex="-1" aria-labelledby="deleteCorsoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteCorsoModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Sei sicuro di voler eliminare il corso <strong id="deleteCorsoNome"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Attenzione:</strong> Questa azione eliminerà anche tutte le scadenze associate a questo corso per tutti i militari.
                </div>
                <input type="hidden" id="delete_corso_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annulla
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>

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
            btn.addEventListener('click', function() {
                const corsoId = this.dataset.corsoId;
                const nome = this.dataset.nome;
                
                document.getElementById('delete_corso_id').value = corsoId;
                document.getElementById('deleteCorsoNome').textContent = nome;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteCorsoModal'));
                modal.show();
            });
        });
        
        // Conferma eliminazione
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                const corsoId = document.getElementById('delete_corso_id').value;
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
                
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCorsoModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
                });
            });
        }

        // Ricerca e filtri
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            searchInput.addEventListener('keyup', filterTable);
        }
        
        const tipoFilter = document.getElementById('tipoFilter');
        if (tipoFilter) {
            tipoFilter.addEventListener('change', filterTable);
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

