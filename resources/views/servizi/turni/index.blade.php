@extends('layouts.app')

@section('title', 'Servizi')

<!-- Carica CSS personalizzato per toast e modals -->
<link rel="stylesheet" href="{{ asset('css/turni-custom.css') }}?v={{ time() }}">

@section('content')
<style>
/* ========================================
   TURNI - Stili specifici
   (Stili base tabelle in table-standard.css)
   ======================================== */

/* Tabella servizi - Layout colonne proporzionale */
.sugeco-table-wrapper .sugeco-table {
    table-layout: fixed !important;
    width: 100% !important;
    min-width: auto !important;
}

/* Prima colonna: Tipo di Servizio - 18% della larghezza */
.sugeco-table th:first-child,
.sugeco-table td:first-child {
    width: 18% !important;
    min-width: 140px !important;
    max-width: 200px !important;
}

/* Colonne giorni: distribuite equamente nel restante 82% */
.sugeco-table th:not(:first-child),
.sugeco-table td:not(:first-child) {
    width: calc(82% / 7) !important;
    min-width: 100px !important;
}

/* Celle servizio nome - testo può andare a capo se necessario */
.sugeco-table td.servizio-nome {
    white-space: normal !important;
    word-wrap: break-word !important;
    text-align: left !important;
    padding: 10px 12px !important;
    font-weight: 600;
    color: #0a2342;
}

/* Celle giorno per servizi - classe specifica per evitare conflitti con table-standard.css */
.sugeco-table td.turni-giorno-cell {
    width: auto !important;
    min-width: 100px !important;
    max-width: none !important;
    height: auto !important;
    min-height: 50px !important;
    padding: 8px !important;
    vertical-align: middle;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.sugeco-table td.turni-giorno-cell:hover {
    background-color: #e9ecef !important;
}

/* Celle weekend per turni */
.sugeco-table td.turni-giorno-cell.weekend {
    background-color: rgba(220, 53, 69, 0.08) !important;
}

.sugeco-table td.turni-giorno-cell.weekend:hover {
    background-color: rgba(220, 53, 69, 0.15) !important;
}

/* Date badge nell'header */
.date-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 0.75rem;
    display: inline-block;
    margin-top: 5px;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
}

/* Badge militare assegnato */
.militare-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 10px;
    background: #28a745 !important;
    color: white !important;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: default;
    margin: 2px;
}

.militare-badge .remove-btn {
    margin-left: 8px;
    color: rgba(255, 255, 255, 0.9);
    cursor: pointer;
    font-weight: bold;
    font-size: 1.1rem;
    line-height: 1;
    padding: 0 6px;
    border-radius: 3px;
    border: none;
    background: transparent;
}

.militare-badge .remove-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    color: white;
}

/* Cella vuota */
.posto-vuoto {
    color: #cbd5e1;
    font-size: 1.2rem;
}

/* Card contenitore */
.card {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

/* Header pagina */
.page-header {
    margin-bottom: 0.5rem;
}

.page-title {
    font-family: 'Oswald', sans-serif;
    color: #0a2342;
    font-size: 1.8rem;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
}

/* Navigazione settimana */
.btn-outline-primary {
    border-color: #0a2342;
    color: #0a2342;
    font-weight: 500;
    padding: 10px 20px;
}

.btn-outline-primary:hover {
    background: #0a2342;
    border-color: #0a2342;
    color: white;
}

/* Info settimana */
.week-info h5 {
    font-family: 'Oswald', sans-serif;
    color: #0a2342;
    font-weight: 600;
}

/* Loading spinner */
.spinner-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(10, 35, 66, 0.6);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    backdrop-filter: blur(3px);
}

.spinner-border-lg {
    width: 3.5rem;
    height: 3.5rem;
}

/* Input group comandante */
.input-group-text {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #0a2342;
}

.input-group .form-control {
    border-color: #e2e8f0;
}

.input-group .form-control:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.15);
}

/* Bottoni */
.btn-outline-secondary {
    border-color: #cbd5e1;
    color: #475569;
}

.btn-outline-secondary:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #1e293b;
}

.btn-outline-success {
    border-color: #22c55e;
    color: #16a34a;
}

.btn-outline-success:hover {
    background: #22c55e;
    border-color: #22c55e;
    color: white;
}

/* Modal styling */
.modal-content {
    border: none;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
    background: #0a2342;
    color: white;
    border-radius: 8px 8px 0 0;
    padding: 1rem 1.25rem;
}

.modal-header .modal-title {
    font-family: 'Oswald', sans-serif;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}

.modal-header .btn-close:hover {
    opacity: 1;
}

/* FAB Excel */
.fab {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.fab-excel {
    background: #217346;
}

.fab-excel:hover {
    background: #1a5c38;
    box-shadow: 0 4px 12px rgba(33, 115, 70, 0.35);
    color: white;
}

.fab i {
    font-size: 1.3rem;
}

/* Responsive */
@media (max-width: 768px) {
    .militare-badge {
        font-size: 0.75rem;
        padding: 6px 10px;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}
</style>

<div class="container-fluid">
    <!-- Header stile standard -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">Servizi</h1>
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

    <!-- Comandante + Gestione servizi -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-end flex-wrap gap-2">
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
            <div class="sugeco-table-wrapper">
                <table class="sugeco-table">
                    <thead>
                        <tr>
                            <th>TIPO DI SERVIZIO</th>
                            @foreach($giorniSettimana as $giorno)
                                <th class="{{ $giorno['is_weekend'] ? 'weekend' : '' }}">
                                    <div>{{ $giorno['giorno_settimana'] }}</div>
                                    <div class="date-badge">{{ $giorno['data']->format('d/m') }}</div>
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
                                    <td class="turni-giorno-cell {{ $giorno['is_weekend'] ? 'weekend' : '' }}" 
                                        onclick="apriModalAssegnazione({{ $servizio->id }}, '{{ $dataKey }}', '{{ addslashes($giorno['giorno_settimana']) }}', '{{ addslashes($servizio->nome) }}', {{ $servizio->num_posti_settimana ?? $servizio->num_posti }}, {{ $assegnazioni->count() }})">
                                        
                                        @if($assegnazioni->isNotEmpty())
                                            @foreach($assegnazioni as $assegnazione)
                                                <div class="militare-badge mb-1">
                                                    <span class="militare-nome">{{ $assegnazione->militare->grado->sigla ?? '' }} {{ strtoupper($assegnazione->militare->cognome) }}</span>
                                                    <button type="button" class="remove-btn" onclick="event.stopPropagation(); rimuoviAssegnazione({{ $assegnazione->id }})" title="Rimuovi">×</button>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="posto-vuoto">—</div>
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
                <h5 class="modal-title">Gestione servizi - Settimana {{ $turno->numero_settimana }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Impostazioni specifiche per questa settimana.</strong> Le modifiche non influenzeranno le settimane precedenti o successive.
                </div>
                <div class="table-responsive">
                    <table class="sugeco-table">
                        <thead>
                            <tr>
                                <th>Nome completo</th>
                                <th>Sigla CPT</th>
                                <th>Posti</th>
                                <th>Smontante</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($serviziConImpostazioni as $item)
                                @php
                                    $tipo = $item['tipo_servizio'];
                                    $numPosti = $item['num_posti'];
                                    $smontante = $item['smontante_cpt'];
                                @endphp
                                <tr data-sigla="{{ $tipo->codice }}">
                                    <td>
                                        <div class="fw-semibold">{{ $tipo->descrizione ?: $tipo->nome }}</div>
                                    </td>
                                    <td>
                                        <span class="badge"
                                              style="background-color: {{ $tipo->colore_badge }}; color: #fff;">
                                            {{ $tipo->codice }}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm" 
                                               id="servizio-posti-{{ $tipo->codice }}" 
                                               value="{{ $numPosti }}">
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input"
                                               id="servizio-smontante-{{ $tipo->codice }}"
                                               {{ $smontante ? 'checked' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <small class="text-muted d-block mt-2">
                    I servizi vengono gestiti dalla pagina Codici CPT. Qui puoi impostare posti e smontante per questa settimana.
                </small>
                <div class="text-end mt-3">
                    <button class="btn btn-primary" onclick="salvaImpostazioniServizi()">
                        Salva modifiche
                    </button>
                </div>
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
        // Tutto ok - aggiorna UI dinamicamente senza ricaricare
        showToast(`✓ ${successCount} militare/i assegnato/i con successo`, 'success');
        
        // Aggiorna la cella della tabella con i nuovi badge
        aggiornaUIAssegnazioni();
        
        // Chiudi il modal
        bootstrap.Modal.getInstance(document.getElementById('modalAssegnazione')).hide();
    }
}

// Aggiorna la UI della tabella dopo un'assegnazione senza ricaricare la pagina
async function aggiornaUIAssegnazioni() {
    try {
        // Trova la cella corrispondente al servizio e alla data corrente
        const cellaTarget = document.querySelector(`td.turni-giorno-cell[onclick*="apriModalAssegnazione(${currentServizioId}, '${currentData}'"]`);
        
        if (!cellaTarget) {
            console.warn('Cella target non trovata, ricarico la pagina');
            location.reload();
            return;
        }
        
        // Recupera le assegnazioni aggiornate via AJAX
        const response = await fetch(`{{ url('/servizi/turni/assegnazioni') }}?servizio_id=${currentServizioId}&data=${currentData}&turno_id=${turnoId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Ricostruisci il contenuto della cella
            let nuovoContenuto = '';
            
            if (data.assegnazioni && data.assegnazioni.length > 0) {
                data.assegnazioni.forEach(assegnazione => {
                    nuovoContenuto += `
                        <div class="militare-badge mb-1">
                            <span class="militare-nome">${assegnazione.grado_sigla} ${assegnazione.cognome.toUpperCase()}</span>
                            <button type="button" class="remove-btn" onclick="event.stopPropagation(); rimuoviAssegnazione(${assegnazione.id})" title="Rimuovi">×</button>
                        </div>
                    `;
                });
            } else {
                nuovoContenuto = '<div class="posto-vuoto">—</div>';
            }
            
            cellaTarget.innerHTML = nuovoContenuto;
            
            // Aggiorna l'onclick della cella con il nuovo numero di assegnati
            const nuovoNumAssegnati = data.assegnazioni ? data.assegnazioni.length : 0;
            cellaTarget.setAttribute('onclick', 
                `apriModalAssegnazione(${currentServizioId}, '${currentData}', '${currentGiornoSettimana.replace(/'/g, "\\'")}', '${currentNomeServizio.replace(/'/g, "\\'")}', ${currentMaxPosti}, ${nuovoNumAssegnati})`
            );
            
            // Aggiorna anche la variabile locale per le prossime aperture del modal
            currentNumAssegnati = nuovoNumAssegnati;
        } else {
            console.warn('Errore nel recupero assegnazioni:', data.message);
            location.reload();
        }
        
    } catch (error) {
        console.error('Errore aggiornamento UI:', error);
        // Fallback: ricarica la pagina in caso di errore
        location.reload();
    }
}

// Rimuovi assegnazione direttamente (senza ricaricare la pagina)
async function rimuoviAssegnazione(assegnazioneId) {
    if (!assegnazioneId) {
        console.error('ID assegnazione mancante');
        return;
    }
    
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
            // Rimuovi il badge del militare dalla UI senza ricaricare
            const badgeElement = document.querySelector(`button.remove-btn[onclick*="rimuoviAssegnazione(${assegnazioneId})"]`);
            if (badgeElement) {
                const badgeContainer = badgeElement.closest('.militare-badge');
                const cellaParent = badgeElement.closest('td.turni-giorno-cell');
                
                if (badgeContainer) {
                    badgeContainer.remove();
                }
                
                // Se la cella è ora vuota, aggiungi il placeholder
                if (cellaParent && cellaParent.querySelectorAll('.militare-badge').length === 0) {
                    cellaParent.innerHTML = '<div class="posto-vuoto">—</div>';
                }
                
                // Aggiorna l'onclick della cella con il nuovo numero di assegnati
                if (cellaParent) {
                    const onclickAttr = cellaParent.getAttribute('onclick');
                    if (onclickAttr) {
                        // Estrai i parametri dall'onclick esistente
                        const match = onclickAttr.match(/apriModalAssegnazione\((\d+),\s*'([^']+)',\s*'([^']*)',\s*'([^']*)',\s*(\d+),\s*(\d+)\)/);
                        if (match) {
                            const [, servizioId, data, giorno, nome, maxPosti, numAssegnati] = match;
                            const nuovoNumAssegnati = Math.max(0, parseInt(numAssegnati) - 1);
                            cellaParent.setAttribute('onclick', 
                                `apriModalAssegnazione(${servizioId}, '${data}', '${giorno}', '${nome}', ${maxPosti}, ${nuovoNumAssegnati})`
                            );
                        }
                    }
                }
                
                showToast('Militare rimosso dal servizio', 'success');
            } else {
                // Fallback: ricarica se non trova l'elemento
                location.reload();
            }
        } else {
            showToast(result.message || 'Errore durante la rimozione', 'error');
            console.error('Errore rimozione:', result.message);
        }

    } catch (error) {
        hideSpinner();
        console.error('Errore rimozione:', error);
        showToast('Errore di rete durante la rimozione', 'error');
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

// Gestione servizi (solo impostazioni)
async function salvaImpostazioniServizi() {
    const rows = document.querySelectorAll('#modalGestioneServizi tbody tr[data-sigla]');
    const servizi = [];

    for (const row of rows) {
        const sigla = row.dataset.sigla;
        const postiEl = document.getElementById(`servizio-posti-${sigla}`);
        const smontanteEl = document.getElementById(`servizio-smontante-${sigla}`);
        if (!postiEl || !smontanteEl) {
            showToast('Impossibile leggere i dati di un servizio', 'error');
            return;
        }
        const rawValue = postiEl.value ?? '';
        const numPosti = rawValue === '' ? 0 : parseInt(rawValue, 10);
        const smontante = !!smontanteEl.checked;

        if (Number.isNaN(numPosti) || numPosti < 0) {
            showToast('Il numero di posti deve essere 0 o superiore', 'error');
            return;
        }

        servizi.push({
            sigla_cpt: sigla,
            num_posti: numPosti,
            smontante_cpt: smontante
        });
    }

    showSpinner();
    try {
        const response = await fetch(`{{ route('servizi.turni.servizi.update-settings-batch') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ turno_id: turnoId, servizi })
        });
        const result = await response.json();
        hideSpinner();

        if (response.ok && result.success) {
            showToast(result.message || 'Impostazioni aggiornate', 'success');
            location.reload();
        } else {
            console.error('Errore salvataggio servizi:', result);
            showToast(extractErrorMessage(result), 'error');
        }
    } catch (error) {
        hideSpinner();
        console.error('Errore aggiornamento servizi:', error);
        showToast('Errore di rete', 'error');
    }
}

// Inizializzazione al caricamento della pagina
document.addEventListener('DOMContentLoaded', () => {
    // Evita focus su modal nascosto (accessibilità)
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
   class="fab fab-excel" title="Esporta in Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

@endsection
