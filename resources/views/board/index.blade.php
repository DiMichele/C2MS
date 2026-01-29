@extends('layouts.app')

@section('title', 'Hub Attività')

@section('content')
<div class="container-fluid">
    <!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="page-title mb-0">Hub Attività - Vista Battaglione</h1>
    
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('board.calendar') }}" class="btn btn-outline-primary">
            <i class="fas fa-calendar"></i> Vista Calendario
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createActivityModal">
            <i class="fas fa-plus"></i> Nuova Attività
        </button>
    </div>
</div>

    <div id="board-container" class="board-container">
        @php
            // Organizza le colonne su 2 righe
            $primaRiga = $columns->filter(function($col) {
                return in_array($col->slug, ['servizi-isolati', 'cattedre', 'corsi']);
            })->sortBy(function($col) {
                $order = ['servizi-isolati' => 1, 'cattedre' => 2, 'corsi' => 3];
                return $order[$col->slug] ?? 99;
            });
            
            $secondaRiga = $columns->filter(function($col) {
                return in_array($col->slug, ['esercitazioni', 'stand-by', 'operazioni']);
            })->sortBy(function($col) {
                $order = ['esercitazioni' => 1, 'stand-by' => 2, 'operazioni' => 3];
                return $order[$col->slug] ?? 99;
            });
        @endphp
        
        <!-- Prima Riga: Servizi Isolati, Cattedre, Corsi -->
        <div class="row board-row mb-4">
            @foreach($primaRiga as $column)
            <div class="col-xl-4 col-lg-4 col-md-4 mb-3">
                <div class="card board-column shadow-sm" data-column-id="{{ $column->id }}" data-column-slug="{{ $column->slug }}">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold column-title">
                            @php
                                $colors = [
                                    'servizi-isolati' => 'text-secondary',
                                    'esercitazioni' => 'text-warning',
                                    'stand-by' => 'text-warning',
                                    'operazioni' => 'text-danger',
                                    'corsi' => 'text-primary',
                                    'cattedre' => 'text-success'
                                ];
                                $bg = [
                                    'servizi-isolati' => 'bg-secondary',
                                    'esercitazioni' => 'bg-warning',
                                    'stand-by' => 'bg-warning',
                                    'operazioni' => 'bg-danger',
                                    'corsi' => 'bg-primary',
                                    'cattedre' => 'bg-success'
                                ];
                                $icons = [
                                    'servizi-isolati' => 'fa-shield-alt',
                                    'esercitazioni' => 'fa-running',
                                    'stand-by' => 'fa-pause-circle',
                                    'operazioni' => 'fa-bolt',
                                    'corsi' => 'fa-graduation-cap',
                                    'cattedre' => 'fa-chalkboard-teacher'
                                ];
                                $slug = $column->slug;
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
                            <div class="card mb-3 activity-card shadow-sm card-{{ $column->slug }}" id="activity-{{ $activity->id }}" data-activity-id="{{ $activity->id }}" style="position: relative;">
                                <!-- Pulsanti azione -->
                                <div class="activity-actions">
                                    <a href="{{ route('board.activities.show', $activity) }}" class="btn btn-sm btn-light" title="Visualizza dettagli" style="padding: 0.15rem 0.35rem; margin-right: 3px;">
                                        <i class="fas fa-eye" style="font-size: 0.75rem;"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $activity->id }}, event)" title="Elimina" style="padding: 0.15rem 0.35rem;">
                                        <i class="fas fa-trash" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                                
                                <div class="card-body d-flex flex-column p-3">
                                    @if($activity->compagniaMounting)
                                    <div class="mb-2">
                                        <span class="badge" style="background-color: {{ $activity->compagniaMounting->colore ?? '#6c757d' }}; font-size: 0.75rem;">
                                            <i class="fas fa-flag me-1"></i>{{ $activity->compagniaMounting->nome }}
                                        </span>
                                    </div>
                                    @endif
                                    <h6 class="card-title mb-2 text-truncate" title="{{ $activity->title }}">{{ $activity->title }}</h6>
                                    <div class="activity-dates mb-2 small text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>{{ $activity->start_date->format('d/m/Y') }}
                                        @if($activity->end_date) - {{ $activity->end_date->format('d/m/Y') }} @endif
                                    </div>
                                    <!-- Descrizione con più testo visibile -->
                                    <p class="card-text small mb-3" style="max-height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;" title="{{ $activity->description ?: 'Nessuna descrizione' }}">{{ $activity->description ?: 'Nessuna descrizione' }}</p>
                                    
                                    <!-- Militari coinvolti - Solo numero con possibilità  di click -->
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
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Seconda Riga: Esercitazioni, Stand-by, Operazioni -->
        <div class="row board-row">
            @foreach($secondaRiga as $column)
            <div class="col-xl-4 col-lg-4 col-md-4 mb-3">
                <div class="card board-column shadow-sm" data-column-id="{{ $column->id }}" data-column-slug="{{ $column->slug }}">
                    <div class="card-header d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold column-title">
                            @php
                                $colors = [
                                    'servizi-isolati' => 'text-secondary',
                                    'esercitazioni' => 'text-warning',
                                    'stand-by' => 'text-warning',
                                    'operazioni' => 'text-danger',
                                    'corsi' => 'text-primary',
                                    'cattedre' => 'text-success'
                                ];
                                $bg = [
                                    'servizi-isolati' => 'bg-secondary',
                                    'esercitazioni' => 'bg-warning',
                                    'stand-by' => 'bg-warning',
                                    'operazioni' => 'bg-danger',
                                    'corsi' => 'bg-primary',
                                    'cattedre' => 'bg-success'
                                ];
                                $icons = [
                                    'servizi-isolati' => 'fa-shield-alt',
                                    'esercitazioni' => 'fa-running',
                                    'stand-by' => 'fa-pause-circle',
                                    'operazioni' => 'fa-bolt',
                                    'corsi' => 'fa-graduation-cap',
                                    'cattedre' => 'fa-chalkboard-teacher'
                                ];
                                $slug = $column->slug;
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
                            <div class="card mb-3 activity-card shadow-sm card-{{ $column->slug }}" id="activity-{{ $activity->id }}" data-activity-id="{{ $activity->id }}" style="position: relative;">
                                <!-- Pulsanti azione -->
                                <div class="activity-actions">
                                    <a href="{{ route('board.activities.show', $activity) }}" class="btn btn-sm btn-light" title="Visualizza dettagli" style="padding: 0.15rem 0.35rem; margin-right: 3px;">
                                        <i class="fas fa-eye" style="font-size: 0.75rem;"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="openDeleteModal({{ $activity->id }}, event)" title="Elimina" style="padding: 0.15rem 0.35rem;">
                                        <i class="fas fa-trash" style="font-size: 0.75rem;"></i>
                                    </button>
                                </div>
                                
                                <div class="card-body d-flex flex-column p-3">
                                    @if($activity->compagniaMounting)
                                    <div class="mb-2">
                                        <span class="badge" style="background-color: {{ $activity->compagniaMounting->colore ?? '#6c757d' }}; font-size: 0.75rem;">
                                            <i class="fas fa-flag me-1"></i>{{ $activity->compagniaMounting->nome }}
                                        </span>
                                    </div>
                                    @endif
                                    <h6 class="card-title mb-2 text-truncate" title="{{ $activity->title }}">{{ $activity->title }}</h6>
                                    <div class="activity-dates mb-2 small text-muted">
                                        <i class="far fa-calendar-alt me-1"></i>{{ $activity->start_date->format('d/m/Y') }}
                                        @if($activity->end_date) - {{ $activity->end_date->format('d/m/Y') }} @endif
                                    </div>
                                    <p class="card-text small mb-3" style="max-height: 60px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;" title="{{ $activity->description ?: 'Nessuna descrizione' }}">{{ $activity->description ?: 'Nessuna descrizione' }}</p>
                                    
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

<!-- Modal per visualizzare militari di ogni attività  -->
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
                        @php
                            // Ordina e raggruppa militari per compagnia
                            $militariOrdinati = $activity->militari->sortBy(function($m) {
                                $compagniaOrdine = 999;
                                if ($m->compagnia) {
                                    if ($m->compagnia->nome == '110') $compagniaOrdine = 1;
                                    elseif ($m->compagnia->nome == '124') $compagniaOrdine = 2;
                                    elseif ($m->compagnia->nome == '127') $compagniaOrdine = 3;
                                }
                                $gradoOrdine = -1 * (optional($m->grado)->ordine ?? 0);
                                return [$compagniaOrdine, $gradoOrdine, $m->cognome, $m->nome];
                            })->groupBy(function($m) {
                                return $m->compagnia ? $m->compagnia->nome : 'Senza Compagnia';
                            });
                        @endphp
                        
                        @foreach($militariOrdinati as $compagniaNome => $militari)
                            <div class="mb-3">
                                <div class="px-3 py-2 bg-light border-bottom">
                                    <strong class="text-uppercase" style="font-size: 0.85rem; color: #0A2342; letter-spacing: 0.5px;">
                                        {{ $compagniaNome }} ({{ $militari->count() }})
                                    </strong>
                                </div>
                                <div class="list-group list-group-flush">
                                    @foreach($militari as $militare)
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <div class="flex-grow-1">
                                                <strong class="d-block">{{ optional($militare->grado)->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</strong>
                                                <small class="text-muted">
                                                    @if($militare->plotone){{ $militare->plotone->nome }}@endif
                                                </small>
                                            </div>
                                            <a href="{{ route('anagrafica.show', $militare) }}" class="btn btn-sm btn-outline-primary" title="Visualizza profilo">
                                                <i class="fas fa-user"></i>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
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

<!-- Modal per la creazione di nuove attività  -->
<div class="modal fade" id="createActivityModal" tabindex="-1" aria-labelledby="createActivityModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('board.activities.store') }}" method="POST" id="newActivityForm">
        @csrf
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="createActivityModalLabel">
            <i class="fas fa-plus-circle text-primary me-2"></i>Crea Nuova Attività 
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="compagnia_mounting_id" class="form-label fw-bold">Compagnia Mounting (Organizzatrice) *</label>
            <select class="form-control" id="compagnia_mounting_id" name="compagnia_mounting_id" required>
              <option value="">-- Seleziona compagnia --</option>
              @foreach($compagnie as $comp)
              <option value="{{ $comp->id }}">
                {{ $comp->nome }}
              </option>
              @endforeach
            </select>
            <small class="form-text text-muted">Indica quale compagnia organizza/monta l'attività </small>
          </div>

          <div class="mb-3">
            <label for="title" class="form-label fw-bold">Titolo *</label>
            <input type="text" class="form-control" id="title" name="title" placeholder="Inserisci il titolo dell'attività " required>
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label fw-bold">Descrizione</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Descrivi l'attività  (opzionale)"></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="start_date" class="form-label fw-bold">Data Inizio *</label>
              <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="end_date" class="form-label fw-bold">Data Fine</label>
              <input type="date" class="form-control" id="end_date" name="end_date">
              <small class="form-text text-muted">Se non specificata, sarà  considerata la stessa data di inizio</small>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="column_id" class="form-label fw-bold">Tipologia *</label>
            <select class="form-control" id="column_id" name="column_id" required>
              <option value="">-- Seleziona tipologia --</option>
              @foreach(App\Models\BoardColumn::orderBy('order')->get() as $column)
              <option value="{{ $column->id }}" data-slug="{{ $column->slug }}">{{ $column->name }}</option>
              @endforeach
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label fw-bold">Militari Coinvolti</label>
            
            <!-- Info disponibilità -->
            <div id="disponibilita-info" class="alert alert-info mb-2 py-2 d-none">
              <i class="fas fa-info-circle me-1"></i>
              <small>I militari sono colorati in base alla disponibilità per la data selezionata: 
                <span class="badge bg-success">Disponibile</span> 
                <span class="badge bg-warning text-dark">Non disponibile</span>
              </small>
            </div>
            
            <div id="availability-warnings" class="alert alert-warning d-none mb-2" role="alert">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>Attenzione - Conflitti rilevati:</strong>
              <ul id="conflict-list" class="mb-0 mt-2"></ul>
            </div>
            
            <!-- Custom Militari Selector -->
            <div class="militari-selector-wrapper">
              <!-- Barra di ricerca in alto -->
              <div class="militari-search-section">
                <input type="text" id="militari-search" class="militari-search-input" placeholder="Cerca militare per nome, cognome o grado...">
              </div>
              
              <!-- Badge militari selezionati -->
              <div class="militari-selected-section empty" id="militari-selected">
                <span class="empty-message">Nessun militare selezionato</span>
              </div>
              
              <!-- Lista militari disponibili -->
              <div class="militari-list-section" id="militari-list">
                @php
                    // Verifica se l'utente può vedere tutti i militari nella Board
                    $user = auth()->user();
                    $canViewAllMilitari = $user && (
                        $user->isGlobalAdmin() || 
                        $user->hasPermission('board.view_all_militari') ||
                        $user->hasPermission('view_all_companies')
                    );
                    
                    // Query con ordinamento corretto: prima compagnia (110, 124, 127), poi grado decrescente
                    $militariQuery = $canViewAllMilitari 
                        ? App\Models\Militare::withoutGlobalScopes() 
                        : App\Models\Militare::query();
                    
                    $militari = $militariQuery->with(['grado', 'compagnia'])
                        ->leftJoin('compagnie', 'militari.compagnia_id', '=', 'compagnie.id')
                        ->leftJoin('gradi', 'militari.grado_id', '=', 'gradi.id')
                        ->orderByRaw("CASE 
                            WHEN compagnie.nome = '110' THEN 1
                            WHEN compagnie.nome = '124' THEN 2
                            WHEN compagnie.nome = '127' THEN 3
                            ELSE 999 END")
                        ->orderBy('gradi.ordine', 'desc')
                        ->orderBy('militari.cognome')
                        ->orderBy('militari.nome')
                        ->select('militari.*')
                        ->get()
                        ->groupBy('compagnia.nome');
                @endphp
                @foreach($militari as $compagniaNome => $militariCompagnia)
                  <div class="militari-group">
                    <div class="militari-group-header">{{ $compagniaNome }}</div>
                    @foreach($militariCompagnia as $militare)
                      <div class="militare-item" data-id="{{ $militare->id }}" data-nome="{{ optional($militare->grado)->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}">
                        <i class="fas fa-user militare-item-icon"></i>
                        <span class="militare-item-name">{{ optional($militare->grado)->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</span>
                        <i class="fas fa-check militare-item-check" style="display: none;"></i>
                      </div>
                    @endforeach
                  </div>
                @endforeach
              </div>
              
              <!-- Counter -->
              <div class="militari-counter">
                <span id="militari-count">0</span> militari selezionati
              </div>
            </div>
            
            <!-- Hidden input per il form -->
            <input type="hidden" name="militari[]" id="militari-hidden">
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
    
    /* Pulsanti azione sulla card */
    .activity-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        z-index: 10;
        opacity: 0;
        transition: opacity 0.2s ease;
        display: flex;
        gap: 2px;
    }
    
    .activity-card:hover .activity-actions {
        opacity: 1;
    }
    
    .activity-actions .btn {
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
    
    /* Stili specifici per colonne */
    .card-servizi-isolati {
        border-left-color: #6c757d; /* Servizi Isolati - Grigio */
    }
    
    .card-esercitazioni {
        border-left-color: #fd7e14; /* Esercitazioni - Arancione */
    }
    
    .card-stand-by {
        border-left-color: #ffc107; /* Stand-by - Giallo */
    }
    
    .card-operazioni {
        border-left-color: #dc3545; /* Operazioni - Rosso */
    }
    
    .card-corsi {
        border-left-color: #0d6efd; /* Corsi - Blu */
    }
    
    .card-cattedre {
        border-left-color: #198754; /* Cattedre - Verde */
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
    
    /* ========================================
       SELECT2 MINIMAL SUGECO - Stile pulito e professionale
       ======================================== */
    
    /* Contenitore principale - Layout a due sezioni */
    .select2-container--bootstrap-5 .select2-selection--multiple {
        min-height: 180px !important;
        padding: 0 !important;
        border: 1px solid #E2E8F0 !important;
        border-radius: 8px !important;
        background: white !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
        transition: all 0.2s ease !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple:hover {
        border-color: #0A2342 !important;
        box-shadow: 0 2px 4px rgba(10, 35, 66, 0.08) !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple:focus-within {
        border-color: #0A2342 !important;
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1) !important;
    }
    
    /* Contenitore interno - Flexbox verticale */
    .select2-container--bootstrap-5 .select2-selection__rendered {
        display: flex !important;
        flex-direction: column !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    /* BARRA DI RICERCA - Sempre in alto, ben visibile */
    .select2-container--bootstrap-5 .select2-search--inline {
        order: 1 !important;
        width: 100% !important;
        padding: 15px !important;
        background: #F4F2ED !important;
        border-bottom: 2px solid #E2E8F0 !important;
        flex-shrink: 0 !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field {
        width: 100% !important;
        height: 48px !important;
        padding: 12px 18px !important;
        font-size: 1rem !important;
        margin: 0 !important;
        border: 2px solid #CBD5E0 !important;
        border-radius: 6px !important;
        background-color: white !important;
        color: #0A2342 !important;
        font-family: 'Roboto', sans-serif !important;
        transition: all 0.2s ease !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field:focus {
        outline: none !important;
        border-color: #0A2342 !important;
        background-color: white !important;
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.08) !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--inline .select2-search__field::placeholder {
        color: #718096 !important;
        font-weight: 400 !important;
    }
    
    /* Area militari selezionati - Sotto la barra di ricerca */
    .select2-selection__rendered-choices {
        order: 2 !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 8px !important;
        padding: 15px !important;
        min-height: 100px !important;
        max-height: 280px !important;
        overflow-y: auto !important;
        background: white !important;
    }
    
    /* Badge militari - Stile minimal SUGECO */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        order: 2 !important;
        background: #0A2342 !important;
        border: none !important;
        color: white !important;
        padding: 8px 12px !important;
        margin: 0 !important;
        border-radius: 4px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        font-family: 'Roboto', sans-serif !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        transition: all 0.2s ease !important;
        box-shadow: 0 1px 2px rgba(10, 35, 66, 0.1) !important;
        max-width: 100% !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice:hover {
        background: #0F3A6D !important;
        box-shadow: 0 2px 4px rgba(10, 35, 66, 0.15) !important;
    }
    
    /* Bottone rimozione - Minimal */
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove {
        color: rgba(255, 255, 255, 0.8) !important;
        margin-right: 0 !important;
        font-weight: normal !important;
        font-size: 1.2rem !important;
        line-height: 1 !important;
        transition: all 0.2s ease !important;
        opacity: 0.7 !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove:hover {
        opacity: 1 !important;
        color: #BF9D5E !important;
    }
    
    /* Dropdown - Stile SUGECO */
    .select2-container--bootstrap-5 .select2-dropdown {
        border: 1px solid #0A2342 !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 12px rgba(10, 35, 66, 0.15) !important;
        background: white !important;
        margin-top: 4px !important;
        overflow: hidden !important;
    }
    
    /* Opzioni nel dropdown - Minimal */
    .select2-container--bootstrap-5 .select2-results__option {
        padding: 12px 16px !important;
        font-size: 0.9rem !important;
        color: #2D3748 !important;
        transition: all 0.15s ease !important;
        border-left: 3px solid transparent !important;
    }
    
    .select2-container--bootstrap-5 .select2-results__option--highlighted {
        background: #F4F2ED !important;
        color: #0A2342 !important;
        border-left-color: #BF9D5E !important;
    }
    
    /* Gruppi compagnie - Header minimal */
    .select2-container--bootstrap-5 .select2-results__group {
        font-weight: 600 !important;
        color: #0A2342 !important;
        padding: 10px 16px 6px !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 1px !important;
        background: #F7FAFC !important;
        border-bottom: 1px solid #E2E8F0 !important;
        font-family: 'Oswald', sans-serif !important;
    }
    
    /* Scrollbar minimal */
    .select2-selection__rendered-choices::-webkit-scrollbar,
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar {
        width: 6px !important;
    }
    
    .select2-selection__rendered-choices::-webkit-scrollbar-track,
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-track {
        background: #F7FAFC !important;
    }
    
    .select2-selection__rendered-choices::-webkit-scrollbar-thumb,
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-thumb {
        background: #CBD5E0 !important;
        border-radius: 3px !important;
    }
    
    .select2-selection__rendered-choices::-webkit-scrollbar-thumb:hover,
    .select2-container--bootstrap-5 .select2-selection--multiple::-webkit-scrollbar-thumb:hover {
        background: #A0AEC0 !important;
    }
    
    /* Risultati nel dropdown - Icona minimal */
    .select2-result-militare {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .select2-result-militare i {
        color: #718096 !important;
        font-size: 0.9rem !important;
    }
    
    .select2-result-militare .militare-name {
        font-weight: 400 !important;
    }
    
    /* Messaggi */
    .select2-container--bootstrap-5 .select2-results__message {
        padding: 20px !important;
        text-align: center !important;
        color: #718096 !important;
        font-size: 0.9rem !important;
    }
    
    /* Search dropdown */
    .select2-container--bootstrap-5 .select2-search--dropdown {
        padding: 12px !important;
        background: #F7FAFC !important;
        border-bottom: 1px solid #E2E8F0 !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
        border: 1px solid #CBD5E0 !important;
        padding: 10px 14px !important;
        font-size: 0.95rem !important;
        border-radius: 6px !important;
    }
    
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
        border-color: #0A2342 !important;
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.08) !important;
        outline: none !important;
    }
    
    /* Alert conflitti professionale */
    #availability-warnings {
        border-left: 4px solid #ffc107 !important;
        background-color: #fff3cd !important;
        border-radius: 0.375rem !important;
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Fix per problemi di opacità  globale */
    body:not(.cert-modal-open):not(.modal-open) {
        opacity: 1 !important;
        filter: none !important;
    }
    
    /* Assicura che il main-content non abbia opacità  ridotta */
    .main-content {
        opacity: 1 !important;
    }
    
    /* Rimuovi opacità  da elementi che non dovrebbero averla */
    .board-container,
    .board-column,
    .activity-card:not(.sortable-chosen):not(.sortable-ghost) {
        opacity: 1 !important;
    }
</style>
<link href="{{ asset('vendor/css/select2.min.css') }}" rel="stylesheet" />
<link href="{{ asset('vendor/css/select2-bootstrap-5-theme.min.css') }}" rel="stylesheet" />
@endsection

@push('scripts')
<script src="{{ asset('vendor/js/sortable.min.js') }}"></script>
<script src="{{ asset('vendor/js/select2.min.js') }}"></script>
<script>
// Controllo disponibilità militari - sistema automatico batch
let conflittiDisponibilita = {};
let disponibilitaMilitari = {}; // Cache della disponibilità per data corrente

/**
 * Carica la disponibilità di tutti i militari per una data usando endpoint batch
 */
async function caricaDisponibilitaMilitari(data) {
    if (!data) return;
    
    try {
        const response = await fetch('{{ route("servizi.turni.militari-disponibilita") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ data: data })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Crea mappa di disponibilità
            disponibilitaMilitari = {};
            
            // Militari disponibili
            result.disponibili.forEach(m => {
                disponibilitaMilitari[m.id] = { disponibile: true };
            });
            
            // Militari non disponibili
            result.non_disponibili.forEach(m => {
                disponibilitaMilitari[m.id] = { 
                    disponibile: false, 
                    motivo: m.motivo 
                };
            });
            
            // Aggiorna visual nella lista
            aggiornaVisualDisponibilita();
            
            // Mostra info
            document.getElementById('disponibilita-info').classList.remove('d-none');
        }
    } catch (error) {
        console.error('Errore caricamento disponibilità:', error);
    }
}

/**
 * Aggiorna l'aspetto visivo dei militari nella lista in base alla disponibilità
 */
function aggiornaVisualDisponibilita() {
    const listContainer = document.getElementById('militari-list');
    if (!listContainer) return;
    
    listContainer.querySelectorAll('.militare-item').forEach(item => {
        const id = item.dataset.id;
        const disponibilita = disponibilitaMilitari[id];
        
        // Rimuovi classi precedenti
        item.classList.remove('militare-disponibile', 'militare-non-disponibile');
        
        // Rimuovi badge esistente
        const oldBadge = item.querySelector('.disponibilita-badge');
        if (oldBadge) oldBadge.remove();
        
        if (disponibilita) {
            if (disponibilita.disponibile) {
                item.classList.add('militare-disponibile');
            } else {
                item.classList.add('militare-non-disponibile');
                // Aggiungi tooltip con motivo
                item.title = disponibilita.motivo || 'Non disponibile';
                
                // Aggiungi badge
                const badge = document.createElement('span');
                badge.className = 'disponibilita-badge badge bg-warning text-dark ms-auto';
                badge.innerHTML = '<i class="fas fa-calendar-times"></i>';
                badge.title = disponibilita.motivo;
                item.appendChild(badge);
            }
        }
    });
}

/**
 * Aggiorna i conflitti per i militari selezionati
 */
async function aggiornaConflitti() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const warningDiv = document.getElementById('availability-warnings');
    const conflictList = document.getElementById('conflict-list');
    
    if (!startDateInput || !startDateInput.value) {
        warningDiv.classList.add('d-none');
        return;
    }
    
    // Usa il Set globale militariSelezionati
    if (!window.militariSelezionati || militariSelezionati.size === 0) {
        warningDiv.classList.add('d-none');
        return;
    }
    
    // Ottieni i dati dei militari selezionati che NON sono disponibili
    conflittiDisponibilita = {};
    
    militariSelezionati.forEach(id => {
        const item = document.querySelector(`[data-id="${id}"]`);
        const disponibilita = disponibilitaMilitari[id];
        
        if (item && disponibilita && !disponibilita.disponibile) {
            conflittiDisponibilita[id] = {
                nome: item.dataset.nome,
                conflitti: [{
                    data: startDateInput.value,
                    motivo: disponibilita.motivo
                }]
            };
        }
    });
    
    // Mostra/nascondi avvisi
    if (Object.keys(conflittiDisponibilita).length > 0) {
        let html = '';
        for (const [militareId, data] of Object.entries(conflittiDisponibilita)) {
            html += `<li class="mb-1"><strong>${data.nome}</strong>: `;
            html += data.conflitti.map(c => {
                const dataFormatted = new Date(c.data + 'T00:00:00').toLocaleDateString('it-IT');
                return `${dataFormatted} (${c.motivo})`;
            }).join(', ');
            html += '</li>';
        }
        conflictList.innerHTML = html;
        warningDiv.classList.remove('d-none');
    } else {
        warningDiv.classList.add('d-none');
    }
}

// Funzione per aprire conferma eliminazione attività
async function openDeleteModal(activityId, event) {
    event.preventDefault();
    event.stopPropagation();
    
    // Usa il sistema di conferma unificato
    const confirmed = await SUGECO.Confirm.show({
        title: 'Elimina Attività',
        message: 'Eliminare questa attività? Verrà rimossa dal CPT di tutti i militari associati. L\'operazione non può essere annullata.',
        type: 'danger',
        confirmText: 'Elimina Definitivamente'
    });
    
    if (!confirmed) return;
    
    // Esegui eliminazione via form submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `{{ url('board/activities') }}/${activityId}`;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const methodField = document.createElement('input');
    methodField.type = 'hidden';
    methodField.name = '_method';
    methodField.value = 'DELETE';
    
    const csrfField = document.createElement('input');
    csrfField.type = 'hidden';
    csrfField.name = '_token';
    csrfField.value = csrfToken;
    
    form.appendChild(methodField);
    form.appendChild(csrfField);
    document.body.appendChild(form);
    form.submit();
}

window.addEventListener('DOMContentLoaded', () => {
    const token = document.querySelector('meta[name="csrf-token"]').content;
    
    // Fix per rimuovere eventuali classi CSS problematiche che causano opacità 
    function fixOpacityIssues() {
        // Rimuovi classi sortable-chosen che potrebbero essere rimaste
        document.querySelectorAll('.sortable-chosen, .activity-card.sortable-chosen').forEach(el => {
            el.classList.remove('sortable-chosen');
            el.style.opacity = '';
        });
        
        // Rimuovi classe dragging-active dal body se presente
        document.body.classList.remove('dragging-active');
        
        // Verifica e rimuovi eventuali overlay attivi
        const overlays = document.querySelectorAll('.modal-overlay.show, .cert-modal-container.active, .custom-confirm-overlay');
        overlays.forEach(overlay => {
            if (!overlay.closest('.modal.show')) {
                overlay.classList.remove('show', 'active');
                overlay.style.display = 'none';
            }
        });
        
        // Assicura che il body non abbia filtri di opacità 
        const bodyStyle = window.getComputedStyle(document.body);
        if (bodyStyle.opacity && parseFloat(bodyStyle.opacity) < 1) {
            document.body.style.opacity = '';
        }
    }
    
    // Esegui il fix immediatamente e dopo un breve delay
    fixOpacityIssues();
    setTimeout(fixOpacityIssues, 100);
    setTimeout(fixOpacityIssues, 500);
    
    // ==========================================
    // CUSTOM MILITARI SELECTOR - Sistema pulito e professionale
    // ==========================================
    
    window.militariSelezionati = new Set();
    let militariSelezionati = window.militariSelezionati;
    
    const searchInput = document.getElementById('militari-search');
    const selectedContainer = document.getElementById('militari-selected');
    const listContainer = document.getElementById('militari-list');
    const counterSpan = document.getElementById('militari-count');
    const hiddenInput = document.getElementById('militari-hidden');
    
    // Funzione per aggiornare il counter
    function aggiornaCounter() {
        counterSpan.textContent = militariSelezionati.size;
    }
    
    // Funzione per aggiornare l'hidden input
    function aggiornaHiddenInput() {
        hiddenInput.value = Array.from(militariSelezionati).join(',');
    }
    
    // Funzione per aggiornare la sezione badge
    function aggiornaBadges() {
        selectedContainer.innerHTML = '';
        
        if (militariSelezionati.size === 0) {
            selectedContainer.classList.add('empty');
            selectedContainer.innerHTML = '<span class="empty-message">Nessun militare selezionato</span>';
            return;
        }
        
        selectedContainer.classList.remove('empty');
        
        militariSelezionati.forEach(id => {
            const item = listContainer.querySelector(`[data-id="${id}"]`);
            if (item) {
                const nome = item.dataset.nome;
                const badge = document.createElement('div');
                badge.className = 'militare-badge';
                badge.innerHTML = `
                    <span class="militare-badge-name">${nome}</span>
                    <span class="militare-badge-remove" data-id="${id}">&times;</span>
                `;
                selectedContainer.appendChild(badge);
            }
        });
        
        // Aggiungi listener per rimuovere badge
        selectedContainer.querySelectorAll('.militare-badge-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                militariSelezionati.delete(id);
                aggiornaInterfaccia();
                aggiornaConflitti();
            });
        });
    }
    
    // Funzione per aggiornare lo stato degli item nella lista
    function aggiornaListaStati() {
        listContainer.querySelectorAll('.militare-item').forEach(item => {
            const id = item.dataset.id;
            const checkIcon = item.querySelector('.militare-item-check');
            
            if (militariSelezionati.has(id)) {
                item.classList.add('selected');
                checkIcon.style.display = 'inline';
            } else {
                item.classList.remove('selected');
                checkIcon.style.display = 'none';
            }
        });
    }
    
    // Funzione per aggiornare tutta l'interfaccia
    function aggiornaInterfaccia() {
        aggiornaBadges();
        aggiornaListaStati();
        aggiornaCounter();
        aggiornaHiddenInput();
    }
    
    // Click su un militare nella lista
    if (listContainer) {
        listContainer.addEventListener('click', function(e) {
            const item = e.target.closest('.militare-item');
            if (!item || item.classList.contains('disabled')) return;
            
            const id = item.dataset.id;
            
            if (militariSelezionati.has(id)) {
                militariSelezionati.delete(id);
            } else {
                militariSelezionati.add(id);
            }
            
            aggiornaInterfaccia();
            aggiornaConflitti();
        });
    }
    
    // Ricerca militari
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            let hasResults = false;
            
            listContainer.querySelectorAll('.militari-group').forEach(group => {
                const items = group.querySelectorAll('.militare-item');
                let groupHasResults = false;
                
                items.forEach(item => {
                    const nome = item.dataset.nome.toLowerCase();
                    
                    if (searchTerm === '' || nome.includes(searchTerm)) {
                        item.style.display = '';
                        groupHasResults = true;
                        hasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Mostra/nascondi il gruppo
                if (groupHasResults) {
                    group.style.display = '';
                } else {
                    group.style.display = 'none';
                }
            });
            
            // Mostra messaggio se nessun risultato
            let noResultsMsg = listContainer.querySelector('.militari-no-results');
            
            if (!hasResults) {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.className = 'militari-no-results';
                    noResultsMsg.innerHTML = '<i class="fas fa-users-slash"></i><br>Nessun militare trovato';
                    listContainer.appendChild(noResultsMsg);
                }
            } else {
                if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            }
        });
    }
    
    // Aggiungi listener per date - carica automaticamente disponibilità
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    if (startDateInput) {
        startDateInput.addEventListener('change', async function() {
            // Carica disponibilità per tutti i militari (chiamata batch)
            await caricaDisponibilitaMilitari(this.value);
            // Poi aggiorna i conflitti per i selezionati
            aggiornaConflitti();
        });
    }
    if (endDateInput) {
        endDateInput.addEventListener('change', aggiornaConflitti);
    }
    
    // Gestione submit form
    const activityForm = document.querySelector('#newActivityForm');
    if (activityForm) {
        activityForm.addEventListener('submit', async (e) => {
            const compagniaMountingField = document.querySelector('#compagnia_mounting_id');
            const titleField = document.querySelector('#title');
            const startDateField = document.querySelector('#start_date');
            const columnIdField = document.querySelector('#column_id');
            
            // Verifica campi obbligatori
            if (!titleField.value) {
                showWarning('Il titolo è obbligatorio');
                e.preventDefault();
                return false;
            }
            if (!startDateField.value) {
                showWarning('La data di inizio è obbligatoria');
                e.preventDefault();
                return false;
            }
            if (!columnIdField.value) {
                showWarning('Devi selezionare uno stato');
                e.preventDefault();
                return false;
            }
            if (!compagniaMountingField || !compagniaMountingField.value) {
                showWarning('Devi selezionare la compagnia mounting (organizzatrice)');
                e.preventDefault();
                return false;
            }
            
            // Avviso se ci sono conflitti - usa sistema conferma unificato
            if (Object.keys(conflittiDisponibilita).length > 0) {
                e.preventDefault();
                const conferma = await SUGECO.Confirm.warning('Alcuni militari selezionati hanno conflitti di disponibilità. Vuoi procedere comunque?', 'Procedi');
                if (conferma) {
                    // Rimuovi temporaneamente il listener e invia il form
                    this.submit();
                }
                return false;
            }
            
            });
    }
    
    // pulsanti "Nuova Attività "
    document.querySelectorAll('.add-activity-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const { columnId, columnName } = e.currentTarget.dataset;
            const sel = document.querySelector('#column_id'); if (sel) sel.value = columnId;
            document.querySelector('#createActivityModal .modal-title').innerHTML =
                `<i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attività  in "${columnName}"`;
        });
    });
    
    // reset modal
    const modal = document.getElementById('createActivityModal');
    if (modal) {
        // Quando si apre il modal
        modal.addEventListener('show.bs.modal', async () => {
            // Imposta la data di inizio ad oggi se non impostata
            const startDateField = document.querySelector('#start_date');
            if (startDateField && !startDateField.value) {
                startDateField.value = new Date().toISOString().split('T')[0];
            }
            
            // Carica automaticamente la disponibilità per la data selezionata
            if (startDateField && startDateField.value) {
                await caricaDisponibilitaMilitari(startDateField.value);
            }
        });
        
        // Quando si chiude il modal
        modal.addEventListener('hidden.bs.modal', () => {
            const compagniaMountingId = document.querySelector('#compagnia_mounting_id')?.value;
            document.querySelector('#newActivityForm').reset();
            // Ripristina la compagnia mounting se era selezionata
            if (compagniaMountingId) {
                document.querySelector('#compagnia_mounting_id').value = compagniaMountingId;
            }
            // Reset Select2 militari
            if (typeof $ !== 'undefined' && $('#militari').length) {
                $('#militari').val(null).trigger('change');
            }
            document.querySelector('#createActivityModal .modal-title').innerHTML =
                '<i class="fas fa-plus-circle text-primary me-2"></i>Nuova Attività ';
            document.querySelector('#start_date').value = new Date().toISOString().split('T')[0];
            
            // Reset indicatori disponibilità
            disponibilitaMilitari = {};
            document.getElementById('disponibilita-info').classList.add('d-none');
            document.getElementById('availability-warnings').classList.add('d-none');
            const listContainer = document.getElementById('militari-list');
            if (listContainer) {
                listContainer.querySelectorAll('.militare-item').forEach(item => {
                    item.classList.remove('militare-disponibile', 'militare-non-disponibile');
                    item.title = '';
                    const badge = item.querySelector('.disponibilita-badge');
                    if (badge) badge.remove();
                });
            }
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
            // Filtra gli elementi che NON devono essere trascinabili
            filter: '.empty-column-message',
            // Non avviare il drag sugli elementi filtrati
            preventOnFilter: true,
            // Solo gli elementi con classe activity-card possono essere trascinati
            draggable: '.activity-card',
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
                if (from === to && evt.oldIndex === evt.newIndex) {
                    // Rimuovi classi anche se non c'è stato movimento
                    evt.item.classList.remove('sortable-chosen');
                    evt.item.style.opacity = '';
                    return;
                }

                const item = evt.item;
                const actId = item.dataset.activityId;
                const colId = to.closest('.board-column').dataset.columnId;
                const slug  = to.closest('.board-column').dataset.columnSlug;

                // Rimuovi tutte le classi sortable-chosen e resetta opacità 
                item.classList.remove('sortable-chosen');
                item.style.opacity = '';
                
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
                <p class="mb-0">Nessuna attività  in questa colonna</p>
                <p class="small">Trascina qui un'attività  o creane una nuova</p>`;
            el.appendChild(d);
        } else if (hasCards && emptyMsg) {
            emptyMsg.remove();
        }
    }
    
    // Inizializza gli empty states
    document.querySelectorAll('.activities-container').forEach(checkEmpty);
    
    // Listener per quando si chiudono i modal - rimuovi overlay residui
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', () => {
            fixOpacityIssues();
        });
    });
    
    // Listener per quando la pagina diventa visibile (utile se si torna alla tab)
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            fixOpacityIssues();
        }
    });
    
    // Listener per quando si clicca sulla pagina - rimuovi eventuali classi problematiche
    document.addEventListener('click', (e) => {
        // Se non si sta cliccando su un elemento sortable, rimuovi le classi
        if (!e.target.closest('.activity-card') && !e.target.closest('.sortable')) {
            setTimeout(() => {
                document.querySelectorAll('.sortable-chosen').forEach(el => {
                    el.classList.remove('sortable-chosen');
                    el.style.opacity = '';
                });
            }, 100);
        }
    });
});
</script>

<!-- Floating Button Export Excel -->
<a href="{{ route('board.export') }}" class="fab fab-excel" data-tooltip="Esporta Board" aria-label="Esporta Board Excel">
    <i class="fas fa-file-excel"></i>
</a>

<!-- Modal conferma eliminazione gestito da SUGECO.Confirm -->

@endpush

