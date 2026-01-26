@extends('layouts.app')

@section('title', 'Disponibilità Personale - SUGECO')

@section('content')
<script>
// Dati militari per il filtraggio lato client
window.militariData = @json($militariData);
window.giorniMese = @json(count($giorniMese));
</script>

<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Disponibilità Personale</h1>
    </div>

    <!-- Selettori Mese/Anno centrati -->
    <div class="d-flex justify-content-center mb-3">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap justify-content-center">
            <select name="mese" class="form-select form-select-sm" onchange="cambiaPeriodo()" style="width: 140px; border-radius: 6px !important;">
                @foreach($nomiMesi as $num => $nome)
                    <option value="{{ $num }}" {{ $mese == $num ? 'selected' : '' }}>{{ $nome }}</option>
                @endforeach
            </select>
            <select name="anno" class="form-select form-select-sm" onchange="cambiaPeriodo()" style="width: 100px; border-radius: 6px !important;">
                @for($a = 2025; $a <= 2030; $a++)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endfor
            </select>
        </form>
    </div>

    <!-- Filtri Compagnia, Plotone, Ufficio -->
    <div class="disponibilita-filters-inline mb-4">
        <div class="disponibilita-filter-item">
            <label for="compagniaSelect">Compagnia</label>
            <select id="compagniaSelect" class="form-select form-select-sm">
                <option value="">Tutte le compagnie</option>
                @foreach($compagnie as $compagnia)
                    <option value="{{ $compagnia->id }}">
                        {{ $compagnia->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="disponibilita-filter-item">
            <label for="plotoneSelect">Plotone</label>
            <select id="plotoneSelect" class="form-select form-select-sm" disabled>
                <option value="">Seleziona prima una compagnia</option>
                @foreach($plotoni as $plotone)
                    <option value="{{ $plotone->id }}" 
                            data-compagnia-id="{{ $plotone->compagnia_id }}">
                        {{ $plotone->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="disponibilita-filter-item">
            <label for="ufficioSelect">Ufficio</label>
            <select id="ufficioSelect" class="form-select form-select-sm">
                <option value="">Tutti gli uffici</option>
                @foreach($poli as $polo)
                    <option value="{{ $polo->id }}">
                        {{ $polo->nome }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Legenda badge -->
    <div class="d-flex justify-content-center gap-2 mb-3">
        <span class="badge" style="background-color: #d4edda; color: #155724;">Alto (&gt;70%)</span>
        <span class="badge" style="background-color: #fff3cd; color: #856404;">Medio (40-70%)</span>
        <span class="badge" style="background-color: #f8d7da; color: #721c24;">Basso (&lt;40%)</span>
    </div>

    <!-- Tabella Disponibilità -->
    <div class="sugeco-table-wrapper">
        <table class="sugeco-table">
            <thead>
                <tr>
                    <th>Ufficio</th>
                    <th>Tot.</th>
                    @foreach($giorniMese as $giorno)
                        @php
                            $isWeekend = $giorno['is_weekend'];
                            $isHoliday = $giorno['is_holiday'];
                            $isToday = $giorno['is_today'] ?? false;
                        @endphp
                        @php
                            // Mappa nomi giorni (abbreviati e completi) -> nome completo maiuscolo
                            $mappaGiorni = [
                                'Dom' => 'DOMENICA', 'Domenica' => 'DOMENICA',
                                'Lun' => 'LUNEDI', 'Lunedì' => 'LUNEDI',
                                'Mar' => 'MARTEDI', 'Martedì' => 'MARTEDI',
                                'Mer' => 'MERCOLEDI', 'Mercoledì' => 'MERCOLEDI',
                                'Gio' => 'GIOVEDI', 'Giovedì' => 'GIOVEDI',
                                'Ven' => 'VENERDI', 'Venerdì' => 'VENERDI',
                                'Sab' => 'SABATO', 'Sabato' => 'SABATO'
                            ];
                            $nomeGiornoCompleto = $mappaGiorni[$giorno['nome_giorno']] ?? strtoupper($giorno['nome_giorno']);
                        @endphp
                        <th class="{{ $isWeekend || $isHoliday ? 'weekend' : '' }} {{ $isToday ? 'today' : '' }}" 
                            style="padding: 4px 2px;">
                            <div>{{ $nomeGiornoCompleto }}</div>
                            <div class="date-badge">{{ $giorno['data']->format('d/m') }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="disponibilitaTableBody">
                @foreach($disponibilitaPerPolo as $poloData)
                    <tr class="polo-row" data-polo-id="{{ $poloData['polo']->id }}">
                        <td class="polo-nome"><strong>{{ $poloData['polo']->nome }}</strong></td>
                        <td class="polo-totale"><strong>{{ $poloData['totale_militari'] }}</strong></td>
                        @foreach($giorniMese as $giorno)
                            @php
                                $datiGiorno = $poloData['giorni'][$giorno['giorno']];
                                $percentuale = $datiGiorno['percentuale_liberi'];
                                
                                if ($percentuale >= 70) {
                                    $classeColore = 'scadenza-valido';
                                } elseif ($percentuale >= 40) {
                                    $classeColore = 'scadenza-in-scadenza';
                                } else {
                                    $classeColore = 'scadenza-scaduto';
                                }
                                
                                $isWeekendHoliday = $giorno['is_weekend'] || $giorno['is_holiday'];
                                if ($isWeekendHoliday) {
                                    $classeColore = 'scadenza-mancante';
                                }
                            @endphp
                            <td class="disponibilita-cell {{ $classeColore }}"
                                data-polo-id="{{ $poloData['polo']->id }}"
                                data-giorno="{{ $giorno['giorno'] }}"
                                data-data="{{ $anno }}-{{ str_pad($mese, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($giorno['giorno'], 2, '0', STR_PAD_LEFT) }}"
                                data-is-weekend="{{ $isWeekendHoliday ? '1' : '0' }}"
                                title="Liberi: {{ $datiGiorno['liberi'] }} / Impegnati: {{ $datiGiorno['impegnati'] }}"
                                style="cursor: pointer;">
                                <strong class="liberi-count">{{ $datiGiorno['liberi'] }}</strong>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dettaglio -->
<div class="modal fade" id="dettaglioGiornoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title mb-0">Dettaglio Disponibilità</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dettaglioGiornoContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.disponibilita-cell:hover {
    opacity: 0.8;
}

/* Date badge nell'header - stesso stile di CPT/Pianificazione */
.date-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
    display: inline-block;
    margin-top: 3px;
    font-family: 'Roboto', sans-serif;
    font-weight: 500;
}

/* Weekend header - stesso stile di CPT/Pianificazione */
.sugeco-table th.weekend {
    background-color: #dc3545 !important;
    border-top-color: #dc3545;
}

/* Today header */
.sugeco-table th.today {
    background-color: #0A2342 !important;
}

.sugeco-table th.today .date-badge {
    background: #ff8c00;
    color: white;
}

/* Filtri inline */
.disponibilita-filters-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    justify-content: center;
    align-items: flex-end;
    padding: 16px 24px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    max-width: 800px;
    margin: 0 auto;
}

.disponibilita-filter-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.disponibilita-filter-item label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
}

.disponibilita-filter-item .form-select {
    min-width: 200px;
    border-radius: 6px !important;
    white-space: nowrap;
    overflow: visible;
    text-overflow: clip;
}

.disponibilita-filter-item .form-select:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
    opacity: 0.7;
}

@media (max-width: 768px) {
    .disponibilita-filters-inline {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }

    .disponibilita-filter-item {
        width: 100%;
    }

    .disponibilita-filter-item .form-select {
        width: 100%;
        min-width: auto;
    }
}

/* Riepilogo Box */
.riepilogo-box {
    text-align: center;
    padding: 16px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.riepilogo-box .numero {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
}

.riepilogo-box .etichetta {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    margin-top: 4px;
}

.riepilogo-box.liberi { border-left: 3px solid #28a745; }
.riepilogo-box.impegnati { border-left: 3px solid #dc3545; }
.riepilogo-box.totale { border-left: 3px solid #6c757d; }

.riepilogo-box.liberi .numero { color: #28a745; }
.riepilogo-box.impegnati .numero { color: #dc3545; }
.riepilogo-box.totale .numero { color: #212529; }

/* Tabs semplici */
.nav-tabs-simple {
    border-bottom: 2px solid #e9ecef;
    gap: 0;
}

.nav-tabs-simple .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    padding: 10px 20px;
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
    background: none;
    border-radius: 0;
}

.nav-tabs-simple .nav-link:hover {
    color: #212529;
    border-bottom-color: #dee2e6;
}

.nav-tabs-simple .nav-link.active {
    color: #212529;
    border-bottom-color: #212529;
}

.empty-msg {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Lista militari - Design moderno */
.lista-militari {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 8px;
}

.militare-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: #f8f9fa;
    border-radius: 8px;
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.militare-item:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

.militare-libero {
    border-left-color: #28a745;
}

.militare-impegnato {
    border-left-color: #dc3545;
}

.militare-numero {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #0A2342;
    color: white;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

.militare-libero .militare-numero {
    background: #28a745;
}

.militare-impegnato .militare-numero {
    background: #dc3545;
}

.militare-nome {
    font-weight: 600;
    color: #212529;
    font-size: 0.9rem;
}

.militare-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
    flex: 1;
}

.militare-motivo {
    font-size: 0.8rem;
    color: #6c757d;
    font-style: italic;
}

/* Scrollbar */
.lista-scroll::-webkit-scrollbar {
    width: 5px;
}

.lista-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.lista-scroll::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('dettaglioGiornoModal');
    const modalContent = document.getElementById('dettaglioGiornoContent');
    const modal = new bootstrap.Modal(modalElement);
    
    // Inizializza filtri
    initFiltri();
    
    const loadingHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Caricamento...</p>
        </div>
    `;
    
    document.querySelectorAll('.disponibilita-cell').forEach(function(cell) {
        cell.addEventListener('click', function() {
            const poloId = this.dataset.poloId;
            const data = this.dataset.data;
            
            modalContent.innerHTML = loadingHTML;
            modal.show();
            
            fetch(`{{ url('disponibilita/militari-liberi') }}?data=${data}&polo_id=${poloId}`)
                .then(response => response.json())
                .then(responseData => {
                    if (responseData.success) {
                        const percentuale = responseData.totale > 0 ? Math.round((responseData.liberi / responseData.totale) * 100) : 0;
                        
                        let html = `
                            <!-- Riepilogo numerico -->
                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <div class="riepilogo-box liberi">
                                        <div class="numero">${responseData.liberi}</div>
                                        <div class="etichetta">Liberi</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="riepilogo-box impegnati">
                                        <div class="numero">${responseData.impegnati}</div>
                                        <div class="etichetta">Impegnati</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="riepilogo-box totale">
                                        <div class="numero">${responseData.totale}</div>
                                        <div class="etichetta">Totale</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barra disponibilità -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Disponibilità</small>
                                    <small class="fw-bold">${percentuale}%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-${percentuale >= 70 ? 'success' : percentuale >= 40 ? 'warning' : 'danger'}" 
                                         style="width: ${percentuale}%"></div>
                                </div>
                            </div>
                            
                            <!-- Tabs -->
                            <ul class="nav nav-tabs-simple mb-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-liberi">
                                        Liberi (${responseData.liberi})
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-impegnati">
                                        Impegnati (${responseData.impegnati})
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Liberi -->
                                <div class="tab-pane fade show active" id="tab-liberi">
                                    <div class="lista-scroll" style="max-height: 280px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_liberi && responseData.militari_liberi.length > 0) {
                            html += `<div class="lista-militari">`;
                            responseData.militari_liberi.forEach(function(m, index) {
                                html += `
                                    <div class="militare-item militare-libero">
                                        <span class="militare-numero">${index + 1}</span>
                                        <span class="militare-nome">${m.nome_completo}</span>
                                    </div>
                                `;
                            });
                            html += `</div>`;
                        } else {
                            html += `<div class="empty-msg"><i class="fas fa-check-circle text-success mb-2" style="font-size: 2rem;"></i><br>Nessun militare libero</div>`;
                        }
                        
                        html += `
                                    </div>
                                </div>
                                
                                <!-- Impegnati -->
                                <div class="tab-pane fade" id="tab-impegnati">
                                    <div class="lista-scroll" style="max-height: 280px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_impegnati && responseData.militari_impegnati.length > 0) {
                            html += `<div class="lista-militari">`;
                            responseData.militari_impegnati.forEach(function(m, index) {
                                html += `
                                    <div class="militare-item militare-impegnato">
                                        <span class="militare-numero">${index + 1}</span>
                                        <div class="militare-info">
                                            <span class="militare-nome">${m.nome_completo}</span>
                                            <span class="militare-motivo">${m.motivo || m.codice || m.fonte || '-'}</span>
                                        </div>
                                    </div>
                                `;
                            });
                            html += `</div>`;
                        } else {
                            html += `<div class="empty-msg"><i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i><br>Nessun militare impegnato</div>`;
                        }
                        
                        html += `
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        modalContent.innerHTML = html;
                    } else {
                        modalContent.innerHTML = `
                            <div class="alert alert-warning mb-0">
                                ${responseData.message || 'Nessun dato disponibile'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger mb-0">Errore nel caricamento. Riprova.</div>
                    `;
                });
        });
    });
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalContent.innerHTML = loadingHTML;
    });
});

/**
 * Inizializza i filtri e gestisce la logica di filtraggio lato client
 */
function initFiltri() {
    const compagniaSelect = document.getElementById('compagniaSelect');
    const plotoneSelect = document.getElementById('plotoneSelect');
    const ufficioSelect = document.getElementById('ufficioSelect');
    
    if (compagniaSelect) {
        compagniaSelect.addEventListener('change', function() {
            aggiornaOpzioniPlotone(this.value);
            applicaFiltriClientSide();
        });
    }
    
    if (plotoneSelect) {
        plotoneSelect.addEventListener('change', function() {
            applicaFiltriClientSide();
        });
    }
    
    if (ufficioSelect) {
        ufficioSelect.addEventListener('change', function() {
            applicaFiltriClientSide();
        });
    }
    
    // Applica i filtri iniziali
    applicaFiltriClientSide();
}

/**
 * Aggiorna le opzioni visibili del select plotone in base alla compagnia
 */
function aggiornaOpzioniPlotone(compagniaId) {
    const plotoneSelect = document.getElementById('plotoneSelect');
    if (!plotoneSelect) return;
    
    const options = plotoneSelect.querySelectorAll('option');
    const placeholderOption = plotoneSelect.querySelector('option[value=""]');
    
    if (!compagniaId) {
        // Nessuna compagnia selezionata: disabilita il select plotoni
        plotoneSelect.disabled = true;
        plotoneSelect.value = '';
        if (placeholderOption) {
            placeholderOption.textContent = 'Seleziona prima una compagnia';
        }
        // Nascondi tutte le opzioni tranne il placeholder
        options.forEach(option => {
            if (option.value) {
                option.style.display = 'none';
            }
        });
        return;
    }
    
    // Compagnia selezionata: abilita il select e filtra plotoni
    plotoneSelect.disabled = false;
    if (placeholderOption) {
        placeholderOption.textContent = 'Tutti i plotoni';
    }
    
    options.forEach(option => {
        if (!option.value) return;
        
        const optCompagniaId = option.dataset.compagniaId;
        option.style.display = (optCompagniaId === compagniaId) ? '' : 'none';
    });
    
    // Se il plotone selezionato non appartiene più alla compagnia, resetta
    if (plotoneSelect.value) {
        const selectedOption = plotoneSelect.querySelector(`option[value="${plotoneSelect.value}"]`);
        if (selectedOption && selectedOption.style.display === 'none') {
            plotoneSelect.value = '';
        }
    }
}

/**
 * Cambia il periodo (mese/anno) - questo richiede reload per nuovi dati
 */
function cambiaPeriodo() {
    const mese = document.querySelector('select[name="mese"]').value;
    const anno = document.querySelector('select[name="anno"]').value;
    
    const params = new URLSearchParams();
    params.append('mese', mese);
    params.append('anno', anno);
    
    window.location.href = '{{ route("disponibilita.index") }}?' + params.toString();
}

/**
 * Applica i filtri lato client senza ricaricare la pagina
 */
function applicaFiltriClientSide() {
    const compagniaId = document.getElementById('compagniaSelect').value;
    const plotoneId = document.getElementById('plotoneSelect').value;
    const ufficioId = document.getElementById('ufficioSelect').value;
    
    const militariData = window.militariData || [];
    const giorniMese = window.giorniMese || 31;
    
    // Filtra i militari in base ai filtri selezionati
    const militariFiltrati = militariData.filter(m => {
        if (compagniaId && m.compagnia_id != compagniaId) return false;
        if (plotoneId && m.plotone_id != plotoneId) return false;
        if (ufficioId && m.polo_id != ufficioId) return false;
        return true;
    });
    
    // Raggruppa per polo
    const militariPerPolo = {};
    militariFiltrati.forEach(m => {
        if (!militariPerPolo[m.polo_id]) {
            militariPerPolo[m.polo_id] = [];
        }
        militariPerPolo[m.polo_id].push(m);
    });
    
    // Aggiorna ogni riga della tabella
    document.querySelectorAll('.polo-row').forEach(row => {
        const poloId = row.dataset.poloId;
        const militariPolo = militariPerPolo[poloId] || [];
        const totaleMilitari = militariPolo.length;
        
        // Se filtro ufficio è attivo, nascondi righe non corrispondenti
        if (ufficioId && poloId !== ufficioId) {
            row.style.display = 'none';
            return;
        }
        
        // Mostra la riga se ha militari o se nessun filtro compagnia/plotone è attivo
        if (totaleMilitari === 0 && (compagniaId || plotoneId)) {
            row.style.display = 'none';
            return;
        }
        
        row.style.display = '';
        
        // Aggiorna il totale
        const totaleCell = row.querySelector('.polo-totale strong');
        if (totaleCell) {
            totaleCell.textContent = totaleMilitari;
        }
        
        // Aggiorna ogni cella giorno
        row.querySelectorAll('.disponibilita-cell').forEach(cell => {
            const giorno = parseInt(cell.dataset.giorno);
            const isWeekend = cell.dataset.isWeekend === '1';
            
            // Calcola liberi/impegnati per questo giorno
            let impegnati = 0;
            militariPolo.forEach(m => {
                if (m.impegni && m.impegni[giorno]) {
                    impegnati++;
                }
            });
            
            const liberi = totaleMilitari - impegnati;
            const percentuale = totaleMilitari > 0 ? Math.round((liberi / totaleMilitari) * 100) : 0;
            
            // Aggiorna il numero
            const countEl = cell.querySelector('.liberi-count');
            if (countEl) {
                countEl.textContent = liberi;
            }
            
            // Aggiorna il colore (solo se non è weekend/festivo)
            cell.classList.remove('scadenza-valido', 'scadenza-in-scadenza', 'scadenza-scaduto', 'scadenza-mancante');
            
            if (isWeekend) {
                cell.classList.add('scadenza-mancante');
            } else if (percentuale >= 70) {
                cell.classList.add('scadenza-valido');
            } else if (percentuale >= 40) {
                cell.classList.add('scadenza-in-scadenza');
            } else {
                cell.classList.add('scadenza-scaduto');
            }
            
            // Aggiorna tooltip
            cell.title = `Liberi: ${liberi} / Impegnati: ${impegnati}`;
        });
    });
    
    // Verifica se ci sono righe visibili
    const righeVisibili = document.querySelectorAll('.polo-row[style=""], .polo-row:not([style])');
    const noResultsMsg = document.getElementById('noResultsMessage');
    
    if (righeVisibili.length === 0) {
        if (!noResultsMsg) {
            const tbody = document.getElementById('disponibilitaTableBody');
            const msg = document.createElement('tr');
            msg.id = 'noResultsMessage';
            msg.innerHTML = '<td colspan="100" class="text-center py-4 text-muted"><i class="fas fa-search me-2"></i>Nessun risultato con i filtri selezionati</td>';
            tbody.appendChild(msg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}
</script>
@endsection
