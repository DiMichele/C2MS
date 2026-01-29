{{--
|--------------------------------------------------------------------------
| Sezione Attività - Design Minimalista
|--------------------------------------------------------------------------
| @version 2.0
--}}

@php
    $attivita = $militare->activities ?? collect();
    $attivitaAttive = $attivita->filter(function($a) {
        return $a->end_date === null || $a->end_date->isFuture() || $a->end_date->isToday();
    })->sortBy('start_date');
@endphp

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-attivita" aria-expanded="false">
        <div class="sezione-titolo">Attività Assegnate</div>
        <div class="sezione-badge-group">
            @if($attivitaAttive->count() > 0)
                <span class="badge-count">{{ $attivitaAttive->count() }}</span>
            @else
                <span class="badge bg-secondary" style="font-size: 0.75rem;">Nessuna</span>
            @endif
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-attivita" class="collapse sezione-content">
        @if($attivitaAttive->count() > 0)
            @foreach($attivitaAttive as $activity)
                <div class="attivita-item">
                    <div class="attivita-colore" style="background-color: {{ $activity->column->color ?? '#6c757d' }};"></div>
                    <div class="attivita-content">
                        <div class="attivita-titolo">
                            {{ $activity->title }}
                            @if($activity->column)
                                <span class="badge" style="background-color: {{ $activity->column->color ?? '#6c757d' }}; color: white; font-size: 0.7rem;">
                                    {{ $activity->column->name }}
                                </span>
                            @endif
                        </div>
                        <div class="attivita-periodo">
                            {{ $activity->getPeriodoFormattato() }}
                            @if($activity->getDurata())
                                ({{ $activity->getDurata() }} giorni)
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-muted mb-0">Nessuna attività assegnata</p>
        @endif
    </div>
</div>
