@extends('layouts.app')

@section('title', 'Pianificazione - ' . $militare->cognome . ' ' . $militare->nome)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('pianificazione.index', ['mese' => $mese, 'anno' => $anno]) }}">
                            Pianificazione Mensile
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $militare->cognome }} {{ $militare->nome }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="fas fa-user-clock text-primary me-2"></i>
                Pianificazione - {{ $militare->cognome }} {{ $militare->nome }}
            </h1>
            <p class="text-muted mb-0">{{ $pianificazioneMensile->nome }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('militare.show', $militare) }}" class="btn btn-outline-primary">
                <i class="fas fa-user me-1"></i>
                Scheda Militare
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Colonna sinistra - Info militare -->
        <div class="col-md-4">
            <!-- Carta info militare -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>
                        Informazioni Militare
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <small class="text-muted">Matricola</small>
                            <div class="fw-bold">{{ $militare->numero_matricola ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Categoria</small>
                            <div>
                                @if($militare->categoria)
                                    <span class="badge badge-categoria-{{ strtolower($militare->categoria) }}">
                                        {{ $militare->categoria }}
                                    </span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Grado</small>
                            <div class="fw-bold">{{ $militare->grado->nome ?? 'N/A' }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Plotone</small>
                            <div>{{ $militare->plotone->nome ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Approntamento Principale</small>
                            <div>{{ $militare->approntamentoPrincipale->nome ?? 'Nessuno' }}</div>
                        </div>
                        @if($militare->nos_status)
                        <div class="col-12">
                            <small class="text-muted">Status NOS</small>
                            <div>
                                <span class="badge bg-{{ $militare->nos_status === 'SI' ? 'success' : ($militare->nos_status === 'NO' ? 'danger' : 'warning') }}">
                                    {{ $militare->nos_status }}
                                </span>
                                @if($militare->nos_scadenza)
                                    <small class="text-muted ms-2">Scadenza: {{ $militare->nos_scadenza->format('d/m/Y') }}</small>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Statistiche mensili -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistiche Mensili
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-success mb-1">{{ $statisticheMilitare['giorni_disponibile'] }}</div>
                                <small class="text-muted">Giorni Disponibile</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-warning mb-1">{{ $statisticheMilitare['giorni_servizio'] }}</div>
                                <small class="text-muted">Giorni Servizio</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-danger mb-1">{{ $statisticheMilitare['giorni_assente'] }}</div>
                                <small class="text-muted">Giorni Assente</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="h4 text-secondary mb-1">{{ $statisticheMilitare['giorni_non_pianificati'] }}</div>
                                <small class="text-muted">Non Pianificati</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ultimo poligono -->
            @if($militare->ultimoPoligono)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bullseye me-2"></i>
                        Ultimo Poligono
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">{{ $militare->ultimoPoligono->tipoPoligono->nome }}</div>
                            <small class="text-muted">{{ $militare->ultimoPoligono->data_poligono->format('d/m/Y') }}</small>
                        </div>
                        <div>
                            <span class="badge bg-{{ $militare->ultimoPoligono->getColoreBadgeEsito() }}">
                                {{ $militare->ultimoPoligono->getTestoEsito() }}
                            </span>
                            @if($militare->ultimoPoligono->punteggio)
                                <div class="small text-muted mt-1">{{ $militare->ultimoPoligono->punteggio }}/100</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Ultime valutazioni -->
            @if($militare->valutazioni->isNotEmpty())
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        Ultime Valutazioni
                    </h5>
                </div>
                <div class="card-body">
                    @foreach($militare->valutazioni->take(3) as $valutazione)
                        <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2' : '' }}">
                            <div>
                                <small class="text-muted">{{ $valutazione->created_at->format('d/m/Y') }}</small>
                            </div>
                            <div>
                                <span class="badge bg-primary">{{ $valutazione->voto_finale }}/10</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Colonna destra - Calendario -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>
                            Calendario Mensile
                        </h5>
                        <div class="d-flex gap-2">
                            <!-- Selettore mese/anno -->
                            <form method="GET" class="d-inline-flex gap-2">
                                <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ $mese == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                                <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()">
                                    @for($a = date('Y') - 1; $a <= date('Y') + 1; $a++)
                                        <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                                    @endfor
                                </select>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="12%">Giorno</th>
                                    <th width="15%">Data</th>
                                    <th width="20%">Codice</th>
                                    <th width="30%">Attività</th>
                                    <th width="23%">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($giorniMese as $giorno)
                                    @php
                                        $pianificazione = $giorno['pianificazione'];
                                        $codice = $pianificazione?->tipoServizio?->codice ?? '';
                                        $colore = 'secondary';
                                        $attivita = 'Nessuna pianificazione';
                                        
                                        if ($pianificazione && $pianificazione->tipoServizio) {
                                            $gerarchia = $pianificazione->tipoServizio->codiceGerarchia;
                                            if ($gerarchia) {
                                                $colore = $gerarchia->colore_badge ?? 'secondary';
                                                $attivita = $gerarchia->macro_attivita;
                                                if ($gerarchia->tipo_attivita) {
                                                    $attivita .= ' - ' . $gerarchia->tipo_attivita;
                                                }
                                            }
                                        }
                                    @endphp
                                    
                                    <tr class="{{ $giorno['is_weekend'] ? 'table-secondary' : '' }} {{ $giorno['is_today'] ? 'table-warning' : '' }}">
                                        <td class="fw-bold">{{ $giorno['giorno'] }}</td>
                                        <td>
                                            <div>{{ $giorno['data']->format('d/m/Y') }}</div>
                                            <small class="text-muted">{{ $giorno['nome_giorno'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if($codice)
                                                <span class="badge bg-{{ $colore }}">{{ $codice }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $attivita }}</div>
                                            @if($pianificazione && $pianificazione->tipoServizio && $pianificazione->tipoServizio->codiceGerarchia && $pianificazione->tipoServizio->codiceGerarchia->attivita_specifica)
                                                <small class="text-muted">{{ $pianificazione->tipoServizio->codiceGerarchia->attivita_specifica }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pianificazione && $pianificazione->note)
                                                <small>{{ $pianificazione->note }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.badge-categoria-u { background-color: #0d6efd !important; }
.badge-categoria-su { background-color: #198754 !important; }
.badge-categoria-grad { background-color: #ffc107 !important; color: #000 !important; }

.table-secondary {
    background-color: #f8f9fa !important;
}

.table-warning {
    background-color: #fff3cd !important;
}
</style>
@endpush
