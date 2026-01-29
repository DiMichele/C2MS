{{--
|--------------------------------------------------------------------------
| Sezione Teatro Operativo - Design Minimalista
|--------------------------------------------------------------------------
| @version 2.0
--}}

@php
    $teatroConfermato = $militare->getTeatroOperativoConfermato();
    $tuttiTeatri = $militare->teatriOperativi ?? collect();
    $haTeatro = $teatroConfermato !== null;
@endphp

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-teatro" aria-expanded="false">
        <div class="sezione-titolo">Teatro Operativo</div>
        <div class="sezione-badge-group">
            @if($haTeatro)
                <span class="badge bg-danger">In T.O.</span>
            @else
                <span class="badge bg-secondary" style="font-size: 0.75rem;">Nessuno</span>
            @endif
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-teatro" class="collapse sezione-content">
        @if($haTeatro)
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <strong class="fs-5">{{ $teatroConfermato->nome }}</strong>
                        @if($teatroConfermato->codice)
                            <span class="badge bg-dark ms-2">{{ $teatroConfermato->codice }}</span>
                        @endif
                    </div>
                    <span class="badge bg-success">Confermato</span>
                </div>
                
                <div class="teatro-info">
                    <div class="teatro-campo">
                        <div class="teatro-campo-label">Periodo</div>
                        <div class="teatro-campo-value">{{ $teatroConfermato->getPeriodoFormattato() }}</div>
                    </div>
                    <div class="teatro-campo">
                        <div class="teatro-campo-label">Ruolo</div>
                        <div class="teatro-campo-value">{{ $teatroConfermato->pivot->ruolo ?? '-' }}</div>
                    </div>
                    <div class="teatro-campo">
                        <div class="teatro-campo-label">Data Assegnazione</div>
                        <div class="teatro-campo-value">
                            {{ $teatroConfermato->pivot->data_assegnazione ? \Carbon\Carbon::parse($teatroConfermato->pivot->data_assegnazione)->format('d/m/Y') : '-' }}
                        </div>
                    </div>
                    @if($teatroConfermato->pivot->note)
                    <div class="teatro-campo" style="grid-column: 1 / -1;">
                        <div class="teatro-campo-label">Note</div>
                        <div class="teatro-campo-value">{{ $teatroConfermato->pivot->note }}</div>
                    </div>
                    @endif
                </div>
            </div>
        @else
            <p class="text-muted mb-0">Nessun Teatro Operativo assegnato</p>
        @endif
        
        {{-- Altri Teatri --}}
        @php
            $altriTeatri = $tuttiTeatri->filter(function($t) use ($teatroConfermato) {
                return !$teatroConfermato || $t->id !== $teatroConfermato->id;
            });
        @endphp
        
        @if($altriTeatri->count() > 0)
            <hr class="my-3">
            <div class="mb-2 text-muted small">Altri Teatri Operativi</div>
            @foreach($altriTeatri as $teatro)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>{{ $teatro->nome }}</strong>
                        @if($teatro->codice)
                            <span class="badge bg-secondary ms-1">{{ $teatro->codice }}</span>
                        @endif
                        <br>
                        <small class="text-muted">{{ $teatro->getPeriodoFormattato() }}</small>
                    </div>
                    <span class="badge bg-{{ ($teatro->pivot->stato ?? 'bozza') == 'confermato' ? 'success' : 'warning' }}">
                        {{ ucfirst($teatro->pivot->stato ?? 'Bozza') }}
                    </span>
                </div>
            @endforeach
        @endif
    </div>
</div>
