@extends('layouts.app')

@section('title', 'Approntamenti - SUGECO')

@section('content')
@php
    use App\Models\ScadenzaApprontamento;
    use App\Models\PrenotazioneApprontamento;
    use App\Http\Controllers\ApprontamentiController;
    
    $colonneLabels = ScadenzaApprontamento::getLabels();
    
    // Escludi la colonna teatro_operativo dalla visualizzazione
    $colonneBase = array_filter($colonne ?? [], function($key) {
        return $key !== 'teatro_operativo';
    }, ARRAY_FILTER_USE_KEY);
    
    // Filtra le colonne in base alla configurazione del teatro
    $colonneFiltrate = $colonneBase;
    if (isset($teatroSelezionato) && $teatroSelezionato) {
        $colonneFiltrate = $teatroSelezionato->getColonneVisibili($colonneBase);
    }
@endphp

<style>
/* === Page Header === */
.page-title {
    font-size: 2.2rem;
    font-weight: 700;
    color: #0a2342;
    letter-spacing: 2px;
    margin-bottom: 0.5rem;
    position: relative;
    display: inline-block;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, #d4af37, #f4d03f);
    border-radius: 2px;
}

/* === FIX Filtri Toggle - Override CSS base === */
.approntamenti-filters {
    display: none !important;
    opacity: 0;
    max-height: 0;
    overflow: hidden;
    transition: opacity 0.3s ease, max-height 0.3s ease;
}

.approntamenti-filters.visible {
    display: block !important;
    opacity: 1;
    max-height: 2000px;
    overflow: visible;
}

/* === Dashboard Cards === */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid #e2e8f0;
    position: relative;
}

.dashboard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #d4af37, #f4d03f);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(10, 35, 66, 0.18);
    border-color: #0a2342;
}

.dashboard-card:hover::before {
    opacity: 1;
}

.dashboard-card-header {
    background: linear-gradient(135deg, #0a2342 0%, #1a3a5c 100%);
    color: white;
    padding: 16px 20px;
}

.dashboard-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dashboard-card-periodo {
    font-size: 0.75rem;
    opacity: 0.8;
    margin-top: 4px;
}

.dashboard-card-body { padding: 20px; }

.dashboard-progress-container { margin-bottom: 16px; }

.dashboard-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 0.85rem;
}

.dashboard-progress-label { color: #64748b; }
.dashboard-progress-value { font-weight: 700; color: #0a2342; }

.dashboard-progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.dashboard-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.dashboard-progress-fill.success { background: linear-gradient(90deg, #28a745, #20c997); }
.dashboard-progress-fill.warning { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.dashboard-progress-fill.danger { background: linear-gradient(90deg, #dc3545, #e74c3c); }

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
}

.dashboard-stat { text-align: center; }

.dashboard-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.dashboard-stat-value.success { color: #28a745; }
.dashboard-stat-value.warning { color: #ffc107; }
.dashboard-stat-value.danger { color: #dc3545; }

.dashboard-stat-label {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

.dashboard-card-footer {
    padding: 12px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.dashboard-card-footer .btn { width: 100%; }

/* === Sottotitolo Teatro === definito in basso ===*/

/* === Link militare gold effect === definito in basso nella sezione tabella ===*/

/* === Modal === */
.scadenza-modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    z-index: 9999;
    min-width: 400px;
}

.scadenza-modal.show {
    display: block;
    animation: fadeIn 0.2s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -45%); }
    to { opacity: 1; transform: translate(-50%, -50%); }
}

.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9998;
}

.modal-overlay.show { display: block; }

.modal-header-custom {
    border-bottom: 2px solid #0a2342;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-header-custom h5 {
    color: #0a2342;
    font-weight: 600;
    margin: 0;
    font-size: 1.2rem;
}

.modal-militare-info {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 20px;
    font-weight: 600;
    color: #0a2342;
    font-size: 1.05rem;
}

.modal-body-custom { margin-bottom: 20px; }
.modal-body-custom .form-group { margin-bottom: 15px; }

.modal-body-custom label {
    font-weight: 600;
    color: #0a2342;
    margin-bottom: 8px;
    display: block;
}

.modal-body-custom input[type="date"] {
    width: 100%;
    padding: 10px;
    border: 2px solid #dee2e6;
    border-radius: 4px;
    font-size: 1rem;
}

.modal-body-custom input[type="date"]:focus {
    outline: none;
    border-color: #0a2342;
}

.modal-footer-custom {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-save {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
}

.btn-save:hover { background: #218838; }

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel:hover { background: #5a6268; }

/* === Empty State === */
.empty-state {
    text-align: center;
    padding: 60px 30px;
    color: #64748b;
}

.empty-state-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.empty-state-text {
    font-size: 0.95rem;
    max-width: 400px;
    margin: 0 auto;
}

/* === Statistiche Cattedre === */
.stats-cattedre-section {
    margin-bottom: 20px;
}

.stats-cattedre-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.stats-cattedre-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #0a2342;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    cursor: pointer;
}

.stats-cattedre-title i.fa-chart-bar { color: #d4af37; }
.stats-cattedre-title i.fa-chevron-down {
    transition: transform 0.3s;
    font-size: 0.7rem;
    color: #6c757d;
}

.stats-cattedre-title.collapsed i.fa-chevron-down {
    transform: rotate(-90deg);
}

.stats-cattedre-actions {
    display: flex;
    gap: 8px;
}

.stats-cattedre-container {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    overflow: hidden;
    transition: max-height 0.4s ease, opacity 0.4s ease, padding 0.4s ease;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.stats-cattedre-container.hidden {
    max-height: 0;
    opacity: 0;
    padding: 0 16px;
    border: none;
}

.stats-cattedre-scroll {
    overflow-x: auto;
    padding-bottom: 8px;
    scrollbar-width: thin;
    scrollbar-color: rgba(10, 35, 66, 0.3) rgba(0, 0, 0, 0.05);
}

.stats-cattedre-scroll::-webkit-scrollbar {
    height: 6px;
}

.stats-cattedre-scroll::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 3px;
}

.stats-cattedre-scroll::-webkit-scrollbar-thumb {
    background: rgba(10, 35, 66, 0.3);
    border-radius: 3px;
}

.stats-cattedre-grid {
    display: flex;
    gap: 12px;
    min-width: max-content;
}

.stat-cattedra-card {
    background: white;
    border-radius: 10px;
    min-width: 140px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    overflow: hidden;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.stat-cattedra-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10,35,66,0.12);
    border-color: #0a2342;
}

.stat-cattedra-header {
    background: #0a2342;
    color: white;
    padding: 8px 12px;
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    text-align: center;
    letter-spacing: 0.3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-cattedra-body {
    padding: 12px;
}

.stat-cattedra-values {
    display: flex;
    justify-content: center;
    gap: 16px;
}

.stat-cattedra-value {
    text-align: center;
}

.stat-cattedra-number {
    font-size: 1.4rem;
    font-weight: 700;
    line-height: 1;
}

.stat-cattedra-number.fatto { color: #28a745; }
.stat-cattedra-number.mancante { color: #dc3545; }

.stat-cattedra-label {
    font-size: 0.6rem;
    color: #94a3b8;
    text-transform: uppercase;
    margin-top: 2px;
}

.stat-cattedra-progress {
    height: 4px;
    background: #e2e8f0;
    border-radius: 2px;
    margin-top: 10px;
    overflow: hidden;
}

.stat-cattedra-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 2px;
    transition: width 0.5s ease;
}

/* === No Results === */
.no-results-row {
    display: none;
}

.no-results-row.visible {
    display: table-row;
}

.no-results-cell {
    text-align: center;
    padding: 40px 20px !important;
    background: #f8f9fa;
}

/* === FAB Export Button Floating === */
.fab-export {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: white;
    text-decoration: none;
    box-shadow: 0 6px 20px rgba(33, 115, 70, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
    background: linear-gradient(135deg, #217346 0%, #1e6b3e 100%);
    border: none;
}

.fab-export:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 10px 30px rgba(33, 115, 70, 0.5);
    color: white;
    text-decoration: none;
}

.fab-export:active {
    transform: translateY(-2px) scale(1.02);
}

.fab-export .fab-tooltip {
    position: absolute;
    right: 70px;
    background: #0a2342;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 0.85rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    font-weight: 500;
}

.fab-export:hover .fab-tooltip {
    opacity: 1;
    visibility: visible;
}

/* === Select Teatro Professionale === */
#selectTeatro {
    border: 2px solid #e2e8f0;
    padding: 14px 20px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

#selectTeatro:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.1);
    outline: none;
}

/* === Subtitle Teatro Elegante === */
.subtitle-teatro {
    font-size: 1.1rem;
    color: #64748b;
    font-weight: 500;
    margin-top: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.subtitle-teatro i {
    color: #d4af37;
}

/* === Stili tabella e scadenze: vedi table-standard.css === */

/* === Barra di Ricerca Professionale === */
.search-container {
    position: relative;
}

.search-container .form-control {
    border: 2px solid #e2e8f0;
    padding: 12px 16px 12px 45px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.search-container .form-control:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.1);
    outline: none;
}

.search-container .form-control::placeholder {
    color: #94a3b8;
}

.search-container .search-icon {
    font-size: 1rem;
    color: #94a3b8;
    transition: color 0.3s ease;
}

.search-container .form-control:focus + .search-icon,
.search-container:focus-within .search-icon {
    color: #0a2342;
}

/* === Export Bar Professionale === */
.export-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 16px 0;
    margin-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}

.btn-export-excel {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #217346 0%, #1e6b3e 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(33, 115, 70, 0.25);
    transition: all 0.3s ease;
}

.btn-export-excel:hover {
    background: linear-gradient(135deg, #1e6b3e 0%, #185a33 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(33, 115, 70, 0.35);
}

.btn-export-excel i {
    font-size: 1.1rem;
}
</style>

@if(!isset($teatroSelezionato) || !$teatroSelezionato)
    <!-- ========== DASHBOARD VIEW ========== -->
    
    <div class="text-center mb-4">
        <h1 class="page-title">APPRONTAMENTI</h1>
        <p class="subtitle-teatro">Seleziona un Teatro Operativo per visualizzare i dettagli</p>
    </div>

    <div class="d-flex justify-content-center mb-4">
        <div style="width: 400px;">
            <select id="selectTeatro" class="form-select form-select-lg" onchange="selezionaTeatro(this.value)" style="border-radius: 6px !important;">
                <option value="">Seleziona un Teatro Operativo</option>
                @if(isset($teatriOperativi) && $teatriOperativi->count() > 0)
                    @foreach($teatriOperativi as $teatro)
                    <option value="{{ $teatro->id }}">
                        {{ $teatro->nome }} ({{ $teatro->getNumeroMilitari() }} militari)
                    </option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>

    @if(isset($teatriOperativi) && $teatriOperativi->count() > 0)
        <div class="dashboard-grid">
            @foreach($teatriOperativi as $teatro)
            @php
                $totMilitari = $teatro->getNumeroMilitari();
                $militariTeatro = $teatro->militari;
                $completati = 0;
                $inCorso = 0;
                $totCattedre = count($colonneFiltrate);
                
                foreach($militariTeatro as $m) {
                    $scadenza = $m->scadenzaApprontamento;
                    $cattedreFatte = 0;
                    foreach($colonneFiltrate as $campo => $config) {
                        $valore = $scadenza ? $scadenza->$campo : null;
                        if($valore && $valore !== 'NR') {
                            $cattedreFatte++;
                        }
                    }
                    if($totCattedre > 0 && $cattedreFatte >= $totCattedre) {
                        $completati++;
                    } elseif($cattedreFatte > 0) {
                        $inCorso++;
                    }
                }
                $daIniziare = $totMilitari - $completati - $inCorso;
                $percentuale = $totMilitari > 0 ? round(($completati / $totMilitari) * 100) : 0;
                
                $progressClass = 'danger';
                if($percentuale >= 80) $progressClass = 'success';
                elseif($percentuale >= 40) $progressClass = 'warning';
            @endphp
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h3 class="dashboard-card-title">
                        {{ $teatro->nome }}
                        <span class="badge bg-light text-dark">{{ $totMilitari }}</span>
                    </h3>
                    @if($teatro->data_inizio || $teatro->data_fine)
                    <div class="dashboard-card-periodo">
                        <i class="fas fa-calendar-alt me-1"></i>
                        {{ $teatro->getPeriodoFormattato() }}
                    </div>
                    @endif
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-progress-container">
                        <div class="dashboard-progress-header">
                            <span class="dashboard-progress-label">Completamento</span>
                            <span class="dashboard-progress-value">{{ $percentuale }}%</span>
                        </div>
                        <div class="dashboard-progress-bar">
                            <div class="dashboard-progress-fill {{ $progressClass }}" style="width: {{ $percentuale }}%"></div>
                        </div>
                    </div>
                    
                    <div class="dashboard-stats">
                        <div class="dashboard-stat">
                            <div class="dashboard-stat-value success">{{ $completati }}</div>
                            <div class="dashboard-stat-label">Completati</div>
                        </div>
                        <div class="dashboard-stat">
                            <div class="dashboard-stat-value warning">{{ $inCorso }}</div>
                            <div class="dashboard-stat-label">In Corso</div>
                        </div>
                        <div class="dashboard-stat">
                            <div class="dashboard-stat-value danger">{{ $daIniziare }}</div>
                            <div class="dashboard-stat-label">Da Iniziare</div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-card-footer">
                    <a href="{{ route('approntamenti.index', ['teatro_id' => $teatro->id]) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-arrow-right me-1"></i> Gestisci
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-theater-masks"></i></div>
            <div class="empty-state-title">Nessun Teatro Operativo</div>
            <div class="empty-state-text">
                Non ci sono Teatri Operativi configurati. 
                <a href="{{ route('impieghi-personale.index') }}">Vai a Organici</a> per crearli.
            </div>
        </div>
    @endif

@else
    <!-- ========== TEATRO SELECTED VIEW ========== -->
    
    @php
        // Calcola statistiche per ogni cattedra
        $statsCattedre = [];
        foreach($colonneFiltrate as $campo => $config) {
            $statsCattedre[$campo] = ['fatto' => 0, 'mancante' => 0, 'label' => $colonneLabels[$campo] ?? $campo];
        }
        foreach($militari as $m) {
            foreach($colonneFiltrate as $campo => $config) {
                $datiCampo = ApprontamentiController::getValoreCampo($m, $campo);
                if($datiCampo['stato'] == 'valido' || $datiCampo['stato'] == 'in_scadenza') {
                    $statsCattedre[$campo]['fatto']++;
                } else {
                    $statsCattedre[$campo]['mancante']++;
                }
            }
        }
    @endphp
    
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="page-title">APPRONTAMENTI</h1>
        <p class="subtitle-teatro">
            <i class="fas fa-flag"></i> {{ $teatroSelezionato->nome }}
            @if($teatroSelezionato->data_inizio || $teatroSelezionato->data_fine)
                - {{ $teatroSelezionato->getPeriodoFormattato() }}
            @endif
        </p>
    </div>

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 500px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
            <input 
                type="text" 
                id="searchMilitare" 
                class="form-control" 
                placeholder="Cerca militare..." 
                style="padding-left: 40px; border-radius: 6px !important;">
        </div>
    </div>

    <!-- Filtri e legenda -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center">
            <button id="toggleFilters" class="btn btn-primary" style="border-radius: 6px !important;">
                <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i>
                <span id="toggleFiltersText">Mostra filtri</span>
            </button>
            @if($canEdit ?? false)
            <button type="button" class="btn btn-success" onclick="openPrenotazioneModal()" style="border-radius: 6px !important;">
                <i class="fas fa-calendar-plus me-1"></i> Prenota Cattedra
            </button>
            @endif
        </div>
        
        <div class="legenda-scadenze d-flex gap-2 flex-wrap">
            <span class="badge-legenda badge-legenda-valido" style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(40,167,69,0.2);">
                <i class="fas fa-check-circle"></i> Valido
            </span>
            <span class="badge-legenda badge-legenda-in-scadenza" style="background: linear-gradient(135deg, #ffc107, #fd7e14); color: #212529; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(255,193,7,0.2);">
                <i class="fas fa-exclamation-triangle"></i> In Scadenza
            </span>
            <span class="badge-legenda badge-legenda-scaduto" style="background: linear-gradient(135deg, #dc3545, #e74c3c); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(220,53,69,0.2);">
                <i class="fas fa-times-circle"></i> Scaduto
            </span>
            <span class="badge-legenda badge-legenda-mancante" style="background: linear-gradient(135deg, #6c757d, #495057); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 4px rgba(108,117,125,0.2);">
                <i class="fas fa-minus-circle"></i> Non presente
            </span>
        </div>
    </div>

    <!-- Sezione Filtri (stessa logica della pagina CPT) -->
    <div id="filtersContainer" class="filter-section approntamenti-filters">
        <div class="filter-card mb-4">
            <div class="filter-card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-filter me-2"></i> Filtri avanzati
                </div>
            </div>
            <div class="card-body p-3">
                <form id="filtroForm" class="filter-local" onsubmit="return false;">
                    <div class="row mb-3">
                        @foreach($colonneFiltrate as $campo => $config)
                        <div class="col-md-3 mb-2">
                            <label class="form-label">
                                <i class="fas fa-bookmark me-1 text-muted"></i> {{ $colonneLabels[$campo] ?? $campo }}
                            </label>
                            <div class="select-wrapper">
                                <select name="{{ $campo }}" id="filter_{{ $campo }}" class="form-select filter-select" data-label="{{ $colonneLabels[$campo] ?? $campo }}" data-nosubmit="true">
                                    <option value="">Tutti</option>
                                    <option value="valido">Valido</option>
                                    <option value="in_scadenza">In Scadenza</option>
                                    <option value="scaduto">Scaduto</option>
                                    <option value="mancante">Non presente</option>
                                </select>
                                <span class="clear-filter" data-filter="{{ $campo }}" style="display: none;" title="Rimuovi questo filtro">
                                    <i class="fas fa-times"></i>
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    {{-- Pulsanti reset --}}
                    <div class="d-flex justify-content-end align-items-center mt-3 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary reset-all-filters" id="resetAllFilters" style="border-radius: 6px !important; display: none;">
                            <i class="fas fa-redo me-1"></i> Reset filtri
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Badge filtri attivi -->
    <div id="appliedFiltersContainer" class="applied-filters mb-3" style="display: none;"></div>

    <!-- Statistiche per Cattedra -->
    <div class="stats-cattedre-section">
        <div class="stats-cattedre-header">
            <h6 class="stats-cattedre-title" id="toggleStats" onclick="toggleStatsSection()">
                <i class="fas fa-chart-bar"></i> 
                Riepilogo Cattedre ({{ count($militari) }} militari)
                <i class="fas fa-chevron-down"></i>
            </h6>
            <div class="stats-cattedre-actions">
                <!-- Pulsanti rimossi - export via FAB floating -->
            </div>
        </div>
        <div class="stats-cattedre-container" id="statsContainer">
            <div class="stats-cattedre-scroll">
                <div class="stats-cattedre-grid">
                    @php $totaleMilitari = count($militari); @endphp
                    @foreach($statsCattedre as $campo => $stat)
                    @php 
                        $percentuale = $totaleMilitari > 0 ? round(($stat['fatto'] / $totaleMilitari) * 100) : 0; 
                    @endphp
                    <div class="stat-cattedra-card" title="{{ $stat['label'] }}" data-campo="{{ $campo }}" data-label="{{ $stat['label'] }}" data-fatto="{{ $stat['fatto'] }}" data-mancante="{{ $stat['mancante'] }}">
                        <div class="stat-cattedra-header">{{ $stat['label'] }}</div>
                        <div class="stat-cattedra-body">
                            <div class="stat-cattedra-values">
                                <div class="stat-cattedra-value">
                                    <span class="stat-cattedra-number fatto">{{ $stat['fatto'] }}</span>
                                    <span class="stat-cattedra-label">Fatto</span>
                                </div>
                                <div class="stat-cattedra-value">
                                    <span class="stat-cattedra-number mancante">{{ $stat['mancante'] }}</span>
                                    <span class="stat-cattedra-label">Mancante</span>
                                </div>
                            </div>
                            <div class="stat-cattedra-progress">
                                <div class="stat-cattedra-progress-bar" style="width: {{ $percentuale }}%"></div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Tabella -->
    <div class="sugeco-table-nav-container" data-table-nav="auto">
        <div class="sugeco-table-wrapper">
            <table class="sugeco-table" id="approntamentiTable">
                <thead>
                    <tr>
                        <th>Grado</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                        @foreach($colonneFiltrate as $campo => $config)
                        <th>
                            {{ $colonneLabels[$campo] ?? $campo }}
                            @if(ScadenzaApprontamento::isColonnaCondivisa($campo))
                            <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">SYNC</span>
                            @endif
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @forelse($militari as $m)
                    <tr data-militare-id="{{ $m->id }}" 
                        data-militare-nome="{{ $m->grado->sigla ?? '' }} {{ $m->cognome }} {{ $m->nome }}">
                        <td><strong>{{ $m->grado->sigla ?? '-' }}</strong></td>
                        <td>
                            <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                                {{ $m->cognome }}
                            </a>
                        </td>
                        <td>{{ $m->nome }}</td>
                        
                        @foreach($colonneFiltrate as $campo => $config)
                        @php
                            $isSync = ScadenzaApprontamento::isColonnaCondivisa($campo);
                            $datiCampo = ApprontamentiController::getValoreCampo($m, $campo);
                            $valore = $datiCampo['valore'];
                            $valoreRaw = $datiCampo['valore_raw'];
                            $stato = $datiCampo['stato'];
                            
                            $prenotazione = ApprontamentiController::getPrenotazioneAttiva($m->id, $campo, $teatroSelezionato->id);
                            $isPrenotato = $prenotazione !== null;
                            
                            $classeColore = match($stato) {
                                'valido' => 'scadenza-valido',
                                'in_scadenza' => 'scadenza-in-scadenza',
                                'scaduto' => 'scadenza-scaduto',
                                default => 'scadenza-mancante'
                            };
                            
                            if ($isPrenotato) {
                                $classeColore = 'scadenza-prenotato';
                                $stato = 'prenotato';
                            }
                        @endphp
                        <td class="scadenza-cell {{ $classeColore }}" 
                            data-militare-id="{{ $m->id }}"
                            data-campo="{{ $campo }}"
                            data-campo-label="{{ $colonneLabels[$campo] ?? $campo }}"
                            data-valore-raw="{{ $valoreRaw }}"
                            data-stato="{{ $stato }}"
                            data-prenotato="{{ $isPrenotato ? 'true' : 'false' }}"
                            @if(($canEdit ?? false) && !$isSync)
                            onclick="openDataModal(this)"
                            style="cursor: pointer;"
                            @else
                            style="cursor: default;"
                            @endif
                            @if($isSync) title="Modificabile da SPP/Idoneità" @endif>
                            @if($isPrenotato)
                            <i class="fas fa-calendar-check me-1" style="font-size: 0.7rem;"></i>{{ $prenotazione->data_prenotazione->format('d/m/Y') }}
                            @else
                            {{ $valore }}
                            @endif
                        </td>
                        @endforeach
                    </tr>
                @empty
                    <tr class="empty-data-row">
                        <td colspan="{{ 3 + count($colonneFiltrate) }}" class="text-center py-4">
                            <i class="fas fa-users-slash fa-2x text-muted mb-2"></i>
                            <div class="text-muted">Nessun militare assegnato a questo teatro</div>
                        </td>
                    </tr>
                @endforelse
                <!-- Riga per nessun risultato filtri -->
                <tr class="no-results-row" id="noResultsRow">
                    <td colspan="{{ 3 + count($colonneFiltrate) }}" class="no-results-cell">
                        <i class="fas fa-search fa-2x text-muted mb-3 d-block"></i>
                        <div class="text-muted fw-semibold mb-1">Nessun risultato trovato</div>
                        <div class="text-muted small">Prova a modificare i criteri di ricerca o i filtri</div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Modal Overlay -->
<div class="modal-overlay" id="modalOverlay" onclick="closeDataModal()"></div>

<!-- Modal Modifica Data -->
<div class="scadenza-modal" id="dataModal">
    <div class="modal-header-custom">
        <h5 id="modalTitle">Modifica Data</h5>
    </div>
    <div class="modal-militare-info" id="modalMilitareInfo"></div>
    <div class="modal-body-custom">
        <div id="prenotazioneInfo" class="alert alert-info" style="display: none;">
            <small><i class="fas fa-info-circle me-1"></i>Prenotazione attiva - Inserisci data effettiva</small>
        </div>
        <div class="form-group">
            <label for="modalDataInput">Data:</label>
            <input type="date" id="modalDataInput" class="form-control">
        </div>
    </div>
    <div class="modal-footer-custom">
        <button type="button" class="btn-cancel" onclick="closeDataModal()">Annulla</button>
        <button type="button" class="btn-save" id="btnSaveData" onclick="saveData()">Salva</button>
    </div>
</div>

<!-- Modal Prenotazione -->
<div class="modal fade" id="modalPrenotazione" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white;">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Prenota Cattedra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Cattedra</label>
                        <select class="form-select" id="prenotazioneCattedra" style="border-radius: 6px !important;">
                            <option value="">Seleziona...</option>
                            @if(isset($colonneFiltrate))
                            @foreach($colonneFiltrate as $campo => $config)
                                @if(!ScadenzaApprontamento::isColonnaCondivisa($campo))
                                <option value="{{ $campo }}">{{ $colonneLabels[$campo] ?? $campo }}</option>
                                @endif
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Data</label>
                        <input type="date" class="form-control" id="prenotazioneData" style="border-radius: 6px !important;">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Quantità</label>
                        <input type="number" class="form-control" id="prenotazioneQuantita" min="1" max="50" value="10" style="border-radius: 6px !important;">
                    </div>
                </div>
                
                <div class="text-center mb-3">
                    <button type="button" class="btn btn-primary" id="btnCercaMilitari" style="border-radius: 6px !important;">
                        <i class="fas fa-search me-1"></i>Cerca Disponibili
                    </button>
                </div>
                
                <div id="risultatiPrenotazione" style="display: none;">
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <strong>Militari Proposti</strong>
                        <span class="badge bg-secondary" id="contatoreMilitari"></span>
                    </div>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="40"><input type="checkbox" class="form-check-input" id="selectAllMilitari" checked></th>
                                    <th>Grado</th>
                                    <th>Cognome</th>
                                    <th>Nome</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody id="tabellaPropostaMilitari"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 6px !important;">Chiudi</button>
                <button type="button" class="btn btn-success" id="btnSalvaPrenotazioni" style="display: none; border-radius: 6px !important;">
                    <i class="fas fa-check me-1"></i>Prenota
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FAB Export Floating -->
@if(isset($teatroSelezionato) && $teatroSelezionato)
<a href="{{ route('approntamenti.export-excel', ['teatro_id' => $teatroSelezionato->id]) }}" 
   class="fab-export" title="Esporta Excel">
    <i class="fas fa-file-excel"></i>
    <span class="fab-tooltip">Esporta Excel</span>
</a>
@endif

@endsection

@push('scripts')
<script>
const CONFIG = {
    teatroId: {{ isset($teatroSelezionato) && $teatroSelezionato ? $teatroSelezionato->id : 'null' }},
    csrfToken: '{{ csrf_token() }}',
    routes: {
        index: '{{ route("approntamenti.index") }}',
        updateSingola: '{{ route("approntamenti.update-singola", ["militare" => "__ID__"]) }}',
        confermaPrenotazione: '{{ route("approntamenti.conferma-prenotazione") }}',
        proponiPrenotazione: '{{ route("approntamenti.proponi-prenotazione") }}',
        salvaPrenotazione: '{{ route("approntamenti.salva-prenotazione") }}'
    }
};

let currentCell = null;

// === Teatro Selection ===
function selezionaTeatro(value) {
    if (value) window.location.href = `${CONFIG.routes.index}?teatro_id=${value}`;
}

// === API Helper ===
async function apiCall(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CONFIG.csrfToken,
            'Accept': 'application/json'
        },
        ...options
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'Errore');
    return data;
}

// === Toast ===
function showToast(type, message) {
    if (window.SUGECO?.Toast) {
        window.SUGECO.Toast.show(type, message);
    } else {
        alert(message);
    }
}

// === Toggle Filtri (identico a CPT) ===
document.getElementById('toggleFilters')?.addEventListener('click', function() {
    const filtersContainer = document.getElementById('filtersContainer');
    const toggleText = document.getElementById('toggleFiltersText');
    const toggleIcon = document.getElementById('toggleFiltersIcon');
    
    const isVisible = filtersContainer.classList.toggle('visible');
    this.classList.toggle('active', isVisible);
    
    if (toggleText) {
        toggleText.textContent = isVisible ? 'Nascondi filtri' : 'Mostra filtri';
    }
    
    if (toggleIcon) {
        toggleIcon.classList.toggle('fa-rotate-180', isVisible);
    }
    
    // Salva stato
    try {
        localStorage.setItem('approntamentiFiltersOpen', isVisible ? 'true' : 'false');
    } catch (e) {}
});

// === Toggle Statistiche Cattedre ===
function toggleStatsSection() {
    const container = document.getElementById('statsContainer');
    const title = document.getElementById('toggleStats');
    
    if (container.classList.contains('hidden')) {
        container.classList.remove('hidden');
        title.classList.remove('collapsed');
    } else {
        container.classList.add('hidden');
        title.classList.add('collapsed');
    }
}

// === Export Statistiche Cattedre in Excel ===
function exportStatsCattedre() {
    const cards = document.querySelectorAll('.stat-cattedra-card');
    const teatroNome = '{{ $teatroSelezionato->nome ?? "Teatro" }}';
    const totaleMilitari = {{ count($militari ?? []) }};
    
    // Crea CSV
    let csv = 'Cattedra,Fatto,Mancante,Totale,% Completamento\n';
    
    cards.forEach(card => {
        const label = card.getAttribute('data-label');
        const fatto = card.getAttribute('data-fatto');
        const mancante = card.getAttribute('data-mancante');
        const percentuale = totaleMilitari > 0 ? Math.round((fatto / totaleMilitari) * 100) : 0;
        
        csv += `"${label}",${fatto},${mancante},${totaleMilitari},${percentuale}%\n`;
    });
    
    // Download
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `Statistiche_Cattedre_${teatroNome.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    
    showToast('success', 'Statistiche esportate');
}

// === Filtri Client-Side (identico alla pagina CPT usando SUGECO.Filters) ===
function applicaFiltri() {
    const searchTerm = document.getElementById('searchMilitare')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('#approntamentiTable tbody tr[data-militare-id]');
    const noResultsRow = document.getElementById('noResultsRow');
    const emptyDataRow = document.querySelector('.empty-data-row');
    
    // Leggi tutti i filtri dalle select
    const filterSelects = document.querySelectorAll('.filter-select');
    const filtriAttivi = {};
    filterSelects.forEach(select => {
        const name = select.getAttribute('name');
        const value = select.value;
        if (value) filtriAttivi[name] = value;
    });
    
    let visibili = 0;
    rows.forEach(row => {
        const militareNome = row.getAttribute('data-militare-nome') || '';
        
        // Check ricerca
        const matchRicerca = !searchTerm || militareNome.toLowerCase().includes(searchTerm);
        
        // Check filtri
        let matchFiltri = true;
        for (const [filtroKey, filtroValue] of Object.entries(filtriAttivi)) {
            const cella = row.querySelector(`.scadenza-cell[data-campo="${filtroKey}"]`);
            if (cella) {
                let statoCell = cella.getAttribute('data-stato');
                // Normalizza stato mancante
                if (statoCell === 'non_presente') statoCell = 'mancante';
                if (statoCell === 'prenotato') statoCell = 'mancante';
                
                // Per il filtro "scaduto", verifica lo stato esatto
                if (filtroValue === 'scaduto' && statoCell !== 'scaduto') {
                    matchFiltri = false;
                    break;
                }
                // Per il filtro "mancante", verifica sia mancante che non_presente
                if (filtroValue === 'mancante' && statoCell !== 'mancante') {
                    matchFiltri = false;
                    break;
                }
                // Per altri filtri confronto diretto
                if (filtroValue !== 'scaduto' && filtroValue !== 'mancante' && statoCell !== filtroValue) {
                    matchFiltri = false;
                    break;
                }
            }
        }
        
        if (matchRicerca && matchFiltri) {
            row.style.display = '';
            visibili++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Mostra/nascondi messaggio "nessun risultato"
    if (noResultsRow) {
        if (visibili === 0 && rows.length > 0) {
            noResultsRow.classList.add('visible');
            if (emptyDataRow) emptyDataRow.style.display = 'none';
        } else {
            noResultsRow.classList.remove('visible');
        }
    }
    
    // Aggiorna UI filtri (highlight, X, badge)
    aggiornaUIFiltri();
    
    // Aggiorna pulsante Reset
    const resetBtn = document.getElementById('resetAllFilters');
    const hasActiveFilters = Object.keys(filtriAttivi).length > 0 || searchTerm;
    if (resetBtn) {
        resetBtn.style.display = hasActiveFilters ? 'inline-block' : 'none';
    }
}

// Aggiorna UI filtri attivi (identico a CPT)
function aggiornaUIFiltri() {
    const filterSelects = document.querySelectorAll('.filter-select');
    const appliedContainer = document.getElementById('appliedFiltersContainer');
    const filtriAttivi = [];
    
    // Aggiorna ogni select
    filterSelects.forEach(select => {
        const wrapper = select.closest('.select-wrapper');
        const clearBtn = wrapper?.querySelector('.clear-filter');
        const hasValue = select.value !== '';
        
        // Highlight select
        if (hasValue) {
            select.classList.add('applied');
            if (clearBtn) clearBtn.style.display = 'flex';
            filtriAttivi.push({
                name: select.getAttribute('name'),
                label: select.getAttribute('data-label'),
                value: select.value,
                valueLabel: select.options[select.selectedIndex].text
            });
        } else {
            select.classList.remove('applied');
            if (clearBtn) clearBtn.style.display = 'none';
        }
    });
    
    // Aggiorna badge filtri attivi
    if (appliedContainer) {
        if (filtriAttivi.length > 0) {
            let html = filtriAttivi.map(f => `
                <span class="applied-filter">
                    <span class="filter-name">${f.label}:</span>
                    <span class="filter-value">${f.valueLabel}</span>
                    <span class="remove-filter" data-name="${f.name}" title="Rimuovi">
                        <i class="fas fa-times"></i>
                    </span>
                </span>
            `).join('');
            
            html += `
                <span class="applied-filter" style="background: #dc3545; cursor: pointer;" onclick="rimuoviTuttiFiltri()">
                    <i class="fas fa-times me-1"></i> Rimuovi tutti
                </span>
            `;
            
            appliedContainer.innerHTML = html;
            appliedContainer.style.display = 'flex';
            
            // Event listener per rimuovere singoli filtri
            appliedContainer.querySelectorAll('.remove-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    const name = this.getAttribute('data-name');
                    const select = document.querySelector(`select[name="${name}"]`);
                    if (select) {
                        select.value = '';
                        applicaFiltri();
                    }
                });
            });
        } else {
            appliedContainer.innerHTML = '';
            appliedContainer.style.display = 'none';
        }
    }
}

// Rimuovi tutti i filtri
function rimuoviTuttiFiltri() {
    document.querySelectorAll('.filter-select').forEach(select => {
        select.value = '';
        select.classList.remove('applied');
    });
    document.getElementById('searchMilitare').value = '';
    applicaFiltri();
}

// Eventi filtri
document.getElementById('searchMilitare')?.addEventListener('input', applicaFiltri);

document.querySelectorAll('.filter-select').forEach(select => {
    select.addEventListener('change', function(e) {
        e.preventDefault();
        applicaFiltri();
    });
});

// Clear filter buttons (X nelle select)
document.querySelectorAll('.clear-filter').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const filterName = this.getAttribute('data-filter');
        const select = document.getElementById('filter_' + filterName) || 
                      document.querySelector(`select[name="${filterName}"]`);
        if (select) {
            select.value = '';
            select.classList.remove('applied');
            this.style.display = 'none';
            applicaFiltri();
        }
    });
});

// Reset All Filters button
document.getElementById('resetAllFilters')?.addEventListener('click', function(e) {
    e.preventDefault();
    rimuoviTuttiFiltri();
});

// Impedisci submit form filtri
document.getElementById('filtroForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    return false;
});

// === Modal Data ===
function openDataModal(cell) {
    currentCell = cell;
    const militareId = cell.getAttribute('data-militare-id');
    const campo = cell.getAttribute('data-campo');
    const campoLabel = cell.getAttribute('data-campo-label');
    const valoreRaw = cell.getAttribute('data-valore-raw');
    const isPrenotato = cell.getAttribute('data-prenotato') === 'true';
    const row = cell.closest('tr');
    const militareNome = row.getAttribute('data-militare-nome');
    
    document.getElementById('modalTitle').textContent = `Modifica ${campoLabel}`;
    document.getElementById('modalMilitareInfo').textContent = militareNome;
    document.getElementById('modalDataInput').value = valoreRaw || '';
    document.getElementById('prenotazioneInfo').style.display = isPrenotato ? 'block' : 'none';
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('dataModal').classList.add('show');
}

function closeDataModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.getElementById('dataModal').classList.remove('show');
    currentCell = null;
}

async function saveData() {
    if (!currentCell) return;
    
    const dateInput = document.getElementById('modalDataInput');
    if (!dateInput.value) {
        showToast('warning', 'Seleziona una data');
        return;
    }
    
    const militareId = currentCell.getAttribute('data-militare-id');
    const campo = currentCell.getAttribute('data-campo');
    const isPrenotato = currentCell.getAttribute('data-prenotato') === 'true';
    
    const [year, month, day] = dateInput.value.split('-');
    const valore = `${day}/${month}/${year}`;
    
    const btn = document.getElementById('btnSaveData');
    btn.disabled = true;
    btn.textContent = 'Salvataggio...';
    
    try {
        const url = isPrenotato 
            ? CONFIG.routes.confermaPrenotazione
            : CONFIG.routes.updateSingola.replace('__ID__', militareId);
        
        const body = isPrenotato
            ? { militare_id: militareId, teatro_id: CONFIG.teatroId, cattedra: campo, data_effettiva: valore }
            : { campo: campo, valore: valore };
        
        const data = await apiCall(url, { method: 'POST', body: JSON.stringify(body) });
        
        if (data.success) {
            currentCell.innerHTML = data.valore;
            currentCell.className = 'scadenza-cell ' + getClasseFromStato(data.stato);
            currentCell.setAttribute('data-stato', data.stato);
            currentCell.setAttribute('data-valore-raw', dateInput.value);
            currentCell.setAttribute('data-prenotato', 'false');
            
            closeDataModal();
            showToast('success', data.message || 'Salvato');
        }
    } catch (error) {
        showToast('error', error.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Salva';
    }
}

function getClasseFromStato(stato) {
    return {
        'valido': 'scadenza-valido',
        'in_scadenza': 'scadenza-in-scadenza',
        'scaduto': 'scadenza-scaduto'
    }[stato] || 'scadenza-mancante';
}

// === Prenotazione ===
function openPrenotazioneModal() {
    document.getElementById('risultatiPrenotazione').style.display = 'none';
    document.getElementById('btnSalvaPrenotazioni').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPrenotazione')).show();
}

document.getElementById('btnCercaMilitari')?.addEventListener('click', async function() {
    const cattedra = document.getElementById('prenotazioneCattedra').value;
    const data = document.getElementById('prenotazioneData').value;
    const quantita = document.getElementById('prenotazioneQuantita').value;
    
    if (!cattedra || !data || !quantita) {
        showToast('warning', 'Compila tutti i campi');
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ricerca...';
    
    try {
        const result = await apiCall(CONFIG.routes.proponiPrenotazione, {
            method: 'POST',
            body: JSON.stringify({ teatro_id: CONFIG.teatroId, cattedra, data, quantita })
        });
        
        if (result.success) {
            const tbody = document.getElementById('tabellaPropostaMilitari');
            
            tbody.innerHTML = result.militari.length === 0
                ? '<tr><td colspan="5" class="text-center text-muted py-3">Nessun militare disponibile</td></tr>'
                : result.militari.map(m => `
                    <tr>
                        <td><input type="checkbox" class="form-check-input militare-checkbox" value="${m.id}" checked></td>
                        <td><strong>${m.grado}</strong></td>
                        <td>${m.cognome}</td>
                        <td>${m.nome}</td>
                        <td><span class="badge bg-secondary">${m.stato_cattedra}</span></td>
                    </tr>
                `).join('');
            
            document.getElementById('contatoreMilitari').textContent = `${result.totale_proposti} proposti`;
            document.getElementById('risultatiPrenotazione').style.display = 'block';
            document.getElementById('btnSalvaPrenotazioni').style.display = result.militari.length > 0 ? 'inline-block' : 'none';
        }
    } catch (error) {
        showToast('error', error.message);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-search me-1"></i>Cerca Disponibili';
    }
});

document.getElementById('btnSalvaPrenotazioni')?.addEventListener('click', async function() {
    const ids = Array.from(document.querySelectorAll('.militare-checkbox:checked')).map(cb => parseInt(cb.value));
    
    if (ids.length === 0) {
        showToast('warning', 'Seleziona almeno un militare');
        return;
    }
    
    this.disabled = true;
    
    try {
        const result = await apiCall(CONFIG.routes.salvaPrenotazione, {
            method: 'POST',
            body: JSON.stringify({
                teatro_id: CONFIG.teatroId,
                cattedra: document.getElementById('prenotazioneCattedra').value,
                data: document.getElementById('prenotazioneData').value,
                militari_ids: ids
            })
        });
        
        if (result.success) {
            showToast('success', result.message);
            bootstrap.Modal.getInstance(document.getElementById('modalPrenotazione')).hide();
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('error', error.message);
        this.disabled = false;
    }
});

document.getElementById('selectAllMilitari')?.addEventListener('change', function() {
    document.querySelectorAll('.militare-checkbox').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
