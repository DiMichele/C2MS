@extends('layouts.app')

@section('title', 'Test Pianificazione - ' . count($militariConPianificazione) . ' militari')

@section('content')
<div class="container-fluid">
    <h1>Test Pianificazione - {{ count($militariConPianificazione) }} militari</h1>
    
    <div class="alert alert-info">
        <strong>Debug Info:</strong><br>
        Militari ricevuti: {{ count($militariConPianificazione) }}<br>
        Giorni del mese: {{ count($giorniMese) }}<br>
        Mese/Anno: {{ $mese }}/{{ $anno }}
    </div>
    
    <div style="height: 400px; overflow: auto; border: 1px solid #ccc;">
        <table class="table table-sm table-striped">
            <thead class="table-dark sticky-top">
                <tr>
                    <th>#</th>
                    <th>Grado</th>
                    <th>Cognome</th>
                    <th>Nome</th>
                    <th>Plotone</th>
                    <th>Pianificazioni</th>
                </tr>
            </thead>
            <tbody>
                @foreach($militariConPianificazione as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['militare']->grado->nome ?? 'N/A' }}</td>
                        <td>{{ $item['militare']->cognome }}</td>
                        <td>{{ $item['militare']->nome }}</td>
                        <td>{{ $item['militare']->plotone->nome ?? 'N/A' }}</td>
                        <td>{{ count($item['pianificazioni']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-3">
        <a href="{{ route('pianificazione.index') }}" class="btn btn-primary">Torna alla Pianificazione</a>
    </div>
</div>
@endsection
