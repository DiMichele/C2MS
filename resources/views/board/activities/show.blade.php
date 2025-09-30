@extends('layouts.app')

@section('title', $activity->title)

@push('styles')
<style>
    /* Badge militari minimal */
    #militariCounter {
        background: #007bff;
        color: white;
        border-radius: 4px;
        font-weight: 600;
        min-width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        padding: 2px 6px;
        border: none;
        box-shadow: none;
        text-decoration: none;
    }
    
    /* Rimuovi tutti gli effetti Bootstrap */
    #militariCounter:hover,
    #militariCounter:focus,
    #militariCounter:active {
        background: #007bff;
        color: white;
        transform: none;
        box-shadow: none;
        border: none;
    }
</style>
@endpush

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-6">
            <a href="{{ route('board.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Torna alla Board
            </a>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editActivityModal">
                    <i class="fas fa-edit"></i> Modifica
                </button>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConfirmModal">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center py-3 bg-light">
                    <h4 class="mb-0 fw-bold">{{ $activity->title }}</h4>
                    @php
                        $statusClasses = [
                            'urgenti' => 'bg-danger',
                            'in-scadenza' => 'bg-warning',
                            'pianificate' => 'bg-success',
                            'fuori-porta' => 'bg-info',
                        ];
                        $statusClass = $statusClasses[$activity->column->slug] ?? 'bg-primary';
                    @endphp
                    <span class="badge {{ $statusClass }}">{{ $activity->column->name }}</span>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Descrizione</h5>
                        <p class="mt-3">{{ $activity->description ?: 'Nessuna descrizione disponibile' }}</p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Data Inizio</h5>
                            <p class="mt-3">
                                <i class="fas fa-calendar text-primary me-2"></i> 
                                {{ $activity->start_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Data Fine</h5>
                            <p class="mt-3">
                                <i class="fas fa-calendar-check text-primary me-2"></i> 
                                {{ $activity->end_date ? $activity->end_date->format('d/m/Y') : 'Non specificata' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Stato Attuale</h5>
                        <div class="mt-3">
                            @php
                                $statusClass = match($activity->column->slug) {
                                    'urgenti' => 'bg-danger text-white',
                                    'in-scadenza' => 'bg-warning text-dark',
                                    'pianificate' => 'bg-success text-white', 
                                    'fuori-porta' => 'bg-info text-white',
                                    default => 'bg-primary text-white'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} fs-6 px-3 py-2" id="activity-status-badge">
                                <i class="fas fa-tag me-2"></i>{{ $activity->column->name }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Informazioni Aggiuntive</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Data creazione:</strong> {{ $activity->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ultimo aggiornamento:</strong> {{ $activity->updated_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Allegati e Link</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addAttachmentModal">
                        <i class="fas fa-plus"></i> Aggiungi
                    </button>
                </div>
                <div class="card-body">
                    @if($activity->attachments->count() > 0)
                        <div class="list-group">
                            @foreach($activity->attachments as $attachment)
                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ $attachment->url }}" target="_blank" class="text-decoration-none d-flex align-items-center">
                                            @if($attachment->type == 'link')
                                                <i class="fas fa-link text-primary me-2"></i>
                                            @else
                                                <i class="fas fa-file text-primary me-2"></i>
                                            @endif
                                            <span>{{ $attachment->title }}</span>
                                        </a>
                                        <small class="text-muted d-block">Aggiunto il {{ $attachment->created_at->format('d/m/Y') }}</small>
                                    </div>
                                    <div class="btn-group">
                                        <a href="{{ $attachment->url }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Apri collegamento">
                                            <i class="fas fa-external-link-alt me-1"></i>Apri
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteAttachmentModal" 
                                                data-attachment-id="{{ $attachment->id }}"
                                                data-attachment-title="{{ $attachment->title }}"
                                                title="Elimina allegato">
                                            <i class="fas fa-trash-alt me-1"></i>Elimina
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-file-alt mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-0">Nessun allegato disponibile</p>
                            <p class="small">Clicca su "Aggiungi" per inserire un nuovo allegato</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Militari Coinvolti</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMilitareModal">
                        <i class="fas fa-user-plus"></i> Aggiungi
                    </button>
                </div>
                <div class="card-body">
                    @if($activity->militari->count() > 0)
                        <div class="list-group">
                            @foreach($activity->militari as $militare)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }}</strong>
                                        <br>
                                        <small>{{ $militare->nome }}</small>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        @if($militare->plotone)
                                            <span class="badge bg-light text-dark border me-1">{{ $militare->plotone->nome }}</span>
                                        @endif
                                        @if($militare->polo)
                                            <span class="badge bg-light text-dark border me-2">{{ $militare->polo->nome }}</span>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#removeMilitareModal"
                                                data-militare-id="{{ $militare->id }}"
                                                data-militare-nome="{{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}"
                                                title="Rimuovi {{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} dall'attività"
                                                aria-label="Rimuovi militare">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-users mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-0">Nessun militare associato</p>
                            <p class="small">Clicca su "Aggiungi" per associare militari</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
            </div>
            
<!-- Modal per modificare l'attività -->
<div class="modal fade" id="editActivityModal" tabindex="-1" aria-labelledby="editActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('board.activities.update', $activity) }}" method="POST" id="editActivityForm">
                @csrf
                @method('PUT')
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="editActivityModalLabel">
                        <i class="fas fa-edit text-primary me-2"></i>Modifica Attività
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label fw-bold">Titolo *</label>
                        <input type="text" class="form-control" id="edit_title" name="title" value="{{ $activity->title }}" placeholder="Inserisci il titolo dell'attività" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label fw-bold">Descrizione</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" placeholder="Descrivi l'attività (opzionale)">{{ $activity->description }}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_start_date" class="form-label fw-bold">Data Inizio *</label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" value="{{ $activity->start_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_end_date" class="form-label fw-bold">Data Fine</label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date" value="{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : '' }}">
                            <small class="form-text text-muted">Se non specificata, sarà considerata la stessa data di inizio</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_column_id" class="form-label fw-bold">Stato *</label>
                        <select class="form-control" id="edit_column_id" name="column_id" required>
                            @foreach(App\Models\BoardColumn::orderBy('order')->get() as $column)
                            <option value="{{ $column->id }}" {{ $column->id == $activity->column_id ? 'selected' : '' }}>{{ $column->name }}</option>
                            @endforeach
                        </select>
                            </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Gestione Militari Coinvolti</label>
                        
                        <!-- Militari attualmente assegnati con possibilità di aggiungere -->
                        <div class="border rounded p-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0 text-muted d-flex align-items-center">
                                    <i class="fas fa-users me-2"></i>
                                    Militari Assegnati 
                                    <span class="ms-2" id="militariCounter">{{ $activity->militari->count() }}</span>
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="showAddMilitareSection()">
                                    <i class="fas fa-plus me-1"></i>Aggiungi Militare
                                </button>
                            </div>
                            
                            <!-- Lista militari attuali -->
                            <div id="militariAttuali" class="mb-3">
                                @if($activity->militari->count() > 0)
                                    <div class="row" id="militariGrid">
                                        @foreach($activity->militari as $militare)
                                        <div class="col-md-6 mb-2" data-militare-id="{{ $militare->id }}">
                                            <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border">
                                                <div class="flex-grow-1">
                                                    <strong class="d-block">{{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }}</strong>
                                                    <small class="text-muted">{{ $militare->nome }}</small>
                                                    @if($militare->plotone || $militare->polo)
                                                        <div class="mt-1">
                                                            @if($militare->plotone)
                                                                <span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">{{ $militare->plotone->nome }}</span>
                                                            @endif
                                                            @if($militare->polo)
                                                                <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">{{ $militare->polo->nome }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-militare-btn" 
                                                        data-militare-remove="{{ $militare->id }}"
                                                        title="Rimuovi militare">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div id="noMilitariMessage" class="text-center py-3 text-muted">
                                        <i class="fas fa-user-slash mb-2" style="font-size: 1.5rem;"></i>
                                        <p class="mb-0">Nessun militare assegnato a questa attività</p>
                                        <small>Clicca su "Aggiungi Militare" per iniziare</small>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Sezione per aggiungere nuovi militari (nascosta inizialmente) -->
                            <div id="addMilitareSection" style="display: none;" class="border-top pt-3">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-plus me-1"></i>Seleziona Militari da Aggiungere
                                </h6>
                                <div class="mb-2">
                                    <select class="form-control select2-add" id="nuovi_militari">
                                        <option value="">-- Seleziona un militare --</option>
                                        @foreach(App\Models\Militare::with(['grado', 'plotone', 'polo'])->orderByGradoENome()->get() as $militare)
                                            @if(!$activity->militari->contains($militare->id))
                                            <option value="{{ $militare->id }}">
                                                {{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                                @if($militare->plotone) - {{ $militare->plotone->nome }}@endif @if($militare->polo), {{ $militare->polo->nome }}@endif
                                            </option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Il militare selezionato verrà aggiunto automaticamente alla lista
                                    </small>
                                </div>
                                
                                <!-- Lista militari selezionati per l'aggiunta -->
                                <div id="militariSelezionatiContainer" class="mt-3" style="display: none;">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-users me-1"></i>Militari Selezionati per l'Aggiunta
                                    </h6>
                                    <div id="militariSelezionatiGrid" class="row">
                                        <!-- I militari selezionati appariranno qui -->
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hideAddMilitareSection()">
                                        <i class="fas fa-times me-1"></i>Chiudi
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Hidden input per il form finale -->
                            <input type="hidden" name="militari[]" id="militari_hidden_input">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <div id="autoSaveStatus" class="text-muted small">
                            <span id="autoSaveText"></span>
                        </div>
                        <div>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                            <button type="submit" class="btn btn-outline-primary" style="display: none;" id="manualSaveBtn">
                                <i class="fas fa-save me-1"></i> Salva Manualmente
                            </button>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per aggiungere militare -->
<div class="modal fade" id="addMilitareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('board.activities.attach.militare', $activity) }}" method="POST" id="addMilitareForm">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus text-primary me-2"></i>Aggiungi Militare
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_id" value="{{ $activity->id }}">
                    <div class="mb-3">
                        <label for="militare_id" class="form-label fw-bold">Militare</label>
                        <select class="form-control select2" id="militare_id" name="militare_id" required>
                            <option value="">-- Seleziona militare --</option>
                            @foreach(App\Models\Militare::with(['grado', 'plotone', 'polo'])->orderByGradoENome()->get() as $militare)
                                @if(!$activity->militari->contains($militare->id))
                                <option value="{{ $militare->id }}">
                                    {{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                    @if($militare->plotone) - {{ $militare->plotone->nome }}@endif @if($militare->polo), {{ $militare->polo->nome }}@endif
                                </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Aggiungi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per rimuovere militare -->
<div class="modal fade" id="removeMilitareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="" method="POST" id="removeMilitareForm">
                @csrf
                @method('DELETE')
                <input type="hidden" name="militare_id" id="removeMilitareId">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Rimuovi Militare</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler rimuovere <strong id="removeMilitareName"></strong> da questa attività?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Rimuovi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per aggiungere allegato -->
<div class="modal fade" id="addAttachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('board.activities.attach.file', $activity) }}" method="POST" id="addAttachmentForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-paperclip text-primary me-2"></i>Aggiungi Allegato
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="activity_id" value="{{ $activity->id }}">
                    
                    <!-- Selezione tipo allegato -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo Allegato *</label>
                        <div class="btn-group w-100" role="group" aria-label="Tipo allegato">
                            <input type="radio" class="btn-check" name="attachment_type" id="attachment_link" value="link" checked>
                            <label class="btn btn-outline-primary" for="attachment_link">
                                <i class="fas fa-link me-1"></i>Link Web
                            </label>
                            
                            <input type="radio" class="btn-check" name="attachment_type" id="attachment_file" value="file">
                            <label class="btn btn-outline-primary" for="attachment_file">
                                <i class="fas fa-upload me-1"></i>Carica File
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Titolo *</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Inserisci il titolo dell'allegato" required>
                    </div>
                    
                    <!-- Sezione per link web -->
                    <div id="linkSection" class="mb-3">
                        <label for="url" class="form-label fw-bold">URL *</label>
                        <input type="url" class="form-control" id="url" name="url" placeholder="https://esempio.com">
                        <small class="form-text text-muted">Inserisci l'indirizzo web completo</small>
                    </div>
                    
                    <!-- Sezione per caricamento file -->
                    <div id="fileSection" class="mb-3" style="display: none;">
                        <label for="file" class="form-label fw-bold">File *</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip,.rar">
                        <small class="form-text text-muted">Formati supportati: PDF, Office, Immagini, Archivi (max 10MB)</small>
                    </div>
                    
                    <input type="hidden" name="type" id="finalType" value="link">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Aggiungi Allegato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per eliminare allegato -->
<div class="modal fade" id="deleteAttachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form action="" method="POST" id="deleteAttachmentForm">
                @csrf
                @method('DELETE')
                <input type="hidden" name="attachment_id" id="deleteAttachmentId">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Elimina Allegato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Sei sicuro di voler eliminare l'allegato <strong id="deleteAttachmentTitle"></strong>?</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Elimina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per conferma eliminazione attività -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('board.activities.destroy', $activity) }}" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Elimina Attività
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Attenzione! Questa operazione non può essere annullata.
                    </div>
                    <p>Sei sicuro di voler eliminare definitivamente l'attività "<strong>{{ $activity->title }}</strong>"?</p>
                    <p>Questa operazione eliminerà anche tutti gli allegati e le associazioni con i militari.</p>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt me-1"></i> Elimina Definitivamente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per conferma conflitti militare -->
<div class="modal fade" id="conflictConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Conflitto Rilevato
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Il militare selezionato è già impegnato in altre attività nello stesso periodo.
                </div>
                <p><strong>Militare:</strong> <span id="conflictMilitareName"></span></p>
                <p><strong>Attività in conflitto:</strong></p>
                <div id="conflictsList"></div>
                <hr>
                <p class="mb-0"><strong>Vuoi comunque aggiungere il militare a questa attività?</strong></p>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning" id="forceAddMilitare">
                    <i class="fas fa-exclamation-triangle me-1"></i> Aggiungi Comunque
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast di notifica -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
    <div id="notificationToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i id="toastIcon" class="fas fa-info-circle text-primary me-2"></i>
            <strong class="me-auto" id="toastTitle">Notifica</strong>
            <small>Adesso</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage">
            Operazione completata con successo
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>

    
    /* Stili per le card */
    .card {
        border-radius: 0.5rem;
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        transition: all 0.2s ease;
    }
    
    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    
    .card-header {
        border-bottom: 1px solid rgba(0,0,0,0.1);
        border-radius: 0.5rem 0.5rem 0 0 !important;
    }
    
    /* Stili per la lista di militari e allegati */
    .list-group-item {
        border-left: none;
        border-right: none;
        transition: all 0.2s ease;
    }
    
    .list-group-item:first-child {
        border-top: none;
    }
    
    .list-group-item:last-child {
        border-bottom: none;
    }
    
    .list-group-item:hover {
        background-color: rgba(0,0,0,0.01);
    }
    
    /* Stili responsive */
    @media (max-width: 767px) {
        .row.mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .row > div {
            margin-bottom: 1rem;
        }
    }
    
    /* Stili specifici per i modal - Forza il layout a 2 colonne */
    .modal #militariGrid {
        display: flex !important;
        flex-wrap: wrap !important;
    }
    
    .modal #militariGrid .col-md-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding-right: 0.75rem;
        padding-left: 0.75rem;
    }
    
    /* Assicura che il layout si mantenga anche quando generato dinamicamente */
    .modal .row {
        display: flex !important;
        flex-wrap: wrap !important;
        margin-right: -0.75rem;
        margin-left: -0.75rem;
    }
    
    /* Forza esplicitamente il comportamento Bootstrap per i militari */
    .modal [data-militare-id] {
        flex: 0 0 auto !important;
        width: 50% !important;
        max-width: 50% !important;
        display: block !important;
    }
    

</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// Array per tenere traccia dei militari attuali
let militariAttuali = @json($activity->militari->pluck('id')->toArray());

// Funzione per assicurare che la griglia esista
function ensureGridExists() {
    let militariGrid = document.getElementById('militariGrid');
    if (!militariGrid) {
        const militariAttualiContainer = document.getElementById('militariAttuali');
        const noMilitariMessage = document.getElementById('noMilitariMessage');
        
        if (militariAttualiContainer) {
            
            // Crea la griglia seguendo ESATTAMENTE la struttura del Blade template
            const gridDiv = document.createElement('div');
            gridDiv.className = 'row';
            gridDiv.id = 'militariGrid';
            
            // Nascondi il messaggio vuoto se presente
            if (noMilitariMessage) {
                noMilitariMessage.style.display = 'none';
            }
            
            // Inserisci la griglia PRIMA del messaggio vuoto (se esiste) o alla fine
            if (noMilitariMessage) {
                militariAttualiContainer.insertBefore(gridDiv, noMilitariMessage);
            } else {
                militariAttualiContainer.appendChild(gridDiv);
            }
            
            militariGrid = gridDiv;
        }
    }
    return militariGrid;
}

document.addEventListener('DOMContentLoaded', function() {

    
    // Verifica presenza elementi chiave
    const editModal = document.getElementById('editActivityModal');
    const editForm = document.getElementById('editActivityForm');
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    
    

    
    // Assicuriamo che la griglia esista al caricamento della pagina
    ensureGridExists();
    
    // Debug: verifica lo stato iniziale
    const initialGrid = document.getElementById('militariGrid');

    
    // Event delegation per i pulsanti di rimozione militari (solo nel modal)
    document.addEventListener('click', function(e) {
        if (e.target.closest('#militariGrid .remove-militare-btn')) {
            const btn = e.target.closest('.remove-militare-btn');
            const militareId = btn.getAttribute('data-militare-remove');
            
            if (militareId) {
                e.preventDefault();
                e.stopPropagation();
                rimuoviMilitare(militareId);
            }
        }
    });
    // Utility per le notifiche
    function showToast(message, title = 'Notifica', icon = 'fa-info-circle', iconClass = 'text-primary') {
        const toast = document.getElementById('notificationToast');
        if (!toast) return;
        
        const toastBootstrap = bootstrap.Toast.getOrCreateInstance(toast);
        
        document.getElementById('toastMessage').textContent = message;
        document.getElementById('toastTitle').textContent = title;
        
        const iconElement = document.getElementById('toastIcon');
        iconElement.className = `fas ${icon} ${iconClass} me-2`;
        
        toastBootstrap.show();
    }
    
    // Mostra toast in caso di operazioni effettuate (richiesta da URL)
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    const message = urlParams.get('message');
    
    if (status && message) {
        if (status === 'success') {
            showToast(message, 'Successo', 'fa-check-circle', 'text-success');
        } else if (status === 'error') {
            showToast(message, 'Errore', 'fa-exclamation-circle', 'text-danger');
        }
    }
    
    // Inizializza Select2
    try {
        if (jQuery && jQuery.fn.select2) {
            // Select2 per il modal principale
            $('.select2, .select2-edit').select2({
                placeholder: 'Seleziona un militare',
                width: '100%',
                theme: 'bootstrap-5',
                language: 'it',
                dropdownParent: $('#addMilitareModal'),
                selectionCssClass: 'py-1',
                dropdownCssClass: 'py-1'
            });
            
            // Select2 per il select nel modal di modifica
            $('#nuovi_militari').select2({
                placeholder: '-- Seleziona un militare --',
                width: '100%',
                theme: 'bootstrap-5',
                language: 'it',
                dropdownParent: $('#editActivityModal'),
                selectionCssClass: 'py-1',
                dropdownCssClass: 'py-1'
            });
            
        }
    } catch (error) {
    }
    
    // Gestisci reset form quando modal vengono chiusi
    const modals = ['addMilitareModal', 'addAttachmentModal'];
    
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById(modalId.replace('Modal', 'Form'));
                if (form) form.reset();
                
                // Reset Select2 se presente
                if (modalId === 'addMilitareModal') {
                    try {
                        if (jQuery && jQuery.fn.select2) {
                            $('#militare_id').val('').trigger('change');
                        }
                    } catch (error) {
                        // Errore silenzioso
                    }
                }
            });
        }
    });
    
    // Gestisci il modal per rimuovere militare
    const removeMilitareModal = document.getElementById('removeMilitareModal');
    if (removeMilitareModal) {
        removeMilitareModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const militareId = button.getAttribute('data-militare-id');
            const militareName = button.getAttribute('data-militare-nome');
            
            // Imposta l'action del form con entrambi i parametri
            const form = document.getElementById('removeMilitareForm');
            form.action = `{{ route('board.activities.detach.militare', ['activity' => $activity->id, 'militare' => '__MILITARE_ID__']) }}`.replace('__MILITARE_ID__', militareId);
            
            document.getElementById('removeMilitareId').value = militareId;
            document.getElementById('removeMilitareName').textContent = militareName;
        });
    }
    
    // Gestisci il modal per eliminare allegato
    const deleteAttachmentModal = document.getElementById('deleteAttachmentModal');
    if (deleteAttachmentModal) {
        deleteAttachmentModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const attachmentId = button.getAttribute('data-attachment-id');
            const attachmentTitle = button.getAttribute('data-attachment-title');
            
            // Imposta l'action del form con entrambi i parametri
            const form = document.getElementById('deleteAttachmentForm');
            form.action = `{{ route('board.activities.detach.file', ['activity' => $activity->id, 'attachment' => '__ATTACHMENT_ID__']) }}`.replace('__ATTACHMENT_ID__', attachmentId);
            
            document.getElementById('deleteAttachmentId').value = attachmentId;
            document.getElementById('deleteAttachmentTitle').textContent = attachmentTitle;
        });
    }
    
    // Gestisci il form per aggiungere militare con controllo conflitti
    const addMilitareForm = document.getElementById('addMilitareForm');
    if (addMilitareForm) {
        addMilitareForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const militareId = formData.get('militare_id');
            
            if (!militareId) {
                showToast('Seleziona un militare', 'Errore', 'fa-exclamation-circle', 'text-danger');
                return;
            }
            
            // Ottieni il token CSRF
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Aggiungi i dati al FormData
            formData.append('_token', csrfToken);
            
            // Invia la richiesta per controllare i conflitti
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.has_conflicts) {
                    // Mostra modal di conferma conflitti
                    showConflictModal(militareId, data.conflicts);
                } else if (data.success) {
                    // Successo - aggiorna dinamicamente senza ricaricare
                    showToast(data.message, 'Successo', 'fa-check-circle', 'text-success');
                    
                    // Chiudi il modal
                    const addModal = bootstrap.Modal.getInstance(document.getElementById('addMilitareModal'));
                    if (addModal) {
                        addModal.hide();
                    }
                    
                    // Reset del form
                    addMilitareForm.reset();
                    if (jQuery && jQuery.fn.select2) {
                        $('#militare_id').val(null).trigger('change');
                    }
                    
                    // Aggiorna la lista militari se i dati sono forniti
                    if (data.militare) {
                        addMilitareToView(data.militare);
                    } else {
                        // Fallback: ricarica solo se non abbiamo i dati
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                    }
                } else {
                    showToast(data.message || 'Errore durante l\'aggiunta', 'Errore', 'fa-exclamation-circle', 'text-danger');
                }
            })
            .catch(error => {
                showToast('Errore di connessione', 'Errore', 'fa-exclamation-circle', 'text-danger');
            });
        });
    }
    
    // Gestisce il cambio tra link e file nel modal allegati
    document.querySelectorAll('input[name="attachment_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const linkSection = document.getElementById('linkSection');
            const fileSection = document.getElementById('fileSection');
            const finalType = document.getElementById('finalType');
            const urlInput = document.getElementById('url');
            const fileInput = document.getElementById('file');
            
            if (this.value === 'link') {
                linkSection.style.display = 'block';
                fileSection.style.display = 'none';
                finalType.value = 'link';
                urlInput.required = true;
                fileInput.required = false;
            } else {
                linkSection.style.display = 'none';
                fileSection.style.display = 'block';
                finalType.value = 'file';
                urlInput.required = false;
                fileInput.required = true;
            }
        });
    });
    
    // Gestisci il form per rimuovere militare via AJAX
    const removeMilitareForm = document.getElementById('removeMilitareForm');
    if (removeMilitareForm) {
        removeMilitareForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const militareId = formData.get('militare_id');
            
            if (!militareId) {
                showToast('Errore: ID militare non trovato', 'Errore', 'fa-exclamation-circle', 'text-danger');
                return;
            }
            
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    // Chiudi il modal
                    const removeModal = bootstrap.Modal.getInstance(document.getElementById('removeMilitareModal'));
                    if (removeModal) {
                        removeModal.hide();
                    }
                    
                    // Rimuovi il militare dalla vista principale
                    removeMilitareFromView(militareId);
                    
                    // Mostra toast di successo
                    showToast(data.message, 'Successo', 'fa-check-circle', 'text-success');
                    
                } else {
                    showToast(data.message || 'Errore durante la rimozione', 'Errore', 'fa-exclamation-circle', 'text-danger');
                }
            })
            .catch(error => {
                showToast('Errore di connessione', 'Errore', 'fa-exclamation-circle', 'text-danger');
            });
        });
    }

    // Gestisci il select per aggiungere militari dal modal di modifica
    const nuoviMilitariSelect = document.getElementById('nuovi_militari');
    if (nuoviMilitariSelect) {
        // Event listener nativo
        nuoviMilitariSelect.addEventListener('change', function() {
            const militareId = this.value;
            
            if (militareId) {
                
                // Chiama la funzione per aggiungere automaticamente il militare
                aggiungiMilitareAutomatico(militareId);
            }
        });
        
        // Event listener per Select2 se presente
        try {
            if (jQuery && jQuery.fn.select2) {
                $('#nuovi_militari').on('select2:select', function(e) {
                    const militareId = e.params.data.id;
                    
                    if (militareId) {
                
                        
                        // Chiama la funzione per aggiungere automaticamente il militare
                        aggiungiMilitareAutomatico(militareId);
                    }
                });
            }
        } catch (error) {
        }
        
    }

    // ============================================
    // FUNZIONI DI SUPPORTO PER MILITARI - DENTRO DOMContentLoaded  
    // ============================================
    
    // Funzione per aggiungere automaticamente un militare quando selezionato
    function aggiungiMilitareAutomatico(militareId) {
        const select = document.getElementById('nuovi_militari');
        const option = select.querySelector(`option[value="${militareId}"]`);
        
        if (!option) return;
        
        const militareText = option.text;
        
        // Aggiungi all'array se non già presente
        if (!window.militariAttuali.includes(parseInt(militareId))) {
            
            // Salva il militare nel database tramite AJAX
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const activityId = '{{ $activity->id }}';
            
            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('militare_id', militareId);
            formData.append('activity_id', activityId);
            
            fetch(`{{ route('board.activities.attach.militare', $activity) }}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    window.militariAttuali.push(parseInt(militareId));
                    
                    // Aggiungi alla UI del modal con i dati completi
                    aggiungiMilitareAllaUI(militareId, data.militare);
                    
                    // Aggiungi anche alla vista principale
                    addMilitareToMainView(data.militare);
                
                // Aggiorna counter
                updateMilitariCounter();
                
                // Controlla stato vuoto
                checkEmptyState();
                
                // Aggiorna input hidden
                updateHiddenInput();
                
                // Rimuovi l'opzione dal select e aggiorna
                option.remove();
                
                // Reset della selezione
                if (jQuery && jQuery.fn.select2) {
                    $('#nuovi_militari').val(null).trigger('change');
                }
                
                    // Mostra toast di successo standard
                    if (typeof showToast === 'function') {
                        showToast(`Militare ${militareText.split(' - ')[0]} aggiunto con successo`, 'Successo', 'fa-check-circle', 'text-success');
                    }
                    
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Errore durante l\'aggiunta', 'Errore', 'fa-exclamation-circle', 'text-danger');
                    }
                }
            })
            .catch(error => {
                if (typeof showToast === 'function') {
                    showToast('Errore di connessione', 'Errore', 'fa-exclamation-circle', 'text-danger');
                }
            });
        }
    }
    
    // Funzione per aggiornare il counter
    function updateMilitariCounter() {
        const counter = document.getElementById('militariCounter');
        if (counter) {
            counter.textContent = militariAttuali.length;
        }
    }

    // Variabile globale per tenere traccia dei militari attuali
    window.militariAttuali = [];
    
    // Inizializza l'array con i militari già presenti
    document.querySelectorAll('#militariGrid [data-militare-id]').forEach(element => {
        const militareId = parseInt(element.getAttribute('data-militare-id'));
        if (militareId && !window.militariAttuali.includes(militareId)) {
            window.militariAttuali.push(militareId);
        }
    });
    


    // Event listener per rimozione militari dalla sezione selezionati
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-militare-btn')) {
            const button = e.target.closest('.remove-militare-btn');
            const militareId = button.getAttribute('data-militare-remove');
            

            
            if (militareId) {
                removeMilitareViaAjax(militareId);
            }
        }
    });

    // Funzione per rimuovere militare tramite AJAX
    function removeMilitareViaAjax(militareId) {

        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const activityId = '{{ $activity->id }}';
        
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');
        
        const deleteUrl = `{{ route('board.activities.detach.militare', ['activity' => $activity->id, 'militare' => '__MILITARE_ID__']) }}`.replace('__MILITARE_ID__', militareId);

        
        fetch(deleteUrl, {
            method: 'POST',
            body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                
                // Rimuovi dalla lista locale
                window.militariAttuali = window.militariAttuali.filter(id => id != militareId);
                
                // Rimuovi dalla UI della sezione selezionati
                removeMilitareFromView(militareId);
                
                // Rimuovi dalla vista principale
                removeMilitareFromMainView(militareId);
                
                // Aggiorna counter
                updateMilitariCounter();
                
                // Controlla stato vuoto
                checkEmptyState();
                
                // Mostra toast di successo
                if (typeof showToast === 'function') {
                    showToast('Militare rimosso con successo', 'Successo', 'fa-check-circle', 'text-success');
                }
                
                } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Errore durante la rimozione', 'Errore', 'fa-exclamation-circle', 'text-danger');
                }
                }
            })
            .catch(error => {
            if (typeof showToast === 'function') {
                showToast('Errore di connessione', 'Errore', 'fa-exclamation-circle', 'text-danger');
            }
        });
    }

    // Funzione per rimuovere militare dalla vista della sezione selezionati
    function removeMilitareFromView(militareId) {
        const militareElement = document.querySelector(`#militariSelezionatiGrid [data-militare-id="${militareId}"]`);
        if (militareElement) {
            militareElement.remove();
            
            // Nascondi il container se non ci sono più militari selezionati
            const grid = document.getElementById('militariSelezionatiGrid');
            const container = document.getElementById('militariSelezionatiContainer');
            if (grid && grid.children.length === 0 && container) {
                container.style.display = 'none';
            }
        }
        
        // Rimuovi anche dalla griglia principale del modal se presente
        const mainGridElement = document.querySelector(`#militariGrid [data-militare-id="${militareId}"]`);
        if (mainGridElement) {
            mainGridElement.remove();
        }
    }

    // Funzione per rimuovere militare dalla vista principale
    function removeMilitareFromMainView(militareId) {
        const mainListElements = document.querySelectorAll('.list-group-item');
        mainListElements.forEach(element => {
            const button = element.querySelector(`[data-militare-id="${militareId}"]`);
            if (button) {
                element.remove();
                
                // Aggiorna il counter nella vista principale
                const mainCounter = document.querySelector('.badge.bg-primary');
                if (mainCounter) {
                    const currentCount = parseInt(mainCounter.textContent) || 0;
                    mainCounter.textContent = Math.max(0, currentCount - 1);
                }
            }
        });
    }

    // Funzione per aggiungere militare alla UI nella sezione "Selezionati per l'Aggiunta"
    function aggiungiMilitareAllaUI(militareId, militareData) {
        
        // Controlla se il militare esiste già nella sezione selezionati
        const existingElement = document.querySelector(`#militariSelezionatiGrid [data-militare-id="${militareId}"]`);
        if (existingElement) {
            return;
        }
        
        // Ottieni la griglia dei militari selezionati
        const grid = document.getElementById('militariSelezionatiGrid');
        const container = document.getElementById('militariSelezionatiContainer');
        
        if (!grid || !container) {
            return;
        }

        // Mostra il container se nascosto
        container.style.display = 'block';
        
        const militareDiv = document.createElement('div');
        militareDiv.className = 'col-md-6 mb-2';
        militareDiv.setAttribute('data-militare-id', militareId);
        
        // Costruisci i badge per plotone e polo
        let badgesHtml = '';
        if (militareData.plotone || militareData.polo) {
            badgesHtml = '<div class="mt-1">';
            if (militareData.plotone) {
                badgesHtml += `<span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">${militareData.plotone.nome}</span>`;
            }
            if (militareData.polo) {
                badgesHtml += `<span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${militareData.polo.nome}</span>`;
            }
            badgesHtml += '</div>';
        }
        
        militareDiv.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-2 bg-white rounded border">
                <div class="flex-grow-1">
                    <strong class="d-block">${militareData.grado.abbreviazione} ${militareData.cognome}</strong>
                    <small class="text-muted">${militareData.nome}</small>
                    ${badgesHtml}
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger remove-militare-btn" 
                        data-militare-remove="${militareId}"
                        title="Rimuovi militare">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        grid.appendChild(militareDiv);
    }

    // Funzione per aggiungere militare alla vista principale (fuori dal modal)
    function addMilitareToMainView(militare) {
        
        // Trova la lista dei militari nella vista principale
        const mainListGroup = document.querySelector('.list-group');
        if (!mainListGroup) {
            return;
        }
        
        // Costruisci i badge per plotone e polo
        let badgesHtml = '';
        if (militare.plotone || militare.polo) {
            badgesHtml = '<div class="mt-1">';
            if (militare.plotone) {
                badgesHtml += `<span class="badge bg-light text-dark border me-1" style="font-size: 0.7rem;">${militare.plotone.nome}</span>`;
            }
            if (militare.polo) {
                badgesHtml += `<span class="badge bg-light text-dark border" style="font-size: 0.7rem;">${militare.polo.nome}</span>`;
            }
            badgesHtml += '</div>';
        }
        
        // Crea l'elemento del militare per la vista principale
        const militareElement = document.createElement('div');
        militareElement.className = 'list-group-item d-flex justify-content-between align-items-center';
        militareElement.innerHTML = `
            <div>
                <strong>${militare.grado.abbreviazione} ${militare.cognome}</strong>
                <br>
                <small>${militare.nome}</small>
                ${badgesHtml}
                </div>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#removeMilitareModal"
                        data-militare-id="${militare.id}"
                        data-militare-name="${militare.grado.abbreviazione} ${militare.cognome}"
                        title="Rimuovi militare">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        
        // Aggiungi alla lista principale
        mainListGroup.appendChild(militareElement);
        
        // Aggiorna il counter nella vista principale se presente
        const mainCounter = document.querySelector('.badge.bg-primary');
        if (mainCounter) {
            const currentCount = parseInt(mainCounter.textContent) || 0;
            mainCounter.textContent = currentCount + 1;
        }
    }
    
    // Funzione per controllare se non ci sono militari
    function checkEmptyState() {
        const noMilitariMessage = document.getElementById('noMilitariMessage');
        const militariGrid = document.getElementById('militariGrid');
        
        
        if (window.militariAttuali.length === 0) {
            // Nessun militare - mostra messaggio vuoto
            if (militariGrid) {
                militariGrid.style.display = 'none';
            }
            if (noMilitariMessage) {
                noMilitariMessage.style.display = 'block';
            }
        } else {
            // Ci sono militari - mostra griglia
            if (militariGrid) {
                militariGrid.style.display = 'block';
            }
            if (noMilitariMessage) {
                noMilitariMessage.style.display = 'none';
            }
        }
    }
    
    // Funzione per aggiornare l'input hidden
    function updateHiddenInput() {
        // Aggiorna eventuali input hidden se necessario
        const hiddenInputs = document.querySelectorAll('input[name="militari[]"]');
        hiddenInputs.forEach(input => input.remove());
        
        const form = document.getElementById('editActivityForm');
        if (form) {
            window.militariAttuali.forEach(militareId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'militari[]';
            input.value = militareId;
            form.appendChild(input);
        });
    }
    }

    // Funzione per assicurare che la griglia esista
    function ensureGridExists() {
        let grid = document.getElementById('militariGrid');
        if (!grid) {
            // Crea una nuova griglia se non esiste
            grid = document.createElement('div');
            grid.id = 'militariGrid';
            grid.className = 'row';
            
            const container = document.getElementById('militariAttuali');
            if (container) {
                // Nascondi il messaggio "nessun militare" se presente
                const noMilitariMessage = document.getElementById('noMilitariMessage');
                if (noMilitariMessage) {
                    noMilitariMessage.style.display = 'none';
                }
                
                container.appendChild(grid);
            }
            } else {
            // La griglia esiste già, assicurati che sia visibile
            grid.style.display = 'block';
        }
        return grid;
    }
    
    // Funzione per mostrare feedback quando un militare viene aggiunto
    function showMilitareAddedFeedback(militareText) {
        // Crea un toast temporaneo
        const toast = document.createElement('div');
        toast.className = 'alert alert-success alert-dismissible fade show position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            <strong>Militare aggiunto:</strong> ${militareText.split(' - ')[0]}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Rimuovi automaticamente dopo 3 secondi
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 3000);
    }
    
    // ============================================
// SISTEMA DI AUTOSALVATAGGIO - DENTRO DOMContentLoaded
    // ============================================
    
    // Variabili per l'autosalvataggio
    let autoSaveTimeout = null;
    let autoSaveInProgress = false;
    let lastSavedValues = {};
    
    // Selettori dei campi da monitorare
    const fieldsToWatch = {
        'edit_title': 'title',
        'edit_description': 'description', 
        'edit_start_date': 'start_date',
        'edit_end_date': 'end_date',
        'edit_column_id': 'column_id'
    };
    
    // Funzione per mostrare lo stato dell'autosalvataggio
    function updateAutoSaveStatus(status, message) {
        const statusElement = document.getElementById('autoSaveStatus');
        const textElement = document.getElementById('autoSaveText');
        
    if (textElement) {
        textElement.textContent = message;
    }
        
    if (statusElement) {
        statusElement.classList.remove('text-muted', 'text-success', 'text-warning', 'text-danger');
        statusElement.classList.add(`text-${status}`);
    }
    }
    
    // Funzione per l'autosalvataggio
    function performAutoSave(changedField, value) {
    
    if (autoSaveInProgress) {
        return;
    }
        
        autoSaveInProgress = true;
        updateAutoSaveStatus('warning', 'Salvataggio in corso...');
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const activityId = '{{ $activity->id }}';
    
        
        // Prepara i dati da inviare
        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'PATCH');
    formData.append('field', changedField);
    formData.append('value', value);

        
        fetch(`{{ route('board.activities.autosave', $activity) }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            return response.json();
        })
        .then(data => {
            
            if (data.success) {
                updateAutoSaveStatus('success', `Salvato automaticamente alle ${data.updated_at}`);
                
                // Memorizza il valore salvato
                lastSavedValues[changedField] = value;
                
                // Aggiorna la data di ultimo aggiornamento nella vista
                const paragraphs = document.querySelectorAll('.col-md-6 p');
                paragraphs.forEach(p => {
                    if (p.textContent.includes('Ultimo aggiornamento:')) {
                        p.innerHTML = `<strong>Ultimo aggiornamento:</strong> ${data.updated_at}`;
                    }
                });
                
                // Aggiorna i dati nella vista principale se disponibili
                if (data.activity) {
                    updateMainView(data.activity);
                }

            } else {
                updateAutoSaveStatus('danger', 'Errore durante il salvataggio');
                
                // Mostra il pulsante di salvataggio manuale
            const manualBtn = document.getElementById('manualSaveBtn');
            if (manualBtn) {
                manualBtn.style.display = 'inline-block';
            }
            }
        })
        .catch(error => {
            updateAutoSaveStatus('danger', 'Errore di connessione');
            
            // Mostra il pulsante di salvataggio manuale
        const manualBtn2 = document.getElementById('manualSaveBtn');
        if (manualBtn2) {
            manualBtn2.style.display = 'inline-block';
        }
        })
        .finally(() => {
            autoSaveInProgress = false;
        });
    }
    
    // Funzione per gestire i cambiamenti dei campi
    function handleFieldChange(fieldId, apiFieldName, value) {
        // Controlla se il valore è effettivamente cambiato
        if (lastSavedValues[apiFieldName] === value) {
            return; // Nessun cambiamento
        }
        
        // Cancella il timeout precedente
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }
        
        // Imposta nuovo timeout (debounce di 1.5 secondi)
        autoSaveTimeout = setTimeout(() => {
            performAutoSave(apiFieldName, value);
        }, 1500);
        
        // Mostra stato "in attesa di salvataggio"
        updateAutoSaveStatus('muted', 'Modifiche in attesa di salvataggio...');
    }
    
    // Inizializzazione dell'autosalvataggio quando il modal si apre
    if (editModal) {
    
        editModal.addEventListener('shown.bs.modal', function() {
            
            // Memorizza i valori iniziali
            Object.entries(fieldsToWatch).forEach(([fieldId, apiFieldName]) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    lastSavedValues[apiFieldName] = field.value;
            } else {
                }
            });
            
            // Aggiungi event listeners per ogni campo
            Object.entries(fieldsToWatch).forEach(([fieldId, apiFieldName]) => {
                const field = document.getElementById(fieldId);
                if (field) {
                
                    // Per i campi di testo, usa input event con debounce
                    if (field.type === 'text' || field.tagName === 'TEXTAREA') {
                        field.addEventListener('input', function() {
                            handleFieldChange(fieldId, apiFieldName, this.value);
                        });
                    }
                    
                    // Per le date e select, usa change event
                    if (field.type === 'date' || field.tagName === 'SELECT') {
                        field.addEventListener('change', function() {
                            handleFieldChange(fieldId, apiFieldName, this.value);
                        });
                    }
                    
            } else {
                }
            });
            
                                     // Stato iniziale - non mostrare alcun messaggio
            updateAutoSaveStatus('muted', '');
        });
        
        // Pulizia quando il modal si chiude
        editModal.addEventListener('hidden.bs.modal', function() {
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = null;
            }
            autoSaveInProgress = false;
            lastSavedValues = {};
        });
} else {
    }
    
    // Gestisci il pulsante di salvataggio manuale (fallback)
    const manualSaveBtn = document.getElementById('manualSaveBtn');
    if (manualSaveBtn) {
        manualSaveBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Ottieni i valori correnti di tutti i campi
            const allData = new FormData();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            allData.append('_token', csrfToken);
            allData.append('_method', 'PATCH');
            
            Object.entries(fieldsToWatch).forEach(([fieldId, apiFieldName]) => {
                const field = document.getElementById(fieldId);
                if (field && field.value) {
                    allData.append(apiFieldName, field.value);
                }
            });
            
            updateAutoSaveStatus('warning', 'Salvataggio manuale in corso...');
            
            fetch(`{{ route('board.activities.autosave', $activity) }}`, {
                method: 'POST',
                body: allData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAutoSaveStatus('success', `Salvato manualmente alle ${data.updated_at}`);
                    manualSaveBtn.style.display = 'none';
                    
                    // Aggiorna i valori salvati
                    Object.entries(fieldsToWatch).forEach(([fieldId, apiFieldName]) => {
                        const field = document.getElementById(fieldId);
                        if (field) {
                            lastSavedValues[apiFieldName] = field.value;
                        }
                    });
                } else {
                    updateAutoSaveStatus('danger', 'Errore nel salvataggio manuale');
                }
            })
            .catch(error => {
            updateAutoSaveStatus('danger', 'Errore di connessione durante salvataggio manuale');
            });
        });
    }
    
});

// Funzione per rimuovere un militare dalla vista dinamicamente
function removeMilitareFromView(militareId) {
    
    // Rimuovi dalla lista principale
    const militareElement = document.querySelector(`.list-group-item [data-militare-id="${militareId}"]`);
    if (militareElement) {
        militareElement.closest('.list-group-item').remove();
    }
    
    // Rimuovi anche dal modal di modifica se presente
    const modalMilitareElement = document.querySelector(`#militariGrid [data-militare-id="${militareId}"]`);
    if (modalMilitareElement) {
        modalMilitareElement.remove();
        
        // Aggiorna il counter nel modal
        if (typeof updateMilitariCounter === 'function') {
            updateMilitariCounter();
        }
    }
    
    // Per ora non riaggiungere automaticamente l'opzione ai select
    // L'utente può ricaricare la pagina se necessario, oppure
    // implementeremo un refresh dei select in futuro
    
    // Controlla se la lista è vuota e mostra il messaggio appropriato
    const listGroup = document.querySelector('.col-md-4 .list-group');
    if (listGroup && listGroup.children.length === 0) {
        const militariCard = document.querySelector('.col-md-4 .card-body');
        if (militariCard) {
            militariCard.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-users mb-3" style="font-size: 2rem;"></i>
                    <p class="mb-0">Nessun militare coinvolto</p>
                    <p class="small">Clicca su "Aggiungi" per assegnare militari</p>
                </div>
            `;
        }
    }
}

// Funzione per aggiungere un militare alla vista dinamicamente
function addMilitareToView(militare) {
    
    // Trova la lista dei militari nella card
    const militariCard = document.querySelector('.col-md-4 .card-body');
    if (!militariCard) {
        return;
    }
    
    // Controlla se c'è il messaggio "Nessun militare coinvolto"
    const noMilitariMessage = militariCard.querySelector('.text-center.py-4');
    if (noMilitariMessage) {
        // Rimuovi il messaggio e crea la lista
        noMilitariMessage.remove();
        militariCard.innerHTML = '<div class="list-group"></div>';
    }
    
    // Trova la lista group
    let listGroup = militariCard.querySelector('.list-group');
    if (!listGroup) {
        listGroup = document.createElement('div');
        listGroup.className = 'list-group';
        militariCard.appendChild(listGroup);
    }
    
    // Costruisci i badge per plotone e polo
    let badgesHtml = '';
    if (militare.plotone || militare.polo) {
        badgesHtml = '<div class="mt-1">';
        if (militare.plotone) {
            badgesHtml += `<span class="badge bg-light text-dark border me-1">${militare.plotone.nome}</span>`;
        }
        if (militare.polo) {
            badgesHtml += `<span class="badge bg-light text-dark border">${militare.polo.nome}</span>`;
        }
        badgesHtml += '</div>';
    }
    
    // Crea l'elemento del militare
        const militareElement = document.createElement('div');
        militareElement.className = 'list-group-item d-flex justify-content-between align-items-center';
        militareElement.innerHTML = `
            <div>
                <strong>${militare.grado.abbreviazione} ${militare.cognome}</strong>
                <br>
                <small>${militare.nome}</small>
            ${badgesHtml}
            </div>
            <div class="d-flex align-items-center">
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#removeMilitareModal"
                        data-militare-id="${militare.id}"
                    data-militare-name="${militare.grado.abbreviazione} ${militare.cognome}"
                    title="Rimuovi militare">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        `;
        
    // Aggiungi alla lista
    listGroup.appendChild(militareElement);
    
    // Rimuovi l'opzione dal select del modal "Aggiungi militare"
    const selectOption = document.querySelector(`#militare_id option[value="${militare.id}"]`);
    if (selectOption) {
        selectOption.remove();
    }
    
    // Aggiorna il select2 se presente
    if (jQuery && jQuery.fn.select2) {
        $('#militare_id').trigger('change');
    }
    
}

// Funzione per aggiornare la vista principale dopo l'autosave
function updateMainView(activity) {
    if (!activity) return;
    
    
    // Aggiorna il titolo nella card header
    const cardTitle = document.querySelector('.card-header h4');
    if (cardTitle && activity.title) {
        cardTitle.textContent = activity.title;
    }
    
    // Aggiorna la descrizione
    const descriptionParagraph = document.querySelector('.card-body .mb-4 p');
    if (descriptionParagraph && activity.description) {
        descriptionParagraph.textContent = activity.description;
    }
    
    // Aggiorna le date se presenti
    if (activity.start_date) {
        const startDateElement = document.querySelector('.col-md-6 p i.fa-calendar').parentElement;
        if (startDateElement) {
            const formattedDate = new Date(activity.start_date).toLocaleDateString('it-IT');
            startDateElement.innerHTML = `<i class="fas fa-calendar text-primary me-2"></i> ${formattedDate}`;
        }
    }
    
    if (activity.end_date) {
        const endDateElement = document.querySelector('.col-md-6 p i.fa-calendar-check').parentElement;
        if (endDateElement) {
            const formattedDate = new Date(activity.end_date).toLocaleDateString('it-IT');
            endDateElement.innerHTML = `<i class="fas fa-calendar-check text-primary me-2"></i> ${formattedDate}`;
        }
    }
    
    // Aggiorna il badge dello stato se la colonna è cambiata
    if (activity.column) {
        const statusBadge = document.getElementById('activity-status-badge');
        if (statusBadge) {
            // Mappa degli stili per colonna
            const statusStyles = {
                'urgenti': 'bg-danger text-white',
                'in-scadenza': 'bg-warning text-dark', 
                'pianificate': 'bg-success text-white',
                'fuori-porta': 'bg-info text-white'
            };
            
            const newClass = statusStyles[activity.column.slug] || 'bg-primary text-white';
            statusBadge.className = `badge ${newClass} fs-6 px-3 py-2`;
            statusBadge.innerHTML = `<i class="fas fa-tag me-2"></i>${activity.column.name}`;
        }
        
        // Aggiorna anche il badge nell'header della card
        const headerBadge = document.querySelector('.card-header .badge');
        if (headerBadge) {
            const headerStyles = {
                'urgenti': 'bg-danger',
                'in-scadenza': 'bg-warning',
                'pianificate': 'bg-success', 
                'fuori-porta': 'bg-info'
            };
            
            const newHeaderClass = headerStyles[activity.column.slug] || 'bg-primary';
            headerBadge.className = `badge ${newHeaderClass}`;
            headerBadge.textContent = activity.column.name;
        }
    }
}

// Funzione per rimuovere un militare
window.rimuoviMilitare = function(militareId) {
    
    // Controlla se il militare esiste nell'array
    if (!window.militariAttuali.includes(parseInt(militareId))) {
        return;
    }
    
    // Rimuovi dall'array
    window.militariAttuali = window.militariAttuali.filter(id => id != militareId);
    
    // Rimuovi dalla UI - cerca solo dentro la griglia del modal
    const militareElement = document.querySelector(`#militariGrid [data-militare-id="${militareId}"]`);
    if (militareElement) {
        militareElement.remove();

    
    // Aggiorna il counter
    updateMilitariCounter();
    
    // Aggiorna il messaggio se non ci sono più militari
    checkEmptyState();
    
    // Aggiorna l'input hidden
    updateHiddenInput();
    
    // Riaggiunge l'opzione al select dropdown dei nuovi militari
    riaggiungeMilitareAlSelect(militareId);
    
    // Aggiorna le opzioni disponibili se la sezione aggiunta è aperta
    const addSection = document.getElementById('addMilitareSection');
    if (addSection && addSection.style.display !== 'none') {
        aggiornaOpzioniDisponibili();
    }
    
    }
    
};


// Funzioni globali per gestire la sezione aggiunta militari
window.showAddMilitareSection = function() {
    const section = document.getElementById('addMilitareSection');
    if (section) {
        section.style.display = 'block';
    }
};

window.hideAddMilitareSection = function() {
    const section = document.getElementById('addMilitareSection');
    if (section) {
        section.style.display = 'none';
    }
};

// Funzioni globali necessarie per rimuoviMilitare
window.updateMilitariCounter = function() {
    const counter = document.getElementById('militariCounter');
    if (counter) {
        counter.textContent = window.militariAttuali.length;
    }
};

window.checkEmptyState = function() {
    const noMilitariMessage = document.getElementById('noMilitariMessage');
    const militariGrid = document.getElementById('militariGrid');
    
    if (window.militariAttuali.length === 0) {
        if (militariGrid) {
            militariGrid.style.display = 'none';
        }
        if (noMilitariMessage) {
            noMilitariMessage.style.display = 'block';
        }
    } else {
        if (militariGrid) {
            militariGrid.style.display = 'block';
        }
        if (noMilitariMessage) {
            noMilitariMessage.style.display = 'none';
        }
    }
};

window.updateHiddenInput = function() {
    const hiddenInputs = document.querySelectorAll('input[name="militari[]"]');
    hiddenInputs.forEach(input => input.remove());
    
    const form = document.getElementById('editActivityForm');
    if (form) {
        window.militariAttuali.forEach(militareId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'militari[]';
            input.value = militareId;
            form.appendChild(input);
        });
    }
};

window.riaggiungeMilitareAlSelect = function(militareId) {
    // Funzione placeholder - può essere implementata se necessario
};

window.aggiornaOpzioniDisponibili = function() {
    // Funzione placeholder - può essere implementata se necessario
};
</script>
@endpush
