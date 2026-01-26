@if(!isset($isTab) || !$isTab)
@extends('layouts.app')

@section('title', 'Gestione Campi Anagrafica')

@section('content')
@endif
<div class="container-fluid">
    @if(!isset($isTab))
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Campi Anagrafica</h1>
        <p class="text-muted">Configura le colonne personalizzate da visualizzare nella pagina anagrafica</p>
    </div>
    @else
    <div class="text-center mb-4">
        <h3 class="mb-2">Gestione Campi Anagrafica</h3>
        <p class="text-muted">Configura le colonne personalizzate da visualizzare nella pagina anagrafica</p>
    </div>
    @endif

    <!-- Tabella Campi -->
    <div class="table-container-ruolini" style="max-width: 1400px; margin: 0 auto;">
        <table class="sugeco-table">
            <thead>
                <tr>
                    <th>Nome Campo</th>
                    <th>Tipo</th>
                    <th>Opzioni</th>
                    <th>Stato</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campi as $campo)
                <tr data-campo-id="{{ $campo->id }}">
                    <td><strong>{{ $campo->etichetta }}</strong></td>
                    <td>
                        @switch($campo->tipo_campo)
                            @case('text') Testo @break
                            @case('select') Selezione @break
                            @case('date') Data @break
                            @case('number') Numero @break
                            @case('textarea') Testo Lungo @break
                            @case('checkbox') Checkbox @break
                            @case('email') Email @break
                            @case('tel') Telefono @break
                        @endswitch
                    </td>
                    <td>
                        @if(($campo->tipo_campo === 'select' || $campo->tipo_campo === 'checkbox') && $campo->opzioni)
                            <small>{{ implode(', ', $campo->opzioni) }}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm toggle-active-btn {{ $campo->attivo ? 'btn-success' : 'btn-secondary' }}"
                                data-campo-id="{{ $campo->id }}"
                                data-attivo="{{ $campo->attivo ? 1 : 0 }}"
                                title="{{ $campo->attivo ? 'Clicca per disattivare: la colonna verrà nascosta nella pagina anagrafica' : 'Clicca per attivare: la colonna verrà mostrata nella pagina anagrafica' }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top">
                            <i class="fas fa-{{ $campo->attivo ? 'check' : 'times' }}"></i>
                            {{ $campo->attivo ? 'Attivo' : 'Disattivo' }}
                        </button>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-primary edit-campo-btn" 
                                data-campo='@json($campo)'
                                title="Modifica">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-campo-btn" 
                                data-campo-id="{{ $campo->id }}"
                                data-nome="{{ $campo->etichetta }}"
                                title="Elimina">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <p class="mb-0">Nessun campo configurato. Aggiungi il tuo primo campo personalizzato!</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- FAB Nuovo Campo -->
    <button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createCampoModal" title="Nuovo Campo">
        <i class="fas fa-plus"></i>
    </button>
</div>

<!-- Modal Creazione Campo -->
<div class="modal fade" id="createCampoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nuovo Campo Personalizzato</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createCampoForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-tag me-1"></i>Nome Campo (Etichetta) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="etichetta" id="create_etichetta" required placeholder="Es: Sesso" autocomplete="off">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-cog me-1"></i>Tipo Campo <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_campo" id="tipo_campo_create" required>
                                <option value="text">Testo</option>
                                <option value="select">Selezione (Dropdown)</option>
                                <option value="date">Data</option>
                                <option value="number">Numero</option>
                                <option value="email">Email</option>
                                <option value="tel">Telefono</option>
                                <option value="textarea">Testo Lungo</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="opzioni_container_create" style="display:none;">
                        <label class="form-label"><i class="fas fa-list me-1"></i>Opzioni (una per riga)</label>
                        <textarea class="form-control" name="opzioni_text" rows="3" placeholder="M&#10;F&#10;Altro"></textarea>
                        <small class="text-muted" id="opzioni_help_create">Per campi di selezione, inserisci ogni opzione su una riga separata</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="obbligatorio" id="obbligatorio_create" value="1">
                                <label class="form-check-label" for="obbligatorio_create">
                                    Campo obbligatorio
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-info-circle me-1"></i>Descrizione (opzionale)</label>
                        <input type="text" class="form-control" name="descrizione" placeholder="Testo di aiuto per gli utenti">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Crea Campo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Campo -->
<div class="modal fade" id="editCampoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifica Campo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCampoForm">
                @csrf
                <input type="hidden" id="edit_campo_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-tag me-1"></i>Nome Campo (Etichetta) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="etichetta" id="edit_etichetta" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="fas fa-cog me-1"></i>Tipo Campo <span class="text-danger">*</span></label>
                            <select class="form-select" name="tipo_campo" id="edit_tipo_campo" required>
                                <option value="text">Testo</option>
                                <option value="select">Selezione (Dropdown)</option>
                                <option value="date">Data</option>
                                <option value="number">Numero</option>
                                <option value="email">Email</option>
                                <option value="tel">Telefono</option>
                                <option value="textarea">Testo Lungo</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="opzioni_container_edit" style="display:none;">
                        <label class="form-label"><i class="fas fa-list me-1"></i>Opzioni (una per riga)</label>
                        <textarea class="form-control" name="opzioni_text" id="edit_opzioni_text" rows="3"></textarea>
                        <small class="text-muted" id="opzioni_help_edit">Per campi di selezione, inserisci ogni opzione su una riga separata</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="obbligatorio" id="edit_obbligatorio" value="1">
                                <label class="form-check-label" for="edit_obbligatorio">Campo obbligatorio</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-info-circle me-1"></i>Descrizione (opzionale)</label>
                        <input type="text" class="form-control" name="descrizione" id="edit_descrizione">
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

<!-- Modal Elimina Campo -->
<div class="modal fade" id="deleteCampoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Conferma Eliminazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Sei sicuro di voler eliminare il campo <strong id="deleteCampoNome"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Attenzione:</strong> Eliminando questo campo verranno rimossi anche tutti i valori salvati per i militari.
                </div>
                <input type="hidden" id="delete_campo_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annulla
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCampo">
                    <i class="fas fa-trash me-1"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>

@if(!isset($isTab) || !$isTab)
@endsection
@endif

<style>
/* Stili specifici per questa pagina */
/* (Stili base tabelle in table-standard.css) */
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // Inizializza i tooltip Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Mostra/nascondi opzioni in base al tipo campo
    document.getElementById('tipo_campo_create').addEventListener('change', function() {
        const opzioniContainer = document.getElementById('opzioni_container_create');
        const opzioniHelp = document.getElementById('opzioni_help_create');
        const mostraOpzioni = this.value === 'select' || this.value === 'checkbox';
        opzioniContainer.style.display = mostraOpzioni ? 'block' : 'none';
        
        if (mostraOpzioni) {
            if (this.value === 'checkbox') {
                opzioniHelp.textContent = 'Per campi checkbox, inserisci ogni opzione su una riga separata. Ogni opzione verrà mostrata come checkbox separata.';
            } else {
                opzioniHelp.textContent = 'Per campi di selezione, inserisci ogni opzione su una riga separata';
            }
        }
    });
    
    // Creazione campo
    document.getElementById('createCampoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Verifica che l'etichetta sia compilata
        const etichettaInput = this.querySelector('input[name="etichetta"]');
        const etichettaValue = etichettaInput ? etichettaInput.value.trim() : '';
        if (!etichettaValue) {
            alert('Errore: Il campo "Nome Campo (Etichetta)" è obbligatorio');
            etichettaInput?.focus();
            return;
        }
        
        const formData = new FormData(this);
        
        // Assicurati che l'etichetta sia sempre presente nel FormData
        if (!formData.get('etichetta') || formData.get('etichetta') !== etichettaValue) {
            formData.set('etichetta', etichettaValue);
        }
        
        // Verifica che tutti i campi obbligatori siano presenti
        if (!formData.get('etichetta') || !formData.get('tipo_campo')) {
            alert('Errore: Compila tutti i campi obbligatori');
            return;
        }
        
        // Converti opzioni da textarea a array
        if (formData.get('tipo_campo') === 'select' || formData.get('tipo_campo') === 'checkbox') {
            const opzioniText = formData.get('opzioni_text');
            const opzioniArray = opzioniText ? opzioniText.split('\n').filter(o => o.trim()) : [];
            formData.delete('opzioni_text');
            if (opzioniArray.length > 0) {
                opzioniArray.forEach((opt, index) => {
                    formData.append(`opzioni[${index}]`, opt.trim());
                });
            }
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
        
        fetch('{{ route("gestione-campi-anagrafica.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                // Se è un errore di validazione (422) o altro errore
                if (response.status === 422 || data.errors) {
                    const errorMessages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Errore di validazione'];
                    throw new Error(errorMessages.join('\n'));
                }
                throw new Error(data.message || 'Errore durante la creazione');
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('createCampoModal')).hide();
                location.reload();
            } else {
                let errorMsg = data.message || 'Errore sconosciuto';
                if (data.errors) {
                    const errorList = Object.values(data.errors).flat().join('\n');
                    errorMsg = errorList || errorMsg;
                }
                alert('Errore: ' + errorMsg);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea Campo';
            }
        })
        .catch(error => {
            const errorMsg = error.message || 'Errore durante la creazione';
            alert('Errore: ' + errorMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea Campo';
        });
    });
    
    // Edit campo
    document.querySelectorAll('.edit-campo-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const campo = JSON.parse(this.dataset.campo);
            document.getElementById('edit_campo_id').value = campo.id;
            document.getElementById('edit_etichetta').value = campo.etichetta;
            document.getElementById('edit_tipo_campo').value = campo.tipo_campo;
            document.getElementById('edit_obbligatorio').checked = campo.obbligatorio;
            document.getElementById('edit_descrizione').value = campo.descrizione || '';
            
            // Gestisci opzioni
            const opzioniContainer = document.getElementById('opzioni_container_edit');
            const opzioniHelp = document.getElementById('opzioni_help_edit');
            if ((campo.tipo_campo === 'select' || campo.tipo_campo === 'checkbox') && campo.opzioni) {
                opzioniContainer.style.display = 'block';
                document.getElementById('edit_opzioni_text').value = campo.opzioni.join('\n');
                if (campo.tipo_campo === 'checkbox') {
                    opzioniHelp.textContent = 'Per campi checkbox, inserisci ogni opzione su una riga separata. Ogni opzione verrà mostrata come checkbox separata.';
                } else {
                    opzioniHelp.textContent = 'Per campi di selezione, inserisci ogni opzione su una riga separata';
                }
            } else {
                opzioniContainer.style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('editCampoModal')).show();
        });
    });
    
    // Mostra/nascondi opzioni edit
    document.getElementById('edit_tipo_campo').addEventListener('change', function() {
        const opzioniContainer = document.getElementById('opzioni_container_edit');
        const opzioniHelp = document.getElementById('opzioni_help_edit');
        const mostraOpzioni = this.value === 'select' || this.value === 'checkbox';
        opzioniContainer.style.display = mostraOpzioni ? 'block' : 'none';
        
        if (mostraOpzioni) {
            if (this.value === 'checkbox') {
                opzioniHelp.textContent = 'Per campi checkbox, inserisci ogni opzione su una riga separata. Ogni opzione verrà mostrata come checkbox separata.';
            } else {
                opzioniHelp.textContent = 'Per campi di selezione, inserisci ogni opzione su una riga separata';
            }
        }
    });
    
    // Submit edit form
    document.getElementById('editCampoForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Verifica che l'etichetta sia compilata
        const etichettaInput = this.querySelector('input[name="etichetta"]');
        const etichettaValue = etichettaInput ? etichettaInput.value.trim() : '';
        if (!etichettaValue) {
            alert('Errore: Il campo "Nome Campo (Etichetta)" è obbligatorio');
            etichettaInput?.focus();
            return;
        }
        
        // Verifica che il tipo campo sia selezionato
        const tipoCampoInput = this.querySelector('select[name="tipo_campo"]');
        const tipoCampoValue = tipoCampoInput ? tipoCampoInput.value : '';
        if (!tipoCampoValue) {
            alert('Errore: Il campo "Tipo Campo" è obbligatorio');
            tipoCampoInput?.focus();
            return;
        }
        
        const campoId = document.getElementById('edit_campo_id').value;
        if (!campoId) {
            alert('Errore: ID campo non trovato');
            return;
        }
        
        const formData = new FormData(this);
        
        // Aggiungi _method per Laravel (necessario per PUT con FormData)
        formData.append('_method', 'PUT');
        
        // Assicurati che etichetta e tipo_campo siano sempre presenti nel FormData
        formData.set('etichetta', etichettaValue);
        formData.set('tipo_campo', tipoCampoValue);
        
        // Verifica che tutti i campi obbligatori siano presenti
        if (!formData.get('etichetta') || !formData.get('tipo_campo')) {
            alert('Errore: Compila tutti i campi obbligatori');
            return;
        }
        
        // Converti opzioni
        if (tipoCampoValue === 'select' || tipoCampoValue === 'checkbox') {
            const opzioniText = formData.get('opzioni_text');
            const opzioniArray = opzioniText ? opzioniText.split('\n').filter(o => o.trim()) : [];
            formData.delete('opzioni_text');
            if (opzioniArray.length > 0) {
                opzioniArray.forEach((opt, index) => {
                    formData.append(`opzioni[${index}]`, opt.trim());
                });
            }
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
        
        fetch(`{{ url('gestione-campi-anagrafica') }}/${campoId}/edit`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                // Se è un errore di validazione (422) o altro errore
                if (response.status === 422 || data.errors) {
                    const errorMessages = data.errors ? Object.values(data.errors).flat() : [data.message || 'Errore di validazione'];
                    throw new Error(errorMessages.join('\n'));
                }
                throw new Error(data.message || 'Errore durante l\'aggiornamento');
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('editCampoModal')).hide();
                location.reload();
            } else {
                let errorMsg = data.message || 'Errore sconosciuto';
                if (data.errors) {
                    const errorList = Object.values(data.errors).flat().join('\n');
                    errorMsg = errorList || errorMsg;
                }
                alert('Errore: ' + errorMsg);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva Modifiche';
            }
        })
        .catch(error => {
            const errorMsg = error.message || 'Errore durante l\'aggiornamento';
            alert('Errore: ' + errorMsg);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva Modifiche';
        });
    });
    
    // Delete campo
    document.querySelectorAll('.delete-campo-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_campo_id').value = this.dataset.campoId;
            document.getElementById('deleteCampoNome').textContent = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('deleteCampoModal')).show();
        });
    });
    
    document.getElementById('confirmDeleteCampo').addEventListener('click', function() {
        const campoId = document.getElementById('delete_campo_id').value;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
        
        fetch(`{{ url('gestione-campi-anagrafica') }}/${campoId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('deleteCampoModal')).hide();
                location.reload();
            } else {
                alert('Errore: ' + data.message);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
            }
        });
    });
    
    // Toggle attivo
    document.querySelectorAll('.toggle-active-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const campoId = this.dataset.campoId;
            const attivo = parseInt(this.dataset.attivo);
            
            fetch(`{{ url('gestione-campi-anagrafica') }}/${campoId}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                }
            });
        });
    });
});
</script>
@endpush

