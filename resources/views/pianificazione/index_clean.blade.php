@extends('layouts.app')

@section('title', 'Pianificazione Mensile')

@section('content')
<script src="{{ asset('js/pianificazione-test.js') }}"></script>

<div class="container-fluid">
    <!-- Header con controlli -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                Pianificazione Mensile - {{ $mese }}/{{ $anno }}
            </h1>
            <p class="text-muted mb-0">Gestione impegni giornalieri dei militari</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group" role="group">
                <a href="{{ route('pianificazione.index', ['mese' => $mese - 1, 'anno' => $anno]) }}" 
                   class="btn btn-outline-secondary">
                    <i class="fas fa-chevron-left"></i> Mese Precedente
                </a>
                <a href="{{ route('pianificazione.index', ['mese' => $mese + 1, 'anno' => $anno]) }}" 
                   class="btn btn-outline-secondary">
                    Mese Successivo <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Statistiche rapide -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Militari Totali</h6>
                            <h3 class="mb-0">{{ count($militariConPianificazione) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Giorni del Mese</h6>
                            <h3 class="mb-0">{{ count($giorniMese) }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Pianificazioni</h6>
                            <h3 class="mb-0">{{ $statistiche['totale_pianificazioni'] ?? 0 }}</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tasks fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Copertura</h6>
                            <h3 class="mb-0">{{ round(($statistiche['copertura'] ?? 0), 1) }}%</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-pie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabella principale stile CPT -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>
                Calendario Impegni - {{ count($militariConPianificazione) }} militari
                <small class="text-muted">(Scroll per vedere tutti)</small>
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" id="toggleWeekends">
                    <i class="fas fa-calendar-week me-1"></i>
                    Nascondi Weekend
                </button>
                <button class="btn btn-sm btn-outline-success" id="exportExcel">
                    <i class="fas fa-file-excel me-1"></i>
                    Esporta Excel
                </button>
            </div>
        </div>
    </div>
    
    @php
        // Check if any filters are active
        $activeFilters = [];
        foreach(['grado_id', 'plotone_id', 'ufficio_id', 'incarico', 'impegno', 'giorno'] as $filter) {
            if(request()->filled($filter)) $activeFilters[] = $filter;
        }
        $hasActiveFilters = count($activeFilters) > 0;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        
        <div>
            <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}">
                <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
                <span id="toggleFiltersText">
                    {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
                </span>
            </button>
        </div>
        
        <div class="search-container" style="position: relative; width: 320px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
            <input type="text" 
                   id="searchMilitare" 
                   class="form-control" 
                   placeholder="Cerca militare..." 
                   aria-label="Cerca militare" 
                   style="padding-left: 40px; border-radius: 20px;"
                   data-search-type="militare"
                   data-target-container="pianificazioneTable">
        </div>
        
        <div>
            <span class="badge bg-primary">{{ count($militariConPianificazione) }} militari</span>
        </div>
    </div>

    <!-- Filtri con sezione migliorata -->
    <div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
        @include('components.filters.filter-pianificazione')
    </div>
    
    <div class="card-body p-0">
        <div class="table-container" style="border: 1px solid #dee2e6;">
            <!-- Header fisso -->
            <div class="table-header-fixed">
                <table class="table table-sm table-bordered mb-0" style="width: 2340px; min-width: 2340px;">
                    <thead class="table-dark">
                    <tr>
                        <!-- Colonne fisse per info militare -->
                    <th class="bg-dark text-white" style="min-width: 160px; width: 160px;">Grado</th>
                    <th class="bg-dark text-white" style="min-width: 140px; width: 140px;">Cognome</th>
                    <th class="bg-dark text-white" style="min-width: 120px; width: 120px;">Nome</th>
                        <th class="bg-dark text-white" style="min-width: 80px; width: 80px;">Plotone</th>
                        <th class="bg-dark text-white" style="min-width: 100px; width: 100px;">Ufficio</th>
                        <th class="bg-dark text-white" style="min-width: 120px; width: 120px;">Incarico</th>
                        <th class="bg-dark text-white" style="min-width: 130px; width: 130px;">Approntamento</th>
                        
                        <!-- Colonne per ogni giorno del mese -->
                        @foreach($giorniMese as $giorno)
                            <th class="text-center {{ $giorno['is_weekend'] ? 'weekend-column bg-secondary' : '' }} {{ $giorno['is_today'] ? 'today-column bg-warning' : '' }}" 
                                style="min-width: 40px; max-width: 40px; padding: 4px 2px;">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="fw-bold" style="font-size: 12px;">{{ $giorno['giorno'] }}</div>
                                    <div class="opacity-75" style="font-size: 9px;">{{ substr($giorno['nome_giorno'], 0, 1) }}</div>
                                </div>
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                </table>
            </div>
            
            <!-- Body scrollabile -->
            <div class="table-body-scroll" style="max-height: 60vh; overflow: auto;">
                <table class="table table-sm table-bordered mb-0" id="pianificazioneTable" style="width: 2340px; min-width: 2340px;">
                    <tbody>
                    @foreach($militariConPianificazione as $index => $item)
                        <tr class="militare-row" data-militare-id="{{ $item['militare']->id }}">
                            <!-- Info militare (colonne fisse) -->
                            <td class="fw-bold" style="width: 160px; padding: 4px 6px;">
                                {{ $item['militare']->grado->nome ?? '-' }}
                            </td>
                            <td style="width: 140px; padding: 4px 6px;">
                                <a href="{{ route('pianificazione.militare', $item['militare']) }}?mese={{ $mese }}&anno={{ $anno }}" 
                                   class="text-decoration-none fw-bold">
                                    {{ $item['militare']->cognome }}
                                </a>
                            </td>
                            <td style="width: 120px; padding: 4px 6px;">
                                {{ $item['militare']->nome }}
                            </td>
                            <td style="width: 80px; padding: 4px 6px;">
                                {{ str_replace(['° Plotone', 'Plotone'], ['°', ''], $item['militare']->plotone->nome ?? '-') }}
                            </td>
                            <td style="width: 100px; padding: 4px 6px;">
                                <small>{{ $item['militare']->polo->nome ?? '-' }}</small>
                            </td>
                            <td style="width: 120px; padding: 4px 6px;">
                                <small>{{ $item['militare']->mansione->nome ?? ($item['militare']->ruolo->nome ?? '-') }}</small>
                            </td>
                            <td style="width: 130px; padding: 4px 6px;">
                                <small>{{ $item['militare']->approntamentoPrincipale->nome ?? '-' }}</small>
                            </td>
                            
                            <!-- Celle per ogni giorno -->
                            @foreach($giorniMese as $giorno)
                                @php
                                    $pianificazione = $item['pianificazioni'][$giorno['giorno']] ?? null;
                                    $codice = '';
                                    $colore = 'light';
                                    $tooltip = 'Nessuna pianificazione';
                                    
                                    if ($pianificazione) {
                                        if ($pianificazione->tipoServizio) {
                                            $codice = $pianificazione->tipoServizio->codice;
                                            $gerarchia = $pianificazione->tipoServizio->codiceGerarchia;
                                            // Usa la mappa dei codici per nome e colore
                                            $tooltip = $pianificazione->tipoServizio->nome ?? $codice;
                                            
                                            // Determina il colore basato sul codice
                                            $colore = match($codice) {
                                                // ASSENTE - Giallo
                                                'LS', 'LO', 'LM', 'P', 'TIR', 'TRAS' => 'cpt-giallo',
                                                
                                                // PROVVEDIMENTI MEDICO SANITARI - Rosso
                                                'RMD', 'LC', 'IS' => 'cpt-rosso',
                                                
                                                // SERVIZIO - Verde
                                                'S-G1', 'S-G2', 'S-SA', 'S-CD1', 'S-CD2', 'S-SG', 'S-CG', 'S-UI', 'S-UP', 'S-AE', 'S-ARM', 'SI-GD', 'SI', 'SI-VM', 'S-PI' => 'cpt-verde',
                                                
                                                // OPERAZIONE - Rosso
                                                'TO' => 'cpt-rosso',
                                                
                                                // ADD./APP./CATTEDRE - Giallo
                                                'APS1', 'APS2', 'APS3', 'APS4', 'AL-ELIX', 'AL-MCM', 'AL-BLS', 'AL-CIED', 'AL-SM', 'AL-RM', 'AL-RSPP', 'AL-LEG', 'AL-SEA', 'AL-MI', 'AL-PO', 'AL-PI', 'AP-M', 'AP-A', 'AC-SW', 'AC', 'PEFO' => 'cpt-giallo',
                                                
                                                // SUPP.CIS/EXE - Verde
                                                'EXE' => 'cpt-verde',
                                                
                                                default => 'light'
                                            };
                                        } else {
                                            // Pianificazione esiste ma senza tipo servizio (Nessun impegno)
                                            $codice = '';
                                            $colore = 'light';
                                            $tooltip = 'Nessun impegno';
                                        }
                                    } else {
                                        // Militari senza pianificazione: cella vuota
                                        $codice = '';
                                        $colore = 'light';
                                        $tooltip = 'Nessuna pianificazione';
                                    }
                                @endphp
                                
                                <td class="text-center p-1 giorno-cell {{ $giorno['is_weekend'] ? 'weekend-column' : '' }} {{ $giorno['is_today'] ? 'today-column' : '' }}"
                                    data-giorno="{{ $giorno['giorno'] }}"
                                    data-militare-id="{{ $item['militare']->id }}"
                                    data-tipo-servizio-id="{{ $pianificazione->tipo_servizio_id ?? '' }}"
                                    style="min-width: 40px; max-width: 40px; font-size: 11px; cursor: pointer;"
                                    onclick="openEditModal(this)">
                                    
                                    @if($codice)
                                        @php
                                            // Colori CPT esatti inline per garantire che funzionino
                                            $inlineStyle = match($colore) {
                                                'cpt-verde' => 'background-color: #00b050 !important; color: white !important;',
                                                'cpt-giallo' => 'background-color: #ffff00 !important; color: black !important;',
                                                'cpt-rosso' => 'background-color: #ff0000 !important; color: white !important;',
                                                'cpt-arancione' => 'background-color: #ffc000 !important; color: black !important;',
                                                default => ''
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $colore }} codice-badge" 
                                              style="font-size: 9px; padding: 2px 4px; min-width: 28px; {{ $inlineStyle }}"
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top"
                                              title="{{ $tooltip }}">
                                            {{ $codice }}
                                        </span>
                                    @else
                                        <span class="text-muted" style="font-size: 10px;">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal per modificare impegno giornaliero -->
<div class="modal fade" id="editGiornoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Impegno Giornaliero</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editGiornoForm">
                    <input type="hidden" id="editMilitareId" name="militare_id">
                    <input type="hidden" id="editGiorno" name="giorno">
                    <input type="hidden" id="editPianificazioneMensileId" name="pianificazione_mensile_id" value="{{ $pianificazioneMensile->id }}">
                    
                    <div class="mb-3">
                        <label for="editMilitareNome" class="form-label">Militare</label>
                        <input type="text" class="form-control" id="editMilitareNome" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editGiornoLabel" class="form-label">Giorno</label>
                        <input type="text" class="form-control" id="editGiornoLabel" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editTipoServizio" class="form-label">Tipo Servizio</label>
                        <select class="form-select" id="editTipoServizio" name="tipo_servizio_id">
                            <option value="">Nessun impegno</option>
                            @foreach($impegniPerCategoria as $categoria => $impegniCategoria)
                                <optgroup label="{{ $categoria }}">
                                    @foreach($impegniCategoria as $impegno)
                                        <option value="{{ $impegno->codice }}">{{ $impegno->nome }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="saveGiornoBtn">Salva</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* Stili per la tabella con header fisso */
.table-container {
    display: flex;
    flex-direction: column;
    height: 60vh;
}

.table-header-fixed {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 2px solid #dee2e6;
    flex-shrink: 0;
    /* Non permettere scroll indipendente all'header */
    overflow-x: hidden;
    overflow-y: hidden;
}

.table-header-fixed::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.table-body-scroll {
    flex: 1;
    overflow: auto;
    position: relative;
    width: 100%;
    overflow-x: auto;
}

/* Larghezza fissa per le tabelle */
.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed;
    width: 2340px; /* Larghezza fissa per garantire scroll */
    min-width: 2340px;
}

/* Container delle tabelle deve avere overflow */
.table-body-scroll {
    width: 100%;
    overflow-x: auto;
}

/* Stile compatto per la tabella pianificazione */
#pianificazioneTable {
    font-size: 11px;
}

/* Filtri - usa CSS esterno filters.css */

/* Toggle button */
#toggleFilters {
    background: linear-gradient(to right, #0f3a6d, #1a4a7a) !important;
    color: white !important;
    border: none !important;
    padding: 12px 20px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    box-shadow: 0 3px 8px rgba(15, 58, 109, 0.2) !important;
    display: flex !important;
    align-items: center !important;
    position: relative !important;
    overflow: hidden !important;
    transition: all 0.3s ease !important;
}

#toggleFilters:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 6px 15px rgba(15, 58, 109, 0.3) !important;
}

#toggleFilters.active {
    background: linear-gradient(to right, #d4af37, #e6c547) !important;
    color: #0f3a6d !important;
}

#toggleFilters i {
    margin-right: 8px !important;
    transition: transform 0.3s ease !important;
}

#pianificazioneTable th {
    font-size: 11px;
    font-weight: 600;
    padding: 6px 4px;
}

#pianificazioneTable td {
    padding: 3px 4px;
    vertical-align: middle;
}

.weekend-column {
    background-color: #f8f9fa !important;
}

.today-column {
    background-color: #fff3cd !important;
}

.giorno-cell {
    cursor: pointer;
    transition: background-color 0.2s;
}

.giorno-cell:hover {
    background-color: #e9ecef !important;
}

.codice-badge {
    cursor: pointer;
}

.badge-categoria-u { background-color: #0d6efd !important; }
.badge-categoria-su { background-color: #198754 !important; }
.badge-categoria-grad { background-color: #ffc107 !important; color: #000 !important; }

/* Colori CPT esatti */
.cpt-rosso { background-color: #ff0000 !important; color: white !important; }
.cpt-giallo { background-color: #ffff00 !important; color: black !important; }
.cpt-verde { background-color: #00b050 !important; color: white !important; }
.cpt-arancione { background-color: #ffc000 !important; color: black !important; }

/* Bootstrap color overrides */
.bg-secondary { background-color: #6c757d !important; color: white !important; }
.bg-light { background-color: #f8f9fa !important; color: #495057 !important; }
</style>
@endpush

@push('scripts')
{{-- JavaScript spostato nel file pianificazione-test.js --}}
@endpush
