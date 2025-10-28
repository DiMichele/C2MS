@extends('layouts.app')

@section('title', 'CPT')

@section('content')
<script>
// Passa i dati dal PHP al JavaScript
window.pageData = {
    mese: {{ $mese }},
    anno: {{ $anno }},
    mesiItaliani: {
        1: 'Gennaio', 2: 'Febbraio', 3: 'Marzo', 4: 'Aprile',
        5: 'Maggio', 6: 'Giugno', 7: 'Luglio', 8: 'Agosto',
        9: 'Settembre', 10: 'Ottobre', 11: 'Novembre', 12: 'Dicembre'
    }
};
</script>
<script src="{{ asset('js/pianificazione-test.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/filtro-plotoni-compagnia.js') }}?v={{ time() }}"></script>

<div class="container-fluid" style="position: relative; z-index: 1;">
    <!-- Header con controlli -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">
                    CPT
            </h1>
            </div>
        </div>
    </div>
    
            <!-- Selettori data -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-center">
                        <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px; border-radius: 6px !important;">
                    @php
                        $mesiItaliani = [
                            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
                            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
                            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre'
                        ];
                    @endphp
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ $mese == $m ? 'selected' : '' }}>
                            {{ $mesiItaliani[$m] }}
                        </option>
                    @endfor
                </select>
                    <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px; border-radius: 6px !important;">
                        @for($a = 2025; $a <= 2030; $a++)
                        <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endfor
                </select>
            </form>
            </div>
        </div>
    </div>

    <!-- Tabella principale stile CPT -->
    <div class="card" style="background: transparent; border: none; box-shadow: none;">
        <div style="background: transparent; border: none; padding: 0;">
            <!-- Export button removed - now using floating button -->
        </div>
        
        @php
            // Check if any filters are active
            $activeFilters = [];
            foreach(['compagnia', 'grado_id', 'plotone_id', 'patente', 'approntamento_id', 'impegno', 'compleanno', 'giorno'] as $filter) {
                if(request()->filled($filter)) $activeFilters[] = $filter;
            }
            $hasActiveFilters = count($activeFilters) > 0;
        @endphp

        <div class="d-flex justify-content-between align-items-center mb-3" style="background: transparent;">
        
            <div>
                <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
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
                       style="padding: 8px 12px 8px 40px; border-radius: 6px !important;"
                       data-search-type="militare"
                       data-target-container="pianificazioneTable">
            </div>
            
            <div>
                <span class="badge bg-primary counter-badge" style="border-radius: 6px !important;">{{ count($militariConPianificazione) }} militari</span>
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
                <table class="table table-sm table-bordered mb-0" style="width: 3032px; min-width: 3032px; table-layout: fixed;">
                        <thead class="table-dark">
                        <tr>
                            <!-- Colonne fisse per info militare -->
                        <th class="bg-dark text-white" style="width: 160px;">Compagnia</th>
                        <th class="bg-dark text-white" style="width: 200px;">Grado</th>
                        <th class="bg-dark text-white" style="width: 230px;">Cognome</th>
                        <th class="bg-dark text-white" style="width: 170px;">Nome</th>
                            <th class="bg-dark text-white" style="width: 120px;">Plotone</th>
                            <th class="bg-dark text-white" style="width: 112px;">Patente</th>
                            <th class="bg-dark text-white" style="width: 120px;">Approntamento</th>
                            
                            <!-- Colonne per ogni giorno del mese -->
                            @foreach($giorniMese as $giorno)
                                @php
                                    $isWeekend = $giorno['is_weekend'];
                                    $isHoliday = $giorno['is_holiday'];
                                    $isToday = $giorno['is_today'];
                                    $headerStyle = "width: 60px; padding: 4px 2px;";
                                    
                                    // Colori inline per intestazione
                                    $textColor = '';
                                    if ($isWeekend || $isHoliday) {
                                        $textColor = 'color: #dc3545 !important;';
                                    } elseif ($isToday) {
                                        $textColor = 'color: #ff8c00 !important;';
                                    }
                                @endphp
                                <th class="text-center {{ $isWeekend ? 'weekend-column' : '' }} {{ $isHoliday ? 'holiday-column' : '' }} {{ $isToday ? 'today-column' : '' }}" 
                                style="{{ $headerStyle }}">
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="fw-bold" style="font-size: 12px; {{ $textColor }} font-weight: 700 !important; opacity: 1 !important;">{{ $giorno['giorno'] }}</div>
                                        <div style="font-size: 9px; {{ $textColor }} font-weight: 700 !important; opacity: 1 !important;">{{ substr($giorno['nome_giorno'], 0, 1) }}</div>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                        </thead>
                    </table>
                </div>
                
                <!-- Body scrollabile -->
                <div class="table-body-scroll" style="max-height: 60vh; overflow: auto;">
                    <table class="table table-sm table-bordered mb-0" id="pianificazioneTable" style="width: 3032px; min-width: 3032px; table-layout: fixed;">
                        <tbody>
                        @forelse($militariConPianificazione as $index => $item)
                            <tr class="militare-row" data-militare-id="{{ $item['militare']->id }}">
                                <!-- Info militare (colonne fisse) -->
                                <td class="fw-bold text-center" style="width: 160px; padding: 4px 6px;">
                                    {{ $item['militare']->compagnia->numero ?? '-' }}
                                </td>
                                <td class="fw-bold" style="width: 200px; padding: 4px 6px;">
                                    <span title="{{ $item['militare']->grado->nome ?? '-' }}">
                                        {{ $item['militare']->grado->sigla ?? '-' }}
                                    </span>
                                </td>
                                <td style="width: 230px; padding: 4px 6px;">
                                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}"
                                       class="link-name">
                                        {{ $item['militare']->cognome }}
                                    </a>
                                </td>
                                <td style="width: 170px; padding: 4px 6px;">
                                    {{ $item['militare']->nome }}
                                </td>
                                <td class="text-center" style="width: 120px; padding: 4px 6px;">
                                    {{ str_replace(['° Plotone', 'Plotone'], ['°', ''], $item['militare']->plotone->nome ?? '-') }}
                                </td>
                                <td class="text-center" style="width: 112px; padding: 4px 2px; font-size: 0.85rem;">
                                    @php
                                        $patenti = $item['militare']->patenti->pluck('categoria')->toArray();
                                    @endphp
                                    {{ !empty($patenti) ? implode(' ', $patenti) : '-' }}
                                </td>
                                <td style="width: 120px; padding: 4px 6px;">
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
                                                
                                                // SERVIZIO - Verde (include nuovi servizi turno)
                                                'S-G1', 'S-G2', 'S-SA', 'S-CD1', 'S-CD2', 'S-SG', 'S-CG', 'S-UI', 'S-UP', 'S-AE', 'S-ARM', 'SI-GD', 'SI', 'SI-VM', 'S-PI',
                                                'G-BTG', 'NVA', 'CG', 'NS-DA', 'PDT', 'AA', 'VS-CETLI', 'CORR', 'NDI', 'PDT1', 'S-SI' => 'cpt-verde',
                                                
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
                                    
                                    @php
                                        $cellStyle = "width: 60px; font-size: 11px; cursor: pointer;";
                                        
                                        // Aggiungi background-color inline per weekend/festivi/oggi
                                        if ($giorno['is_weekend'] || $giorno['is_holiday']) {
                                            $cellStyle .= " background-color: rgba(255, 0, 0, 0.12);";
                                        } elseif ($giorno['is_today']) {
                                            $cellStyle .= " background-color: rgba(255, 220, 0, 0.20);";
                                        }
                                    @endphp
                                    <td class="text-center giorno-cell {{ $giorno['is_weekend'] ? 'weekend-column' : '' }} {{ $giorno['is_holiday'] ? 'holiday-column' : '' }} {{ $giorno['is_today'] ? 'today-column' : '' }}"
                                        data-giorno="{{ $giorno['giorno'] }}"
                                        data-militare-id="{{ $item['militare']->id }}"
                                        data-tipo-servizio-id="{{ $pianificazione->tipo_servizio_id ?? '' }}"
                                    style="{{ $cellStyle }}"
                                    @can('cpt.edit')
                                    tabindex="0"
                                    role="button"
                                    aria-label="Modifica impegno per {{ $item['militare']->cognome }} {{ $item['militare']->nome }} - {{ $giorno['giorno'] }} {{ $mese }}/{{ $anno }}"
                                    onclick="openEditModal(this)"
                                    onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); openEditModal(this); }"
                                    @else
                                    title="Non hai i permessi per modificare"
                                    @endcan>
                                        
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
                                        <span class="text-muted" style="font-size: 10px;" 
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top"
                                              title="Nessun impegno">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-search fa-3x mb-3"></i>
                                        <h5>Nessun militare soddisfa questi requisiti</h5>
                                        <p class="mb-0">Prova a modificare i filtri di ricerca</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
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
                                        <option value="{{ $impegno->codice }}" data-id="{{ $impegno->id }}">{{ $impegno->nome }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editGiornoFine" class="form-label">Fino al giorno (opzionale)</label>
                        <input type="date" class="form-control" id="editGiornoFine" name="giorno_fine" 
                               min="{{ $anno }}-{{ sprintf('%02d', $mese) }}-{{ sprintf('%02d', 1) }}"
                               max="{{ $anno + 1 }}-{{ sprintf('%02d', 12) }}-{{ sprintf('%02d', 31) }}">
                        <div class="form-text text-muted">
                            Lascia vuoto per il giorno singolo. Usa il calendario per selezionare un range.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 6px !important;">Annulla</button>
                <button type="button" class="btn btn-primary" id="saveGiornoBtn" style="border-radius: 6px !important;">Salva</button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

@endsection

@push('styles')
<style id="cpt-custom-styles-{{ time() }}">
/* Stili per la tabella con header fisso */
.table-container {
    display: flex;
    flex-direction: column;
    height: 60vh;
}

.table-header-fixed {
    position: relative;
    z-index: 10;
    background: white;
    border-bottom: 2px solid #dee2e6;
    overflow: hidden;
}

.table-header-fixed::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* Sfondo alternato per la tabella */
.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

/* Hover effect */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

/* Bordi leggermente più scuri dell'hover */
.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Stili per i link con sottolineatura d'oro */
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
    width: 3032px; /* Larghezza fissa per garantire scroll */
    min-width: 3032px;
    border-collapse: separate;
    border-spacing: 0;
}

/* Allineamento perfetto tra header e body */
.table-header-fixed,
.table-body-scroll {
    font-family: monospace;
    font-size: 11px;
}

/* Larghezza specifica per ogni colonna per allineamento perfetto (header/body) */
.table-header-fixed table th:nth-child(1), .table-body-scroll table td:nth-child(1) { width: 160px; min-width: 160px; max-width: 160px; } /* Compagnia */
.table-header-fixed table th:nth-child(2), .table-body-scroll table td:nth-child(2) { width: 200px; min-width: 200px; max-width: 200px; } /* Grado */
.table-header-fixed table th:nth-child(3), .table-body-scroll table td:nth-child(3) { width: 230px; min-width: 230px; max-width: 230px; } /* Cognome */
.table-header-fixed table th:nth-child(4), .table-body-scroll table td:nth-child(4) { width: 170px; min-width: 170px; max-width: 170px; } /* Nome */
.table-header-fixed table th:nth-child(5), .table-body-scroll table td:nth-child(5) { width: 120px; min-width: 120px; max-width: 120px; } /* Plotone */
.table-header-fixed table th:nth-child(6), .table-body-scroll table td:nth-child(6) { width: 112px; min-width: 112px; max-width: 112px; } /* Patente */
.table-header-fixed table th:nth-child(7), .table-body-scroll table td:nth-child(7) { width: 120px; min-width: 120px; max-width: 120px; } /* Approntamento */

/* Colonne giorni - larghezza fissa */
.table-header-fixed table th:nth-child(n+8), .table-body-scroll table td:nth-child(n+8) { 
    width: 60px; 
    min-width: 60px; 
    max-width: 60px;
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

/* Centratura verticale e orizzontale dei badge nelle celle giorno */
.giorno-cell {
    vertical-align: middle !important;
    text-align: center !important;
}

.giorno-cell .badge,
.giorno-cell .codice-badge {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    vertical-align: middle !important;
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
    padding: 0;
    vertical-align: middle;
}

/* Padding per elementi specifici che lo necessitano */
.badge, .btn, .form-control, .search-container input, .counter-badge {
    padding: 6px 12px;
}

.badge {
    padding: 4px 8px;
}

.btn-sm {
    padding: 4px 8px;
}

        /* RIGHE ALTERNATE SEMPLICI */
        .table tbody tr.militare-row:nth-child(even) {
            background-color: #f8f9fa !important;
        }
        
        .table tbody tr.militare-row:nth-child(odd) {
            background-color: #ffffff !important;
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

/* Stili per il date picker */
#editGiornoFine {
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 12px;
    font-size: 14px;
    transition: all 0.3s ease;
}

#editGiornoFine:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    background-color: white;
}

#editGiornoFine::-webkit-calendar-picker-indicator {
    background-color: #0d6efd;
    border-radius: 4px;
    padding: 4px;
    cursor: pointer;
    filter: invert(1);
}

/* Stili per il page header */
.page-header {
    margin-bottom: 1rem;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 600;
    color: #2c3e50;
    margin: 0;
}

.page-subtitle {
    font-size: 0.95rem;
    color: #7f8c8d;
    margin-top: 0.25rem;
    font-weight: 500;
}

        /* Forza background neutro per la pagina */
body {
    background-color: #f8f9fa !important;
}

/* CSS AGGRESSIVO PER RISOLVERE TUTTI I PROBLEMI */

/* 1. RIMUOVI DUPLICAZIONE - Forza una sola istanza */
body, html {
    overflow-x: hidden !important;
}

.container-fluid {
    position: relative !important;
    z-index: 1 !important;
}

/* Rimuovi bordi arrotondati dalle celle della tabella - bordi dritti minimal */
.table-bordered td,
.table-bordered th,
table.table td,
table.table th,
#pianificazioneTable td,
#pianificazioneTable th,
.table-header-fixed table td,
.table-header-fixed table th,
.table td,
.table th,
tbody td,
tbody th {
    border-radius: 0 !important;
    -webkit-border-radius: 0 !important;
    -moz-border-radius: 0 !important;
}

/* Rimuovi border-radius anche dai bordi interni */
.table > :not(caption) > * > * {
    border-radius: 0 !important;
}

.table-bordered > :not(caption) > * {
    border-radius: 0 !important;
}

.table-bordered > :not(caption) > * > * {
    border-radius: 0 !important;
}

/* Rimosso - duplicato spostato più in alto */

/* 2. Sfondo weekend/festivi - rosso semitrasparente */
table.table tbody td.weekend-column,
table.table tbody td.holiday-column {
    background-color: rgba(255, 0, 0, 0.12) !important;
    color: #495057 !important;
}

/* Header weekend/festivi - numero e lettera in rosso */
table.table thead th.weekend-column,
table.table thead th.holiday-column,
.table-header-fixed th.weekend-column,
.table-header-fixed th.holiday-column {
    background-color: transparent !important;
}

table.table thead th.weekend-column .fw-bold,
table.table thead th.holiday-column .fw-bold,
table.table thead th.weekend-column .opacity-75,
table.table thead th.holiday-column .opacity-75,
table.table thead th.weekend-column div,
table.table thead th.holiday-column div,
.table-header-fixed th.weekend-column .fw-bold,
.table-header-fixed th.holiday-column .fw-bold,
.table-header-fixed th.weekend-column .opacity-75,
.table-header-fixed th.holiday-column .opacity-75,
.table-header-fixed th.weekend-column div,
.table-header-fixed th.holiday-column div {
    color: #dc3545 !important;
    font-weight: 700 !important;
    opacity: 1 !important;
}

/* Sfondo oggi - giallo semitrasparente */
table.table tbody td.today-column {
    background-color: rgba(255, 220, 0, 0.20) !important;
}

/* Header oggi - numero e lettera in giallo/arancione scuro (per visibilità) */
table.table thead th.today-column,
.table-header-fixed th.today-column {
    background-color: transparent !important;
}

table.table thead th.today-column .fw-bold,
table.table thead th.today-column .opacity-75,
table.table thead th.today-column div,
.table-header-fixed th.today-column .fw-bold,
.table-header-fixed th.today-column .opacity-75,
.table-header-fixed th.today-column div {
    color: #ff8c00 !important;
    font-weight: 700 !important;
    opacity: 1 !important;
}

/* 3. HOVER SU TUTTA LA RIGA - Come in Forza Effettiva */
/* Specificity massima per sovrascrivere qualsiasi altro stile */
#pianificazioneTable tbody tr.militare-row:hover td {
    background-color: rgba(10, 35, 66, 0.15) !important;
    transition: background-color 0.15s ease;
}

/* Weekend/festivi durante hover - stile coerente con hover normale */
#pianificazioneTable tbody tr.militare-row:hover td.weekend-column,
#pianificazioneTable tbody tr.militare-row:hover td.holiday-column,
#pianificazioneTable tbody tr.militare-row:hover td.today-column {
    background-color: rgba(10, 35, 66, 0.15) !important;
}

/* I badge DENTRO le celle devono mantenere il loro colore durante l'hover */
#pianificazioneTable tbody tr.militare-row:hover td .badge {
    /* Non modificare i colori dei badge durante l'hover */
}

/* Usa gli stili Bootstrap standard per i selettori */

/* Stili minimal per l'anteprima del range */
#rangePreview {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    font-size: 12px;
    color: #6c757d;
    padding: 8px 12px;
    margin-top: 8px;
}

#rangePreview .badge {
    font-size: 10px;
    padding: 2px 6px;
    background-color: #6c757d;
}

/* CSS FINALE PER FORZARE TUTTO */
html, body {
    overflow-x: hidden !important;
}

/* Rimuovi qualsiasi duplicazione */
.container-fluid {
    position: relative !important;
    z-index: 1 !important;
}

/* Weekend/festivi - grigio tortora molto chiaro (sezione duplicata rimossa per evitare conflitti) */
/* Hover su tutta la riga (già gestito sopra con .militare-row) */

</style>
@endpush

@push('scripts')
{{-- JavaScript spostato nel file pianificazione-test.js --}}

<script>
// Gestione hover manuale e tooltip per le righe della tabella pianificazione
document.addEventListener('DOMContentLoaded', function() {
    
    // ===== INIZIALIZZA TOOLTIP BOOTSTRAP =====
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    console.log('✓ Tooltip Bootstrap inizializzati:', tooltipList.length);
    
    // ===== GESTIONE HOVER RIGHE =====
    const table = document.getElementById('pianificazioneTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr.militare-row');
    const hoverColor = 'rgba(10, 35, 66, 0.15)';
    
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            const cells = this.querySelectorAll('td');
            cells.forEach(cell => {
                // Salva il colore originale inline (se presente)
                if (!cell.dataset.originalBg) {
                    cell.dataset.originalBg = cell.style.backgroundColor || '';
                }
                // Applica l'hover
                cell.style.backgroundColor = hoverColor;
            });
        });
        
        row.addEventListener('mouseleave', function() {
            const cells = this.querySelectorAll('td');
            cells.forEach(cell => {
                // Ripristina il colore originale salvato
                if (cell.dataset.originalBg !== undefined) {
                    cell.style.backgroundColor = cell.dataset.originalBg;
                }
            });
        });
    });
    
    // Gestione export Excel con filtri
    const exportBtn = document.getElementById('exportExcel');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Costruisci URL con parametri correnti (inclusi filtri + mese e anno)
            const urlParams = new URLSearchParams(window.location.search);
            
            // Assicurati che mese e anno siano presenti
            if (!urlParams.has('mese')) {
                urlParams.set('mese', '{{ $mese }}');
            }
            if (!urlParams.has('anno')) {
                urlParams.set('anno', '{{ $anno }}');
            }
            
            const exportUrl = '{{ route("pianificazione.export-excel") }}?' + urlParams.toString();
            
            // Redirect per scaricare il file
            window.location.href = exportUrl;
        });
    }
});
</script>
@endpush
