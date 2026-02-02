@extends('layouts.app')

@section('title', 'Gestione Configurazione Anagrafica')

@section('styles')
<style>
/* Toggle Container - Stile moderno come Gestione Permessi */
.toggle-wrapper-anagrafica {
    background-color: white;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(10, 35, 66, 0.1);
    padding: 0.4rem;
    position: relative;
    display: inline-flex;
    transition: all 0.3s ease;
}

.toggle-wrapper-anagrafica:hover {
    box-shadow: 0 6px 20px rgba(10, 35, 66, 0.15);
    transform: translateY(-2px);
}

.toggle-option-anagrafica {
    position: relative;
    z-index: 1;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    min-width: 160px;
    text-align: center;
    font-size: 0.95rem;
    border: none;
    background: transparent;
}

.toggle-option-anagrafica.active {
    color: white;
}

.toggle-option-anagrafica:not(.active) {
    color: #6c757d;
}

.toggle-option-anagrafica:not(.active):hover {
    color: #0A2342;
}

.toggle-slider-anagrafica {
    position: absolute;
    top: 0.4rem;
    left: 0.4rem;
    height: calc(100% - 0.8rem);
    width: calc(50% - 0.4rem);
    background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%);
    border-radius: 50px;
    transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
    box-shadow: 0 4px 15px rgba(10, 35, 66, 0.25);
}

.toggle-slider-anagrafica.pos-1 { transform: translateX(0); }
.toggle-slider-anagrafica.pos-2 { transform: translateX(100%); }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Configurazione Anagrafica</h1>
    </div>

    <!-- Toggle moderno -->
    <div class="d-flex justify-content-center mb-4">
        <div class="toggle-wrapper-anagrafica">
            <div id="toggleSliderAnagrafica" class="toggle-slider-anagrafica pos-1"></div>
            <button class="toggle-option-anagrafica active" id="incarichiBtn" onclick="switchTabAnagrafica('incarichi')">
                Incarichi
            </button>
            <button class="toggle-option-anagrafica" id="campiBtn" onclick="switchTabAnagrafica('campi')">
                Campi
            </button>
        </div>
    </div>

    <!-- Content Panels -->
    <!-- PANEL INCARICHI -->
    <div id="incarichiPanel">
        <div class="table-container-ruolini" style="max-width: 800px; margin: 0 auto;">
            <table class="sugeco-table" id="incarichiTable">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Nome Incarico</th>
                        <th style="width: 120px;">Azioni</th>
                    </tr>
                </thead>
                <tbody id="incarichiTableBody">
                    @forelse($incarichi as $incarico)
                    <tr data-id="{{ $incarico->id }}">
                        <td class="text-center drag-handle" style="cursor: move;">
                            <i class="fas fa-grip-vertical text-muted"></i>
                        </td>
                        <td><strong>{{ $incarico->nome }}</strong></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-primary edit-incarico-btn" 
                                    data-id="{{ $incarico->id }}"
                                    data-nome="{{ $incarico->nome }}"
                                    title="Modifica">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-incarico-btn" 
                                    data-id="{{ $incarico->id }}"
                                    data-nome="{{ $incarico->nome }}"
                                    title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="text-center text-muted py-5">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            <p class="mb-0">Nessun incarico configurato</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- FAB per Incarichi -->
        <button class="fab fab-success fab-incarichi" data-bs-toggle="modal" data-bs-target="#createIncaricoModal" data-tooltip="Nuovo Incarico" aria-label="Nuovo Incarico">
            <i class="fas fa-plus"></i>
        </button>
    </div>

    <!-- PANEL CAMPI -->
    <div id="campiPanel" style="display: none;">
        @include('gestione-campi-anagrafica.index', ['isTab' => true, 'campi' => $campi])
    </div>

</div>

<!-- MODALS PLOTONI -->
@include('gestione-anagrafica-config.modals.plotoni')

<!-- MODALS UFFICI -->
@include('gestione-anagrafica-config.modals.uffici')

<!-- MODALS INCARICHI -->
@include('gestione-anagrafica-config.modals.incarichi')

@endsection

@push('scripts')
<script src="{{ asset('js/gestione-anagrafica-config.js') }}"></script>
<script>
// Toggle per i pannelli
function switchTabAnagrafica(tab) {
    // Nascondi/mostra pannelli
    document.getElementById('incarichiPanel').style.display = tab === 'incarichi' ? '' : 'none';
    document.getElementById('campiPanel').style.display = tab === 'campi' ? '' : 'none';
    
    // Aggiorna stato attivo
    document.getElementById('incarichiBtn').classList.toggle('active', tab === 'incarichi');
    document.getElementById('campiBtn').classList.toggle('active', tab === 'campi');
    
    // Sposta lo slider
    const slider = document.getElementById('toggleSliderAnagrafica');
    slider.classList.remove('pos-1', 'pos-2');
    slider.classList.add(tab === 'incarichi' ? 'pos-1' : 'pos-2');
    
    // Salva stato in localStorage
    localStorage.setItem('anagraficaConfigTab', tab);
}

// Inizializza SortableJS per gli incarichi
document.addEventListener('DOMContentLoaded', function() {
    const incarichiTbody = document.getElementById('incarichiTableBody');
    if (incarichiTbody && typeof Sortable !== 'undefined') {
        new Sortable(incarichiTbody, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const rows = incarichiTbody.querySelectorAll('tr[data-id]');
                const ordini = [];
                rows.forEach(function(row) {
                    ordini.push(row.dataset.id);
                });
                
                // Salva l'ordine
                fetch('{{ route("gestione-anagrafica-config.update-order-incarichi") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ordini: ordini })
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && window.SUGECO && window.SUGECO.Toast) {
                        SUGECO.Toast.success('Ordine aggiornato!');
                    }
                })
                .catch(function(error) {
                    console.error('Errore salvataggio ordine:', error);
                });
            }
        });
    }
    
    // Ripristina tab salvato
    const savedTab = localStorage.getItem('anagraficaConfigTab');
    if (savedTab && ['incarichi', 'campi'].includes(savedTab)) {
        switchTabAnagrafica(savedTab);
    }
});
</script>
@endpush

