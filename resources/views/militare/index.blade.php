@extends('layouts.app')
@section('title', 'Anagrafica - SUGECO')

@section('content')
<style>
/* Effetto hover sulle righe come nel CPT */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

/* Assicura che l'hover funzioni anche con le celle inline */
.table tbody tr:hover td {
    background-color: transparent !important;
}

/* Bordi squadrati per le celle come nel CPT */
.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
}

/* Uniforma gli stili dei form controls */
.form-control, .form-select {
    border-radius: 0 !important;
}

/* Stili per i filtri come nel CPT */
.filter-select {
    border-radius: 0 !important;
}

/* Assicura che la tabella abbia lo stesso comportamento del CPT */
.table-container {
    overflow-x: auto !important;
    overflow-y: auto !important;
}

.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed !important;
}

/* Sfondo leggermente off-white per la tabella */
.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

/* Bordi leggermente più scuri dell'hover */
.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Stili per i link come nel CPT */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
}

.link-name:hover {
    color: #0a2342;
    text-decoration: none;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}

/* Ridimensiona le icone nei pulsanti azioni */
.btn-sm i.fas {
    font-size: 0.85rem;
}

/* Stili minimal per le patenti */
.patenti-container {
    padding: 2px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.patenti-row {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.patenti-container .form-check {
    padding-left: 0;
    margin-bottom: 0 !important;
}

.patenti-container .form-check-input {
    width: 14px;
    height: 14px;
    border: 1px solid #adb5bd;
    border-radius: 2px;
    margin-right: 3px;
    transition: all 0.15s ease;
}

.patenti-container .form-check-input:checked {
    background-color: #0a2342;
    border-color: #0a2342;
}

.patenti-container .form-check-label {
    color: #6c757d;
    user-select: none;
    font-size: 0.8rem;
    line-height: 1.2;
}

.patenti-container .form-check-input:checked + .form-check-label {
    color: #0a2342;
}
</style>
@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['compagnia', 'plotone_id', 'grado_id', 'polo_id', 'mansione_id', 'nos_status', 'email_istituzionale', 'telefono'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Anagrafica</h1>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <!-- Bottone filtri -->
    <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
        <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
        <span id="toggleFiltersText">
            {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
        </span>
    </button>
    
    <div class="search-container" style="position: relative; width: 320px;">
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
    
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-primary">{{ $militari->count() }} militari</span>
        
        @can('anagrafica.create')
        <a href="{{ route('anagrafica.create') }}" class="btn btn-success" style="border-radius: 6px !important;">
            <i class="fas fa-plus me-2"></i>Nuovo Militare
        </a>
        @endcan
    </div>
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
            <form id="filtroForm" action="{{ route('anagrafica.index') }}" method="GET">
                {{-- Prima riga filtri --}}
                <div class="row mb-3">
                    {{-- Filtro Compagnia --}}
                    <div class="col-md-3">
                        <label for="compagnia" class="form-label">
                            <i class="fas fa-flag me-1"></i> Compagnia
                        </label>
                        <div class="select-wrapper">
                            <select name="compagnia" id="compagnia" class="form-select filter-select {{ request()->filled('compagnia') ? 'applied' : '' }}">
                                <option value="">Tutte le compagnie</option>
                                @php
                                    $compagnieMap = [
                                        1 => '124',
                                        2 => '110',
                                        3 => '127'
                                    ];
                                @endphp
                                @foreach($compagnieMap as $compagniaId => $compagniaNumero)
                                    <option value="{{ $compagniaId }}" {{ request('compagnia') == $compagniaId ? 'selected' : '' }}>{{ $compagniaNumero }}</option>
                                @endforeach
                            </select>
                            @if(request()->filled('compagnia'))
                                <span class="clear-filter" data-filter="compagnia" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Plotone --}}
                    <div class="col-md-3">
                        <label for="plotone_id" class="form-label">
                            <i class="fas fa-users me-1"></i> Plotone
                        </label>
                        <div class="select-wrapper">
                            <select name="plotone_id" id="plotone_id" class="form-select filter-select {{ request()->filled('plotone_id') ? 'applied' : '' }}">
                                <option value="">Tutti i plotoni</option>
                                @foreach($plotoni as $plotone)
                                    <option value="{{ $plotone->id }}" {{ request('plotone_id') == $plotone->id ? 'selected' : '' }}>
                                        {{ $plotone->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('plotone_id'))
                                <span class="clear-filter" data-filter="plotone_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Grado --}}
                    <div class="col-md-3">
                        <label for="grado_id" class="form-label">
                            <i class="fas fa-medal me-1"></i> Grado
                        </label>
                        <div class="select-wrapper">
                            <select name="grado_id" id="grado_id" class="form-select filter-select {{ request()->filled('grado_id') ? 'applied' : '' }}">
                                <option value="">Tutti i gradi</option>
                                @foreach($gradi as $grado)
                                    <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                                        {{ $grado->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('grado_id'))
                                <span class="clear-filter" data-filter="grado_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Ufficio (Polo) --}}
                    <div class="col-md-3">
                        <label for="polo_id" class="form-label">
                            <i class="fas fa-building me-1"></i> Ufficio
                        </label>
                        <div class="select-wrapper">
                            <select name="polo_id" id="polo_id" class="form-select filter-select {{ request()->filled('polo_id') ? 'applied' : '' }}">
                                <option value="">Tutti gli uffici</option>
                                @foreach($poli as $polo)
                                    <option value="{{ $polo->id }}" {{ request('polo_id') == $polo->id ? 'selected' : '' }}>
                                        {{ $polo->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('polo_id'))
                                <span class="clear-filter" data-filter="polo_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Seconda riga filtri --}}
                <div class="row">
                    {{-- Filtro Incarico (Mansione) --}}
                    <div class="col-md-3">
                        <label for="mansione_id" class="form-label">
                            <i class="fas fa-briefcase me-1"></i> Incarico
                        </label>
                        <div class="select-wrapper">
                            <select name="mansione_id" id="mansione_id" class="form-select filter-select {{ request()->filled('mansione_id') ? 'applied' : '' }}">
                                <option value="">Tutti gli incarichi</option>
                                @foreach(\App\Models\Mansione::all() as $mansione)
                                    <option value="{{ $mansione->id }}" {{ request('mansione_id') == $mansione->id ? 'selected' : '' }}>
                                        {{ $mansione->nome }}
                                    </option>
                                @endforeach
                            </select>
                            @if(request()->filled('mansione_id'))
                                <span class="clear-filter" data-filter="mansione_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro NOS --}}
                    <div class="col-md-3">
                        <label for="nos_status" class="form-label">
                            <i class="fas fa-check-circle me-1"></i> NOS
                        </label>
                        <div class="select-wrapper">
                            <select name="nos_status" id="nos_status" class="form-select filter-select {{ request()->filled('nos_status') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="si" {{ request('nos_status') == 'si' ? 'selected' : '' }}>SI</option>
                                <option value="no" {{ request('nos_status') == 'no' ? 'selected' : '' }}>NO</option>
                                <option value="da richiedere" {{ request('nos_status') == 'da richiedere' ? 'selected' : '' }}>Da Richiedere</option>
                                <option value="non previsto" {{ request('nos_status') == 'non previsto' ? 'selected' : '' }}>Non Previsto</option>
                                <option value="in attesa" {{ request('nos_status') == 'in attesa' ? 'selected' : '' }}>In Attesa</option>
                            </select>
                            @if(request()->filled('nos_status'))
                                <span class="clear-filter" data-filter="nos_status" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Email Istituzionale --}}
                    <div class="col-md-3">
                        <label for="email_istituzionale" class="form-label">
                            <i class="fas fa-envelope me-1"></i> Email Istituzionale
                        </label>
                        <div class="select-wrapper">
                            <select name="email_istituzionale" id="email_istituzionale" class="form-select filter-select {{ request()->filled('email_istituzionale') ? 'applied' : '' }}">
                                <option value="">Tutte</option>
                                <option value="registrata" {{ request('email_istituzionale') == 'registrata' ? 'selected' : '' }}>Registrata</option>
                                <option value="non_registrata" {{ request('email_istituzionale') == 'non_registrata' ? 'selected' : '' }}>Non Registrata</option>
                            </select>
                            @if(request()->filled('email_istituzionale'))
                                <span class="clear-filter" data-filter="email_istituzionale" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Filtro Cellulare --}}
                    <div class="col-md-3">
                        <label for="telefono" class="form-label">
                            <i class="fas fa-phone me-1"></i> Cellulare
                        </label>
                        <div class="select-wrapper">
                            <select name="telefono" id="telefono" class="form-select filter-select {{ request()->filled('telefono') ? 'applied' : '' }}">
                                <option value="">Tutti</option>
                                <option value="registrato" {{ request('telefono') == 'registrato' ? 'selected' : '' }}>Registrato</option>
                                <option value="non_registrato" {{ request('telefono') == 'non_registrato' ? 'selected' : '' }}>Non Registrato</option>
                            </select>
                            @if(request()->filled('telefono'))
                                <span class="clear-filter" data-filter="telefono" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('anagrafica.index') }}" class="btn btn-danger">
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
<div class="table-container" style="position: relative; height: 600px; overflow: auto; overflow-x: auto;">
    <!-- Intestazione fissa -->
     <div class="table-header-fixed" style="position: sticky; top: 0; z-index: 10; background: white;">
         <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: 2480px; min-width: 2480px;">
             <colgroup>
                 <col style="width:160px">
                 <col style="width:200px">
                 <col style="width:230px">
                 <col style="width:170px">
                 <col style="width:190px">
                 <col style="width:190px">
                 <col style="width:210px">
                 <col style="width:180px">
                 <col style="width:140px">
                 <col style="width:150px">
                 <col style="width:150px">
                 <col style="width:270px">
                 <col style="width:210px">
                 <col style="width:150px">
             </colgroup>
            <thead class="table-dark" style="user-select:none;">
                <tr>
                    <th class="text-center">Compagnia</th>
                    <th class="text-center">Grado</th>
                    <th class="text-center">Cognome</th>
                    <th class="text-center">Nome</th>
                    <th class="text-center">Plotone</th>
                    <th class="text-center">Ufficio</th>
                    <th class="text-center">Incarico</th>
                    <th class="text-center">Patenti</th>
                    <th class="text-center">NOS</th>
                    <th class="text-center">Anzianità</th>
                    <th class="text-center">Data di Nascita</th>
                    <th class="text-center">Email Istituzionale</th>
                    <th class="text-center">Cellulare</th>
                    <th class="text-center">Azioni</th>
                </tr>
            </thead>
        </table>
    </div>
    
    <!-- Corpo scrollabile -->
     <div class="table-body-scroll">
         <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: 2480px; min-width: 2480px;">
             <colgroup>
                 <col style="width:160px">
                 <col style="width:200px">
                 <col style="width:230px">
                 <col style="width:170px">
                 <col style="width:190px">
                 <col style="width:190px">
                 <col style="width:210px">
                 <col style="width:180px">
                 <col style="width:140px">
                 <col style="width:150px">
                 <col style="width:150px">
                 <col style="width:270px">
                 <col style="width:210px">
                 <col style="width:150px">
             </colgroup>
            <tbody id="militariTableBody">
            @forelse($militari as $m)
                <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}" data-update-url="{{ route('anagrafica.update-field', $m->id) }}">
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field compagnia-select" data-field="compagnia" data-militare-id="{{ $m->id }}" data-row-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            @php
                                $compagnieMap = [
                                    1 => '124',
                                    2 => '110', 
                                    3 => '127'
                                ];
                            @endphp
                            @foreach($compagnieMap as $compagniaId => $compagniaNumero)
                                <option value="{{ $compagniaId }}" {{ $m->compagnia_id == $compagniaId ? 'selected' : '' }}>{{ $compagniaNumero }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field" data-field="grado_id" data-militare-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            @foreach($gradi as $grado)
                                <option value="{{ $grado->id }}" {{ $m->grado_id == $grado->id ? 'selected' : '' }}>
                                    {{ $grado->abbreviazione ?? $grado->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                            {{ $m->cognome }}
                        </a>
                    </td>
                    <td>
                        {{ $m->nome }}
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field plotone-select" data-field="plotone_id" data-militare-id="{{ $m->id }}" data-row-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            @foreach($plotoni as $plotone)
                                <option value="{{ $plotone->id }}" data-compagnia-id="{{ $plotone->compagnia_id }}" {{ $m->plotone_id == $plotone->id ? 'selected' : '' }}>
                                    {{ $plotone->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field" data-field="polo_id" data-militare-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            @foreach($poli as $polo)
                                <option value="{{ $polo->id }}" {{ $m->polo_id == $polo->id ? 'selected' : '' }}>
                                    {{ $polo->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field" data-field="mansione_id" data-militare-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            @foreach(\App\Models\Mansione::all() as $mansione)
                                <option value="{{ $mansione->id }}" {{ $m->mansione_id == $mansione->id ? 'selected' : '' }}>
                                    {{ $mansione->nome }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center" style="padding: 4px;">
                        <div class="patenti-container">
                            @php
                                $patentiMilitare = $m->patenti->pluck('categoria')->toArray();
                            @endphp
                            <div class="patenti-row">
                                @foreach(['2', '3'] as $patente)
                                    <div class="form-check form-check-inline mb-0">
                                        <input type="checkbox" 
                                               class="form-check-input patente-checkbox" 
                                               id="patente_{{ $m->id }}_{{ $patente }}"
                                               data-militare-id="{{ $m->id }}" 
                                               data-patente="{{ $patente }}" 
                                               {{ in_array($patente, $patentiMilitare) ? 'checked' : '' }}
                                               {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}
                                               style="cursor: pointer;">
                                        <label class="form-check-label" 
                                               for="patente_{{ $m->id }}_{{ $patente }}" 
                                               style="cursor: pointer;">
                                            {{ $patente }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            <div class="patenti-row">
                                @foreach(['4', '5', '6'] as $patente)
                                    <div class="form-check form-check-inline mb-0">
                                        <input type="checkbox" 
                                               class="form-check-input patente-checkbox" 
                                               id="patente_{{ $m->id }}_{{ $patente }}"
                                               data-militare-id="{{ $m->id }}" 
                                               data-patente="{{ $patente }}" 
                                               {{ in_array($patente, $patentiMilitare) ? 'checked' : '' }}
                                               {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}
                                               style="cursor: pointer;">
                                        <label class="form-check-label" 
                                               for="patente_{{ $m->id }}_{{ $patente }}" 
                                               style="cursor: pointer;">
                                            {{ $patente }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <select class="form-select form-select-sm editable-field" data-field="nos_status" data-militare-id="{{ $m->id }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                            <option value="">--</option>
                            <option value="si" {{ $m->nos_status == 'si' ? 'selected' : '' }}>SI</option>
                            <option value="no" {{ $m->nos_status == 'no' ? 'selected' : '' }}>NO</option>
                            <option value="da richiedere" {{ $m->nos_status == 'da richiedere' ? 'selected' : '' }}>Da Richiedere</option>
                            <option value="non previsto" {{ $m->nos_status == 'non previsto' ? 'selected' : '' }}>Non Previsto</option>
                            <option value="in attesa" {{ $m->nos_status == 'in attesa' ? 'selected' : '' }}>In Attesa</option>
                        </select>
                    </td>
                     <td>
                         <input type="date" class="form-control form-control-sm editable-field" data-field="anzianita" data-militare-id="{{ $m->id }}" value="{{ $m->anzianita ? $m->anzianita->format('Y-m-d') : '' }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                     </td>
                    <td>
                        <input type="date" class="form-control form-control-sm editable-field" data-field="data_nascita" data-militare-id="{{ $m->id }}" value="{{ $m->data_nascita ? $m->data_nascita->format('Y-m-d') : '' }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    </td>
                    <td>
                        <input type="email" class="form-control form-control-sm editable-field" data-field="email_istituzionale" data-militare-id="{{ $m->id }}" value="{{ $m->email_istituzionale }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    </td>
                    <td>
                        <input type="tel" class="form-control form-control-sm editable-field" data-field="telefono" data-militare-id="{{ $m->id }}" value="{{ $m->telefono }}" style="width: 100%;" {{ auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    </td>
                     <td class="text-center">
                         <div class="d-flex justify-content-center gap-1">
                             <a href="{{ route('anagrafica.show', $m->id) }}" class="btn btn-sm btn-outline-primary" title="Visualizza">
                                 <i class="fas fa-eye"></i>
                             </a>
                             @can('anagrafica.edit')
                             <a href="{{ route('anagrafica.edit', $m->id) }}" class="btn btn-sm btn-outline-warning" title="Modifica">
                                 <i class="fas fa-edit"></i>
                             </a>
                             @endcan
                             @can('anagrafica.delete')
                             <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $m->id }})" title="Elimina">
                                 <i class="fas fa-trash"></i>
                             </button>
                             @endcan
                         </div>
                     </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun militare trovato.</p>
                            <a href="{{ route('anagrafica.index') }}" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>


<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcelBtn" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

<!-- Modal per conferma eliminazione -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-body text-center p-5">
        <div class="mb-4">
          <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
            <i class="fas fa-trash-alt text-danger" style="font-size: 2rem;"></i>
          </div>
        </div>
        
        <h4 class="mb-3">Eliminare questo militare?</h4>
        <p class="text-muted mb-2">Stai per eliminare:</p>
        <h5 class="fw-bold mb-4" id="militare-to-delete"></h5>
        
        <div class="alert alert-danger bg-danger bg-opacity-10 border-0 mb-4">
          <small><i class="fas fa-exclamation-circle me-1"></i> Questa azione è irreversibile</small>
        </div>
        
        <div class="d-grid gap-2">
          <form id="deleteForm" action="" method="POST">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger w-100 mb-2">
                <i class="fas fa-trash-alt me-2"></i>Sì, Elimina
              </button>
          </form>
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<!-- Script per inizializzazione moduli -->
<script>
// Funzione per confermare l'eliminazione del militare
function confirmDelete(militareId) {
    // Ottieni i dati del militare dalla riga
    const row = document.getElementById('militare-' + militareId);
    if (!row) {
        console.error('Riga militare non trovata:', militareId);
        return;
    }
    
    const cognome = row.querySelector('a.link-name').textContent.trim();
    const grado = row.querySelector('select[data-field="grado_id"] option:checked').textContent.trim();
    
    // Imposta il nome del militare nel modal
    document.getElementById('militare-to-delete').textContent = grado + ' ' + cognome;
    
    // Imposta l'action del form di eliminazione con la rotta corretta
    const deleteForm = document.getElementById('deleteForm');
    const deleteUrl = '{{ url("anagrafica") }}/' + militareId;
    deleteForm.action = deleteUrl;
    
    console.log('Form action impostato a:', deleteUrl);
    
    // Mostra il modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    // Forza l'inizializzazione dei filtri se non già fatto
    if (window.SUGECO && window.SUGECO.Filters) {
        window.SUGECO.Filters.init();
    }
    
    // Inizializza il nuovo sistema di ricerca
    if (window.SUGECO && window.SUGECO.Search) {
        window.SUGECO.Search.init();
    }
    
    // Export Excel
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Raccoglie tutti i parametri del form attuale
            const form = document.getElementById('filtroForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            // Aggiunge tutti i parametri del form
            for (let [key, value] of formData.entries()) {
                if (value) {
                    params.append(key, value);
                }
            }
            
            // Crea l'URL per l'export
            const exportUrl = '{{ route("anagrafica.export-excel") }}?' + params.toString();
            
            // Redirect per scaricare il file (stesso meccanismo del CPT)
            window.location.href = exportUrl;
        });
    }
    
    // Gestione editing inline dei campi
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('editable-field')) {
            const field = e.target.getAttribute('data-field');
            const militareId = e.target.getAttribute('data-militare-id');
            const value = e.target.value;
            
            // Invia la modifica via AJAX (usa la rotta dalla riga per evitare 404)
            const row = e.target.closest('tr.militare-row');
            const updateUrl = row ? row.getAttribute('data-update-url') : null;
            if (!updateUrl) {
                console.error('URL aggiornamento non trovato sulla riga');
                return;
            }
            fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    field: field,
                    value: value
                })
            })
            .then(response => {
                // Gestione specifica per 403 (permessi mancanti)
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Non hai i permessi per eseguire questa azione');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostra un feedback visivo
                    e.target.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        e.target.style.backgroundColor = '';
                    }, 1000);
                    
                    // Se è stata cambiata la compagnia, resetta e filtra i plotoni
                    if (field === 'compagnia' && data.plotone_reset) {
                        const rowId = e.target.getAttribute('data-row-id');
                        const plotoneSelect = document.querySelector(`.plotone-select[data-row-id="${rowId}"]`);
                        if (plotoneSelect) {
                            plotoneSelect.value = ''; // Resetta il plotone
                            filterPlotoniByCompagnia(plotoneSelect, value); // Filtra i plotoni per la nuova compagnia
                        }
                    }
                } else {
                    // Feedback visivo negativo
                    e.target.style.backgroundColor = '#f8d7da';
                    setTimeout(() => {
                        e.target.style.backgroundColor = '';
                    }, 2000);
                    
                    // Messaggio non invasivo (solo console)
                    console.warn('Aggiornamento non riuscito:', data.message || 'Errore sconosciuto');
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                
                // Feedback visivo per errore
                e.target.style.backgroundColor = '#f8d7da';
                setTimeout(() => {
                    e.target.style.backgroundColor = '';
                }, 2000);
                
                // Non mostrare più alert, solo console per debugging
                // L'utente vede il campo tornare al colore originale = operazione non riuscita
            });
        }
        
        // Gestione patenti checkbox
        if (e.target.classList.contains('patente-checkbox')) {
            const militareId = e.target.getAttribute('data-militare-id');
            const patente = e.target.getAttribute('data-patente');
            const isChecked = e.target.checked;
            const checkbox = e.target;
            const formCheck = checkbox.closest('.form-check');
            
            const url = '{{ url("anagrafica") }}/' + militareId + '/patenti/' + (isChecked ? 'add' : 'remove');
            
            // Feedback visivo immediato
            formCheck.style.transition = 'transform 0.2s ease';
            formCheck.style.transform = 'scale(1.05)';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    patente: patente
                })
            })
            .then(response => {
                // Gestione specifica per 403 (permessi mancanti)
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Non hai i permessi per eseguire questa azione');
                    });
                }
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Feedback minimal - solo ripristina scala
                    formCheck.style.transform = 'scale(1)';
                } else {
                    // Ripristina lo stato precedente in caso di errore
                    checkbox.checked = !isChecked;
                    formCheck.style.transform = 'scale(1)';
                    
                    // Messaggio non invasivo (solo console)
                    console.warn('Aggiornamento patente non riuscito:', data.message || 'Errore sconosciuto');
                }
            })
            .catch(error => {
                console.error('Errore durante aggiornamento patente:', error);
                
                // Ripristina lo stato precedente
                checkbox.checked = !isChecked;
                formCheck.style.transform = 'scale(1)';
                
                // Non mostrare più alert, solo console per debugging
            });
        }
    });
    
    // Funzione per filtrare i plotoni in base alla compagnia
    function filterPlotoniByCompagnia(plotoneSelect, compagniaId) {
        const options = plotoneSelect.querySelectorAll('option');
        
        options.forEach(option => {
            if (option.value === '') {
                // Mantieni sempre l'opzione vuota "--"
                option.style.display = '';
                return;
            }
            
            const optionCompagniaId = option.getAttribute('data-compagnia-id');
            
            if (!compagniaId || optionCompagniaId == compagniaId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    }
    
    // Inizializza i filtri dei plotoni all'avvio
    document.querySelectorAll('.plotone-select').forEach(plotoneSelect => {
        const rowId = plotoneSelect.getAttribute('data-row-id');
        const compagniaSelect = document.querySelector(`.compagnia-select[data-row-id="${rowId}"]`);
        
        if (compagniaSelect) {
            const compagniaId = compagniaSelect.value;
            if (compagniaId) {
                filterPlotoniByCompagnia(plotoneSelect, compagniaId);
            }
        }
    });
});
</script>
<!-- File JavaScript per pagina militare -->
<script src="{{ asset('js/militare.js') }}"></script>
@endpush
