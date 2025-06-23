@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Inserisci Evento</h1>
</div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('eventi.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label>Tipologia</label>
            <select name="tipologia" class="form-control" required>
                <option value="Esercitazione">Esercitazione</option>
                <option value="Missione">Missione</option>
                <option value="Corso">Corso</option>
            </select>
        </div>

        <div class="mb-3">
            <label>Nome Evento</label>
            <input type="text" name="nome" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Data Inizio</label>
            <input type="date" name="data_inizio" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Data Fine</label>
            <input type="date" name="data_fine" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Località</label>
            <input type="text" name="localita" class="form-control" required>
        </div>

        <!-- 🔎 Barra di ricerca per i militari -->
        <div class="mb-3">
            <label>Cerca Militare</label>
            <input type="text" id="searchMilitare" class="form-control" placeholder="🔎 Digita per cercare...">
        </div>

        <div class="mb-3">
            <label>Seleziona Militari</label>
            <div id="militariList">
                @foreach ($militari as $militare)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="militare_id[]" value="{{ $militare->id }}">
                    <label class="form-check-label">
                        {{ $militare->grado->nome }} - {{ $militare->cognome }} {{ $militare->nome }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <button type="submit" class="btn btn-success">✅ Salva</button>
        <a href="{{ route('eventi.index') }}" class="btn btn-secondary">↩ Indietro</a>
    </form>
</div>

<!-- 🔥 JavaScript per la ricerca dinamica -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.getElementById("searchMilitare");
        const militariList = document.getElementById("militariList").getElementsByClassName("form-check");

        searchInput.addEventListener("keyup", function() {
            const filter = searchInput.value.toLowerCase();
            Array.from(militariList).forEach(function(item) {
                const label = item.querySelector("label").textContent.toLowerCase();
                item.style.display = label.includes(filter) ? "" : "none";
            });
        });
    });
</script>
@endsection
