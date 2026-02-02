{{--
|--------------------------------------------------------------------------
| Pagina di dettaglio militare - Design Minimalista
|--------------------------------------------------------------------------
| @version 3.0
| @author Michele Di Gennaro
--}}

@extends('layouts.app')
@section('title', 'Dettaglio ' . $militare->cognome . ' ' . $militare->nome . ' - SUGECO')

@section('styles')
<style>
    /* =====================================================
       LAYOUT GENERALE
       ===================================================== */
    .dettaglio-militare {
        max-width: 1200px;
        margin: 0 auto;
    }

    /* =====================================================
       HEADER INFO
       ===================================================== */
    .profilo-info-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        padding: 1.5rem;
        background: white;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 1px solid #e9ecef;
        flex-wrap: wrap;
    }
    
    .profilo-foto-container {
        flex-shrink: 0;
    }
    
    .profilo-foto {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 2px solid #e9ecef;
        cursor: pointer;
    }
    
    .profilo-foto img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profilo-foto-placeholder {
        font-size: 2.5rem;
        color: #adb5bd;
    }
    
    .profilo-stato-container {
        flex: 1;
        min-width: 200px;
    }
    
    .profilo-stato-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .profilo-stato-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--navy);
    }
    
    .profilo-azioni {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .badge-acquisito {
        background: #17a2b8;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* =====================================================
       SEZIONI ACCORDION
       ===================================================== */
    .dettaglio-sezione {
        background: white;
        border-radius: 10px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        overflow: hidden;
        border: 1px solid #e9ecef;
    }
    
    .sezione-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        cursor: pointer;
        background: #f8f9fa;
        border-bottom: 1px solid transparent;
        transition: background 0.2s;
    }
    
    .sezione-header:hover {
        background: #f0f2f5;
    }
    
    .sezione-header[aria-expanded="true"] {
        border-bottom-color: #e9ecef;
    }
    
    .sezione-titolo {
        font-weight: 600;
        font-size: 1rem;
        color: var(--navy);
    }
    
    .sezione-badge-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .sezione-chevron {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        transition: transform 0.2s;
    }
    
    .sezione-chevron::after {
        content: '';
        width: 8px;
        height: 8px;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg);
        margin-top: -4px;
    }
    
    .sezione-header[aria-expanded="true"] .sezione-chevron::after {
        transform: rotate(-135deg);
        margin-top: 4px;
    }
    
    .sezione-content {
        padding: 1.25rem;
    }

    /* =====================================================
       TABELLA DATI
       ===================================================== */
    .tabella-dati {
        width: 100%;
        border-collapse: collapse;
    }
    
    .tabella-dati td {
        padding: 0.6rem 0;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: top;
    }
    
    .tabella-dati tr:last-child td {
        border-bottom: none;
    }
    
    .tabella-dati .td-label {
        width: 40%;
        color: #6c757d;
        font-size: 0.9rem;
    }
    
    .tabella-dati .td-value {
        color: #2d3748;
        font-weight: 500;
    }
    
    .tabella-dati .td-value a {
        color: var(--navy);
        text-decoration: none;
    }
    
    .tabella-dati .td-value a:hover {
        text-decoration: underline;
    }

    /* =====================================================
       TABELLA SCADENZE
       ===================================================== */
    .tabella-scadenze {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }
    
    .tabella-scadenze th {
        text-align: left;
        padding: 0.75rem;
        background: #f8f9fa;
        font-weight: 600;
        color: var(--navy);
        border-bottom: 2px solid #e9ecef;
    }
    
    .tabella-scadenze td {
        padding: 0.75rem;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
    }
    
    .tabella-scadenze tr:last-child td {
        border-bottom: none;
    }
    
    .tabella-scadenze tr:hover td {
        background: #fafbfc;
    }

    /* =====================================================
       BADGES STATO
       ===================================================== */
    .badge-stato {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-valido {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-in-scadenza {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-scaduto {
        background: #f8d7da;
        color: #721c24;
    }
    
    .badge-non-presente {
        background: #e9ecef;
        color: #6c757d;
    }
    
    .badge-count {
        background: var(--navy);
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    /* =====================================================
       PATENTI
       ===================================================== */
    .patenti-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    
    .patente-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #e9ecef;
    }
    
    .patente-categoria {
        font-weight: 700;
        color: var(--navy);
        font-size: 1.1rem;
        min-width: 30px;
    }
    
    .patente-info {
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* =====================================================
       TEATRO OPERATIVO
       ===================================================== */
    .teatro-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
    
    .teatro-campo {
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .teatro-campo-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.25rem;
    }
    
    .teatro-campo-value {
        font-weight: 600;
        color: var(--navy);
    }

    /* =====================================================
       MINI CALENDARIO
       ===================================================== */
    .mini-calendario {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }
    
    .mini-cal-header {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        margin-bottom: 4px;
    }
    
    .mini-cal-giorno {
        text-align: center;
        font-weight: 600;
        font-size: 0.7rem;
        color: var(--navy);
        padding: 4px 2px;
    }
    
    .mini-cal-giorno.weekend {
        color: #dc3545;
    }
    
    .mini-cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }
    
    .mini-cal-cella {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        background: white;
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--navy);
        border: 1px solid #e9ecef;
    }
    
    .mini-cal-cella.vuota {
        background: transparent;
        border: none;
    }
    
    .mini-cal-cella.oggi {
        border: 2px solid var(--gold);
        font-weight: 700;
    }
    
    .mini-cal-cella.weekend {
        background: #fff5f5;
        color: #dc3545;
    }
    
    .mini-cal-cella.con-impegno {
        background: var(--impegno-color, #6c757d);
        color: white;
        border-color: transparent;
    }
    
    .calendario-stats {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--navy);
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
    }

    /* =====================================================
       ATTIVITA
       ===================================================== */
    .attivita-item {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 0.75rem;
        border-bottom: 1px solid #f1f3f5;
    }
    
    .attivita-item:last-child {
        border-bottom: none;
    }
    
    .attivita-colore {
        width: 4px;
        height: 40px;
        border-radius: 2px;
        flex-shrink: 0;
    }
    
    .attivita-content {
        flex: 1;
    }
    
    .attivita-titolo {
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 0.25rem;
    }
    
    .attivita-periodo {
        font-size: 0.85rem;
        color: #6c757d;
    }

    /* =====================================================
       RESPONSIVE
       ===================================================== */
    @media (max-width: 768px) {
        .profilo-info-bar {
            flex-direction: column;
            text-align: center;
        }
        
        .profilo-azioni {
            justify-content: center;
        }
        
        .tabella-dati .td-label {
            width: 50%;
        }
        
        .teatro-info {
            grid-template-columns: 1fr;
        }
        
        .calendario-stats {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
@php
    $relationType = $militare->getRelationType(auth()->user());
    $isReadOnly = $militare->isReadOnlyFor(auth()->user());
    $isAcquired = $relationType === 'acquired';
    
    // Stato dal CPT
    $oggi = now();
    $pianificazioneOggi = $militare->pianificazioniGiornaliere()
        ->whereHas('pianificazioneMensile', function($q) use ($oggi) {
            $q->where('anno', $oggi->year)->where('mese', $oggi->month);
        })
        ->where('giorno', $oggi->day)
        ->with('tipoServizio')
        ->first();
    $tipoServizio = $pianificazioneOggi ? $pianificazioneOggi->tipoServizio : null;
@endphp

<div class="dettaglio-militare container-fluid">
    
    {{-- Titolo --}}
    <div class="text-center mb-4">
        <h1 class="page-title">{{ $militare->grado->sigla ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</h1>
        @if($isAcquired)
            <div class="mt-2">
                <span class="badge-acquisito">Militare Acquisito - Sola Lettura</span>
            </div>
        @endif
    </div>
    
    {{-- Barra Info --}}
    <div class="profilo-info-bar">
        {{-- Stato --}}
        <div class="profilo-stato-container">
            <div class="profilo-stato-label">Stato Odierno</div>
            <div class="profilo-stato-value">
                @if($tipoServizio)
                    <span class="badge" style="background-color: {{ $tipoServizio->colore_badge ?? '#6c757d' }}; color: white;">
                        {{ $tipoServizio->codice }}
                    </span>
                    {{ $tipoServizio->nome }}
                @else
                    Libero
                @endif
            </div>
        </div>
        
        {{-- Azioni --}}
        <div class="profilo-azioni">
            @can('cpt.view')
                <a href="{{ route('disponibilita.militare', $militare->id) }}" class="btn btn-outline-primary btn-sm">
                    Calendario
                </a>
            @endcan
            
            @if(!$isReadOnly)
                @can('anagrafica.edit')
                    <a href="{{ route('anagrafica.edit', $militare->id) }}" class="btn btn-warning btn-sm">
                        Modifica
                    </a>
                @endcan
                @can('anagrafica.delete')
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmDeleteMilitare()">
                        Elimina
                    </button>
                @endcan
            @endif
        </div>
    </div>
    
    {{-- Sezioni --}}
    <div class="sezioni-container">
        @include('militare.partials._sezione_anagrafica')
        @include('militare.partials._sezione_patenti')
        @include('militare.partials._sezione_teatro_operativo')
        @include('militare.partials._sezione_scadenze')
        @include('militare.partials._sezione_calendario')
    </div>
</div>

{{-- Modal eliminazione gestito da SUGECO.Confirm --}}
<form id="deleteForm" action="{{ route('anagrafica.destroy', $militare->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
// Funzione per conferma eliminazione militare dalla pagina show
async function confirmDeleteMilitare() {
    const confirmed = await SUGECO.Confirm.delete('Eliminare {{ $militare->grado->nome ?? "" }} {{ $militare->cognome }} {{ $militare->nome }}? Questa azione non può essere annullata.');
    if (confirmed) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endpush

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione sezioni
    document.querySelectorAll('.sezione-header').forEach(function(header) {
        const target = header.getAttribute('data-bs-target');
        const collapse = document.querySelector(target);
        
        if (collapse) {
            collapse.addEventListener('shown.bs.collapse', function() {
                header.setAttribute('aria-expanded', 'true');
            });
            collapse.addEventListener('hidden.bs.collapse', function() {
                header.setAttribute('aria-expanded', 'false');
            });
        }
    });
});
</script>
@endsection
