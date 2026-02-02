@extends('layouts.app')
@section('title', 'Organigramma - SUGECO')

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
        --success: #28a745;
        --danger: #D32F2F;
        --ufficio-color: #6f42c1;
    }

    /* Container principale */
    .organigramma-wrapper {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(10, 35, 66, 0.08);
        min-height: calc(100vh - 200px);
    }

    /* Header con controlli */
    .organigramma-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        background: var(--gray-100);
    }

    .organigramma-controls {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    /* Toggle switches */
    .control-switch {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.4rem 0.8rem;
        background: white;
        border-radius: 6px;
        border: 1px solid var(--gray-300);
        font-size: 0.875rem;
    }

    .control-switch .form-check-input:checked {
        background-color: var(--navy);
        border-color: var(--navy);
    }

    /* Toggle Vista Plotoni/Uffici */
    .view-toggle {
        display: flex;
        background: white;
        border-radius: 6px;
        border: 1px solid var(--gray-300);
        overflow: hidden;
    }

    .view-toggle-btn {
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--navy);
    }

    .view-toggle-btn:hover {
        background: var(--gray-100);
    }

    .view-toggle-btn.active {
        background: var(--navy);
        color: white;
    }

    .view-toggle-btn i {
        margin-right: 0.4rem;
    }

    /* Area organigramma scrollabile */
    .organigramma-scroll {
        overflow: auto;
        padding: 2rem;
        min-height: 500px;
    }

    /* Loading */
    .loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }

    .spinner-wrapper .spinner-border {
        width: 2.5rem;
        height: 2.5rem;
        color: var(--navy);
    }

    /* ============================================
       ORGANIGRAMMA - STRUTTURA AD ALBERO
       ============================================ */
    .org-tree {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        min-width: fit-content;
    }

    .org-tree ul {
        padding-top: 20px;
        position: relative;
        display: flex;
        justify-content: center;
        list-style: none;
        margin: 0;
        padding-left: 0;
    }

    .org-tree li {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        padding: 20px 10px 0 10px;
    }

    /* Linee di connessione */
    .org-tree li::before,
    .org-tree li::after {
        content: '';
        position: absolute;
        top: 0;
        width: 50%;
        height: 20px;
        border-top: 2px solid var(--navy);
    }

    .org-tree li::before {
        right: 50%;
        border-right: 2px solid var(--navy);
    }

    .org-tree li::after {
        left: 50%;
        border-left: 2px solid var(--navy);
    }

    /* Rimuovi linee per primo e ultimo figlio */
    .org-tree li:first-child::before {
        border: none;
    }

    .org-tree li:last-child::after {
        border: none;
    }

    .org-tree li:only-child::before,
    .org-tree li:only-child::after {
        border: none;
    }

    /* Linea verticale dal parent */
    .org-tree ul::before {
        content: '';
        position: absolute;
        top: 0;
        left: 50%;
        border-left: 2px solid var(--navy);
        height: 20px;
    }

    /* Primo livello non ha linea sopra */
    .org-tree > ul::before {
        display: none;
    }

    /* ============================================
       NODI ORGANIGRAMMA
       ============================================ */
    .org-node {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        transition: transform 0.2s;
        padding-bottom: 15px;
    }

    .org-node:hover {
        transform: translateY(-2px);
    }

    .org-node-box {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: white;
        padding: 12px 16px;
        border-radius: 8px;
        text-align: center;
        min-width: 130px;
        max-width: 200px;
        box-shadow: 0 4px 12px rgba(10, 35, 66, 0.25);
        position: relative;
    }

    .org-node.has-children .org-node-box::after {
        content: '';
        position: absolute;
        bottom: -20px;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 20px;
        background: var(--navy);
    }

    .org-node-name {
        font-weight: 600;
        font-size: 0.85rem;
        line-height: 1.3;
        margin-bottom: 2px;
    }


    /* Pulsante Toggle - COMPLETAMENTE RIFATTO */
    .org-toggle-btn {
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 26px;
        height: 26px;
        background: var(--navy);
        color: white;
        border: 3px solid white;
        border-radius: 50%;
        font-size: 16px;
        font-weight: bold;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 50;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        transition: all 0.15s ease;
        user-select: none;
    }

    .org-toggle-btn:hover {
        background: var(--gold);
        transform: translateX(-50%) scale(1.15);
    }

    .org-toggle-btn:active {
        transform: translateX(-50%) scale(0.95);
    }

    /* Stili per profondità diverse - Palette distintiva per livello */
    /* Reggimento (depth 0) - Blu navy scuro */
    .depth-0 .org-node-box {
        background: linear-gradient(135deg, #1a365d 0%, #234876 100%);
        min-width: 160px;
        padding: 14px 20px;
    }

    .depth-0 .org-node-name {
        font-size: 0.95rem;
    }

    /* Battaglione (depth 1) - Blu medio */
    .depth-1 .org-node-box {
        background: linear-gradient(135deg, #2c5282 0%, #3d6898 100%);
    }

    /* Compagnia (depth 2) - Blu chiaro */
    .depth-2 .org-node-box {
        background: linear-gradient(135deg, #2b6cb0 0%, #3c7dc1 100%);
    }

    /* Plotone (depth 3) - Azzurro */
    .depth-3 .org-node-box {
        background: linear-gradient(135deg, #4299e1 0%, #54aaf0 100%);
    }

    /* Ufficio/Sezione (depth 4+) - Azzurro chiaro */
    .depth-4 .org-node-box,
    .depth-5 .org-node-box {
        background: linear-gradient(135deg, #63b3ed 0%, #75c4fc 100%);
        min-width: 110px;
        padding: 10px 12px;
    }

    .depth-4 .org-node-name,
    .depth-5 .org-node-name {
        font-size: 0.8rem;
    }

    /* Nodo Ufficio */
    .org-node.ufficio .org-node-box {
        background: linear-gradient(135deg, var(--ufficio-color) 0%, #5a32a3 100%);
    }

    /* Nodo Militare */
    .org-node.militare .org-node-box {
        background: linear-gradient(135deg, var(--success) 0%, #218838 100%);
        min-width: 100px;
        max-width: 150px;
        padding: 8px 10px;
    }

    .org-node.militare .org-node-name {
        font-size: 0.75rem;
    }

    /* Linee per militari - colore diverso */
    .has-militari > ul::before,
    .has-militari li::before,
    .has-militari li::after {
        border-color: var(--success);
    }

    /* Figli nascosti (collapsed) */
    .org-tree li.collapsed > ul {
        display: none !important;
    }

    .org-tree li.collapsed .org-node.has-children .org-node-box::after {
        display: none;
    }

    /* ============================================
       MODAL STYLES
       ============================================ */
    .modal-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: white;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .form-label {
        font-weight: 600;
        color: var(--navy);
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid var(--gray-200);
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 0.2rem rgba(191, 157, 94, 0.25);
    }

    /* Lista azioni */
    .actions-list .list-group-item {
        border: none;
        padding: 12px 16px;
        cursor: pointer;
    }

    .actions-list .list-group-item:hover {
        background: var(--gray-100);
    }

    .actions-list .list-group-item.text-danger:hover {
        background: rgba(211, 47, 47, 0.08);
    }

    /* ============================================
       RESPONSIVE
       ============================================ */
    @media (max-width: 992px) {
        .org-node-box {
            min-width: 100px;
            max-width: 140px;
            padding: 10px 12px;
        }

        .org-node-name {
            font-size: 0.75rem;
        }

        .org-tree li {
            padding: 15px 5px 0 5px;
        }
    }

    @media (max-width: 576px) {
        .organigramma-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    {{-- Header pagina --}}
    <div class="text-center mb-4">
        <h1 class="page-title">Organigramma</h1>
    </div>

    {{-- Container principale --}}
    <div class="organigramma-wrapper position-relative">
        {{-- Header con controlli --}}
        <div class="organigramma-header">
            <div class="organigramma-controls">
                {{-- Toggle Vista --}}
                <div class="view-toggle">
                    <button type="button" class="view-toggle-btn active" data-view="plotoni">
                        <i class="fas fa-users"></i> Plotoni
                    </button>
                    <button type="button" class="view-toggle-btn" data-view="uffici">
                        <i class="fas fa-building"></i> Uffici
                    </button>
                </div>

                {{-- Switch Militari --}}
                <div class="control-switch">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="showMilitari">
                        <label class="form-check-label" for="showMilitari">Mostra Militari</label>
                    </div>
                </div>
            </div>

            {{-- Pulsanti azioni --}}
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnExpandAll">
                    <i class="fas fa-expand-alt me-1"></i> Espandi
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCollapseAll">
                    <i class="fas fa-compress-alt me-1"></i> Comprimi
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRefresh">
                    <i class="fas fa-sync-alt me-1"></i> Aggiorna
                </button>
            </div>
        </div>

        {{-- Area organigramma --}}
        <div class="organigramma-scroll" id="organigrammaScroll">
            <div class="org-tree" id="orgTree">
                {{-- Generato dinamicamente --}}
            </div>
        </div>

        {{-- Loading --}}
        <div class="loading-overlay" id="treeLoading" style="display: none;">
            <div class="spinner-wrapper text-center">
                <div class="spinner-border" role="status"></div>
                <p class="mt-2 mb-0">Caricamento...</p>
            </div>
        </div>
    </div>
</div>

{{-- Modal solo se in modalità edit --}}
@if($canEdit)
{{-- Modal Crea/Modifica Unità --}}
<div class="modal fade" id="unitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unitModalTitle">Nuova Unità</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="unitForm">
                <div class="modal-body">
                    <input type="hidden" id="unitUuid" name="uuid">
                    <input type="hidden" id="parentUuid" name="parent_uuid">
                    <input type="hidden" id="unitType" name="type_id">
                    <input type="hidden" id="unitCode" name="code">
                    <input type="hidden" id="unitOrder" name="sort_order" value="0">
                    <input type="hidden" id="unitDescription" name="description">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="unitName" name="name" required maxlength="150" placeholder="Es: 1a Compagnia, Plotone Alfa, Ufficio S1...">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="unitActive" name="is_active" checked>
                        <label class="form-check-label" for="unitActive">Unità attiva</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveUnit">Salva</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Conferma Eliminazione --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Conferma Eliminazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler eliminare l'unità <strong id="deleteUnitName"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Questa azione non può essere annullata.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">Elimina</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Azioni --}}
<div class="modal fade" id="actionsModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionsModalTitle">Azioni</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="actionsModalBody"></div>
        </div>
    </div>
</div>

{{-- Modal Cambio Unità Militare --}}
<div class="modal fade" id="militareUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Trasferisci Militare</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="militareTransferInfo" class="mb-3"></p>
                <div class="mb-3">
                    <label class="form-label">Nuova Unità</label>
                    <select class="form-select" id="newUnitSelect" required>
                        <option value="">Seleziona unità...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-primary" id="btnConfirmTransfer">Trasferisci</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canEdit = @json($canEdit);
    const baseUrl = '{{ route('gerarchia.index') }}';
    
    // Stato
    let showMilitari = false;
    let currentView = 'plotoni'; // 'plotoni' o 'uffici'
    let treeData = [];
    let allUnits = [];
    let currentMilitareId = null;
    
    // Modal (solo se canEdit è true)
    let unitModal, deleteModal, actionsModal, militareUnitModal;
    
    // Inizializza modal solo se esistono (in modalità edit)
    if (canEdit) {
        const unitModalEl = document.getElementById('unitModal');
        const deleteModalEl = document.getElementById('deleteModal');
        const actionsModalEl = document.getElementById('actionsModal');
        const militareUnitModalEl = document.getElementById('militareUnitModal');
        
        if (unitModalEl) unitModal = new bootstrap.Modal(unitModalEl);
        if (deleteModalEl) deleteModal = new bootstrap.Modal(deleteModalEl);
        if (actionsModalEl) actionsModal = new bootstrap.Modal(actionsModalEl);
        if (militareUnitModalEl) militareUnitModal = new bootstrap.Modal(militareUnitModalEl);
    }

    // Carica l'albero iniziale
    loadTree();
    loadAllUnits();

    // ============================================
    // EVENT LISTENERS
    // ============================================

    // Toggle Vista Plotoni/Uffici
    document.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentView = this.dataset.view;
            renderOrgChart(treeData);
        });
    });

    // Toggle militari
    document.getElementById('showMilitari').addEventListener('change', (e) => {
        showMilitari = e.target.checked;
        loadTree();
    });

    // Pulsanti
    document.getElementById('btnExpandAll').addEventListener('click', expandAll);
    document.getElementById('btnCollapseAll').addEventListener('click', collapseAll);
    document.getElementById('btnRefresh').addEventListener('click', loadTree);
    document.getElementById('btnAddRoot')?.addEventListener('click', () => openUnitModal(null));

    // Form submit (solo se canEdit è true)
    if (canEdit) {
        document.getElementById('unitForm')?.addEventListener('submit', saveUnit);
        document.getElementById('btnConfirmDelete')?.addEventListener('click', confirmDelete);
        document.getElementById('btnConfirmTransfer')?.addEventListener('click', confirmMilitareTransfer);
    }

    // ============================================
    // FUNZIONI PRINCIPALI
    // ============================================

    function loadTree() {
        showLoading(true);

        fetch(`${baseUrl}/api/tree?include_militari=${showMilitari}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                treeData = data.data;
                renderOrgChart(treeData);
            } else {
                window.showToast('Errore nel caricamento', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('Errore di connessione', 'error');
        })
        .finally(() => showLoading(false));
    }

    function loadAllUnits() {
        fetch(`${baseUrl}/api/tree?include_militari=false`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allUnits = flattenUnits(data.data);
            }
        });
    }

    function flattenUnits(nodes, result = [], prefix = '') {
        nodes.forEach(node => {
            if (node.type !== 'militare') {
                result.push({
                    id: node.id,
                    uuid: node.uuid,
                    name: prefix + node.name,
                    depth: node.depth,
                    type: node.type
                });
                if (node.children && node.children.length > 0) {
                    flattenUnits(node.children, result, prefix + '— ');
                }
            }
        });
        return result;
    }

    // ============================================
    // RENDERING ORGANIGRAMMA
    // ============================================

    function renderOrgChart(data) {
        const container = document.getElementById('orgTree');
        const scrollContainer = document.getElementById('organigrammaScroll');
        
        if (!data || data.length === 0) {
            container.innerHTML = '<p class="text-center text-muted py-5">Nessuna unità organizzativa trovata.</p>';
            return;
        }

        // Filtra i dati in base alla vista corrente
        const filteredData = filterDataByView(data);

        // Crea la struttura HTML
        const ul = document.createElement('ul');
        filteredData.forEach(node => {
            ul.appendChild(createNodeElement(node, 0));
        });
        
        container.innerHTML = '';
        container.appendChild(ul);

        // Centra lo scroll orizzontalmente
        setTimeout(() => {
            const scrollWidth = scrollContainer.scrollWidth;
            const clientWidth = scrollContainer.clientWidth;
            if (scrollWidth > clientWidth) {
                scrollContainer.scrollLeft = (scrollWidth - clientWidth) / 2;
            }
        }, 100);
    }

    function filterDataByView(data) {
        // Struttura: Reggimento > Battaglione > Compagnia > (Plotoni | Uffici)
        // Vista Plotoni: mostra plotoni sotto le compagnie, nasconde uffici
        // Vista Uffici: mostra uffici sotto le compagnie, nasconde plotoni
        return data.map(node => filterNodeByView(node, null)).filter(n => n !== null);
    }

    function filterNodeByView(node, parentTypeCode) {
        if (!node) return null;
        
        // Determina il tipo del nodo
        const nodeTypeCode = node.type?.code || '';
        const isMilitare = node.type === 'militare';
        const isUfficio = nodeTypeCode === 'ufficio';
        const isPlotone = nodeTypeCode === 'plotone';
        const isCompagnia = nodeTypeCode === 'compagnia';
        
        // I militari passano sempre (se abilitati)
        if (isMilitare) {
            return node;
        }
        
        // Filtra i figli delle compagnie in base alla vista
        // (uffici e plotoni sono sotto le compagnie)
        if (parentTypeCode === 'compagnia') {
            if (currentView === 'plotoni' && isUfficio) return null;
            if (currentView === 'uffici' && isPlotone) return null;
        }

        // Processa ricorsivamente i figli, passando il tipo corrente come parent
        let filteredChildren = [];
        if (node.children && node.children.length > 0) {
            filteredChildren = node.children
                .map(child => filterNodeByView(child, nodeTypeCode))
                .filter(c => c !== null);
        }

        return {
            ...node,
            children: filteredChildren
        };
    }

    function createNodeElement(node, depth) {
        const isMilitare = node.type === 'militare';
        const hasChildren = node.children && node.children.length > 0;
        const hasMilitariChildren = hasChildren && node.children.some(c => c.type === 'militare');
        const nodeTypeCode = node.type?.code || '';
        const isUfficio = nodeTypeCode === 'ufficio';
        
        const li = document.createElement('li');
        li.className = `depth-${depth}`;
        li.dataset.uuid = node.uuid || '';
        if (hasMilitariChildren) li.classList.add('has-militari');
        
        // Nodo principale
        const nodeDiv = document.createElement('div');
        nodeDiv.className = 'org-node';
        if (isMilitare) nodeDiv.classList.add('militare');
        if (isUfficio && !isMilitare) nodeDiv.classList.add('ufficio');
        if (hasChildren) nodeDiv.classList.add('has-children');
        
        nodeDiv.dataset.uuid = node.uuid || '';
        nodeDiv.dataset.id = isMilitare ? node.militare_id : node.id;
        nodeDiv.dataset.type = isMilitare ? 'militare' : 'unit';
        nodeDiv.dataset.name = node.name;
        
        // Box del nodo
        const boxDiv = document.createElement('div');
        boxDiv.className = 'org-node-box';
        boxDiv.innerHTML = `<div class="org-node-name">${escapeHtml(node.name)}</div>`;
        
        // Click handler sul box
        if (canEdit) {
            boxDiv.addEventListener('click', (e) => {
                e.stopPropagation();
                if (isMilitare) {
                    openMilitareActions(node.militare_id, node);
                } else {
                    openUnitActions(node.uuid, node);
                }
            });
        }
        
        nodeDiv.appendChild(boxDiv);
        
        // Pulsante Toggle per espandere/comprimere - SOLO SE HA FIGLI
        if (hasChildren) {
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'org-toggle-btn';
            toggleBtn.innerHTML = '−';
            toggleBtn.title = 'Comprimi/Espandi';
            
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isCollapsed = li.classList.toggle('collapsed');
                this.innerHTML = isCollapsed ? '+' : '−';
            });
            
            nodeDiv.appendChild(toggleBtn);
        }
        
        li.appendChild(nodeDiv);
        
        // Figli
        if (hasChildren) {
            const childUl = document.createElement('ul');
            node.children.forEach(child => {
                childUl.appendChild(createNodeElement(child, depth + 1));
            });
            li.appendChild(childUl);
        }
        
        return li;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================
    // ESPANDI / COMPRIMI
    // ============================================

    function expandAll() {
        document.querySelectorAll('#orgTree li.collapsed').forEach(li => {
            li.classList.remove('collapsed');
            const btn = li.querySelector('.org-toggle-btn');
            if (btn) btn.innerHTML = '−';
        });
    }

    function collapseAll() {
        document.querySelectorAll('#orgTree li').forEach(li => {
            if (li.querySelector(':scope > ul')) {
                li.classList.add('collapsed');
                const btn = li.querySelector('.org-toggle-btn');
                if (btn) btn.innerHTML = '+';
            }
        });
    }

    // ============================================
    // MODAL AZIONI
    // ============================================

    function openUnitActions(uuid, nodeData) {
        document.getElementById('actionsModalTitle').textContent = nodeData?.name || 'Azioni Unità';
        document.getElementById('actionsModalBody').innerHTML = `
            <div class="list-group actions-list">
                <button class="list-group-item list-group-item-action" onclick="window.orgFunctions.openEditModal('${uuid}')">
                    <i class="fas fa-edit me-2"></i> Rinomina / Modifica
                </button>
                <button class="list-group-item list-group-item-action" onclick="window.orgFunctions.openUnitModal('${uuid}')">
                    <i class="fas fa-plus me-2"></i> Aggiungi Sotto-Unità
                </button>
                <button class="list-group-item list-group-item-action text-danger" onclick="window.orgFunctions.openDeleteModal('${uuid}', '${(nodeData?.name || '').replace(/'/g, "\\'")}')">
                    <i class="fas fa-trash me-2"></i> Elimina Unità
                </button>
            </div>
        `;
        actionsModal.show();
    }

    function openMilitareActions(militareId, nodeData) {
        const nome = nodeData?.name || `Militare #${militareId}`;
        document.getElementById('actionsModalTitle').textContent = nome;
        document.getElementById('actionsModalBody').innerHTML = `
            <div class="list-group actions-list">
                <button class="list-group-item list-group-item-action" onclick="window.orgFunctions.openMilitareTransferModal(${militareId}, '${nome.replace(/'/g, "\\'")}', ${nodeData?.unit_id || 'null'})">
                    <i class="fas fa-exchange-alt me-2"></i> Cambia Unità
                </button>
                <button class="list-group-item list-group-item-action" onclick="window.location.href='{{ url('anagrafica') }}/${militareId}';">
                    <i class="fas fa-user me-2"></i> Vedi Scheda Militare
                </button>
            </div>
        `;
        actionsModal.show();
    }

    function openMilitareTransferModal(militareId, nome, currentUnitId) {
        actionsModal.hide();
        currentMilitareId = militareId;
        document.getElementById('militareTransferInfo').textContent = `Trasferisci: ${nome}`;
        
        const select = document.getElementById('newUnitSelect');
        select.innerHTML = '<option value="">Seleziona unità...</option>';
        
        allUnits.forEach(unit => {
            if (unit.id !== currentUnitId) {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.name;
                select.appendChild(option);
            }
        });
        
        militareUnitModal.show();
    }

    function confirmMilitareTransfer() {
        const newUnitId = document.getElementById('newUnitSelect').value;
        
        if (!newUnitId) {
            window.showToast('Seleziona un\'unità di destinazione', 'warning');
            return;
        }
        
        updateMilitareUnit(currentMilitareId, newUnitId);
        militareUnitModal.hide();
    }

    function updateMilitareUnit(militareId, newUnitId) {
        fetch(`{{ url('militari') }}/${militareId}/update-unit`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ organizational_unit_id: newUnitId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast(`${data.data.militare_nome}: ${data.data.old_unit} → ${data.data.new_unit}`, 'success');
                loadTree();
            } else {
                window.showToast(data.message || 'Errore nel trasferimento', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('Errore di connessione', 'error');
        });
    }

    // ============================================
    // GESTIONE UNITÀ (CRUD)
    // ============================================

    function openUnitModal(parentUuid) {
        actionsModal.hide();
        document.getElementById('unitModalTitle').textContent = 'Nuova Unità';
        document.getElementById('unitForm').reset();
        document.getElementById('unitUuid').value = '';
        document.getElementById('parentUuid').value = parentUuid || '';
        document.getElementById('unitActive').checked = true;
        
        // Imposta valori di default per i campi nascosti
        document.getElementById('unitCode').value = '';
        document.getElementById('unitOrder').value = '0';
        document.getElementById('unitDescription').value = '';

        // Carica e imposta automaticamente il primo tipo disponibile
        loadContainableTypes(parentUuid, true);
        unitModal.show();
    }

    function openEditModal(uuid) {
        actionsModal.hide();
        fetch(`${baseUrl}/api/units/${uuid}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const unit = data.data.unit;
                document.getElementById('unitModalTitle').textContent = 'Modifica Unità';
                document.getElementById('unitUuid').value = unit.uuid;
                document.getElementById('parentUuid').value = unit.parent?.uuid || '';
                document.getElementById('unitName').value = unit.name;
                document.getElementById('unitCode').value = unit.code || '';
                document.getElementById('unitType').value = unit.type?.id || '';
                document.getElementById('unitOrder').value = unit.sort_order || 0;
                document.getElementById('unitDescription').value = unit.description || '';
                document.getElementById('unitActive').checked = unit.is_active;

                // Carica tipi disponibili ma non sovrascrivere il tipo corrente
                loadContainableTypes(unit.parent?.uuid, false);
                unitModal.show();
            }
        });
    }

    function loadContainableTypes(parentUuid, autoSelectFirst = false) {
        const url = parentUuid 
            ? `${baseUrl}/api/types/containable/${parentUuid}`
            : `${baseUrl}/api/types`;

        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                const typeInput = document.getElementById('unitType');
                const currentValue = typeInput.value;
                
                // Se non c'è già un valore o dobbiamo auto-selezionare, usa il primo tipo disponibile
                if (autoSelectFirst || !currentValue) {
                    typeInput.value = data.data[0].id;
                }
            }
        })
        .catch(error => {
            console.error('Errore caricamento tipi:', error);
        });
    }

    function saveUnit(e) {
        e.preventDefault();
        
        const uuid = document.getElementById('unitUuid').value;
        const isEdit = !!uuid;
        const url = isEdit 
            ? `${baseUrl}/api/units/${uuid}`
            : `${baseUrl}/api/units`;

        const typeValue = document.getElementById('unitType').value;
        if (!typeValue) {
            window.showToast('Tipo unità mancante. Riprova.', 'error');
            return;
        }

        const formData = {
            name: document.getElementById('unitName').value,
            type_id: parseInt(typeValue),
            parent_uuid: document.getElementById('parentUuid').value || null,
            code: document.getElementById('unitCode').value || null,
            description: document.getElementById('unitDescription').value || null,
            sort_order: parseInt(document.getElementById('unitOrder').value) || 0,
            is_active: document.getElementById('unitActive').checked
        };

        fetch(url, {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message || 'Operazione completata', 'success');
                unitModal.hide();
                loadTree();
                loadAllUnits();
            } else {
                window.showToast(data.message || 'Errore', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('Errore di connessione', 'error');
        });
    }

    function openDeleteModal(uuid, name) {
        actionsModal.hide();
        document.getElementById('deleteUnitName').textContent = name;
        document.getElementById('btnConfirmDelete').dataset.uuid = uuid;
        deleteModal.show();
    }

    function confirmDelete() {
        const uuid = document.getElementById('btnConfirmDelete').dataset.uuid;

        fetch(`${baseUrl}/api/units/${uuid}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.showToast(data.message || 'Unità eliminata', 'success');
                deleteModal.hide();
                loadTree();
                loadAllUnits();
            } else {
                window.showToast(data.message || 'Errore', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.showToast('Errore di connessione', 'error');
        });
    }

    // ============================================
    // UTILITÀ
    // ============================================

    function showLoading(show) {
        document.getElementById('treeLoading').style.display = show ? 'flex' : 'none';
    }

    // Esponi funzioni globalmente per i click handler negli innerHTML
    window.orgFunctions = {
        openUnitModal,
        openEditModal,
        openDeleteModal,
        openMilitareTransferModal
    };
});
</script>
@endpush
