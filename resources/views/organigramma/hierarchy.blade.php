@extends('layouts.app')
@section('title', 'Organigramma')

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
    }

    /* Container principale */
    .org-viewer-container {
        background: white;
        border-radius: 16px;
        padding: 2rem;
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.08);
        min-height: 600px;
    }

    /* Albero gerarchico */
    .hierarchy-tree {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .hierarchy-tree ul {
        list-style: none;
        padding-left: 2rem;
        margin: 0;
    }

    .tree-item {
        position: relative;
        padding: 0.5rem 0;
    }

    .tree-item::before {
        content: '';
        position: absolute;
        left: -1.5rem;
        top: 0;
        height: 100%;
        width: 2px;
        background: var(--gray-300);
    }

    .tree-item:last-child::before {
        height: 1.3rem;
    }

    .tree-item::after {
        content: '';
        position: absolute;
        left: -1.5rem;
        top: 1.3rem;
        width: 1.5rem;
        height: 2px;
        background: var(--gray-300);
    }

    .hierarchy-tree > .tree-item::before,
    .hierarchy-tree > .tree-item::after {
        display: none;
    }

    .tree-node {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.25rem;
        background: white;
        border: 2px solid var(--gray-200);
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .tree-node:hover {
        border-color: var(--gold);
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .tree-node.highlighted {
        border-color: var(--gold);
        background: rgba(191, 157, 94, 0.1);
    }

    .tree-node .node-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }

    .tree-node .node-name {
        font-weight: 600;
        color: var(--navy);
    }

    .tree-node .node-type {
        font-size: 0.75rem;
        color: var(--gray-700);
    }

    .tree-node .node-count {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: var(--gray-500);
        margin-left: 0.5rem;
        padding: 0.25rem 0.5rem;
        background: var(--gray-100);
        border-radius: 12px;
    }

    /* Toggle figli */
    .toggle-children {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: var(--gray-100);
        border: 1px solid var(--gray-300);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 0.5rem;
    }

    .toggle-children:hover {
        background: var(--navy-light);
        color: white;
    }

    .toggle-children.collapsed i {
        transform: rotate(-90deg);
    }

    .tree-children {
        display: block;
    }

    .tree-children.hidden {
        display: none;
    }

    /* Search box */
    .search-box {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .search-box input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 3rem;
        border: 2px solid var(--gray-200);
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }

    .search-box input:focus {
        border-color: var(--gold);
        outline: none;
        box-shadow: 0 0 0 3px rgba(191, 157, 94, 0.15);
    }

    .search-box i {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-500);
    }

    /* Filtro */
    .filter-row {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }

    .filter-row .form-select {
        max-width: 300px;
    }

    /* Toolbar buttons */
    .toolbar-btn {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    /* Tooltip */
    .node-tooltip {
        position: absolute;
        background: var(--navy);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        max-width: 300px;
        display: none;
    }

    .node-tooltip.visible {
        display: block;
    }

    .node-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 1rem;
        border: 6px solid transparent;
        border-top-color: var(--navy);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-1">Organigramma</h1>
            <p class="text-muted mb-0">Visualizzazione struttura organizzativa</p>
        </div>
        <div class="d-flex gap-2">
            @if($canEdit)
            <a href="{{ route('gerarchia.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-2"></i>Modifica Organigramma
            </a>
            @endif
            <button class="btn btn-success" id="exportExcelBtn">
                <i class="fas fa-file-excel me-2"></i>Esporta Excel
            </button>
        </div>
    </div>

    <!-- Filtri -->
    <div class="filter-row">
        <div class="search-box flex-grow-1" style="max-width: 400px;">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cerca unità o militare...">
        </div>
        <select class="form-select" id="filterUnit">
            <option value="">Tutte le unità accessibili</option>
            @foreach($accessibleUnits as $unit)
                <option value="{{ $unit->id }}">
                    {{ str_repeat('— ', $unit->depth ?? 0) }}{{ $unit->name }}
                </option>
            @endforeach
        </select>
        <button class="btn btn-outline-secondary toolbar-btn" id="expandAllBtn">
            <i class="fas fa-expand me-1"></i> Espandi
        </button>
        <button class="btn btn-outline-secondary toolbar-btn" id="collapseAllBtn">
            <i class="fas fa-compress me-1"></i> Comprimi
        </button>
    </div>

    <!-- Contenuto -->
    <div class="org-viewer-container">
        @if(count($tree) > 0)
        <ul class="hierarchy-tree" id="hierarchyTree">
            @foreach($tree as $node)
                @include('organigramma.partials._tree_node', ['node' => $node])
            @endforeach
        </ul>
        @else
        <div class="text-center py-5">
            <i class="fas fa-sitemap fa-4x text-muted mb-3"></i>
            <h5>Nessuna unità organizzativa trovata</h5>
            <p class="text-muted">Non hai accesso a nessuna unità organizzativa o non ne sono state create.</p>
            @if($canEdit)
            <a href="{{ route('gerarchia.index') }}" class="btn btn-primary mt-3">
                <i class="fas fa-plus me-2"></i>Crea la prima unità
            </a>
            @endif
        </div>
        @endif
    </div>
</div>

<!-- Tooltip -->
<div class="node-tooltip" id="nodeTooltip"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tree = document.getElementById('hierarchyTree');
    const searchInput = document.getElementById('searchInput');
    const filterUnit = document.getElementById('filterUnit');
    const tooltip = document.getElementById('nodeTooltip');

    // Toggle figli
    document.querySelectorAll('.toggle-children').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const item = this.closest('.tree-item');
            const children = item.querySelector('.tree-children');
            if (children) {
                children.classList.toggle('hidden');
                this.classList.toggle('collapsed');
            }
        });
    });

    // Espandi tutto
    document.getElementById('expandAllBtn').addEventListener('click', function() {
        document.querySelectorAll('.tree-children').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('.toggle-children').forEach(el => el.classList.remove('collapsed'));
    });

    // Comprimi tutto
    document.getElementById('collapseAllBtn').addEventListener('click', function() {
        document.querySelectorAll('.tree-children').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.toggle-children').forEach(el => el.classList.add('collapsed'));
    });

    // Ricerca
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        
        document.querySelectorAll('.tree-node').forEach(node => {
            const text = node.textContent.toLowerCase();
            const matches = term === '' || text.includes(term);
            
            node.classList.toggle('highlighted', matches && term !== '');
            
            // Mostra/nascondi in base alla ricerca
            const item = node.closest('.tree-item');
            if (term && !matches) {
                // Nascondi solo se non ha figli che corrispondono
                const hasMatchingChild = item.querySelector('.tree-node.highlighted');
                if (!hasMatchingChild) {
                    item.style.opacity = '0.3';
                } else {
                    item.style.opacity = '1';
                }
            } else {
                item.style.opacity = '1';
            }
        });

        // Se c'è una ricerca, espandi tutto per mostrare i risultati
        if (term) {
            document.querySelectorAll('.tree-children').forEach(el => el.classList.remove('hidden'));
            document.querySelectorAll('.toggle-children').forEach(el => el.classList.remove('collapsed'));
        }
    });

    // Filtro unità
    filterUnit.addEventListener('change', function() {
        const unitId = this.value;
        
        if (!unitId) {
            // Mostra tutto
            document.querySelectorAll('.tree-item').forEach(item => {
                item.style.display = '';
            });
            return;
        }

        // Nascondi tutto, poi mostra solo il ramo selezionato
        document.querySelectorAll('.tree-item').forEach(item => {
            const nodeId = item.querySelector('.tree-node')?.dataset.unitId;
            if (nodeId === unitId || isDescendantOf(item, unitId) || isAncestorOf(item, unitId)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    function isDescendantOf(item, ancestorId) {
        let parent = item.parentElement?.closest('.tree-item');
        while (parent) {
            const nodeId = parent.querySelector('.tree-node')?.dataset.unitId;
            if (nodeId === ancestorId) return true;
            parent = parent.parentElement?.closest('.tree-item');
        }
        return false;
    }

    function isAncestorOf(item, descendantId) {
        const descendants = item.querySelectorAll('.tree-node');
        for (const node of descendants) {
            if (node.dataset.unitId === descendantId) return true;
        }
        return false;
    }

    // Tooltip al hover
    document.querySelectorAll('.tree-node').forEach(node => {
        node.addEventListener('mouseenter', function(e) {
            const name = this.dataset.name || this.querySelector('.node-name')?.textContent || '';
            const type = this.dataset.type || '';
            const count = this.dataset.count || '0';
            
            tooltip.innerHTML = `
                <strong>${name}</strong><br>
                <small>${type}</small><br>
                <i class="fas fa-users me-1"></i>${count} militari
            `;
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            tooltip.classList.add('visible');
        });

        node.addEventListener('mouseleave', function() {
            tooltip.classList.remove('visible');
        });
    });

    // Export Excel
    document.getElementById('exportExcelBtn').addEventListener('click', function() {
        window.location.href = '{{ route("gerarchia.export-excel") }}';
    });
});
</script>
@endpush
