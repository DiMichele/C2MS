@extends('layouts.app')

@section('title', 'Gestione CPT')

@section('content')
<style>
/* =============================================
   GESTIONE CPT - STILI ACCORDION
   ============================================= */

.form-control, .form-select {
    border-radius: 0 !important;
}

/* Badge CPT con colori esatti */
.codice-badge {
    display: inline-block;
    padding: 6px 12px;
    font-weight: 700;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 60px;
    text-align: center;
    font-family: 'Courier New', monospace;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

/* Accordion per tipo impiego */
.impiego-accordion {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 1rem;
}

.impiego-accordion-header {
    width: 100%;
    text-align: left;
    border: none;
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    transition: all 0.2s;
    border-radius: 8px;
}

.impiego-accordion-header:hover {
    filter: brightness(1.1);
}

.impiego-accordion-header:focus {
    outline: none;
}

.impiego-accordion-header h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
}

.impiego-accordion-header .badge {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 12px;
}

.impiego-accordion-icon {
    color: #fff;
    transition: transform 0.2s ease;
    font-size: 0.9rem;
}

.impiego-accordion-header[aria-expanded="true"] .impiego-accordion-icon {
    transform: rotate(180deg);
}

.impiego-accordion-header[aria-expanded="true"] {
    border-radius: 8px 8px 0 0;
}

/* Colori per tipo impiego */
.impiego-DISPONIBILE { background: linear-gradient(135deg, #198754 0%, #20c997 100%); }
.impiego-INDISPONIBILE { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
.impiego-NON_DISPONIBILE { background: linear-gradient(135deg, #dc3545 0%, #e35d6a 100%); }
.impiego-PRESENTE_SERVIZIO { background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%); }
.impiego-DISPONIBILE_ESIGENZA { background: linear-gradient(135deg, #0dcaf0 0%, #20c997 100%); }

/* Contenuto accordion */
.impiego-content {
    background: #fff;
}

.impiego-content .codici-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.impiego-content .codice-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.impiego-content .codice-item:hover {
    background: #f8f9fa;
}

.impiego-content .codice-item:last-child {
    border-bottom: none;
}

.codice-info {
    display: flex;
    align-items: center;
    gap: 16px;
    flex: 1;
}

.codice-descrizione {
    font-size: 0.95rem;
    color: #495057;
}

.codice-stato {
    font-size: 0.8rem;
}

.codice-azioni {
    display: flex;
    gap: 8px;
}

/* Container scrollabile */
.accordions-container {
    max-height: calc(100vh - 280px);
    overflow-y: auto;
    padding-right: 5px;
}

/* Filtri senza icone */
.filter-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
    margin-bottom: 4px;
}

/* Stato inattivo */
.codice-item.inattivo {
    opacity: 0.6;
    background: #f8f9fa;
}

.codice-item.inattivo .codice-badge {
    filter: grayscale(50%);
}
</style>

<!-- Header -->
<div class="text-center mb-4">
    <h1 class="page-title">GESTIONE CPT</h1>
</div>

<!-- Barra di ricerca centrata -->
<div class="d-flex justify-content-center mb-3">
    <div class="search-container" style="position: relative; width: 500px;">
        <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d;"></i>
        <input 
            type="text" 
            id="searchCodice" 
            class="form-control" 
            placeholder="Cerca codice o descrizione..." 
            aria-label="Cerca codice" 
            style="padding-left: 40px; border-radius: 6px !important;">
    </div>
</div>

<!-- Filtri e azioni -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <!-- Filtro Stato (senza icone) -->
        <div>
            <label for="filtroStato" class="filter-label d-block">Stato</label>
            <select id="filtroStato" class="form-select form-select-sm" style="min-width: 150px; border-radius: 6px !important;">
                <option value="">Tutti</option>
                <option value="attivo">Solo Attivi</option>
                <option value="inattivo">Solo Inattivi</option>
            </select>
        </div>
        
        <!-- Filtro Tipo Impiego (senza icone) -->
        <div>
            <label for="filtroImpiego" class="filter-label d-block">Tipo Impiego</label>
            <select id="filtroImpiego" class="form-select form-select-sm" style="min-width: 200px; border-radius: 6px !important;">
                <option value="">Tutti i tipi</option>
                @foreach($tipiImpiego as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    
    <div>
        <a href="{{ route('codici-cpt.create') }}" class="btn btn-success" style="border-radius: 6px !important;">
            <i class="fas fa-plus me-1"></i>Nuovo Codice
        </a>
    </div>
</div>

<!-- Messaggi -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<!-- Accordion per tipo di impiego -->
<div class="accordions-container">
    @forelse($codiciPerImpiego as $impiego => $codiciGruppo)
    <div class="impiego-accordion" data-impiego="{{ $impiego }}">
        <button class="impiego-accordion-header impiego-{{ $impiego }} collapsed" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#impiego-{{ Str::slug($impiego) }}" 
                aria-expanded="false" 
                aria-controls="impiego-{{ Str::slug($impiego) }}">
            <div class="d-flex align-items-center gap-3">
                <h5>{{ $tipiImpiego[$impiego] ?? str_replace('_', ' ', ucfirst(strtolower($impiego))) }}</h5>
                <span class="badge codici-count">{{ $codiciGruppo->count() }} codici</span>
            </div>
            <i class="fas fa-chevron-down impiego-accordion-icon"></i>
        </button>
        <div id="impiego-{{ Str::slug($impiego) }}" class="collapse">
            <div class="impiego-content">
                <ul class="codici-list">
                    @foreach($codiciGruppo as $codice)
                    @php
                        // Calcola contrasto testo automatico
                        $hex = ltrim($codice->colore_badge, '#');
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        $luminosita = ($r * 299 + $g * 587 + $b * 114) / 1000;
                        $testoColore = $luminosita > 128 ? '#000' : '#fff';
                    @endphp
                    <li class="codice-item {{ !$codice->attivo ? 'inattivo' : '' }}" 
                        data-codice="{{ strtolower($codice->codice) }}"
                        data-descrizione="{{ strtolower($codice->attivita_specifica) }}"
                        data-attivo="{{ $codice->attivo ? 'attivo' : 'inattivo' }}">
                        <div class="codice-info">
                            <span class="codice-badge" 
                                  style="background-color: {{ $codice->colore_badge }}; color: {{ $testoColore }};">
                                {{ $codice->codice }}
                            </span>
                            <span class="codice-descrizione">{{ $codice->attivita_specifica }}</span>
                            @if(!$codice->attivo)
                                <span class="badge bg-secondary codice-stato">Inattivo</span>
                            @endif
                        </div>
                        <div class="codice-azioni">
                            <a href="{{ route('codici-cpt.edit', $codice) }}" 
                               class="btn btn-sm btn-outline-primary"
                               title="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('codici-cpt.toggle', $codice) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        class="btn btn-sm btn-outline-{{ $codice->attivo ? 'warning' : 'success' }}"
                                        title="{{ $codice->attivo ? 'Disattiva' : 'Attiva' }}">
                                    <i class="fas fa-{{ $codice->attivo ? 'eye-slash' : 'eye' }}"></i>
                                </button>
                            </form>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-danger delete-codice-btn"
                                    data-codice-id="{{ $codice->id }}"
                                    data-codice-nome="{{ $codice->codice }}"
                                    data-delete-url="{{ route('codici-cpt.destroy', $codice) }}"
                                    title="Elimina">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Nessun codice trovato</h5>
        <p class="text-muted mb-3">Inizia creando il tuo primo codice CPT</p>
        <a href="{{ route('codici-cpt.create') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Crea Codice
        </a>
    </div>
    @endforelse
</div>

<!-- Floating Button Export Excel -->
<a href="{{ route('codici-cpt.export') }}" class="fab fab-excel" data-tooltip="Esporta Excel" aria-label="Esporta Excel">
    <i class="fas fa-file-excel"></i>
</a>

<!-- Form nascosto per eliminazione -->
<form id="deleteCodiceForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCodice');
    const filtroStato = document.getElementById('filtroStato');
    const filtroImpiego = document.getElementById('filtroImpiego');
    const accordions = document.querySelectorAll('.impiego-accordion');
    
    // Funzione per applicare i filtri client-side
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statoFilter = filtroStato.value;
        const impiegoFilter = filtroImpiego.value;
        
        let visibleCount = 0;
        
        accordions.forEach(accordion => {
            const impiego = accordion.dataset.impiego;
            const items = accordion.querySelectorAll('.codice-item');
            const countBadge = accordion.querySelector('.codici-count');
            
            // Filtra per tipo impiego
            if (impiegoFilter && impiego !== impiegoFilter) {
                accordion.style.display = 'none';
                return;
            }
            
            accordion.style.display = '';
            let accordionVisibleCount = 0;
            
            items.forEach(item => {
                const codice = item.dataset.codice;
                const descrizione = item.dataset.descrizione;
                const attivo = item.dataset.attivo;
                
                let visible = true;
                
                // Filtro ricerca
                if (searchTerm && !codice.includes(searchTerm) && !descrizione.includes(searchTerm)) {
                    visible = false;
                }
                
                // Filtro stato
                if (statoFilter && attivo !== statoFilter) {
                    visible = false;
                }
                
                item.style.display = visible ? '' : 'none';
                if (visible) {
                    accordionVisibleCount++;
                    visibleCount++;
                }
            });
            
            // Aggiorna contatore nel badge dell'accordion
            if (countBadge) {
                countBadge.textContent = accordionVisibleCount + ' codici';
            }
            
            // Nascondi accordion se non ha codici visibili
            if (accordionVisibleCount === 0) {
                accordion.style.display = 'none';
            }
        });
        
    }
    
    // Event listeners per i filtri
    searchInput.addEventListener('input', applyFilters);
    filtroStato.addEventListener('change', applyFilters);
    filtroImpiego.addEventListener('change', applyFilters);
    
    // Gestione eliminazione codici
    document.querySelectorAll('.delete-codice-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const codiceNome = this.dataset.codiceNome;
            const deleteUrl = this.dataset.deleteUrl;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare il codice "${codiceNome}"? Questa azione non può essere annullata.`);
            
            if (confirmed) {
                const form = document.getElementById('deleteCodiceForm');
                form.action = deleteUrl;
                form.submit();
            }
        });
    });
});
</script>
@endsection
