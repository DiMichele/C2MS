@extends('layouts.app')
@section('title', 'Organigramma - ' . ($activeUnit->name ?? 'SUGECO'))

@section('styles')
<style>
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
        --info: #1976D2;
        --info-light: rgba(25, 118, 210, 0.15);
    }

    /* Header dell'unità */
    .unit-header-card {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        border-radius: 16px;
        padding: 2rem;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.2);
    }

    .unit-header-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .unit-icon-large {
        width: 80px;
        height: 80px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(10px);
    }

    .unit-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
    }

    .unit-type-badge {
        background: rgba(255,255,255,0.2);
        padding: 0.35rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-top: 0.5rem;
        display: inline-block;
    }

    /* Statistiche */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        text-align: center;
        box-shadow: 0 4px 15px rgba(10, 35, 66, 0.08);
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.75rem;
        font-size: 1.1rem;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
    }

    /* Container principale */
    .org-container {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.08);
    }

    /* Toolbar */
    .org-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .search-box {
        position: relative;
        flex: 1;
        max-width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.75rem;
        border: 2px solid var(--gray-200);
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.2s;
    }

    .search-box input:focus {
        outline: none;
        border-color: var(--navy);
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-500);
    }

    .toolbar-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .toolbar-btn {
        padding: 0.6rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        border: 2px solid var(--gray-200);
        background: white;
        color: var(--navy);
        cursor: pointer;
        transition: all 0.2s;
    }

    .toolbar-btn:hover {
        border-color: var(--navy);
        background: var(--gray-100);
    }

    .toolbar-btn i {
        margin-right: 0.4rem;
    }

    /* Toggle Vista Plotoni/Uffici */
    .view-toggle {
        display: flex;
        background: var(--gray-100);
        border-radius: 10px;
        padding: 4px;
    }

    .view-toggle-btn {
        padding: 0.6rem 1.25rem;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.25s;
        color: var(--gray-500);
        font-weight: 500;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .view-toggle-btn.active {
        background: white;
        color: var(--navy);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .view-toggle-btn i {
        font-size: 0.85rem;
    }

    /* Contenuti delle viste */
    .view-content {
        display: none;
    }

    .view-content.active {
        display: block;
    }

    /* Albero gerarchico */
    .hierarchy-container {
        padding: 0.5rem 0;
    }

    /* Nodo unità */
    .unit-node {
        margin-bottom: 0.75rem;
    }

    .unit-node-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        background: white;
        border: 2px solid var(--gray-200);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }

    .unit-node-header:hover {
        border-color: var(--navy-light);
        background: var(--gray-100);
    }

    .unit-node.expanded > .unit-node-header {
        border-color: var(--navy);
        background: var(--gray-100);
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    .expand-icon {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--gray-100);
        color: var(--gray-500);
        font-size: 0.7rem;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .unit-node.expanded > .unit-node-header .expand-icon {
        background: var(--navy);
        color: white;
        transform: rotate(90deg);
    }

    .unit-node-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .unit-node-info {
        flex: 1;
        min-width: 0;
    }

    .unit-node-name {
        font-weight: 600;
        color: var(--navy);
        font-size: 0.95rem;
    }

    .unit-node-type {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .unit-node-count {
        background: var(--gray-100);
        padding: 0.3rem 0.7rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--navy);
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .unit-node-count i {
        color: var(--gray-500);
        font-size: 0.7rem;
    }

    /* Children container */
    .unit-children {
        display: none;
        margin-left: 1.5rem;
        padding: 0.75rem 0 0.75rem 1rem;
        border-left: 2px solid var(--gray-200);
    }

    .unit-node.expanded > .unit-children {
        display: block;
    }

    /* Militari list */
    .militari-list {
        display: none;
        background: var(--gray-100);
        border: 2px solid var(--gray-200);
        border-top: none;
        border-radius: 0 0 10px 10px;
        padding: 0.75rem;
        margin-top: -2px;
    }

    .unit-node.expanded > .militari-list {
        display: block;
    }

    .militare-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 0.75rem;
        background: white;
        border-radius: 8px;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
        cursor: pointer;
    }

    .militare-item:last-child {
        margin-bottom: 0;
    }

    .militare-item:hover {
        box-shadow: 0 2px 8px rgba(10, 35, 66, 0.1);
    }

    .militare-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--navy);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .militare-info {
        flex: 1;
        min-width: 0;
    }

    .militare-name {
        font-weight: 600;
        color: var(--navy);
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .militare-grado {
        font-size: 0.75rem;
        color: var(--gray-500);
    }

    .militare-badge {
        padding: 0.2rem 0.5rem;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .militare-badge.presente {
        background: var(--success-light);
        color: var(--success);
    }

    .militare-badge.assente {
        background: rgba(211, 47, 47, 0.1);
        color: #D32F2F;
    }

    .militare-badge.ufficio {
        background: var(--info-light);
        color: var(--info);
    }

    /* Ufficio card per vista uffici */
    .ufficio-card {
        background: white;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        margin-bottom: 1rem;
        overflow: hidden;
        transition: all 0.2s;
    }

    .ufficio-card:hover {
        border-color: var(--info);
    }

    .ufficio-card-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: var(--gray-100);
        cursor: pointer;
    }

    .ufficio-card.expanded .ufficio-card-header {
        background: var(--info-light);
    }

    .ufficio-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: var(--info);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .ufficio-info h4 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: var(--navy);
    }

    .ufficio-info p {
        margin: 0.25rem 0 0;
        font-size: 0.8rem;
        color: var(--gray-500);
    }

    .ufficio-count {
        margin-left: auto;
        background: white;
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--navy);
    }

    .ufficio-toggle {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        color: var(--gray-500);
        transition: all 0.2s;
    }

    .ufficio-card.expanded .ufficio-toggle {
        transform: rotate(180deg);
        background: var(--info);
        color: white;
    }

    .ufficio-militari {
        display: none;
        padding: 1rem;
    }

    .ufficio-card.expanded .ufficio-militari {
        display: block;
    }

    .ufficio-empty {
        text-align: center;
        padding: 2rem;
        color: var(--gray-500);
    }

    /* Depth styling - colori diversi per livello */
    .depth-1 > .unit-node-header .unit-node-icon { background-color: var(--navy); }
    .depth-2 > .unit-node-header .unit-node-icon { background-color: var(--navy-light); }
    .depth-3 > .unit-node-header .unit-node-icon { background-color: var(--gold); }
    .depth-4 > .unit-node-header .unit-node-icon { background-color: #6B7280; }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: var(--gray-500);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .unit-header-content {
            flex-direction: column;
            text-align: center;
        }

        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }

        .org-toolbar {
            flex-direction: column;
            align-items: stretch;
        }

        .search-box {
            max-width: none;
        }

        .unit-children {
            margin-left: 0.75rem;
            padding-left: 0.5rem;
        }

        .view-toggle {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Unità -->
    <div class="unit-header-card">
        <div class="unit-header-content">
            <div class="unit-icon-large">
                <i class="fas {{ $activeUnit->type->icon ?? 'fa-building' }}"></i>
            </div>
            <div>
                <h1 class="unit-title">{{ $activeUnit->name }}</h1>
                <span class="unit-type-badge">
                    <i class="fas {{ $activeUnit->type->icon ?? 'fa-tag' }} me-1"></i>
                    {{ $activeUnit->type->name ?? 'Unità Organizzativa' }}
                </span>
            </div>
        </div>
    </div>

    <!-- Statistiche -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(10, 35, 66, 0.1); color: var(--navy);">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="stat-number">{{ $stats['direct_children'] }}</div>
            <div class="stat-label">Unità Subordinate</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(191, 157, 94, 0.15); color: var(--gold);">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="stat-number">{{ $stats['total_units'] }}</div>
            <div class="stat-label">Totale Sotto-unità</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(46, 125, 50, 0.15); color: var(--success);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-number">{{ $stats['total_militari'] }}</div>
            <div class="stat-label">Militari (Plotoni)</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--info-light); color: var(--info);">
                <i class="fas fa-briefcase"></i>
            </div>
            <div class="stat-number">{{ $stats['total_uffici'] ?? 0 }}</div>
            <div class="stat-label">Uffici</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(156, 39, 176, 0.1); color: #9C27B0;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-number">{{ $stats['total_militari_uffici'] ?? 0 }}</div>
            <div class="stat-label">Militari (Uffici)</div>
        </div>
    </div>

    <!-- Container principale -->
    <div class="org-container">
        <!-- Toolbar -->
        <div class="org-toolbar">
            <!-- Toggle Vista -->
            <div class="view-toggle">
                <button class="view-toggle-btn active" data-view="plotoni">
                    <i class="fas fa-users"></i>
                    Plotoni
                </button>
                <button class="view-toggle-btn" data-view="uffici">
                    <i class="fas fa-briefcase"></i>
                    Uffici
                </button>
            </div>

            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cerca unità o militare...">
            </div>
            
            <div class="toolbar-buttons">
                <button class="toolbar-btn" id="expandAllBtn">
                    <i class="fas fa-expand-alt"></i>Espandi
                </button>
                <button class="toolbar-btn" id="collapseAllBtn">
                    <i class="fas fa-compress-alt"></i>Comprimi
                </button>
            </div>
        </div>

        <!-- VISTA PLOTONI -->
        <div class="view-content active" id="vistaPlotoni">
            @php
                $directChildren = $units->where('parent_id', $activeUnit->id);
            @endphp

            @if($directChildren->count() > 0)
            <div class="hierarchy-container" id="hierarchyContainer">
                @foreach($directChildren as $child)
                    @include('organigramma.partials._unit_node_recursive', [
                        'unit' => $child, 
                        'allUnits' => $units,
                        'depth' => 1,
                        'ufficiDisponibili' => $ufficiDisponibili ?? collect()
                    ])
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h5>Nessuna sotto-unità</h5>
                <p>Questa unità non ha sotto-unità organizzative.</p>
            </div>
            @endif
        </div>

        <!-- VISTA UFFICI -->
        <div class="view-content" id="vistaUffici">
            @if(isset($poli) && $poli->count() > 0)
                @foreach($poli as $polo)
                @php
                    $militariUfficio = $militariByUfficio[$polo->id] ?? collect();
                @endphp
                <div class="ufficio-card" data-ufficio-id="{{ $polo->id }}">
                    <div class="ufficio-card-header" onclick="this.closest('.ufficio-card').classList.toggle('expanded')">
                        <div class="ufficio-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="ufficio-info">
                            <h4>{{ $polo->nome }}</h4>
                            <p>Ufficio</p>
                        </div>
                        <div class="ufficio-count">
                            <i class="fas fa-users me-1"></i>{{ $militariUfficio->count() }}
                        </div>
                        <div class="ufficio-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="ufficio-militari">
                        @if($militariUfficio->count() > 0)
                            @foreach($militariUfficio->sortBy(fn($m) => ($m->grado->ordine ?? 999) . $m->cognome) as $militare)
                            <div class="militare-item">
                                <div class="militare-avatar">
                                    {{ strtoupper(substr($militare->nome ?? '', 0, 1)) }}{{ strtoupper(substr($militare->cognome ?? '', 0, 1)) }}
                                </div>
                                <div class="militare-info">
                                    <div class="militare-name">
                                        {{ $militare->grado->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                                    </div>
                                    <div class="militare-grado">{{ $militare->grado->nome ?? 'N/D' }}</div>
                                </div>
                                @if($militare->plotone)
                                <span class="militare-badge ufficio" title="Plotone di appartenenza">
                                    <i class="fas fa-users me-1"></i>{{ $militare->plotone->nome ?? 'N/D' }}
                                </span>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <div class="ufficio-empty">
                                <i class="fas fa-user-slash fa-2x mb-2" style="opacity: 0.3;"></i>
                                <p>Nessun militare assegnato a questo ufficio</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach
            @else
            <div class="empty-state">
                <i class="fas fa-briefcase"></i>
                <h5>Nessun ufficio</h5>
                <p>Nessun ufficio configurato nel sistema.</p>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle vista Plotoni/Uffici
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Aggiorna bottoni
            document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Mostra/nascondi contenuto
            const view = this.dataset.view;
            document.querySelectorAll('.view-content').forEach(v => v.classList.remove('active'));
            document.getElementById('vista' + view.charAt(0).toUpperCase() + view.slice(1)).classList.add('active');
        });
    });

    // Toggle expand/collapse unità
    document.querySelectorAll('.unit-node-header').forEach(header => {
        header.addEventListener('click', function(e) {
            e.stopPropagation();
            const node = this.closest('.unit-node');
            node.classList.toggle('expanded');
        });
    });

    // Expand all
    document.getElementById('expandAllBtn')?.addEventListener('click', function() {
        document.querySelectorAll('.unit-node, .ufficio-card').forEach(node => {
            node.classList.add('expanded');
        });
    });

    // Collapse all
    document.getElementById('collapseAllBtn')?.addEventListener('click', function() {
        document.querySelectorAll('.unit-node, .ufficio-card').forEach(node => {
            node.classList.remove('expanded');
        });
    });

    // Search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (!query) {
                document.querySelectorAll('.unit-node, .militare-item, .ufficio-card').forEach(el => {
                    el.style.display = '';
                });
                document.querySelectorAll('.unit-node, .ufficio-card').forEach(node => {
                    node.classList.remove('expanded');
                });
                return;
            }
            
            // Cerca nelle unità (vista plotoni)
            document.querySelectorAll('.unit-node').forEach(node => {
                node.style.display = 'none';
            });
            
            document.querySelectorAll('.unit-node').forEach(node => {
                const name = node.querySelector('.unit-node-name')?.textContent.toLowerCase() || '';
                if (name.includes(query)) {
                    showNodeAndParents(node);
                }
            });
            
            // Cerca nei militari (vista plotoni)
            document.querySelectorAll('#vistaPlotoni .militare-item').forEach(item => {
                const name = item.querySelector('.militare-name')?.textContent.toLowerCase() || '';
                const grado = item.querySelector('.militare-grado')?.textContent.toLowerCase() || '';
                
                if (name.includes(query) || grado.includes(query)) {
                    item.style.display = '';
                    let parent = item.closest('.unit-node');
                    while (parent) {
                        showNodeAndParents(parent);
                        parent.classList.add('expanded');
                        parent = parent.parentElement?.closest('.unit-node');
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            // Cerca negli uffici
            document.querySelectorAll('.ufficio-card').forEach(card => {
                const name = card.querySelector('.ufficio-info h4')?.textContent.toLowerCase() || '';
                const militariMatch = Array.from(card.querySelectorAll('.militare-item')).some(item => {
                    const mName = item.querySelector('.militare-name')?.textContent.toLowerCase() || '';
                    return mName.includes(query);
                });
                
                if (name.includes(query) || militariMatch) {
                    card.style.display = '';
                    if (militariMatch) card.classList.add('expanded');
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    function showNodeAndParents(node) {
        node.style.display = '';
        let parent = node.parentElement?.closest('.unit-node');
        while (parent) {
            parent.style.display = '';
            parent.classList.add('expanded');
            parent = parent.parentElement?.closest('.unit-node');
        }
    }

});

</script>
@endpush
