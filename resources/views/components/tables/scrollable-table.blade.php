{{--
    Componente Blade: Tabella Scrollabile con Navigazione
    
    Uso:
    @include('components.tables.scrollable-table', [
        'tableId' => 'myTable',          // ID univoco della tabella
        'maxHeight' => 'calc(100vh - 280px)' // Opzionale: altezza massima
    ])
        <table class="sugeco-table" id="myTable">
            ...contenuto tabella...
        </table>
    @slot('table')
    @endslot
    
    Oppure uso semplificato (wrappa automaticamente il contenuto):
    <div class="sugeco-table-nav-container" data-table-nav="auto">
        <div class="sugeco-table-wrapper">
            <table class="sugeco-table">...</table>
        </div>
    </div>
--}}

@php
    $tableId = $tableId ?? 'sugeco-table-' . uniqid();
    $maxHeight = $maxHeight ?? 'calc(100vh - 280px)';
@endphp

<div class="sugeco-table-nav-container" data-table-nav="auto">
    {{-- Le frecce vengono create automaticamente dal JS --}}
    
    <div class="sugeco-table-wrapper" style="max-height: {{ $maxHeight }};">
        {{ $slot }}
    </div>
    
    {{-- Progress bar creata automaticamente dal JS --}}
</div>
