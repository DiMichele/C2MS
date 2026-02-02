@extends('layouts.app')

@section('title', $activity->title . ' - Dettaglio Attività')

@section('styles')
<style>
/* ==========================================
   PAGE LAYOUT
   ========================================== */

.activity-detail-page {
    max-width: 1400px;
    margin: 0 auto;
}

/* ==========================================
   STANDARD PAGE HEADER
   ========================================== */

.page-header {
    text-align: center;
    margin-bottom: 2rem;
}

.page-title {
    font-family: 'Oswald', sans-serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: 2px;
    margin-bottom: 0.5rem;
    position: relative;
    display: inline-block;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, var(--gold), #f4d03f);
    border-radius: 2px;
}

.page-subtitle {
    color: var(--gray-600);
    font-size: 0.9375rem;
    margin-top: 1rem;
}

/* Breadcrumb */
.breadcrumb-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.breadcrumb {
    background: transparent;
    padding: 0;
    margin: 0;
    font-size: 0.8125rem;
}

.breadcrumb-item a {
    color: var(--gray-600);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--navy);
}

.breadcrumb-item.active {
    color: var(--gray-500);
}

/* ==========================================
   LAYOUT PRINCIPALE
   ========================================== */

.activity-detail-layout {
    display: flex;
    gap: 1.5rem;
    align-items: flex-start;
}

.activity-sidebar {
    width: 320px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* ==========================================
   INFO CARDS
   ========================================== */

.info-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    border: 1px solid var(--gray-200);
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.875rem 1rem;
    background: var(--navy);
    color: white;
}

.info-card-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    font-size: 0.875rem;
}

.info-card-title {
    flex: 1;
    margin: 0;
    font-family: 'Oswald', sans-serif;
    font-size: 0.9375rem;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.info-card-body {
    padding: 1rem;
}

/* Title Card */
.title-card .info-card-body {
    padding: 0;
}

.activity-title-input {
    width: 100%;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--navy);
    font-family: 'Oswald', sans-serif;
    font-size: 1.5rem;
    font-weight: 600;
    padding: 1rem;
    transition: all 0.3s ease;
}

.activity-title-input:hover {
    background: var(--gray-50);
}

.activity-title-input:focus {
    outline: none;
    border-bottom-color: var(--gold);
    background: var(--light-sand);
}

/* ==========================================
   DATE FIELDS
   ========================================== */

.date-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.date-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.date-field label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--gray-600);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-field .form-control {
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.date-field .form-control:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 2px rgba(10, 35, 66, 0.1);
}

.duration-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background: var(--light-sand);
    border-radius: 6px;
    margin-top: 0.75rem;
    font-size: 0.8125rem;
    color: var(--navy);
    font-weight: 500;
}

.duration-info i {
    color: var(--gold);
}

/* ==========================================
   FORM SELECT
   ========================================== */

.form-select {
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    transition: all 0.2s ease;
}

.form-select:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 2px rgba(10, 35, 66, 0.1);
}

.tipologia-badge {
    display: flex;
    align-items: center;
    margin-top: 0.5rem;
}

/* ==========================================
   METADATA CARD
   ========================================== */

.metadata-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.metadata-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.metadata-item:first-child {
    padding-top: 0;
}

.metadata-label {
    font-size: 0.75rem;
    color: var(--gray-600);
    display: flex;
    align-items: center;
}

.metadata-label i {
    width: 1rem;
    color: var(--gray-500);
}

.metadata-value {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--navy);
}

/* ==========================================
   NOTE CARD
   ========================================== */

.description-editor {
    width: 100%;
    min-height: 120px;
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    font-size: 0.875rem;
    line-height: 1.6;
    resize: vertical;
    transition: all 0.2s ease;
}

.description-editor:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 2px rgba(10, 35, 66, 0.1);
}

.description-editor::placeholder {
    color: var(--gray-500);
}

/* ==========================================
   MILITARI CARD
   ========================================== */

.militari-card .info-card-header {
    position: relative;
}

.militari-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 0.375rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 11px;
    font-size: 0.6875rem;
    font-weight: 600;
    margin-left: 0.375rem;
}

.btn-add-militare {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-left: auto;
    padding: 0.375rem 0.75rem;
    background: var(--gold);
    color: var(--navy);
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-add-militare:hover {
    background: #c4a030;
}

/* Filtro Militari */
.militari-filter-bar {
    padding: 0.75rem 1rem;
    background: var(--gray-100);
    border-bottom: 1px solid var(--gray-200);
}

.search-wrapper {
    position: relative;
}

.search-wrapper i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-500);
    font-size: 0.8125rem;
}

.militari-filter-bar .militari-search-input {
    width: 100%;
    padding: 0.5rem 0.75rem 0.5rem 2rem;
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    font-size: 0.8125rem;
    transition: all 0.2s ease;
}

.militari-filter-bar .militari-search-input:focus {
    outline: none;
    border-color: var(--navy);
    box-shadow: 0 0 0 2px rgba(10, 35, 66, 0.1);
}

/* Lista Militari */
.militari-list-container {
    max-height: 500px;
    overflow-y: auto;
}

.militari-group {
    border-bottom: 1px solid var(--gray-200);
}

.militari-group:last-child {
    border-bottom: none;
}

.militari-group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--light-sand);
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.group-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 0.25rem;
    background: var(--navy);
    color: white;
    border-radius: 9px;
    font-size: 0.625rem;
}

.militari-group-items {
    padding: 0;
}

.militare-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-bottom: 1px solid var(--gray-100);
    transition: all 0.15s ease;
}

.militare-item:last-child {
    border-bottom: none;
}

.militare-item:hover {
    background: var(--gray-50);
}

.militare-info {
    flex: 1;
    min-width: 0;
}

.militare-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--navy);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s ease;
}

.militare-link:hover {
    color: var(--navy-light);
}

.militare-grado {
    font-weight: 700;
    color: var(--gray-700);
}

.militare-nome {
    font-weight: 500;
}

/* Pulsante rimozione militare - più visibile */
.btn-remove-militare {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    color: #dc2626;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.75rem;
    margin-left: 0.75rem;
    flex-shrink: 0;
}

.btn-remove-militare:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
    transform: scale(1.05);
}

/* Tooltip largo per rimozione militare */
.tooltip-wide .tooltip-inner {
    max-width: 280px;
    min-width: 200px;
    padding: 8px 12px;
    font-size: 0.8125rem;
    text-align: left;
}

/* Empty State */
.empty-militari-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
    text-align: center;
    color: var(--gray-500);
}

.empty-militari-state i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
    opacity: 0.5;
}

.empty-militari-state p {
    margin-bottom: 0.75rem;
    font-size: 0.9375rem;
}

/* Floating button usa gli stili globali da global.css */

/* ==========================================
   DELETE BUTTON IN HEADER
   ========================================== */

.btn-delete-activity {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1rem;
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    color: #dc2626;
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-delete-activity:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: white;
}

/* ==========================================
   GLOBAL SAVE INDICATOR
   ========================================== */

.global-save-indicator {
    position: fixed;
    bottom: 100px;
    right: 30px;
    z-index: 1050;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.3s ease;
    pointer-events: none;
}

.global-save-indicator.visible {
    opacity: 1;
    transform: translateY(0);
}

.save-indicator-content {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.625rem 1rem;
    background: var(--navy);
    color: white;
    border-radius: 6px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    font-size: 0.8125rem;
    font-weight: 500;
}

.save-indicator-content .saving-icon,
.save-indicator-content .saved-icon,
.save-indicator-content .error-icon {
    display: none;
}

.global-save-indicator.saving .saving-icon { display: inline; }
.global-save-indicator.saved .saved-icon { display: inline; color: #4ade80; }
.global-save-indicator.error .error-icon { display: inline; color: #f87171; }

.save-indicator-content .save-text { display: none; }
.global-save-indicator.saving .save-text::after { content: 'Salvando...'; }
.global-save-indicator.saved .save-text::after { content: 'Salvato'; }
.global-save-indicator.error .save-text::after { content: 'Errore'; }
.global-save-indicator.visible .save-indicator-content .save-text { display: inline; }

/* ==========================================
   ANIMATIONS
   ========================================== */

@keyframes fadeOutRight {
    from { opacity: 1; transform: translateX(0); }
    to { opacity: 0; transform: translateX(20px); }
}

/* ==========================================
   MODAL STYLING
   ========================================== */

#addMilitareModal .modal-content,
#confirmRemoveModal .modal-content,
#deleteModal .modal-content {
    border: none;
    border-radius: 8px;
    overflow: hidden;
}

#addMilitareModal .modal-header,
#confirmRemoveModal .modal-header {
    background: var(--navy);
    color: white;
    border: none;
    padding: 1rem 1.25rem;
}

#addMilitareModal .modal-header .btn-close,
#confirmRemoveModal .modal-header .btn-close {
    filter: brightness(0) invert(1);
}

/* Militari Selector nel Modal */
#addMilitareModal .militari-selector-wrapper {
    border: 1px solid var(--gray-300);
    border-radius: 6px;
    overflow: hidden;
}

#addMilitareModal .militari-search-section {
    padding: 0.75rem;
    background: var(--light-sand);
    border-bottom: 1px solid var(--gray-200);
}

#addMilitareModal .militari-search-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: 4px;
    font-size: 0.875rem;
}

#addMilitareModal .militari-search-input:focus {
    outline: none;
    border-color: var(--navy);
}

#addMilitareModal .militari-selected-section {
    min-height: 50px;
    max-height: 100px;
    overflow-y: auto;
    padding: 0.5rem;
    background: white;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
    align-content: flex-start;
}

#addMilitareModal .militari-selected-section.empty {
    align-items: center;
    justify-content: center;
}

#addMilitareModal .militari-selected-section .empty-message {
    color: var(--gray-500);
    font-size: 0.8125rem;
    font-style: italic;
}

#addMilitareModal .militare-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    background: var(--navy);
    color: white;
    border-radius: 3px;
    font-size: 0.75rem;
    font-weight: 500;
}

#addMilitareModal .militare-badge-remove {
    cursor: pointer;
    opacity: 0.7;
}

#addMilitareModal .militare-badge-remove:hover {
    opacity: 1;
}

#addMilitareModal .militari-list-section {
    max-height: 250px;
    overflow-y: auto;
}

#addMilitareModal .militari-group-header {
    padding: 0.5rem 0.75rem;
    background: var(--gray-100);
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--gray-200);
}

#addMilitareModal .militare-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border-bottom: 1px solid var(--gray-100);
    font-size: 0.8125rem;
}

#addMilitareModal .militare-item:hover {
    background: var(--light-sand);
}

#addMilitareModal .militare-item.selected {
    background: rgba(10, 35, 66, 0.08);
}

#addMilitareModal .militare-item[data-gia-presente="1"] {
    opacity: 0.5;
    cursor: not-allowed;
}

#addMilitareModal .militare-item-icon {
    color: var(--gray-500);
    font-size: 0.75rem;
}

#addMilitareModal .militare-item-name {
    flex: 1;
}

#addMilitareModal .militare-item-check {
    color: var(--success);
}

#addMilitareModal .militari-counter {
    padding: 0.5rem 0.75rem;
    background: var(--gray-100);
    font-size: 0.75rem;
    color: var(--gray-600);
    text-align: center;
    border-top: 1px solid var(--gray-200);
}

/* ==========================================
   RESPONSIVE
   ========================================== */

@media (max-width: 992px) {
    .activity-detail-layout {
        flex-direction: column;
    }
    
    .activity-sidebar {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
    }
    
    .activity-sidebar .info-card {
        flex: 1 1 calc(50% - 0.5rem);
        min-width: 280px;
    }
    
    .activity-main {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .activity-sidebar .info-card {
        flex: 1 1 100%;
    }
    
}

/* Scrollbar */
.militari-list-container::-webkit-scrollbar,
#addMilitareModal .militari-list-section::-webkit-scrollbar {
    width: 5px;
}

.militari-list-container::-webkit-scrollbar-track,
#addMilitareModal .militari-list-section::-webkit-scrollbar-track {
    background: var(--gray-100);
}

.militari-list-container::-webkit-scrollbar-thumb,
#addMilitareModal .militari-list-section::-webkit-scrollbar-thumb {
    background: var(--gray-400);
    border-radius: 3px;
}
</style>
@endsection

@section('content')
<div class="container-fluid activity-detail-page">
    
    {{-- Header Standard --}}
    <div class="page-header">
        <nav class="breadcrumb-wrapper">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('board.index') }}"><i class="fas fa-th-large me-1"></i>Hub Attività</a></li>
                <li class="breadcrumb-item active">Dettaglio</li>
            </ol>
        </nav>
        <h1 class="page-title">DETTAGLIO ATTIVITÀ</h1>
        <p class="page-subtitle">
            <button type="button" class="btn-delete-activity" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="fas fa-trash"></i>
                Elimina attività
            </button>
        </p>
    </div>

    {{-- Layout Principale --}}
    <div class="activity-detail-layout">
        
        {{-- Sidebar Sinistra --}}
        <aside class="activity-sidebar">
            
            {{-- Card Titolo --}}
            <div class="info-card title-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <h3 class="info-card-title">Titolo Attività</h3>
                </div>
                <div class="info-card-body">
                    <input type="text" 
                           class="activity-title-input autosave" 
                           data-field="title"
                           value="{{ $activity->title }}"
                           placeholder="Titolo attività...">
                </div>
            </div>

            {{-- Card Periodo --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="info-card-title">Periodo</h3>
                </div>
                <div class="info-card-body">
                    <div class="date-field-group">
                        <div class="date-field">
                            <label for="start_date">Data Inizio</label>
                            <input type="date" 
                                   id="start_date"
                                   class="form-control autosave" 
                                   data-field="start_date"
                                   value="{{ $activity->start_date->format('Y-m-d') }}">
                        </div>
                        <div class="date-field">
                            <label for="end_date">Data Fine</label>
                            <input type="date" 
                                   id="end_date"
                                   class="form-control autosave" 
                                   data-field="end_date"
                                   value="{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : '' }}">
                        </div>
                    </div>
                    <div class="duration-info">
                        <i class="fas fa-clock"></i>
                        <span id="duration-days">
                            @php
                                $start = $activity->start_date;
                                $end = $activity->end_date ?? $activity->start_date;
                                $days = $start->diffInDays($end) + 1;
                            @endphp
                            {{ $days }} {{ $days == 1 ? 'giorno' : 'giorni' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Card Compagnia Mounting --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="info-card-title">Compagnia Mounting</h3>
                </div>
                <div class="info-card-body">
                    <select class="form-select autosave" data-field="compagnia_mounting_id" id="compagnia_mounting_id">
                        @foreach(\App\Models\Compagnia::orderBy('nome')->get() as $comp)
                            <option value="{{ $comp->id }}" 
                                    {{ $activity->compagnia_mounting_id == $comp->id ? 'selected' : '' }}>
                                {{ $comp->nome }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted mt-2 d-block">Compagnia organizzatrice</small>
                </div>
            </div>

            {{-- Card Tipologia --}}
            <div class="info-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        @php
                            $icons = [
                                'servizi-isolati' => 'fa-shield-alt',
                                'esercitazioni' => 'fa-running',
                                'stand-by' => 'fa-pause-circle',
                                'operazioni' => 'fa-bolt',
                                'corsi' => 'fa-graduation-cap',
                                'cattedre' => 'fa-chalkboard-teacher'
                            ];
                        @endphp
                        <i class="fas {{ $icons[$activity->column->slug] ?? 'fa-tag' }}"></i>
                    </div>
                    <h3 class="info-card-title">Tipologia</h3>
                </div>
                <div class="info-card-body">
                    <select class="form-select autosave" data-field="column_id" id="column_id">
                        @foreach(\App\Models\BoardColumn::orderBy('order')->get() as $col)
                            <option value="{{ $col->id }}" {{ $activity->column_id == $col->id ? 'selected' : '' }}>
                                {{ $col->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="tipologia-badge">
                        @php
                            $colors = [
                                'servizi-isolati' => 'secondary',
                                'esercitazioni' => 'warning',
                                'stand-by' => 'warning',
                                'operazioni' => 'danger',
                                'corsi' => 'primary',
                                'cattedre' => 'success'
                            ];
                            $color = $colors[$activity->column->slug] ?? 'primary';
                        @endphp
                        <span class="badge bg-{{ $color }}" id="tipologia-badge">{{ $activity->column->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Card Informazioni --}}
            <div class="info-card metadata-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <h3 class="info-card-title">Informazioni</h3>
                </div>
                <div class="info-card-body">
                    <div class="metadata-item">
                        <span class="metadata-label"><i class="fas fa-users me-2"></i>Militari</span>
                        <span class="metadata-value" id="stat-militari">{{ $activity->militari->count() }}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label"><i class="fas fa-sitemap me-2"></i>Compagnie coinvolte</span>
                        <span class="metadata-value" id="stat-compagnie">{{ $activity->militari->pluck('compagnia_id')->unique()->count() }}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label"><i class="fas fa-user me-2"></i>Creato da</span>
                        <span class="metadata-value">{{ $activity->creator->name ?? 'Sistema' }}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label"><i class="fas fa-calendar-plus me-2"></i>Creazione</span>
                        <span class="metadata-value">{{ $activity->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="metadata-item">
                        <span class="metadata-label"><i class="fas fa-edit me-2"></i>Ultimo agg.</span>
                        <span class="metadata-value" id="last-updated">{{ $activity->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </aside>

        {{-- Contenuto Principale --}}
        <main class="activity-main">
            
            {{-- Card Militari Coinvolti --}}
            <div class="info-card militari-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="info-card-title">
                        Militari Coinvolti 
                        <span class="militari-count-badge" id="militari-count-badge">{{ $activity->militari->count() }}</span>
                    </h3>
                    <button type="button" class="btn-add-militare" data-bs-toggle="modal" data-bs-target="#addMilitareModal">
                        <i class="fas fa-plus"></i>
                        <span>Aggiungi</span>
                    </button>
                </div>
                <div class="info-card-body p-0">
                    {{-- Barra ricerca militari --}}
                    <div class="militari-filter-bar">
                        <div class="search-wrapper">
                            <i class="fas fa-search"></i>
                            <input type="text" 
                                   id="militari-search-filter" 
                                   class="militari-search-input" 
                                   placeholder="Cerca tra i militari assegnati...">
                        </div>
                    </div>
                    
                    {{-- Lista Militari --}}
                    <div class="militari-list-container" id="militariListContainer">
                        @if($activity->militari->count() > 0)
                            @php
                                $militariPerCompagnia = $activity->militari->sortBy(function($m) {
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
                            
                            @foreach($militariPerCompagnia as $compagniaNome => $militari)
                                <div class="militari-group" data-compagnia="{{ $compagniaNome }}">
                                    <div class="militari-group-header">
                                        <span class="group-name">Compagnia {{ $compagniaNome }}</span>
                                        <span class="group-count">{{ $militari->count() }}</span>
                                    </div>
                                    <div class="militari-group-items">
                                        @foreach($militari as $militare)
                                            <div class="militare-item" data-militare-id="{{ $militare->id }}" data-nome="{{ strtolower($militare->cognome . ' ' . $militare->nome) }}">
                                                <div class="militare-info">
                                                    <a href="{{ route('anagrafica.show', $militare) }}" class="militare-link" title="Visualizza profilo">
                                                        <span class="militare-grado">{{ optional($militare->grado)->abbreviazione ?? '' }}</span>
                                                        <span class="militare-nome">{{ $militare->cognome }} {{ $militare->nome }}</span>
                                                    </a>
                                                </div>
                                                <button type="button" 
                                                        class="btn-remove-militare" 
                                                        data-militare-id="{{ $militare->id }}"
                                                        data-militare-nome="{{ optional($militare->grado)->abbreviazione }} {{ $militare->cognome }} {{ $militare->nome }}"
                                                        data-bs-toggle="tooltip"
                                                        data-bs-placement="left"
                                                        data-bs-custom-class="tooltip-wide"
                                                        data-bs-title="Rimuovi {{ optional($militare->grado)->abbreviazione }} {{ $militare->cognome }} dall'attività">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="empty-militari-state">
                                <i class="fas fa-user-plus"></i>
                                <p>Nessun militare assegnato</p>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilitareModal">
                                    <i class="fas fa-plus me-1"></i>Aggiungi Militari
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card Note --}}
            <div class="info-card description-card">
                <div class="info-card-header">
                    <div class="info-card-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <h3 class="info-card-title">Note</h3>
                </div>
                <div class="info-card-body">
                    <textarea class="description-editor autosave" 
                              data-field="description" 
                              rows="5"
                              placeholder="Aggiungi note per questa attività...">{{ $activity->description }}</textarea>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- Floating Export Button --}}
<a href="{{ route('board.activities.export', $activity) }}" class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

{{-- Modal Aggiungi Militari --}}
<div class="modal fade" id="addMilitareModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Aggiungi Militari</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Info disponibilità --}}
                <div id="disponibilita-info-modal" class="alert alert-info mb-3 py-2 d-none">
                    <i class="fas fa-info-circle me-1"></i>
                    <small>I militari sono colorati in base alla disponibilità: 
                        <span class="badge bg-success">Disponibile</span> 
                        <span class="badge bg-warning text-dark">Non disponibile</span>
                    </small>
                </div>
                
                {{-- Warning conflitti --}}
                <div id="conflitti-warning-modal" class="alert alert-warning mb-3 d-none">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Attenzione:</strong> <span id="conflitti-count-modal">0</span> militari selezionati hanno conflitti di disponibilità.
                    <small class="d-block mt-1">Puoi comunque aggiungerli, ma verranno sovrascritti i loro impegni nel CPT.</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Seleziona Militari</label>
                    
                    <div class="militari-selector-wrapper">
                        <div class="militari-search-section">
                            <input type="text" id="militari-search-modal" class="militari-search-input" placeholder="Cerca militare per nome, cognome o grado...">
                        </div>
                        
                        <div class="militari-selected-section" id="militari-selected-modal">
                            <span class="empty-message">Nessun militare selezionato</span>
                        </div>
                        
                        <div class="militari-list-section" id="militari-list-modal">
                            @php
                                $userModal = auth()->user();
                                $canViewAllMilitariModal = $userModal && (
                                    $userModal->isGlobalAdmin() || 
                                    $userModal->hasPermission('board.view_all_militari') ||
                                    $userModal->hasPermission('view_all_companies')
                                );
                                
                                $militariModalQuery = $canViewAllMilitariModal 
                                    ? \App\Models\Militare::withoutGlobalScopes() 
                                    : \App\Models\Militare::query();
                                
                                $militariModal = $militariModalQuery->with(['grado', 'compagnia'])
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
                            @foreach($militariModal as $compNome => $mils)
                              <div class="militari-group">
                                <div class="militari-group-header">Compagnia {{ $compNome }}</div>
                                @foreach($mils as $mil)
                                  <div class="militare-item" 
                                       data-id="{{ $mil->id }}" 
                                       data-nome="{{ optional($mil->grado)->abbreviazione }} {{ $mil->cognome }} {{ $mil->nome }}"
                                       data-gia-presente="{{ $activity->militari->contains($mil->id) ? '1' : '0' }}">
                                    <i class="fas fa-user militare-item-icon"></i>
                                    <span class="militare-item-name">{{ optional($mil->grado)->abbreviazione }} {{ $mil->cognome }} {{ $mil->nome }}</span>
                                    @if($activity->militari->contains($mil->id))
                                        <span class="badge bg-secondary ms-auto me-2">Già assegnato</span>
                                    @endif
                                    <i class="fas fa-check militare-item-check" style="display: none;"></i>
                                  </div>
                                @endforeach
                              </div>
                            @endforeach
                        </div>
                        
                        <div class="militari-counter">
                            <span id="militari-count-modal">0</span> militari selezionati
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                <button type="button" class="btn btn-success" id="aggiungiSelezionatiBtn" disabled>
                    <i class="fas fa-check me-1"></i>Aggiungi Selezionati (<span id="countSelezionati">0</span>)
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal conferma rimozione militare e eliminazione attività gestiti da SUGECO.Confirm --}}

{{-- Form nascosto per eliminazione attività --}}
<form id="deleteActivityForm" action="{{ route('board.activities.destroy', $activity) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

{{-- Global Save Indicator --}}
<div class="global-save-indicator" id="globalSaveIndicator">
    <div class="save-indicator-content">
        <i class="fas fa-spinner fa-spin saving-icon"></i>
        <i class="fas fa-check saved-icon"></i>
        <i class="fas fa-times error-icon"></i>
        <span class="save-text">Salvato</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Variabili globali
const activityId = {{ $activity->id }};
const activityStartDate = '{{ $activity->start_date->format('Y-m-d') }}';
const activityEndDate = '{{ $activity->end_date ? $activity->end_date->format('Y-m-d') : $activity->start_date->format('Y-m-d') }}';
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let militariSelezionatiModal = new Set();
// risultatiVerifica è stato rimosso - ora usiamo disponibilitaMilitariModalDetail
let militareIdToRemove = null;

// ==========================================
// TOAST NOTIFICATIONS
// ==========================================

function showToast(title, message, type = 'info') {
    if (window.SUGECO && window.SUGECO.Toast) {
        window.SUGECO.Toast.show(message, type);
        return;
    }
    
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const iconMap = {
        'success': 'fa-check-circle text-success',
        'error': 'fa-times-circle text-danger',
        'warning': 'fa-exclamation-triangle text-warning',
        'info': 'fa-info-circle text-info'
    };
    
    const toastHTML = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="fas ${iconMap[type] || iconMap.info} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const bsToast = new bootstrap.Toast(toastElement, { delay: 4000 });
    bsToast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}

// ==========================================
// SISTEMA AUTOSALVATAGGIO
// ==========================================

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSaveIndicator(status) {
    const indicator = document.getElementById('globalSaveIndicator');
    indicator.classList.remove('saving', 'saved', 'error');
    indicator.classList.add('visible', status);
    
    if (status === 'saved') {
        setTimeout(() => {
            indicator.classList.remove('visible');
        }, 2000);
    }
}

async function autoSaveField(field, value) {
    showSaveIndicator('saving');
    
    try {
        const response = await fetch(`{{ route('board.activities.autosave', $activity) }}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ field, value })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSaveIndicator('saved');
            
            if (result.updated_at) {
                document.getElementById('last-updated').textContent = result.updated_at;
            }
            
            if (field === 'start_date' || field === 'end_date') {
                updateDuration();
            }
            
            if (field === 'column_id' && result.activity && result.activity.column) {
                const badge = document.getElementById('tipologia-badge');
                badge.textContent = result.activity.column.name;
            }
            
        } else {
            showSaveIndicator('error');
            showToast('Errore', result.message || 'Errore durante il salvataggio', 'error');
        }
    } catch (error) {
        console.error('Errore autosave:', error);
        showSaveIndicator('error');
        showToast('Errore', 'Errore di connessione', 'error');
    }
}

const debouncedAutoSave = debounce(autoSaveField, 800);

function updateDuration() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value || startDate;
    
    if (startDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        document.getElementById('duration-days').textContent = diffDays + (diffDays === 1 ? ' giorno' : ' giorni');
    }
}

// ==========================================
// GESTIONE MILITARI
// ==========================================

function filterMilitariList() {
    const searchTerm = document.getElementById('militari-search-filter').value.toLowerCase().trim();
    const container = document.getElementById('militariListContainer');
    
    container.querySelectorAll('.militare-item').forEach(item => {
        const nome = item.dataset.nome || '';
        if (searchTerm === '' || nome.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
    
    container.querySelectorAll('.militari-group').forEach(group => {
        const visibleItems = group.querySelectorAll('.militare-item:not([style*="display: none"])').length;
        group.style.display = visibleItems > 0 ? '' : 'none';
    });
}

function setupRemoveMilitareButtons() {
    document.querySelectorAll('.btn-remove-militare').forEach(btn => {
        btn.addEventListener('click', async function() {
            const militareId = this.dataset.militareId;
            const militareNome = this.dataset.militareNome;
            
            // Usa il sistema di conferma unificato
            const confirmed = await SUGECO.Confirm.show({
                title: 'Rimuovi Militare',
                message: `Rimuovere ${militareNome} da questa attività? Il militare sarà rimosso anche dal CPT.`,
                type: 'warning',
                confirmText: 'Rimuovi'
            });
            
            if (confirmed) {
                removeMilitare(militareId);
            }
        });
    });
}

// Funzione per conferma eliminazione attività
async function confirmDeleteActivity() {
    const confirmed = await SUGECO.Confirm.show({
        title: 'Elimina Attività',
        message: 'Eliminare questa attività? Verrà rimossa definitivamente dal CPT. L\'operazione non può essere annullata.',
        type: 'danger',
        confirmText: 'Elimina'
    });
    
    if (confirmed) {
        document.getElementById('deleteActivityForm').submit();
    }
}

async function removeMilitare(militareId) {
    try {
        const response = await fetch(`{{ url('board/activities') }}/${activityId}/militari/${militareId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const item = document.querySelector(`[data-militare-id="${militareId}"]`);
            if (item) {
                item.style.animation = 'fadeOutRight 0.3s ease forwards';
                setTimeout(() => {
                    item.remove();
                    updateMilitariCounts();
                }, 300);
            }
            showToast('Successo', 'Militare rimosso con successo', 'success');
        } else {
            showToast('Errore', data.message, 'error');
        }
    } catch (error) {
        showToast('Errore', 'Errore di connessione', 'error');
    }
}

function updateMilitariCounts() {
    const container = document.getElementById('militariListContainer');
    const count = container.querySelectorAll('.militare-item').length;
    
    document.getElementById('militari-count-badge').textContent = count;
    document.getElementById('stat-militari').textContent = count;
    
    const compagnie = new Set();
    container.querySelectorAll('.militari-group').forEach(group => {
        if (group.querySelectorAll('.militare-item').length > 0) {
            compagnie.add(group.dataset.compagnia);
        }
    });
    document.getElementById('stat-compagnie').textContent = compagnie.size;
    
    container.querySelectorAll('.militari-group').forEach(group => {
        const groupCount = group.querySelectorAll('.militare-item').length;
        const countEl = group.querySelector('.group-count');
        if (countEl) {
            countEl.textContent = groupCount;
        }
        if (groupCount === 0) {
            group.style.display = 'none';
        }
    });
    
    if (count === 0) {
        container.innerHTML = `
            <div class="empty-militari-state">
                <i class="fas fa-user-plus"></i>
                <p>Nessun militare assegnato</p>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMilitareModal">
                    <i class="fas fa-plus me-1"></i>Aggiungi Militari
                </button>
            </div>
        `;
    }
}

// ==========================================
// MODAL AGGIUNGI MILITARI
// ==========================================

function setupMilitariModal() {
    const searchInputModal = document.getElementById('militari-search-modal');
    const selectedContainerModal = document.getElementById('militari-selected-modal');
    const listContainerModal = document.getElementById('militari-list-modal');
    const counterSpanModal = document.getElementById('militari-count-modal');
    const aggiungiBtn = document.getElementById('aggiungiSelezionatiBtn');
    const countSelezionati = document.getElementById('countSelezionati');
    
    function aggiornaCounterModal() {
        counterSpanModal.textContent = militariSelezionatiModal.size;
        countSelezionati.textContent = militariSelezionatiModal.size;
        aggiungiBtn.disabled = militariSelezionatiModal.size === 0;
        
        // Aggiorna warning conflitti
        aggiornaWarningConflittiDetail();
    }
    
    function aggiornaBadgesModal() {
        selectedContainerModal.innerHTML = '';
        
        if (militariSelezionatiModal.size === 0) {
            selectedContainerModal.classList.add('empty');
            selectedContainerModal.innerHTML = '<span class="empty-message">Nessun militare selezionato</span>';
            return;
        }
        
        selectedContainerModal.classList.remove('empty');
        
        militariSelezionatiModal.forEach(id => {
            const item = listContainerModal.querySelector(`[data-id="${id}"]`);
            if (item) {
                const nome = item.dataset.nome;
                const badge = document.createElement('div');
                badge.className = 'militare-badge';
                badge.innerHTML = `
                    <span class="militare-badge-name">${nome}</span>
                    <span class="militare-badge-remove" data-id="${id}">&times;</span>
                `;
                selectedContainerModal.appendChild(badge);
            }
        });
        
        selectedContainerModal.querySelectorAll('.militare-badge-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                militariSelezionatiModal.delete(id);
                aggiornaInterfacciaModal();
            });
        });
    }
    
    function aggiornaListaStatiModal() {
        listContainerModal.querySelectorAll('.militare-item').forEach(item => {
            const id = item.dataset.id;
            const checkIcon = item.querySelector('.militare-item-check');
            
            if (militariSelezionatiModal.has(id)) {
                item.classList.add('selected');
                if (checkIcon) checkIcon.style.display = 'inline';
            } else {
                item.classList.remove('selected');
                if (checkIcon) checkIcon.style.display = 'none';
            }
        });
    }
    
    function aggiornaInterfacciaModal() {
        aggiornaBadgesModal();
        aggiornaListaStatiModal();
        aggiornaCounterModal();
    }
    
    if (listContainerModal) {
        listContainerModal.addEventListener('click', function(e) {
            const item = e.target.closest('.militare-item');
            if (!item || item.classList.contains('disabled') || item.dataset.giaPresente === '1') return;
            
            const id = item.dataset.id;
            
            if (militariSelezionatiModal.has(id)) {
                militariSelezionatiModal.delete(id);
            } else {
                militariSelezionatiModal.add(id);
            }
            
            aggiornaInterfacciaModal();
        });
    }
    
    if (searchInputModal) {
        searchInputModal.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            listContainerModal.querySelectorAll('.militari-group').forEach(group => {
                const items = group.querySelectorAll('.militare-item');
                let groupHasResults = false;
                
                items.forEach(item => {
                    const nome = item.dataset.nome.toLowerCase();
                    if (searchTerm === '' || nome.includes(searchTerm)) {
                        item.style.display = '';
                        groupHasResults = true;
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                group.style.display = groupHasResults ? '' : 'none';
            });
        });
    }
    
    // Quando si apre il modal, carica la disponibilità
    document.getElementById('addMilitareModal').addEventListener('show.bs.modal', async function() {
        await caricaDisponibilitaMilitariModalDetail();
    });
    
    document.getElementById('addMilitareModal').addEventListener('hidden.bs.modal', function() {
        militariSelezionatiModal.clear();
        if (searchInputModal) searchInputModal.value = '';
        aggiornaInterfacciaModal();
        
        // Reset indicatori disponibilità
        disponibilitaMilitariModalDetail = {};
        document.getElementById('disponibilita-info-modal').classList.add('d-none');
        document.getElementById('conflitti-warning-modal').classList.add('d-none');
        listContainerModal.querySelectorAll('.militare-item').forEach(item => {
            item.classList.remove('militare-disponibile', 'militare-non-disponibile');
            item.title = '';
            const badge = item.querySelector('.disponibilita-badge');
            if (badge) badge.remove();
        });
    });
    
    window.aggiornaInterfacciaModal = aggiornaInterfacciaModal;
}

// Cache disponibilità militari per il modal detail
let disponibilitaMilitariModalDetail = {};

/**
 * Carica la disponibilità di tutti i militari per il periodo dell'attività
 */
async function caricaDisponibilitaMilitariModalDetail() {
    try {
        const response = await fetch('{{ route("servizi.turni.militari-disponibilita") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                data: activityStartDate,
                exclude_activity_id: activityId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            disponibilitaMilitariModalDetail = {};
            
            result.disponibili.forEach(m => {
                disponibilitaMilitariModalDetail[m.id] = { disponibile: true };
            });
            
            result.non_disponibili.forEach(m => {
                disponibilitaMilitariModalDetail[m.id] = { 
                    disponibile: false, 
                    motivo: m.motivo 
                };
            });
            
            aggiornaVisualDisponibilitaModalDetail();
            document.getElementById('disponibilita-info-modal').classList.remove('d-none');
        }
    } catch (error) {
        console.error('Errore caricamento disponibilità:', error);
    }
}

/**
 * Aggiorna l'aspetto visivo dei militari nella lista del modal detail
 */
function aggiornaVisualDisponibilitaModalDetail() {
    const listContainer = document.getElementById('militari-list-modal');
    if (!listContainer) return;
    
    listContainer.querySelectorAll('.militare-item').forEach(item => {
        const id = item.dataset.id;
        const disponibilita = disponibilitaMilitariModalDetail[id];
        
        item.classList.remove('militare-disponibile', 'militare-non-disponibile');
        const oldBadge = item.querySelector('.disponibilita-badge');
        if (oldBadge) oldBadge.remove();
        
        if (disponibilita) {
            if (disponibilita.disponibile) {
                item.classList.add('militare-disponibile');
            } else {
                item.classList.add('militare-non-disponibile');
                item.title = disponibilita.motivo || 'Non disponibile';
                
                const badge = document.createElement('span');
                badge.className = 'disponibilita-badge badge bg-warning text-dark ms-auto me-2';
                badge.innerHTML = '<i class="fas fa-calendar-times"></i>';
                badge.title = disponibilita.motivo;
                
                const giaAssegnatoBadge = item.querySelector('.badge.bg-secondary');
                const checkIcon = item.querySelector('.militare-item-check');
                if (giaAssegnatoBadge) {
                    item.insertBefore(badge, giaAssegnatoBadge);
                } else if (checkIcon) {
                    item.insertBefore(badge, checkIcon);
                } else {
                    item.appendChild(badge);
                }
            }
        }
    });
}

/**
 * Aggiorna warning conflitti per militari selezionati
 */
function aggiornaWarningConflittiDetail() {
    const warningDiv = document.getElementById('conflitti-warning-modal');
    const countSpan = document.getElementById('conflitti-count-modal');
    
    let conflittiCount = 0;
    militariSelezionatiModal.forEach(id => {
        const disp = disponibilitaMilitariModalDetail[id];
        if (disp && !disp.disponibile) {
            conflittiCount++;
        }
    });
    
    if (conflittiCount > 0) {
        countSpan.textContent = conflittiCount;
        warningDiv.classList.remove('d-none');
    } else {
        warningDiv.classList.add('d-none');
    }
}

// Aggiungi militari selezionati (nuovo sistema diretto)
document.getElementById('aggiungiSelezionatiBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Assegnazione...';
    
    const militariDaAggiungere = Array.from(militariSelezionatiModal);
    let aggiunti = 0;
    
    for (const militareId of militariDaAggiungere) {
        const dati = disponibilitaMilitariModalDetail[militareId];
        const force = dati && !dati.disponibile;
        
        try {
            const response = await fetch('{{ route('board.activities.attach.militare', $activity) }}', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                body: JSON.stringify({militare_id: militareId, force: force})
            });
            
            const result = await response.json();
            if (result.success) {
                aggiunti++;
            }
        } catch (error) {
            console.error('Errore:', error);
        }
    }
    
    bootstrap.Modal.getInstance(document.getElementById('addMilitareModal')).hide();
    
    if (aggiunti > 0) {
        showToast('Successo', `${aggiunti} militare/i aggiunti con successo`, 'success');
        setTimeout(() => location.reload(), 500);
    }
});

// ==========================================
// INIZIALIZZAZIONE
// ==========================================

document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tooltip Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
    
    // Setup autosave per tutti i campi
    document.querySelectorAll('.autosave').forEach(field => {
        field.addEventListener('input', function() {
            const fieldName = this.dataset.field;
            const value = this.value;
            debouncedAutoSave(fieldName, value);
        });
        
        field.addEventListener('change', function() {
            const fieldName = this.dataset.field;
            const value = this.value;
            autoSaveField(fieldName, value);
        });
    });
    
    // Setup filtro militari
    const searchFilter = document.getElementById('militari-search-filter');
    if (searchFilter) {
        searchFilter.addEventListener('input', filterMilitariList);
    }
    
    // Setup bottoni rimozione militari
    setupRemoveMilitareButtons();
    
    // Setup modal aggiungi militari
    setupMilitariModal();
    
    // Setup bottone elimina attività (se presente)
    const deleteBtn = document.querySelector('[data-action="delete-activity"]');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', confirmDeleteActivity);
    }
});
</script>
@endpush
