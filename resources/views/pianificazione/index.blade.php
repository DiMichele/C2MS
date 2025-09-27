@extends('layouts.app')

@section('title', 'Pianificazione Mensile - ' . $pianificazioneMensile->nome)

@section('content')
<script>
// Funzione globale per aprire il modal di modifica
function openEditModal(cell) {
    const militareId = cell.getAttribute('data-militare-id');
    const giorno = cell.getAttribute('data-giorno');
    const tipoServizioId = cell.getAttribute('data-tipo-servizio-id');
    
    // Trova la riga del militare per ottenere i dati
    const row = cell.closest('tr');
    if (!row) return;
    
    // Estrai dati militare dalle celle
    const gradoCell = row.cells[0];
    const cognomeCell = row.cells[1]; 
    const nomeCell = row.cells[2];
    
    if (!gradoCell || !cognomeCell || !nomeCell) return;
    
    const grado = gradoCell.textContent.trim();
    const cognomeLink = cognomeCell.querySelector('a');
    const cognome = cognomeLink ? cognomeLink.textContent.trim() : cognomeCell.textContent.trim();
    const nome = nomeCell.textContent.trim();
    
    const nomeCompleto = `${grado} ${cognome} ${nome}`;
    const giornoCompleto = `${giorno} Settembre`;
    
    // Trova elementi modal
    const modal = document.getElementById('editGiornoModal');
    const editMilitareId = document.getElementById('editMilitareId');
    const editGiorno = document.getElementById('editGiorno');
    const editMilitareNome = document.getElementById('editMilitareNome');
    const editGiornoLabel = document.getElementById('editGiornoLabel');
    const editTipoServizio = document.getElementById('editTipoServizio');
    
    if (!modal) return;
    
    // Popola campi
    if (editMilitareId) editMilitareId.value = militareId;
    if (editGiorno) editGiorno.value = giorno;
    if (editTipoServizio) editTipoServizio.value = tipoServizioId || '';
    if (editMilitareNome) editMilitareNome.value = nomeCompleto;
    if (editGiornoLabel) editGiornoLabel.value = giornoCompleto;
    
    // Mostra modal
    if (typeof $ !== 'undefined') {
        $(modal).modal('show');
    } else if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}
</script>

<div class="container-fluid">
    <!-- Header con controlli -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-0">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                Pianificazione Mensile - {{ $pianificazioneMensile->nome }}
            </h1>
            <p class="text-muted mb-0">Vista calendario completa come CPT Excel</p>
        </div>
        <div class="col-md-4 text-end">
            <!-- Selettore mese/anno -->
            <form method="GET" class="d-inline-flex gap-2">
                <select name="mese" class="form-select" onchange="this.form.submit()" style="min-width: 140px;">
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
                <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 100px; padding-right: 35px;">
                    @for($a = date('Y') - 1; $a <= date('Y') + 1; $a++)
                        <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endfor
                </select>
            </form>
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
            <!-- Gestione filtri migliorata -->
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
                <div class="table-header-fixed" style="overflow-x: auto;">
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
                                                if ($gerarchia) {
                                                    // Converte colori hex del database in classi CPT
                                                    $coloreBadge = $gerarchia->colore_badge ?? 'secondary';
                                                    $colore = match($coloreBadge) {
                                                        '#ff0000' => 'cpt-rosso',      // Rosso CPT
                                                        '#00b050' => 'cpt-verde',      // Verde CPT  
                                                        '#ffff00' => 'cpt-giallo',     // Giallo CPT
                                                        '#ffc000' => 'cpt-arancione',  // Arancione CPT
                                                        '#28a745' => 'cpt-verde',      // Verde Bootstrap -> Verde CPT
                                                        default => 'secondary'
                                                    };
                                                    $tooltip = $gerarchia->macro_attivita . ' - ' . $gerarchia->tipo_attivita;
                                                    if ($gerarchia->attivita_specifica) {
                                                        $tooltip .= '<br>' . $gerarchia->attivita_specifica;
                                                    }
                                                } else {
                                                // Colori ESATTI dal CPT come da immagine fornita
                                                $colore = match($codice) {
                                                    // ROSSO - OPERAZIONI E CONDIZIONI MEDICHE GRAVI
                                                    'TO' => 'cpt-rosso',         // Teatro Operativo
                                                    'RMD', 'rmd' => 'cpt-rosso', // Riposo Medico Domiciliare
                                                    'LC', 'lc' => 'cpt-rosso',   // Licenza di Convalescenza
                                                    
                                                    // GIALLO - ASSENZE PROGRAMMATE (dall'immagine)
                                                    'lo' => 'cpt-giallo',        // Licenza Ordinaria
                                                    'p' => 'cpt-giallo',         // Permessino
                                                    'ls' => 'cpt-giallo',        // Licenza Straordinaria
                                                    'lm' => 'cpt-giallo',        // Licenza di Maternità
                                                    
                                                    // GIALLO - ALTRI CODICI DALL'IMMAGINE CPT
                                                    'APS1' => 'cpt-giallo',      // Prelievi (1)
                                                    'APS2' => 'cpt-giallo',      // Vaccini (2)
                                                    'APS3' => 'cpt-giallo',      // ECG (3)
                                                    'APS4' => 'cpt-giallo',      // Idoneità (4)
                                                    'AL-ELIX' => 'cpt-giallo',   // Elitrasporto
                                                    'AL-MCM' => 'cpt-giallo',    // MCM
                                                    'AL-BLS' => 'cpt-giallo',    // BLS
                                                    'AL-CIED' => 'cpt-giallo',   // C-IED
                                                    'AL-SM' => 'cpt-giallo',     // Stress Management
                                                    'AL-RM' => 'cpt-giallo',     // Rapporto Media
                                                    'AL-RSPP' => 'cpt-giallo',   // RSPP
                                                    'AL-LEG' => 'cpt-giallo',    // Aspetti Legali
                                                    'AL-SEA' => 'cpt-giallo',    // Sexual Exploitation and Abuse
                                                    'AL-MI' => 'cpt-giallo',     // Malattie Infettive
                                                    'AL-PO' => 'cpt-giallo',     // Propaganda Ostile
                                                    'AL-PI' => 'cpt-giallo',     // Pubblica Informazione
                                                    'AP-M' => 'cpt-giallo',      // Mantenimento
                                                    'AP-A' => 'cpt-giallo',      // Approntamento
                                                    'AC-SW' => 'cpt-giallo',     // Corso Smart Working
                                                    'AC' => 'cpt-giallo',        // Corso Servizio Isolato
                                                    'PEFO' => 'cpt-giallo',      // PEFO
                                                    
                                                    // ALTRI CODICI COMUNI
                                                    'lsds' => 'cpt-giallo',      // Licenza Straordinaria Domanda di Servizio
                                                    'fp' => 'cpt-giallo',        // Ferie/Permesso
                                                    'is' => 'cpt-giallo',        // Inabilità Servizio
                                                    
                                                    // SERVIZIO (Verde) - MACRO ATTIVITA: SERVIZIO - Codici dall'immagine
                                                    'S-G1' => 'cpt-verde',       // Guardia d'Avanzo Lunga
                                                    'S-G2' => 'cpt-verde',       // Guardia d'Avanzo Corta
                                                    'S-SA' => 'cpt-verde',       // Sorveglianza d'Avanzo
                                                    'S-CD1' => 'cpt-verde',      // Conduttore Guardia Lungo
                                                    'S-CD2' => 'cpt-verde',      // Conduttore Guardia Corto
                                                    'S-CD3' => 'cpt-verde',      // Conduttore Pian del Termine Lungo
                                                    'S-CD4' => 'cpt-verde',      // Conduttore Pian del Termine Corto
                                                    'S-SG' => 'cpt-verde',       // Sottufficiale di Giornata
                                                    'S-CG' => 'cpt-verde',       // Comandante della Guardia
                                                    'S-UI' => 'cpt-verde',       // Ufficiale di Ispezione
                                                    'S-UP' => 'cpt-verde',       // Ufficiale di Picchetto
                                                    'S-AE' => 'cpt-verde',       // Aree Esterne
                                                    'S-ARM' => 'cpt-verde',      // Armiere di Servizio
                                                    'SI-GD' => 'cpt-verde',      // Servizio Isolato-Guardia Distaccata
                                                    'SI' => 'cpt-verde',         // Servizio Isolato-Capomacchina/CAU
                                                    'S-VM' => 'cpt-verde',       // Visita Medica
                                                    'S-PI' => 'cpt-verde',       // Pronto Impiego
                                                    
                                                    // ALTRI SERVIZI COMUNI
                                                    'G1' => 'cpt-verde',         // G1
                                                    'G2' => 'cpt-verde',         // G2
                                                    'CD2' => 'cpt-verde',        // CD2
                                                    'PDT1' => 'cpt-verde',       // PDT1
                                                    'PDT2' => 'cpt-verde',       // PDT2
                                                    'AE' => 'cpt-verde',         // AE
                                                    'A-A' => 'cpt-verde',        // A-A
                                                    'CETLI' => 'cpt-verde',      // CETLI
                                                    
                                                    // ISOLATO (non ha colore specifico nell'immagine - uso Verde)
                                                    
                                                    // APPRONTAMENTI (Arancione) - MACRO ATTIVITA: APPRONTAMENTI/ATTIVITA'  
                                                    'KOSOVO' => 'cpt-arancione', // Missione Kosovo
                                                    'MCM' => 'cpt-arancione',    // Missione Contro Mine
                                                    'C-IED' => 'cpt-arancione',  // Counter-IED
                                                    'LCC' => 'cpt-arancione',    // Comando
                                                    'CENTURIA' => 'cpt-arancione', // Addestramento Centuria
                                                    'TIROCINIO' => 'cpt-arancione', // Tirocinio
                                                    
                                                    // ALTRI (mantengo logica precedente)
                                                    'TRASFERITO' => 'cpt-giallo',
                                                    'CONGEDATO' => 'secondary',
                                                    
                                                    // GIORNI SETTIMANA (neutri)
                                                    'L', 'M', 'G', 'V', 'S', 'D' => 'light',
                                                    
                                                    default => 'light'      // Bianco per codici sconosciuti
                                                };
                                                }
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
                                        data-bs-toggle="tooltip" 
                                        data-bs-html="true"
                                        title="{{ $tooltip }}"
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
                                                  style="font-size: 9px; padding: 2px 4px; min-width: 28px; {{ $inlineStyle }}">
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

<!-- MODAL DUPLICATO RIMOSSO - CAUSAVA CONFLITTI ID -->

<!-- Modal per modificare impegno giornaliero -->
<div class="modal fade" id="editGiornoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Modifica Impegno</h5>
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
                            @foreach($impegni as $impegno)
                                <option value="{{ $impegno->id }}">{{ $impegno->codice }} - {{ $impegno->nome }}</option>
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
/* Header fisso con body scrollabile */
.table-container {
    position: relative;
    height: 70vh;
    display: flex;
    flex-direction: column;
}

.table-header-fixed {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 2px solid #dee2e6;
    flex-shrink: 0;
    /* Nascondi scrollbar dell'header */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
}

.table-header-fixed::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.table-body-scroll {
    flex: 1;
    overflow: auto;
    position: relative;
}

/* Sincronizza scroll orizzontale */
.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed;
    width: 2340px; /* Larghezza fissa per garantire scroll */
    min-width: 2340px;
}

/* Container delle tabelle deve avere overflow */
.table-header-fixed,
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

/* Container scroll principale */
.cpt-scroll-container {
    width: 100%;
    height: 75vh;
    overflow: auto !important;
    position: relative;
}

/* Assicura che la tabella abbia il contesto di posizionamento corretto */
#pianificazioneTable {
    position: relative;
}

/* Scrollbar personalizzata per stile CPT */
.cpt-scroll-container::-webkit-scrollbar {
    width: 14px;
    height: 14px;
}

.cpt-scroll-container::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 7px;
}

.cpt-scroll-container::-webkit-scrollbar-thumb {
    background: #495057;
    border-radius: 7px;
    border: 2px solid #f8f9fa;
}

.cpt-scroll-container::-webkit-scrollbar-thumb:hover {
    background: #343a40;
}

.cpt-scroll-container::-webkit-scrollbar-corner {
    background: #f8f9fa;
}

/* Miglioramenti per sticky columns */
.sticky-column {
    position: sticky;
    left: 0;
    background-color: #fff !important;
    z-index: 20;
    border-right: 2px solid #dee2e6 !important;
    font-size: 12px;
    padding: 4px 6px;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.table-dark .sticky-column {
    background-color: #212529 !important;
    box-shadow: 2px 0 5px rgba(0,0,0,0.3);
}

/* Colori CPT ESATTI dall'immagine fornita */
.bg-cpt-verde { 
    background-color: #00b050 !important; 
    color: white !important;
} /* DISPONIBILE e SERVIZIO - Verde CPT */

.bg-cpt-giallo { 
    background-color: #ffff00 !important; 
    color: black !important;
} /* ASSENTE - Giallo CPT */

.bg-cpt-rosso { 
    background-color: #ff0000 !important; 
    color: white !important;
} /* NON IMPIEGABILE - Rosso CPT */

.bg-cpt-arancione { 
    background-color: #ffc000 !important; 
    color: black !important;
} /* APPRONTAMENTI - Arancione CPT */

/* Mantieni i colori Bootstrap esistenti per compatibilità */
.bg-success { background-color: #00b050 !important; color: white !important; }
.bg-warning { background-color: #ffff00 !important; color: black !important; }
.bg-danger { background-color: #ff0000 !important; color: white !important; }
.bg-primary { background-color: #00b050 !important; color: white !important; }
.bg-info { background-color: #00b050 !important; color: white !important; }
.bg-dark { background-color: #ffc000 !important; color: black !important; }
.bg-secondary { background-color: #6c757d !important; color: white !important; }
.bg-light { background-color: #f8f9fa !important; color: #495057 !important; }
</style>
@endpush

@push('scripts')
<script>

// Salva le modifiche
document.getElementById('saveGiornoBtn')?.addEventListener('click', function() {
    const form = document.getElementById('editGiornoForm');
    if (!form) return;
    
    const militareIdEl = document.getElementById('editMilitareId');
    const giornoEl = document.getElementById('editGiorno');
    const pianificazioneMensileIdEl = document.getElementById('editPianificazioneMensileId');
    const tipoServizioEl = document.getElementById('editTipoServizio');
    
    const militareId = militareIdEl?.value;
    const giorno = giornoEl?.value;
    const pianificazioneMensileId = pianificazioneMensileIdEl?.value;
    const tipoServizioId = tipoServizioEl?.value;
    
    if (!militareId || !giorno || !pianificazioneMensileId) {
        alert('Dati mancanti per il salvataggio');
        return;
    }
    
    const updateUrl = `{{ route('pianificazione.militare.update-giorno', ':militare') }}`.replace(':militare', militareId);
    const csrfToken = '{{ csrf_token() }}';
    
    fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            pianificazione_mensile_id: pianificazioneMensileId,
            giorno: giorno,
            tipo_servizio_id: tipoServizioId || null
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Chiudi il modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editGiornoModal'));
            if (modal) modal.hide();
            
            // Aggiorna la cella specifica
            updateCellContent(militareId, giorno, data.pianificazione);
        } else {
            alert('Errore nel salvataggio: ' + (data.message || 'Errore sconosciuto'));
        }
    })
    .catch(error => {
        alert('Errore nel salvataggio: ' + error.message);
    });
});

// Funzione per aggiornare il contenuto di una cella specifica
function updateCellContent(militareId, giorno, pianificazioneData) {
    
    // Trova la cella specifica
    const cell = document.querySelector(`[data-militare-id="${militareId}"][data-giorno="${giorno}"]`);
    if (!cell) return;
    
    // Pulisci il contenuto attuale
    cell.innerHTML = '';
    
    // Se c'è una pianificazione, aggiungi il badge
    if (pianificazioneData && pianificazioneData.tipo_servizio && pianificazioneData.tipo_servizio.codice) {
        const codice = pianificazioneData.tipo_servizio.codice;
        
        // Determina il colore basato sul codice (stesso logic della view)
        let colore = 'light';
        let inlineStyle = '';
        
        switch(codice) {
            case 'TO':
            case 'RMD':
            case 'lc':
                colore = 'cpt-rosso';
                inlineStyle = 'background-color: #ff0000 !important; color: white !important;';
                break;
            case 'lo':
            case 'p':
            case 'ls':
            case 'lm':
                colore = 'cpt-giallo';
                inlineStyle = 'background-color: #ffff00 !important; color: black !important;';
                break;
            case 'KOSOVO':
            case 'MCM':
                colore = 'cpt-arancione';
                inlineStyle = 'background-color: #ffa500 !important; color: white !important;';
                break;
            default:
                // Altri codici verdi
                colore = 'cpt-verde';
                inlineStyle = 'background-color: #00b050 !important; color: white !important;';
                break;
        }
        
        // Crea il badge
        const badge = document.createElement('span');
        badge.className = `badge ${colore}`;
        badge.style.cssText = `font-size: 9px; padding: 2px 4px; ${inlineStyle}`;
        badge.textContent = codice;
        badge.title = pianificazioneData.tipo_servizio.nome || codice;
        
        cell.appendChild(badge);
        
        // Aggiorna gli attributi data
        cell.setAttribute('data-tipo-servizio-id', pianificazioneData.tipo_servizio.id);
        cell.setAttribute('title', pianificazioneData.tipo_servizio.nome || codice);
    } else {
        // Nessuna pianificazione - cella vuota
        cell.setAttribute('data-tipo-servizio-id', '');
        cell.setAttribute('title', 'Nessuna pianificazione');
    }
    
    // Feedback visivo di successo
    cell.style.backgroundColor = '#d4edda';
    setTimeout(() => {
        cell.style.backgroundColor = '';
    }, 1000);
}

// Export Excel
document.getElementById('exportExcel').addEventListener('click', function() {
    const mese = {{ $mese }};
    const anno = {{ $anno }};
    
    // Crea URL per l'export con parametri
    const exportUrl = `{{ route('pianificazione.export-excel') }}?mese=${mese}&anno=${anno}`;
    
    // Apri in una nuova finestra per il download
    window.open(exportUrl, '_blank');
});


// Codice che deve essere eseguito dopo il caricamento del DOM
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filtri
    const toggleFilters = document.getElementById('toggleFilters');
    const filtersContainer = document.getElementById('filtersContainer');
    const toggleFiltersText = document.getElementById('toggleFiltersText');
    const toggleFiltersIcon = document.getElementById('toggleFiltersIcon');
    
    if (toggleFilters && filtersContainer) {
        toggleFilters.addEventListener('click', function(e) {
            e.preventDefault();
            
            const isVisible = filtersContainer.classList.toggle('visible');
            toggleFilters.classList.toggle('active', isVisible);
            
            if (toggleFiltersText) {
                toggleFiltersText.textContent = isVisible ? 'Nascondi filtri' : 'Mostra filtri';
            }
            
            if (toggleFiltersIcon) {
                toggleFiltersIcon.className = isVisible ? 'fas fa-filter-circle-xmark me-2' : 'fas fa-filter me-2';
            }
        });
    }
    
    // Gestione form filtri
    const filtroForm = document.getElementById('filtroForm');
    if (filtroForm) {
        // Aggiungi parametri mese e anno al form
        const meseInput = document.createElement('input');
        meseInput.type = 'hidden';
        meseInput.name = 'mese';
        meseInput.value = {{ $mese }};
        filtroForm.appendChild(meseInput);
        
        const annoInput = document.createElement('input');
        annoInput.type = 'hidden';
        annoInput.name = 'anno';
        annoInput.value = {{ $anno }};
        filtroForm.appendChild(annoInput);

        // Auto-submit quando cambiano i filtri
        const filterSelects = filtroForm.querySelectorAll('.filter-select');
        filterSelects.forEach(select => {
            select.addEventListener('change', function() {
                filtroForm.submit();
            });
        });

        // Gestione clear filter
        const clearFilters = filtroForm.querySelectorAll('.clear-filter');
        clearFilters.forEach(clear => {
            clear.addEventListener('click', function() {
                const filterName = this.getAttribute('data-filter');
                const select = filtroForm.querySelector(`[name="${filterName}"]`);
                if (select) {
                    select.value = '';
                    filtroForm.submit();
                }
            });
        });
    }

    // Ricerca militari
    const searchInput = document.getElementById('searchMilitare');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#pianificazioneTable tbody tr');
            
            rows.forEach(row => {
                const grado = row.cells[0]?.textContent.toLowerCase() || '';
                const cognome = row.cells[1]?.textContent.toLowerCase() || '';
                const nome = row.cells[2]?.textContent.toLowerCase() || '';
                const searchText = `${grado} ${cognome} ${nome}`;
                
                if (searchText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Filtri automatici al cambio
    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', function() {
            const form = document.getElementById('filtroForm');
            if (form) {
                form.submit();
            }
        });
    });

    // Clear individual filters
    document.querySelectorAll('.clear-filter').forEach(button => {
        button.addEventListener('click', function() {
            const filterName = this.dataset.filter;
            const filterInput = document.getElementById(filterName);
            if (filterInput) {
                filterInput.value = '';
                const form = document.getElementById('filtroForm');
                if (form) {
                    form.submit();
                }
            }
        });
    });
});
    // Inizializza tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Toggle weekend columns
    document.getElementById('toggleWeekends')?.addEventListener('click', function() {
        const weekendColumns = document.querySelectorAll('.weekend-column');
        const isHidden = weekendColumns[0]?.style.display === 'none';
        
        weekendColumns.forEach(col => {
            col.style.display = isHidden ? '' : 'none';
        });
        
        this.innerHTML = isHidden ? 
            '<i class="fas fa-calendar-week me-1"></i> Nascondi Weekend' : 
            '<i class="fas fa-calendar-week me-1"></i> Mostra Weekend';
    });

    // Inizializza il modulo Filters per l'auto-submit
    if (typeof C2MS !== 'undefined' && typeof C2MS.Filters !== 'undefined') {
        C2MS.Filters.init();
    }
});

</script>
@endpush

