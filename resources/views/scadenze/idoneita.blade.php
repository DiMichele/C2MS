@extends('layouts.app')

@section('title', 'Idoneità Sanitarie')

@section('content')
<style>
/* Stili uniformi come le altre pagine */
.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

.table tbody tr:hover td {
    background-color: transparent !important;
}

.table-bordered td, 
table.table td, 
.table td {
    border-radius: 0 !important;
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

/* Badge scadenze */
.badge-scadenza {
    padding: 6px 12px;
    font-weight: 600;
    font-size: 0.85rem;
    border-radius: 4px;
    display: inline-block;
    min-width: 100px;
    text-align: center;
}

.badge-valido {
    background-color: #28a745;
    color: white;
}

.badge-in-scadenza {
    background-color: #ffc107;
    color: #000;
}

.badge-scaduto {
    background-color: #dc3545;
    color: white;
}

.badge-mancante {
    background-color: #6c757d;
    color: white;
}

/* Colonna sticky per militare */
.table th:first-child,
.table td:first-child {
    position: sticky;
    left: 0;
    background-color: #e9ecef;
    z-index: 5;
    font-weight: 600;
}

.table thead th:first-child {
    background-color: #0a2342 !important;
    z-index: 15;
}

.table tbody tr:nth-of-type(odd) td:first-child {
    background-color: #ffffff;
}

.table tbody tr:nth-of-type(even) td:first-child {
    background-color: #fafafa;
}

.table tbody tr:hover td:first-child {
    background-color: rgba(10, 35, 66, 0.12) !important;
}

/* Input date styling */
.input-data-scadenza {
    border: none;
    background: transparent;
    font-size: 0.85rem;
    padding: 4px 8px;
    text-align: center;
    cursor: pointer;
    width: 110px;
}

.input-data-scadenza:focus {
    outline: 2px solid #0a2342;
    background: white;
}

/* Link militare con effetto gold */
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
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">IDONEITÀ SANITARIE</h1>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="badge bg-primary">{{ $data->count() }} militari</span>
    </div>
    <div class="d-flex gap-2">
        <span class="badge badge-valido"><i class="fas fa-check"></i> Valido</span>
        <span class="badge badge-in-scadenza"><i class="fas fa-exclamation-triangle"></i> In Scadenza (≤30gg)</span>
        <span class="badge badge-scaduto"><i class="fas fa-times"></i> Scaduto</span>
        <span class="badge badge-mancante"><i class="fas fa-minus"></i> Mancante</span>
    </div>
</div>

<!-- Tabella con scroll orizzontale -->
<div class="table-responsive" style="max-height: 70vh; overflow: auto;">
    <table class="table table-sm table-bordered table-hover mb-0">
        <thead style="position: sticky; top: 0; z-index: 10;">
            <tr style="background-color: #0a2342; color: white;">
                <th style="min-width: 200px;">Militare</th>
                <th style="min-width: 180px;">Idoneità alla Mansione</th>
                <th style="min-width: 150px;">Idoneità SMI</th>
                <th style="min-width: 150px;">ECG</th>
                <th style="min-width: 150px;">Prelievi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $item)
            <tr data-militare-id="{{ $item['militare']->id }}">
                <td>
                    <a href="{{ route('anagrafica.show', $item['militare']->id) }}" class="link-name">
                        {{ $item['militare']->grado->abbreviazione ?? '' }} 
                        {{ $item['militare']->cognome }} 
                        {{ $item['militare']->nome }}
                    </a>
                </td>
                
                @foreach(['idoneita_mansione', 'idoneita_smi', 'ecg', 'prelievi'] as $campo)
                <td class="text-center" data-campo="{{ $campo }}">
                    @php
                        $scadenza = $item[$campo];
                        $badgeClass = match($scadenza['stato']) {
                            'valido' => 'badge-valido',
                            'in_scadenza' => 'badge-in-scadenza',
                            'scaduto' => 'badge-scaduto',
                            'mancante' => 'badge-mancante',
                            default => 'badge-mancante'
                        };
                        $testoStato = match($scadenza['stato']) {
                            'valido' => 'Valido',
                            'in_scadenza' => 'In scadenza',
                            'scaduto' => 'Scaduto',
                            'mancante' => 'Non presente',
                            default => 'N/D'
                        };
                        // Map campo to database field
                        $campoDB = match($campo) {
                            'idoneita_mansione' => 'idoneita_mans_data_conseguimento',
                            'idoneita_smi' => 'idoneita_smi_data_conseguimento',
                            'ecg' => 'ecg_data_conseguimento',
                            'prelievi' => 'prelievi_data_conseguimento',
                            default => $campo . '_data_conseguimento'
                        };
                    @endphp
                    
                    @can('scadenze.edit')
                    <input type="date" 
                           class="input-data-scadenza editable-scadenza" 
                           value="{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('Y-m-d') : '' }}"
                           data-campo="{{ $campoDB }}"
                           data-militare-id="{{ $item['militare']->id }}">
                    @else
                    <div>{{ $scadenza['data_conseguimento'] ? $scadenza['data_conseguimento']->format('d/m/Y') : '-' }}</div>
                    @endcan
                    
                    <div class="mt-1">
                        <span class="badge badge-scadenza {{ $badgeClass }}">
                            {{ $testoStato }}
                            @if($scadenza['data_scadenza'])
                                <br><small>{{ $scadenza['data_scadenza']->format('d/m/Y') }}</small>
                            @endif
                        </span>
                    </div>
                </td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Floating Button Export Excel -->
<button type="button" class="fab fab-excel" id="exportExcel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</button>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione modifica inline date
    @can('scadenze.edit')
    document.querySelectorAll('.editable-scadenza').forEach(input => {
        input.addEventListener('change', function() {
            const militareId = this.dataset.militareId;
            const campo = this.dataset.campo;
            const nuovaData = this.value;
            const cell = this.closest('td');
            
            // Feedback visivo
            cell.style.background = '#ffffcc';
            
            fetch(`/scadenze/idoneita/${militareId}/update-singola`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    campo: campo,
                    data: nuovaData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Aggiorna il badge
                    const badge = cell.querySelector('.badge-scadenza');
                    const scadenza = data.scadenza;
                    
                    // Rimuovi classi vecchie
                    badge.classList.remove('badge-valido', 'badge-in-scadenza', 'badge-scaduto', 'badge-mancante');
                    
                    // Aggiungi classe nuova
                    const badgeClass = {
                        'valido': 'badge-valido',
                        'in_scadenza': 'badge-in-scadenza',
                        'scaduto': 'badge-scaduto',
                        'mancante': 'badge-mancante'
                    }[scadenza.stato] || 'badge-mancante';
                    
                    badge.classList.add(badgeClass);
                    
                    // Aggiorna testo
                    const testoStato = {
                        'valido': 'Valido',
                        'in_scadenza': 'In scadenza',
                        'scaduto': 'Scaduto',
                        'mancante': 'Non presente'
                    }[scadenza.stato] || 'N/D';
                    
                    let html = testoStato;
                    if (scadenza.data_scadenza) {
                        const dataScadenza = new Date(scadenza.data_scadenza.date);
                        html += `<br><small>${dataScadenza.toLocaleDateString('it-IT')}</small>`;
                    }
                    badge.innerHTML = html;
                    
                    // Feedback verde
                    cell.style.background = '#d4edda';
                    setTimeout(() => { cell.style.background = ''; }, 2000);
                } else {
                    cell.style.background = '#f8d7da';
                    setTimeout(() => { cell.style.background = ''; }, 2000);
                }
            })
            .catch(error => {
                console.error('Errore:', error);
                cell.style.background = '#f8d7da';
                setTimeout(() => { cell.style.background = ''; }, 2000);
            });
        });
    });
    @endcan
    
    // Export Excel
    document.getElementById('exportExcel').addEventListener('click', function() {
        window.location.href = '/scadenze/idoneita/export-excel';
    });
});
</script>
@endpush

