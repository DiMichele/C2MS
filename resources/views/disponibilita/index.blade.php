@extends('layouts.app')

@section('title', 'Disponibilità Personale - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="page-title">Disponibilità Personale</h1>
        <p class="text-muted">Panoramica del personale libero per ufficio e giornata</p>
    </div>

    <!-- Selettori -->
    <div class="d-flex justify-content-center mb-4">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px; border-radius: 6px !important;">
                @foreach($nomiMesi as $num => $nome)
                    <option value="{{ $num }}" {{ $mese == $num ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
            <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px; border-radius: 6px !important;">
                @for($a = 2025; $a <= 2030; $a++)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endfor
            </select>
            
            <span class="mx-2">|</span>
            
            <select name="polo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 200px; border-radius: 6px !important;">
                <option value="">Tutti gli Uffici</option>
                @foreach($poli as $polo)
                    <option value="{{ $polo->id }}" {{ $poloId == $polo->id ? 'selected' : '' }}>
                        {{ $polo->nome }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Legenda -->
    <div class="d-flex justify-content-center mb-3">
        <div class="d-flex gap-3 align-items-center">
            <span class="badge" style="background-color: #28a745;">Alto (>70%)</span>
            <span class="badge" style="background-color: #ffc107; color: #000;">Medio (40-70%)</span>
            <span class="badge" style="background-color: #dc3545;">Basso (<40%)</span>
        </div>
    </div>

    <!-- Tabella Disponibilità -->
    <div class="table-container" style="overflow-x: auto;">
        <table class="table table-sm table-bordered mb-0" id="disponibilitaTable">
            <thead style="background-color: #0a2342; color: white;">
                <tr>
                    <th style="min-width: 200px; position: sticky; left: 0; background-color: #0a2342; z-index: 10;">
                        Polo/Ufficio
                    </th>
                    <th class="text-center" style="min-width: 60px;">Tot.</th>
                    @foreach($giorniMese as $giorno)
                        <th class="text-center {{ $giorno['is_weekend'] || $giorno['is_holiday'] ? 'bg-danger' : '' }}" 
                            style="min-width: 50px; {{ $giorno['is_today'] ? 'background-color: #ffc107 !important; color: #000;' : '' }}">
                            <div style="font-weight: 700;">{{ $giorno['giorno'] }}</div>
                            <small>{{ $giorno['nome_giorno'] }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($disponibilitaPerPolo as $poloData)
                    @if($poloData['totale_militari'] > 0)
                    <tr>
                        <td style="position: sticky; left: 0; background-color: #fff; z-index: 5; font-weight: 600;">
                            {{ $poloData['polo']->nome }}
                        </td>
                        <td class="text-center" style="background-color: rgba(10, 35, 66, 0.1);">
                            <strong>{{ $poloData['totale_militari'] }}</strong>
                        </td>
                        @foreach($giorniMese as $giorno)
                            @php
                                $datiGiorno = $poloData['giorni'][$giorno['giorno']];
                                $percentuale = $datiGiorno['percentuale_liberi'];
                                
                                // Determina il colore in base alla percentuale di liberi
                                if ($percentuale >= 70) {
                                    $bgColor = 'rgba(40, 167, 69, 0.3)';
                                    $textColor = '#155724';
                                } elseif ($percentuale >= 40) {
                                    $bgColor = 'rgba(255, 193, 7, 0.3)';
                                    $textColor = '#856404';
                                } else {
                                    $bgColor = 'rgba(220, 53, 69, 0.3)';
                                    $textColor = '#721c24';
                                }
                                
                                // Weekend/festivi più chiari
                                if ($giorno['is_weekend'] || $giorno['is_holiday']) {
                                    $bgColor = 'rgba(220, 53, 69, 0.08)';
                                }
                            @endphp
                            <td class="text-center disponibilita-cell" 
                                style="background-color: {{ $bgColor }}; cursor: pointer;"
                                data-polo-id="{{ $poloData['polo']->id }}"
                                data-data="{{ $anno }}-{{ str_pad($mese, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($giorno['giorno'], 2, '0', STR_PAD_LEFT) }}"
                                data-liberi="{{ $datiGiorno['liberi'] }}"
                                data-impegnati="{{ $datiGiorno['impegnati'] }}"
                                data-totale="{{ $datiGiorno['totale'] }}"
                                data-bs-toggle="tooltip"
                                title="Liberi: {{ $datiGiorno['liberi'] }} / Impegnati: {{ $datiGiorno['impegnati'] }}">
                                <strong style="color: {{ $textColor }};">{{ $datiGiorno['liberi'] }}</strong>
                            </td>
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dettaglio Giorno -->
<div class="modal fade" id="dettaglioGiornoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>Dettaglio Disponibilità
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dettaglioGiornoContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    margin: 0;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    max-height: calc(100vh - 350px);
    overflow: auto;
}

.disponibilita-cell:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 5;
    position: relative;
}

.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.05) !important;
}

.militare-libero-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.militare-libero-item:last-child {
    border-bottom: none;
}

.militare-libero-item:hover {
    background-color: rgba(10, 35, 66, 0.05);
}

/* ===== MODAL DISPONIBILITA - DESIGN PROFESSIONALE ===== */

/* Statistiche Cards */
.disponibilita-stats .stat-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
    border-radius: 12px;
    padding: 16px 12px;
    text-align: center;
    border: 1px solid #e9ecef;
    transition: transform 0.2s, box-shadow 0.2s;
}

.disponibilita-stats .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.stat-card.stat-liberi { border-left: 4px solid #28a745; }
.stat-card.stat-impegnati { border-left: 4px solid #dc3545; }
.stat-card.stat-totale { border-left: 4px solid #0a2342; }

.stat-card .stat-icon {
    font-size: 1.2rem;
    margin-bottom: 6px;
    opacity: 0.7;
}

.stat-liberi .stat-icon { color: #28a745; }
.stat-impegnati .stat-icon { color: #dc3545; }
.stat-totale .stat-icon { color: #0a2342; }

.stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1;
    color: #0a2342;
}

.stat-card .stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* Tabs Pills */
.disponibilita-tabs .nav-link {
    border-radius: 8px;
    padding: 10px 16px;
    font-weight: 500;
    color: #495057;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    transition: all 0.2s;
}

.disponibilita-tabs .nav-link:hover {
    background: #e9ecef;
}

.disponibilita-tabs .nav-link.active {
    background: linear-gradient(135deg, #0a2342 0%, #1a3a5a 100%);
    color: white;
    border-color: transparent;
}

.disponibilita-tabs .nav-item {
    margin: 0 4px;
}

/* Militari Cards */
.militari-list {
    padding: 4px;
}

.militare-card {
    display: flex;
    align-items: center;
    padding: 12px 14px;
    margin-bottom: 8px;
    background: #fff;
    border-radius: 10px;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
    animation: fadeInUp 0.3s ease forwards;
    opacity: 0;
}

.militare-card:hover {
    border-color: #0a2342;
    box-shadow: 0 2px 8px rgba(10, 35, 66, 0.1);
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.militare-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 14px;
    font-size: 1rem;
}

.militare-avatar.libero {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #28a745;
}

.militare-avatar.impegnato {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #dc3545;
}

.militare-info {
    flex: 1;
}

.militare-nome {
    font-weight: 600;
    color: #0a2342;
    font-size: 0.95rem;
}

.militare-dettagli {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.badge-ufficio {
    font-size: 0.7rem;
    padding: 3px 8px;
    background: #e9ecef;
    color: #495057;
    border-radius: 4px;
    font-weight: 500;
}

.badge-servizio {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 4px;
    font-weight: 600;
}

.badge-servizio.primary {
    background: #cce5ff;
    color: #004085;
}

.badge-servizio.info {
    background: #d1ecf1;
    color: #0c5460;
}

.servizio-desc {
    font-size: 0.75rem;
    color: #6c757d;
}

.militare-status {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.militare-status.libero {
    background: #d4edda;
    color: #28a745;
}

.militare-ufficio {
    margin-left: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 12px;
}

.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

/* Progress bar */
.disponibilita-progress .progress {
    background: #e9ecef;
}

/* Scrollbar stilizzata per lista */
.militari-list::-webkit-scrollbar {
    width: 6px;
}

.militari-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.militari-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.militari-list::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Riferimento al modal (singleton)
    const modalElement = document.getElementById('dettaglioGiornoModal');
    const modalContent = document.getElementById('dettaglioGiornoContent');
    const modal = new bootstrap.Modal(modalElement);
    
    // Contenuto loading da mostrare subito
    const loadingHTML = `
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2 text-muted">Caricamento dettagli...</p>
        </div>
    `;
    
    // Click su cella per vedere dettaglio
    document.querySelectorAll('.disponibilita-cell').forEach(function(cell) {
        cell.addEventListener('click', function() {
            const poloId = this.dataset.poloId;
            const data = this.dataset.data;
            const liberi = this.dataset.liberi;
            const impegnati = this.dataset.impegnati;
            const totale = this.dataset.totale;
            
            // PRIMA resetta il contenuto con lo spinner
            modalContent.innerHTML = loadingHTML;
            
            // POI mostra il modal (ora con lo spinner già visibile)
            modal.show();
            
            // Carica dettagli via AJAX
            fetch(`{{ url('disponibilita/militari-liberi') }}?data=${data}&polo_id=${poloId}`)
                .then(response => response.json())
                .then(responseData => {
                    if (responseData.success) {
                        // Calcola percentuale
                        const percentuale = responseData.totale > 0 ? Math.round((responseData.liberi / responseData.totale) * 100) : 0;
                        const percentualeClass = percentuale >= 70 ? 'success' : (percentuale >= 40 ? 'warning' : 'danger');
                        
                        let html = `
                            <!-- Header con statistiche -->
                            <div class="disponibilita-stats mb-4">
                                <div class="row g-3">
                                    <div class="col-4">
                                        <div class="stat-card stat-liberi">
                                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                                            <div class="stat-value">${responseData.liberi}</div>
                                            <div class="stat-label">Liberi</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-card stat-impegnati">
                                            <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                                            <div class="stat-value">${responseData.impegnati}</div>
                                            <div class="stat-label">Impegnati</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="stat-card stat-totale">
                                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                                            <div class="stat-value">${responseData.totale}</div>
                                            <div class="stat-label">Totale</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Barra progresso disponibilità -->
                                <div class="disponibilita-progress mt-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Disponibilità</small>
                                        <small class="fw-bold text-${percentualeClass}">${percentuale}%</small>
                                    </div>
                                    <div class="progress" style="height: 8px; border-radius: 4px;">
                                        <div class="progress-bar bg-${percentualeClass}" role="progressbar" style="width: ${percentuale}%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- TABS -->
                            <ul class="nav nav-pills nav-fill mb-3 disponibilita-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#liberi-content" type="button">
                                        <i class="fas fa-user-check me-2"></i>Liberi
                                        <span class="badge bg-white text-success ms-2">${responseData.liberi}</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#impegnati-content" type="button">
                                        <i class="fas fa-briefcase me-2"></i>Impegnati
                                        <span class="badge bg-white text-danger ms-2">${responseData.impegnati}</span>
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- TAB LIBERI -->
                                <div class="tab-pane fade show active" id="liberi-content" role="tabpanel">
                                    <div class="militari-list" style="max-height: 320px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_liberi && responseData.militari_liberi.length > 0) {
                            responseData.militari_liberi.forEach(function(m, index) {
                                html += `
                                    <div class="militare-card militare-libero" style="animation-delay: ${index * 0.03}s">
                                        <div class="militare-avatar libero">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="militare-info">
                                            <div class="militare-nome">${m.nome_completo}</div>
                                            <div class="militare-dettagli">
                                                <span class="badge-ufficio">${m.polo || 'N/A'}</span>
                                            </div>
                                        </div>
                                        <div class="militare-status libero">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            html += `
                                <div class="empty-state">
                                    <i class="fas fa-user-clock"></i>
                                    <p>Nessun militare libero in questo giorno</p>
                                </div>
                            `;
                        }
                        
                        html += `
                                    </div>
                                </div>
                                
                                <!-- TAB IMPEGNATI -->
                                <div class="tab-pane fade" id="impegnati-content" role="tabpanel">
                                    <div class="militari-list" style="max-height: 320px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_impegnati && responseData.militari_impegnati.length > 0) {
                            responseData.militari_impegnati.forEach(function(m, index) {
                                let iconClass = m.fonte === 'CPT' ? 'fa-calendar-day' : 'fa-clock';
                                let colorClass = m.fonte === 'CPT' ? 'primary' : 'info';
                                html += `
                                    <div class="militare-card militare-impegnato" style="animation-delay: ${index * 0.03}s">
                                        <div class="militare-avatar impegnato">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="militare-info">
                                            <div class="militare-nome">${m.nome_completo}</div>
                                            <div class="militare-dettagli">
                                                <span class="badge-servizio ${colorClass}">
                                                    <i class="fas ${iconClass} me-1"></i>${m.codice || m.fonte}
                                                </span>
                                                <span class="servizio-desc">${m.motivo}</span>
                                            </div>
                                        </div>
                                        <div class="militare-ufficio">
                                            <span class="badge-ufficio">${m.polo || 'N/A'}</span>
                                        </div>
                                    </div>
                                `;
                            });
                        } else {
                            html += `
                                <div class="empty-state">
                                    <i class="fas fa-mug-hot"></i>
                                    <p>Nessun militare impegnato in questo giorno</p>
                                </div>
                            `;
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
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                ${responseData.message || 'Nessun dato disponibile'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            Errore nel caricamento dei dati. Riprova.
                        </div>
                    `;
                });
        });
    });
    
    // Reset contenuto quando il modal viene chiuso (per sicurezza)
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalContent.innerHTML = loadingHTML;
    });
});
</script>
@endsection

