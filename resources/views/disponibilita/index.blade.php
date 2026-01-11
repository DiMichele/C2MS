@extends('layouts.app')

@section('title', 'Disponibilità Personale - SUGECO')

@section('content')
<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 pt-3">
        <h1 class="h3 mb-0 text-dark fw-bold">Disponibilità Personale</h1>
    </div>

    <!-- Filtri -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row align-items-end g-3">
                <div class="col-auto">
                    <label class="form-label small text-muted mb-1">Mese</label>
                    <select name="mese" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 140px;">
                        @foreach($nomiMesi as $num => $nome)
                            <option value="{{ $num }}" {{ $mese == $num ? 'selected' : '' }}>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted mb-1">Anno</label>
                    <select name="anno" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px;">
                        @for($a = 2025; $a <= 2030; $a++)
                            <option value="{{ $a }}" {{ $anno == $a ? 'selected' : '' }}>{{ $a }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small text-muted mb-1">Ufficio</label>
                    <select name="polo_id" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 180px;">
                        <option value="">Tutti</option>
                        @foreach($poli as $polo)
                            <option value="{{ $polo->id }}" {{ $poloId == $polo->id ? 'selected' : '' }}>{{ $polo->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto ms-auto">
                    <div class="d-flex gap-2 align-items-center small">
                        <span class="badge bg-success">Alto (&gt;70%)</span>
                        <span class="badge bg-warning text-dark">Medio (40-70%)</span>
                        <span class="badge bg-danger">Basso (&lt;40%)</span>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabella Disponibilità -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: calc(100vh - 280px);">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-dark sticky-top">
                        <tr>
                            <th style="min-width: 180px; position: sticky; left: 0; background-color: #212529; z-index: 10;">
                                Ufficio
                            </th>
                            <th class="text-center" style="min-width: 50px;">Tot.</th>
                            @foreach($giorniMese as $giorno)
                                <th class="text-center {{ $giorno['is_weekend'] || $giorno['is_holiday'] ? 'bg-secondary' : '' }}" 
                                    style="min-width: 45px; {{ $giorno['is_today'] ? 'background-color: #ffc107 !important; color: #000;' : '' }}">
                                    <div class="fw-bold">{{ $giorno['giorno'] }}</div>
                                    <small class="text-uppercase" style="font-size: 0.65rem;">{{ $giorno['nome_giorno'] }}</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($disponibilitaPerPolo as $poloData)
                            @if($poloData['totale_militari'] > 0)
                            <tr>
                                <td class="fw-medium" style="position: sticky; left: 0; background-color: #fff; z-index: 5;">
                                    {{ $poloData['polo']->nome }}
                                </td>
                                <td class="text-center bg-light fw-bold">
                                    {{ $poloData['totale_militari'] }}
                                </td>
                                @foreach($giorniMese as $giorno)
                                    @php
                                        $datiGiorno = $poloData['giorni'][$giorno['giorno']];
                                        $percentuale = $datiGiorno['percentuale_liberi'];
                                        
                                        if ($percentuale >= 70) {
                                            $bgColor = 'rgba(40, 167, 69, 0.25)';
                                            $textColor = '#155724';
                                        } elseif ($percentuale >= 40) {
                                            $bgColor = 'rgba(255, 193, 7, 0.25)';
                                            $textColor = '#856404';
                                        } else {
                                            $bgColor = 'rgba(220, 53, 69, 0.25)';
                                            $textColor = '#721c24';
                                        }
                                        
                                        if ($giorno['is_weekend'] || $giorno['is_holiday']) {
                                            $bgColor = 'rgba(108, 117, 125, 0.1)';
                                        }
                                    @endphp
                                    <td class="text-center disponibilita-cell" 
                                        style="background-color: {{ $bgColor }}; cursor: pointer;"
                                        data-polo-id="{{ $poloData['polo']->id }}"
                                        data-data="{{ $anno }}-{{ str_pad($mese, 2, '0', STR_PAD_LEFT) }}-{{ str_pad($giorno['giorno'], 2, '0', STR_PAD_LEFT) }}"
                                        data-liberi="{{ $datiGiorno['liberi'] }}"
                                        data-impegnati="{{ $datiGiorno['impegnati'] }}"
                                        data-totale="{{ $datiGiorno['totale'] }}"
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
    </div>
</div>

<!-- Modal Dettaglio -->
<div class="modal fade" id="dettaglioGiornoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title mb-0">Dettaglio Disponibilità</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="dettaglioGiornoContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted small">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.table th {
    font-size: 0.75rem;
    font-weight: 600;
}

.table td {
    font-size: 0.85rem;
    vertical-align: middle;
}

.disponibilita-cell:hover {
    opacity: 0.8;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 2;
}

/* Modal Styles */
.modal-header {
    border-bottom: 1px solid #dee2e6;
}

.riepilogo-box {
    text-align: center;
    padding: 16px;
    border-radius: 6px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.riepilogo-box .numero {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1;
}

.riepilogo-box .etichetta {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    margin-top: 4px;
}

.riepilogo-box.liberi { border-left: 3px solid #28a745; }
.riepilogo-box.impegnati { border-left: 3px solid #dc3545; }
.riepilogo-box.totale { border-left: 3px solid #6c757d; }

.riepilogo-box.liberi .numero { color: #28a745; }
.riepilogo-box.impegnati .numero { color: #dc3545; }
.riepilogo-box.totale .numero { color: #212529; }

/* Tabs semplici */
.nav-tabs-simple {
    border-bottom: 2px solid #e9ecef;
    gap: 0;
}

.nav-tabs-simple .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    padding: 10px 20px;
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
    background: none;
    border-radius: 0;
}

.nav-tabs-simple .nav-link:hover {
    color: #212529;
    border-bottom-color: #dee2e6;
}

.nav-tabs-simple .nav-link.active {
    color: #212529;
    border-bottom-color: #212529;
}

/* Lista militari */
.militari-table {
    font-size: 0.85rem;
}

.militari-table th {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
    border-bottom-width: 1px;
    padding: 8px 12px;
}

.militari-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}

.militari-table tbody tr:hover {
    background-color: #f9fafb;
}

.empty-msg {
    text-align: center;
    padding: 30px;
    color: #6c757d;
}

/* Scrollbar */
.lista-scroll::-webkit-scrollbar {
    width: 5px;
}

.lista-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.lista-scroll::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 3px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('dettaglioGiornoModal');
    const modalContent = document.getElementById('dettaglioGiornoContent');
    const modal = new bootstrap.Modal(modalElement);
    
    const loadingHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 text-muted small">Caricamento...</p>
        </div>
    `;
    
    document.querySelectorAll('.disponibilita-cell').forEach(function(cell) {
        cell.addEventListener('click', function() {
            const poloId = this.dataset.poloId;
            const data = this.dataset.data;
            
            modalContent.innerHTML = loadingHTML;
            modal.show();
            
            fetch(`{{ url('disponibilita/militari-liberi') }}?data=${data}&polo_id=${poloId}`)
                .then(response => response.json())
                .then(responseData => {
                    if (responseData.success) {
                        const percentuale = responseData.totale > 0 ? Math.round((responseData.liberi / responseData.totale) * 100) : 0;
                        
                        let html = `
                            <!-- Riepilogo numerico -->
                            <div class="row g-3 mb-4">
                                <div class="col-4">
                                    <div class="riepilogo-box liberi">
                                        <div class="numero">${responseData.liberi}</div>
                                        <div class="etichetta">Liberi</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="riepilogo-box impegnati">
                                        <div class="numero">${responseData.impegnati}</div>
                                        <div class="etichetta">Impegnati</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="riepilogo-box totale">
                                        <div class="numero">${responseData.totale}</div>
                                        <div class="etichetta">Totale</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barra disponibilità -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Disponibilità</small>
                                    <small class="fw-bold">${percentuale}%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-${percentuale >= 70 ? 'success' : percentuale >= 40 ? 'warning' : 'danger'}" 
                                         style="width: ${percentuale}%"></div>
                                </div>
                            </div>
                            
                            <!-- Tabs -->
                            <ul class="nav nav-tabs-simple mb-3" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-liberi">
                                        Liberi (${responseData.liberi})
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-impegnati">
                                        Impegnati (${responseData.impegnati})
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Liberi -->
                                <div class="tab-pane fade show active" id="tab-liberi">
                                    <div class="lista-scroll" style="max-height: 280px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_liberi && responseData.militari_liberi.length > 0) {
                            html += `
                                <table class="table militari-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Ufficio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            responseData.militari_liberi.forEach(function(m) {
                                html += `
                                    <tr>
                                        <td class="fw-medium">${m.nome_completo}</td>
                                        <td class="text-muted">${m.polo || '-'}</td>
                                    </tr>
                                `;
                            });
                            html += `</tbody></table>`;
                        } else {
                            html += `<div class="empty-msg">Nessun militare libero</div>`;
                        }
                        
                        html += `
                                    </div>
                                </div>
                                
                                <!-- Impegnati -->
                                <div class="tab-pane fade" id="tab-impegnati">
                                    <div class="lista-scroll" style="max-height: 280px; overflow-y: auto;">
                        `;
                        
                        if (responseData.militari_impegnati && responseData.militari_impegnati.length > 0) {
                            html += `
                                <table class="table militari-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Ufficio</th>
                                            <th>Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            responseData.militari_impegnati.forEach(function(m) {
                                html += `
                                    <tr>
                                        <td class="fw-medium">${m.nome_completo}</td>
                                        <td class="text-muted">${m.polo || '-'}</td>
                                        <td>
                                            <span class="badge bg-secondary">${m.codice || m.fonte}</span>
                                            <small class="text-muted ms-1">${m.motivo || ''}</small>
                                        </td>
                                    </tr>
                                `;
                            });
                            html += `</tbody></table>`;
                        } else {
                            html += `<div class="empty-msg">Nessun militare impegnato</div>`;
                        }
                        
                        html += `
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        modalContent.innerHTML = html;
                    } else {
                        modalContent.innerHTML = `
                            <div class="alert alert-warning mb-0">
                                ${responseData.message || 'Nessun dato disponibile'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Errore:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger mb-0">Errore nel caricamento. Riprova.</div>
                    `;
                });
        });
    });
    
    modalElement.addEventListener('hidden.bs.modal', function () {
        modalContent.innerHTML = loadingHTML;
    });
});
</script>
@endsection
