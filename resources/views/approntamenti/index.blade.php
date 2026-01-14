@extends('layouts.app')
@section('title', 'Approntamenti - SUGECO')

@section('content')
<style>
/* Effetto hover sulle righe */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

/* Bordi squadrati */
.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
}

.form-control, .form-select {
    border-radius: 0 !important;
}

.filter-select {
    border-radius: 0 !important;
}

.table-container {
    overflow-x: auto !important;
    overflow-y: auto !important;
}

.table-header-fixed table,
.table-body-scroll table {
    table-layout: fixed !important;
}

.table tbody tr {
    background-color: #fafafa;
}

.table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

.table-bordered > :not(caption) > * > * {
    border-color: rgba(10, 35, 66, 0.20) !important;
}

.scadenza-cell {
    cursor: pointer;
    transition: all 0.2s;
    padding: 8px !important;
    font-size: 0.85rem;
}

.scadenza-cell:hover {
    opacity: 0.8;
}

.scadenza-cell.readonly {
    cursor: default;
    opacity: 0.9;
}

.scadenza-cell.readonly:hover {
    opacity: 0.9;
}

.link-name {
    color: #0a2342;
    text-decoration: none;
    position: relative;
}

.link-name:hover {
    color: #0a2342;
    text-decoration: none;
}

.link-name::after {
    content: '';
    position: absolute;
    width: 0;
    height: 2px;
    bottom: -2px;
    left: 0;
    background-color: #d4af37;
    transition: width 0.3s ease;
}

.link-name:hover::after {
    width: 100%;
}

/* Colore Non Richiesto */
.stato-non-richiesto {
    background-color: #e2e3e5;
    color: #6c757d;
}

/* Stili tabella larga */
.table-wide {
    width: 4800px;
    min-width: 4800px;
}

/* Badge SYNC sotto il nome colonna */
.sync-label {
    display: block;
    font-size: 0.6rem;
    color: #d4af37;
    font-weight: 600;
    margin-top: 2px;
    letter-spacing: 0.5px;
}

/* Filtri container - VISIBILITA */
#filtersContainer {
    display: none;
    overflow: hidden;
}

#filtersContainer.show {
    display: block;
}

/* Filter card */
.filter-card {
    border: 1px solid rgba(10, 35, 66, 0.15);
    border-radius: 0;
    background: #fff;
}

.filter-card-header {
    background-color: #0a2342;
    color: white;
    padding: 10px 15px;
    font-weight: 500;
}

/* Select wrapper per clear button */
.select-wrapper {
    position: relative;
}

.select-wrapper .clear-filter {
    position: absolute;
    right: 30px;
    top: 50%;
    transform: translateY(-50%);
    color: #dc3545;
    cursor: pointer;
    z-index: 5;
}

.filter-select.applied {
    border-color: #d4af37;
    box-shadow: 0 0 0 1px #d4af37;
}
</style>

@php
    use App\Models\ScadenzaApprontamento;
    
    $colonneLabels = ScadenzaApprontamento::getLabels();
    
    $activeFilters = [];
    foreach($colonne as $campo => $config) {
        if(request()->filled($campo) && request($campo) != 'tutti') $activeFilters[] = $campo;
    }
    $hasActiveFilters = count($activeFilters) > 0;
@endphp

<!-- Header -->
<div class="text-center mb-4">
    <h1 class="page-title">Approntamenti</h1>
</div>

<!-- Barra di ricerca -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchMilitare" 
            class="form-control" 
            placeholder="Cerca militare..." 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Pulsanti azioni -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <button type="button" id="btnToggleFilters" class="btn btn-primary {{ $hasActiveFilters ? 'active' : '' }}" style="border-radius: 6px !important;">
            <i class="fas fa-filter me-2"></i> 
            <span id="btnToggleFiltersText">{{ $hasActiveFilters ? 'Nascondi filtri' : 'Mostra filtri' }}</span>
        </button>
    </div>
    <div>
        <a href="{{ route('approntamenti.export-excel') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}" class="btn btn-success" style="border-radius: 6px !important;">
            <i class="fas fa-file-excel me-2"></i> Esporta Excel
        </a>
    </div>
</div>

<!-- Filtri -->
<div id="filtersContainer" class="{{ $hasActiveFilters ? 'show' : '' }}">
    <div class="filter-card mb-4">
        <div class="filter-card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-filter me-2"></i> Filtri avanzati
            </div>
        </div>
        <div class="card-body p-3">
            <form id="filtroForm" action="{{ route('approntamenti.index') }}" method="GET">
                @php
                    $colonneArray = array_keys($colonne);
                    $chunks = array_chunk($colonneArray, 4);
                @endphp
                
                @foreach($chunks as $chunk)
                <div class="row mb-3">
                    @foreach($chunk as $campo)
                    <div class="col-md-3">
                        <label for="filter_{{ $campo }}" class="form-label">{{ $colonneLabels[$campo] ?? $campo }}</label>
                        <div class="select-wrapper">
                            <select name="{{ $campo }}" id="filter_{{ $campo }}" class="form-select filter-select {{ (request($campo) && request($campo) != 'tutti') ? 'applied' : '' }}">
                                <option value="tutti" {{ (request($campo, 'tutti') == 'tutti') ? 'selected' : '' }}>Tutti</option>
                                <option value="validi" {{ request($campo) == 'validi' ? 'selected' : '' }}>Validi</option>
                                <option value="in_scadenza" {{ request($campo) == 'in_scadenza' ? 'selected' : '' }}>In Scadenza</option>
                                <option value="scaduti" {{ request($campo) == 'scaduti' ? 'selected' : '' }}>Scaduti / Non presenti</option>
                                <option value="non_richiesti" {{ request($campo) == 'non_richiesti' ? 'selected' : '' }}>Non richiesti</option>
                            </select>
                            @if(request($campo) && request($campo) != 'tutti')
                                <span class="clear-filter" data-filter="filter_{{ $campo }}" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endforeach
                
                <div class="d-flex justify-content-center mt-3">
                    @if($hasActiveFilters)
                    <a href="{{ route('approntamenti.index') }}" class="btn btn-danger">
                        <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri ({{ count($activeFilters) }})
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabella -->
<div class="table-container" style="position: relative; max-height: calc(100vh - 320px); overflow: auto;">
    <!-- Intestazione fissa -->
    <div class="table-header-fixed" style="position: sticky; top: 0; z-index: 10; background: white;">
        <table class="table table-sm table-bordered mb-0 table-wide">
            <colgroup>
                <col style="width:70px">
                <col style="width:150px">
                <col style="width:120px">
                @foreach($colonne as $campo => $config)
                <col style="width:{{ strlen($colonneLabels[$campo] ?? $campo) > 20 ? '180px' : '140px' }}">
                @endforeach
            </colgroup>
            <thead class="table-dark" style="user-select:none;">
                <tr>
                    <th class="text-center">Grado</th>
                    <th class="text-center">Cognome</th>
                    <th class="text-center">Nome</th>
                    @foreach($colonne as $campo => $config)
                    <th class="text-center" style="font-size: 0.75rem; line-height: 1.2;">
                        {{ $colonneLabels[$campo] ?? $campo }}
                        @if(ScadenzaApprontamento::isColonnaCondivisa($campo))
                        <span class="sync-label">SYNC</span>
                        @endif
                    </th>
                    @endforeach
                </tr>
            </thead>
        </table>
    </div>
    
    <!-- Corpo scrollabile -->
    <div class="table-body-scroll">
        <table class="table table-sm table-bordered mb-0 table-wide">
            <colgroup>
                <col style="width:70px">
                <col style="width:150px">
                <col style="width:120px">
                @foreach($colonne as $campo => $config)
                <col style="width:{{ strlen($colonneLabels[$campo] ?? $campo) > 20 ? '180px' : '140px' }}">
                @endforeach
            </colgroup>
            <tbody id="militariTableBody">
            @forelse($militari as $m)
                @php
                    $scadenza = $m->scadenzaApprontamento;
                @endphp
                <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}">
                    <td class="text-center">{{ $m->grado->sigla ?? '-' }}</td>
                    <td>
                        <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                            {{ $m->cognome }}
                        </a>
                    </td>
                    <td>{{ $m->nome }}</td>
                    
                    @foreach($colonne as $campo => $config)
                    @php
                        $isSync = ScadenzaApprontamento::isColonnaCondivisa($campo);
                        $valore = $scadenza ? $scadenza->getValoreFormattato($campo) : '-';
                        $valoreRaw = $scadenza ? ($scadenza->$campo ?? '') : '';
                        $colore = $scadenza ? $scadenza->getColore($campo) : 'background-color: #f8f9fa; color: #6c757d;';
                    @endphp
                    <td class="text-center scadenza-cell {{ $isSync ? 'readonly' : '' }}" 
                        style="{{ $colore }}"
                        @if($canEdit && !$isSync)
                        data-militare-id="{{ $m->id }}"
                        data-campo="{{ $campo }}"
                        onclick="apriModalData({{ $m->id }}, '{{ $campo }}', '{{ $valoreRaw }}')"
                        @endif
                        @if($isSync)
                        title="Modificabile dalla pagina SPP/Idoneità"
                        @endif>
                        {{ $valore }}
                    </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($colonne) }}" class="text-center text-muted py-4">
                        Nessun militare trovato
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Totale -->
<div class="mt-3 text-center text-muted">
    Totale: <strong>{{ count($militari) }}</strong> militari
</div>

<!-- Modal per modifica data -->
<div class="modal fade" id="modalData" tabindex="-1" aria-labelledby="modalDataLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #0a2342; color: white;">
                <h5 class="modal-title" id="modalDataLabel">Modifica Valore</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="inputValore" class="form-label">Data o "Non richiesto" (NR)</label>
                    <input type="text" class="form-control" id="inputValore" placeholder="dd/mm/yyyy o NR">
                    <div class="form-text">Inserisci una data nel formato dd/mm/yyyy oppure "NR" per Non Richiesto. Lascia vuoto per cancellare.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btnSalvaValore">
                    <i class="fas fa-save me-1"></i> Salva
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let modalMilitareId = null;
    let modalCampo = null;

    // Toggle filtri
    const btnToggle = document.getElementById('btnToggleFilters');
    const filtersContainer = document.getElementById('filtersContainer');
    const btnText = document.getElementById('btnToggleFiltersText');
    
    if (btnToggle && filtersContainer) {
        btnToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (filtersContainer.classList.contains('show')) {
                filtersContainer.classList.remove('show');
                btnToggle.classList.remove('active');
                btnText.textContent = 'Mostra filtri';
            } else {
                filtersContainer.classList.add('show');
                btnToggle.classList.add('active');
                btnText.textContent = 'Nascondi filtri';
            }
        });
    }

    // Submit automatico filtri
    document.querySelectorAll('.filter-select').forEach(function(select) {
        select.addEventListener('change', function() {
            document.getElementById('filtroForm').submit();
        });
    });

    // Clear singolo filtro
    document.querySelectorAll('.clear-filter').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filterId = this.dataset.filter;
            const select = document.getElementById(filterId);
            if (select) {
                select.value = 'tutti';
                document.getElementById('filtroForm').submit();
            }
        });
    });

    // Ricerca
    const searchInput = document.getElementById('searchMilitare');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#militariTableBody tr.militare-row').forEach(function(row) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Modal per modifica
    window.apriModalData = function(militareId, campo, valoreAttuale) {
        modalMilitareId = militareId;
        modalCampo = campo;
        
        let displayValue = '';
        if (valoreAttuale) {
            if (valoreAttuale === 'NR') {
                displayValue = 'NR';
            } else {
                try {
                    const date = new Date(valoreAttuale);
                    if (!isNaN(date.getTime())) {
                        displayValue = date.toLocaleDateString('it-IT', {day: '2-digit', month: '2-digit', year: 'numeric'});
                    } else {
                        displayValue = valoreAttuale;
                    }
                } catch (e) {
                    displayValue = valoreAttuale;
                }
            }
        }
        
        document.getElementById('inputValore').value = displayValue;
        
        const colonneLabels = @json(ScadenzaApprontamento::getLabels());
        document.getElementById('modalDataLabel').textContent = 'Modifica ' + (colonneLabels[campo] || campo);
        
        const modal = new bootstrap.Modal(document.getElementById('modalData'));
        modal.show();
    };

    // Salva valore
    document.getElementById('btnSalvaValore').addEventListener('click', function() {
        const valore = document.getElementById('inputValore').value.trim();
        const btn = this;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Salvataggio...';
        
        fetch('{{ route("approntamenti.update-singola", ["militare" => ":id"]) }}'.replace(':id', modalMilitareId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                campo: modalCampo,
                valore: valore
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.getElementById('militare-' + modalMilitareId);
                const cells = row.querySelectorAll('td');
                const colonneKeys = @json(array_keys($colonne));
                const cellIndex = colonneKeys.indexOf(modalCampo) + 3;
                
                if (cells[cellIndex]) {
                    cells[cellIndex].textContent = data.valore;
                    cells[cellIndex].style.cssText = data.colore;
                }
                
                bootstrap.Modal.getInstance(document.getElementById('modalData')).hide();
                
                if (typeof showToast === 'function') {
                    showToast('success', data.message);
                }
            } else {
                alert('Errore: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Errore:', error);
            alert('Errore durante il salvataggio');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> Salva';
        });
    });
});
</script>
@endpush
