@extends('layouts.app')

@section('title', 'Dashboard - C2MS')

@section('styles')
<!-- Stili specifici per la dashboard sono ora in dashboard.css -->
<link href="{{ asset('css/dashboard-modern.css') }}" rel="stylesheet">
<style>
/* Override finale per titolo dashboard - Forza font-weight 300 con massima specificità */
html body .main-content div.text-center h1.page-title,
html body div.text-center h1.page-title,
body .main-content div h1.page-title,
div.text-center h1.page-title,
.text-center h1.page-title,
h1.page-title[class] {
    font-family: 'Oswald', sans-serif !important;
    font-size: 2.5rem !important;
    font-weight: 300 !important;
    color: #0A2342 !important;
    margin-bottom: 0 !important;
    text-align: center !important;
    letter-spacing: 0.5px !important;
}

/* Forza rimozione font-weight 600 */
h1.page-title {
    font-weight: 300 !important;
}
</style>

<script>
// Forza font-weight 300 via JavaScript come ultima risorsa
document.addEventListener('DOMContentLoaded', function() {
    const pageTitle = document.querySelector('h1.page-title');
    if (pageTitle) {
        pageTitle.style.setProperty('font-weight', '300', 'important');
        pageTitle.style.setProperty('font-family', 'Oswald, sans-serif', 'important');
        pageTitle.style.setProperty('font-size', '2.5rem', 'important');
        pageTitle.style.setProperty('color', '#0A2342', 'important');
        pageTitle.style.setProperty('letter-spacing', '0.5px', 'important');
    }
});
</script>
@endsection

@section('content')
<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Dashboard</h1>
</div>

@php
    use Carbon\Carbon;
    // Calcolo delle statistiche per i widget
    $totalMilitari = \App\Models\Militare::count();
    
    // Presenze di oggi
    $presentiOggi = \App\Models\Militare::whereHas('presenzaOggi', function($query) {
        $query->where('stato', 'Presente');
    })->count();
    
    $assentiOggi = $totalMilitari - $presentiOggi;
    $percentualePresenti = $totalMilitari > 0 ? round(($presentiOggi / $totalMilitari) * 100) : 0;
    
    // Certificati lavoratori
    $certificatiValidi = \App\Models\CertificatiLavoratori::where('data_scadenza', '>', now())->count();
    $certificatiInScadenza = \App\Models\CertificatiLavoratori::whereBetween('data_scadenza', [now(), now()->addDays(30)])->count();
    $certificatiScaduti = \App\Models\CertificatiLavoratori::where('data_scadenza', '<=', now())->count();
    $totaleCertificati = $certificatiValidi + $certificatiInScadenza + $certificatiScaduti;
    
    // Idoneità
    $idoneitaValide = \App\Models\Idoneita::where('data_scadenza', '>', now())->count();
    $idoneitaInScadenza = \App\Models\Idoneita::whereBetween('data_scadenza', [now(), now()->addDays(30)])->count();
    $idoneitaScadute = \App\Models\Idoneita::where('data_scadenza', '<=', now())->count();
    $totaleIdoneita = $idoneitaValide + $idoneitaInScadenza + $idoneitaScadute;
    
    // Percentuali per progress bar
    $percCertValidi = $totaleCertificati > 0 ? round(($certificatiValidi / $totaleCertificati) * 100) : 0;
    $percCertInScadenza = $totaleCertificati > 0 ? round(($certificatiInScadenza / $totaleCertificati) * 100) : 0;
    $percCertScaduti = $totaleCertificati > 0 ? round(($certificatiScaduti / $totaleCertificati) * 100) : 0;
    
    $percIdonValide = $totaleIdoneita > 0 ? round(($idoneitaValide / $totaleIdoneita) * 100) : 0;
    $percIdonInScadenza = $totaleIdoneita > 0 ? round(($idoneitaInScadenza / $totaleIdoneita) * 100) : 0;
    $percIdonScadute = $totaleIdoneita > 0 ? round(($idoneitaScadute / $totaleIdoneita) * 100) : 0;
    
    // Dati aggiuntivi per KPI
    $plotoni = \App\Models\Plotone::count();
    $poli = \App\Models\Polo::count();
@endphp

<!-- Ricerca Rapida Professionale -->
<div class="search-section mb-4">
    <div class="search-wrapper">
        <i class="fas fa-search search-icon"></i>
        <input type="text" 
               id="quickSearch" 
               class="search-input-clean" 
               placeholder="Cerca militare per cognome o nome..." 
               data-search-type="dashboard">
    </div>
</div>

<!-- KPI Compatte -->
<div class="compact-kpi-container">
    <div class="compact-kpi-card kpi-primary">
        <div class="compact-kpi-content">
            <div class="compact-kpi-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="compact-kpi-data">
                <div class="compact-kpi-value">{{ number_format($totalMilitari) }}</div>
                <div class="compact-kpi-label">Forza Effettiva</div>
            </div>
        </div>
    </div>
    
    <div class="compact-kpi-card kpi-success">
        <div class="compact-kpi-content">
            <div class="compact-kpi-icon">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="compact-kpi-data">
                <div class="compact-kpi-value">{{ number_format($presentiOggi) }}</div>
                <div class="compact-kpi-label">Presenti Oggi</div>
            </div>
        </div>
        <div class="compact-kpi-trend trend-up">
            <i class="fas fa-arrow-up"></i> {{ $percentualePresenti }}%
        </div>
    </div>
    
    <div class="compact-kpi-card kpi-danger">
        <div class="compact-kpi-content">
            <div class="compact-kpi-icon">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="compact-kpi-data">
                <div class="compact-kpi-value">{{ number_format($assentiOggi) }}</div>
                <div class="compact-kpi-label">Assenti Oggi</div>
            </div>
        </div>
        <div class="compact-kpi-trend trend-down">
            <i class="fas fa-arrow-down"></i> {{ 100 - $percentualePresenti }}%
        </div>
    </div>
</div>

<!-- Grafico a Ciambella e Stato Certificati -->
<div class="row g-4 mb-4 align-items-stretch">
    <!-- Grafico a Ciambella -->
    <div class="col-md-4 d-flex">
        <div class="dashboard-card flex-fill d-flex flex-column">
            <div class="card-header">
                <h5 class="card-title">
                    Presenza Odierna
                </h5>
            </div>
            <div class="card-body">
                <div class="presence-chart-container">
                    <canvas id="presenceChart"></canvas>
                    <div class="doughnut-label">
                        <div class="percentage">{{ $percentualePresenti }}%</div>
                        <div class="label">Presenti</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-around text-center mt-4">
                    <div>
                        <div class="d-flex align-items-center justify-content-center mb-1">
                            <div style="width:12px; height:12px; background-color:#28a745; border-radius:50%; margin-right:5px;"></div>
                            <span class="small">Presenti</span>
                        </div>
                        <div class="fw-bold">{{ number_format($presentiOggi) }}</div>
                    </div>
                    <div>
                        <div class="d-flex align-items-center justify-content-center mb-1">
                            <div style="width:12px; height:12px; background-color:#dc3545; border-radius:50%; margin-right:5px;"></div>
                            <span class="small">Assenti</span>
                        </div>
                        <div class="fw-bold">{{ number_format($assentiOggi) }}</div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <div class="row row-cols-2 g-2">
                        <div class="col">
                            <a href="{{ url('/militare?presenza=Presente') }}" class="btn btn-sm btn-outline-success w-100">
                                <i class="fas fa-user-check me-1"></i> Presenti
                            </a>
                        </div>
                        <div class="col">
                            <a href="{{ url('/militare?presenza=Assente') }}" class="btn btn-sm btn-outline-danger w-100">
                                <i class="fas fa-user-slash me-1"></i> Assenti
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stato Certificati e Idoneità -->
    <div class="col-md-8">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">
                    Stato Certificati e Idoneità
                </h5>
            </div>
            <div class="card-body">
                <div class="cert-content-expanded">
                    <div class="row">
                        <!-- Certificati Lavoratori -->
                        <div class="col-md-6 border-end">
                            <h6 class="mb-3 fw-bold">
                                <i class="fas fa-file-alt text-primary me-2"></i>Certificati Lavoratori
                            </h6>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>Validi</span>
                                <span class="text-success fw-bold">{{ number_format($certificatiValidi) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>In scadenza</span>
                                <span class="text-warning fw-bold">{{ number_format($certificatiInScadenza) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>Scaduti</span>
                                <span class="text-danger fw-bold">{{ number_format($certificatiScaduti) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Totale</span>
                                <span>{{ number_format($totaleCertificati) }}</span>
                            </div>
                            <div class="cert-progress">
                                <div class="d-flex h-100">
                                    <div class="cert-progress-bar progress-valid" style="width: {{ $percCertValidi }}%;"></div>
                                    <div class="cert-progress-bar progress-expiring" style="width: {{ $percCertInScadenza }}%;"></div>
                                    <div class="cert-progress-bar progress-expired" style="width: {{ $percCertScaduti }}%;"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Idoneità -->
                        <div class="col-md-6">
                            <h6 class="mb-3 fw-bold">
                                <i class="fas fa-heartbeat text-danger me-2"></i>Idoneità
                            </h6>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>Valide</span>
                                <span class="text-success fw-bold">{{ number_format($idoneitaValide) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>In scadenza</span>
                                <span class="text-warning fw-bold">{{ number_format($idoneitaInScadenza) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between">
                                <span>Scadute</span>
                                <span class="text-danger fw-bold">{{ number_format($idoneitaScadute) }}</span>
                            </div>
                            <div class="cert-stats-row d-flex justify-content-between fw-bold border-top pt-2">
                                <span>Totale</span>
                                <span>{{ number_format($totaleIdoneita) }}</span>
                            </div>
                            <div class="cert-progress">
                                <div class="d-flex h-100">
                                    <div class="cert-progress-bar progress-valid" style="width: {{ $percIdonValide }}%;"></div>
                                    <div class="cert-progress-bar progress-expiring" style="width: {{ $percIdonInScadenza }}%;"></div>
                                    <div class="cert-progress-bar progress-expired" style="width: {{ $percIdonScadute }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sezione Azioni Compatta -->
                <div class="cert-actions-section">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <a href="{{ route('certificati.corsi_lavoratori') }}" class="btn btn-primary btn-sm w-100 cert-action-btn">
                                <i class="fas fa-file-alt me-1"></i>
                                <span>Gestisci Certificati</span>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="{{ route('certificati.idoneita') }}" class="btn btn-success btn-sm w-100 cert-action-btn">
                                <i class="fas fa-heartbeat me-1"></i>
                                <span>Gestisci Idoneità</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avvisi Urgenti -->
<div class="row g-4 mb-4">
    <div class="col-md-12">
        <div class="alerts-card">
            <div class="card-header-clean">
                <h5 class="card-title-clean">Avvisi e Priorità</h5>
                <p class="card-subtitle-clean">Situazioni che richiedono attenzione immediata</p>
            </div>
            <div class="card-body p-0">
                @if($certificatiScaduti > 0 || $idoneitaScadute > 0)
                    <div class="alert-item critical">
                        <div class="alert-content">
                            <div class="alert-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div>
                                <div class="alert-message">Documentazione Scaduta</div>
                                <div class="alert-detail">
                                    {{ $certificatiScaduti }} certificati e {{ $idoneitaScadute }} idoneità da rinnovare urgentemente
                                </div>
                            </div>
                        </div>
                        <div class="alert-action">
                            <a href="{{ route('certificati.corsi_lavoratori') }}?scaduti=1" class="btn btn-sm btn-outline-danger">
                                Gestisci
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($certificatiInScadenza > 0)
                    <div class="alert-item warning">
                        <div class="alert-content">
                            <div class="alert-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="alert-message">Certificati in Scadenza</div>
                                <div class="alert-detail">
                                    {{ $certificatiInScadenza }} certificati scadranno nei prossimi 30 giorni
                                </div>
                            </div>
                        </div>
                        <div class="alert-action">
                            <a href="{{ route('certificati.corsi_lavoratori') }}?in_scadenza=1" class="btn btn-sm btn-outline-warning">
                                Visualizza
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($idoneitaInScadenza > 0)
                    <div class="alert-item warning">
                        <div class="alert-content">
                            <div class="alert-icon">
                                <i class="fas fa-heartbeat"></i>
                            </div>
                            <div>
                                <div class="alert-message">Idoneità in Scadenza</div>
                                <div class="alert-detail">
                                    {{ $idoneitaInScadenza }} idoneità scadranno nei prossimi 30 giorni
                                </div>
                            </div>
                        </div>
                        <div class="alert-action">
                            <a href="{{ route('certificati.idoneita') }}?in_scadenza=1" class="btn btn-sm btn-outline-warning">
                                Visualizza
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($assentiOggi > ($totalMilitari * 0.2) && $totalMilitari > 0)
                    <div class="alert-item info">
                        <div class="alert-content">
                            <div class="alert-icon">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div>
                                <div class="alert-message">Alto Tasso di Assenza</div>
                                <div class="alert-detail">
                                    {{ $assentiOggi }} militari assenti oggi ({{ 100 - $percentualePresenti }}% della forza)
                                </div>
                            </div>
                        </div>
                        <div class="alert-action">
                            <a href="{{ url('/militare?presenza=Assente') }}" class="btn btn-sm btn-outline-primary">
                                Dettagli
                            </a>
                        </div>
                    </div>
                @endif
                
                @if($certificatiScaduti == 0 && $idoneitaScadute == 0 && $certificatiInScadenza == 0 && $idoneitaInScadenza == 0 && ($assentiOggi <= ($totalMilitari * 0.2) || $totalMilitari == 0))
                    <div class="alert-item info">
                        <div class="alert-content">
                            <div class="alert-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div>
                                <div class="alert-message">Nessun avviso urgente</div>
                                <div class="alert-detail">
                                    Tutto regolare al momento
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Struttura Organizzativa -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="card-title">
                    Struttura Organizzativa
                </h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="p-3 rounded text-center" style="background-color: rgba(10, 35, 66, 0.05);">
                            <h3 class="mb-1">{{ \App\Models\Plotone::count() }}</h3>
                            <p class="mb-0 text-muted">Plotoni</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="p-3 rounded text-center" style="background-color: rgba(191, 157, 94, 0.1);">
                            <h3 class="mb-1">{{ \App\Models\Polo::count() }}</h3>
                            <p class="mb-0 text-muted">Poli</p>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold mb-3">
                            Plotoni
                        </h6>
                        <div class="org-item-container">
                            @foreach(\App\Models\Plotone::withCount('militari')->orderByDesc('militari_count')->take(5)->get() as $plotone)
                                <a href="{{ url('/militare?plotone_id=' . $plotone->id) }}" class="org-item">
                                    <div class="org-name">
                                        {{ $plotone->nome }}
                                    </div>
                                    <div class="org-count">{{ $plotone->militari_count }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <h6 class="fw-bold mb-3">
                            Poli
                        </h6>
                        <div class="org-item-container">
                            @foreach(\App\Models\Polo::withCount('militari')->orderByDesc('militari_count')->take(5)->get() as $polo)
                                <a href="{{ url('/militare?polo_id=' . $polo->id) }}" class="org-item">
                                    <div class="org-name">
                                        {{ $polo->nome }}
                                    </div>
                                    <div class="org-count">{{ $polo->militari_count }}</div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center mt-2">
                    <a href="{{ url('/organigramma') }}" class="btn btn-primary">
                        Visualizza Organigrammi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Accesso Rapido -->
<div class="row g-4">
    <div class="col-12">
        <div class="dashboard-card quick-access-card">
            <div class="card-header">
                <h5 class="card-title">
                    Accesso Rapido
                </h5>
            </div>
            <div class="card-body">
                <div class="quick-access-grid">
                    <a href="{{ url('/militare?presenza=Presente') }}" class="quick-access-item green">
                        <i class="fas fa-user-check"></i>
                        <span class="quick-access-text">Presenti Oggi</span>
                    </a>
                    <a href="{{ url('/militare?presenza=Assente') }}" class="quick-access-item red">
                        <i class="fas fa-user-slash"></i>
                        <span class="quick-access-text">Assenti Oggi</span>
                    </a>
                    <a href="{{ route('certificati.corsi_lavoratori') }}?in_scadenza=1" class="quick-access-item amber">
                        <i class="fas fa-certificate"></i>
                        <span class="quick-access-text">Certificati in Scadenza</span>
                    </a>
                    <a href="{{ route('certificati.corsi_lavoratori') }}?scaduti=1" class="quick-access-item red">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="quick-access-text">Certificati Scaduti</span>
                    </a>
                    <a href="{{ route('certificati.idoneita') }}?in_scadenza=1" class="quick-access-item amber">
                        <i class="fas fa-heartbeat"></i>
                        <span class="quick-access-text">Idoneità in Scadenza</span>
                    </a>
                    <a href="{{ route('certificati.idoneita') }}?scaduti=1" class="quick-access-item red">
                        <i class="fas fa-medkit"></i>
                        <span class="quick-access-text">Idoneità Scadute</span>
                    </a>
                    <a href="{{ route('pianificazione.index') }}" class="quick-access-item blue">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="quick-access-text">Pianificazione Mensile</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grafico a ciambella delle presenze
    const presenceCtx = document.getElementById('presenceChart').getContext('2d');
    const presenceChart = new Chart(presenceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Presenti', 'Assenti'],
            datasets: [{
                data: [{{ $percentualePresenti }}, {{ 100 - $percentualePresenti }}],
                // Colori espliciti per evitare problemi con var(--error) e var(--success)
                backgroundColor: ['#28a745', '#dc3545'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
