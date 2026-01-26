@extends('layouts.app')
@section('title', 'Scadenze - SUGECO')

@section('content')
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['pefo', 'idoneita_mans', 'idoneita_smi', 'lavoratore_4h', 'lavoratore_8h', 'preposto', 'dirigenti', 'poligono_approntamento', 'poligono_mantenimento'] as $filter) {
        if(request()->filled($filter) && request($filter) != 'tutti') $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Scadenze</h1>
</div>

<!-- Barra di ricerca centrata sotto il titolo -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchMilitare" 
            class="form-control" 
            data-search-type="militare"
            data-target-container="militariTableBody"
            placeholder="Cerca militare..." 
            aria-label="Cerca militare" 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e badge su riga separata -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
        <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
        <span id="toggleFiltersText">
            {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
        </span>
    </button>
    
</div>

<!-- Filtri con sezione migliorata -->
<div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-filter me-2"></i> Filtri avanzati
            </div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('scadenze.index') }}" method="GET">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro PEFO --}}
                    <div class="col-md-3">
                        <label for="pefo" class="form-label">
                            <i class="fas fa-certificate me-1"></i> PEFO
                        </label>
                        <div class="select-wrapper">
                            <select name="pefo" id="pefo" class="form-select filter-select {{ (request('pefo') && request('pefo') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('pefo', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('pefo') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('pefo') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('pefo') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('pefo') && request('pefo') != 'tutti')
                                <span class="clear-filter" data-filter="pefo" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Idoneità Mansione --}}
                    <div class="col-md-3">
                        <label for="idoneita_mans" class="form-label">
                            <i class="fas fa-certificate me-1"></i> Idoneità Mansione
                        </label>
                        <div class="select-wrapper">
                            <select name="idoneita_mans" id="idoneita_mans" class="form-select filter-select {{ (request('idoneita_mans') && request('idoneita_mans') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('idoneita_mans', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('idoneita_mans') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('idoneita_mans') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('idoneita_mans') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('idoneita_mans') && request('idoneita_mans') != 'tutti')
                                <span class="clear-filter" data-filter="idoneita_mans" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Idoneità SMI --}}
                    <div class="col-md-3">
                        <label for="idoneita_smi" class="form-label">
                            <i class="fas fa-certificate me-1"></i> Idoneità SMI
                        </label>
                        <div class="select-wrapper">
                            <select name="idoneita_smi" id="idoneita_smi" class="form-select filter-select {{ (request('idoneita_smi') && request('idoneita_smi') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('idoneita_smi', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('idoneita_smi') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('idoneita_smi') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('idoneita_smi') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('idoneita_smi') && request('idoneita_smi') != 'tutti')
                                <span class="clear-filter" data-filter="idoneita_smi" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Lavoratore 4h --}}
                    <div class="col-md-3">
                        <label for="lavoratore_4h" class="form-label">
                            <i class="fas fa-hard-hat me-1"></i> Lavoratore 4h
                        </label>
                        <div class="select-wrapper">
                            <select name="lavoratore_4h" id="lavoratore_4h" class="form-select filter-select {{ (request('lavoratore_4h') && request('lavoratore_4h') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('lavoratore_4h', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('lavoratore_4h') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('lavoratore_4h') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('lavoratore_4h') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('lavoratore_4h') && request('lavoratore_4h') != 'tutti')
                                <span class="clear-filter" data-filter="lavoratore_4h" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Seconda riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro Lavoratore 8h --}}
                    <div class="col-md-3">
                        <label for="lavoratore_8h" class="form-label">
                            <i class="fas fa-hard-hat me-1"></i> Lavoratore 8h
                        </label>
                        <div class="select-wrapper">
                            <select name="lavoratore_8h" id="lavoratore_8h" class="form-select filter-select {{ (request('lavoratore_8h') && request('lavoratore_8h') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('lavoratore_8h', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('lavoratore_8h') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('lavoratore_8h') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('lavoratore_8h') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('lavoratore_8h') && request('lavoratore_8h') != 'tutti')
                                <span class="clear-filter" data-filter="lavoratore_8h" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Preposto --}}
                    <div class="col-md-3">
                        <label for="preposto" class="form-label">
                            <i class="fas fa-user-tie me-1"></i> Preposto
                        </label>
                        <div class="select-wrapper">
                            <select name="preposto" id="preposto" class="form-select filter-select {{ (request('preposto') && request('preposto') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('preposto', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('preposto') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('preposto') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('preposto') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('preposto') && request('preposto') != 'tutti')
                                <span class="clear-filter" data-filter="preposto" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Dirigenti --}}
                    <div class="col-md-3">
                        <label for="dirigenti" class="form-label">
                            <i class="fas fa-user-shield me-1"></i> Dirigenti
                        </label>
                        <div class="select-wrapper">
                            <select name="dirigenti" id="dirigenti" class="form-select filter-select {{ (request('dirigenti') && request('dirigenti') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('dirigenti', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('dirigenti') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('dirigenti') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('dirigenti') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('dirigenti') && request('dirigenti') != 'tutti')
                                <span class="clear-filter" data-filter="dirigenti" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Teatro Operativo --}}
                    <div class="col-md-3">
                        <label for="poligono_approntamento" class="form-label">
                            <i class="fas fa-bullseye me-1"></i> Teatro Operativo
                        </label>
                        <div class="select-wrapper">
                            <select name="poligono_approntamento" id="poligono_approntamento" class="form-select filter-select {{ (request('poligono_approntamento') && request('poligono_approntamento') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('poligono_approntamento', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('poligono_approntamento') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('poligono_approntamento') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('poligono_approntamento') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('poligono_approntamento') && request('poligono_approntamento') != 'tutti')
                                <span class="clear-filter" data-filter="poligono_approntamento" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Terza riga filtri --}}
                <div class="row">
                    {{-- Filtro Poligono Mantenimento --}}
                    <div class="col-md-3">
                        <label for="poligono_mantenimento" class="form-label">
                            <i class="fas fa-bullseye me-1"></i> Poligono Mantenimento
                        </label>
                        <div class="select-wrapper">
                            <select name="poligono_mantenimento" id="poligono_mantenimento" class="form-select filter-select {{ (request('poligono_mantenimento') && request('poligono_mantenimento') != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request('poligono_mantenimento', 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request('poligono_mantenimento') == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request('poligono_mantenimento') == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request('poligono_mantenimento') == 'scaduti' ? 'selected' : '' }}>Scaduti</option>
                            </select>
                            @if(request('poligono_mantenimento') && request('poligono_mantenimento') != 'tutti')
                                <span class="clear-filter" data-filter="poligono_mantenimento" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('scadenze.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Table contenente i militari -->
<!-- Tabella con intestazione fissa e scroll -->
<div class="sugeco-table-wrapper">
    <!-- Intestazione fissa -->
     <div class="table-header-fixed">
        <table class="sugeco-table">
            <thead>
                <tr>
                    <th>Compagnia</th>
                    <th>Grado</th>
                    <th>Cognome</th>
                    <th>Nome</th>
                    <th>PEFO</th>
                    <th>Idoneità Mansione</th>
                    <th>Idoneità SMI</th>
                    <th>Lavoratore 4h</th>
                    <th>Lavoratore 8h</th>
                    <th>Preposto</th>
                    <th>Dirigenti</th>
                    <th>Teatro Operativo</th>
                    <th>Poligono Mantenimento</th>
                </tr>
            </thead>
        </table>
    </div>
    
    <!-- Corpo scrollabile -->
     <div class="table-body-scroll">
        <table class="sugeco-table">
            <tbody id="militariTableBody">
            @forelse($militari as $m)
                @php
                    $scadenza = $m->scadenza;
                @endphp
                <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}">
                    <td>
                        @if($m->compagnia)
                            <span class="badge bg-primary">{{ $m->compagnia }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        {{ $m->grado->sigla ?? '-' }}
                    </td>
                    <td>
                        <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                            {{ $m->cognome }}
                        </a>
                    </td>
                    <td>
                        {{ $m->nome }}
                    </td>
                    
                    <!-- PEFO -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="pefo"
                        onclick="apriModalData({{ $m->id }}, 'pefo', '{{ $scadenza->pefo_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->pefo_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->pefo_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('pefo') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Idoneità Mansione -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="idoneita_mans"
                        onclick="apriModalData({{ $m->id }}, 'idoneita_mans', '{{ $scadenza->idoneita_mans_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->idoneita_mans_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->idoneita_mans_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('idoneita_mans') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Idoneità SMI -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="idoneita_smi"
                        onclick="apriModalData({{ $m->id }}, 'idoneita_smi', '{{ $scadenza->idoneita_smi_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->idoneita_smi_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->idoneita_smi_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('idoneita_smi') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Lavoratore 4h -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="lavoratore_4h"
                        onclick="apriModalData({{ $m->id }}, 'lavoratore_4h', '{{ $scadenza->lavoratore_4h_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->lavoratore_4h_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->lavoratore_4h_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('lavoratore_4h') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Lavoratore 8h -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="lavoratore_8h"
                        onclick="apriModalData({{ $m->id }}, 'lavoratore_8h', '{{ $scadenza->lavoratore_8h_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->lavoratore_8h_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->lavoratore_8h_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('lavoratore_8h') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Preposto -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="preposto"
                        onclick="apriModalData({{ $m->id }}, 'preposto', '{{ $scadenza->preposto_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->preposto_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->preposto_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('preposto') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Dirigenti -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="dirigenti"
                        onclick="apriModalData({{ $m->id }}, 'dirigenti', '{{ $scadenza->dirigenti_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->dirigenti_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->dirigenti_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('dirigenti') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Teatro Operativo -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="poligono_approntamento"
                        onclick="apriModalData({{ $m->id }}, 'poligono_approntamento', '{{ $scadenza->poligono_approntamento_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->poligono_approntamento_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->poligono_approntamento_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('poligono_approntamento') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>

                    <!-- Poligono Mantenimento -->
                    <td class="@if($canEdit) scadenza-cell @endif"
                        @if($canEdit)
                        data-militare-id="{{ $m->id }}"
                        data-tipo="poligono_mantenimento"
                        onclick="apriModalData({{ $m->id }}, 'poligono_mantenimento', '{{ $scadenza->poligono_mantenimento_data_conseguimento ?? '' }}')"
                        @endif>
                        @if($scadenza && $scadenza->poligono_mantenimento_data_conseguimento)
                            <small class="d-block">Cons: {{ \Carbon\Carbon::parse($scadenza->poligono_mantenimento_data_conseguimento)->format('d/m/Y') }}</small>
                            <strong>Scad: {{ $scadenza->formatScadenza('poligono_mantenimento') }}</strong>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                @include('components.no-results', ['showButton' => true, 'buttonUrl' => route('scadenze.index')])
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal per inserimento data -->
<div class="modal fade" id="modalData" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt"></i> Inserisci Data Conseguimento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalMilitareId">
                <input type="hidden" id="modalTipo">
                
                <div class="mb-3">
                    <label for="modalDataConseguimento" class="form-label fw-bold">Data Conseguimento</label>
                    <input type="date" class="form-control" id="modalDataConseguimento">
                    <small class="text-muted">Lascia vuoto per rimuovere la data</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" onclick="salvaData()">
                    <i class="fas fa-save"></i> Salva
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let modalBootstrap;

document.addEventListener('DOMContentLoaded', function() {
    modalBootstrap = new bootstrap.Modal(document.getElementById('modalData'));
    
    // Forza l'inizializzazione dei filtri se non già fatto
    if (window.SUGECO && window.SUGECO.Filters) {
        window.SUGECO.Filters.init();
    }
    
    // Inizializza il nuovo sistema di ricerca
    if (window.SUGECO && window.SUGECO.Search) {
        window.SUGECO.Search.init();
    }
});

function apriModalData(militareId, tipo, dataAttuale) {
    document.getElementById('modalMilitareId').value = militareId;
    document.getElementById('modalTipo').value = tipo;
    document.getElementById('modalDataConseguimento').value = dataAttuale || '';
    
    modalBootstrap.show();
}

function salvaData() {
    const militareId = document.getElementById('modalMilitareId').value;
    const tipo = document.getElementById('modalTipo').value;
    const data = document.getElementById('modalDataConseguimento').value;

    // Mostra loading
    const btnSalva = event.target;
    const testoOriginale = btnSalva.innerHTML;
    btnSalva.disabled = true;
    btnSalva.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvataggio...';

    const baseUrl = '{{ url("/") }}';
    const url = baseUrl + '/scadenze/' + militareId + '/update-singola';

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ tipo, data })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Aggiorna la cella
            const cell = document.querySelector(`[data-militare-id="${militareId}"][data-tipo="${tipo}"]`);
            if (cell) {
                cell.style = result.colore;
                if (data) {
                    cell.innerHTML = `
                        <small class="d-block">Cons: ${result.data_conseguimento}</small>
                        <strong>Scad: ${result.data_scadenza}</strong>
                    `;
                } else {
                    cell.innerHTML = '<span class="text-muted">-</span>';
                }
            }

            modalBootstrap.hide();
        } else {
            alert('Errore: ' + result.message);
        }
    })
    .catch(error => {
        console.error('Errore:', error);
        alert('Errore durante il salvataggio');
    })
    .finally(() => {
        btnSalva.disabled = false;
        btnSalva.innerHTML = testoOriginale;
    });
}
</script>
@endpush
