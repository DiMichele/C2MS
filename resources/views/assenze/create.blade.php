@extends('layouts.app')
@section('title', 'Inserisci Assenza - C2MS')

@section('content')
<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">Inserisci Assenza</h1>
</div>

@if ($errors->any())
<div class="alert alert-danger card mb-4 p-3">
    <div class="d-flex align-items-center">
        <i class="fas fa-exclamation-triangle me-3 fa-2x"></i>
        <div>
            <h5 class="alert-heading mb-1">Attenzione</h5>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-calendar-plus me-2"></i> Dati Assenza
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('assenze.store') }}" method="POST">
            @csrf

            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="tipologia" class="form-label">
                        <i class="fas fa-tag me-1"></i> Tipologia
                    </label>
                    <div class="select-wrapper">
                        <select name="tipologia" id="tipologia" class="form-select" required>
                            <option value="">Seleziona tipologia</option>
                            <option value="Licenza Ordinaria">Licenza Ordinaria</option>
                            <option value="R.M.D.">R.M.D.</option>
                            <option value="Recupero Compensativo">Recupero Compensativo</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="data_inizio" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i> Data Inizio
                    </label>
                    <input type="date" name="data_inizio" id="data_inizio" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <label for="data_fine" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i> Data Fine
                    </label>
                    <input type="date" name="data_fine" id="data_fine" class="form-control" required>
                </div>
            </div>

            <!-- Campo Orario, visibile solo se "Recupero Compensativo" -->
            <div class="row mb-4" id="orario_container" style="display: none;">
                <div class="col-md-6">
                    <label for="orario_inizio" class="form-label">
                        <i class="fas fa-clock me-1"></i> Orario Inizio
                    </label>
                    <input type="time" name="orario_inizio" id="orario_inizio" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="orario_fine" class="form-label">
                        <i class="fas fa-clock me-1"></i> Orario Fine
                    </label>
                    <input type="time" name="orario_fine" id="orario_fine" class="form-control">
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-users me-2"></i> Seleziona Militari
                    </div>
                </div>
                <div class="card-body">
                    <!-- Barra di ricerca migliorata -->
                    <div class="search-container mb-4" style="position: relative;">
                        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 5;"></i>
                        <input type="text" id="search-militari" class="form-control" placeholder="Cerca militare per nome, cognome o grado..." aria-label="Cerca militare" style="padding-left: 40px; border-radius: 20px;">
                    </div>

                    <!-- Lista dei militari con checkbox, stile migliorato -->
                    <div id="militari-list" class="row">
                        @foreach ($militari as $militare)
                        <div class="col-md-4 mb-2 militare-check-item">
                            <div class="form-check custom-checkbox">
                                <input class="form-check-input" type="checkbox" name="militare_id[]" id="militare-{{ $militare->id }}" value="{{ $militare->id }}">
                                <label class="form-check-label d-flex align-items-center" for="militare-{{ $militare->id }}">
                                    <span class="ms-2">
                                        <strong>{{ $militare->grado->nome }}</strong> - {{ $militare->cognome }} {{ $militare->nome }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('assenze.index') }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Indietro
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Salva
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const tipologiaSelect = document.getElementById("tipologia");
        const orarioContainer = document.getElementById("orario_container");
        const orarioInizio = document.getElementById("orario_inizio");
        const orarioFine = document.getElementById("orario_fine");

        // Gestione della visualizzazione del campo orario
        tipologiaSelect.addEventListener("change", function() {
            if (this.value === "Recupero Compensativo") {
                orarioContainer.style.display = "flex";
                orarioInizio.setAttribute("required", "required");
                orarioFine.setAttribute("required", "required");
            } else {
                orarioContainer.style.display = "none";
                orarioInizio.removeAttribute("required");
                orarioFine.removeAttribute("required");
            }
        });

        // Funzione per la ricerca dei militari migliorata
        window.filterMilitari = function() {
            let input = document.getElementById('search-militari').value.toLowerCase();
            let militari = document.querySelectorAll('.militare-check-item');

            militari.forEach(item => {
                let label = item.querySelector("label").textContent.toLowerCase();
                if (label.includes(input)) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            });
        };

        // Aggiungere event listener per la ricerca
        document.getElementById('search-militari').addEventListener('keyup', filterMilitari);
        document.getElementById('search-militari').addEventListener('input', filterMilitari);
    });
</script>

<style>
    /* Stile custom per le checkbox */
    .custom-checkbox {
        padding: 0.5rem;
        border-radius: 8px;
        transition: background-color 0.2s;
    }
    
    .custom-checkbox:hover {
        background-color: rgba(0,0,0,0.03);
    }
    
    .form-check-input:checked + .form-check-label {
        color: var(--navy);
        font-weight: 500;
    }
    
    #militari-list {
        max-height: 400px;
        overflow-y: auto;
        padding: 0.5rem;
    }
</style>
@endsection
