@extends('layouts.app')

@section('title', 'Gestione Ruolini - SUGECO')

@section('content')
<style>
/* =============================================
   GESTIONE RUOLINI - STILI PROFESSIONALI
   ============================================= */

/* Badge CPT Professionale - Come pagina CPT */
.codice-badge-ruolini {
    display: inline-block;
    padding: 8px 14px;
    font-weight: 700;
    border-radius: 6px;
    font-size: 0.95rem;
    min-width: 70px;
    text-align: center;
    font-family: 'Courier New', Consolas, monospace;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
    text-transform: uppercase;
    border: 1px solid rgba(255,255,255,0.2);
}

/* Container tabella - stili visivi */
/* (Scroll gestito da table-standard.css) */
.table-container-ruolini {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    /* overflow gestito da table-standard.css */
}

/* Righe con stato presente/assente */
.row-presente {
    background: linear-gradient(90deg, rgba(25, 135, 84, 0.08) 0%, rgba(255,255,255,0) 50%) !important;
    border-left: 4px solid #198754 !important;
}

.row-assente {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.08) 0%, rgba(255,255,255,0) 50%) !important;
    border-left: 4px solid #dc3545 !important;
}

/* Selettore Stato Migliorato con colori distintivi */
.stato-selector-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.stato-select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background: #fff;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23495057' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.6rem center;
    background-size: 10px;
    min-width: 120px;
}

/* Stato Presente - Verde */
.stato-select.stato-presente {
    background-color: #d1e7dd;
    border-color: #198754;
    color: #0f5132;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%230f5132' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
}

.stato-select.stato-presente:hover {
    background-color: #badbcc;
    border-color: #146c43;
}

/* Stato Assente - Rosso */
.stato-select.stato-assente {
    background-color: #f8d7da;
    border-color: #dc3545;
    color: #842029;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23842029' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
}

.stato-select.stato-assente:hover {
    background-color: #f1c4c8;
    border-color: #b02a37;
}

.stato-select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.15);
}


/* Accordion Categoria - Stile Ruolini */
.categoria-accordion {
    border-radius: 12px;
    border: 1px solid #dee2e6;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 1rem;
}

.categoria-accordion-header {
    width: 100%;
    text-align: left;
    border: none;
    background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 12px;
}

.categoria-accordion-header:hover {
    background: linear-gradient(135deg, #0d2a4d 0%, #1f4268 100%);
}

.categoria-accordion-header:focus {
    outline: none;
}

.categoria-accordion-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}

.categoria-accordion-header .badge {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 12px;
}

.categoria-accordion-icon {
    color: #fff;
    transition: transform 0.2s ease;
    font-size: 0.9rem;
}

.categoria-accordion-header[aria-expanded="true"] .categoria-accordion-icon {
    transform: rotate(180deg);
}

.categoria-accordion-header[aria-expanded="true"] {
    border-radius: 12px 12px 0 0;
}

/* Contenuto accordion */
.categoria-content {
    background: #fff;
}

.categoria-content .sugeco-table {
    margin-bottom: 0;
    border-radius: 0;
}

.categoria-content .sugeco-table thead th {
    background: #e9ecef;
    color: #495057;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px;
    border-top: none;
    border-bottom: 2px solid #dee2e6;
}

/* Responsive */
@media (max-width: 768px) {
    .codice-badge-ruolini {
        font-size: 0.8rem;
        padding: 6px 10px;
        min-width: 60px;
    }
    
    .stato-select {
        min-width: 110px;
        font-size: 0.8rem;
        padding: 0.4rem 1.8rem 0.4rem 0.6rem;
    }
}
</style>

<div class="container-fluid">
    <!-- Header con info Compagnia -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-1">Gestione Ruolini</h1>
            <p class="text-muted mb-0">
                <i class="fas fa-building me-1"></i>
                Configurazione per: <strong class="text-primary">{{ $compagniaCorrente->nome ?? 'N/D' }}</strong>
            </p>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            @if($isGlobalAdmin && $compagnie->isNotEmpty())
            <!-- Selettore Compagnia per Admin -->
            <div class="admin-compagnia-selector">
                <label class="form-label mb-1 small text-muted">
                    <i class="fas fa-shield-alt me-1"></i>Visualizza come:
                </label>
                <select id="compagniaSelector" class="form-select form-select-sm" style="min-width: 200px;">
                    @foreach($compagnie as $comp)
                    <option value="{{ $comp->id }}" {{ $compagniaId == $comp->id ? 'selected' : '' }}>
                        {{ $comp->nome }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
            
            <!-- Badge Compagnia -->
            <div class="compagnia-badge bg-primary text-white px-3 py-2 rounded">
                <i class="fas fa-flag me-1"></i>
                {{ $compagniaCorrente->nome ?? 'N/D' }}
            </div>
        </div>
    </div>

    @php
        // Raggruppa i servizi per categoria
        $serviziPerCategoria = $tipiServizio->groupBy('categoria')->sortKeys();
    @endphp

    <!-- Configurazione per Categoria -->
    @forelse($serviziPerCategoria as $categoria => $servizi)
    <div class="categoria-accordion">
        <button class="categoria-accordion-header collapsed" type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#cat-{{ Str::slug($categoria) }}" 
                aria-expanded="false" 
                aria-controls="cat-{{ Str::slug($categoria) }}">
            <div class="d-flex align-items-center gap-3">
                <h5>{{ $categoria }}</h5>
                <span class="badge">{{ $servizi->count() }} servizi</span>
            </div>
            <i class="fas fa-chevron-down categoria-accordion-icon"></i>
        </button>
        <div id="cat-{{ Str::slug($categoria) }}" class="collapse">
            <div class="categoria-content">
                <table class="sugeco-table" id="ruoliniTable-{{ Str::slug($categoria) }}">
                    <thead>
                        <tr>
                            <th style="width: 100px;">CODICE</th>
                            <th>NOME SERVIZIO</th>
                            <th style="width: 130px;">STATO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($servizi as $tipo)
                        @php
                            $config = $configurazioni->get($tipo->id);
                            $statoPresenza = $config ? $config->stato_presenza : 'assente';
                            $coloriChiari = ['#ffff00', '#ffc000', '#ffffff', '#f0f0f0', '#e0e0e0', '#ffeb3b', '#fff9c4', '#fffde7'];
                            $coloreTesto = in_array(strtolower($tipo->colore_badge), $coloriChiari) ? '#000' : '#fff';
                        @endphp
                        <tr data-tipo-id="{{ $tipo->id }}" class="row-{{ $statoPresenza }}">
                            <td>
                                <span class="codice-badge-ruolini" 
                                      style="background-color: {{ $tipo->colore_badge }}; color: {{ $coloreTesto }};">
                                    {{ $tipo->codice }}
                                </span>
                            </td>
                            <td><strong>{{ $tipo->nome }}</strong></td>
                            <td>
                                <select class="stato-select stato-{{ $statoPresenza }}" 
                                        data-tipo-id="{{ $tipo->id }}"
                                        autocomplete="off">
                                    <option value="assente" {{ $statoPresenza === 'assente' ? 'selected' : '' }}>Assente</option>
                                    <option value="presente" {{ $statoPresenza === 'presente' ? 'selected' : '' }}>Presente</option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center text-muted py-5">
        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
        Nessun tipo di servizio disponibile
    </div>
    @endforelse
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) return;

    // Aggiorna le classi visive
    function updateStatoVisuals(row, select, stato) {
        row.classList.remove('row-presente', 'row-assente');
        row.classList.add('row-' + stato);
        select.classList.remove('stato-presente', 'stato-assente');
        select.classList.add('stato-' + stato);
    }

    // Salvataggio automatico al cambio stato
    document.querySelectorAll('.stato-select').forEach(function(select) {
        select.addEventListener('change', function() {
            const tipoId = this.dataset.tipoId;
            const stato = this.value;
            const row = this.closest('tr');
            
            updateStatoVisuals(row, this, stato);
            
            fetch('{{ url("gestione-ruolini") }}/' + tipoId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ stato_presenza: stato, note: null })
            })
            .then(r => r.ok ? r.json() : Promise.reject('Errore'))
            .then(data => {
                if (window.SUGECO?.showSaveFeedback) {
                    window.SUGECO.showSaveFeedback(this, data.success, 2000);
                }
            })
            .catch(err => {
                console.error('Errore:', err);
                if (window.SUGECO?.showSaveFeedback) {
                    window.SUGECO.showSaveFeedback(this, false, 2000);
                }
            });
        });
    });

    // Selettore compagnia per admin
    const compagniaSelector = document.getElementById('compagniaSelector');
    if (compagniaSelector) {
        compagniaSelector.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('compagnia_id', this.value);
            window.location.href = url.toString();
        });
    }
});
</script>
@endsection
