{{--
|--------------------------------------------------------------------------
| Sezione Patenti - Design Minimalista
|--------------------------------------------------------------------------
| @version 1.0
--}}

@php
    $patenti = $militare->patenti ?? collect();
    $patentiValide = $patenti->filter(fn($p) => $p->isValida())->count();
    $patentiScadute = $patenti->filter(fn($p) => $p->isScaduta())->count();
@endphp

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-patenti" aria-expanded="false">
        <div class="sezione-titolo">Patenti</div>
        <div class="sezione-badge-group">
            @if($patenti->count() > 0)
                <span class="badge-count">{{ $patenti->count() }}</span>
            @else
                <span class="badge bg-secondary" style="font-size: 0.75rem;">Nessuna</span>
            @endif
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-patenti" class="collapse sezione-content">
        @if($patenti->count() > 0)
            <div class="patenti-grid">
                @foreach($patenti->sortBy('categoria') as $patente)
                    @php
                        $stato = $patente->getStato();
                    @endphp
                    <div class="patente-item">
                        <span class="patente-categoria">{{ $patente->categoria }}</span>
                        <div class="patente-info">
                            <div>{{ $patente->getNomeCategoria() }}</div>
                            @if($patente->data_scadenza)
                                <div class="d-flex align-items-center gap-1">
                                    <span>Scad: {{ $patente->data_scadenza->format('d/m/Y') }}</span>
                                    <span class="badge-stato badge-{{ $stato == 'valida' ? 'valido' : ($stato == 'in_scadenza' ? 'in-scadenza' : 'scaduto') }}">
                                        {{ $stato == 'valida' ? 'OK' : ($stato == 'in_scadenza' ? 'Scade' : 'Scaduta') }}
                                    </span>
                                </div>
                            @endif
                            @if($patente->tipo)
                                <div class="text-muted">{{ $patente->getNomeTipo() }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">Nessuna patente registrata</p>
        @endif
    </div>
</div>
