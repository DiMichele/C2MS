{{--
|--------------------------------------------------------------------------
| Sezione Scadenze e Certificazioni - Design Minimalista
|--------------------------------------------------------------------------
| Mostra solo le 6 scadenze essenziali usando le tabelle normalizzate
| @version 2.0
--}}

@php
    $scadenze = $militare->getScadenzeEssenziali();
    
    // Calcola statistiche
    $validi = 0;
    $inScadenza = 0;
    $scaduti = 0;
    
    foreach ($scadenze as $scadenza) {
        if ($scadenza['stato'] === 'valido') $validi++;
        elseif ($scadenza['stato'] === 'in_scadenza') $inScadenza++;
        elseif ($scadenza['stato'] === 'scaduto') $scaduti++;
    }
@endphp

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-scadenze" aria-expanded="false">
        <div class="sezione-titolo">Scadenze e Certificazioni</div>
        <div class="sezione-badge-group">
            @if($validi > 0)
                <span class="badge bg-success" style="font-size: 0.7rem;">{{ $validi }} OK</span>
            @endif
            @if($inScadenza > 0)
                <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">{{ $inScadenza }}</span>
            @endif
            @if($scaduti > 0)
                <span class="badge bg-danger" style="font-size: 0.7rem;">{{ $scaduti }}</span>
            @endif
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-scadenze" class="collapse sezione-content">
        <table class="tabella-scadenze">
            <thead>
                <tr>
                    <th style="width: 40%;">Certificazione</th>
                    <th style="width: 25%;">Conseguimento</th>
                    <th style="width: 25%;">Scadenza</th>
                    <th style="width: 10%;">Stato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scadenze as $scadenza)
                    <tr>
                        <td>{{ $scadenza['nome'] }}</td>
                        <td>
                            @if($scadenza['data_conseguimento'])
                                {{ \Carbon\Carbon::parse($scadenza['data_conseguimento'])->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($scadenza['data_scadenza'])
                                {{ \Carbon\Carbon::parse($scadenza['data_scadenza'])->format('d/m/Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @switch($scadenza['stato'])
                                @case('valido')
                                    <span class="badge-stato badge-valido">OK</span>
                                    @break
                                @case('in_scadenza')
                                    <span class="badge-stato badge-in-scadenza">Scade</span>
                                    @break
                                @case('scaduto')
                                    <span class="badge-stato badge-scaduto">Scaduto</span>
                                    @break
                                @default
                                    <span class="badge-stato badge-non-presente">-</span>
                            @endswitch
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
