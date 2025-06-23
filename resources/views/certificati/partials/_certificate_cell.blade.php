<td data-cert-type="{{ explode('_', $tipo)[2] ?? '' }}">
    @if(!$showButton)
        <span class="text-muted not-required-message">{{ $notRequiredMessage ?? 'Non richiesto' }}</span>
    @else
        @if($cert)
            <div class="certificate-cell">
                <span style="color: {{ $info['color'] }}; font-weight: bold;">
                    {{ $info['status'] }}
                </span>
                <div class="tooltip-content">
                    <div class="certificate-info">
                        <div><strong>Data Ottenimento:</strong> {{ $info['ottenimento'] }}</div>
                        <div><strong>Data Scadenza:</strong> {{ $info['scadenza'] }}</div>
                        <div><strong>Giorni Rimanenti:</strong> {{ $info['daysRemaining'] }}</div>
                        <div><strong>Presenza File:</strong> {{ $cert->file_path ? 'File caricato' : 'File non caricato' }}</div>
                    </div>
                </div>
            </div>
            <br>
            <div class="certificate-buttons">
                <a href="{{ route('certificati.edit', $cert->id) }}" 
                   class="action-btn edit" 
                   data-tooltip="Modifica/Carica {{ ucfirst(explode('_', $tipo)[2] ?? '') }}">
                    <i class="fas fa-pencil-alt"></i>
                </a>
            </div>
        @else
            <span class="text-danger">–</span>
            <br>
            <div class="certificate-buttons">
                <a href="{{ route('certificati.create', ['militare' => $militareId, 'tipo' => $tipo]) }}" 
                   class="action-btn plus" 
                   data-tooltip="Carica {{ ucfirst(explode('_', $tipo)[2] ?? '') }}">
                    <i class="fas fa-plus-circle"></i>
                </a>
            </div>
        @endif
    @endif
</td> 
