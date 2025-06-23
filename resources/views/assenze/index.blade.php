@extends('layouts.app')

@section('content')

<div class="container">
    <!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Gestione Assenze</h1>
</div>

    <a href="{{ route('assenze.create') }}" class="btn btn-primary mb-3">➕ Aggiungi Assenza</a>

    <table class="table table-striped">
    <thead>
        <tr>
            <th>TIPOLOGIA</th>
            <th>DATA INIZIO</th>
            <th>DATA FINE</th>
            <th>ORARIO</th>
            <th>STATO</th>
            <th>GRADO</th>
            <th>MILITARE</th>
            <th>AZIONI</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($assenze as $assenza)
        <tr>
            <td>{{ $assenza->tipologia }}</td>
            <td>{{ $assenza->data_inizio }}</td>
            <td>{{ $assenza->data_fine }}</td>
            <td>
                @if ($assenza->tipologia == 'Recupero Compensativo' && $assenza->orario_inizio && $assenza->orario_fine)
                    {{ \Carbon\Carbon::parse($assenza->orario_inizio)->format('H:i') }} - 
                    {{ \Carbon\Carbon::parse($assenza->orario_fine)->format('H:i') }}
                @else
                    -
                @endif
            </td>
            <td>
                <span class="badge {{ $assenza->stato == 'Approvata' ? 'bg-success' : 'bg-warning' }}">
                    {{ $assenza->stato }}
                </span>
            </td>
            <td>{{ $assenza->militare->grado->nome }}</td>
            <td>{{ $assenza->militare->cognome }} {{ $assenza->militare->nome }}</td>
            <td>
                @if ($assenza->stato == 'Richiesta Ricevuta')
                    <form action="{{ route('assenze.update', $assenza->id) }}" method="POST" style="display:inline;">
                        @csrf
                        @method('PUT')
                        <button type="submit" class="btn btn-primary btn-sm">✔ Approva</button>
                    </form>
                @endif
                <form action="{{ route('assenze.destroy', $assenza->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">🗑 Elimina</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

</div>
@endsection
