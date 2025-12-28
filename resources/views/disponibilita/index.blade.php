@extends('layouts.app')

@section('title', 'Disponibilità Personale - SUGECO')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="text-center mb-4">
        <h1 class="page-title">Disponibilità Personale</h1>
        <p class="text-muted">Panoramica del personale libero per polo e giornata</p>
    </div>

    <!-- Selettori -->
    <div class="d-flex justify-content-center mb-4">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px; border-radius: 6px !important;">
                @foreach($nomiMesi as $num => $nome)
                    <option value="{{ $num }}" {{ $mese == $num ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
            <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px; border-radius: 6px !important;">
                @for($a = 2025; $a <= 2030; $a++)
                    <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endfor
            </select>
            
            <span class="mx-2">|</span>
            
            <select name="polo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 200px; border-radius: 6px !important;">
                <option value="">Tutti i Poli</option>
                @foreach($poli as $polo)
                    <option value="{{ $polo->id }}" {{ $poloId == $polo->id ? 'selected' : '' }}>
                        {{ $polo->nome }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Legenda -->
    <div class="d-flex justify-content-center mb-3">
        <div class="d-flex gap-3 align-items-center">
            <span class="badge" style="background-color: #28a745;">Alto (>70%)</span>
            <span class="badge" style="background-color: #ffc107; color: #000;">Medio (40-70%)</span>
            <span class="badge" style="background-color: #dc3545;">Basso (<40%)</span>
        </div>
    </div>

    <!-- Tabella Disponibilità -->
    <div class="table-container" style="overflow-x: auto;">
        <table class="table table-sm table-bordered mb-0" id="disponibilitaTable">
            <thead style="background-color: #0a2342; color: white;">
                <tr>
                    <th style="min-width: 200px; position: sticky; left: 0; background-color: #0a2342; z-index: 10;">
                        Polo/Ufficio
                    </th>
                    <th class="text-center" style="min-width: 60px;">Tot.</th>
                    @foreach($giorniMese as $giorno)
                        <th class="text-center {{ $giorno['is_weekend'] || $giorno['is_holiday'] ? 'bg-danger' : '' }}" 
                            style="min-width: 50px; {{ $giorno['is_today'] ? 'background-color: #ffc107 !important; color: #000;' : '' }}">
                            <div style="font-weight: 700;">{{ $giorno['giorno'] }}</div>
                            <small>{{ $giorno['nome_giorno'] }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($disponibilitaPerPolo as $poloData)
                    @if($poloData['totale_militari'] > 0)
                    <tr>
                        <td style="position: sticky; left: 0; background-color: #fff; z-index: 5; font-weight: 600;">
                            {{ $poloData['polo']->nome }}
                        </td>
                        <td class="text-center" style="background-color: rgba(10, 35, 66, 0.1);">
                            <strong>{{ $poloData['totale_militari'] }}</strong>
                        </td>
                        @foreach($giorniMese as $giorno)
                            @php
                                $datiGiorno = $poloData['giorni'][$giorno['giorno']];
                                $percentuale = $datiGiorno['percentuale_liberi'];
                                
                                // Determina il colore in base alla percentuale di liberi
                                if ($percentuale >= 70) {
                                    $bgColor = 'rgba(40, 167, 69, 0.3)';
                                    $textColor = '#155724';
                                } elseif ($percentuale >= 40) {
                                    $bgColor = 'rgba(255, 193, 7, 0.3)';
                                    $textColor = '#856404';
                                } else {
                                    $bgColor = 'rgba(220, 53, 69, 0.3)';
                                    $textColor = '#721c24';
                                }
                                
                                // Weekend/festivi più chiari
                                if ($giorno['is_weekend'] || $giorno['is_holiday']) {
                                    $bgColor = 'rgba(220, 53, 69, 0.08)';
                                }
                            @endphp
                            <td class="text-center disponibilita-cell" 
                                style="background-color: {{ $bgColor }}; cursor: pointer;"
                                data-polo-id="{{ $poloData['polo']->id }}"
                                data-data="{{ $anno }}-{{ str_pad($mese, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($giorno['giorno'], 2, '0', STR_PAD_LEFT) }}"
                                data-liberi="{{ $datiGiorno['liberi'] }}"
                                data-impegnati="{{ $datiGiorno['impegnati'] }}"
                                data-totale="{{ $datiGiorno['totale'] }}"
                                data-bs-toggle="tooltip"
                                title="Liberi: {{ $datiGiorno['liberi'] }} / Impegnati: {{ $datiGiorno['impegnati'] }}">
                                <strong style="color: {{ $textColor }};">{{ $datiGiorno['liberi'] }}</strong>
                            </td>
                        @endforeach
                    </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Dettaglio Giorno -->
<div class="modal fade" id="dettaglioGiornoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-users me-2"></i>Dettaglio Disponibilità
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dettaglioGiornoContent">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--navy);
    margin: 0;
}

.table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    max-height: calc(100vh - 350px);
    overflow: auto;
}

.disponibilita-cell:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 5;
    position: relative;
}

.table tbody tr:hover {
    background-color: rgba(10, 35, 66, 0.05) !important;
}

.militare-libero-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.militare-libero-item:last-child {
    border-bottom: none;
}

.militare-libero-item:hover {
    background-color: rgba(10, 35, 66, 0.05);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inizializza tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Click su cella per vedere dettaglio
    document.querySelectorAll('.disponibilita-cell').forEach(function(cell) {
        cell.addEventListener('click', function() {
            const poloId = this.dataset.poloId;
            const data = this.dataset.data;
            const liberi = this.dataset.liberi;
            const impegnati = this.dataset.impegnati;
            const totale = this.dataset.totale;
            
            // Mostra modal
            const modal = new bootstrap.Modal(document.getElementById('dettaglioGiornoModal'));
            modal.show();
            
            // Carica dettagli via AJAX
            fetch(`{{ url('disponibilita/militari-liberi') }}?data=${data}&polo_id=${poloId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = `
                            <div class="mb-3">
                                <h6 class="text-muted">${data.data}</h6>
                                <div class="d-flex gap-3 mb-3">
                                    <span class="badge bg-success fs-6">Liberi: ${data.liberi}</span>
                                    <span class="badge bg-danger fs-6">Impegnati: ${data.impegnati}</span>
                                    <span class="badge bg-secondary fs-6">Totale: ${data.totale}</span>
                                </div>
                            </div>
                            <h6>Personale Libero:</h6>
                            <div class="list-group">
                        `;
                        
                        if (data.militari_liberi.length > 0) {
                            data.militari_liberi.forEach(function(m) {
                                html += `
                                    <div class="militare-libero-item">
                                        <span><strong>${m.nome_completo}</strong></span>
                                        <span class="text-muted">${m.polo} - ${m.compagnia}</span>
                                    </div>
                                `;
                            });
                        } else {
                            html += '<div class="text-center text-muted py-3">Nessun militare libero in questo giorno</div>';
                        }
                        
                        html += '</div>';
                        document.getElementById('dettaglioGiornoContent').innerHTML = html;
                    }
                })
                .catch(error => {
                    document.getElementById('dettaglioGiornoContent').innerHTML = `
                        <div class="alert alert-danger">Errore nel caricamento dei dati</div>
                    `;
                });
        });
    });
});
</script>
@endsection

