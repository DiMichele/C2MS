@extends('layouts.app')

@section('title', 'Trasparenza Servizi - C2MS')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="text-center mb-3">
        <h1 class="page-title">Trasparenza Servizi</h1>
    </div>

    <!-- Selettori Mese/Anno -->
    <div class="d-flex justify-content-center mb-3">
        <div class="d-flex gap-2 align-items-center">
            <select name="mese" id="meseSelect" class="form-select form-select-sm" style="width: 140px; border-radius: 6px !important;" onchange="cambiaData()">
                @foreach($nomiMesi as $num => $nome)
                    <option value="{{ $num }}" {{ $mese == $num ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
            <select name="anno" id="annoSelect" class="form-select form-select-sm" style="width: 100px; border-radius: 6px !important;" onchange="cambiaData()">
                @for($a = 2020; $a <= 2030; $a++)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>
                        {{ $a }}
                    </option>
                @endfor
            </select>
        </div>
    </div>

    <!-- Barra di ricerca centrata -->
    <div class="d-flex justify-content-center mb-3">
        <div class="search-container" style="position: relative; width: 400px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
            <input 
                type="text" 
                id="searchMilitare" 
                class="form-control" 
                data-search-type="militare"
                data-target-container="militariTableBody"
                placeholder="Cerca militare..." 
                aria-label="Cerca militare" 
                style="padding-left: 40px; border-radius: 6px !important;">
        </div>
    </div>

    <!-- Export Excel -->
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('trasparenza.export-excel', ['anno' => $anno, 'mese' => $mese]) }}" 
           class="btn btn-outline-success" style="border-radius: 6px !important;">
            <i class="fas fa-file-excel me-2"></i> Esporta Excel
        </a>
    </div>

    <!-- Tabella con header fisso -->
    <div class="table-container">
        <!-- Header fisso -->
        <div class="table-header-fixed" style="position: sticky; top: 0; z-index: 10; background: white;">
            <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; min-width: 100%;">
                <colgroup>
                    <col style="width:160px">
                    <col style="width:200px">
                    <col style="width:230px">
                    <col style="width:170px">
                    @for($g = 1; $g <= $giorniNelMese; $g++)
                        <col style="width:70px">
                    @endfor
                    <col style="width:80px">
                    <col style="width:80px">
                    <col style="width:110px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="text-center">COMPAGNIA</th>
                        <th class="text-center">GRADO</th>
                        <th class="text-center">COGNOME</th>
                        <th class="text-center">NOME</th>
                        
                        <!-- Giorni del mese -->
                        @for($g = 1; $g <= $giorniNelMese; $g++)
                            @php
                                $data = \Carbon\Carbon::create($anno, $mese, $g);
                                $dataFormattata = $data->format('m-d');
                                $isSabato = $data->dayOfWeek === 6;
                                $isDomenica = $data->dayOfWeek === 0;
                                $isFestivita = isset($festivitaFisse[$dataFormattata]);
                                $isFestivo = $isSabato || $isDomenica || $isFestivita;
                                $dayName = substr($data->locale('it')->dayName, 0, 3);
                            @endphp
                            <th class="text-center {{ $isFestivo ? 'bg-danger text-white' : '' }}">
                                <div style="font-weight: 700; {{ $isFestivo ? 'color: white;' : '' }}">{{ $g }}</div>
                                <small style="{{ $isFestivo ? 'color: white;' : '' }}">{{ ucfirst($dayName) }}</small>
                            </th>
                        @endfor
                        
                        <!-- Totali -->
                        <th class="text-center" style="background: rgba(52, 103, 81, 0.1);">
                            <strong>FERIALI</strong>
                        </th>
                        <th class="text-center" style="background: rgba(220, 53, 69, 0.1);">
                            <strong>FESTIVI</strong>
                        </th>
                        <th class="text-center" style="background: rgba(255, 193, 7, 0.1);">
                            <strong>SUPERFESTIVI</strong>
                        </th>
                    </tr>
                </thead>
            </table>
        </div>

        <!-- Body scrollabile -->
        <div class="table-body-scroll">
            <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; min-width: 100%;">
                <colgroup>
                    <col style="width:160px">
                    <col style="width:200px">
                    <col style="width:230px">
                    <col style="width:170px">
                    @for($g = 1; $g <= $giorniNelMese; $g++)
                        <col style="width:70px">
                    @endfor
                    <col style="width:80px">
                    <col style="width:80px">
                    <col style="width:110px">
                </colgroup>
                <tbody id="militariTableBody">
                    @foreach($datiMilitari as $dato)
                        <tr>
                            <td style="padding: 4px 6px;">
                                {{ $dato['militare']->compagnia->nome ?? '-' }}
                            </td>
                            <td style="padding: 4px 6px;">
                                <span class="fw-bold">{{ $dato['militare']->grado->sigla ?? '' }}</span>
                            </td>
                            <td style="padding: 4px 6px;">
                                <a href="{{ route('anagrafica.show', $dato['militare']->id) }}" class="link-name">
                                    {{ $dato['militare']->cognome }}
                                </a>
                            </td>
                            <td style="padding: 4px 6px;">
                                {{ $dato['militare']->nome }}
                            </td>
                            
                            <!-- Giorni -->
                            @for($g = 1; $g <= $giorniNelMese; $g++)
                                @php
                                    $data = \Carbon\Carbon::create($anno, $mese, $g);
                                    $dataFormattata = $data->format('m-d');
                                    $isSabato = $data->dayOfWeek === 6;
                                    $isDomenica = $data->dayOfWeek === 0;
                                    $isFestivita = isset($festivitaFisse[$dataFormattata]);
                                    $isFestivo = $isSabato || $isDomenica || $isFestivita;
                                    $dettaglio = $dato['giorniDettaglio'][$g] ?? null;
                                    $codice = $dettaglio['codice'] ?? null;
                                    $nomeCompleto = $dettaglio['nome'] ?? null;
                                @endphp
                                <td class="text-center {{ $isFestivo ? 'bg-festivo' : '' }}" style="padding: 4px;">
                                    @if($codice)
                                        <span class="badge bg-primary servizio-badge" 
                                              data-bs-toggle="tooltip" 
                                              data-bs-placement="top" 
                                              title="{{ $nomeCompleto }}">
                                            {{ $codice }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endfor
                            
                            <!-- Totali -->
                            <td class="text-center" style="padding: 4px; background: rgba(52, 103, 81, 0.05);">
                                <strong style="color: #346751;">{{ $dato['totali']['feriali'] }}</strong>
                            </td>
                            <td class="text-center" style="padding: 4px; background: rgba(220, 53, 69, 0.05);">
                                <strong style="color: #ac0e28;">{{ $dato['totali']['festivi'] }}</strong>
                            </td>
                            <td class="text-center" style="padding: 4px; background: rgba(255, 193, 7, 0.05);">
                                <strong style="color: #f59e0b;">{{ $dato['totali']['superfestivi'] }}</strong>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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

/* Container con scroll */
.table-container {
    overflow-x: auto !important;
    overflow-y: auto !important;
    max-height: calc(100vh - 300px);
}

.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed !important;
}

/* Stili tabella uniformi alle altre */
.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

/* Background festivi */
.bg-festivo {
    background-color: rgba(220, 53, 69, 0.08) !important;
}

/* Header festivi con testo bianco */
.table thead th.bg-danger {
    background-color: #dc3545 !important;
}

.table thead th.bg-danger div,
.table thead th.bg-danger small {
    color: white !important;
}

/* Badge servizi - professionali e compatti */
.servizio-badge {
    font-size: 0.55rem;
    padding: 1px 4px;
    font-weight: 700;
    cursor: help;
    line-height: 1.1;
    letter-spacing: 0.3px;
    border-radius: 3px !important;
}

/* Link militari */
.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
    font-weight: 500;
}

.link-name:hover {
    color: #0a2342;
    text-decoration: none;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza i tooltip di Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inizializza il sistema di ricerca
    if (window.C2MS && window.C2MS.Search) {
        window.C2MS.Search.init();
    }
});

// Funzione per cambiare automaticamente mese/anno
function cambiaData() {
    const mese = document.getElementById('meseSelect').value;
    const anno = document.getElementById('annoSelect').value;
    window.location.href = '{{ route("trasparenza.index") }}?mese=' + mese + '&anno=' + anno;
}
</script>
@endsection
