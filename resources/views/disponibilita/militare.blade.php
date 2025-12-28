@extends('layouts.app')

@section('title', 'Impegni ' . $militare->cognome . ' ' . $militare->nome . ' - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header con info militare -->
    <div class="text-center mb-4">
        <h1 class="page-title">Impegni Personale</h1>
        <div class="militare-info-card mx-auto mt-3">
            <div class="d-flex align-items-center justify-content-center gap-3">
                <div class="militare-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="text-start">
                    <h4 class="mb-0">{{ $militare->grado->sigla ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</h4>
                    <small class="text-muted">
                        {{ $militare->compagnia->nome ?? 'N/A' }} | 
                        {{ $militare->polo->nome ?? 'N/A' }} |
                        {{ $militare->plotone->nome ?? 'N/A' }}
                    </small>
                </div>
            </div>
        </div>
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
            
            <a href="{{ route('anagrafica.show', $militare) }}" class="btn btn-outline-secondary btn-sm ms-3">
                <i class="fas fa-arrow-left me-1"></i> Torna al Profilo
            </a>
        </form>
    </div>

    <!-- Statistiche Mensili -->
    <div class="row justify-content-center mb-4">
        <div class="col-md-8">
            <div class="stats-row">
                <div class="stat-card stat-impegni">
                    <div class="stat-value">{{ $statistiche['totale_impegni'] }}</div>
                    <div class="stat-label">Giorni Impegnati</div>
                </div>
                <div class="stat-card stat-liberi">
                    <div class="stat-value">{{ $statistiche['giorni_liberi'] }}</div>
                    <div class="stat-label">Giorni Liberi</div>
                </div>
                <div class="stat-card stat-percentuale">
                    <div class="stat-value">{{ $statistiche['percentuale_impegno'] }}%</div>
                    <div class="stat-label">Impegno Mensile</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab per Vista Calendario / Lista -->
    <ul class="nav nav-tabs justify-content-center mb-4" id="vistaTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="calendario-tab" data-bs-toggle="tab" data-bs-target="#calendario" type="button" role="tab">
                <i class="fas fa-calendar-alt me-2"></i>Vista Calendario
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="lista-tab" data-bs-toggle="tab" data-bs-target="#lista" type="button" role="tab">
                <i class="fas fa-list me-2"></i>Lista Impegni
            </button>
        </li>
    </ul>

    <div class="tab-content" id="vistaTabContent">
        <!-- Vista Calendario -->
        <div class="tab-pane fade show active" id="calendario" role="tabpanel">
            <!-- Legenda compatta -->
            <div class="legenda-calendario mb-3">
                <div class="d-flex justify-content-center flex-wrap gap-3">
                    <span class="legenda-item"><span class="legenda-dot" style="background:#dc3545;"></span>T.O.</span>
                    <span class="legenda-item"><span class="legenda-dot" style="background:#ffc107;"></span>Esercitazioni</span>
                    <span class="legenda-item"><span class="legenda-dot" style="background:#28a745;"></span>Cattedre</span>
                    <span class="legenda-item"><span class="legenda-dot" style="background:#007bff;"></span>Corsi</span>
                    <span class="legenda-item"><span class="legenda-dot" style="background:#6c757d;"></span>Servizi</span>
                    <span class="legenda-item"><span class="legenda-dot" style="background:#fd7e14;"></span>Stand-by</span>
                </div>
            </div>
            <div class="calendario-container">
                @php
                    // Calcola il giorno della settimana del primo giorno del mese (0=Dom, 1=Lun, ...)
                    $primoGiorno = \Carbon\Carbon::create($anno, $mese, 1);
                    $giornoSettimanaInizio = $primoGiorno->dayOfWeek;
                    // Converti da 0=Dom a 0=Lun
                    $giornoSettimanaInizio = $giornoSettimanaInizio == 0 ? 6 : $giornoSettimanaInizio - 1;
                @endphp
                
                <!-- Header giorni settimana -->
                <div class="calendario-header">
                    <div class="giorno-header">Lun</div>
                    <div class="giorno-header">Mar</div>
                    <div class="giorno-header">Mer</div>
                    <div class="giorno-header">Gio</div>
                    <div class="giorno-header">Ven</div>
                    <div class="giorno-header weekend">Sab</div>
                    <div class="giorno-header weekend">Dom</div>
                </div>
                
                <!-- Griglia calendario -->
                <div class="calendario-grid">
                    {{-- Celle vuote prima del primo giorno --}}
                    @for($i = 0; $i < $giornoSettimanaInizio; $i++)
                        <div class="calendario-cella vuota"></div>
                    @endfor
                    
                    {{-- Giorni del mese --}}
                    @foreach($calendarioMese as $giorno)
                        @php
                            $cellaClasses = [];
                            if ($giorno['is_today']) $cellaClasses[] = 'oggi';
                            if ($giorno['is_weekend'] || $giorno['is_holiday']) $cellaClasses[] = 'festivo';
                            if ($giorno['ha_impegno']) $cellaClasses[] = 'con-impegno';
                            $numImpegni = count($giorno['impegni'] ?? []);
                        @endphp
                        <div class="calendario-cella {{ implode(' ', $cellaClasses) }}">
                            <div class="cella-numero">{{ $giorno['giorno'] }}</div>
                            @if($giorno['ha_impegno'])
                                <div class="impegni-container {{ $numImpegni > 1 ? 'multi' : '' }}">
                                    @foreach($giorno['impegni'] as $impegno)
                                        <div class="cella-impegno" style="background-color: {{ $impegno['colore'] }};">
                                            <strong>{{ $impegno['codice'] }}</strong>
                                            @if($numImpegni == 1)
                                                <small class="d-block text-truncate" title="{{ $impegno['descrizione'] }}">{{ Str::limit($impegno['descrizione'], 15) }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Vista Lista -->
        <div class="tab-pane fade" id="lista" role="tabpanel">
            <div class="lista-impegni-container">
                @if(count($listaImpegni) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background-color: #0a2342; color: white;">
                                <tr>
                                    <th style="width: 120px;">Data</th>
                                    <th style="width: 100px;">Giorno</th>
                                    <th style="width: 100px;">Codice</th>
                                    <th>Descrizione</th>
                                    <th style="width: 100px;">Fonte</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($listaImpegni as $impegno)
                                    <tr>
                                        <td>
                                            <strong>{{ $impegno['data_formattata'] }}</strong>
                                        </td>
                                        <td>{{ $impegno['nome_giorno'] }}</td>
                                        <td>
                                            <span class="badge" style="background-color: {{ $impegno['colore'] }}; color: white;">
                                                {{ $impegno['codice'] }}
                                            </span>
                                        </td>
                                        <td>{{ $impegno['descrizione'] }}</td>
                                        <td>
                                            <span class="badge bg-{{ $impegno['fonte'] == 'CPT' ? 'primary' : 'info' }}">
                                                {{ $impegno['fonte'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Legenda colori -->
                    <div class="legenda-colori mt-4 p-3 bg-light rounded">
                        <h6 class="mb-3"><i class="fas fa-palette me-2"></i>Legenda Colori</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #dc3545;">T.O.</span> Teatro Operativo
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #ffc107; color: #212529;">EXE</span> Esercitazioni
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #28a745;">CAT</span> Cattedre
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #007bff;">CRS</span> Corsi
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #6c757d;">SI</span> Servizi Isolati
                            </span>
                            <span class="d-flex align-items-center gap-2">
                                <span class="badge" style="background-color: #fd7e14;">STB</span> Stand-by
                            </span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-calendar-check fa-3x mb-3"></i>
                        <p>Nessun impegno registrato per questo mese</p>
                    </div>
                @endif
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

.militare-info-card {
    background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%);
    color: white;
    padding: 20px 40px;
    border-radius: 12px;
    display: inline-block;
    box-shadow: 0 4px 15px rgba(10, 35, 66, 0.3);
}

.militare-avatar {
    font-size: 3rem;
    opacity: 0.8;
}

/* Statistiche */
.stats-row {
    display: flex;
    gap: 20px;
    justify-content: center;
}

.stat-card {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    min-width: 150px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.stat-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 4px;
}

.stat-impegni .stat-value { color: #dc3545; }
.stat-liberi .stat-value { color: #28a745; }
.stat-percentuale .stat-value { color: #0a2342; }

/* Calendario */
.calendario-container {
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.calendario-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
    margin-bottom: 10px;
}

.giorno-header {
    text-align: center;
    font-weight: 600;
    padding: 12px 8px;
    color: #0a2342;
    font-size: 0.9rem;
}

.giorno-header.weekend {
    color: #dc3545;
}

.calendario-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 6px;
}

.calendario-cella {
    min-height: 100px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 8px;
    position: relative;
    transition: all 0.2s;
    border: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
}

.calendario-cella:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.calendario-cella.vuota {
    background: transparent;
    border: none;
    min-height: auto;
}

.calendario-cella.oggi {
    background: #fff8e6;
    border: 2px solid #ffc107 !important;
}

.calendario-cella.festivo {
    background: #fff5f5;
}

.calendario-cella.festivo:not(.con-impegno) {
    background: #fff5f5;
}

.calendario-cella.con-impegno {
    background: white;
}

.cella-numero {
    font-weight: 700;
    font-size: 1rem;
    color: #0a2342;
    margin-bottom: 6px;
}

.calendario-cella.festivo .cella-numero {
    color: #dc3545;
}

/* Container impegni */
.impegni-container {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.impegni-container.multi {
    gap: 3px;
}

.cella-impegno {
    padding: 5px 8px;
    border-radius: 4px;
    font-size: 0.72rem;
    color: white;
    text-shadow: 0 1px 1px rgba(0,0,0,0.2);
}

.impegni-container.multi .cella-impegno {
    padding: 3px 6px;
    font-size: 0.68rem;
}

.cella-impegno strong {
    font-size: 0.78rem;
    font-weight: 600;
}

.impegni-container.multi .cella-impegno strong {
    font-size: 0.7rem;
}

.cella-impegno small {
    opacity: 0.9;
    font-size: 0.65rem;
}

/* Legenda calendario */
.legenda-calendario {
    text-align: center;
}

.legenda-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #555;
}

.legenda-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    display: inline-block;
}

/* Responsive */
@media (max-width: 768px) {
    .calendario-cella {
        min-height: 70px;
        padding: 4px;
    }
    
    .cella-numero {
        font-size: 0.85rem;
    }
    
    .cella-impegno {
        padding: 3px 4px;
        font-size: 0.6rem;
    }
    
    .cella-impegno strong {
        font-size: 0.65rem;
    }
    
    .impegni-container.multi .cella-impegno {
        padding: 2px 3px;
    }
}

/* Lista */
.lista-impegni-container {
    max-width: 900px;
    margin: 0 auto;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Tab personalizzati */
.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 12px 24px;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
    background-color: #0a2342;
    color: white;
}

.nav-tabs .nav-link:hover:not(.active) {
    background-color: rgba(10, 35, 66, 0.1);
}
</style>
@endsection

