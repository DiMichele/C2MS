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

    <!-- Barra pulsanti e legenda -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex gap-2 align-items-center">
            <button type="button" id="toggleFilters" class="btn btn-outline-primary" style="border-radius: 6px !important;">
                <i id="toggleFiltersIcon" class="fas fa-filter me-2"></i>
                <span id="toggleFiltersText">Mostra filtri</span>
            </button>
            @if($canEdit ?? false)
            <button type="button" class="btn btn-success" onclick="openPrenotazioneUnificata()" style="border-radius: 6px !important;">
                <i class="fas fa-calendar-plus me-1"></i> Pianifica Cattedra
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

    <!-- Sezione Filtri (collassabile) -->
    <div id="filtersContainer" class="mb-3" style="display: none;">
        <div class="filter-card">
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
                        @php
                            $statCattedra = $statsCattedre[$campo] ?? ['fatto' => 0, 'mancante' => 0];
                        @endphp
                        <th class="cattedra-header-cell">
                            <div class="cattedra-header-name">
                                {{ $colonneLabels[$campo] ?? $campo }}
                                @if(ScadenzaApprontamento::isColonnaCondivisa($campo))
                                <span class="badge bg-warning text-dark ms-1" style="font-size: 0.6rem;">SYNC</span>
                                @endif
                            </div>
                            <div class="cattedra-header-stats">
                                <span class="stat-fatto" title="Fatto">{{ $statCattedra['fatto'] }}</span>
                                <span class="stat-separator">/</span>
                                <span class="stat-mancante" title="Da fare">{{ $statCattedra['mancante'] }}</span>
                            </div>
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

<!-- Modal Prenotazione Unificato -->
<div class="modal fade" id="modalPrenotazioneUnificata" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" id="modalPrenotazioneHeader" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white;">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Pianifica Cattedra</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Step 1: Selezione Cattedra e Data -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Cattedra <span class="text-danger">*</span></label>
                                <select class="form-select" id="prenotazioneCattedra" style="border-radius: 6px !important;" onchange="onCattedraChange()">
                                    <option value="">Seleziona cattedra...</option>
                                    @if(isset($colonneFiltrate))
                                    @foreach($colonneFiltrate as $campo => $config)
                                        @if(!ScadenzaApprontamento::isColonnaCondivisa($campo))
                                        <option value="{{ $campo }}" 
                                                data-is-mcm="{{ $campo === 'mcm' ? 'true' : 'false' }}"
                                                data-requisito-awareness="{{ $campo === 'cied_pratico' ? 'true' : 'false' }}">
                                            {{ $colonneLabels[$campo] ?? $campo }}
                                            @if($campo === 'mcm') (40 ore) @endif
                                        </option>
                                        @endif
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            
                            <!-- Sezione date singola (default) -->
                            <div class="col-md-4" id="sezioneDataSingola">
                                <label class="form-label fw-semibold">Data <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="prenotazioneData" style="border-radius: 6px !important;" onchange="onDataChange()">
                                <div id="infoGiornata" class="form-text mt-1" style="display: none;"></div>
                            </div>
                            
                            <div class="col-md-4" id="sezioneQuantitaGlobale">
                                <label class="form-label fw-semibold">N. Militari <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="prenotazioneQuantita" min="1" max="100" value="15" style="border-radius: 6px !important;">
                            </div>
                        </div>
                        
                        <!-- Sezione date multiple (solo per MCM) -->
                        <div id="sezioneDateMultiple" class="mt-3" style="display: none;">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt me-1"></i>Giornate MCM
                            </label>
                            <div id="mcmDateContainer">
                                <div class="mcm-date-row mb-2" data-date-row="0">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="mcm-date-input-wrapper flex-grow-1">
                                            <input type="date" class="form-control mcm-date-input" onchange="onMcmDateChange(this)">
                                        </div>
                                        <div class="mcm-ore-display">
                                            <span class="badge bg-secondary mcm-ore-badge">0h</span>
                                        </div>
                                        <div class="mcm-quantita-wrapper">
                                            <input type="number" class="form-control form-control-sm mcm-quantita-input" 
                                                   value="15" min="1" max="50" style="width: 70px;" 
                                                   placeholder="N." title="Numero militari">
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-date" 
                                                onclick="removeMcmDateRow(this)" style="display: none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addMcmDateRow()">
                                <i class="fas fa-plus me-1"></i>Aggiungi giornata
                            </button>
                        </div>
                        
                        <!-- Info MCM -->
                        <div id="infoMcm" class="alert alert-warning mt-3" style="display: none;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-info-circle me-3 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <strong>MCM richiede 40 ore totali</strong>
                                    <span class="ms-2">| Lun-Gio: 8 ore | Ven: 4 ore | Requisiti: Idoneità SMI o T.O. valida</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info C-IED pratico -->
                        <div id="infoCiedPratico" class="alert alert-info mt-3" style="display: none;">
                            <div class="d-flex align-items-start">
                                <i class="fas fa-exclamation-triangle me-3 mt-1" style="font-size: 1.2rem;"></i>
                                <div>
                                    <strong>Requisito:</strong> AWARENESS C-IED deve essere stato completato o prenotato in data precedente
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mb-4">
                    <button type="button" class="btn btn-primary btn-lg" id="btnCercaMilitari" style="border-radius: 6px !important;">
                        <i class="fas fa-search me-1"></i>Cerca Militari Disponibili
                    </button>
                </div>
                
                <!-- Risultati -->
                <div id="risultatiPrenotazione" style="display: none;">
                    <hr>
                    
                    <!-- Tabs per Disponibili/Non Disponibili -->
                    <ul class="nav nav-tabs mb-3" id="tabMilitari" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-disponibili" data-bs-toggle="tab" data-bs-target="#panel-disponibili" type="button">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Disponibili <span class="badge bg-success ms-1" id="countDisponibili">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-non-disponibili" data-bs-toggle="tab" data-bs-target="#panel-non-disponibili" type="button">
                                <i class="fas fa-times-circle text-danger me-1"></i>
                                Non Disponibili <span class="badge bg-danger ms-1" id="countNonDisponibili">0</span>
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Panel Disponibili -->
                        <div class="tab-pane fade show active" id="panel-disponibili" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <input type="checkbox" class="form-check-input me-2" id="selectAllDisponibili" checked>
                                    <label class="form-check-label" for="selectAllDisponibili">Seleziona tutti</label>
                                </div>
                            </div>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th width="40"></th>
                                            <th>Grado</th>
                                            <th>Cognome</th>
                                            <th>Nome</th>
                                            <th>Stato Cattedra</th>
                                            <th id="colOreHeader" style="display: none;">Ore MCM</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabellaDisponibili"></tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Panel Non Disponibili -->
                        <div class="tab-pane fade" id="panel-non-disponibili" role="tabpanel">
                            <div class="alert alert-secondary mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Questi militari non sono disponibili per la data selezionata. Il motivo è indicato per ciascuno.
                            </div>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Grado</th>
                                            <th>Cognome</th>
                                            <th>Nome</th>
                                            <th>Motivo Non Disponibilità</th>
                                            <th id="colOreHeaderNonDisp" style="display: none;">Ore MCM</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabellaNonDisponibili"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Risultati MCM Multi-Giorno -->
                <div id="risultatiMcmMultiGiorno" style="display: none;">
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-calendar-alt me-2 text-warning"></i>Militari per ogni Giornata MCM</h6>
                    <div class="accordion" id="accordionMcmGiorni"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 6px !important;">Chiudi</button>
                <button type="button" class="btn btn-success" id="btnSalvaPrenotazioni" style="display: none; border-radius: 6px !important;">
                    <i class="fas fa-check me-1"></i>Conferma Prenotazione
                </button>
            </div>
        </div>
    </div>
</div>

<!-- FAB Container - Pulsanti Floating Verticali -->
@if(isset($teatroSelezionato) && $teatroSelezionato)
<div class="fab-vertical-stack">
    @if($canEdit ?? false)
    <button type="button" class="fab-button fab-prenotazioni" onclick="openModalPrenotazioniAttive()" title="Prenotazioni Attive">
        <i class="fas fa-calendar-check"></i>
        <span class="fab-counter" id="fabBadgePrenotazioni" style="display: none;">0</span>
    </button>
    @endif
    <a href="{{ route('approntamenti.export-excel', ['teatro_id' => $teatroSelezionato->id]) }}" 
       class="fab-button fab-excel" title="Esporta Excel">
        <i class="fas fa-file-excel"></i>
    </a>
</div>
@endif

<!-- Modal Prenotazioni Attive -->
<div class="modal fade" id="modalPrenotazioniAttive" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white; border: none;">
                <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>Gestione Prenotazioni Attive</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background: #f8f9fa;">
                <div id="loadingPrenotazioni" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x" style="color: #0a2342;"></i>
                    <p class="mt-3 text-muted">Caricamento prenotazioni...</p>
                </div>
                
                <div id="contentPrenotazioni" style="display: none;">
                    <div id="alertNoPrenotazioni" class="alert alert-info" style="display: none; border-radius: 8px;">
                        <i class="fas fa-info-circle me-2"></i>
                        Nessuna prenotazione attiva per questo teatro operativo.
                    </div>
                    
                    <!-- Toolbar -->
                    <div class="card mb-4" style="border: none; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <div class="card-body py-3">
                            <div class="row align-items-center g-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold mb-1" style="color: #0a2342;">
                                        <i class="fas fa-filter me-1"></i>Filtra per cattedra
                                    </label>
                                    <select class="form-select" id="filtroPrenotazioneCattedra" onchange="filtraPrenotazioniPerCattedra()" style="border-radius: 8px;">
                                        <option value="">-- Tutte le cattedre --</option>
                                    </select>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button type="button" class="btn" onclick="exportPrenotazioniExcel()" style="background: linear-gradient(135deg, #28a745, #218838); color: white; border-radius: 8px;">
                                        <i class="fas fa-file-excel me-2"></i>Esporta Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Accordion per cattedre -->
                    <div class="accordion" id="accordionPrenotazioni"></div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                    <i class="fas fa-times me-1"></i>Chiudi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifica Data Cattedra (per tutto il gruppo) -->
<div class="modal fade" id="modalModificaDataCattedra" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 12px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #d4af37, #f4d03f); color: #0a2342; border: none;">
                <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Modifica Data Cattedra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modificaCattedraKey">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="color: #0a2342;">Cattedra</label>
                    <div id="modificaCattedraLabel" class="form-control-plaintext fw-bold" style="color: #0a2342;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="color: #0a2342;">Militari coinvolti</label>
                    <div id="modificaMilitariCount" class="form-control-plaintext"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="color: #0a2342;">Nuova Data Prenotazione <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="modificaNuovaData" style="border-radius: 8px;">
                    <small class="form-text text-muted">La nuova data sarà applicata a tutti i militari prenotati per questa cattedra</small>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">Annulla</button>
                <button type="button" class="btn" onclick="salvaModificaDataCattedra()" style="background: linear-gradient(135deg, #d4af37, #f4d03f); color: #0a2342; border-radius: 8px;">
                    <i class="fas fa-save me-1"></i>Salva Modifiche
                </button>
            </div>
        </div>
    </div>
</div>


<style>
/* Intestazione Cattedre con Statistiche */
.cattedra-header-cell {
    vertical-align: middle;
    text-align: center;
    min-width: 80px;
}

.cattedra-header-name {
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 4px;
}

.cattedra-header-stats {
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 500;
}

.cattedra-header-stats .stat-fatto {
    color: #28a745;
}

.cattedra-header-stats .stat-separator {
    color: #adb5bd;
    margin: 0 2px;
}

.cattedra-header-stats .stat-mancante {
    color: #dc3545;
}

/* MCM Date Rows */
.mcm-date-row {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.mcm-date-row:hover {
    border-color: #0a2342;
    background: #fff;
}

.mcm-date-input-wrapper {
    position: relative;
}

.mcm-date-input-wrapper input {
    border-radius: 6px !important;
    border: 1px solid #ced4da;
    padding-right: 10px;
}

.mcm-ore-display {
    min-width: 45px;
    text-align: center;
}

.mcm-ore-badge {
    font-size: 0.75rem;
    padding: 5px 8px;
}

.mcm-quantita-wrapper input {
    border-radius: 6px !important;
    text-align: center;
    font-weight: 600;
}

.mcm-quantita-wrapper input:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 2px rgba(10, 35, 66, 0.1);
}

#sezioneDateMultiple .btn-remove-date {
    padding: 0.25rem 0.5rem;
}

/* MCM Accordion Giorni */
.mcm-giorno-item .accordion-button {
    background: #f8f9fa;
    padding: 0.75rem 1rem;
}

.mcm-giorno-item .accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #fff8e1, #fffde7);
    color: #0a2342;
}

.mcm-giorno-item .accordion-body {
    padding: 1rem;
    background: #fff;
}

.mcm-giorno-item details summary {
    cursor: pointer;
    user-select: none;
}

.mcm-giorno-item details summary:hover {
    text-decoration: underline;
}

/* FAB Vertical Stack */
.fab-vertical-stack {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 1040;
}

.fab-button {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    transition: all 0.3s ease;
    position: relative;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.fab-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
}

.fab-prenotazioni {
    background: linear-gradient(135deg, #0a2342, #1a3a5c);
    color: white;
}

.fab-prenotazioni:hover {
    color: #d4af37;
}

.fab-excel {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
}

.fab-excel:hover {
    color: white;
}

.fab-counter {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    min-width: 22px;
    height: 22px;
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    border: 2px solid white;
}

/* Accordion Prenotazioni */
#accordionPrenotazioni .accordion-item {
    border: 1px solid #dee2e6;
    margin-bottom: 8px;
    border-radius: 6px !important;
    overflow: hidden;
}

#accordionPrenotazioni .accordion-button {
    font-weight: 500;
    background: #f8f9fa;
    color: #333;
    padding: 12px 16px;
}

#accordionPrenotazioni .accordion-button:not(.collapsed) {
    background: #0a2342;
    color: white;
    box-shadow: none;
}

#accordionPrenotazioni .accordion-button:focus {
    box-shadow: none;
}

#accordionPrenotazioni .accordion-button::after {
    filter: brightness(0) saturate(100%);
}

#accordionPrenotazioni .accordion-button:not(.collapsed)::after {
    filter: brightness(0) invert(1);
}

#accordionPrenotazioni .accordion-body {
    padding: 16px;
    background: white;
}

/* Lista militari */
#accordionPrenotazioni .list-group-item {
    border-left: none;
    border-right: none;
    border-radius: 0;
}

#accordionPrenotazioni .list-group-item:first-child {
    border-top: none;
}

.fw-medium {
    font-weight: 500;
}
</style>

@endsection

@push('scripts')
<script>
const CONFIG = {
    teatroId: {{ isset($teatroSelezionato) && $teatroSelezionato ? $teatroSelezionato->id : 'null' }},
    csrfToken: '{{ csrf_token() }}',
    routes: {
        index: '{{ route("approntamenti.index") }}',
        updateSingola: '{{ route("approntamenti.update-singola", ["militare" => "__ID__"]) }}',
        proponiPrenotazione: '{{ route("approntamenti.proponi-prenotazione") }}',
        salvaPrenotazione: '{{ route("approntamenti.salva-prenotazione") }}',
        // Prenotazioni attive
        prenotazioniAttive: '{{ route("approntamenti.prenotazioni-attive") }}',
        modificaPrenotazioneMultipla: '{{ route("approntamenti.modifica-prenotazione-multipla") }}',
        confermaPrenotazioneMultipla: '{{ route("approntamenti.conferma-prenotazione-multipla") }}',
        verificaDisponibilita: '{{ route("approntamenti.verifica-disponibilita") }}',
        annullaPrenotazione: '{{ route("approntamenti.annulla-prenotazione") }}',
        exportPrenotazioniExcel: '{{ route("approntamenti.export-prenotazioni-excel") }}'
    }
};

let currentCell = null;
let currentMilitariData = []; // Dati militari correnti
let prenotazioniAttiveData = []; // Dati prenotazioni attive
let mcmDateRowCount = 1; // Contatore righe date MCM

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

// === Toggle Filtri (CORRETTO) ===
document.getElementById('toggleFilters')?.addEventListener('click', function() {
    const filtersContainer = document.getElementById('filtersContainer');
    const toggleText = document.getElementById('toggleFiltersText');
    const toggleIcon = document.getElementById('toggleFiltersIcon');
    
    // Toggle visibility usando display
    const isCurrentlyVisible = filtersContainer.style.display !== 'none';
    const newVisibleState = !isCurrentlyVisible;
    
    filtersContainer.style.display = newVisibleState ? 'block' : 'none';
    this.classList.toggle('active', newVisibleState);
    this.classList.toggle('btn-primary', newVisibleState);
    this.classList.toggle('btn-outline-primary', !newVisibleState);
    
    if (toggleText) {
        toggleText.textContent = newVisibleState ? 'Nascondi filtri' : 'Mostra filtri';
    }
    
    if (toggleIcon) {
        toggleIcon.classList.toggle('fa-times', newVisibleState);
        toggleIcon.classList.toggle('fa-filter', !newVisibleState);
    }
    
    // Salva stato
    try {
        localStorage.setItem('approntamentiFiltersOpen', isVisible ? 'true' : 'false');
    } catch (e) {}
});

// === Export Statistiche Cattedre in Excel (deprecato) ===
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
    
    // Se è prenotato, mostra il modal specifico per prenotazioni
    if (isPrenotato) {
        openPrenotatoModal(cell, militareId, campo, campoLabel, militareNome, valoreRaw);
        return;
    }
    
    document.getElementById('modalTitle').textContent = `Modifica ${campoLabel}`;
    document.getElementById('modalMilitareInfo').textContent = militareNome;
    document.getElementById('modalDataInput').value = valoreRaw || '';
    document.getElementById('prenotazioneInfo').style.display = false;
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('dataModal').classList.add('show');
}

// Modal specifico per celle prenotate
function openPrenotatoModal(cell, militareId, campo, campoLabel, militareNome, dataPrenotazione) {
    // Cerca la prenotazione
    const prenotazioneId = cell.getAttribute('data-prenotazione-id');
    
    let modalHtml = `
        <div class="modal fade" id="modalPrenotatoAzione" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white;">
                        <h5 class="modal-title"><i class="fas fa-calendar-check me-2"></i>${campoLabel}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-3">
                            <strong>${militareNome}</strong>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success btn-lg" onclick="confermaPrenotatoDaTabella('${militareId}', '${campo}')">
                                <i class="fas fa-check me-2"></i>Conferma Svolta
                            </button>
                            <button type="button" class="btn btn-outline-warning" onclick="modificaDataPrenotato('${militareId}', '${campo}', '${dataPrenotazione}')">
                                <i class="fas fa-calendar-alt me-2"></i>Modifica Data
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="rimuoviPrenotatoDaTabella('${militareId}', '${campo}')">
                                <i class="fas fa-times me-2"></i>Rimuovi Prenotazione
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Rimuovi modal esistente se presente
    const existing = document.getElementById('modalPrenotatoAzione');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    new bootstrap.Modal(document.getElementById('modalPrenotatoAzione')).show();
}

// Conferma prenotazione dalla tabella
async function confermaPrenotatoDaTabella(militareId, campo) {
    // Chiudi modal
    bootstrap.Modal.getInstance(document.getElementById('modalPrenotatoAzione'))?.hide();
    
    try {
        // Trova la prenotazione
        const result = await apiCall(CONFIG.routes.prenotazioniAttive + `?teatro_id=${CONFIG.teatroId}`);
        if (!result.success) {
            showToast('error', 'Errore nel caricamento');
            return;
        }
        
        // Cerca la prenotazione specifica
        let prenotazioneId = null;
        for (const gruppo of result.prenotazioni) {
            if (gruppo.cattedra === campo) {
                const militare = gruppo.militari.find(m => m.militare_id == militareId);
                if (militare) {
                    prenotazioneId = militare.id;
                    break;
                }
            }
        }
        
        if (!prenotazioneId) {
            showToast('error', 'Prenotazione non trovata');
            return;
        }
        
        // Conferma
        const confermaResult = await apiCall(CONFIG.routes.confermaPrenotazioneMultipla, {
            method: 'POST',
            body: JSON.stringify({ prenotazione_ids: [prenotazioneId] })
        });
        
        if (confermaResult.success) {
            showToast('success', 'Prenotazione confermata');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// Modifica data prenotazione dalla tabella
function modificaDataPrenotato(militareId, campo, dataAttuale) {
    // Chiudi modal corrente
    bootstrap.Modal.getInstance(document.getElementById('modalPrenotatoAzione'))?.hide();
    
    // Mostra modal per modifica data
    let modalHtml = `
        <div class="modal fade" id="modalModificaDataPrenotato" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #0a2342, #1a3a5c); color: white;">
                        <h5 class="modal-title">Modifica Data</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Nuova data:</label>
                        <input type="date" class="form-control" id="nuovaDataPrenotato" value="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                        <button type="button" class="btn btn-primary" onclick="salvaModificaDataPrenotato('${militareId}', '${campo}')">Salva</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const existing = document.getElementById('modalModificaDataPrenotato');
    if (existing) existing.remove();
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    new bootstrap.Modal(document.getElementById('modalModificaDataPrenotato')).show();
}

// Salva modifica data prenotazione singola
async function salvaModificaDataPrenotato(militareId, campo) {
    const nuovaData = document.getElementById('nuovaDataPrenotato').value;
    if (!nuovaData) {
        showToast('warning', 'Seleziona una data');
        return;
    }
    
    try {
        // Trova la prenotazione
        const result = await apiCall(CONFIG.routes.prenotazioniAttive + `?teatro_id=${CONFIG.teatroId}`);
        if (!result.success) {
            showToast('error', 'Errore nel caricamento');
            return;
        }
        
        let prenotazioneId = null;
        for (const gruppo of result.prenotazioni) {
            if (gruppo.cattedra === campo) {
                const militare = gruppo.militari.find(m => m.militare_id == militareId);
                if (militare) {
                    prenotazioneId = militare.id;
                    break;
                }
            }
        }
        
        if (!prenotazioneId) {
            showToast('error', 'Prenotazione non trovata');
            return;
        }
        
        // Modifica data
        const modificaResult = await apiCall(CONFIG.routes.modificaPrenotazioneMultipla, {
            method: 'POST',
            body: JSON.stringify({ 
                prenotazione_ids: [prenotazioneId],
                data_prenotazione: nuovaData
            })
        });
        
        if (modificaResult.success) {
            showToast('success', 'Data aggiornata');
            bootstrap.Modal.getInstance(document.getElementById('modalModificaDataPrenotato'))?.hide();
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// Rimuovi prenotazione dalla tabella
async function rimuoviPrenotatoDaTabella(militareId, campo) {
    if (!await confermaAzione('Rimuovere questa prenotazione?', 'Rimuovi')) {
        return;
    }
    
    // Chiudi modal
    bootstrap.Modal.getInstance(document.getElementById('modalPrenotatoAzione'))?.hide();
    
    try {
        // Trova la prenotazione
        const result = await apiCall(CONFIG.routes.prenotazioniAttive + `?teatro_id=${CONFIG.teatroId}`);
        if (!result.success) {
            showToast('error', 'Errore nel caricamento');
            return;
        }
        
        let prenotazioneId = null;
        for (const gruppo of result.prenotazioni) {
            if (gruppo.cattedra === campo) {
                const militare = gruppo.militari.find(m => m.militare_id == militareId);
                if (militare) {
                    prenotazioneId = militare.id;
                    break;
                }
            }
        }
        
        if (!prenotazioneId) {
            showToast('error', 'Prenotazione non trovata');
            return;
        }
        
        // Rimuovi
        const rimuoviResult = await apiCall(CONFIG.routes.annullaPrenotazione, {
            method: 'POST',
            body: JSON.stringify({ prenotazione_id: prenotazioneId })
        });
        
        if (rimuoviResult.success) {
            showToast('success', 'Prenotazione rimossa');
            setTimeout(() => location.reload(), 1000);
        }
    } catch (error) {
        showToast('error', error.message);
    }
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

// ==========================================
// PRENOTAZIONE UNIFICATA (tutte le cattedre)
// ==========================================

let isMcmCattedra = false;

// Apre il modal unificato
function openPrenotazioneUnificata() {
    // Reset form
    const cattedraEl = document.getElementById('prenotazioneCattedra');
    const dataEl = document.getElementById('prenotazioneData');
    const quantitaEl = document.getElementById('prenotazioneQuantita');
    const risultatiEl = document.getElementById('risultatiPrenotazione');
    const btnSalvaEl = document.getElementById('btnSalvaPrenotazioni');
    const infoMcmEl = document.getElementById('infoMcm');
    const infoGiornataEl = document.getElementById('infoGiornata');
    const colOreEl = document.getElementById('colOreHeader');
    const colOreNonDispEl = document.getElementById('colOreHeaderNonDisp');
    const headerEl = document.getElementById('modalPrenotazioneHeader');
    const sezioneDateMultipleEl = document.getElementById('sezioneDateMultiple');
    const sezioneDataSingolaEl = document.getElementById('sezioneDataSingola');
    const infoCiedEl = document.getElementById('infoCiedPratico');
    
    const sezioneQuantitaEl = document.getElementById('sezioneQuantitaGlobale');
    
    if (cattedraEl) cattedraEl.value = '';
    if (dataEl) dataEl.value = '';
    if (quantitaEl) quantitaEl.value = '15';
    if (risultatiEl) risultatiEl.style.display = 'none';
    if (btnSalvaEl) btnSalvaEl.style.display = 'none';
    if (infoMcmEl) infoMcmEl.style.display = 'none';
    if (infoGiornataEl) infoGiornataEl.style.display = 'none';
    if (colOreEl) colOreEl.style.display = 'none';
    if (colOreNonDispEl) colOreNonDispEl.style.display = 'none';
    if (sezioneDateMultipleEl) sezioneDateMultipleEl.style.display = 'none';
    if (sezioneDataSingolaEl) sezioneDataSingolaEl.style.display = 'block';
    if (sezioneQuantitaEl) sezioneQuantitaEl.style.display = 'block';
    if (infoCiedEl) infoCiedEl.style.display = 'none';
    
    // Reset header color
    if (headerEl) {
        headerEl.style.background = 'linear-gradient(135deg, #0a2342, #1a3a5c)';
        headerEl.style.color = 'white';
    }
    
    currentMilitariData = [];
    isMcmCattedra = false;
    
    new bootstrap.Modal(document.getElementById('modalPrenotazioneUnificata')).show();
}

// Quando cambia la data
function onDataChange() {
    const dataInput = document.getElementById('prenotazioneData');
    const infoGiornata = document.getElementById('infoGiornata');
    
    if (dataInput.value && isMcmCattedra) {
        const date = new Date(dataInput.value);
        const dayOfWeek = date.getDay();
        const giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
        
        let ore = 0;
        if (dayOfWeek >= 1 && dayOfWeek <= 4) ore = 8;
        else if (dayOfWeek === 5) ore = 4;
        
        if (ore > 0) {
            infoGiornata.innerHTML = `<i class="fas fa-clock me-1"></i>${giorni[dayOfWeek]}: <strong>${ore} ore</strong>`;
            infoGiornata.className = 'form-text mt-1 text-success';
        } else {
            infoGiornata.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i>${giorni[dayOfWeek]}: Weekend - Non valido`;
            infoGiornata.className = 'form-text mt-1 text-danger';
        }
        infoGiornata.style.display = 'block';
    } else {
        infoGiornata.style.display = 'none';
    }
}

// Cerca militari disponibili
document.getElementById('btnCercaMilitari')?.addEventListener('click', async function() {
    const cattedra = document.getElementById('prenotazioneCattedra').value;
    const isMcm = document.getElementById('prenotazioneCattedra').selectedOptions[0]?.dataset.isMcm === 'true';
    
    if (!cattedra) {
        showToast('warning', 'Seleziona una cattedra');
        return;
    }
    
    // Per MCM, gestisci multi-giorno
    if (isMcm) {
        await cercaMilitariMcmMultiGiorno(cattedra, this);
        return;
    }
    
    // Per cattedre normali
    const data = document.getElementById('prenotazioneData').value;
    const quantita = document.getElementById('prenotazioneQuantita').value;
    
    if (!data) {
        showToast('warning', 'Seleziona una data');
        return;
    }
    if (!quantita || quantita < 1) {
        showToast('warning', 'Specifica il numero di militari');
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ricerca...';
    
    try {
        const result = await apiCall(CONFIG.routes.proponiPrenotazione, {
            method: 'POST',
            body: JSON.stringify({ 
                teatro_id: CONFIG.teatroId, 
                cattedra, 
                data, 
                quantita: parseInt(quantita) 
            })
        });
        
        if (result.success) {
            const isMcm = result.is_mcm;
            const tbodyDisp = document.getElementById('tabellaDisponibili');
            const tbodyNonDisp = document.getElementById('tabellaNonDisponibili');
            
            // Salva dati per export
            currentMilitariData = result.militari_disponibili || [];
            
            // Mostra/nascondi colonne ore
            document.getElementById('colOreHeader').style.display = isMcm ? 'table-cell' : 'none';
            document.getElementById('colOreHeaderNonDisp').style.display = isMcm ? 'table-cell' : 'none';
            
            // Popola tabella disponibili
            if (result.militari_disponibili?.length === 0) {
                tbodyDisp.innerHTML = `
                    <tr>
                        <td colspan="${isMcm ? 6 : 5}" class="text-center text-muted py-4">
                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                            Nessun militare disponibile per questa data
                        </td>
                    </tr>
                `;
            } else {
                tbodyDisp.innerHTML = result.militari_disponibili.map(m => {
                    let oreCol = '';
                    if (isMcm) {
                        const progressColor = m.percentuale_mcm >= 75 ? 'bg-success' : 
                                             m.percentuale_mcm >= 50 ? 'bg-warning' : 'bg-danger';
                        oreCol = `
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary">${m.ore_svolte}/40</span>
                                    <div class="progress flex-grow-1" style="height: 16px; min-width: 60px;">
                                        <div class="progress-bar ${progressColor}" style="width: ${m.percentuale_mcm}%">
                                            ${m.percentuale_mcm}%
                                        </div>
                                    </div>
                                    <span class="text-muted small">Rim: ${m.ore_rimanenti}h</span>
                                </div>
                            </td>
                        `;
                    }
                    
                    return `
                        <tr>
                            <td><input type="checkbox" class="form-check-input militare-checkbox" value="${m.id}" checked></td>
                            <td><strong>${m.grado}</strong></td>
                            <td>${m.cognome}</td>
                            <td>${m.nome}</td>
                            <td><span class="badge bg-secondary">${formatStatoCattedra(m.stato_cattedra)}</span></td>
                            ${oreCol}
                        </tr>
                    `;
                }).join('');
            }
            
            // Popola tabella non disponibili
            if (result.militari_non_disponibili?.length === 0) {
                tbodyNonDisp.innerHTML = `
                    <tr>
                        <td colspan="${isMcm ? 5 : 4}" class="text-center text-muted py-3">
                            Tutti i militari sono disponibili
                        </td>
                    </tr>
                `;
            } else {
                tbodyNonDisp.innerHTML = result.militari_non_disponibili.map(m => {
                    let oreCol = '';
                    if (isMcm && m.ore_svolte !== undefined) {
                        oreCol = `<td><span class="badge bg-secondary">${m.ore_svolte}/40</span> (Rim: ${m.ore_rimanenti}h)</td>`;
                    } else if (isMcm) {
                        oreCol = `<td>-</td>`;
                    }
                    
                    // Badge colorato per motivo
                    let motivoClass = 'bg-secondary';
                    if (m.codice_impegno) {
                        motivoClass = 'bg-warning text-dark';
                    } else if (m.motivo_non_disponibile?.includes('valida') || m.motivo_non_disponibile?.includes('Idoneità')) {
                        motivoClass = 'bg-danger';
                    } else if (m.motivo_non_disponibile?.includes('completato') || m.motivo_non_disponibile?.includes('già')) {
                        motivoClass = 'bg-info';
                    }
                    
                    return `
                        <tr class="table-light">
                            <td>${m.grado}</td>
                            <td>${m.cognome}</td>
                            <td>${m.nome}</td>
                            <td>
                                <span class="badge ${motivoClass}">
                                    ${m.codice_impegno ? `<i class="fas fa-calendar-day me-1"></i>${m.codice_impegno}: ` : ''}
                                    ${m.motivo_non_disponibile}
                                </span>
                            </td>
                            ${oreCol}
                        </tr>
                    `;
                }).join('');
            }
            
            // Aggiorna contatori
            document.getElementById('countDisponibili').textContent = result.totale_proposti || 0;
            document.getElementById('countNonDisponibili').textContent = result.totale_non_disponibili || 0;
            
            // Mostra risultati e pulsanti
            document.getElementById('risultatiPrenotazione').style.display = 'block';
            document.getElementById('risultatiMcmMultiGiorno').style.display = 'none';
            
            const btnSalva = document.getElementById('btnSalvaPrenotazioni');
            const btnExport = document.getElementById('btnExportProposta');
            
            if (btnSalva) btnSalva.style.display = (result.militari_disponibili?.length > 0) ? 'inline-block' : 'none';
            if (btnExport) btnExport.style.display = (result.militari_disponibili?.length > 0) ? 'inline-block' : 'none';
            
            // Scroll ai risultati
            document.getElementById('risultatiPrenotazione').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    } catch (error) {
        showToast('error', error.message);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-search me-1"></i>Cerca Militari Disponibili';
    }
});

// Formatta stato cattedra
function formatStatoCattedra(stato) {
    const labels = {
        'non_presente': 'Non presente',
        'scaduto': 'Scaduto',
        'valido': 'Valido',
        'in_scadenza': 'In scadenza'
    };
    return labels[stato] || stato;
}

// Salva prenotazioni
document.getElementById('btnSalvaPrenotazioni')?.addEventListener('click', async function() {
    const cattedra = document.getElementById('prenotazioneCattedra').value;
    const isMcm = document.getElementById('prenotazioneCattedra').selectedOptions[0]?.dataset.isMcm === 'true';
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
    
    try {
        if (isMcm) {
            // Per MCM, salva per ogni giorno usando i checkbox dell'accordion
            const accordionItems = document.querySelectorAll('#accordionMcmGiorni .accordion-collapse');
            let totalePrenotazioni = 0;
            let errori = [];
            
            for (const item of accordionItems) {
                const data = item.dataset.data;
                if (!data) continue;
                
                // Prendi i militari selezionati per questo specifico giorno
                const ids = Array.from(item.querySelectorAll('.mcm-militare-checkbox:checked'))
                    .map(cb => parseInt(cb.value));
                
                if (ids.length === 0) continue;
                
                try {
                    const result = await apiCall(CONFIG.routes.salvaPrenotazione, {
                        method: 'POST',
                        body: JSON.stringify({
                            teatro_id: CONFIG.teatroId,
                            cattedra: cattedra,
                            data: data,
                            militari_ids: ids
                        })
                    });
                    
                    if (result.success) {
                        totalePrenotazioni += ids.length;
                    }
                } catch (e) {
                    errori.push(`${data}: ${e.message}`);
                }
            }
            
            if (totalePrenotazioni > 0) {
                showToast('success', `${totalePrenotazioni} prenotazioni MCM create`);
                bootstrap.Modal.getInstance(document.getElementById('modalPrenotazioneUnificata')).hide();
                setTimeout(() => location.reload(), 1000);
            } else if (errori.length > 0) {
                showToast('error', errori.join(', '));
            } else {
                showToast('warning', 'Nessuna prenotazione creata. Seleziona almeno un militare.');
            }
        } else {
            // Cattedra normale (singola data)
            const ids = Array.from(document.querySelectorAll('.militare-checkbox:checked')).map(cb => parseInt(cb.value));
            
            if (ids.length === 0) {
                showToast('warning', 'Seleziona almeno un militare');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check me-1"></i>Conferma Prenotazione';
                return;
            }
            
            const result = await apiCall(CONFIG.routes.salvaPrenotazione, {
                method: 'POST',
                body: JSON.stringify({
                    teatro_id: CONFIG.teatroId,
                    cattedra: cattedra,
                    data: document.getElementById('prenotazioneData').value,
                    militari_ids: ids
                })
            });
            
            if (result.success) {
                showToast('success', result.message);
                bootstrap.Modal.getInstance(document.getElementById('modalPrenotazioneUnificata')).hide();
                setTimeout(() => location.reload(), 1000);
            }
        }
    } catch (error) {
        showToast('error', error.message);
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check me-1"></i>Conferma Prenotazione';
    }
});

// Select All disponibili
document.getElementById('selectAllDisponibili')?.addEventListener('change', function() {
    document.querySelectorAll('.militare-checkbox').forEach(cb => cb.checked = this.checked);
});

// ==========================================
// MCM - RICERCA MULTI-GIORNO
// ==========================================

// Cerca militari per ogni giorno MCM
async function cercaMilitariMcmMultiGiorno(cattedra, btn) {
    const mcmRows = document.querySelectorAll('#mcmDateContainer .mcm-date-row');
    const giorniMcm = [];
    
    mcmRows.forEach(row => {
        const dateInput = row.querySelector('.mcm-date-input');
        const quantitaInput = row.querySelector('.mcm-quantita-input');
        if (dateInput.value) {
            giorniMcm.push({
                data: dateInput.value,
                quantita: parseInt(quantitaInput.value) || 15
            });
        }
    });
    
    if (giorniMcm.length === 0) {
        showToast('warning', 'Seleziona almeno una data per MCM');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Ricerca...';
    
    // Nascondi risultati normali, mostra container MCM
    document.getElementById('risultatiPrenotazione').style.display = 'none';
    document.getElementById('risultatiMcmMultiGiorno').style.display = 'block';
    
    const accordion = document.getElementById('accordionMcmGiorni');
    accordion.innerHTML = '';
    
    try {
        // Carica militari per ogni giorno
        for (let i = 0; i < giorniMcm.length; i++) {
            const giorno = giorniMcm[i];
            
            try {
                const result = await apiCall(CONFIG.routes.proponiPrenotazione, {
                    method: 'POST',
                    body: JSON.stringify({ 
                        teatro_id: CONFIG.teatroId, 
                        cattedra: cattedra, 
                        data: giorno.data, 
                        quantita: giorno.quantita 
                    })
                });
                
                if (result.success) {
                    // Formatta data per visualizzazione
                    const dataObj = new Date(giorno.data);
                    const giornoSettimana = dataObj.toLocaleDateString('it-IT', { weekday: 'long' });
                    const dataFormattata = dataObj.toLocaleDateString('it-IT');
                    const ore = calcolaOrePerData(giorno.data);
                    
                    const disponibili = result.militari_disponibili || [];
                    const nonDisponibili = result.militari_non_disponibili || [];
                    
                    // Crea accordion item
                    const accordionItem = document.createElement('div');
                    accordionItem.className = 'accordion-item mcm-giorno-item';
                    accordionItem.innerHTML = `
                        <h2 class="accordion-header">
                            <button class="accordion-button ${i === 0 ? '' : 'collapsed'}" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#mcm-giorno-${i}">
                                <span class="me-auto">
                                    <strong>${dataFormattata}</strong> 
                                    <span class="text-muted">(${giornoSettimana})</span>
                                    <span class="badge bg-warning text-dark ms-2">${ore}h</span>
                                </span>
                                <span class="badge bg-success me-2">${disponibili.length} disponibili</span>
                                <span class="badge bg-danger me-2">${nonDisponibili.length} impegnati</span>
                            </button>
                        </h2>
                        <div id="mcm-giorno-${i}" class="accordion-collapse collapse ${i === 0 ? 'show' : ''}" data-data="${giorno.data}">
                            <div class="accordion-body">
                                <div class="mb-2">
                                    <input type="checkbox" class="form-check-input me-2 select-all-giorno" data-giorno="${i}" checked>
                                    <label class="form-check-label">Seleziona tutti (${disponibili.length})</label>
                                </div>
                                ${disponibili.length > 0 ? `
                                <div class="table-responsive" style="max-height: 250px;">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th width="40"></th>
                                                <th>Grado</th>
                                                <th>Cognome</th>
                                                <th>Nome</th>
                                                <th>Ore MCM</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${disponibili.map(m => `
                                                <tr>
                                                    <td><input type="checkbox" class="form-check-input mcm-militare-checkbox" 
                                                        data-giorno="${i}" data-data="${giorno.data}" value="${m.id}" checked></td>
                                                    <td>${m.grado}</td>
                                                    <td>${m.cognome}</td>
                                                    <td>${m.nome}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <span class="badge bg-secondary">${m.ore_svolte}/40</span>
                                                            <small class="text-muted">Rim: ${m.ore_rimanenti}h</small>
                                                        </div>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                ` : '<div class="text-muted text-center py-3">Nessun militare disponibile</div>'}
                                
                                ${nonDisponibili.length > 0 ? `
                                <hr class="my-2">
                                <details>
                                    <summary class="text-danger cursor-pointer mb-2">
                                        <i class="fas fa-times-circle me-1"></i>${nonDisponibili.length} militari impegnati
                                    </summary>
                                    <div class="table-responsive" style="max-height: 150px;">
                                        <table class="table table-sm mb-0">
                                            <tbody>
                                                ${nonDisponibili.map(m => `
                                                    <tr class="text-muted">
                                                        <td>${m.grado}</td>
                                                        <td>${m.cognome}</td>
                                                        <td>${m.nome}</td>
                                                        <td><span class="badge bg-secondary">${m.motivo_non_disponibile}</span></td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
                                ` : ''}
                            </div>
                        </div>
                    `;
                    
                    accordion.appendChild(accordionItem);
                }
            } catch (e) {
                console.error(`Errore per ${giorno.data}:`, e);
            }
        }
        
        // Aggiungi event listener per "seleziona tutti" per ogni giorno
        document.querySelectorAll('.select-all-giorno').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const giorno = this.dataset.giorno;
                document.querySelectorAll(`.mcm-militare-checkbox[data-giorno="${giorno}"]`)
                    .forEach(cb => cb.checked = this.checked);
            });
        });
        
        // Mostra pulsante salva
        document.getElementById('btnSalvaPrenotazioni').style.display = 'inline-block';
        
    } catch (error) {
        showToast('error', error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search me-1"></i>Cerca Militari Disponibili';
    }
}

// ==========================================
// MCM - GESTIONE DATE MULTIPLE
// ==========================================

function onCattedraChange() {
    const select = document.getElementById('prenotazioneCattedra');
    const selectedOption = select.options[select.selectedIndex];
    isMcmCattedra = selectedOption?.getAttribute('data-is-mcm') === 'true';
    const isCiedPratico = selectedOption?.getAttribute('data-requisito-awareness') === 'true';
    
    // Mostra/nascondi sezioni
    document.getElementById('infoMcm').style.display = isMcmCattedra ? 'block' : 'none';
    document.getElementById('infoCiedPratico').style.display = isCiedPratico ? 'block' : 'none';
    document.getElementById('sezioneDataSingola').style.display = isMcmCattedra ? 'none' : 'block';
    document.getElementById('sezioneDateMultiple').style.display = isMcmCattedra ? 'block' : 'none';
    document.getElementById('sezioneQuantitaGlobale').style.display = isMcmCattedra ? 'none' : 'block';
    
    // Cambia colore header per MCM
    const header = document.getElementById('modalPrenotazioneHeader');
    if (isMcmCattedra) {
        header.style.background = 'linear-gradient(135deg, #d4af37, #f4d03f)';
        header.style.color = '#0a2342';
        resetMcmDateRows();
    } else {
        header.style.background = 'linear-gradient(135deg, #0a2342, #1a3a5c)';
        header.style.color = 'white';
    }
    
    // Mostra/nascondi colonne ore
    document.getElementById('colOreHeader').style.display = isMcmCattedra ? 'table-cell' : 'none';
    document.getElementById('colOreHeaderNonDisp').style.display = isMcmCattedra ? 'table-cell' : 'none';
    
    onDataChange();
}

// Reset righe date MCM
function resetMcmDateRows() {
    const container = document.getElementById('mcmDateContainer');
    container.innerHTML = `
        <div class="mcm-date-row mb-2" data-date-row="0">
            <div class="d-flex align-items-center gap-2">
                <div class="mcm-date-input-wrapper flex-grow-1">
                    <input type="date" class="form-control mcm-date-input" onchange="onMcmDateChange(this)">
                </div>
                <div class="mcm-ore-display">
                    <span class="badge bg-secondary mcm-ore-badge">0h</span>
                </div>
                <div class="mcm-quantita-wrapper">
                    <input type="number" class="form-control form-control-sm mcm-quantita-input" 
                           value="15" min="1" max="50" style="width: 70px;" 
                           placeholder="N." title="Numero militari">
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-date" 
                        onclick="removeMcmDateRow(this)" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    mcmDateRowCount = 1;
    updateMcmOreRiepilogo();
}

// Aggiunge riga data MCM
function addMcmDateRow() {
    const container = document.getElementById('mcmDateContainer');
    const rowIndex = mcmDateRowCount++;
    
    const row = document.createElement('div');
    row.className = 'mcm-date-row mb-2';
    row.setAttribute('data-date-row', rowIndex);
    row.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <div class="mcm-date-input-wrapper flex-grow-1">
                <input type="date" class="form-control mcm-date-input" onchange="onMcmDateChange(this)">
            </div>
            <div class="mcm-ore-display">
                <span class="badge bg-secondary mcm-ore-badge">0h</span>
            </div>
            <div class="mcm-quantita-wrapper">
                <input type="number" class="form-control form-control-sm mcm-quantita-input" 
                       value="15" min="1" max="50" style="width: 70px;" 
                       placeholder="N." title="Numero militari">
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm btn-remove-date" 
                    onclick="removeMcmDateRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
    updateMcmRemoveButtons();
}

// Rimuove riga data MCM
function removeMcmDateRow(button) {
    const row = button.closest('.mcm-date-row');
    row.remove();
    updateMcmRemoveButtons();
    updateMcmOreRiepilogo();
}

// Aggiorna visibilità pulsanti rimuovi
function updateMcmRemoveButtons() {
    const rows = document.querySelectorAll('#mcmDateContainer .mcm-date-row');
    rows.forEach(row => {
        const removeBtn = row.querySelector('.btn-remove-date');
        if (removeBtn) {
            removeBtn.style.display = rows.length > 1 ? 'flex' : 'none';
        }
    });
}

// Quando cambia una data MCM
function onMcmDateChange(input) {
    const row = input.closest('.mcm-date-row');
    const oreSpan = row.querySelector('.mcm-ore-badge');
    
    if (input.value) {
        const ore = calcolaOrePerData(input.value);
        if (ore === 0) {
            oreSpan.textContent = 'WE';
            oreSpan.className = 'badge bg-danger mcm-ore-badge';
        } else {
            oreSpan.textContent = `${ore}h`;
            oreSpan.className = 'badge bg-success mcm-ore-badge';
        }
    } else {
        oreSpan.textContent = '0h';
        oreSpan.className = 'badge bg-secondary mcm-ore-badge';
    }
    
    updateMcmOreRiepilogo();
}

// Calcola ore per data
function calcolaOrePerData(dateString) {
    const date = new Date(dateString);
    const dayOfWeek = date.getDay();
    if (dayOfWeek >= 1 && dayOfWeek <= 4) return 8;
    if (dayOfWeek === 5) return 4;
    return 0;
}

// Aggiorna riepilogo ore MCM (funzione mantenuta per compatibilità)
function updateMcmOreRiepilogo() {
    // Rimosso il riepilogo ore
}

// Ottiene date MCM valide selezionate
function getMcmDateSelezionate() {
    const inputs = document.querySelectorAll('.mcm-date-input');
    const date = [];
    
    inputs.forEach(input => {
        if (input.value && calcolaOrePerData(input.value) > 0) {
            date.push(input.value);
        }
    });
    
    return date;
}

// ==========================================
// PRENOTAZIONI ATTIVE - MODAL E GESTIONE
// ==========================================

// Apre modal prenotazioni attive
async function openModalPrenotazioniAttive() {
    document.getElementById('loadingPrenotazioni').style.display = 'block';
    document.getElementById('contentPrenotazioni').style.display = 'none';
    
    new bootstrap.Modal(document.getElementById('modalPrenotazioniAttive')).show();
    
    await caricaPrenotazioniAttive();
}

// Carica prenotazioni attive
async function caricaPrenotazioniAttive() {
    try {
        const result = await apiCall(CONFIG.routes.prenotazioniAttive + `?teatro_id=${CONFIG.teatroId}`);
        
        if (result.success) {
            prenotazioniAttiveData = result.prenotazioni || [];
            
            document.getElementById('loadingPrenotazioni').style.display = 'none';
            document.getElementById('contentPrenotazioni').style.display = 'block';
            
            if (prenotazioniAttiveData.length === 0) {
                document.getElementById('alertNoPrenotazioni').style.display = 'block';
                document.getElementById('accordionPrenotazioni').innerHTML = '';
            } else {
                document.getElementById('alertNoPrenotazioni').style.display = 'none';
                renderPrenotazioniAttive(prenotazioniAttiveData);
            }
            
            // Popola filtro cattedra
            const filtro = document.getElementById('filtroPrenotazioneCattedra');
            filtro.innerHTML = '<option value="">-- Tutte le cattedre --</option>';
            prenotazioniAttiveData.forEach(gruppo => {
                filtro.innerHTML += `<option value="${gruppo.cattedra}">${gruppo.label} (${gruppo.count})</option>`;
            });
            
            // Aggiorna badge FAB (numero cattedre, non persone)
            updateFabBadge(result.totale_cattedre || 0);
        }
    } catch (error) {
        showToast('error', error.message);
        document.getElementById('loadingPrenotazioni').style.display = 'none';
    }
}

// Render prenotazioni nell'accordion
function renderPrenotazioniAttive(prenotazioni) {
    const accordion = document.getElementById('accordionPrenotazioni');
    accordion.innerHTML = '';
    
    prenotazioni.forEach((gruppo, idx) => {
        const isFirst = idx === 0;
        const accordionItem = document.createElement('div');
        accordionItem.className = 'accordion-item';
        accordionItem.setAttribute('data-cattedra', gruppo.cattedra);
        
        // Prepara la lista degli ID delle prenotazioni per questa cattedra
        const prenotazioneIds = gruppo.militari.map(m => m.id);
        
        // Trova la data di prenotazione
        const dataPrenotazione = gruppo.militari.length > 0 ? gruppo.militari[0].data_prenotazione : '-';
        
        // Titolo: "B.A.M. 29/01/2026 (2)"
        accordionItem.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button ${isFirst ? '' : 'collapsed'}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${idx}">
                    <span>${gruppo.label} ${dataPrenotazione} (${gruppo.count})</span>
                </button>
            </h2>
            <div id="collapse-${idx}" class="accordion-collapse collapse ${isFirst ? 'show' : ''}">
                <div class="accordion-body">
                    <!-- Azioni per tutta la cattedra -->
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                            onclick="apriModificaDataCattedra('${gruppo.cattedra}', '${gruppo.label}', ${gruppo.count}, '${prenotazioneIds.join(',')}')">
                            <i class="fas fa-calendar-alt me-1"></i>Modifica Data
                        </button>
                        <button type="button" class="btn btn-sm btn-success" 
                            onclick="confermaTuttiDiretto('${prenotazioneIds.join(',')}')">
                            Conferma Tutti
                        </button>
                    </div>
                    
                    <!-- Lista militari -->
                    <div class="list-group list-group-flush">
                        ${gruppo.militari.map(m => `
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0" data-prenotazione-id="${m.id}">
                                <span>${m.grado} ${m.cognome} ${m.nome}</span>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" onclick="confermaSingolo(${m.id})" title="Conferma">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="rimuoviPrenotazione(${m.id})" title="Rimuovi">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        accordion.appendChild(accordionItem);
    });
}

// Filtra prenotazioni per cattedra
function filtraPrenotazioniPerCattedra() {
    const filtro = document.getElementById('filtroPrenotazioneCattedra').value;
    const items = document.querySelectorAll('#accordionPrenotazioni .accordion-item');
    
    items.forEach(item => {
        if (!filtro || item.getAttribute('data-cattedra') === filtro) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Apre modal per modificare la data di tutta la cattedra
function apriModificaDataCattedra(cattedra, cattedraLabel, count, prenotazioneIds) {
    document.getElementById('modificaCattedraKey').value = cattedra;
    document.getElementById('modificaCattedraKey').dataset.ids = prenotazioneIds;
    document.getElementById('modificaCattedraLabel').textContent = cattedraLabel;
    document.getElementById('modificaMilitariCount').textContent = `${count} militari saranno aggiornati`;
    document.getElementById('modificaNuovaData').value = '';
    
    // Nascondi eventuali alert precedenti
    const alertConflitti = document.getElementById('alertConflittiData');
    if (alertConflitti) alertConflitti.style.display = 'none';
    
    new bootstrap.Modal(document.getElementById('modalModificaDataCattedra')).show();
}

// Salva la modifica della data per tutta la cattedra (con verifica disponibilità)
async function salvaModificaDataCattedra(forzaSalvataggio = false) {
    const nuovaData = document.getElementById('modificaNuovaData').value;
    const prenotazioneIds = document.getElementById('modificaCattedraKey').dataset.ids.split(',').map(id => parseInt(id));
    const cattedra = document.getElementById('modificaCattedraKey').value;
    
    if (!nuovaData) {
        showToast('warning', 'Inserisci la nuova data');
        return;
    }
    
    // Prima verifica disponibilità militari (se non forzato)
    if (!forzaSalvataggio) {
        try {
            const verifica = await apiCall(CONFIG.routes.verificaDisponibilita, {
                method: 'POST',
                body: JSON.stringify({
                    prenotazione_ids: prenotazioneIds,
                    data: nuovaData
                })
            });
            
            if (verifica.success && verifica.conflitti && verifica.conflitti.length > 0) {
                // Mostra i conflitti
                mostraConflittiDisponibilita(verifica.conflitti, nuovaData);
                return;
            }
        } catch (error) {
            // Se la verifica fallisce, procedi comunque (l'endpoint potrebbe non esistere)
            console.warn('Verifica disponibilità non riuscita:', error);
        }
    }
    
    // Procedi con il salvataggio
    try {
        const result = await apiCall(CONFIG.routes.modificaPrenotazioneMultipla, {
            method: 'POST',
            body: JSON.stringify({
                prenotazione_ids: prenotazioneIds,
                data_prenotazione: nuovaData
            })
        });
        
        if (result.success) {
            showToast('success', result.message || 'Date aggiornate con successo');
            bootstrap.Modal.getInstance(document.getElementById('modalModificaDataCattedra')).hide();
            await caricaPrenotazioniAttive();
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// Mostra alert con conflitti di disponibilità
function mostraConflittiDisponibilita(conflitti, nuovaData) {
    let alertHtml = document.getElementById('alertConflittiData');
    
    if (!alertHtml) {
        alertHtml = document.createElement('div');
        alertHtml.id = 'alertConflittiData';
        alertHtml.className = 'alert alert-warning mt-3';
        document.getElementById('modificaNuovaData').parentNode.after(alertHtml);
    }
    
    alertHtml.innerHTML = `
        <strong><i class="fas fa-exclamation-triangle me-2"></i>Attenzione!</strong>
        <p class="mb-2">I seguenti militari risultano impegnati nella data selezionata:</p>
        <ul class="mb-2">
            ${conflitti.map(c => `<li>${c.grado} ${c.cognome} ${c.nome}: <em>${c.motivo}</em></li>`).join('')}
        </ul>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-warning" onclick="salvaModificaDataCattedra(true)">
                Procedi comunque
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('alertConflittiData').style.display='none'">
                Annulla
            </button>
        </div>
    `;
    alertHtml.style.display = 'block';
}

// Conferma tutti i militari direttamente (senza modal)
async function confermaTuttiDiretto(prenotazioneIdsStr) {
    const prenotazioneIds = prenotazioneIdsStr.split(',').map(id => parseInt(id));
    const count = prenotazioneIds.length;
    
    // Mostra conferma elegante
    if (!await confermaAzione(`Confermare ${count} prenotazion${count === 1 ? 'e' : 'i'}?`, 'Conferma')) {
        return;
    }
    
    try {
        const result = await apiCall(CONFIG.routes.confermaPrenotazioneMultipla, {
            method: 'POST',
            body: JSON.stringify({
                prenotazione_ids: prenotazioneIds
            })
        });
        
        if (result.success) {
            showToast('success', result.message || 'Prenotazioni confermate');
            await caricaPrenotazioniAttive();
            setTimeout(() => location.reload(), 1500);
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// Conferma singolo militare
async function confermaSingolo(prenotazioneId) {
    try {
        const result = await apiCall(CONFIG.routes.confermaPrenotazioneMultipla, {
            method: 'POST',
            body: JSON.stringify({
                prenotazione_ids: [prenotazioneId]
            })
        });
        
        if (result.success) {
            showToast('success', 'Prenotazione confermata');
            await caricaPrenotazioniAttive();
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// Rimuove prenotazione con conferma elegante
async function rimuoviPrenotazione(prenotazioneId) {
    if (!await confermaAzione('Rimuovere questa prenotazione?', 'Rimuovi')) {
        return;
    }
    
    try {
        const result = await apiCall(CONFIG.routes.annullaPrenotazione, {
            method: 'POST',
            body: JSON.stringify({ prenotazione_id: prenotazioneId })
        });
        
        if (result.success) {
            showToast('success', 'Prenotazione rimossa');
            await caricaPrenotazioniAttive();
        }
    } catch (error) {
        showToast('error', error.message);
    }
}

// La funzione confermaAzione() è ora disponibile globalmente via SUGECO.Confirm
// Usa: await confermaAzione('messaggio', 'testo bottone')
// Oppure: await SUGECO.Confirm.show({ message: '...', confirmText: '...' })

// Annulla singola prenotazione (alias per retrocompatibilità)
async function annullaPrenotazione(prenotazioneId) {
    await rimuoviPrenotazione(prenotazioneId);
}

// Export prenotazioni Excel
function exportPrenotazioniExcel() {
    const cattedra = document.getElementById('filtroPrenotazioneCattedra').value;
    let url = CONFIG.routes.exportPrenotazioniExcel + `?teatro_id=${CONFIG.teatroId}`;
    if (cattedra) url += `&cattedra=${cattedra}`;
    window.location.href = url;
}

// Aggiorna badge FAB
function updateFabBadge(count) {
    const badge = document.getElementById('fabBadgePrenotazioni');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

// Carica conteggio prenotazioni all'avvio
document.addEventListener('DOMContentLoaded', async function() {
    if (CONFIG.teatroId) {
        try {
            const result = await apiCall(CONFIG.routes.prenotazioniAttive + `?teatro_id=${CONFIG.teatroId}`);
            if (result.success) {
                updateFabBadge(result.totale_cattedre || 0);
            }
        } catch (e) {}
    }
});

</script>
@endpush
