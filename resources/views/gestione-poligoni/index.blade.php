@extends('layouts.app')

@section('title', 'Gestione Poligoni - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Poligoni</h1>
    </div>

    <!-- Tabella Tipi Poligono -->
    <div class="table-responsive" style="max-width: 900px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);">
        <table class="sugeco-table" id="poligoniTable" style="width: 100%;">
            <thead>
                <tr>
                    <th>Nome Tipo Poligono</th>
                    <th style="width: 180px;">Durata Validità</th>
                    <th style="width: 100px;">Azioni</th>
                </tr>
            </thead>
            <tbody id="poligoniTableBody">
                @forelse($poligoni as $poligono)
                <tr data-poligono-id="{{ $poligono->id }}" 
                    data-nome="{{ $poligono->nome }}">
                    <td><strong>{{ $poligono->nome }}</strong></td>
                    <td>
                        <div class="input-group" style="max-width: 150px; margin: 0 auto;">
                            <input type="number" 
                                   class="form-control durata-input text-center" 
                                   data-poligono-id="{{ $poligono->id }}"
                                   value="{{ $poligono->durata_mesi }}" 
                                   min="0"
                                   style="font-weight: 600;">
                            <span class="input-group-text">mesi</span>
                        </div>
                        @if($poligono->durata_mesi == 0)
                        <small class="text-muted d-block text-center">Nessuna scadenza</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary edit-poligono-btn" 
                                data-poligono-id="{{ $poligono->id }}"
                                data-nome="{{ $poligono->nome }}"
                                data-durata="{{ $poligono->durata_mesi }}"
                                title="Rinomina">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-poligono-btn" 
                                data-poligono-id="{{ $poligono->id }}"
                                data-nome="{{ $poligono->nome }}"
                                title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="text-center text-muted py-5">
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
                <h5 class="modal-title" id="createPoligonoModalLabel">Nuovo Tipo Poligono</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createPoligonoForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">
                            Nome Tipo Poligono <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="nome" 
                               name="nome" 
                               placeholder="es. Tiro Notturno, Tiro in Movimento, ecc." 
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
                               value="6" 
                               min="0" 
                               required>
                        <div class="form-text">Inserisci 0 per nessuna scadenza</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-success">Crea Tipo Poligono</button>
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
                <h5 class="modal-title" id="editPoligonoModalLabel">Modifica Tipo Poligono</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPoligonoForm">
                @csrf
                <input type="hidden" id="edit_poligono_id" name="poligono_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_nome" class="form-label">
                            Nome Tipo Poligono <span class="text-danger">*</span>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
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

        // Salvataggio durata inline
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
                        if (window.SUGECO && window.SUGECO.showSaveFeedback) {
                            window.SUGECO.showSaveFeedback(inputElement, true, 2000);
                        }
                    } else {
                        if (window.SUGECO && window.SUGECO.showSaveFeedback) {
                            window.SUGECO.showSaveFeedback(inputElement, false, 2000);
                        }
                    }
                })
                .catch(function(error) {
                    console.error('Errore salvataggio:', error);
                    if (window.SUGECO && window.SUGECO.showSaveFeedback) {
                        window.SUGECO.showSaveFeedback(inputElement, false, 2000);
                    }
                });
            });
        });
        
        // Gestione form creazione
        const createForm = document.getElementById('createPoligonoForm');
        if (createForm) {
            createForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const data = {
                    nome: formData.get('nome'),
                    durata_mesi: parseInt(formData.get('durata_mesi'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Creazione...';
                
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
                        const modal = bootstrap.Modal.getInstance(document.getElementById('createPoligonoModal'));
                        modal.hide();
                        location.reload();
                    }
                })
                .catch(function(error) {
                    alert('Errore: ' + error.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Crea Tipo Poligono';
                });
            });
        }
        
        // Gestione pulsanti modifica
        document.querySelectorAll('.edit-poligono-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const poligonoId = this.dataset.poligonoId;
                const nome = this.dataset.nome;
                const durata = this.dataset.durata;
                
                document.getElementById('edit_poligono_id').value = poligonoId;
                document.getElementById('edit_nome').value = nome;
                document.getElementById('edit_durata_mesi').value = durata;
                
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
                    durata_mesi: parseInt(formData.get('durata_mesi'))
                };
                
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Salvataggio...';
                
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
                    submitBtn.innerHTML = 'Salva Modifiche';
                });
            });
        }
        
        // Gestione pulsanti eliminazione
        document.querySelectorAll('.delete-poligono-btn').forEach(function(btn) {
            btn.addEventListener('click', async function() {
                const poligonoId = this.dataset.poligonoId;
                const nome = this.dataset.nome;
                
                // Usa il sistema di conferma unificato
                const confirmed = await SUGECO.Confirm.show({
                    title: 'Conferma Eliminazione',
                    message: `Eliminare il tipo di poligono "${nome}"? Verranno eliminate anche tutte le scadenze associate.`,
                    type: 'danger',
                    confirmText: 'Elimina'
                });
                
                if (!confirmed) return;
                
                // Esegui eliminazione
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
                        location.reload();
                    }
                })
                .catch(function(error) {
                    showError('Errore: ' + error.message);
                });
            });
        });
    }
})();
</script>

<style>
.durata-input {
    border: 2px solid #dee2e6;
    border-radius: 6px 0 0 6px !important;
    transition: all 0.2s;
}

.durata-input:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.25);
}

.input-group-text {
    background: #f8f9fa;
    border: 2px solid #dee2e6;
    border-left: none;
    font-size: 0.875rem;
}
</style>
@endsection
