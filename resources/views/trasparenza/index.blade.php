@extends('layouts.app')

@section('title', 'Trasparenza Servizi - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Trasparenza Servizi</h1>
    </div>

    <!-- Selettori Mese/Anno centrati -->
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

    <!-- Tabella con header fisso -->
    <div class="sugeco-table-wrapper">
        <table class="sugeco-table">
            <thead>
                <tr>
                    <th>Compagnia</th>
                    <th>Grado</th>
                    <th>Cognome</th>
                    <th>Nome</th>
                    
                    <!-- Giorni del mese -->
                    @for($g = 1; $g <= $giorniNelMese; $g++)
                        @php
                            $data = \Carbon\Carbon::create($anno, $mese, $g);
                            $dataFormattata = $data->format('m-d');
                            $isSabato = $data->dayOfWeek === 6;
                            $isDomenica = $data->dayOfWeek === 0;
                            $isFestivita = isset($festivitaFisse[$dataFormattata]);
                            $isFestivo = $isSabato || $isDomenica || $isFestivita;
                            $isToday = $data->isToday();
                            // Mappa giorno della settimana -> nome completo maiuscolo
                            $nomiGiorni = ['DOMENICA', 'LUNEDI', 'MARTEDI', 'MERCOLEDI', 'GIOVEDI', 'VENERDI', 'SABATO'];
                            $nomeGiornoCompleto = $nomiGiorni[$data->dayOfWeek];
                        @endphp
                        <th class="{{ $isFestivo ? 'weekend' : '' }} {{ $isToday ? 'today' : '' }}" style="padding: 4px 2px;">
                            <div>{{ $nomeGiornoCompleto }}</div>
                            <div class="date-badge">{{ $data->format('d/m') }}</div>
                        </th>
                    @endfor
                    
                    <!-- Totali Mensili -->
                    <th>Fer. mese</th>
                    <th>Fest. mese</th>
                    <th>Sup. mese</th>
                    
                    <!-- Totali Annuali -->
                    <th>Fer. anno</th>
                    <th>Fest. anno</th>
                    <th>Sup. anno</th>
                    <th>Tot. anno</th>
                </tr>
            </thead>
            <tbody id="militariTableBody">
                @foreach($datiMilitari as $dato)
                    <tr>
                        <td>{{ $dato['militare']->compagnia->nome ?? '-' }}</td>
                        <td><strong>{{ $dato['militare']->grado->sigla ?? '' }}</strong></td>
                        <td>
                            <a href="{{ route('anagrafica.show', $dato['militare']->id) }}" class="link-name">
                                {{ $dato['militare']->cognome }}
                            </a>
                        </td>
                        <td>{{ $dato['militare']->nome }}</td>
                        
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
                            <td class="text-center">
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
                        
                        <!-- Totali Mensili -->
                        <td class="text-center"><strong>{{ $dato['totali']['feriali'] }}</strong></td>
                        <td class="text-center"><strong>{{ $dato['totali']['festivi'] }}</strong></td>
                        <td class="text-center"><strong>{{ $dato['totali']['superfestivi'] }}</strong></td>
                        
                        <!-- Totali Annuali -->
                        @php
                            $annuale = $totaliAnnuali[$dato['militare']->id] ?? ['feriali' => 0, 'festivi' => 0, 'superfestivi' => 0, 'totale' => 0];
                        @endphp
                        <td class="text-center"><strong>{{ $annuale['feriali'] }}</strong></td>
                        <td class="text-center"><strong>{{ $annuale['festivi'] }}</strong></td>
                        <td class="text-center"><strong>{{ $annuale['superfestivi'] }}</strong></td>
                        <td class="text-center"><strong>{{ $annuale['totale'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Floating Button Export Excel -->
<a href="{{ route('trasparenza.export-excel', ['anno' => $anno, 'mese' => $mese]) }}" 
   class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

<style>
/* Stili specifici per pagina Trasparenza */
/* (Stili base tabelle in table-standard.css) */

/* Date badge nell'header - stesso stile di Turni/CPT/Disponibilità */
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

/* Weekend/festivi header */
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

/* Colonne giorni - più strette */
.sugeco-table th:nth-child(n+5),
.sugeco-table td:nth-child(n+5) {
    min-width: 50px;
    text-align: center;
}

/* Colonne totali - leggermente più larghe */
.sugeco-table th:nth-last-child(-n+7),
.sugeco-table td:nth-last-child(-n+7) {
    min-width: 60px;
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
    if (window.SUGECO && window.SUGECO.Search) {
        window.SUGECO.Search.init();
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
