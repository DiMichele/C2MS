{{--
|--------------------------------------------------------------------------
| Sezione Teatro Operativo - Design Minimalista
|--------------------------------------------------------------------------
| Mostra solo il nome del Teatro Operativo confermato
| @version 3.0
--}}

@php
    $teatroConfermato = $militare->getTeatroOperativoConfermato();
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
            <div class="py-2">
                <strong class="fs-5">{{ $teatroConfermato->nome }}</strong>
            </div>
        @else
            <p class="text-muted mb-0">Nessun Teatro Operativo confermato</p>
        @endif
    </div>
</div>
