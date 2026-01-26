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
<script src="{{ asset('js/pianificazione.js') }}?v={{ time() }}"></script>

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
            foreach(['compagnia', 'grado_id', 'plotone_id', 'ufficio_id', 'patente', 'approntamento_id', 'impegno', 'stato_impegno', 'compleanno', 'giorno'] as $filter) {
                if(request()->filled($filter)) $activeFilters[] = $filter;
            }
            $hasActiveFilters = count($activeFilters) > 0;
        @endphp

        <!-- Barra di ricerca centrata sotto il titolo -->
        <div class="d-flex justify-content-center mb-3">
            <div class="search-container" style="position: relative; width: 400px;">
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
        </div>
        
        <div class="d-flex justify-content-start align-items-center mb-3" style="background: transparent;">
            <div>
                <button id="toggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
                    <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i> 
                    <span id="toggleFiltersText">
                        {{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Filtri con sezione migliorata -->
        <div id="filtersContainer" class="filter-section {{ $hasActiveFilters ? 'visible' : '' }}">
            @include('components.filters.filter-pianificazione')
        </div>
        
        <div class="card-body p-0">
            <!-- Tabella unica con header sticky (stile pagina Servizi) -->
            <div class="sugeco-table-wrapper cpt-wrapper">
                <table class="sugeco-table" id="pianificazioneTable">
                    <thead>
                        <tr>
                            <!-- Colonne fisse per info militare -->
                            <th>Compagnia</th>
                            <th>Grado</th>
                            <th>Cognome</th>
                            <th>Nome</th>
                            <th>Plotone</th>
                            <th>Ufficio</th>
                            <th>Patente</th>
                            <th>Teatro Op.</th>
                            
                            <!-- Colonne per ogni giorno del mese - TUTTE con larghezza FISSA identica -->
                            @foreach($giorniMese as $giorno)
                                @php
                                    $isWeekend = $giorno['is_weekend'];
                                    $isHoliday = $giorno['is_holiday'];
                                    $isToday = $giorno['is_today'];
                                    // Nomi giorni completi maiuscolo (identico a pagina Servizi)
                                    $mappaGiorni = ['Dom' => 'DOMENICA', 'Lun' => 'LUNEDI', 'Mar' => 'MARTEDI', 'Mer' => 'MERCOLEDI', 'Gio' => 'GIOVEDI', 'Ven' => 'VENERDI', 'Sab' => 'SABATO'];
                                    $nomeGiornoCompleto = $mappaGiorni[$giorno['nome_giorno']] ?? strtoupper($giorno['nome_giorno']);
                                @endphp
                                <th class="giorno-header {{ $isWeekend || $isHoliday ? 'weekend' : '' }} {{ $isToday ? 'today' : '' }}" style="width: 95px; min-width: 95px; max-width: 95px;">
                                    <div>{{ $nomeGiornoCompleto }}</div>
                                    <div class="date-badge">{{ $giorno['data']->format('d/m') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($militariConPianificazione as $index => $item)
                            @php
                                $patentiArray = $item['militare']->patenti->pluck('categoria')->toArray();
                                $impegniGiorni = [];
                                foreach($item['pianificazioni'] as $giornoPian => $pian) {
                                    if ($pian && $pian->tipoServizio) {
                                        $impegniGiorni[$giornoPian] = $pian->tipoServizio->codice;
                                    }
                                }
                                // Ottieni gli ID dei teatri operativi assegnati al militare
                                $teatriOperativiIds = $item['militare']->teatriOperativi->pluck('id')->toArray();
                                $teatriOperativiIdsStr = implode(',', $teatriOperativiIds);
                            @endphp
                            <tr class="militare-row" 
                                data-militare-id="{{ $item['militare']->id }}"
                                data-compagnia-id="{{ $item['militare']->compagnia_id ?? '' }}"
                                data-grado-id="{{ $item['militare']->grado_id ?? '' }}"
                                data-plotone-id="{{ $item['militare']->plotone_id ?? '' }}"
                                data-ufficio-id="{{ $item['militare']->polo_id ?? '' }}"
                                data-patenti="{{ implode(',', $patentiArray) }}"
                                data-approntamento-id="{{ $teatriOperativiIdsStr }}"
                                data-impegni="{{ json_encode($impegniGiorni) }}"
                                data-data-nascita="{{ $item['militare']->data_nascita ?? '' }}">
                                <!-- Info militare -->
                                <td class="text-center">{{ $item['militare']->compagnia->numero ?? '-' }}</td>
                                <td title="{{ $item['militare']->grado->nome ?? '-' }}">{{ $item['militare']->grado->sigla ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                                        {{ $item['militare']->cognome }}
                                    </a>
                                </td>
                                <td>{{ $item['militare']->nome }}</td>
                                <td class="text-center">{{ str_replace(['° Plotone', 'Plotone'], ['°', ''], $item['militare']->plotone->nome ?? '-') }}</td>
                                <td class="text-center">{{ $item['militare']->polo->nome ?? '-' }}</td>
                                <td class="text-center">
                                    @php
                                        $patenti = $item['militare']->patenti->pluck('categoria')->toArray();
                                    @endphp
                                    {{ !empty($patenti) ? implode(' ', $patenti) : '-' }}
                                </td>
                                <td><small>{{ $item['militare']->scadenzaApprontamento->teatro_operativo ?? ($item['militare']->approntamentoPrincipale->nome ?? '-') }}</small></td>
                                
                                <!-- Celle per ogni giorno -->
                                @foreach($giorniMese as $giorno)
                                    @php
                                        $pianificazione = $item['pianificazioni'][$giorno['giorno']] ?? null;
                                        $codice = '';
                                        $coloreBadge = null;
                                        $tooltip = 'Nessuna pianificazione';
                                        
                                        // MAPPA COLORI CPT - Fallback per codici senza colore nel database
                                        $mappaColoriCpt = [
                                            // ASSENTE - Giallo
                                            'LS' => '#ffff00', 'LO' => '#ffff00', 'LM' => '#ffff00',
                                            'P' => '#ffff00', 'TIR' => '#ffff00', 'TRAS' => '#ffff00',
                                            // PROVVEDIMENTI MEDICO SANITARI - Rosso
                                            'RMD' => '#ff0000', 'LC' => '#ff0000', 'IS' => '#ff0000',
                                            // SERVIZIO - Verde
                                            'S-G1' => '#00b050', 'S-G2' => '#00b050', 'S-SA' => '#00b050',
                                            'S-CD1' => '#00b050', 'S-CD2' => '#00b050', 'S-CD3' => '#00b050', 'S-CD4' => '#00b050',
                                            'S-SG' => '#00b050', 'S-CG' => '#00b050', 'S-UI' => '#00b050', 'S-UP' => '#00b050',
                                            'S-AE' => '#00b050', 'S-ARM' => '#00b050', 'SI-GD' => '#00b050',
                                            'SI' => '#00b050', 'S-VM' => '#00b050', 'S-PI' => '#00b050',
                                            'S.I.' => '#00b050', 'RIP' => '#00b050',
                                            // Codici turni e servizi vari
                                            'G1' => '#00b050', 'G2' => '#00b050', 'SG' => '#00b050',
                                            'CD1' => '#00b050', 'CD2' => '#00b050',
                                            'PDT1' => '#00b050', 'PDT2' => '#00b050',
                                            'AE' => '#00b050', 'A-A' => '#00b050',
                                            'CETLI' => '#00b050', 'LCC' => '#00b050', 'CENTURIA' => '#00b050',
                                            'TIROCINIO' => '#00b050',
                                            // SERVIZI TURNO - Verde
                                            'G-BTG' => '#00b050', 'NVA' => '#00b050', 'CG' => '#00b050',
                                            'NS-DA' => '#00b050', 'PDT' => '#00b050', 'AA' => '#00b050',
                                            'VS-CETLI' => '#00b050', 'CORR' => '#00b050', 'NDI' => '#00b050',
                                            // FORMAZIONE/CATTEDRA - Verde
                                            'Cattedra' => '#00b050', 'CATTEDRA' => '#00b050', 'cattedra' => '#00b050',
                                            'APS1' => '#00b050', 'APS2' => '#00b050', 'APS3' => '#00b050', 'APS4' => '#00b050',
                                            'AL-ELIX' => '#00b050', 'AL-MCM' => '#00b050', 'AL-BLS' => '#00b050',
                                            'AL-CIED' => '#00b050', 'AL-SM' => '#00b050', 'AL-RM' => '#00b050',
                                            'AL-RSPP' => '#00b050', 'AL-LEG' => '#00b050', 'AL-SEA' => '#00b050',
                                            'AL-MI' => '#00b050', 'AL-PO' => '#00b050', 'AL-PI' => '#00b050',
                                            'AP-M' => '#00b050', 'AP-A' => '#00b050', 'AC-SW' => '#00b050',
                                            'AC' => '#00b050', 'PEFO' => '#00b050', 'EXE' => '#00b050',
                                            'SMO' => '#00b050', 'smo' => '#00b050',
                                            // OPERAZIONE - Arancione
                                            'TO' => '#ffc000', 'T.O.' => '#ffc000', 'MCM' => '#ffc000', 'KOSOVO' => '#ffc000',
                                        ];
                                        
                                        if ($pianificazione) {
                                            if ($pianificazione->tipoServizio) {
                                                $codice = $pianificazione->tipoServizio->codice;
                                                $gerarchia = $pianificazione->tipoServizio->codiceGerarchia;
                                                // Usa la mappa dei codici per nome e colore
                                                $tooltip = $pianificazione->tipoServizio->nome ?? $codice;
                                                
                                                // USA IL COLORE: prima dalla gerarchia, poi dal tipoServizio stesso, poi fallback hardcoded
                                                $coloreBadge = $gerarchia?->colore_badge 
                                                    ?? $pianificazione->tipoServizio->colore_badge 
                                                    ?? ($mappaColoriCpt[$codice] ?? null);
                                            } else {
                                                // Pianificazione esiste ma senza tipo servizio (Nessun impegno)
                                                $codice = '';
                                                $coloreBadge = null;
                                                $tooltip = 'Nessun impegno';
                                            }
                                        } else {
                                            // Militari senza pianificazione: cella vuota
                                            $codice = '';
                                            $coloreBadge = null;
                                            $tooltip = 'Nessuna pianificazione';
                                        }
                                        
                                        // Funzione per determinare se il colore è chiaro (per il testo)
                                        $isLightColor = function($hexColor) {
                                            if (!$hexColor) return true;
                                            $hex = ltrim($hexColor, '#');
                                            $r = hexdec(substr($hex, 0, 2));
                                            $g = hexdec(substr($hex, 2, 2));
                                            $b = hexdec(substr($hex, 4, 2));
                                            $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
                                            return $brightness > 128;
                                        };
                                    @endphp
                                    
                                    @php
                                        // Stili base cella - LARGHEZZA FISSA 95px come header
                                        $cellStyle = "width: 95px; min-width: 95px; max-width: 95px; height: 32px; cursor: pointer; font-size: 10px; font-weight: 600; padding: 4px 2px; box-sizing: border-box;";
                                        
                                        // COLORA LA CELLA usando il colore dal database
                                        // Verifica esplicita che il colore sia una stringa non vuota
                                        $hasValidColor = $coloreBadge && is_string($coloreBadge) && strlen(trim($coloreBadge)) > 0;
                                        
                                        if ($codice && $hasValidColor) {
                                            // Usa il colore dal database/mappa
                                            $textColor = $isLightColor($coloreBadge) ? '#000000' : '#ffffff';
                                            $cellStyle .= " background-color: {$coloreBadge} !important; color: {$textColor} !important;";
                                        } elseif ($codice) {
                                            // Fallback: se c'è un codice ma non c'è colore valido, usa verde di default
                                            $cellStyle .= " background-color: #00b050 !important; color: #ffffff !important;";
                                        } else {
                                            // Celle vuote - sfondo weekend/festivi/oggi
                                            if ($giorno['is_weekend'] || $giorno['is_holiday']) {
                                                $cellStyle .= " background-color: rgba(255, 0, 0, 0.12);";
                                            } elseif ($giorno['is_today']) {
                                                $cellStyle .= " background-color: rgba(255, 220, 0, 0.20);";
                                            }
                                        }
                                    @endphp
                                    <td class="text-center giorno-cell {{ $codice ? 'has-impegno' : '' }} {{ $giorno['is_weekend'] ? 'weekend-column' : '' }} {{ $giorno['is_holiday'] ? 'holiday-column' : '' }} {{ $giorno['is_today'] ? 'today-column' : '' }}"
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
                                    @php
                                        $tooltipText = '';
                                        if ($pianificazione && $pianificazione->tipoServizio) {
                                            // Ha un tipo servizio attivo
                                            if ($pianificazione->note) {
                                                // Se ci sono note (da board), mostra note + servizio
                                                $tooltipText = $pianificazione->tipoServizio->codice . ' - ' . $pianificazione->tipoServizio->nome . ' (' . $pianificazione->note . ')';
                                            } else {
                                                // Impegno standard: mostra codice + nome completo
                                                $tooltipText = $pianificazione->tipoServizio->codice . ' - ' . $pianificazione->tipoServizio->nome;
                                            }
                                        } else {
                                            // Nessuna pianificazione o pianificazione senza tipo servizio
                                            $tooltipText = 'Nessuna pianificazione - Clicca per aggiungere';
                                        }
                                    @endphp
                                    title="{{ $tooltipText }}"
                                    @else
                                    @php
                                        $tooltipTextView = '';
                                        if ($pianificazione && $pianificazione->tipoServizio) {
                                            // Ha un tipo servizio attivo
                                            if ($pianificazione->note) {
                                                // Se ci sono note (da board), mostra note + servizio
                                                $tooltipTextView = $pianificazione->tipoServizio->codice . ' - ' . $pianificazione->tipoServizio->nome . ' (' . $pianificazione->note . ')';
                                            } else {
                                                // Impegno standard: mostra codice + nome completo
                                                $tooltipTextView = $pianificazione->tipoServizio->codice . ' - ' . $pianificazione->tipoServizio->nome;
                                            }
                                        } else {
                                            // Nessuna pianificazione o pianificazione senza tipo servizio
                                            $tooltipTextView = 'Nessuna pianificazione';
                                        }
                                    @endphp
                                    title="{{ $tooltipTextView }}"
                                    @endcan>
                                        
{{ $codice ?: '-' }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            @include('components.no-results')
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
                        <label for="editMilitareNome" class="form-label">Nominativo</label>
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
/* ========================================
   CPT - Stili IDENTICI alla pagina Servizi
   ======================================== */

/* Wrapper CPT - stesso stile di sugeco-table-wrapper */
.cpt-wrapper {
    max-height: 70vh;
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

/* Tabella CPT - layout fisso per colonne uniformi */
.cpt-wrapper .sugeco-table {
    width: 100%;
    min-width: 3800px; /* 830px colonne fisse + 31 giorni x 95px */
    border-collapse: collapse;
    table-layout: fixed;
}

/* Header sticky - IDENTICO alla pagina Servizi */
.cpt-wrapper .sugeco-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #0a2342;
    color: white;
    padding: 12px 8px;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    border: none;
}

/* Date badge nell'header - IDENTICO alla pagina Servizi */
.date-badge {
    background: rgba(255, 255, 255, 0.25);
    color: white;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    display: inline-block;
    margin-top: 6px;
    font-family: 'Roboto', sans-serif;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Weekend header - IDENTICO alla pagina Servizi */
.cpt-wrapper .sugeco-table th.weekend {
    background-color: #dc3545 !important;
}

/* Today header */
.cpt-wrapper .sugeco-table th.today {
    background-color: #0A2342 !important;
}

.cpt-wrapper .sugeco-table th.today .date-badge {
    background: #ff8c00;
    color: white;
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

/* Celle del body - IDENTICO alla pagina Servizi */
.cpt-wrapper .sugeco-table tbody td {
    padding: 8px 6px;
    text-align: center;
    font-size: 0.85rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

/* Righe alternate */
.cpt-wrapper .sugeco-table tbody tr:nth-child(even) {
    background-color: #f8f9fa;
}

.cpt-wrapper .sugeco-table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.05);
}

/* Colonne info militare - larghezze fisse */
.cpt-wrapper .sugeco-table th:nth-child(1),
.cpt-wrapper .sugeco-table td:nth-child(1) { width: 100px; } /* Compagnia */
.cpt-wrapper .sugeco-table th:nth-child(2),
.cpt-wrapper .sugeco-table td:nth-child(2) { width: 80px; } /* Grado */
.cpt-wrapper .sugeco-table th:nth-child(3),
.cpt-wrapper .sugeco-table td:nth-child(3) { width: 150px; text-align: left !important; } /* Cognome */
.cpt-wrapper .sugeco-table th:nth-child(4),
.cpt-wrapper .sugeco-table td:nth-child(4) { width: 120px; text-align: left !important; } /* Nome */
.cpt-wrapper .sugeco-table th:nth-child(5),
.cpt-wrapper .sugeco-table td:nth-child(5) { width: 80px; } /* Plotone */
.cpt-wrapper .sugeco-table th:nth-child(6),
.cpt-wrapper .sugeco-table td:nth-child(6) { width: 120px; } /* Ufficio */
.cpt-wrapper .sugeco-table th:nth-child(7),
.cpt-wrapper .sugeco-table td:nth-child(7) { width: 80px; } /* Patente */
.cpt-wrapper .sugeco-table th:nth-child(8),
.cpt-wrapper .sugeco-table td:nth-child(8) { width: 100px; } /* Teatro Operativo */

/* Colonne giorni - TUTTE UGUALI, larghezza FISSA OBBLIGATORIA */
.cpt-wrapper .sugeco-table th.giorno-header,
.cpt-wrapper .sugeco-table td.giorno-cell {
    width: 95px !important;
    min-width: 95px !important;
    max-width: 95px !important;
    padding: 6px 4px;
    box-sizing: border-box;
}

/* ========================================================================
   CELLE GIORNO CPT
   ======================================================================== */
   
/* TD celle giorno */
.cpt-wrapper .sugeco-table .giorno-cell {
    cursor: pointer;
    font-size: 10px;
    font-weight: 600;
    text-align: center;
    vertical-align: middle;
    transition: background-color 0.2s;
}

.cpt-wrapper .sugeco-table .giorno-cell:hover {
    filter: brightness(0.9);
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

/* Colonne weekend nel body */
.cpt-wrapper .sugeco-table .weekend-column {
    background-color: rgba(220, 53, 69, 0.1);
}

/* Colonna oggi nel body */
.cpt-wrapper .sugeco-table .today-column {
    background-color: rgba(255, 193, 7, 0.15);
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

/* Rimuovi bordi arrotondati dalle celle */
.cpt-wrapper .sugeco-table td,
.cpt-wrapper .sugeco-table th {
    border-radius: 0;
}

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
    // ===== HOVER GESTITO VIA CSS =====
    // La classe .has-impegno viene aggiunta/rimossa dinamicamente
    // Il CSS usa #pianificazioneTable tbody tr.militare-row:hover td:not(.has-impegno)
    // per applicare l'hover solo alle celle senza impegno
    
    // Gestione export Excel con filtri
    // IMPORTANTE: I filtri sono client-side, quindi dobbiamo raccoglierli dai select, non dall'URL
    const exportBtn = document.getElementById('exportExcel');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Costruisci URL con parametri dai select dei filtri (non dall'URL che potrebbe essere vuoto)
            const urlParams = new URLSearchParams();
            
            // Mese e anno sempre presenti
            urlParams.set('mese', '{{ $mese }}');
            urlParams.set('anno', '{{ $anno }}');
            
            // Raccogli valori da tutti i filtri client-side
            const filtroForm = document.getElementById('filtroForm');
            if (filtroForm) {
                const selects = filtroForm.querySelectorAll('select');
                selects.forEach(select => {
                    const name = select.name || select.id;
                    const value = select.value;
                    if (name && value) {
                        urlParams.set(name, value);
                    }
                });
            }
            
            const exportUrl = '{{ route("pianificazione.export-excel") }}?' + urlParams.toString();
            
            // Redirect per scaricare il file
            window.location.href = exportUrl;
        });
    }
    
    // ===== FILTRAGGIO CLIENT-SIDE (usa modulo riutilizzabile) =====
    // Registra i filtri speciali per questa pagina
    if (window.ClientSideFilters) {
        // Filtro Impegno (con supporto per giorno specifico)
        window.ClientSideFilters.registerSpecialFilter('impegno', function(row, filterValue) {
            const impegni = row.dataset.impegni ? JSON.parse(row.dataset.impegni) : {};
            const giornoFilter = document.getElementById('giorno')?.value || '';
            
            if (filterValue === 'libero') {
                return Object.keys(impegni).length === 0;
            }
            
            // Se è specificato anche il giorno, verifica quel giorno specifico
            if (giornoFilter) {
                return impegni[giornoFilter] === filterValue;
            }
            
            // Altrimenti cerca l'impegno in qualsiasi giorno
            return Object.values(impegni).includes(filterValue);
        });
        
        // Filtro Compleanno
        window.ClientSideFilters.registerSpecialFilter('compleanno', function(row, filterValue) {
            const dataNascita = row.dataset.dataNascita;
            if (!dataNascita) return false;
            
            const oggi = new Date();
            const nascita = new Date(dataNascita);
            const giornoNascita = nascita.getDate();
            const meseNascita = nascita.getMonth() + 1;
            
            switch (filterValue) {
                case 'oggi':
                    return giornoNascita === oggi.getDate() && meseNascita === (oggi.getMonth() + 1);
                case 'ultimi_2':
                    for (let i = 0; i <= 2; i++) {
                        const d = new Date(oggi);
                        d.setDate(d.getDate() - i);
                        if (giornoNascita === d.getDate() && meseNascita === (d.getMonth() + 1)) {
                            return true;
                        }
                    }
                    return false;
                case 'prossimi_2':
                    for (let i = 0; i <= 2; i++) {
                        const d = new Date(oggi);
                        d.setDate(d.getDate() + i);
                        if (giornoNascita === d.getDate() && meseNascita === (d.getMonth() + 1)) {
                            return true;
                        }
                    }
                    return false;
            }
            return true;
        });
        
        // Filtro Disponibile (basato su oggi)
        window.ClientSideFilters.registerSpecialFilter('disponibile', function(row, filterValue) {
            const impegni = row.dataset.impegni ? JSON.parse(row.dataset.impegni) : {};
            const oggi = new Date();
            const impegnoOggi = impegni[oggi.getDate()] || null;
            
            // Codici che indicano assenza (NON_DISPONIBILE)
            const codiciAssenza = ['LS', 'LO', 'LM', 'LC', 'RMD', 'IS', 'TIR', 'TRAS', 'TO'];
            
            if (filterValue === 'si') {
                return !impegnoOggi || !codiciAssenza.includes(impegnoOggi);
            } else if (filterValue === 'no') {
                return impegnoOggi && codiciAssenza.includes(impegnoOggi);
            }
            return true;
        });
        
        // Inizializza il modulo con configurazione specifica per CPT
        window.ClientSideFilters.init({
            tableSelector: '#pianificazioneTable',
            rowSelector: 'tr.militare-row',
            debug: false
        });
    }
});
</script>
@endpush

