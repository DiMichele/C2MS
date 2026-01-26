{{--
|--------------------------------------------------------------------------
| Componente No Results - Messaggio standard "Nessun militare trovato"
|--------------------------------------------------------------------------
| 
| Utilizzo:
|   @include('components.no-results')
|   @include('components.no-results', ['colspan' => 10])
|   @include('components.no-results', ['message' => 'Nessun risultato', 'showButton' => true])
|   @include('components.no-results', ['isTableRow' => false]) // Per uso fuori da tabella
|
--}}

@php
    $colspan = $colspan ?? 100;
    $message = $message ?? 'Nessun militare trovato';
    $submessage = $submessage ?? 'Prova a modificare i criteri di ricerca o i filtri applicati.';
    $showButton = $showButton ?? false;
    $buttonUrl = $buttonUrl ?? '#';
    $buttonText = $buttonText ?? 'Rimuovi tutti i filtri';
    $isTableRow = $isTableRow ?? true;
    $icon = $icon ?? 'fa-users-slash';
@endphp

@if($isTableRow)
<tr class="no-results-row">
    <td colspan="{{ $colspan }}" class="text-center py-5">
        <div class="d-flex flex-column align-items-center empty-state">
            <i class="fas {{ $icon }} fa-3x mb-3 text-muted"></i>
            <p class="lead mb-3">{{ $message }}</p>
            <p class="text-muted mb-3">{{ $submessage }}</p>
            @if($showButton)
            <a href="{{ $buttonUrl }}" class="btn btn-outline-primary mt-2">
                <i class="fas fa-times-circle me-1"></i> {{ $buttonText }}
            </a>
            @endif
        </div>
    </td>
</tr>
@else
<div class="d-flex flex-column align-items-center empty-state py-5">
    <i class="fas {{ $icon }} fa-3x mb-3 text-muted"></i>
    <p class="lead mb-3">{{ $message }}</p>
    <p class="text-muted mb-3">{{ $submessage }}</p>
    @if($showButton)
    <a href="{{ $buttonUrl }}" class="btn btn-outline-primary mt-2">
        <i class="fas fa-times-circle me-1"></i> {{ $buttonText }}
    </a>
    @endif
</div>
@endif
