@extends('layouts.app')

@section('title', 'Calendario Attivita')

@section('content')
<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="page-title mb-1">Calendario Attivita</h1>
                <p class="text-muted mb-0">Panoramica operativa degli impegni e delle attivita programmate.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('board.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-columns"></i> Vista Board
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createActivityModal">
                    <i class="fas fa-plus"></i> Nuova Attivita
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-light py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0 fw-bold">
                    <i class="far fa-calendar-alt text-primary me-2"></i>Visualizzazione calendario
                </h5>
                <div class="calendar-legend d-flex gap-3 flex-wrap mt-2 mt-md-0">
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #6c757d; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Servizi Isolati</span>
                    </span>
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #28a745; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Cattedre</span>
                    </span>
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #007bff; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Corsi</span>
                    </span>
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #ffc107; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Esercitazioni</span>
                    </span>
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #fd7e14; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Stand-by</span>
                    </span>
                    <span class="d-flex align-items-center">
                        <div class="legend-dot me-2" style="background-color: #dc3545; width: 8px; height: 8px; border-radius: 50%; border: 1px solid rgba(27, 31, 36, 0.15);"></div>
                        <span style="font-size: 13px; color: #24292f; font-weight: 500;">Operazioni</span>
                    </span>
                </div>
            </div>
            <div class="calendar-filters mt-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <div class="input-group input-group-sm calendar-search">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="search" id="calendarSearch" class="form-control" placeholder="Cerca per titolo o descrizione">
                            <button class="btn btn-outline-secondary" type="button" id="calendarSearchClear" aria-label="Pulisci ricerca">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex flex-wrap justify-content-md-end align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="filterAssigned">
                            <label class="form-check-label" for="filterAssigned">Solo con militari</label>
                        </div>
                        <div class="small text-muted">
                            Mostrati <span id="calendarVisibleCount">0</span> / <span id="calendarTotalCount">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Container per il calendario con altezza fissa -->
            <div id="calendar-container" class="p-3">
                <div id="calendar"></div>
                <div id="calendar-loading" class="text-center p-5 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Caricamento...</span>
                    </div>
                    <p class="mt-2">Caricamento calendario...</p>
                </div>
                <div id="calendar-error" class="alert alert-danger m-3 d-none">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span>Errore durante il caricamento del calendario. Riprova piÃ¹ tardi.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal per la creazione di nuove attivita -->
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('board.activities.store') }}" method="POST" id="newActivityForm">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="createActivityModalLabel">
                        <i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attivita
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="compagnia_mounting_id" class="form-label fw-bold">Compagnia Organizzatrice *</label>
                        <select class="form-control" id="compagnia_mounting_id" name="compagnia_mounting_id" required>
                            <option value="">-- Seleziona compagnia --</option>
                            @foreach(App\Models\Compagnia::orderBy('nome')->get() as $compagnia)
                            <option value="{{ $compagnia->id }}">{{ $compagnia->nome }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Indica quale compagnia organizza l'attivita</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Titolo *</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="Inserisci il titolo dell'attivita" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Descrizione</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descrivi l'attivita (opzionale)"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label fw-bold">Data Inizio *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label fw-bold">Data Fine</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <small class="form-text text-muted">Se non specificata, sara considerata la stessa data di inizio</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="column_id" class="form-label fw-bold">Tipologia *</label>
                        <select class="form-control" id="column_id" name="column_id" required>
                            <option value="">-- Seleziona tipologia --</option>
                            @foreach(App\Models\BoardColumn::orderBy('order')->get() as $column)
                            <option value="{{ $column->id }}">{{ $column->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="militari" class="form-label fw-bold">Militari Coinvolti</label>
                        <select class="form-control select2" id="militari" name="militari[]" multiple>
                            @php
                                // Verifica se l'utente può vedere tutti i militari nella Board
                                $userCal = auth()->user();
                                $canViewAllMilitariCal = $userCal && (
                                    $userCal->isGlobalAdmin() || 
                                    $userCal->hasPermission('board.view_all_militari') ||
                                    $userCal->hasPermission('view_all_companies')
                                );
                                
                                $militariCalQuery = $canViewAllMilitariCal 
                                    ? App\Models\Militare::withoutGlobalScopes() 
                                    : App\Models\Militare::query();
                            @endphp
                            @foreach($militariCalQuery->with(['grado', 'plotone', 'polo'])->orderByGradoENome()->get() as $militare)
                            <option value="{{ $militare->id }}">
                                {{ optional($militare->grado)->abbreviazione ?? optional($militare->grado)->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                @if($militare->plotone) - {{ $militare->plotone->nome }}@endif @if($militare->polo), {{ $militare->polo->nome }}@endif
                            </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Seleziona uno o piu militari coinvolti nell'attivita</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal per i dettagli dell'evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="eventTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <i class="far fa-calendar-alt text-primary me-2 fs-5"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Periodo</h6>
                                <p id="eventDates" class="mb-0"></p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-tag text-primary me-2 fs-5"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Stato</h6>
                                <span id="eventStatus" class="badge mt-1"></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-flag text-primary me-2 fs-5"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Compagnia organizzatrice</h6>
                                <p id="eventCompagnia" class="mb-0 text-muted"></p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-3">
                            <i class="far fa-file-alt text-primary me-2 mt-1 fs-5"></i>
                            <div>
                                <h6 class="mb-1 fw-bold">Descrizione</h6>
                                <p id="eventDescription" class="mb-0 text-muted"></p>
                            </div>
                        </div>

                        <div class="d-flex mb-2">
                            <i class="fas fa-users text-primary me-2 mt-1 fs-5"></i>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">Militari coinvolti</h6>
                                <p id="eventMilitariCount" class="mb-2 text-muted"></p>
                                <div id="eventMilitariList" class="event-militari-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                <a href="#" id="viewDetailsBtn" class="btn btn-primary">
                    <i class="fas fa-eye me-1"></i> Dettagli completi
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Toast di notifica moderno -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050">
    <div id="calendarToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center p-2">
            <div class="toast-icon-container me-3 d-flex align-items-center justify-content-center">
                <i id="toastIcon" class="fas fa-info-circle fs-4"></i>
            </div>
            <div class="toast-content flex-grow-1">
                <strong class="d-block mb-1" id="toastTitle">Notifica</strong>
                <div id="toastMessage" class="text-wrap">Operazione completata con successo</div>
            </div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-progress-bar"></div>
    </div>
</div>
@endsection

@section('styles')
<!-- FullCalendar CSS (LOCALE) -->
<link href="{{ asset('vendor/css/fullcalendar.min.css') }}" rel='stylesheet' />
<link href="{{ asset('vendor/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('vendor/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
<style>
    .page-header .page-title {
        font-size: 1.75rem;
        font-weight: 600;
        color: #1f2328;
    }

    .page-header p {
        font-size: 0.95rem;
    }

    /* Stili migliorati per il calendario */
    #calendar-container {
        width: 100%;
        height: 700px;
        position: relative;
    }
    
    #calendar {
        width: 100%;
        height: 100%;
        font-family: 'Roboto', sans-serif;
    }

    .calendar-filters {
        border-top: 1px solid #eaeef2;
        padding-top: 0.75rem;
    }

    .calendar-search .form-control {
        border-left: 0;
    }

    .calendar-search .input-group-text {
        border-right: 0;
    }

    .calendar-search .btn {
        border-left: 0;
    }
    
    /* Stili delle celle del calendario piÃ¹ professionali */
    .fc .fc-daygrid-day {
        transition: all 0.15s ease-in-out;
        border: 1px solid #d0d7de !important;
        background: #ffffff !important;
        position: relative;
    }
    
    .fc .fc-daygrid-day:hover {
        background-color: #f6f8fa !important;
        border-color: #d0d7de !important;
    }
    
    .fc .fc-daygrid-day.fc-day-today {
        background-color: #f6f8fa !important;
        border: 1px solid #0969da !important;
        font-weight: 600;
        position: relative;
    }
    
    .fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        background: #0969da !important;
        color: #ffffff !important;
        border-radius: 50% !important;
        width: 24px !important;
        height: 24px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        margin: 4px !important;
    }
    
    /* Numeri dei giorni piÃ¹ leggibili */
    .fc .fc-daygrid-day-number {
        color: #24292f !important;
        font-weight: 500 !important;
        font-size: 13px !important;
        padding: 4px 6px !important;
    }
    
    /* Header dei giorni della settimana */
    .fc .fc-col-header-cell {
        background: #f6f8fa !important;
        border: 1px solid #d0d7de !important;
        font-weight: 600 !important;
        color: #656d76 !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }
    
    /* Feedback visivo piÃ¹ sobrio per drop zone */
    .fc .fc-daygrid-day.fc-drag-over {
        background: #f0f9ff !important;
        border: 1px solid #0969da !important;
        box-shadow: inset 0 0 0 1px rgba(9, 105, 218, 0.2) !important;
    }
    
    /* Miglioriamo anche il generale container del calendario */
    #calendar {
        border: 1px solid #d0d7de !important;
        border-radius: 6px !important;
        overflow: hidden !important;
        background: #ffffff !important;
    }
    

    
    .fc-event {
        cursor: grab !important;
        border-radius: 4px !important;
        box-shadow: 0 1px 3px rgba(27, 31, 36, 0.12) !important;
        padding: 4px 8px !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        transition: all 0.15s ease-in-out !important;
        margin: 1px 0 !important;
        position: relative !important;
        font-weight: 500 !important;
        font-size: 12px !important;
        line-height: 1.3 !important;
    }
    
    .fc-event:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 3px 8px rgba(27, 31, 36, 0.15) !important;
        z-index: 5 !important;
        border-color: rgba(255,255,255,0.2) !important;
    }
    
    /* Migliore leggibilitÃ  del testo negli eventi */
    .fc-event-title {
        font-weight: 500 !important;
        font-size: 12px !important;
        line-height: 1.3 !important;
        color: inherit !important;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1) !important;
    }
    
    /* Stili puliti per il drag */
    .fc-event-dragging {
        opacity: 0.8 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        z-index: 100 !important;
    }
    
    .fc-event-time {
        display: none;
    }
    
    .fc-toolbar-title {
        font-weight: 500 !important;
        text-transform: capitalize;
    }
    
    /* Riprogettazione completa dei pulsanti per stile istituzionale */
    .fc-button-primary {
        background: #ffffff !important;
        border: 1px solid #d0d7de !important;
        color: #24292f !important;
        border-radius: 6px !important;
        padding: 6px 12px !important;
        font-weight: 500 !important;
        font-size: 14px !important;
        line-height: 1.5 !important;
        transition: all 0.15s ease-in-out !important;
        box-shadow: 0 1px 0 rgba(27, 31, 36, 0.04) !important;
        text-shadow: none !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif !important;
    }
    
    .fc-button-primary:hover {
        background: #f6f8fa !important;
        border-color: #d0d7de !important;
        color: #24292f !important;
        transform: none !important;
        box-shadow: 0 1px 0 rgba(27, 31, 36, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.25) !important;
    }
    
    .fc-button-primary:active,
    .fc-button-primary:focus {
        background: #f6f8fa !important;
        border-color: #d0d7de !important;
        color: #24292f !important;
        box-shadow: inset 0 1px 0 rgba(27, 31, 36, 0.1) !important;
        outline: none !important;
    }
    
    .fc-button-primary:disabled {
        background: #f6f8fa !important;
        border-color: #d0d7de !important;
        color: #8c959f !important;
        opacity: 1 !important;
        cursor: not-allowed !important;
        box-shadow: 0 1px 0 rgba(27, 31, 36, 0.04) !important;
    }
    
    .fc-button-active {
        background: #0969da !important;
        border-color: #0969da !important;
        color: #ffffff !important;
        box-shadow: 0 1px 0 rgba(27, 31, 36, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.03) !important;
    }
    
    .fc-button-active:hover {
        background: #0860ca !important;
        border-color: #0860ca !important;
        color: #ffffff !important;
    }
    
    /* Toolbar styling piÃ¹ professionale */
    .fc-toolbar {
        margin-bottom: 1.5rem !important;
        padding: 0 !important;
    }
    
    .fc-toolbar-chunk {
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    
    /* Titolo del mese piÃ¹ istituzionale */
    .fc-toolbar-title {
        font-weight: 600 !important;
        font-size: 1.5rem !important;
        color: #24292f !important;
        margin: 0 1rem !important;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif !important;
    }
    
    /* Raggruppamento visivo dei pulsanti */
    .fc-button-group {
        border-radius: 6px !important;
        box-shadow: 0 1px 0 rgba(27, 31, 36, 0.04) !important;
        overflow: hidden !important;
    }
    
    .fc-button-group .fc-button {
        border-radius: 0 !important;
        border-right-width: 0 !important;
        margin: 0 !important;
    }
    
    .fc-button-group .fc-button:first-child {
        border-top-left-radius: 6px !important;
        border-bottom-left-radius: 6px !important;
    }
    
    .fc-button-group .fc-button:last-child {
        border-top-right-radius: 6px !important;
        border-bottom-right-radius: 6px !important;
        border-right-width: 1px !important;
    }
    
    /* Legend dots */
    .calendar-legend {
        font-size: 0.9rem;
    }
    
    /* Colori per le diverse colonne - corrispondono agli slug reali */
    .event-servizi-isolati {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
        color: white !important;
    }
    
    .event-cattedre {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: white !important;
    }
    
    .event-corsi {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }
    
    .event-esercitazioni {
        background-color: #ffc107 !important;
        border-color: #ffc107 !important;
        color: #212529 !important;
    }
    
    .event-stand-by {
        background-color: #fd7e14 !important;
        border-color: #fd7e14 !important;
        color: white !important;
    }
    
    .event-operazioni {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
    }
    
    /* Miglioramento accessibilitÃ  */
    .fc-event-title {
        font-weight: 500 !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Stili per il tooltip personalizzato */
    .event-tooltip {
        position: absolute;
        background: white;
        border: none;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        z-index: 1000;
        max-width: 300px;
        display: none;
        animation: tooltipFadeIn 0.2s ease-out;
        font-size: 0.9rem;
        color: #333;
    }

    .event-militari-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        max-height: 140px;
        overflow: auto;
        padding-right: 4px;
    }

    .militare-chip {
        background: #f6f8fa;
        border: 1px solid #d0d7de;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 0.78rem;
        color: #24292f;
        white-space: nowrap;
    }
    
    @keyframes tooltipFadeIn {
        from { opacity: 0; transform: translateY(5px); }
        to { opacity: 1; transform: translateY(0); }
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
    
    /* Toast styles rimossi - usando sistema globale */
    
    /* Responsive fixes */
    @media (max-width: 992px) {
        #calendar-container {
            height: 600px;
        }
        
        .fc-toolbar.fc-header-toolbar {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem !important;
        }
        
        .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        
        .calendar-legend {
            justify-content: center;
            margin-top: 1rem;
        }
        
        .fc-view-harness {
            height: 500px !important;
        }
    }
    
    @media (max-width: 576px) {
        #calendar-container {
            height: 500px;
            padding: 0.5rem !important;
        }
        
        .fc-toolbar-title {
            font-size: 1.2rem !important;
        }
        
        .fc-button {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.875rem !important;
        }
        
        .calendar-legend {
            flex-wrap: wrap;
            gap: 1rem !important;
            justify-content: space-around;
        }
    }
</style>
@endsection

@push('scripts')
<!-- FullCalendar (LOCALE) -->
<script src="{{ asset('vendor/js/fullcalendar.min.js') }}"></script>
<script src="{{ asset('vendor/js/fullcalendar-it.min.js') }}"></script>
<script src="{{ asset('vendor/js/select2.min.js') }}"></script>

<!-- Inizializzazione calendario in IIFE isolata -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    function showError(message) {
        showToast(`Errore: ${message}`, 'error');
    }
    
    function showSuccess(message) {
        showToast(`Operazione completata: ${message}`, 'success');
    }

    let calendarInstance = null;

    function updateVisibleCount() {
        if (!calendarInstance) return;
        const total = calendarInstance.getEvents().length;
        const visible = calendarInstance.getEvents().filter(event => event.display !== 'none').length;
        const totalEl = document.getElementById('calendarTotalCount');
        const visibleEl = document.getElementById('calendarVisibleCount');
        if (totalEl) totalEl.textContent = total;
        if (visibleEl) visibleEl.textContent = visible;
    }

    function applyFilters() {
        if (!calendarInstance) return;
        const searchInput = document.getElementById('calendarSearch');
        const onlyAssigned = document.getElementById('filterAssigned');
        const term = (searchInput?.value || '').trim().toLowerCase();
        const mustHaveMilitari = !!onlyAssigned?.checked;

        calendarInstance.getEvents().forEach(event => {
            const title = (event.title || '').toLowerCase();
            const description = (event.extendedProps.description || '').toLowerCase();
            const militariCount = event.extendedProps.militariCount || 0;

            const matchesTerm = !term || title.includes(term) || description.includes(term);
            const matchesMilitari = !mustHaveMilitari || militariCount > 0;
            const shouldShow = matchesTerm && matchesMilitari;

            event.setProp('display', shouldShow ? 'auto' : 'none');
        });

        updateVisibleCount();
    }

    function bindFilterEvents() {
        const searchInput = document.getElementById('calendarSearch');
        const clearButton = document.getElementById('calendarSearchClear');
        const onlyAssigned = document.getElementById('filterAssigned');
        let searchTimer = null;

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (searchTimer) clearTimeout(searchTimer);
                searchTimer = setTimeout(applyFilters, 150);
            });
        }

        if (onlyAssigned) {
            onlyAssigned.addEventListener('change', applyFilters);
        }

        if (clearButton && searchInput) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                applyFilters();
                searchInput.focus();
            });
        }
    }
    
    // Inizializza Select2
    function initSelect2() {
        try {
            if (jQuery && jQuery.fn.select2) {
                $('.select2').select2({
                    placeholder: 'Seleziona i militari coinvolti',
                    width: '100%',
                    theme: 'bootstrap-5',
                    language: 'it',
                    selectionCssClass: 'py-1',
                    dropdownCssClass: 'py-1',
                    allowClear: true
                });
                    }
        } catch (error) {
            // Errore silenzioso
        }
    }
    
    // Imposta data odierna per default nei campi data
    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        const startDateField = document.getElementById('start_date');
        if (startDateField) {
            startDateField.value = today;
        }
    }
    
    // Reset modal quando viene chiuso
    function setupModalReset() {
        const createModal = document.getElementById('createActivityModal');
        if (createModal) {
            createModal.addEventListener('hidden.bs.modal', function() {
                const form = document.getElementById('newActivityForm');
                if (form) form.reset();
                
                // Reset Select2
                try {
                    if (jQuery && jQuery.fn.select2) {
                        $('#militari').val(null).trigger('change');
                    }
                } catch (error) {
                    // Errore silenzioso
                }
                
                // Reset data inizio al giorno corrente
                setDefaultDates();
            });
        }
    }
    
    // Inizializzazione calendario
    function initCalendar() {
        // Elementi DOM
        const calendarEl = document.getElementById('calendar');
        const calendarLoading = document.getElementById('calendar-loading');
        const calendarError = document.getElementById('calendar-error');
        
        if (!calendarEl) {
            return;
        }
        
        // Mostra loader
        if (calendarLoading) calendarLoading.classList.remove('d-none');
        if (calendarError) calendarError.classList.add('d-none');
        
        // Controlla FullCalendar
        if (typeof FullCalendar === 'undefined') {
            if (calendarLoading) calendarLoading.classList.add('d-none');
            if (calendarError) calendarError.classList.remove('d-none');
            return;
        }
        
        // Prepara gli eventi
        const events = [];
        
        @foreach($activities as $activity)
        events.push({
            id: {{ $activity->id }},
            title: @json($activity->title),
            start: '{{ $activity->start_date->format('Y-m-d') }}',
            end: '{{ $activity->end_date ? $activity->end_date->copy()->addDay()->format('Y-m-d') : $activity->start_date->format('Y-m-d') }}',
            allDay: true,
            className: 'event-{{ $activity->column->slug }}',
            description: @json($activity->description ?? ''),
            column: @json($activity->column->name),
            columnSlug: '{{ $activity->column->slug }}',
            compagniaMounting: @json(optional($activity->compagniaMounting)->nome ?? ''),
            militariCount: {{ $activity->militari_payload->count() }},
            militari: @json($activity->militari_payload),
            detailUrl: '{{ route('board.activities.show', $activity) }}'
        });
        @endforeach
        
        try {
            // Inizializza calendario
            calendarInstance = new FullCalendar.Calendar(calendarEl, {
                height: '100%',
                initialView: 'dayGridMonth',
                locale: 'it',
                events: events,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                buttonText: {
                    today: 'Oggi',
                    month: 'Mese',
                    week: 'Settimana',
                    list: 'Lista'
                },
                editable: true,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                dayMaxEvents: true,
                
                // Configurazione semplice per migliorare il drag
                eventDisplay: 'block',
                displayEventTime: false,
                
                // Hook di caricamento
                loading: function(isLoading) {
                    if (calendarLoading) {
                        if (isLoading) {
                            calendarLoading.classList.remove('d-none');
                        } else {
                            calendarLoading.classList.add('d-none');
                        }
                    }
                },
                
                // Callback evento trascinato con animazione migliorata
                eventDrop: function(info) {
                    // Dati da inviare al server
                    const eventId = info.event.id;
                    const startDate = info.event.start.toISOString().split('T')[0];
                    
                    // Calcola la data di fine (sottraendo 1 giorno poichÃ© FullCalendar usa date inclusive)
                    let endDate = startDate; // Default Ã¨ la stessa data di inizio
                    if (info.event.end) {
                        const end = new Date(info.event.end);
                        end.setDate(end.getDate() - 1);
                        endDate = end.toISOString().split('T')[0];
                    }
                    
                    // Ottieni il token CSRF
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        console.error('Token CSRF mancante');
                        info.revert();
                        showError('Errore di sicurezza: token CSRF mancante');
                        return;
                    }
                    
                    // Chiamata fetch per aggiornare le date
                    fetch('{{ route('board.activities.update-dates') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            activity_id: eventId,
                            start_date: startDate,
                            end_date: endDate
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                console.error('Server error response:', text);
                                throw new Error(`Errore server: ${response.status}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Effetto di successo sull'evento
                            const eventEl = info.el;
                            if (eventEl) {
                                eventEl.style.transition = 'all 0.3s ease';
                                eventEl.style.boxShadow = '0 4px 20px rgba(40, 167, 69, 0.3)';
                                setTimeout(() => {
                                    eventEl.style.boxShadow = '';
                                    eventEl.style.transition = '';
                                }, 800);
                            }
                            showSuccess('Date aggiornate con successo');
                        } else {
                            throw new Error(data.message || 'Errore sconosciuto');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        info.revert();
                        showError(`Errore: ${error.message}`);
                    });
                },
                
                // Callback per il resize dell'evento
                eventResize: function(info) {
                    const eventId = info.event.id;
                    const startDate = info.event.start.toISOString().split('T')[0];
                    
                    let endDate = startDate;
                    if (info.event.end) {
                        const end = new Date(info.event.end);
                        end.setDate(end.getDate() - 1);
                        endDate = end.toISOString().split('T')[0];
                    }
                    
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!csrfToken) {
                        info.revert();
                        showError('Errore di sicurezza: token CSRF mancante');
                        return;
                    }
                    
                    fetch('{{ route('board.activities.update-dates') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            activity_id: eventId,
                            start_date: startDate,
                            end_date: endDate
                        })
                    })
                    .then(response => {
                        if (!response.ok) throw new Error(`Errore server: ${response.status}`);
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showSuccess('Date aggiornate con successo');
                        } else {
                            throw new Error(data.message || 'Errore sconosciuto');
                        }
                    })
                    .catch(error => {
                        info.revert();
                        showError(`Errore: ${error.message}`);
                    });
                },
                
                // Evento cliccato con animazione migliorata
                eventClick: function(info) {
                    // Previeni comportamento predefinito
                    info.jsEvent.preventDefault();
                    
                    // Effetto highlight sull'evento selezionato
                    const eventEl = info.el;
                    eventEl.style.transition = 'all 0.3s ease';
                    eventEl.style.boxShadow = '0 0 0 3px rgba(13, 110, 253, 0.5), 0 5px 10px rgba(0,0,0,0.15)';
                    eventEl.style.zIndex = '5';
                    
                    // Ripristina dopo un secondo
                    setTimeout(() => {
                        eventEl.style.boxShadow = '';
                        eventEl.style.zIndex = '';
                    }, 1000);
                    
                    // Popola i campi del modal
                    const modalTitle = document.getElementById('eventTitle');
                    const eventDates = document.getElementById('eventDates');
                    const eventDescription = document.getElementById('eventDescription');
                    const eventStatus = document.getElementById('eventStatus');
                    const eventCompagnia = document.getElementById('eventCompagnia');
                    const eventMilitariCount = document.getElementById('eventMilitariCount');
                    const eventMilitariList = document.getElementById('eventMilitariList');
                    const viewDetailsBtn = document.getElementById('viewDetailsBtn');
                    
                    if (modalTitle) modalTitle.textContent = info.event.title;
                    
                    if (eventDescription) {
                        eventDescription.textContent = info.event.extendedProps.description || 'Nessuna descrizione';
                    }

                    if (eventCompagnia) {
                        eventCompagnia.textContent = info.event.extendedProps.compagniaMounting || 'Non specificata';
                    }

                    if (eventMilitariCount || eventMilitariList) {
                        const militari = info.event.extendedProps.militari || [];
                        if (eventMilitariCount) {
                            eventMilitariCount.textContent = militari.length
                                ? `Totale militari: ${militari.length}`
                                : 'Nessun militare assegnato';
                        }

                        if (eventMilitariList) {
                            eventMilitariList.innerHTML = '';
                            if (!militari.length) {
                                const emptyEl = document.createElement('div');
                                emptyEl.className = 'text-muted small';
                                emptyEl.textContent = 'Nessun militare associato a questa attivita.';
                                eventMilitariList.appendChild(emptyEl);
                            } else {
                                militari.forEach(militare => {
                                    const chip = document.createElement('span');
                                    chip.className = 'militare-chip';
                                    let label = militare.nome || '';
                                    if (militare.compagnia) {
                                        label = `[${militare.compagnia}] ${label}`;
                                    }
                                    if (militare.plotone) {
                                        label += ` · ${militare.plotone}`;
                                    }
                                    chip.textContent = label.trim();
                                    eventMilitariList.appendChild(chip);
                                });
                            }
                        }
                    }
                    
                    // Formatta le date in modo elegante
                    if (eventDates) {
                        const dateStart = new Date(info.event.start);
                        const options = {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        };
                        
                        let dateText = dateStart.toLocaleDateString('it-IT', options);
                        dateText = dateText.charAt(0).toUpperCase() + dateText.slice(1); // Capitalizza
                        
                        if (info.event.end) {
                            const dateEnd = new Date(info.event.end);
                            dateEnd.setDate(dateEnd.getDate() - 1);
                            
                            // Se l'evento Ã¨ nello stesso giorno, mostra solo la data una volta
                            if (dateStart.toDateString() === dateEnd.toDateString()) {
                                // Non fare nulla, usa solo la data di inizio
                            } else {
                                // Se l'evento Ã¨ nello stesso mese, mostra solo il giorno finale
                                if (dateStart.getMonth() === dateEnd.getMonth() && 
                                    dateStart.getFullYear() === dateEnd.getFullYear()) {
                                    dateText += ` - ${dateEnd.getDate()} ${dateEnd.toLocaleDateString('it-IT', {month: 'long', year: 'numeric'})}`;
                                } else {
                                    // Altrimenti mostra la data completa
                                    dateText += ' - ' + dateEnd.toLocaleDateString('it-IT', options);
                                }
                            }
                        }
                        
                        eventDates.textContent = dateText;
                    }
                    
                    // Badge di stato con effetti moderni
                    if (eventStatus) {
                        eventStatus.textContent = info.event.extendedProps.column;
                        eventStatus.className = 'badge fs-6 mt-2';
                        
                        // Applica classe di colore appropriata - usa gli slug reali delle colonne
                        const slugToClass = {
                            'servizi-isolati': 'bg-secondary',
                            'cattedre': 'bg-success',
                            'corsi': 'bg-primary',
                            'esercitazioni': 'bg-warning text-dark',
                            'stand-by': 'bg-warning text-dark',
                            'operazioni': 'bg-danger',
                            'default': 'bg-secondary'
                        };
                        
                        const columnSlug = info.event.extendedProps.columnSlug;
                        const className = slugToClass[columnSlug] || slugToClass['default'];
                        className.split(' ').forEach(cls => eventStatus.classList.add(cls));
                        
                        // Aggiungi animazione di entrata
                        eventStatus.style.opacity = '0';
                        eventStatus.style.transform = 'scale(0.8)';
                        
                        setTimeout(() => {
                            eventStatus.style.transition = 'all 0.3s ease';
                            eventStatus.style.opacity = '1';
                            eventStatus.style.transform = 'scale(1)';
                        }, 100);
                    }
                    
                    // Link ai dettagli migliorato
                    if (viewDetailsBtn) {
                        viewDetailsBtn.href = info.event.extendedProps.detailUrl;
                        viewDetailsBtn.classList.add('btn-shine');
                        
                        // Rimuovi l'effetto dopo un po'
                        setTimeout(() => {
                            viewDetailsBtn.classList.remove('btn-shine');
                        }, 1000);
                    }
                    
                    // Mostra il modal con animazione migliorata
                    const eventModal = document.getElementById('eventDetailsModal');
                    if (eventModal) {
                        eventModal.classList.add('fade-scale');
                        const bsModal = new bootstrap.Modal(eventModal);
                        bsModal.show();
                        
                        // Rimuovi classe dopo apertura
                        eventModal.addEventListener('shown.bs.modal', function() {
                            eventModal.classList.remove('fade-scale');
                        }, { once: true });
                    }
                }
            });
            
            // Renderizza il calendario
            calendarInstance.render();
            
            // Nascondi loader
            if (calendarLoading) calendarLoading.classList.add('d-none');
            bindFilterEvents();
            applyFilters();
            

            
        } catch (error) {
            if (calendarLoading) calendarLoading.classList.add('d-none');
            if (calendarError) calendarError.classList.remove('d-none');
        }
    }
    
    // Usando sistema toast globale - nessuna funzione personalizzata necessaria

    // Inizializza tutto
    try {
        initSelect2();
        setDefaultDates();
        setupModalReset();
        initCalendar();
    } catch (error) {
        const calendarError = document.getElementById('calendar-error');
        const calendarLoading = document.getElementById('calendar-loading');
        if (calendarLoading) calendarLoading.classList.add('d-none');
        if (calendarError) {
            calendarError.classList.remove('d-none');
            calendarError.querySelector('span').textContent = 'Errore: ' + error.message;
        }
    }
});
</script>
@endpush

