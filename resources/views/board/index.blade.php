@extends('layouts.app')

@section('title', 'Hub Attività')

@section('content')
<div class="container-fluid">
    <!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Hub Attività</h1>
</div>
    
    <div class="text-end mb-3">
        <a href="{{ route('board.calendar') }}" class="btn btn-outline-primary me-2">
            <i class="fas fa-calendar"></i> Vista Calendario
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createActivityModal">
            <i class="fas fa-plus"></i> Nuova Attività
        </button>
    </div>

    <div id="board-container" class="board-container">
        <div class="row flex-nowrap board-row">
            @foreach($columns as $column)
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card board-column shadow-sm" data-column-id="{{ $column->id }}" data-column-slug="{{ $column->slug }}">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold column-title">
                            @php
                                $colors = ['urgenti'=>'text-danger','in-scadenza'=>'text-warning','pianificate'=>'text-success','fuori-porta'=>'text-info'];
                                $bg     = ['urgenti'=>'bg-danger','in-scadenza'=>'bg-warning','pianificate'=>'bg-success','fuori-porta'=>'bg-info'];
                                $icons  = ['urgenti'=>'fa-exclamation-circle','in-scadenza'=>'fa-clock','pianificate'=>'fa-check-circle','fuori-porta'=>'fa-map-marker-alt'];
                                $slug   = $column->slug;
                            @endphp
                            <i class="fas {{ $icons[$slug] ?? 'fa-list' }} {{ $colors[$slug] ?? 'text-primary' }} me-2"></i>{{ $column->name }}
                        </h5>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-secondary rounded-pill me-2">{{ $column->activities->count() }}</span>
                            <button class="btn btn-sm add-activity-btn {{ $bg[$slug] ?? 'bg-primary' }} text-white"
                                data-bs-toggle="modal" data-bs-target="#createActivityModal"
                                data-column-id="{{ $column->id }}" data-column-name="{{ $column->name }}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="activities-container">
                            @foreach($column->activities as $activity)
                            <div class="card mb-3 activity-card shadow-sm card-{{ $column->slug }}" id="activity-{{ $activity->id }}" data-activity-id="{{ $activity->id }}">
                                <div class="card-body d-flex flex-column p-3">
                                    <h6 class="card-title mb-2 text-truncate" title="{{ $activity->title }}">{{ $activity->title }}</h6>
                                    <div class="activity-dates mb-2 small text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>{{ $activity->start_date->format('d/m/Y') }}
                                        @if($activity->end_date) - {{ $activity->end_date->format('d/m/Y') }} @endif
                                    </div>
                                    <!-- Descrizione con più testo visibile -->
                                    <p class="card-text small mb-3" style="max-height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;" title="{{ $activity->description ?: 'Nessuna descrizione' }}">{{ $activity->description ?: 'Nessuna descrizione' }}</p>
                                    
                                    <!-- Militari coinvolti - Solo numero con possibilità di click -->
                                    @if($activity->militari->isNotEmpty())
                                    <div class="mb-2">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-users text-primary me-1"></i>
                                                <small class="text-muted fw-bold">Militari Coinvolti:</small>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#militariModal{{ $activity->id }}"
                                                    title="Visualizza tutti i militari">
                                                <i class="far fa-user me-1"></i>
                                                <strong>{{ $activity->militari->count() }}</strong>
                                            </button>
                                        </div>
                                    </div>
                                    @else
                                    <div class="mb-2">
                                        <div class="text-center py-1 text-muted">
                                            <i class="fas fa-user-slash me-1"></i>
                                            <span class="small">Nessun militare assegnato</span>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="mt-auto d-flex justify-content-between align-items-center">
                                        <a href="{{ route('board.activities.show', $activity) }}" class="btn btn-sm btn-outline-primary" title="Visualizza dettagli completi">
                                            <i class="fas fa-eye me-1"></i>Dettagli
                                        </a>
                                        <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

<!-- Modal per visualizzare militari di ogni attività -->
@foreach($columns as $column)
    @foreach($column->activities as $activity)
        @if($activity->militari->isNotEmpty())
        <div class="modal fade" id="militariModal{{ $activity->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title">
                            <i class="fas fa-users text-primary me-2"></i>
                            Militari Coinvolti - {{ $activity->title }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            @foreach($activity->militari as $militare)
                            <div class="col-md-6 mb-3">
                                <div class="card border-left-primary h-100">
                                    <div class="card-body py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1 fw-bold">{{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }}</h6>
                                                <p class="mb-1 text-muted small">{{ $militare->nome }}</p>
                                                @if($militare->plotone)
                                                    <span class="badge bg-light text-dark border me-1">{{ $militare->plotone->nome }}</span>
                                                @endif
                                                @if($militare->polo)
                                                    <span class="badge bg-light text-dark border">{{ $militare->polo->nome }}</span>
                                                @endif
                                            </div>
                                            <a href="{{ route('anagrafica.show', $militare) }}" class="btn btn-sm btn-outline-primary" title="Visualizza profilo militare">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                        <a href="{{ route('board.activities.show', $activity) }}" class="btn btn-primary">
                            <i class="fas fa-eye me-1"></i>Dettagli Attività
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endforeach

<!-- Modal per la creazione di nuove attività -->
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('board.activities.store') }}" method="POST" id="newActivityForm">
        @csrf
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="createActivityModalLabel">
            <i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attività
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="title" class="form-label fw-bold">Titolo *</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="Inserisci il titolo dell'attività" required>
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label fw-bold">Descrizione</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descrivi l'attività (opzionale)"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label fw-bold">Data Inizio *</label>
              <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label fw-bold">Data Fine</label>
              <input type="date" class="form-control" id="end_date" name="end_date">
              <small class="form-text text-muted">Se non specificata, sarà considerata la stessa data di inizio</small>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="column_id" class="form-label fw-bold">Stato *</label>
            <select class="form-control" id="column_id" name="column_id" required>
              <option value="">-- Seleziona stato --</option>
              @foreach(App\Models\BoardColumn::orderBy('order')->get() as $column)
              <option value="{{ $column->id }}">{{ $column->name }}</option>
              @endforeach
            </select>
          </div>
          
          <div class="mb-3">
            <label for="militari" class="form-label fw-bold">Militari Coinvolti</label>
            <select class="form-control select2" id="militari" name="militari[]" multiple>
              @foreach(App\Models\Militare::with(['grado', 'plotone', 'polo'])->orderByGradoENome()->get() as $militare)
              <option value="{{ $militare->id }}">
                {{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                @if($militare->plotone) - {{ $militare->plotone->nome }}@endif @if($militare->polo), {{ $militare->polo->nome }}@endif
              </option>
              @endforeach
            </select>
            <small class="form-text text-muted">Seleziona uno o più militari coinvolti nell'attività</small>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Salva</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast di notifica -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
  <div id="notificationToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex align-items-center p-2">
      <div class="toast-icon-container me-3 d-flex align-items-center justify-content-center">
        <i id="toastIcon" class="fas fa-info-circle fs-4"></i>
      </div>
      <div class="toast-content flex-grow-1">
        <strong id="toastTitle" class="d-block mb-1">Notifica</strong>
        <div id="toastMessage" class="text-wrap">Operazione completata con successo</div>
      </div>
      <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-progress-bar"></div>
  </div>
</div>
@endsection

@section('styles')
<style>
    /* Stile per il cursore di trascinamento su tutto il documento quando trascinamento è attivo */
    body.dragging-active {
        cursor: grabbing !important;
    }
    
    /* Stili board migliorati */
    .board-container {
        overflow-x: auto;
        padding-bottom: 1rem;
        scrollbar-width: thin;
        scrollbar-color: #ccc #f8f9fa;
    }
    
    .board-container::-webkit-scrollbar {
        height: 8px;
    }
    
    .board-container::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 10px;
    }
    
    .board-container::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 10px;
        border: 2px solid #f8f9fa;
    }
    
    .board-container::-webkit-scrollbar-thumb:hover {
        background-color: #999;
    }
    
    .board-row {
        min-height: calc(100vh - 250px);
    }
    
    .board-column {
        min-height: 100%;
        border: none;
        border-radius: 0.75rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        background-color: #fafafa;
    }
    
    .board-column:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .board-column .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0,0,0,0.07);
        border-radius: 0.75rem 0.75rem 0 0;
        padding: 1rem;
    }
    
    .board-column .card-body {
        flex: 1;
        overflow-y: auto;
        max-height: calc(100vh - 300px);
        padding: 1rem;
        scrollbar-width: thin;
        scrollbar-color: #ddd #f8f9fa;
    }
    
    .board-column .card-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .board-column .card-body::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .board-column .card-body::-webkit-scrollbar-thumb {
        background-color: #ddd;
        border-radius: 6px;
    }
    
    .board-column .card-body::-webkit-scrollbar-thumb:hover {
        background-color: #bbb;
    }
    
    .column-title {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    .add-activity-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .add-activity-btn:hover {
        transform: scale(1.15);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .activities-container {
        min-height: 200px;
        background-color: #fafafa;
        border-radius: 0 0 0.75rem 0.75rem;
        transition: all 0.3s ease;
        width: 100%;
        height: 100%;
        padding: 0.75rem;
    }
    
    .activity-card {
        width: 100%;
        cursor: grab;
        border: none;
        border-radius: 0.5rem;
        transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
        border-left: 4px solid;
        user-select: none;
        background-color: white;
        margin-bottom: 1rem;
        box-shadow: 0 3px 8px rgba(0,0,0,0.06);
        min-height: 170px;
        position: relative;
    }
    
    .activity-card .card-body {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 1rem;
    }
    
    /* Stili per la lista militari nelle card */
    .militari-list {
        max-height: 120px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #ddd transparent;
    }
    
    .militari-list::-webkit-scrollbar {
        width: 4px;
    }
    
    .militari-list::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .militari-list::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 2px;
    }
    
    .militari-list::-webkit-scrollbar-thumb:hover {
        background: #bbb;
    }
    
    /* Stile per le card dei militari nei modal */
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    
    .activity-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
    }
    
    .activity-card:active {
        cursor: grabbing;
    }
    
    /* Nascondi completamente il fallback standard */
    .sortable-hidden {
        opacity: 0 !important;
        position: absolute !important;
        top: -9999px !important;
        left: -9999px !important;
        pointer-events: none !important;
        height: 0 !important;
        width: 0 !important;
        overflow: hidden !important;
    }
    
    /* Elemento placeholder durante il trascinamento - Meno trasparente */
    .sortable-ghost {
        opacity: 0.4 !important;
        background-color: #f0f7ff !important;
        border: 2px dashed #4dabf7 !important;
        border-left: 4px solid #4dabf7 !important;
        border-radius: 0.5rem !important;
        box-shadow: none !important;
        transition: all 0.3s ease !important;
    }
    
    /* Stili per area di drop */
    .drop-zone-active {
        background-color: rgba(13, 110, 253, 0.05) !important;
        border-radius: 0.5rem !important;
    }
    
    .drop-zone-highlight {
        background-color: rgba(13, 110, 253, 0.12) !important;
        box-shadow: inset 0 0 0 2px rgba(13, 110, 253, 0.3) !important;
    }
    
    /* Migliorie stile per elemento durante il trascinamento - Movimento diretto senza ritardo */
    .sortable-drag-helper {
        will-change: transform;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2) !important;
        border-radius: 0.6rem !important;
        background-color: white !important;
        cursor: grabbing !important;
        position: fixed !important;
        z-index: 9999 !important;
        pointer-events: none !important;
        backface-visibility: hidden !important; /* Migliora le prestazioni di rendering */
        -webkit-font-smoothing: subpixel-antialiased !important;
        transform-style: preserve-3d !important;
    }
    
    /* Migliore visualizzazione dell'elemento trascinato - Meno trasparente */
    .activity-card.sortable-chosen {
        opacity: 0.4 !important; /* Aumentato da 0.1 a 0.4 per minore trasparenza */
        box-shadow: none !important;
        transform: none !important;
    }
    
    /* Sortable fallback classe */
    .sortable-fallback {
        display: none !important; /* Nascondiamo il fallback standard e usiamo il nostro custom */
    }
    
    .sortable-chosen {
        box-shadow: 0 0 15px rgba(0,0,0,0.25) !important;
    }
    
    /* Stili specifici per colonne - Uniformati con il calendario */
    .card-urgenti {
        border-left-color: #dc3545; /* Urgenti - Rosso */
    }
    
    .card-in-scadenza {
        border-left-color: #ffc107; /* In scadenza - Giallo */
    }
    
    .card-pianificate {
        border-left-color: #28a745; /* Pianificate - Verde */
    }
    
    .card-fuori-porta {
        border-left-color: #17a2b8; /* Fuori porta - Celeste */
    }
    
    /* Stili per drop zone attiva */
    .drop-zone-active {
        background-color: rgba(13, 110, 253, 0.08) !important;
        border: 2px dashed #0d6efd !important;
        border-radius: 0.5rem !important;
        transition: all 0.3s ease !important;
    }
    
    /* Drop zone highlight quando è la zona target corrente */
    .drop-zone-highlight {
        background-color: rgba(13, 110, 253, 0.15) !important;
        box-shadow: inset 0 0 10px rgba(13, 110, 253, 0.2) !important;
    }
    
    /* Card evidenziata dopo lo spostamento */
    .highlight-card {
        animation: highlightPulse 1s ease-in-out;
    }

    /* Animazioni migliorate */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes fadeOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(10px); }
    }
    
    @keyframes highlightPulse {
        0% { box-shadow: 0 0 0 rgba(13, 110, 253, 0); }
        50% { box-shadow: 0 0 15px rgba(13, 110, 253, 0.5); }
        100% { box-shadow: 0 0 0 rgba(13, 110, 253, 0); }
    }
    
    @keyframes pulse {
        0% { background-color: rgba(13, 110, 253, 0.05); }
        50% { background-color: rgba(13, 110, 253, 0.15); }
        100% { background-color: rgba(13, 110, 253, 0.05); }
    }
    
    @keyframes counterAnimation {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .counter-animation {
        animation: counterAnimation 0.5s ease-in-out;
    }
    
    /* Toast animazioni e stili migliorati */
    #notificationToast {
        transition: all 0.3s ease-in-out;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border-radius: 12px;
        border: none;
        overflow: hidden;
        width: 350px;
        max-width: 90vw;
    }
    
    .toast-icon-container {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255,255,255,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .toast-progress-bar {
        height: 3px;
        width: 100%;
        background-color: rgba(255,255,255,0.5);
        position: absolute;
        bottom: 0;
        left: 0;
    }
    
    @keyframes progress {
        from { width: 100%; }
        to { width: 0%; }
    }
    
    @keyframes slideIn {
        from { transform: translateX(50px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(50px); opacity: 0; }
    }
    
    /* Notifica semplice e moderna */
    #moveNotification {
        z-index: 1060;
    }
    
    .move-notification-content {
        background-color: rgba(40, 167, 69, 0.95);
        color: white;
        border-radius: 8px;
        padding: 15px 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        min-width: 250px;
        max-width: 400px;
        transition: all 0.3s ease;
        font-weight: 500;
        animation: fadeScale 0.3s ease-out;
    }
    
    .icon-container {
        font-size: 24px;
        color: white;
    }
    
    /* Animazioni */
    @keyframes notificationIn {
        0% { opacity: 0; transform: scale(0.8); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    @keyframes notificationOut {
        0% { opacity: 1; transform: scale(1); }
        100% { opacity: 0; transform: scale(0.8); }
    }
    
    @keyframes fadeScale {
        0% { opacity: 0; transform: scale(0.8); }
        50% { opacity: 1; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }
    
    .btn-shine {
        position: relative;
        overflow: hidden;
    }
    
    .btn-shine::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(
            to right,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, 0.3) 50%,
            rgba(255, 255, 255, 0) 100%
        );
        transform: rotate(30deg);
        animation: shine 1.5s infinite;
    }
    
    @keyframes shine {
        0% { transform: translateX(-100%) rotate(30deg); }
        100% { transform: translateX(100%) rotate(30deg); }
    }
    
    .fade-scale.modal.fade .modal-dialog {
        transform: scale(0.8);
        opacity: 0;
        transition: all 0.3s ease-in-out;
    }
    
    .fade-scale.modal.show .modal-dialog {
        transform: scale(1);
        opacity: 1;
    }
    
    .activity-card {
        animation: fadeIn 0.3s ease-out;
    }
    
    .empty-column-message {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        min-height: 170px;
        width: 100%;
        padding: 1.5rem;
        border-radius: 0.5rem;
        background-color: rgba(0,0,0,0.02);
        border: 1px dashed rgba(0,0,0,0.1);
        margin: 0.5rem 0;
    }
    
    /* Stili per il badge di stato spostamento */
    .badge.rounded-pill {
        padding: 0.5rem 0.75rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        animation: fadeIn 0.3s ease-out;
    }
    
    /* Stili responsivi migliorati */
    @media (max-width: 992px) {
        .board-row {
            flex-wrap: wrap;
        }
        
        .col-md-3 {
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .board-column {
            height: auto;
            min-height: 300px;
        }
        
        .board-column .card-body {
            max-height: 500px;
        }
    }
    
    @media (min-width: 993px) and (max-width: 1200px) {
        .col-md-3 {
            width: 50%;
            margin-bottom: 1rem;
        }
    }
</style>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    // init Select2
    if (window.jQuery && jQuery.fn.select2) {
        $('.select2').select2({ placeholder:'Seleziona i militari coinvolti', theme:'bootstrap-5', allowClear:true, width:'100%', language:'it' });
    }
    
    // pulsanti "Nuova Attività"
    document.querySelectorAll('.add-activity-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const { columnId, columnName } = e.currentTarget.dataset;
            const sel = document.querySelector('#column_id'); if (sel) sel.value = columnId;
            document.querySelector('#createActivityModal .modal-title').innerHTML =
                `<i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attività in "${columnName}"`;
        });
    });
    
    // reset modal
    const modal = document.getElementById('createActivityModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', () => {
            document.querySelector('#newActivityForm').reset();
            $('#militari').val(null).trigger('change');
            document.querySelector('#createActivityModal .modal-title').innerHTML =
                '<i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attività';
            document.querySelector('#start_date').value = new Date().toISOString().split('T')[0];
        });
    }
    
    // drag & drop
    document.querySelectorAll('.activities-container').forEach(container => {
        new Sortable(container, {
            group: 'board',
            animation: 50,
            easing: 'linear',
            delay: 100,
            delayOnTouchOnly: true,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            forceFallback: false,
            fallbackOnBody: false,
            onStart() {
                document.body.classList.add('dragging-active');
                document.querySelectorAll('.activities-container')
                    .forEach(c => c.classList.add('drop-zone-active'));
            },
            onMove(evt) {
                document.querySelectorAll('.activities-container')
                    .forEach(c => c.classList.remove('drop-zone-highlight'));
                evt.to && evt.to.classList.add('drop-zone-highlight');
                return true;
            },
            onEnd(evt) {
                document.body.classList.remove('dragging-active');
                document.querySelectorAll('.activities-container')
                    .forEach(c => c.classList.remove('drop-zone-active','drop-zone-highlight'));

                const from = evt.from, to = evt.to;
                if (from === to && evt.oldIndex === evt.newIndex) return;

                const item = evt.item;
                const actId = item.dataset.activityId;
                const colId = to.closest('.board-column').dataset.columnId;
                const slug  = to.closest('.board-column').dataset.columnSlug;

                // aggiorna la classe colore
                item.classList.forEach(cn => {
                    if (cn.startsWith('card-')) item.classList.remove(cn);
                });
                slug && item.classList.add(`card-${slug}`);

                // aggiorna badge e messaggi vuoti
                updateCount(from.closest('.board-column').dataset.columnId);
                updateCount(colId);
                checkEmpty(from);
                checkEmpty(to);

                // invia update
                fetch('{{ route("board.activities.position") }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        activity_id: actId,
                        column_id: colId,
                        order: evt.newIndex
                    })
                }).catch(() => location.reload());
            }
        });
    });

    function updateCount(id) {
        const col = document.querySelector(`.board-column[data-column-id="${id}"]`);
        if (!col) return;
        col.querySelector('.badge').textContent = col.querySelectorAll('.activity-card').length;
    }
    
    function checkEmpty(el) {
        const emptyMsg = el.querySelector('.empty-column-message');
        const hasCards = el.querySelectorAll('.activity-card').length > 0;
        if (!hasCards && !emptyMsg) {
            const d = document.createElement('div');
            d.className = 'empty-column-message text-center py-4 text-muted';
            d.innerHTML = `
                <i class="fas fa-inbox mb-2" style="font-size:1.5rem;"></i>
                <p class="mb-0">Nessuna attività in questa colonna</p>
                <p class="small">Trascina qui un'attività o creane una nuova</p>`;
            el.appendChild(d);
        } else if (hasCards && emptyMsg) {
            emptyMsg.remove();
        }
    }
    
    // Inizializza gli empty states
    document.querySelectorAll('.activities-container').forEach(checkEmpty);
});
</script>
@endpush
