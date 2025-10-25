@extends('layouts.app')
@section('title', 'Organigramma - C2MS')

@section('styles')
<style>
    /* Variables */
    :root {
        --navy-dark: #0A1E38;
        --navy: #0A2342;
        --navy-light: #1A3A5F;
        --gold: #BF9D5E;
        --gold-light: #D4B987;
        --gray-100: #f8f9fa;
        --gray-200: #e9ecef;
        --gray-300: #dee2e6;
        --gray-500: #adb5bd;
        --gray-700: #495057;
        --success: #2E7D32;
        --success-light: rgba(46, 125, 50, 0.15);
        --danger: #D32F2F;
        --danger-light: rgba(211, 47, 47, 0.15);
        --transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    }
    
    /* Stile generale della pagina */
    body {
        background-color: #f5f7f9;
    }
    
    /* Rimuovo gli stili del page-header complesso per usare lo stile minimal */
    
    /* Switch per cambiare visualizzazione */
    .toggle-container {
        margin-bottom: 2rem;
    }
    
    .toggle-wrapper {
        background-color: white;
        border-radius: 50px;
        box-shadow: 0 6px 20px rgba(10, 35, 66, 0.08);
        padding: 0.5rem;
        position: relative;
        display: inline-flex;
        transition: var(--transition);
    }
    
    .toggle-wrapper:hover {
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.12);
    }
    
    .toggle-option {
        position: relative;
        z-index: 1;
        padding: 0.8rem 1.5rem;
        border-radius: 50px;
        cursor: pointer;
        transition: var(--transition);
        font-weight: 600;
        min-width: 150px;
        text-align: center;
    }
    
    .toggle-option.active {
        color: white;
    }
    
    .toggle-option:not(.active) {
        color: var(--gray-700);
    }
    
    .toggle-option:not(.active):hover {
        color: var(--navy);
    }
    
    .toggle-option i {
        margin-right: 0.75rem;
        transition: transform 0.3s ease;
    }
    
    .toggle-option.active i {
        transform: scale(1.2);
    }
    
    .toggle-slider {
        position: absolute;
        top: 0.5rem;
        left: 0.5rem;
        height: calc(100% - 1rem);
        width: calc(50% - 0.5rem);
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        border-radius: 50px;
        transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1);
        box-shadow: 0 4px 15px rgba(10, 35, 66, 0.2);
    }
    
    .toggle-slider::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        border-radius: 50px;
    }
    
    .toggle-slider.poli {
        transform: translateX(100%);
    }
    
    /* Contenitore principale dell'organigramma */
    .organigramma-wrapper {
        max-width: 1280px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }
    
    .org-chart-container {
        background-color: white;
        border-radius: 16px;
        padding: 2.5rem;
        margin-bottom: 2.5rem;
        box-shadow: 0 15px 35px rgba(10, 35, 66, 0.08);
        position: relative;
    }
    
    /* Grafico organigramma */
    .org-tree {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    /* IMPROVED COMPANY NODE */
    .node-compagnia {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: white;
        padding: 1.75rem 2.5rem;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(10, 35, 66, 0.18);
        margin-bottom: 3.5rem;
        position: relative;
        text-align: center;
        min-width: 340px;
        transform: translateY(0);
        transition: var(--transition);
        z-index: 2;
    }
    
    .node-compagnia:hover {
        transform: translateY(-5px);
        box-shadow: 0 18px 35px rgba(10, 35, 66, 0.25);
    }
    
    .node-compagnia::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 16px;
        padding: 2px;
        background: linear-gradient(135deg, var(--gold) 0%, rgba(191, 157, 94, 0.3) 100%);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }
    
    .node-compagnia::after {
        content: '';
        position: absolute;
        bottom: -40px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 40px;
        background: linear-gradient(to bottom, var(--gold) 0%, rgba(191, 157, 94, 0.3) 100%);
    }
    
    .node-compagnia-header {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        letter-spacing: -0.01em;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    }
    
    .node-compagnia-subtitle {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-bottom: 1rem;
        font-weight: 400;
        font-style: italic;
    }
    
    .node-compagnia-content {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
        padding-top: 1rem;
        margin-top: 0.5rem;
    }
    
    .node-compagnia-content div {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 0 0.75rem;
    }
    
    .node-compagnia-content div:not(:last-child) {
        border-right: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .stat-label {
        font-size: 0.75rem;
        font-weight: 500;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.35rem;
        color: white !important;
    }
    
    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        color: white !important;
    }
    
    /* LAYOUT ORIZZONTALE PER I PLOTONI/POLI */
    .nodi-figli-container {
        position: relative;
        width: 100%;
        margin-bottom: 20px;
        padding: 0 25px;
    }
    
    .nodi-figli {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        width: 100%;
        padding: 20px 0;
        gap: 20px;
        align-items: stretch;
        scroll-behavior: smooth;
        scrollbar-width: thin;
        -ms-overflow-style: -ms-autohiding-scrollbar;
    }
    
    /* Nuova classe per centrare gli elementi quando sono 3 o meno */
    .nodi-figli.centered {
        justify-content: center;
        overflow-x: visible;
    }
    
    /* Nascondi scrollbar ma mantieni funzionalità di scroll */
    .nodi-figli::-webkit-scrollbar {
        height: 6px;
        background-color: rgba(0,0,0,0.05);
    }
    
    .nodi-figli::-webkit-scrollbar-thumb {
        background-color: rgba(10, 35, 66, 0.2);
        border-radius: 3px;
    }
    
    /* Linea orizzontale di connessione */
    .nodi-figli::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(to right, rgba(191, 157, 94, 0.3) 0%, var(--gold) 50%, rgba(191, 157, 94, 0.3) 100%);
        z-index: 1;
    }
    
    /* Frecce di navigazione */
    .nav-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        background-color: white;
        border-radius: 50%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        color: var(--navy);
        font-size: 1.2rem;
        transition: all 0.3s ease;
        opacity: 0.9;
        border: none;
    }
    
    .nav-arrow:hover {
        background-color: var(--navy);
        color: white;
        transform: translateY(-50%) scale(1.05);
        opacity: 1;
    }
    
    .nav-arrow.disabled {
        opacity: 0.4;
        cursor: not-allowed;
        pointer-events: none;
    }
    
    .nav-prev {
        left: -15px;
    }
    
    .nav-next {
        right: -15px;
    }
    
    /* Indicatore di posizione */
    .scroll-indicator {
        display: flex;
        justify-content: center;
        margin-top: 10px;
        gap: 6px;
    }
    
    .indicator-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: var(--gray-300);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .indicator-dot.active {
        background-color: var(--gold);
        transform: scale(1.3);
    }
    
    /* Stile dei nodi figlio */
    .node-figlio {
        flex: 0 0 320px;
        min-width: 320px;
        max-width: 320px;
        width: 320px;
        background-color: white;
        border-radius: 16px;
        box-shadow: 0 15px 35px rgba(10, 35, 66, 0.08);
        overflow: hidden;
        position: relative;
        transition: var(--transition);
        border: 1px solid var(--gray-200);
        display: flex;
        flex-direction: column;
        height: auto;
    }
    
    .node-figlio:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(10, 35, 66, 0.15);
        border-color: var(--gray-300);
    }
    
    /* Connettore verticale per ogni nodo */
    .node-connector {
        position: absolute;
        top: -20px;
        left: 50%;
        width: 2px;
        height: 20px;
        background: linear-gradient(to top, var(--gold) 0%, rgba(191, 157, 94, 0.3) 100%);
        transform: translateX(-50%);
    }
    
    .node-figlio-header {
        background: linear-gradient(135deg, var(--gray-100) 0%, var(--gray-200) 100%);
        color: var(--navy-dark);
        padding: 1.5rem;
        font-weight: 700;
        font-size: 1.25rem;
        border-bottom: 3px solid var(--gold);
        letter-spacing: -0.01em;
        position: relative;
        overflow: hidden;
        text-shadow: 0 1px 1px rgba(255, 255, 255, 0.5);
    }
    
    .node-figlio-header::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 30%;
        background: linear-gradient(to left, rgba(191, 157, 94, 0.1) 0%, rgba(191, 157, 94, 0) 100%);
    }
    
    .node-figlio-content {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    /* Lista militari */
    .lista-militari {
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
    }
    
    .militare-item {
        display: flex;
        align-items: center;
        padding: 0.9rem 0.5rem;
        border-bottom: 1px solid var(--gray-200);
        transition: var(--transition);
        border-radius: 8px;
        margin-bottom: 0.25rem;
    }
    
    .militare-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .militare-item:hover {
        background-color: var(--gray-100);
        transform: translateX(4px);
    }
    
    .stato-presenza {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 14px;
        flex-shrink: 0;
        position: relative;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .stato-presenza::after {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        opacity: 0.2;
    }
    
    .stato-presenza.presente {
        background-color: var(--success);
    }
    
    .stato-presenza.presente::after {
        background-color: var(--success);
    }
    
    .stato-presenza.assente {
        background-color: var(--danger);
    }
    
    .stato-presenza.assente::after {
        background-color: var(--danger);
    }
    
    /* Tooltip per status presenza */
    .stato-presenza::before {
        content: attr(data-status);
        position: absolute;
        bottom: 140%;
        left: 50%;
        transform: translateX(-50%) scale(0.8);
        background: rgba(0, 0, 0, 0.85);
        color: #fff;
        padding: 3px 6px;
        border-radius: 4px;
        white-space: nowrap;
        font-size: 0.7rem;
        opacity: 0;
        visibility: hidden;
        transition: all 0.2s ease;
        pointer-events: none;
        z-index: 100;
        font-weight: 500;
    }
    
    .stato-presenza:hover {
        transform: scale(1.3);
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.2);
    }
    
    .stato-presenza:hover::before {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) scale(1);
    }
    
    .militare-info {
        flex-grow: 1;
    }
    
    .militare-grado {
        font-size: 0.85rem;
        color: var(--gray-700);
        margin-bottom: 0.25rem;
        font-weight: 400; /* Ridotto il peso per assicurare che non sia in grassetto */
        font-style: normal;
    }
    
    /* Stile per i link nome dei militari - replica esatta di militare.css */
    a.link-name.militare-nome {
        color: var(--navy);
        text-decoration: none;
        font-weight: 400; /* Normale */
        transition: all 0.3s ease;
        position: relative;
        display: inline-block;
        padding: 2px 4px;
    }
    
    a.link-name.militare-nome:hover {
        color: var(--navy-light);
    }
    
    a.link-name.militare-nome::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        bottom: -1px;
        left: 0;
        background-color: var(--gold);
        transform: scaleX(0);
        transition: transform 0.3s ease;
        transform-origin: bottom right;
    }
    
    a.link-name.militare-nome:hover::after {
        transform: scaleX(1);
        transform-origin: bottom left;
    }
    
    a.link-name.militare-nome strong {
        font-weight: 700; /* Il contenuto strong è in grassetto */
    }
    
    /* COMPACT FORZA EFFETTIVA */
    .forza-effettiva {
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px solid var(--gray-200);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .forza-label {
        font-weight: 600;
        color: var(--navy);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: -0.25rem;
    }
    
    .forza-stats {
        display: flex;
        gap: 0.5rem;
        width: 100%;
        justify-content: center;
    }
    
    .forza-count {
        font-weight: 700;
        color: var(--navy);
        padding: 0.5rem 0.4rem;
        border-radius: 6px;
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        transition: var(--transition);
        box-shadow: 0 2px 6px rgba(10, 35, 66, 0.05);
    }
    
    .forza-count:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(10, 35, 66, 0.08);
    }
    
    .count-value {
        font-size: 1.4rem;
        line-height: 1;
        margin-bottom: 0.25rem;
        color: white !important;
        font-weight: 700;
    }
    
    .count-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: white !important;
        font-weight: 600;
    }
    
    .presente-count {
        color: #0D4A14 !important;
        background-color: rgba(46, 125, 50, 0.5) !important;
        font-weight: 700 !important;
        border: 1px solid rgba(46, 125, 50, 0.7) !important;
    }
    
    .assente-count {
        color: #8B0000 !important;
        background-color: rgba(211, 47, 47, 0.5) !important;
        font-weight: 700 !important;
        border: 1px solid rgba(211, 47, 47, 0.7) !important;
    }
    
    .totale-count {
        color: #051122 !important;
        background-color: rgba(10, 35, 66, 0.4) !important;
        font-weight: 700 !important;
        border: 1px solid rgba(10, 35, 66, 0.6) !important;
    }
    
    /* Ricerca militari */
    .search-container {
        position: relative;
        margin-bottom: 0;
        width: 320px;
        z-index: 1000;
    }
    
    .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        pointer-events: none;
        z-index: 5;
        font-size: 0.9rem;
    }
    
    #searchMilitare {
        padding: 12px 15px 12px 40px;
        border-radius: 20px;
        border: 1px solid rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0,0,0,0.06);
        font-size: 0.95rem;
        width: 100%;
    }
    
    #searchMilitare:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 0.25rem rgba(10, 35, 66, 0.15), 0 3px 10px rgba(0,0,0,0.05);
        outline: none;
    }
    
    #searchSuggestions {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        z-index: 1050;
        max-height: 350px;
        overflow-y: auto;
        border: 1px solid rgba(0,0,0,0.1);
        padding: 0.5rem 0;
        animation: fadeInDown 0.3s ease;
    }
    
    .suggestions-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .suggestion-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        transition: all 0.2s ease;
    }
    
    .suggestion-item:hover {
        background-color: rgba(10, 35, 66, 0.05);
        padding-left: 1.25rem;
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .d-none {
        display: none !important;
    }
    
    .no-results {
        padding: 20px;
        color: #6c757d;
        font-style: italic;
        text-align: center;
    }
    
    /* Animazioni e transizioni */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .org-view {
        animation: scaleIn 0.5s ease-out;
    }
    
    .hidden {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
        position: absolute !important;
        pointer-events: none !important;
    }
    
    /* Testo vuoto */
    .testo-vuoto {
        text-align: center;
        color: var(--gray-700);
        font-style: italic;
        padding: 1.5rem;
        background-color: var(--gray-100);
        border-radius: 8px;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .node-figlio {
            flex: 0 0 300px;
            min-width: 300px;
        }
        
        .node-compagnia {
            min-width: 300px;
        }
        
        .nav-arrow {
            width: 36px;
            height: 36px;
        }
        
        .nav-prev {
            left: -15px;
        }
        
        .nav-next {
            right: -15px;
        }
    }
    
    @media (max-width: 768px) {
        .node-figlio {
            flex: 0 0 280px;
            min-width: 280px;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .org-chart-container {
            padding: 1.5rem;
        }
        
        .node-compagnia {
            width: 100%;
            min-width: auto;
        }
    }
</style>
@endsection

@section('content')
    <!-- Header Minimal Solo Titolo -->
    <div class="text-center mb-4">
        <h1 class="page-title">Organigramma</h1>
    </div>

<div class="organigramma-wrapper">
    <!-- Toggle centrato -->
    <div class="d-flex justify-content-center mb-3">
        <div class="toggle-wrapper">
            <div id="toggleSlider" class="toggle-slider"></div>
            <div id="plotoniBtn" class="toggle-option active" onclick="switchView('plotoni')">
                <i class="fas fa-users"></i> Plotoni
            </div>
            <div id="poliBtn" class="toggle-option" onclick="switchView('poli')">
                <i class="fas fa-building"></i> Poli
            </div>
        </div>
    </div>
    
    <!-- Campo di ricerca centrato -->
    <div class="d-flex justify-content-center mb-4">
        <div class="search-container" style="position: relative; width: 400px;">
            <i class="fas fa-search search-icon" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #6c757d; pointer-events: none; z-index: 5;"></i>
            <input type="text" 
                   id="searchMilitare" 
                   class="form-control search-input" 
                   placeholder="Cerca militare..." 
                   aria-label="Cerca militare"
                   data-search-type="organigramma"
                   data-target-container="">
            <!-- Non creiamo searchSuggestions qui - lasciamo che search.js lo faccia -->
        </div>
    </div>

    <!-- Visualizzazione per Plotoni -->
    <div id="plotoniView" class="org-chart-container org-view">
        <div class="org-tree">
            <!-- Nodo Compagnia -->
            <div class="node-compagnia">
                <div class="node-compagnia-header">{{ $compagnia->nome }}</div>
                <div class="node-compagnia-content">
                    <div>
                        <span class="stat-label">Plotoni</span>
                        <span class="stat-value">{{ $compagnia->plotoni->count() }}</span>
                    </div>
                    <div>
                        <span class="stat-label">Militari</span>
                        <span class="stat-value">{{ \App\Models\Militare::whereIn('plotone_id', $compagnia->plotoni->pluck('id'))->count() }}</span>
                    </div>
                    <div>
                        <span class="stat-label">Presenti</span>
                        <span class="stat-value">{{ \App\Models\Militare::whereIn('plotone_id', $compagnia->plotoni->pluck('id'))->whereHas('presenzaOggi', function($q) { $q->where('stato', 'Presente'); })->count() }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Nodi Plotoni con scorrimento orizzontale -->
            @if($compagnia->plotoni->count() > 0)
                <div class="nodi-figli-container">
                    @if($compagnia->plotoni->count() > 3)
                    <button class="nav-arrow nav-prev" id="prevPlotoni" type="button" aria-label="Precedente">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    @endif
                    
                    <div class="nodi-figli {{ $compagnia->plotoni->count() <= 3 ? 'centered' : '' }}" id="plotoni-container">
                        @foreach($compagnia->plotoni as $plotone)
                            @php
                                $militari = $plotone->militari->sortBy(function($militare) {
                                    return optional($militare->grado)->ordine ?? 999;
                                });
                                $presentiCount = $militari->filter(function($militare) {
                                    return $militare->presenzaOggi && $militare->presenzaOggi->stato === 'Presente';
                                })->count();
                            @endphp
                            <div class="node-figlio" data-node-id="{{ $plotone->id }}">
                                <div class="node-connector"></div>
                                <div class="node-figlio-header">Plotone {{ $plotone->nome }}</div>
                                <div class="node-figlio-content">
                                    @if($militari->count() > 0)
                                        <ul class="lista-militari">
                                            @foreach($militari as $militare)
                                                @php
                                                    $isPresente = ($militare->presenzaOggi && $militare->presenzaOggi->stato === 'Presente');
                                                @endphp
                                                <li class="militare-item">
                                                    <span class="stato-presenza {{ $isPresente ? 'presente' : 'assente' }}" data-status="{{ $isPresente ? 'Presente' : 'Assente' }}"></span>
                                                    <div class="militare-info">
                                                        <div class="militare-grado">{{ $militare->grado->nome ?? '' }}</div>
                                                        <a href="{{ route('anagrafica.show', $militare->id) }}" class="link-name militare-nome">
                                                            <strong>{{ $militare->cognome }} {{ $militare->nome }}</strong>
                                                        </a>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="forza-effettiva">
                                            <span class="forza-label">Forza effettiva</span>
                                            <div class="forza-stats">
                                                <div class="forza-count totale-count">
                                                    <span class="count-value">{{ $militari->count() }}</span>
                                                    <span class="count-label">Totale</span>
                                                </div>
                                                <div class="forza-count presente-count">
                                                    <span class="count-value">{{ $presentiCount }}</span>
                                                    <span class="count-label">Presenti</span>
                                                </div>
                                                <div class="forza-count assente-count">
                                                    <span class="count-value">{{ $militari->count() - $presentiCount }}</span>
                                                    <span class="count-label">Assenti</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="testo-vuoto">
                                            <em>Nessun militare assegnato</em>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($compagnia->plotoni->count() > 3)
                    <button class="nav-arrow nav-next" id="nextPlotoni" type="button" aria-label="Successivo">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="scroll-indicator" id="plotoni-indicator">
                        @for($i = 0; $i < ceil($compagnia->plotoni->count() / 3); $i++)
                            <div class="indicator-dot {{ $i == 0 ? 'active' : '' }}" data-index="{{ $i }}"></div>
                        @endfor
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- Visualizzazione per Poli -->
    <div id="poliView" class="org-chart-container org-view hidden">
        <div class="org-tree">
            <!-- Nodo Compagnia -->
            <div class="node-compagnia">
                <div class="node-compagnia-header">{{ $compagnia->nome }}</div>
                <div class="node-compagnia-content">
                    <div>
                        <span class="stat-label">Poli</span>
                        <span class="stat-value">{{ $compagnia->poli->count() }}</span>
                    </div>
                    <div>
                        <span class="stat-label">Militari</span>
                        <span class="stat-value">{{ \App\Models\Militare::whereIn('polo_id', $compagnia->poli->pluck('id'))->count() }}</span>
                    </div>
                    <div>
                        <span class="stat-label">Presenti</span>
                        <span class="stat-value">{{ \App\Models\Militare::whereIn('polo_id', $compagnia->poli->pluck('id'))->whereHas('presenzaOggi', function($q) { $q->where('stato', 'Presente'); })->count() }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Nodi Poli con scorrimento orizzontale -->
            @if($compagnia->poli->count() > 0)
                <div class="nodi-figli-container">
                    @if($compagnia->poli->count() > 3)
                    <button class="nav-arrow nav-prev" id="prevPoli" type="button" aria-label="Precedente">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    @endif
                    
                    <div class="nodi-figli {{ $compagnia->poli->count() <= 3 ? 'centered' : '' }}" id="poli-container">
                        @foreach($compagnia->poli as $polo)
                            @php
                                $militari = $polo->militari->sortBy(function($militare) {
                                    return optional($militare->grado)->ordine ?? 999;
                                });
                                $presentiCount = $militari->filter(function($militare) {
                                    return $militare->presenzaOggi && $militare->presenzaOggi->stato === 'Presente';
                                })->count();
                            @endphp
                            <div class="node-figlio" data-node-id="{{ $polo->id }}">
                                <div class="node-connector"></div>
                                <div class="node-figlio-header">Polo {{ $polo->nome }}</div>
                                <div class="node-figlio-content">
                                    @if($militari->count() > 0)
                                        <ul class="lista-militari">
                                            @foreach($militari as $militare)
                                                @php
                                                    $isPresente = ($militare->presenzaOggi && $militare->presenzaOggi->stato === 'Presente');
                                                @endphp
                                                <li class="militare-item">
                                                    <span class="stato-presenza {{ $isPresente ? 'presente' : 'assente' }}" data-status="{{ $isPresente ? 'Presente' : 'Assente' }}"></span>
                                                    <div class="militare-info">
                                                        <div class="militare-grado">{{ $militare->grado->nome ?? '' }}</div>
                                                        <a href="{{ route('anagrafica.show', $militare->id) }}" class="link-name militare-nome">
                                                            <strong>{{ $militare->cognome }} {{ $militare->nome }}</strong>
                                                        </a>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="forza-effettiva">
                                            <span class="forza-label">Forza effettiva</span>
                                            <div class="forza-stats">
                                                <div class="forza-count totale-count">
                                                    <span class="count-value">{{ $militari->count() }}</span>
                                                    <span class="count-label">Totale</span>
                                                </div>
                                                <div class="forza-count presente-count">
                                                    <span class="count-value">{{ $presentiCount }}</span>
                                                    <span class="count-label">Presenti</span>
                                                </div>
                                                <div class="forza-count assente-count">
                                                    <span class="count-value">{{ $militari->count() - $presentiCount }}</span>
                                                    <span class="count-label">Assenti</span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="testo-vuoto">
                                            <em>Nessun militare assegnato</em>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($compagnia->poli->count() > 3)
                    <button class="nav-arrow nav-next" id="nextPoli" type="button" aria-label="Successivo">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                    
                    <div class="scroll-indicator" id="poli-indicator">
                        @for($i = 0; $i < ceil($compagnia->poli->count() / 3); $i++)
                            <div class="indicator-dot {{ $i == 0 ? 'active' : '' }}" data-index="{{ $i }}"></div>
                        @endfor
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
// Inizializzazione dopo il caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    // Aggiungi classe alla pagina per identificazione
    document.body.classList.add('page-organigramma');
    

    // Inizializza lo stato corrente e assicurati che solo la vista corretta sia visibile
    window.currentView = 'plotoni';
    forceViewVisibility('plotoni');
    
    // Configurazioni per lo scorrimento
    setupScrollContainers();
    
    // Aggiungi effetto hover al toggle
    const toggleWrapper = document.querySelector('.toggle-wrapper');
    if (toggleWrapper) {
        toggleWrapper.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        toggleWrapper.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    }
    
    // Aggiungi effetto pulsazione al toggleSlider al caricamento
    const toggleSlider = document.getElementById('toggleSlider');
    if (toggleSlider) {
        setTimeout(() => {
            toggleSlider.style.transform = 'translateX(0) scale(1.05)';
            setTimeout(() => {
                toggleSlider.style.transform = 'translateX(0) scale(1)';
            }, 300);
        }, 500);
    }
    
    // Click handlers per i pulsanti di navigazione
    const prevPlotoni = document.getElementById('prevPlotoni');
    if (prevPlotoni) {
        prevPlotoni.addEventListener('click', function() {
            scrollContainer('plotoni-container', 'prev');
        });
    }
    
    const nextPlotoni = document.getElementById('nextPlotoni');
    if (nextPlotoni) {
        nextPlotoni.addEventListener('click', function() {
            scrollContainer('plotoni-container', 'next');
        });
    }
    
    const prevPoli = document.getElementById('prevPoli');
    if (prevPoli) {
        prevPoli.addEventListener('click', function() {
            scrollContainer('poli-container', 'prev');
        });
    }
    
    const nextPoli = document.getElementById('nextPoli');
    if (nextPoli) {
        nextPoli.addEventListener('click', function() {
            scrollContainer('poli-container', 'next');
        });
    }
});

// Configurazione dei container scorrevoli
function setupScrollContainers() {
    const containers = ['plotoni-container', 'poli-container'];
    
    containers.forEach(containerId => {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Controlla se il container ha abbastanza elementi per richiedere lo scrolling
        const needsScrolling = !container.classList.contains('centered');
        
        if (needsScrolling) {
            // Configurazione iniziale dei pulsanti di navigazione
            updateNavigationArrows(containerId);
            
            // Aggiungi event listener per lo scorrimento
            container.addEventListener('scroll', function() {
                updateNavigationArrows(containerId);
                updateScrollIndicators(container);
            });
            
            // Imposta la posizione iniziale degli indicatori
            updateScrollIndicators(container);
            
            // Touch e wheel events per scorrimento su desktop
            setupTouchNavigation(container);
            
            // Listener click per gli indicatori
            const indicatorId = containerId.split('-')[0] + '-indicator';
            const indicator = document.getElementById(indicatorId);
            
            if (indicator) {
                const dots = indicator.querySelectorAll('.indicator-dot');
                dots.forEach(dot => {
                    dot.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        const itemWidth = 340; // Larghezza approssimativa di ogni nodo + gap
                        const scrollPosition = index * (itemWidth * 3); // 3 items per pagina
                        container.scrollTo({ left: scrollPosition, behavior: 'smooth' });
                    });
                });
            }
        }
    });
}

// Funzione per navigare nei container scorrevoli
function scrollContainer(containerId, direction) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const scrollAmount = container.clientWidth * 0.9; // Scorri del 90% della larghezza visibile
    
    if (direction === 'next') {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    } else {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    }
    
    // Aggiorna stato dei pulsanti e indicatori dopo lo scroll
    setTimeout(() => {
        updateNavigationArrows(containerId);
        updateScrollIndicators(container);
    }, 500);
}

// Aggiorna lo stato dei pulsanti di navigazione
function updateNavigationArrows(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const prefix = containerId.split('-')[0];
    const capitalized = prefix.charAt(0).toUpperCase() + prefix.slice(1);
    const prevBtn = document.getElementById(`prev${capitalized}`);
    const nextBtn = document.getElementById(`next${capitalized}`);
    
    // Verifica che i pulsanti esistano (non esisteranno se ci sono meno di 4 elementi)
    if (!prevBtn || !nextBtn) return;
    
    // Verifica se c'è margine di scorrimento a sinistra o a destra
    const isAtStart = container.scrollLeft <= 10;
    const isAtEnd = container.scrollLeft + container.clientWidth >= container.scrollWidth - 10;
    
    // Aggiorna classi e stati dei pulsanti
    prevBtn.classList.toggle('disabled', isAtStart);
    nextBtn.classList.toggle('disabled', isAtEnd);
}

// Aggiorna gli indicatori di scorrimento
function updateScrollIndicators(container) {
    if (!container) return;
    
    const indicatorId = `${container.id.split('-')[0]}-indicator`;
    const indicator = document.getElementById(indicatorId);
    
    // Verifica che l'indicatore esista (non esisterà se ci sono meno di 4 elementi)
    if (!indicator) return;
    
    // Calcola l'indice attuale in base alla posizione dello scroll
    const scrollPosition = container.scrollLeft;
    const containerWidth = container.clientWidth;
    const scrollWidth = container.scrollWidth;
    
    // Calcola la percentuale di progresso
    const scrollProgress = scrollPosition / (scrollWidth - containerWidth);
    
    // Numero totale di punti indicatori
    const totalDots = indicator.querySelectorAll('.indicator-dot').length;
    
    // Determina quale punto deve essere attivo
    let activeIndex = Math.min(Math.floor(scrollProgress * totalDots), totalDots - 1);
    if (isNaN(activeIndex)) activeIndex = 0;
    
    // Aggiorna le classi dei punti indicatori
    indicator.querySelectorAll('.indicator-dot').forEach((dot, index) => {
        dot.classList.toggle('active', index === activeIndex);
    });
}

// Setup per la navigazione touch e mouse wheel
function setupTouchNavigation(container) {
    if (!container) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    
    // Eventi mouse per drag su desktop
    container.addEventListener('mousedown', (e) => {
        isDown = true;
        container.style.cursor = 'grabbing';
        startX = e.pageX - container.offsetLeft;
        scrollLeft = container.scrollLeft;
        e.preventDefault();
    });
    
    container.addEventListener('mouseleave', () => {
        isDown = false;
        container.style.cursor = '';
    });
    
    container.addEventListener('mouseup', () => {
        isDown = false;
        container.style.cursor = '';
    });
    
    container.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - container.offsetLeft;
        const walk = (x - startX) * 1.5; // Moltiplicatore velocità
        container.scrollLeft = scrollLeft - walk;
    });
}

// Funzione migliorata per forzare la visibilità corretta
function forceViewVisibility(view) {
    // Assicurati che tutte le viste siano nascoste prima
    const plotoniView = document.getElementById('plotoniView');
    const poliView = document.getElementById('poliView');
    
    if (plotoniView) {
        plotoniView.classList.add('hidden');
        plotoniView.style.display = 'none';
    }
    
    if (poliView) {
        poliView.classList.add('hidden');
        poliView.style.display = 'none';
    }
    
    // Mostra solo la vista corrente
    if (view === 'plotoni' && plotoniView) {
        plotoniView.classList.remove('hidden');
        plotoniView.style.display = 'block';
    } else if (view === 'poli' && poliView) {
        poliView.classList.remove('hidden');
        poliView.style.display = 'block';
    }
}

// Switch view (Plotoni/Poli)
function switchView(view) {
    // Salva lo stato corrente
    window.currentView = view;
    
    // Forza la visibilità corretta
    forceViewVisibility(view);
    
    // Aggiorna le classi attive sui pulsanti
    document.getElementById('plotoniBtn').classList.toggle('active', view === 'plotoni');
    document.getElementById('poliBtn').classList.toggle('active', view === 'poli');
    
    // Sposta lo slider con effetto pulsazione
    const toggleSlider = document.getElementById('toggleSlider');
    toggleSlider.style.transform = view === 'poli' ? 'translateX(100%) scale(1.05)' : 'translateX(0) scale(1.05)';
    
    setTimeout(() => {
        toggleSlider.style.transform = view === 'poli' ? 'translateX(100%) scale(1)' : 'translateX(0) scale(1)';
    }, 300);
    
    // Aggiorna la classe dello slider
    toggleSlider.classList.toggle('poli', view === 'poli');
    
    // Aggiorna l'animazione all'elemento visualizzato
    if (view === 'plotoni') {
        document.getElementById('plotoniView').style.animation = 'none';
        setTimeout(() => {
            document.getElementById('plotoniView').style.animation = 'scaleIn 0.5s ease-out';
        }, 10);
    } else {
        document.getElementById('poliView').style.animation = 'none';
        setTimeout(() => {
            document.getElementById('poliView').style.animation = 'scaleIn 0.5s ease-out';
        }, 10);
    }
    
    // Reset della posizione di scorrimento e aggiornamento dei controlli di navigazione
    setTimeout(() => {
        const containerId = view === 'plotoni' ? 'plotoni-container' : 'poli-container';
        const container = document.getElementById(containerId);
        if (container) {
            container.scrollLeft = 0;
            updateNavigationArrows(containerId);
            updateScrollIndicators(container);
        }
    }, 300);
}
</script>
@endpush
