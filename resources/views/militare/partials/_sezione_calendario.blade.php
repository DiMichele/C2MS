{{--
|--------------------------------------------------------------------------
| Sezione Calendario - Design Minimalista
|--------------------------------------------------------------------------
| @version 2.0
--}}

@php
    $calendario = $calendarioData ?? [];
    $calendarioMese = $calendario['calendarioMese'] ?? [];
    $statistiche = $calendario['statistiche'] ?? ['totale_impegni' => 0, 'giorni_liberi' => 0, 'percentuale_impegno' => 0];
    $nomeMese = $calendario['nomeMese'] ?? now()->translatedFormat('F');
    $anno = $calendario['anno'] ?? now()->year;
    $primoGiornoSettimana = $calendario['primoGiornoSettimana'] ?? 0;
@endphp

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-calendario" aria-expanded="false">
        <div class="sezione-titolo">Calendario Impegni</div>
        <div class="sezione-badge-group">
            <span class="badge bg-secondary" style="font-size: 0.75rem;">{{ $nomeMese }} {{ $anno }}</span>
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-calendario" class="collapse sezione-content">
        <div class="row">
            <div class="col-lg-8">
                <div class="mini-calendario">
                    <div class="mini-cal-header">
                        <div class="mini-cal-giorno">L</div>
                        <div class="mini-cal-giorno">M</div>
                        <div class="mini-cal-giorno">M</div>
                        <div class="mini-cal-giorno">G</div>
                        <div class="mini-cal-giorno">V</div>
                        <div class="mini-cal-giorno weekend">S</div>
                        <div class="mini-cal-giorno weekend">D</div>
                    </div>
                    
                    <div class="mini-cal-grid">
                        @for($i = 0; $i < $primoGiornoSettimana; $i++)
                            <div class="mini-cal-cella vuota"></div>
                        @endfor
                        
                        @foreach($calendarioMese as $giorno)
                            @php
                                $classi = [];
                                if ($giorno['is_today']) $classi[] = 'oggi';
                                if ($giorno['is_weekend']) $classi[] = 'weekend';
                                if ($giorno['ha_impegno']) $classi[] = 'con-impegno';
                                $colore = $giorno['impegni'][0]['colore'] ?? null;
                            @endphp
                            <div class="mini-cal-cella {{ implode(' ', $classi) }}" 
                                 @if($colore) style="--impegno-color: {{ $colore }}" @endif
                                 @if($giorno['ha_impegno']) title="{{ implode(', ', array_column($giorno['impegni'], 'codice')) }}" @endif>
                                {{ $giorno['giorno'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="calendario-stats flex-column">
                    <div class="stat-item">
                        <div class="stat-value text-danger">{{ $statistiche['totale_impegni'] }}</div>
                        <div class="stat-label">Impegnati</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value text-success">{{ $statistiche['giorni_liberi'] }}</div>
                        <div class="stat-label">Liberi</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value">{{ $statistiche['percentuale_impegno'] }}%</div>
                        <div class="stat-label">Impegno</div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="{{ route('disponibilita.militare', $militare) }}" class="btn btn-outline-primary btn-sm w-100">
                        Calendario Completo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
