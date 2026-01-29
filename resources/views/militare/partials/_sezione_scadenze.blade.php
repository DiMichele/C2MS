{{--
|--------------------------------------------------------------------------
| Sezione Scadenze e Certificazioni - Design Minimalista
|--------------------------------------------------------------------------
| Unifica corsi, poligono e idoneità in un'unica tabella
| @version 1.0
--}}

@php
    $scadenza = $militare->scadenza;
    
    // Definizione tutte le scadenze organizzate per categoria
    $categorie = [
        'Idoneità Sanitarie' => [
            'pefo' => 'PEFO',
            'idoneita_mans' => 'Idoneità Mansione',
            'idoneita_smi' => 'Idoneità SMI',
            'idoneita_to' => 'Idoneità T.O.',
        ],
        'Corsi Lavoratori' => [
            'lavoratore_4h' => 'Corso Lavoratore 4h',
            'lavoratore_8h' => 'Corso Lavoratore 8h',
        ],
        'Corsi RSPP' => [
            'preposto' => 'Preposto',
            'dirigenti' => 'Dirigente',
            'antincendio' => 'Antincendio',
            'blsd' => 'BLSD',
            'primo_soccorso_aziendale' => 'Primo Soccorso',
        ],
        'Poligono e Armi' => [
            'poligono_approntamento' => 'Poligono Approntamento',
            'poligono_mantenimento' => 'Poligono Mantenimento',
            'tiri_approntamento' => 'Tiri Approntamento',
            'mantenimento_arma_lunga' => 'Arma Lunga',
            'mantenimento_arma_corta' => 'Arma Corta',
        ],
    ];
    
    // Calcola statistiche
    $totale = 0;
    $validi = 0;
    $inScadenza = 0;
    $scaduti = 0;
    
    foreach ($categorie as $cat => $campi) {
        foreach ($campi as $key => $nome) {
            if ($scadenza && $scadenza->{$key . '_data_conseguimento'}) {
                $totale++;
                $stato = $scadenza->verificaStato($key);
                if ($stato === 'valido') $validi++;
                elseif ($stato === 'in_scadenza') $inScadenza++;
                elseif ($stato === 'scaduto') $scaduti++;
            }
        }
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
        @if($scadenza)
            @foreach($categorie as $nomeCategoria => $campi)
                <div class="mb-4">
                    <h6 class="mb-2 text-muted" style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">{{ $nomeCategoria }}</h6>
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
                            @foreach($campi as $key => $nome)
                                @php
                                    $dataConseguimento = $scadenza->{$key . '_data_conseguimento'};
                                    $stato = $scadenza->verificaStato($key);
                                    $dataScadenza = $scadenza->formatScadenza($key);
                                @endphp
                                <tr>
                                    <td>{{ $nome }}</td>
                                    <td>{{ $dataConseguimento ? $dataConseguimento->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $dataScadenza }}</td>
                                    <td>
                                        @switch($stato)
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
            @endforeach
        @else
            <p class="text-muted mb-0">Nessuna scadenza registrata</p>
        @endif
        
        <div class="text-end mt-3">
            <a href="{{ route('scadenze.index') }}" class="btn btn-outline-primary btn-sm">
                Vedi tutte le scadenze
            </a>
        </div>
    </div>
</div>
