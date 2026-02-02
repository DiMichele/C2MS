@extends('layouts.app')

@section('title', 'Dashboard - SUGECO')

@section('styles')
<link href="{{ asset('css/dashboard-modern.css') }}" rel="stylesheet">
<style>
/* Dashboard Compatta e Professionale */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.kpi-card-minimal {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    border-left: 4px solid;
}

.kpi-card-minimal:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.kpi-card-minimal.primary { border-left-color: #0d6efd; }
.kpi-card-minimal.success { border-left-color: #28a745; }
.kpi-card-minimal.danger { border-left-color: #dc3545; }

.kpi-number {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.kpi-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.section-compact {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #0A2342;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.75rem;
}

.quick-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    text-decoration: none;
    color: #495057;
    transition: all 0.2s;
    font-size: 0.875rem;
}

.quick-link:hover {
    background: #0A2342;
    color: white;
    transform: translateY(-2px);
}

.quick-link i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.activity-card {
    display: block;
    padding: 1rem;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    transition: all 0.2s;
}

.activity-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
    transform: translateY(-2px);
}

.scadenze-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.scadenza-box {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.scadenza-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    font-size: 0.9rem;
}

.scadenza-badge {
    font-size: 1.2rem;
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    min-width: 60px;
    display: inline-block;
    text-align: center;
}
</style>
@endsection

@section('content')
<!-- Header -->
<div class="text-center mb-4">
    <h1 class="page-title">Dashboard</h1>
</div>

<!-- KPI Principali -->
<div class="kpi-grid">
    <div class="kpi-card-minimal primary" onclick="window.location.href='{{ route('anagrafica.index') }}'" style="cursor: pointer;">
        <div class="kpi-number">{{ $kpis['totale_militari'] ?? 0 }}</div>
        <div class="kpi-label">Forza Effettiva</div>
    </div>
    
    <div class="kpi-card-minimal success" onclick="window.location.href='{{ route('pianificazione.index', ['mese' => now()->month, 'anno' => now()->year, 'stato_impegno' => 'libero']) }}'">
        <div class="kpi-number text-success">{{ $kpis['presenti_oggi'] ?? 0 }}</div>
        <div class="kpi-label">Presenti Oggi</div>
    </div>
    
    <div class="kpi-card-minimal danger" onclick="window.location.href='{{ route('pianificazione.index', ['mese' => now()->month, 'anno' => now()->year, 'stato_impegno' => 'impegnato']) }}'">
        <div class="kpi-number text-danger">{{ $kpis['assenti_oggi'] ?? 0 }}</div>
        <div class="kpi-label">Assenti Oggi</div>
    </div>
</div>

<!-- Accesso Rapido -->
<div class="section-compact">
    <div class="section-title">
        <i class="fas fa-bolt"></i>Accesso Rapido
            </div>
    <div class="quick-links">
        <a href="{{ route('anagrafica.index') }}" class="quick-link">
            <i class="fas fa-id-card"></i>
            Anagrafica
        </a>
        <a href="{{ route('scadenze.index') }}" class="quick-link">
            <i class="fas fa-calendar-check"></i>
            Scadenze
        </a>
        <a href="{{ route('board.index') }}" class="quick-link">
            <i class="fas fa-tasks"></i>
            Board Attività
        </a>
        <a href="{{ route('pianificazione.index') }}" class="quick-link">
            <i class="fas fa-calendar-alt"></i>
            Pianificazione
        </a>
        <a href="{{ route('pianificazione.index', ['mese' => now()->month, 'anno' => now()->year, 'stato_impegno' => 'libero']) }}" class="quick-link">
            <i class="fas fa-user-check"></i>
            Presenti
        </a>
        <a href="{{ route('pianificazione.index', ['mese' => now()->month, 'anno' => now()->year, 'stato_impegno' => 'impegnato']) }}" class="quick-link">
            <i class="fas fa-user-slash"></i>
            Assenti
                            </a>
                        </div>
                    </div>

<!-- Attività in Corso -->
<div class="section-compact">
    <div class="section-title">
        <i class="fas fa-clipboard-list"></i>Attività in Corso Oggi
        @if(isset($attivitaOggi) && count($attivitaOggi) > 0)
            <span class="badge bg-primary ms-2" style="font-size: 0.75rem;">{{ count($attivitaOggi) }}</span>
        @endif
    </div>
    @if(isset($attivitaOggi) && count($attivitaOggi) > 0)
        <div style="display: grid; gap: 0.75rem;">
            @foreach($attivitaOggi as $attivita)
            <a href="{{ route('board.activities.show', $attivita['id']) }}" class="activity-card" style="text-decoration: none; color: inherit;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <div style="margin-bottom: 0.5rem;">
                            <span class="badge" style="background: #0d6efd; color: white; font-size: 0.7rem; padding: 0.25rem 0.5rem; margin-right: 0.5rem;">
                                {{ $attivita['categoria'] }}
                            </span>
                            <strong style="font-size: 0.95rem;">{{ $attivita['titolo'] }}</strong>
                        </div>
                        <div style="font-size: 0.85rem; color: #6c757d;">
                            <i class="fas fa-users" style="width: 16px;"></i> {{ $attivita['militari'] }}
                        </div>
                    </div>
                    <div style="color: #6c757d;">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
            @endforeach
            </div>
    @else
        <div class="text-center text-muted py-4" style="background: #f8f9fa; border-radius: 6px;">
            <i class="fas fa-calendar-day" style="font-size: 2rem; margin-bottom: 0.75rem; opacity: 0.3;"></i>
            <p style="margin: 0; font-size: 0.9rem;">Nessuna attività programmata per oggi</p>
        </div>
    @endif
</div>

<!-- Compleanni di Oggi -->
@if(isset($compleanniOggi) && count($compleanniOggi) > 0)
<div class="section-compact">
    <div class="section-title">
        <i class="fas fa-birthday-cake"></i>Compleanni di Oggi
        <span class="badge bg-warning text-dark ms-2" style="font-size: 0.75rem;">{{ count($compleanniOggi) }}</span>
    </div>
    <div style="display: grid; gap: 0.5rem;">
        @foreach($compleanniOggi as $compleanno)
        <a href="{{ route('anagrafica.show', $compleanno['id']) }}" class="activity-card" style="text-decoration: none; color: inherit; padding: 0.75rem 1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-gift" style="color: white; font-size: 1rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">{{ $compleanno['nome_completo'] }}</div>
                        <div style="font-size: 0.8rem; color: #6c757d;">
                            {{ $compleanno['compagnia'] }} &bull; Compie {{ $compleanno['eta'] }} anni
                        </div>
                    </div>
                </div>
                <div style="color: #ffc107; font-size: 1.25rem;">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

<!-- Scadenze -->
<div class="section-compact">
    <div class="section-title">
        <i class="fas fa-exclamation-triangle"></i>Situazione Scadenze
    </div>
    <div class="scadenze-grid">
        <!-- RSPP -->
        <div class="scadenza-box">
            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-hard-hat me-2"></i>RSPP</h6>
            <div class="scadenza-row">
                <span>Validi</span>
                <span class="scadenza-badge bg-success text-white">{{ $scadenzeRspp['validi'] }}</span>
            </div>
            <div class="scadenza-row">
                <span>In Scadenza</span>
                <span class="scadenza-badge bg-warning">{{ $scadenzeRspp['in_scadenza'] }}</span>
                            </div>
            <div class="scadenza-row">
                <span>Scaduti</span>
                <span class="scadenza-badge bg-danger text-white">{{ $scadenzeRspp['scaduti'] }}</span>
    </div>
</div>

        <!-- Idoneità -->
        <div class="scadenza-box">
            <h6 class="fw-bold mb-3 text-danger"><i class="fas fa-heartbeat me-2"></i>Idoneità</h6>
            <div class="scadenza-row">
                <span>Valide</span>
                <span class="scadenza-badge bg-success text-white">{{ $scadenzeIdoneita['validi'] }}</span>
            </div>
            <div class="scadenza-row">
                <span>In Scadenza</span>
                <span class="scadenza-badge bg-warning">{{ $scadenzeIdoneita['in_scadenza'] }}</span>
                        </div>
            <div class="scadenza-row">
                <span>Scadute</span>
                <span class="scadenza-badge bg-danger text-white">{{ $scadenzeIdoneita['scaduti'] }}</span>
    </div>
</div>

        <!-- Poligoni -->
        <div class="scadenza-box">
            <h6 class="fw-bold mb-3 text-warning"><i class="fas fa-bullseye me-2"></i>Poligoni</h6>
            <div class="scadenza-row">
                <span>Validi</span>
                <span class="scadenza-badge bg-success text-white">{{ $scadenzePoligoni['validi'] }}</span>
            </div>
            <div class="scadenza-row">
                <span>In Scadenza</span>
                <span class="scadenza-badge bg-warning">{{ $scadenzePoligoni['in_scadenza'] }}</span>
                </div>
            <div class="scadenza-row">
                <span>Scaduti</span>
                <span class="scadenza-badge bg-danger text-white">{{ $scadenzePoligoni['scaduti'] }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Statistiche per Unità Organizzativa (Multi-tenancy) --}}
@if(isset($statisticheUnita) && $statisticheUnita->isNotEmpty())
<div class="section-compact">
    <div class="section-title">
        <i class="fas fa-sitemap"></i>Situazione per Unità
        <span class="badge bg-info text-white ms-2" style="font-size: 0.75rem;">{{ $statisticheUnita->count() }} unità</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        @foreach($statisticheUnita as $stats)
        <div class="activity-card {{ $stats['is_active'] ? 'border-primary border-2' : '' }}" style="padding: 1rem; {{ $stats['is_active'] ? 'background: #f0f7ff;' : '' }}">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                @if($stats['unit']->type && $stats['unit']->type->icon)
                <i class="fas {{ $stats['unit']->type->icon }}" style="color: {{ $stats['unit']->type->color ?? '#0A2342' }}; font-size: 1.25rem;"></i>
                @else
                <i class="fas fa-building" style="color: #0A2342; font-size: 1.25rem;"></i>
                @endif
                <div>
                    <div style="font-weight: 600; font-size: 0.95rem;">{{ $stats['unit']->name }}</div>
                    @if($stats['unit']->type)
                    <div style="font-size: 0.75rem; color: #6c757d;">{{ $stats['unit']->type->name }}</div>
                    @endif
                </div>
                @if($stats['is_active'])
                <span class="badge bg-primary ms-auto" style="font-size: 0.65rem;">ATTIVA</span>
                @endif
            </div>
            <div style="display: flex; justify-content: space-around; text-align: center;">
                <div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #0A2342;">{{ $stats['militari_count'] }}</div>
                    <div style="font-size: 0.7rem; color: #6c757d;">Militari</div>
                </div>
                <div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: #0d6efd;">{{ $stats['attivita_count'] }}</div>
                    <div style="font-size: 0.7rem; color: #6c757d;">Attività</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
