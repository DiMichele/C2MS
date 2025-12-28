@extends('layouts.app')

@section('title', 'Gestione Poligoni - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Poligoni</h1>
    </div>

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 500px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
            <input 
                type="text" 
                id="searchPoligono" 
                class="form-control" 
                placeholder="Cerca tipo di poligono..." 
                aria-label="Cerca tipo di poligono" 
                autocomplete="off"
                style="padding-left: 40px; border-radius: 6px !important;">
        </div>
    </div>

    <!-- Tabella Tipi Poligono -->
    <div class="table-container-ruolini" style="max-width: 1200px; margin: 0 auto;">
        <table class="table table-hover mb-0 ruolini-table" id="poligoniTable">
            <thead>
                <tr>
                    <th style="width: 40%;">Nome Tipo Poligono</th>
                    <th style="width: 20%; text-align: center;">Durata (mesi)</th>
                    <th style="width: 20%; text-align: center;">Stato</th>
                    <th style="width: 20%; text-align: center;">Azioni</th>
                </tr>
            </thead>
            <tbody id="poligoniTableBody">
                @forelse($poligoni as $poligono)
                <tr data-poligono-id="{{ $poligono->id }}" 
                    data-nome="{{ $poligono->nome }}"
                    data-attivo="{{ $poligono->attivo ? '1' : '0' }}"
                    class="{{ !$poligono->attivo ? 'table-secondary' : '' }}">
                    <td><strong>{{ $poligono->nome }}</strong></td>
                    <td style="text-align: center;">
                        <input type="number" 
                               class="form-control durata-input" 
                               data-poligono-id="{{ $poligono->id }}"
                               value="{{ $poligono->durata_mesi }}" 
                               min="0"
                               placeholder="0 = Nessuna scadenza"
                               style="width: 120px; margin: 0 auto; text-align: center;">
                        @if($poligono->durata_mesi == 0)
                        <small class="text-muted d-block">Nessuna scadenza</small>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if($poligono->attivo)
                            <span class="badge bg-success">Attivo</span>
                        @else
                            <span class="badge bg-secondary">Inattivo</span>
                        @endif
                    </td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-primary edit-poligono-btn" 
                                data-poligono-id="{{ $poligono->id }}"
                                data-nome="{{ $poligono->nome }}"
                                data-descrizione="{{ $poligono->descrizione }}"
                                data-durata="{{ $poligono->durata_mesi }}"
                                data-punteggio-minimo="{{ $poligono->punteggio_minimo }}"
                                data-punteggio-massimo="{{ $poligono->punteggio_massimo }}"
                                title="Modifica tipo poligono">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-poligono-btn" 
                                data-poligono-id="{{ $poligono->id }}"
                                data-nome="{{ $poligono->nome }}"
                                title="Elimina tipo poligono">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <p class="mb-0">Nessun tipo di poligono configurato</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Floating Action Button -->
<button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createPoligonoModal" title="Nuovo Tipo Poligono">
    <i class="fas fa-plus"></i>
</button>

<!-- Modal Creazione Tipo Poligono -->
<div class="modal fade" id="createPoligonoModal" tabindex="-1" aria-labelledby="createPoligonoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="createPoligonoModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Nuovo Tipo Poligono
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPoligonoForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">
                            <i class="fas fa-bullseye me-1"></i>Nome Tipo Poligono <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome" 
                               name="nome" 
                               placeholder="es. Tiro Notturno, Tiro in Movimento, ecc." 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descrizione" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Descrizione
                        </label>
                        <textarea class="form-control" 
                                  id="descrizione" 
                                  name="descrizione" 
                                  rows="2"
                                  placeholder="Descrizione dettagliata (opzionale)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="durata_mesi" class="form-label">
                            <i class="fas fa-clock me-1"></i>Durata Validità (mesi) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="durata_mesi" 
                               name="durata_mesi" 
                               value="6" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="punteggio_minimo" class="form-label">
                                <i class="fas fa-star-half-alt me-1"></i>Punteggio Minimo
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="punteggio_minimo" 
                                   name="punteggio_minimo" 
                                   value="0" 
                                   min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="punteggio_massimo" class="form-label">
                                <i class="fas fa-star me-1"></i>Punteggio Massimo
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="punteggio_massimo" 
                                   name="punteggio_massimo" 
                                   value="100" 
                                   min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Crea Tipo Poligono
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Tipo Poligono -->
<div class="modal fade" id="editPoligonoModal" tabindex="-1" aria-labelledby="editPoligonoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title" id="editPoligonoModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifica Tipo Poligono
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPoligonoForm">
                @csrf
                <input type="hidden" id="edit_poligono_id" name="poligono_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">
                            <i class="fas fa-bullseye me-1"></i>Nome Tipo Poligono <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="edit_nome" 
                               name="nome" 
                               required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_descrizione" class="form-label">
                            <i class="fas fa-align-left me-1"></i>Descrizione
                        </label>
                        <textarea class="form-control" 
                                  id="edit_descrizione" 
                                  name="descrizione" 
                                  rows="2"
                                  placeholder="Descrizione dettagliata (opzionale)"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_durata_mesi" class="form-label">
                            <i class="fas fa-clock me-1"></i>Durata Validità (mesi) <span class="text-danger">*</span>
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="edit_durata_mesi" 
                               name="durata_mesi" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_punteggio_minimo" class="form-label">
                                <i class="fas fa-star-half-alt me-1"></i>Punteggio Minimo
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="edit_punteggio_minimo" 
                                   name="punteggio_minimo" 
                                   min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_punteggio_massimo" class="form-label">
                                <i class="fas fa-star me-1"></i>Punteggio Massimo
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="edit_punteggio_massimo" 
                                   name="punteggio_massimo" 
                                   min="0">
                        </div>
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
<div class="modal fade" id="deletePoligonoModal" tabindex="-1" aria-labelledby="deletePoligonoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deletePoligonoModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Sei sicuro di voler eliminare il tipo di poligono <strong id="deletePoligonoNome"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Attenzione:</strong> Se ci sono scadenze associate, il tipo verrà disattivato invece di essere eliminato.
                </div>
                <input type="hidden" id="delete_poligono_id">
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
        
        const searchInput = document.getElementById('searchPoligono');
        const tbody = document.getElementById('poligoniTableBody');

        // Salvataggio durata
        document.querySelectorAll('.durata-input').forEach(function(input) {
            input.addEventListener('change', function() {
                const poligonoId = this.dataset.poligonoId;
                const durataMesi = parseInt(this.value);
                const inputElement = this;
                
                if (durataMesi < 0) {
                    alert('La durata non può essere negativa');
                    return;
                }
                
                const updateUrl = '{{ url("gestione-poligoni") }}' + '/' + poligonoId;
                
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

        // Gestione form creazione tipo poligono
        const createForm = document.getElementById('createPoligonoForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    nome: formData.get('nome'),
                    descrizione: formData.get('descrizione'),
                    durata_mesi: parseInt(formData.get('durata_mesi')),
                    punteggio_minimo: parseInt(formData.get('punteggio_minimo')) || 0,
                    punteggio_massimo: parseInt(formData.get('punteggio_massimo')) || 100
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
                
                fetch('{{ route("gestione-poligoni.store") }}', {
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createPoligonoModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea Tipo Poligono';
                });
            });
        }
        
        // Gestione pulsanti modifica
        document.querySelectorAll('.edit-poligono-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const poligonoId = this.dataset.poligonoId;
                const nome = this.dataset.nome;
                const descrizione = this.dataset.descrizione || '';
                const durata = this.dataset.durata;
                const punteggioMinimo = this.dataset.punteggioMinimo || 0;
                const punteggioMassimo = this.dataset.punteggioMassimo || 100;
                
                document.getElementById('edit_poligono_id').value = poligonoId;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_descrizione').value = descrizione;
                document.getElementById('edit_durata_mesi').value = durata;
                document.getElementById('edit_punteggio_minimo').value = punteggioMinimo;
                document.getElementById('edit_punteggio_massimo').value = punteggioMassimo;
                
                const modal = new bootstrap.Modal(document.getElementById('editPoligonoModal'));
                modal.show();
            });
        });
        
        // Gestione form modifica tipo poligono
        const editForm = document.getElementById('editPoligonoForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const poligonoId = document.getElementById('edit_poligono_id').value;
                const formData = new FormData(this);
                const data = {
                    nome: formData.get('nome'),
                    descrizione: formData.get('descrizione'),
                    durata_mesi: parseInt(formData.get('durata_mesi')),
                    punteggio_minimo: parseInt(formData.get('punteggio_minimo')) || 0,
                    punteggio_massimo: parseInt(formData.get('punteggio_massimo')) || 100
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
                
                fetch('{{ url("gestione-poligoni") }}' + '/' + poligonoId + '/edit', {
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editPoligonoModal'));
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
        document.querySelectorAll('.delete-poligono-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const poligonoId = this.dataset.poligonoId;
                const nome = this.dataset.nome;
                
                document.getElementById('delete_poligono_id').value = poligonoId;
                document.getElementById('deletePoligonoNome').textContent = nome;
                
                const modal = new bootstrap.Modal(document.getElementById('deletePoligonoModal'));
                modal.show();
            });
        });
        
        // Conferma eliminazione
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                const poligonoId = document.getElementById('delete_poligono_id').value;
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
                
                fetch('{{ url("gestione-poligoni") }}' + '/' + poligonoId, {
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('deletePoligonoModal'));
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

        // Ricerca
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
            searchInput.addEventListener('keyup', filterTable);
        }

        function filterTable() {
            if (!tbody) return;
            
            const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const rows = tbody.querySelectorAll('tr[data-poligono-id]');
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
        }
    }
})();
</script>

<style>
.table-container-ruolini {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    margin: 0;
}

.ruolini-table {
    margin-bottom: 0 !important;
}

.ruolini-table thead {
    background-color: #0a2342;
    color: white;
}

.ruolini-table thead th {
    font-weight: 600;
    padding: 1rem;
    border-bottom: none;
}

.ruolini-table tbody tr {
    transition: all 0.2s;
}

.ruolini-table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.05);
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
</style>
@endsection

