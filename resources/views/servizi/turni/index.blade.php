@extends('layouts.app')

@section('title', 'Turni Settimanali')

<!-- Carica CSS personalizzato per toast e modals -->
<link rel="stylesheet" href="{{ asset('css/turni-custom.css') }}?v={{ time() }}">

@section('content')
<style>
/* Sfondo alternato per la tabella - come le altre pagine */
.turni-table tbody tr {
    background-color: #fafafa;
}

.turni-table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

/* Stili coerenti con le altre pagine - hover su tutta la riga */
.turni-table tbody tr:hover td {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

/* Bordi uniformi - come le altre pagine */
.turni-table thead th,
.turni-table tbody td {
    border: 1px solid rgba(10, 35, 66, 0.20) !important;
}

.turni-table {
    width: 100%;
    border-collapse: collapse;
}

.turni-table thead th {
    background: #0a2342;
    color: white;
    padding: 12px 8px;
    text-align: center;
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

.turni-table tbody td {
    padding: 10px 8px;
    vertical-align: middle;
}

.turni-table tbody td.servizio-nome {
    background: #e9ecef;
    font-weight: 600;
    text-align: left;
    min-width: 210px;
    max-width: 200px;
    width: 200px;
    white-space: normal;
    word-wrap: break-word;
    line-height: 1.3;
    position: sticky;
    left: 0;
    z-index: 5;
}

.turni-table tbody td.giorno-cell {
    text-align: center;
    min-width: 150px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.turni-table tbody td.giorno-cell:hover {
    background-color: #f8f9fa;
}

/* Weekend e festivi come nel CPT - solo testo rosso, background normale */
.turni-table tbody td.weekend {
    background-color: rgba(255, 0, 0, 0.12) !important;
}

.turni-table thead th.weekend {
    background-color: #0a2342 !important; /* Stesso background degli altri giorni */
}

.turni-table thead th.weekend div,
.turni-table thead th.weekend .date-badge {
    color: #dc3545 !important; /* Testo rosso */
    font-weight: 700 !important;
}

.turni-table thead th.weekend .date-badge {
    background: #0a2342 !important; /* Stesso background dell'header */
    color: #dc3545 !important; /* Testo rosso */
}

/* Hover su weekend mantiene effetto */
.turni-table tbody tr:hover td.weekend {
    background-color: rgba(10, 35, 66, 0.15) !important;
}

.militare-badge {
    display: inline-block;
    padding: 6px 12px;
    background: #28a745;
    color: white;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    position: relative;
    margin: 2px;
}

.militare-badge:hover {
    background: #218838;
}

.militare-badge .remove-btn {
    margin-left: 8px;
    color: white;
    cursor: pointer;
    font-weight: bold;
}

.posto-vuoto {
    color: #6c757d;
    font-style: italic;
    font-size: 1.2rem;
}

.date-badge {
    background: #0a2342;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    display: block;
    margin-top: 4px;
}


/* Dropdown categorie militari */
.militari-dropdown optgroup {
    font-weight: bold;
    font-style: normal;
    color: #0a2342;
    background: #f8f9fa;
}

.militari-dropdown option {
    padding-left: 10px;
}

/* Loading spinner */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.3);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.spinner-border-lg {
    width: 3rem;
    height: 3rem;
}
</style>

<div class="container-fluid">
    <!-- Header stile standard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">Turni Settimanali</h1>
            </div>
        </div>
    </div>

    <!-- Navigazione Settimana -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('servizi.turni.index', ['data' => $turno->data_inizio->copy()->subWeek()->format('Y-m-d')]) }}" 
                   class="btn btn-outline-primary" style="border-radius: 6px !important;">
                    <i class="fas fa-chevron-left"></i> Settimana Precedente
                </a>
                
                <div class="text-center">
                    <h5 class="mb-0">Settimana {{ $turno->numero_settimana }} - Anno {{ $turno->anno }}</h5>
                    <p class="mb-0 text-muted">
                        Dal {{ $turno->data_inizio->format('d/m/Y') }} al {{ $turno->data_fine->format('d/m/Y') }}
                    </p>
                </div>

                <a href="{{ route('servizi.turni.index', ['data' => $turno->data_inizio->copy()->addWeek()->format('Y-m-d')]) }}" 
                   class="btn btn-outline-primary" style="border-radius: 6px !important;">
                    Settimana Successiva <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Navigazione Giornaliera + Comandante + Gestione servizi -->
    <div class="row mb-3">
        <div class="col-lg-6 mb-2 mb-lg-0">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('servizi.turni.index', ['data' => $dataRiferimento->copy()->subDay()->format('Y-m-d')]) }}" 
                   class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                    <i class="fas fa-chevron-left"></i> Giorno precedente
                </a>
                <input type="date" id="dataRiferimento" class="form-control form-control-sm" 
                       style="max-width: 170px;" value="{{ $dataRiferimento->format('Y-m-d') }}">
                <a href="{{ route('servizi.turni.index', ['data' => $dataRiferimento->copy()->addDay()->format('Y-m-d')]) }}" 
                   class="btn btn-outline-secondary btn-sm" style="border-radius: 6px !important;">
                    Giorno successivo <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="d-flex align-items-center justify-content-lg-end flex-wrap gap-2">
                <div class="input-group input-group-sm" style="max-width: 440px;">
                    <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                    <input type="text" id="comandanteCompagnia" class="form-control"
                           value="{{ $comandanteCompagnia }}" placeholder="Comandante di compagnia">
                    <button class="btn btn-outline-success" type="button" onclick="salvaComandante()">
                        Salva
                    </button>
                </div>
                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modalGestioneServizi">
                    <i class="fas fa-cog"></i> Gestione servizi
                </button>
            </div>
        </div>
    </div>

    <!-- Export button removed - now using floating button -->

    <!-- Tabella Turni -->
    <div class="card">
        <div class="card-body p-0">
            <div style="overflow-x: auto;">
                <table class="turni-table">
                    <thead>
                        <tr>
                            <th style="min-width: 210px; max-width: 200px; width: 200px;">TIPO DI SERVIZIO</th>
                            @foreach($giorniSettimana as $giorno)
                                <th class="{{ $giorno['is_weekend'] ? 'weekend' : '' }}">
                                    <div>{{ $giorno['giorno_settimana'] }}</div>
                                    <div class="date-badge {{ $giorno['is_weekend'] ? 'weekend' : '' }}">
                                        {{ $giorno['data']->format('d/m') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($serviziTurno as $servizio)
                            <tr>
                                <td class="servizio-nome">
                                    {{ $servizio->nome }}
                                </td>
                                
                                @foreach($giorniSettimana as $giorno)
                                    @php
                                        $dataKey = $giorno['data']->format('Y-m-d');
                                        $assegnazioni = $matriceTurni[$servizio->id]['assegnazioni'][$dataKey] ?? collect();
                                    @endphp
                                    <td class="giorno-cell {{ $giorno['is_weekend'] ? 'weekend' : '' }}" 
                                        onclick="apriModalAssegnazione({{ $servizio->id }}, '{{ $dataKey }}', '{{ addslashes($giorno['giorno_settimana']) }}', '{{ addslashes($servizio->nome) }}', {{ $servizio->num_posti }}, {{ $assegnazioni->count() }})">
                                        
                                        @if($assegnazioni->isNotEmpty())
                                            @foreach($assegnazioni as $assegnazione)
                                                <div class="militare-badge mb-1" onclick="event.stopPropagation()">
                                                    {{ $assegnazione->militare->grado->sigla ?? '' }} {{ strtoupper($assegnazione->militare->cognome) }}
                                                    <span class="remove-btn" 
                                                          onclick="rimuoviAssegnazione(
                                                              {{ $assegnazione->id }}, 
                                                              '{{ addslashes($servizio->nome) }}',
                                                              '{{ $giorno['giorno_settimana'] }} {{ $giorno['data']->format('d/m/Y') }}',
                                                              '{{ $assegnazione->militare->grado->sigla ?? '' }} {{ $assegnazione->militare->cognome }} {{ $assegnazione->militare->nome }}',
                                                              event
                                                          )"
                                                          title="Rimuovi">
                                                        ×
                                                    </span>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="posto-vuoto">&nbsp;</div>
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

<!-- Modal Gestione Servizi -->
<div class="modal fade" id="modalGestioneServizi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestione Servizi Turni</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <strong>Sigla CPT:</strong> identifica il servizio in modo univoco ed è usata per la sincronizzazione con il CPT.
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Sigla CPT</th>
                                <th>Posti</th>
                                <th>Smontante (CPT +1)</th>
                                <th class="text-end">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($serviziTurno as $servizio)
                                <tr>
                                    <td style="min-width: 220px;">
                                        <input type="text" class="form-control form-control-sm" 
                                               id="servizio-nome-{{ $servizio->id }}" 
                                               value="{{ $servizio->nome }}">
                                    </td>
                                    <td style="max-width: 140px;">
                                        <input type="text" class="form-control form-control-sm" 
                                               id="servizio-sigla-{{ $servizio->id }}" 
                                               value="{{ $servizio->sigla_cpt }}">
                                    </td>
                                    <td style="max-width: 90px;">
                                        <input type="number" min="1" class="form-control form-control-sm" 
                                               id="servizio-posti-{{ $servizio->id }}" 
                                               value="{{ $servizio->num_posti }}">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                               id="servizio-smontante-{{ $servizio->id }}"
                                               {{ $servizio->smontante_cpt ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-primary" onclick="aggiornaServizio({{ $servizio->id }})">
                                            Salva
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="rimuoviServizio({{ $servizio->id }}, '{{ addslashes($servizio->nome) }}')">
                                            Rimuovi
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>

                <h6 class="mb-2">Aggiungi nuovo servizio</h6>
                <small class="text-muted d-block mb-2">
                    La sigla CPT è obbligatoria.
                </small>
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="nuovoServizioNome" 
                               placeholder="Nome servizio">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm" id="nuovoServizioSigla" 
                               placeholder="Sigla CPT (es. G1)">
                    </div>
                    <div class="col-md-2">
                        <input type="number" min="1" class="form-control form-control-sm" id="nuovoServizioPosti" 
                               placeholder="Posti" value="1">
                    </div>
                    <div class="col-md-2 d-flex align-items-center">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="nuovoServizioSmontante">
                            <label class="form-check-label" for="nuovoServizioSmontante">
                                Smontante +1
                            </label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-success btn-sm w-100" onclick="creaServizio()">
                            <i class="fas fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    I servizi rimossi vengono disattivati e non saranno più mostrati nella tabella.
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Modal Assegnazione Militare -->
<div class="modal fade" id="modalAssegnazione" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assegna Militare al Servizio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label"><strong>Servizio:</strong> <span id="nomeServizio"></span></label>
                    <br>
                    <label class="form-label"><strong>Data:</strong> <span id="dataServizio"></span></label>
                    <br>
                    <label class="form-label"><strong>Posti:</strong> <span id="posizioneServizio"></span></label>
                </div>

                <div class="mb-3">
                    <label class="form-label">Seleziona Militari</label>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
                        @php
                            // Funzione helper per classificare il grado
                            $classificaGrado = function($nomeGrado) {
                                $nomeGrado = strtolower(trim($nomeGrado));
                                
                                // UFFICIALI - match precisi (da Colonnello a Tenente)
                                if ($nomeGrado === 'colonnello' || $nomeGrado === 'ten. col.' || $nomeGrado === 'tenente colonnello') return 'UFFICIALI';
                                if ($nomeGrado === 'maggiore' || $nomeGrado === 'magg.') return 'UFFICIALI';
                                if ($nomeGrado === 'capitano' || $nomeGrado === 'cap.') return 'UFFICIALI';
                                // Tenente (ma NON luogotenente)
                                if (($nomeGrado === 'tenente' || $nomeGrado === 'ten.') && !str_contains($nomeGrado, 'luogo')) return 'UFFICIALI';
                                if ($nomeGrado === 'sottotenente' || $nomeGrado === 'sotten.') return 'UFFICIALI';
                                
                                // SOTTUFFICIALI - match precisi (da 1° Luogotenente a Sergente)
                                if (str_contains($nomeGrado, 'luogoten')) return 'SOTTUFFICIALI';
                                if (str_contains($nomeGrado, 'maresciall')) return 'SOTTUFFICIALI';
                                if (str_contains($nomeGrado, 'sergent')) return 'SOTTUFFICIALI';
                                if ($nomeGrado === 'mar.' || $nomeGrado === 'serg.' || str_contains($nomeGrado, 'serg.')) return 'SOTTUFFICIALI';
                                
                                // GRADUATI - match precisi (Graduato Aiutante, Graduato, Graduato Scelto)
                                if (str_contains($nomeGrado, 'graduato')) return 'GRADUATI';
                                if ($nomeGrado === 'grad.' || $nomeGrado === 'grad. sc.' || $nomeGrado === 'grad. ai.') return 'GRADUATI';
                                
                                // VOLONTARI - tutto il resto (da Caporal Maggiore a Soldato)
                                return 'VOLONTARI';
                            };
                            
                            $militariPerCategoria = [
                                'UFFICIALI' => collect(),
                                'SOTTUFFICIALI' => collect(),
                                'GRADUATI' => collect(),
                                'VOLONTARI' => collect(),
                            ];
                            
                            foreach ($militari as $militare) {
                                if (!$militare->grado) continue;
                                $categoria = $classificaGrado($militare->grado->nome);
                                $militariPerCategoria[$categoria]->push($militare);
                            }
                        @endphp
                        
                        @foreach($militariPerCategoria as $categoria => $militariCategoria)
                            @if($militariCategoria->isNotEmpty())
                                <div class="mb-3">
                                    <strong class="d-block mb-2" style="color: #0a2342; font-size: 0.9rem;">{{ $categoria }}</strong>
                                    @foreach($militariCategoria as $militare)
                                        <div class="form-check">
                                            <input class="form-check-input militare-checkbox" 
                                                   type="checkbox" 
                                                   value="{{ $militare->id }}" 
                                                   id="militare_{{ $militare->id }}"
                                                   data-nome="{{ $militare->grado->sigla ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}">
                                            <label class="form-check-label" for="militare_{{ $militare->id }}">
                                                {{ $militare->grado->sigla ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                                @if($militare->plotone)
                                                    <small class="text-muted">- {{ $militare->plotone->nome }}</small>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                        
                        @if($militari->isEmpty())
                            <p class="text-muted">Nessun militare disponibile</p>
                        @endif
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselezionaTutti()">
                            <i class="fas fa-square"></i> Deseleziona tutti
                        </button>
                    </div>
                </div>

                <!-- Banner unico per disponibilità e conflitti -->
                <div id="risultatoVerifica" class="alert d-none">
                    <div id="militariDisponibili"></div>
                    <div id="militariConflitti"></div>
                </div>
                
                <!-- Banner errori assegnazione -->
                <div id="erroriAssegnazione" class="alert alert-danger d-none">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-circle me-3" style="font-size: 1.5rem; margin-top: 3px;"></i>
                        <div style="flex: 1;">
                            <h6 class="mb-2"><strong>Errori durante l'assegnazione</strong></h6>
                            <div id="listaErrori"></div>
                            <hr class="my-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Alcuni militari non sono stati assegnati. Verifica i conflitti sopra e prova a forzare l'assegnazione o a deselezionarli.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="flex-direction: column; align-items: stretch;">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-warning" id="btnVerifica" onclick="verificaDisponibilitaMultipla()" disabled>
                        <i class="fas fa-search"></i> Verifica disponibilità
                    </button>
                    <button type="button" class="btn btn-success" id="btnConferma" onclick="confermaAssegnazione()" disabled>
                        <i class="fas fa-check"></i> Conferma
                    </button>
                </div>
                <small class="text-muted text-center mt-2" style="font-size: 0.875rem;">
                    <i class="fas fa-info-circle"></i> Seleziona i militari e clicca "Verifica disponibilità"
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div id="loadingSpinner" class="spinner-overlay d-none">
    <div class="spinner-border spinner-border-lg text-light" role="status">
        <span class="visually-hidden">Caricamento...</span>
    </div>
</div>

<script>
// Variabili globali
let currentServizioId = null;
let currentData = null;
let currentGiornoSettimana = null;
let currentNomeServizio = null;
let currentMaxPosti = null;
let currentNumAssegnati = null;
let hasConflict = false;

const turnoId = {{ $turno->id }};
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const baseTurniUrl = "{{ route('servizi.turni.index') }}";

// Mostra/nascondi spinner
function showSpinner() {
    document.getElementById('loadingSpinner').classList.remove('d-none');
}

function hideSpinner() {
    document.getElementById('loadingSpinner').classList.add('d-none');
}

// Toast globali: gestiti da toast-system.js

function getPostiDisponibili() {
    if (currentMaxPosti === null || currentNumAssegnati === null) {
        return 0;
    }
    return Math.max(currentMaxPosti - currentNumAssegnati, 0);
}

function enforceMaxPosti(checkbox) {
    if (!checkbox) {
        return true;
    }

    const postiDisponibili = getPostiDisponibili();
    const selezionati = document.querySelectorAll('.militare-checkbox:checked').length;

    if (postiDisponibili === 0) {
        checkbox.checked = false;
        showToast('Posti esauriti per questa data', 'warning');
        return false;
    }

    if (selezionati > postiDisponibili) {
        checkbox.checked = false;
        showToast(`Puoi selezionare al massimo ${postiDisponibili} militare/i`, 'warning');
        return false;
    }

    return true;
}

function extractErrorMessage(result) {
    if (!result) {
        return 'Errore imprevisto';
    }
    if (result.message) {
        return result.message;
    }
    if (result.errors) {
        const first = Object.values(result.errors)[0];
        if (Array.isArray(first) && first.length > 0) {
            return first[0];
        }
    }
    return 'Errore imprevisto';
}

// Navigazione giornaliera: cambia data di riferimento
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('dataRiferimento');
    if (dateInput) {
        dateInput.addEventListener('change', () => {
            if (dateInput.value) {
                window.location.href = `${baseTurniUrl}?data=${dateInput.value}`;
            }
        });
    }
});

// Apri modal per assegnazione
function apriModalAssegnazione(servizioId, data, giornoSettimana, nomeServizio, maxPosti, numAssegnati) {
    currentServizioId = servizioId;
    currentData = data;
    currentGiornoSettimana = giornoSettimana;
    currentNomeServizio = nomeServizio;
    currentMaxPosti = maxPosti;
    currentNumAssegnati = numAssegnati;
    hasConflict = false;

    // Reset form - deseleziona tutti i checkbox
    document.querySelectorAll('.militare-checkbox').forEach(cb => {
        cb.checked = false;
        delete cb.dataset.forza;
        cb.disabled = false;
    });
    document.getElementById('risultatoVerifica').classList.add('d-none');
    document.getElementById('erroriAssegnazione').classList.add('d-none');
    document.getElementById('btnConferma').disabled = true;
    document.getElementById('btnVerifica').disabled = true; // Disabilita anche Verifica

    // Popola info servizio
    document.getElementById('nomeServizio').textContent = nomeServizio;
    document.getElementById('dataServizio').textContent = giornoSettimana + ' ' + data;
    document.getElementById('posizioneServizio').textContent = `${numAssegnati} assegnati su ${maxPosti} (disponibili: ${getPostiDisponibili()})`;

    const postiDisponibili = getPostiDisponibili();
    if (postiDisponibili === 0) {
        document.querySelectorAll('.militare-checkbox').forEach(cb => {
            cb.disabled = true;
        });
    }

    // Aggiungi listener per abilitare/disabilitare pulsante Verifica
    document.querySelectorAll('.militare-checkbox').forEach(cb => {
        cb.onchange = () => {
            if (!enforceMaxPosti(cb)) {
                return;
            }
            aggiornaStatoPulsantiModal();
        };
    });

    // Mostra modal
    new bootstrap.Modal(document.getElementById('modalAssegnazione')).show();
}

// Aggiorna stato pulsanti del modal in base alle selezioni
function aggiornaStatoPulsantiModal() {
    const checkboxesSelezionati = document.querySelectorAll('.militare-checkbox:checked').length;
    const btnVerifica = document.getElementById('btnVerifica');
    
    if (checkboxesSelezionati > 0) {
        btnVerifica.disabled = false;
    } else {
        btnVerifica.disabled = true;
        // Se non ci sono più selezioni, nascondi i risultati e disabilita conferma
        document.getElementById('risultatoVerifica').classList.add('d-none');
        document.getElementById('btnConferma').disabled = true;
    }
}

// Seleziona/Deseleziona tutti
function selezionaTutti() {
    const maxSelezionabili = getPostiDisponibili();
    let selezionati = 0;
    document.querySelectorAll('.militare-checkbox').forEach(cb => {
        if (selezionati < maxSelezionabili) {
            cb.checked = true;
            selezionati++;
        } else {
            cb.checked = false;
        }
    });
    aggiornaStatoPulsantiModal();
}

function deselezionaTutti() {
    document.querySelectorAll('.militare-checkbox').forEach(cb => {
        cb.checked = false;
        delete cb.dataset.forza;
    });
    document.getElementById('risultatoVerifica').classList.add('d-none');
    document.getElementById('erroriAssegnazione').classList.add('d-none');
    document.getElementById('btnConferma').disabled = true;
    aggiornaStatoPulsantiModal(); // Disabilita anche Verifica
    hasConflict = false;
}

// Verifica disponibilità per selezione multipla con checkbox
async function verificaDisponibilitaMultipla() {
    const checkboxes = document.querySelectorAll('.militare-checkbox:checked');
    
    // Validazione già gestita dal pulsante disabilitato
    if (checkboxes.length === 0) {
        return;
    }

    showSpinner();
    
    let conflittiTotali = 0;
    let disponibiliTotali = 0;
    const militariDisponibili = [];
    const militariConflitti = [];
    
    for (const checkbox of checkboxes) {
        const militareId = checkbox.value;
        const militareNome = checkbox.dataset.nome;
        
        // Verifica disponibilità
        try {
            const response = await fetch('{{ route("servizi.turni.check-disponibilita") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    militare_id: militareId,
                    data: currentData
                })
            });

            const data = await response.json();

            if (data.disponibile) {
                disponibiliTotali++;
                militariDisponibili.push(militareNome);
                delete checkbox.dataset.conflitto; // Rimuovi flag conflitto se presente
            } else {
                conflittiTotali++;
                checkbox.dataset.conflitto = 'true'; // Marca come conflitto
                militariConflitti.push({
                    nome: militareNome,
                    motivo: data.motivo,
                    checkbox: checkbox
                });
            }

        } catch (error) {
            console.error('Errore verifica disponibilità:', error);
        }
    }
    
    hideSpinner();
    
    // Mostra risultato in un unico banner
    const risultatoDiv = document.getElementById('risultatoVerifica');
    const disponibiliDiv = document.getElementById('militariDisponibili');
    const conflittiDiv = document.getElementById('militariConflitti');
    
    if (conflittiTotali > 0) {
        // Banner giallo con conflitti
        risultatoDiv.className = 'alert alert-warning';
        
        // Sezione disponibili
        if (disponibiliTotali > 0) {
            disponibiliDiv.innerHTML = `
                <div class="mb-3">
                    <strong class="text-success">✓ ${disponibiliTotali} Militare/i disponibile/i:</strong>
                    <div class="mt-1">${militariDisponibili.map(nome => `<span class="badge bg-success me-1">${nome}</span>`).join('')}</div>
                </div>
            `;
        } else {
            disponibiliDiv.innerHTML = '';
        }
        
        // Sezione conflitti con opzioni
        conflittiDiv.innerHTML = `
            <div>
                <strong class="text-danger">⚠️ ${conflittiTotali} Militare/i con conflitto:</strong>
                <div class="mt-2" style="background: #fff; padding: 10px; border-radius: 4px; border-left: 4px solid #dc3545;">
                    ${militariConflitti.map((m, idx) => `
                        <div class="militare-conflitto-card mb-2 pb-2 ${idx < militariConflitti.length - 1 ? 'border-bottom' : ''}" id="conflitto-${m.checkbox.value}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong>${m.nome}</strong>
                                    <span id="badge-forzato-${m.checkbox.value}" class="badge bg-success ms-2 d-none">
                                        <i class="fas fa-check"></i> Forzatura attiva
                                    </span>
                                </div>
                            </div>
                            <div class="text-muted small mt-1">${m.motivo}</div>
                            <div class="mt-2" id="azioni-${m.checkbox.value}">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deselezionaMilitare('militare_${m.checkbox.value}')">
                                    <i class="fas fa-times"></i> Deseleziona
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" id="btn-forza-${m.checkbox.value}" onclick="forzaAssegnazioneMilitare('${m.checkbox.value}', '${m.nome.replace(/'/g, "\\'")}')">
                                    <i class="fas fa-exclamation-triangle"></i> Forza assegnazione
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="alert alert-info mt-3 mb-0">
                    <small>
                        <i class="fas fa-info-circle"></i> 
                        Puoi deselezionare i militari con conflitto o forzare l'assegnazione per sovrascrivere i loro impegni attuali.
                    </small>
                </div>
            </div>
        `;
        
        hasConflict = true;
        // CRITICO: Disabilita il pulsante finché non risolve TUTTI i conflitti
        document.getElementById('btnConferma').disabled = true;
    } else {
        // Banner verde - tutto ok
        risultatoDiv.className = 'alert alert-success';
        disponibiliDiv.innerHTML = `
            <div>
                <strong class="text-success">✓ Tutti i ${disponibiliTotali} militari sono disponibili</strong>
                <div class="mt-2">${militariDisponibili.map(nome => `<span class="badge bg-success me-1 mb-1">${nome}</span>`).join('')}</div>
            </div>
        `;
        conflittiDiv.innerHTML = '';
        hasConflict = false;
        // Abilita il pulsante - tutti disponibili
        document.getElementById('btnConferma').disabled = false;
    }
    
    risultatoDiv.classList.remove('d-none');
}

// Deseleziona un militare specifico
function deselezionaMilitare(checkboxId) {
    const checkbox = document.getElementById(checkboxId);
    if (checkbox) {
        const militareId = checkbox.value;
        
        // 1. Deseleziona il checkbox
        checkbox.checked = false;
        delete checkbox.dataset.forza; // Rimuovi flag forzatura se presente
        delete checkbox.dataset.conflitto; // Rimuovi flag conflitto
        
        // 2. Rimuovi la card del conflitto dal DOM
        const conflittoCard = document.getElementById(`conflitto-${militareId}`);
        if (conflittoCard) {
            conflittoCard.remove();
        }
        
        // 3. Controlla se ci sono ancora militari selezionati
        const checkboxesRimaste = document.querySelectorAll('.militare-checkbox:checked');
        
        if (checkboxesRimaste.length === 0) {
            // Nessun militare selezionato - nascondi tutto e disabilita conferma
            document.getElementById('risultatoVerifica').classList.add('d-none');
            document.getElementById('btnConferma').disabled = true;
        } else {
            // Ci sono ancora militari - controlla se ci sono ancora conflitti
            const conflittiRimasti = document.querySelectorAll('[id^="conflitto-"]');
            
            if (conflittiRimasti.length === 0) {
                // Nessun conflitto rimasto - abilita conferma
                document.getElementById('btnConferma').disabled = false;
            } else {
                // Ci sono ancora conflitti - mantieni disabilitato
                document.getElementById('btnConferma').disabled = true;
            }
        }
    }
}

// Forza assegnazione per un militare specifico (aggiunge attributo data e feedback visivo)
function forzaAssegnazioneMilitare(militareId, nomeMilitare) {
    const checkbox = document.querySelector(`.militare-checkbox[value="${militareId}"]`);
    if (checkbox) {
        checkbox.dataset.forza = 'true';
        
        // Feedback visivo 1: Mostra badge verde "Forzatura attiva"
        const badge = document.getElementById(`badge-forzato-${militareId}`);
        if (badge) {
            badge.classList.remove('d-none');
        }
        
        // Feedback visivo 2: Cambia il pulsante in verde con check
        const btnForza = document.getElementById(`btn-forza-${militareId}`);
        if (btnForza) {
            btnForza.className = 'btn btn-sm btn-success';
            btnForza.innerHTML = '<i class="fas fa-check-circle"></i> Forzatura attivata';
            btnForza.disabled = true;
        }
        
        // Feedback visivo 3: Aggiungi bordo verde alla card
        const card = document.getElementById(`conflitto-${militareId}`);
        if (card) {
            card.style.borderLeft = '4px solid #28a745';
            card.style.background = '#d4edda';
            card.style.padding = '10px';
            card.style.borderRadius = '4px';
        }
        
        // CRITICO: Ri-valida per abilitare il pulsante Conferma
        const checkboxes = document.querySelectorAll('.militare-checkbox:checked');
        const militariDisponibili = Array.from(checkboxes).filter(cb => !cb.dataset.conflitto);
        const militariConflitti = Array.from(checkboxes).filter(cb => cb.dataset.conflitto);
        
        const tuttiConflittiRisolti = militariConflitti.every(cb => cb.dataset.forza === 'true');
        const haDisponibili = militariDisponibili.length > 0 || militariConflitti.some(cb => cb.dataset.forza === 'true');
        
        document.getElementById('btnConferma').disabled = !haDisponibili || !tuttiConflittiRisolti;
    }
}

// Conferma assegnazione (supporta selezione multipla con checkbox)
async function confermaAssegnazione() {
    const checkboxes = document.querySelectorAll('.militare-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showToast('⚠️ Seleziona almeno un militare', 'error');
        return;
    }

    if (checkboxes.length > getPostiDisponibili()) {
        showToast('Hai selezionato più militari dei posti disponibili', 'error');
        return;
    }

    showSpinner();

    let successCount = 0;
    let errorCount = 0;
    const errors = [];

    // Assegna ogni militare selezionato
    for (const checkbox of checkboxes) {
        const militareId = checkbox.value;
        const militareNome = checkbox.dataset.nome;
        const forzaPerQuestoMilitare = checkbox.dataset.forza === 'true';
        
        try {
            const response = await fetch('{{ route("servizi.turni.assegna") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    turno_id: turnoId,
                    servizio_id: currentServizioId,
                    militare_id: militareId,
                    data: currentData,
                    forza_sovrascrizione: forzaPerQuestoMilitare
                })
            });

            const result = await response.json();

            if (result.success) {
                successCount++;
            } else {
                errorCount++;
                errors.push(militareNome + ': ' + result.message);
            }

        } catch (error) {
            errorCount++;
            errors.push(militareNome + ': Errore di rete');
            console.error('Errore assegnazione:', error);
        }
    }

    hideSpinner();

    // Mostra risultato
    if (errorCount > 0) {
        // Mostra banner errori dettagliato nel modal
        const erroriDiv = document.getElementById('erroriAssegnazione');
        const listaErrori = document.getElementById('listaErrori');
        
        listaErrori.innerHTML = `
            <div class="mb-2">
                <strong>${errorCount} militare/i NON assegnato/i:</strong>
            </div>
            ${errors.map((err, idx) => {
                const parts = err.split(':');
                const nomeMilitare = parts[0];
                const motivoErrore = parts.slice(1).join(':').trim();
                return `
                    <div class="p-2 mb-2 bg-white border-start border-danger border-3" style="border-radius: 4px;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-times-circle text-danger me-2 mt-1"></i>
                            <div>
                                <strong>${nomeMilitare}</strong>
                                <div class="text-muted small">${motivoErrore}</div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('')}
        `;
        
        if (successCount > 0) {
            listaErrori.innerHTML = `
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle"></i> 
                    <strong>${successCount} militare/i assegnato/i con successo</strong>
                </div>
            ` + listaErrori.innerHTML;
        }
        
        erroriDiv.classList.remove('d-none');
        
        // Scroll al banner errori
        erroriDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Toast riepilogativo
        if (successCount > 0) {
            showToast(`✓ ${successCount} assegnati, ✗ ${errorCount} errori. Vedi dettagli sotto.`, 'warning');
        } else {
            showToast(`✗ Nessun militare assegnato. ${errorCount} errori. Vedi dettagli sotto.`, 'error');
        }
        
        // Disabilita conferma finché non risolve gli errori
        document.getElementById('btnConferma').disabled = true;
        
    } else if (successCount > 0) {
        // Tutto ok - mostra toast successo e ricarica
        // Rimosso toast: non necessario - ricarica pagina
        bootstrap.Modal.getInstance(document.getElementById('modalAssegnazione')).hide();
        setTimeout(() => location.reload(), 1500);
    }
}

// Rimuovi assegnazione con dialog personalizzato e dettagli chiari
async function rimuoviAssegnazione(assegnazioneId, nomeServizio, dataGiorno, nomeMilitare, event) {
    event.stopPropagation();

    // Crea modal personalizzato minimal
    const modalHtml = `
        <div id="customConfirmModal" class="custom-confirm-overlay">
            <div class="custom-confirm-modal">
                <div class="custom-confirm-header">
                    <h5>Conferma Rimozione</h5>
                </div>
                
                <div class="custom-confirm-body">
                    <div style="background: #F7FAFC; padding: 1rem; border-radius: 6px; border-left: 3px solid #0A2342;">
                        <div style="margin-bottom: 0.5rem; color: #4A5568;">
                            <strong style="color: #0A2342;">Militare:</strong> ${nomeMilitare}
                        </div>
                        <div style="margin-bottom: 0.5rem; color: #4A5568;">
                            <strong style="color: #0A2342;">Data:</strong> ${dataGiorno}
                        </div>
                        <div style="color: #4A5568;">
                            <strong style="color: #0A2342;">Servizio:</strong> ${nomeServizio}
                        </div>
                    </div>
                    <p style="color: #718096; font-size: 0.875rem; margin: 1rem 0 0; text-align: center;">
                        Il militare sarà rimosso dal servizio e dal CPT
                    </p>
                </div>
                
                <div class="custom-confirm-footer">
                    <button onclick="document.getElementById('customConfirmModal').remove()" class="custom-btn custom-btn-cancel">
                        Annulla
                    </button>
                    <button onclick="confermaRimozione(${assegnazioneId})" class="custom-btn custom-btn-confirm">
                        Rimuovi
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

// Funzione separata per confermare la rimozione
async function confermaRimozione(assegnazioneId) {
    document.getElementById('customConfirmModal').remove();
    showSpinner();

    try {
        const response = await fetch('{{ route("servizi.turni.rimuovi") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                assegnazione_id: assegnazioneId
            })
        });

        const result = await response.json();

        hideSpinner();

        if (result.success) {
            // Ricarica immediatamente senza toast
            location.reload();
        } else {
            // Mostra solo errori critici
            console.error('Errore rimozione:', result.message);
            location.reload(); // Ricarica comunque per sicurezza
        }

    } catch (error) {
        hideSpinner();
        console.error('Errore rimozione:', error);
        // Ricarica la pagina per sicurezza
        location.reload();
    }
}

// Sincronizzazione automatica: non serve più pulsante manuale
// La sincronizzazione avviene automaticamente ad ogni assegnazione tramite TurniService->sincronizzaConCPT()

// Salva comandante di compagnia (per export Excel)
async function salvaComandante() {
    const input = document.getElementById('comandanteCompagnia');
    if (!input) {
        return;
    }
    const nome = input.value.trim();
    if (!nome) {
        showToast('Inserisci il nome del comandante', 'error');
        return;
    }

    showSpinner();
    try {
        const response = await fetch('{{ route("servizi.turni.comandante.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ nome })
        });
        const result = await response.json();
        hideSpinner();

        if (response.ok && result.success) {
            showToast('Comandante aggiornato', 'success');
        } else {
            showToast(extractErrorMessage(result), 'error');
        }
    } catch (error) {
        hideSpinner();
        console.error('Errore aggiornamento comandante:', error);
        showToast('Errore di rete', 'error');
    }
}

// Gestione servizi (crea/aggiorna/rimuovi)
async function creaServizio() {
    const nome = document.getElementById('nuovoServizioNome')?.value.trim();
    const sigla = document.getElementById('nuovoServizioSigla')?.value.trim();
    const numPosti = parseInt(document.getElementById('nuovoServizioPosti')?.value, 10);
    const smontante = !!document.getElementById('nuovoServizioSmontante')?.checked;

    if (!nome || !sigla || !numPosti || numPosti < 1) {
        showToast('Compila nome, sigla CPT e posti validi', 'error');
        return;
    }

    showSpinner();
    try {
        const response = await fetch('{{ route("servizi.turni.servizi.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nome,
                sigla_cpt: sigla || null,
                num_posti: numPosti,
                smontante_cpt: smontante
            })
        });
        const result = await response.json();
        hideSpinner();

        if (response.ok && result.success) {
            showToast(result.message || 'Servizio creato', 'success');
            location.reload();
        } else {
            showToast(extractErrorMessage(result), 'error');
        }
    } catch (error) {
        hideSpinner();
        console.error('Errore creazione servizio:', error);
        showToast('Errore di rete', 'error');
    }
}

async function aggiornaServizio(servizioId) {
    const nome = document.getElementById(`servizio-nome-${servizioId}`)?.value.trim();
    const sigla = document.getElementById(`servizio-sigla-${servizioId}`)?.value.trim();
    const numPosti = parseInt(document.getElementById(`servizio-posti-${servizioId}`)?.value, 10);
    const smontante = !!document.getElementById(`servizio-smontante-${servizioId}`)?.checked;

    if (!nome || !sigla || !numPosti || numPosti < 1) {
        showToast('Nome, sigla CPT e posti devono essere validi', 'error');
        return;
    }

    showSpinner();
    try {
        const response = await fetch(`{{ url('/servizi/turni/servizi') }}/${servizioId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nome,
                sigla_cpt: sigla || null,
                num_posti: numPosti,
                smontante_cpt: smontante
            })
        });
        const result = await response.json();
        hideSpinner();

        if (response.ok && result.success) {
            showToast(result.message || 'Servizio aggiornato', 'success');
            location.reload();
        } else {
            showToast(extractErrorMessage(result), 'error');
        }
    } catch (error) {
        hideSpinner();
        console.error('Errore aggiornamento servizio:', error);
        showToast('Errore di rete', 'error');
    }
}

async function rimuoviServizio(servizioId, nomeServizio) {
    if (!confirm(`Confermi la rimozione del servizio "${nomeServizio}"?`)) {
        return;
    }

    showSpinner();
    try {
        const response = await fetch(`{{ url('/servizi/turni/servizi') }}/${servizioId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });
        const result = await response.json();
        hideSpinner();

        if (response.ok && result.success) {
            showToast(result.message || 'Servizio rimosso', 'success');
            location.reload();
        } else {
            showToast(extractErrorMessage(result), 'error');
        }
    } catch (error) {
        hideSpinner();
        console.error('Errore rimozione servizio:', error);
        showToast('Errore di rete', 'error');
    }
}

// Evita focus su modal nascosto (accessibilità)
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modalAssegnazione');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', () => {
            if (modal.contains(document.activeElement)) {
                document.activeElement.blur();
                document.body.focus();
            }
        });
    }
});
</script>

<!-- Floating Button Export Excel -->
<a href="{{ route('servizi.turni.export-excel', ['data' => $turno->data_inizio->format('Y-m-d')]) }}" 
   class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

@endsection
