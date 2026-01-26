@extends('layouts.app')

@section('title', 'Calendario Attivita')

@section('content')
<div class="container-fluid">
    <div class="page-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <h1 class="page-title mb-0">Calendario Attivita</h1>
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

    <!-- Barra di ricerca -->
    <div class="calendar-search-bar mb-4">
        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="search" id="calendarSearch" class="form-control" placeholder="Cerca attivita...">
        </div>
    </div>

    <!-- Legenda -->
    <div class="calendar-legend-bar mb-4">
        <span class="legend-item"><span class="legend-dot" style="background:#6c757d"></span>Servizi Isolati</span>
        <span class="legend-item"><span class="legend-dot" style="background:#28a745"></span>Cattedre</span>
        <span class="legend-item"><span class="legend-dot" style="background:#007bff"></span>Corsi</span>
        <span class="legend-item"><span class="legend-dot" style="background:#ffc107"></span>Esercitazioni</span>
        <span class="legend-item"><span class="legend-dot" style="background:#fd7e14"></span>Stand-by</span>
        <span class="legend-item"><span class="legend-dot" style="background:#dc3545"></span>Operazioni</span>
    </div>

    <!-- Calendario -->
    <div class="calendar-wrapper">
        <div id="calendar"></div>
        <div id="calendar-loading" class="calendar-loader d-none">
            <div class="spinner-border text-primary" role="status"></div>
            <p>Caricamento...</p>
        </div>
        <div id="calendar-error" class="alert alert-danger m-3 d-none">
            <i class="fas fa-exclamation-circle me-2"></i>
            <span>Errore durante il caricamento del calendario.</span>
        </div>
    </div>
</div>

<!-- Modal creazione attivita -->
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
                    </div>
                    <div class="mb-3">
                        <label for="title" class="form-label fw-bold">Titolo *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label fw-bold">Descrizione</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label fw-bold">Data Inizio *</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label fw-bold">Data Fine</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
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
                                $userCal = auth()->user();
                                $canViewAllMilitariCal = $userCal && ($userCal->isGlobalAdmin() || $userCal->hasPermission('board.view_all_militari') || $userCal->hasPermission('view_all_companies'));
                                $militariCalQuery = $canViewAllMilitariCal ? App\Models\Militare::withoutGlobalScopes() : App\Models\Militare::query();
                            @endphp
                            @foreach($militariCalQuery->with(['grado', 'plotone', 'polo'])->orderByGradoENome()->get() as $militare)
                            <option value="{{ $militare->id }}">
                                {{ optional($militare->grado)->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                @if($militare->plotone) - {{ $militare->plotone->nome }}@endif
                            </option>
                            @endforeach
                        </select>
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

<!-- Modal dettagli evento -->
<div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="eventTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
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
                        <h6 class="mb-0 fw-bold">Compagnia</h6>
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
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Chiudi</button>
                <a href="#" id="viewDetailsBtn" class="btn btn-primary"><i class="fas fa-eye me-1"></i> Dettagli</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link href="{{ asset('vendor/css/fullcalendar.min.css') }}" rel='stylesheet' />
<link href="{{ asset('vendor/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('vendor/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
<style>
/* Barra di ricerca */
.calendar-search-bar {
    display: flex;
    justify-content: center;
}
.search-input-wrapper {
    position: relative;
    width: 100%;
    max-width: 400px;
}
.search-input-wrapper .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 14px;
}
.search-input-wrapper .form-control {
    padding: 10px 16px 10px 42px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
}
.search-input-wrapper .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}

/* Legenda */
.calendar-legend-bar {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 16px 24px;
    padding: 12px 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}
.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #4b5563;
    font-weight: 500;
}
.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
}

/* Contenitore calendario */
.calendar-wrapper {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}
.calendar-loader {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px;
    color: #6b7280;
}
.calendar-loader p {
    margin-top: 12px;
    font-size: 14px;
}

/* FullCalendar */
#calendar {
    padding: 20px;
}
.fc .fc-toolbar {
    margin-bottom: 20px !important;
}
.fc .fc-toolbar-title {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    color: #111827 !important;
    text-transform: capitalize !important;
}
.fc .fc-button {
    background: #fff !important;
    border: 1px solid #d1d5db !important;
    color: #374151 !important;
    font-size: 13px !important;
    font-weight: 500 !important;
    padding: 6px 12px !important;
    border-radius: 6px !important;
    box-shadow: none !important;
}
.fc .fc-button:hover {
    background: #f3f4f6 !important;
}
.fc .fc-button:focus {
    box-shadow: 0 0 0 2px rgba(59,130,246,0.2) !important;
    outline: none !important;
}
.fc .fc-today-button {
    background: #2563eb !important;
    border-color: #2563eb !important;
    color: #fff !important;
}
.fc .fc-today-button:hover {
    background: #1d4ed8 !important;
}
.fc .fc-today-button:disabled {
    background: #93c5fd !important;
    border-color: #93c5fd !important;
    opacity: 1 !important;
}
.fc .fc-button-group {
    border-radius: 6px !important;
    overflow: hidden !important;
}
.fc .fc-button-group .fc-button {
    border-radius: 0 !important;
    margin: 0 !important;
}
.fc .fc-button-group .fc-button:first-child {
    border-top-left-radius: 6px !important;
    border-bottom-left-radius: 6px !important;
}
.fc .fc-button-group .fc-button:last-child {
    border-top-right-radius: 6px !important;
    border-bottom-right-radius: 6px !important;
}

/* Header giorni settimana */
.fc .fc-col-header-cell {
    background: #f9fafb !important;
    border: none !important;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 12px 0 !important;
}
.fc .fc-col-header-cell-cushion {
    font-size: 11px !important;
    font-weight: 600 !important;
    color: #6b7280 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    text-decoration: none !important;
}

/* Celle giorni */
.fc .fc-daygrid-day {
    border: 1px solid #f3f4f6 !important;
    background: #fff !important;
}
.fc .fc-daygrid-day:hover {
    background: #fafafa !important;
}
.fc .fc-daygrid-day-number {
    font-size: 13px !important;
    font-weight: 500 !important;
    color: #374151 !important;
    padding: 8px !important;
    text-decoration: none !important;
}
.fc .fc-daygrid-day.fc-day-today {
    background: #eff6ff !important;
}
.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
    background: #2563eb !important;
    color: #fff !important;
    border-radius: 6px !important;
    width: 28px !important;
    height: 28px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 4px !important;
}
.fc .fc-day-other .fc-daygrid-day-number {
    color: #9ca3af !important;
}

/* Eventi */
.fc-event {
    border: none !important;
    border-radius: 4px !important;
    padding: 4px 8px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    cursor: pointer !important;
    margin: 1px 2px !important;
    transition: transform 0.1s ease, box-shadow 0.1s ease !important;
}
.fc-event:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15) !important;
}
.fc-event-title {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
}
.fc-event-time {
    display: none !important;
}
.fc-event.event-saving { opacity: 0.7 !important; box-shadow: 0 0 0 2px #f59e0b !important; }
.fc-event.event-success { box-shadow: 0 0 0 2px #22c55e !important; }
.fc-event.event-error { box-shadow: 0 0 0 2px #ef4444 !important; }

/* Colori eventi */
.event-servizi-isolati { background: #6c757d !important; color: #fff !important; }
.event-cattedre { background: #28a745 !important; color: #fff !important; }
.event-corsi { background: #007bff !important; color: #fff !important; }
.event-esercitazioni { background: #ffc107 !important; color: #212529 !important; }
.event-stand-by { background: #fd7e14 !important; color: #fff !important; }
.event-operazioni { background: #dc3545 !important; color: #fff !important; }

.fc .fc-daygrid-more-link {
    font-size: 11px !important;
    font-weight: 500 !important;
    color: #2563eb !important;
}

/* Modal */
.event-militari-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    max-height: 150px;
    overflow-y: auto;
}
.militare-chip {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 4px 10px;
    font-size: 12px;
    color: #374151;
}

/* Responsive */
@media (max-width: 768px) {
    .calendar-legend-bar {
        gap: 8px 16px;
        padding: 10px 12px;
    }
    .legend-item {
        font-size: 11px;
    }
    #calendar {
        padding: 12px;
    }
    .fc .fc-toolbar {
        flex-direction: column;
        gap: 10px;
    }
    .fc .fc-toolbar-chunk {
        width: 100%;
        justify-content: center;
    }
    .fc .fc-toolbar-title {
        font-size: 1.1rem !important;
    }
    .search-input-wrapper {
        max-width: 100%;
    }
}
</style>
@endsection

@push('scripts')
<script src="{{ asset('vendor/js/fullcalendar.min.js') }}"></script>
<script src="{{ asset('vendor/js/fullcalendar-it.min.js') }}"></script>
<script src="{{ asset('vendor/js/select2.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let calendarInstance = null;

    function setEventStatus(el, status) {
        if (!el) return;
        el.classList.remove('event-saving', 'event-success', 'event-error');
        if (status) el.classList.add(status);
    }

    function applyFilters() {
        if (!calendarInstance) return;
        const term = (document.getElementById('calendarSearch')?.value || '').trim().toLowerCase();
        calendarInstance.getEvents().forEach(event => {
            const title = (event.title || '').toLowerCase();
            const desc = (event.extendedProps.description || '').toLowerCase();
            event.setProp('display', (!term || title.includes(term) || desc.includes(term)) ? 'auto' : 'none');
        });
    }

    function initSelect2() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
            $('.select2').select2({
                placeholder: 'Seleziona militari',
                width: '100%',
                theme: 'bootstrap-5',
                language: 'it',
                allowClear: true
            });
        }
    }

    function initCalendar() {
        const calendarEl = document.getElementById('calendar');
        const loading = document.getElementById('calendar-loading');
        const error = document.getElementById('calendar-error');
        
        if (!calendarEl || typeof FullCalendar === 'undefined') {
            if (loading) loading.classList.add('d-none');
            if (error) error.classList.remove('d-none');
            return;
        }

        const colors = {
            'servizi-isolati': { bg: '#6c757d', text: '#fff' },
            'cattedre': { bg: '#28a745', text: '#fff' },
            'corsi': { bg: '#007bff', text: '#fff' },
            'esercitazioni': { bg: '#ffc107', text: '#212529' },
            'stand-by': { bg: '#fd7e14', text: '#fff' },
            'operazioni': { bg: '#dc3545', text: '#fff' }
        };

        const events = [];
        @foreach($activities as $activity)
        events.push({
            id: {{ $activity->id }},
            title: @json($activity->title),
            start: '{{ $activity->start_date->format('Y-m-d') }}',
            end: '{{ $activity->end_date ? $activity->end_date->copy()->addDay()->format('Y-m-d') : $activity->start_date->format('Y-m-d') }}',
            allDay: true,
            className: 'event-{{ $activity->column->slug }}',
            backgroundColor: colors['{{ $activity->column->slug }}']?.bg || '#6c757d',
            textColor: colors['{{ $activity->column->slug }}']?.text || '#fff',
            description: @json($activity->description ?? ''),
            column: @json($activity->column->name),
            columnSlug: '{{ $activity->column->slug }}',
            compagniaMounting: @json(optional($activity->compagniaMounting)->nome ?? ''),
            militariCount: {{ $activity->militari_payload->count() }},
            militari: @json($activity->militari_payload),
            detailUrl: '{{ route('board.activities.show', $activity) }}'
        });
        @endforeach

        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            height: 'auto',
            initialView: 'dayGridMonth',
            locale: 'it',
            events: events,
            headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
            buttonText: { today: 'Oggi' },
            editable: true,
            dayMaxEvents: 3,
            eventDisplay: 'block',
            displayEventTime: false,
            
            eventDrop: function(info) {
                const el = info.el;
                setEventStatus(el, 'event-saving');
                
                const activityId = parseInt(info.event.id, 10);
                const start = info.event.start.toISOString().split('T')[0];
                let end = start;
                if (info.event.end) {
                    const d = new Date(info.event.end);
                    d.setDate(d.getDate() - 1);
                    end = d.toISOString().split('T')[0];
                }
                
                const url = '{{ route('board.activities.update-dates') }}';
                console.log('URL:', url);
                console.log('Salvando attivita:', { activity_id: activityId, start_date: start, end_date: end });
                
                fetch(url, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify({ activity_id: activityId, start_date: start, end_date: end })
                })
                .then(r => {
                    console.log('Response status:', r.status);
                    return r.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    setEventStatus(el, data.success ? 'event-success' : 'event-error');
                    setTimeout(() => setEventStatus(el, null), 1500);
                    if (!data.success) {
                        console.error('Errore salvataggio:', data.message);
                        setTimeout(() => info.revert(), 2000);
                    }
                })
                .catch(err => { 
                    console.error('Fetch error:', err);
                    setEventStatus(el, 'event-error'); 
                    setTimeout(() => info.revert(), 2000); 
                });
            },

            eventResize: function(info) {
                const el = info.el;
                setEventStatus(el, 'event-saving');
                
                const activityId = parseInt(info.event.id, 10);
                const start = info.event.start.toISOString().split('T')[0];
                let end = start;
                if (info.event.end) {
                    const d = new Date(info.event.end);
                    d.setDate(d.getDate() - 1);
                    end = d.toISOString().split('T')[0];
                }
                
                fetch('{{ route('board.activities.update-dates') }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json', 
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 
                        'Accept': 'application/json' 
                    },
                    body: JSON.stringify({ activity_id: activityId, start_date: start, end_date: end })
                })
                .then(r => r.json())
                .then(data => {
                    setEventStatus(el, data.success ? 'event-success' : 'event-error');
                    setTimeout(() => setEventStatus(el, null), 1500);
                    if (!data.success) setTimeout(() => info.revert(), 2000);
                })
                .catch(() => { 
                    setEventStatus(el, 'event-error'); 
                    setTimeout(() => info.revert(), 2000); 
                });
            },

            eventClick: function(info) {
                info.jsEvent.preventDefault();
                document.getElementById('eventTitle').textContent = info.event.title;
                document.getElementById('eventDescription').textContent = info.event.extendedProps.description || 'Nessuna descrizione';
                document.getElementById('eventCompagnia').textContent = info.event.extendedProps.compagniaMounting || 'Non specificata';
                
                const militari = info.event.extendedProps.militari || [];
                document.getElementById('eventMilitariCount').textContent = militari.length ? `${militari.length} militari` : 'Nessun militare';
                const list = document.getElementById('eventMilitariList');
                list.innerHTML = '';
                militari.forEach(m => {
                    const chip = document.createElement('span');
                    chip.className = 'militare-chip';
                    chip.textContent = m.nome || '';
                    list.appendChild(chip);
                });

                const start = new Date(info.event.start);
                let dateText = start.toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                if (info.event.end) {
                    const end = new Date(info.event.end);
                    end.setDate(end.getDate() - 1);
                    if (start.toDateString() !== end.toDateString()) {
                        dateText += ' - ' + end.toLocaleDateString('it-IT', { day: 'numeric', month: 'long' });
                    }
                }
                document.getElementById('eventDates').textContent = dateText;

                const status = document.getElementById('eventStatus');
                status.textContent = info.event.extendedProps.column;
                status.className = 'badge mt-1';
                const cls = { 'servizi-isolati': 'bg-secondary', 'cattedre': 'bg-success', 'corsi': 'bg-primary', 'esercitazioni': 'bg-warning text-dark', 'stand-by': 'bg-warning text-dark', 'operazioni': 'bg-danger' };
                (cls[info.event.extendedProps.columnSlug] || 'bg-secondary').split(' ').forEach(c => status.classList.add(c));

                document.getElementById('viewDetailsBtn').href = info.event.extendedProps.detailUrl;
                new bootstrap.Modal(document.getElementById('eventDetailsModal')).show();
            }
        });

        calendarInstance.render();
        if (loading) loading.classList.add('d-none');
    }

    // Ricerca
    const searchInput = document.getElementById('calendarSearch');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(applyFilters, 200);
        });
    }

    // Reset modal
    const createModal = document.getElementById('createActivityModal');
    if (createModal) {
        createModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('newActivityForm')?.reset();
            if (typeof jQuery !== 'undefined') $('#militari').val(null).trigger('change');
            const startField = document.getElementById('start_date');
            if (startField) startField.value = new Date().toISOString().split('T')[0];
        });
    }

    initSelect2();
    initCalendar();
    const startField = document.getElementById('start_date');
    if (startField) startField.value = new Date().toISOString().split('T')[0];
});
</script>
@endpush
