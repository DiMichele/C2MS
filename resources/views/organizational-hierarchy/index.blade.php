@extends('layouts.app')
@section('title', 'Gerarchia Organizzativa - SUGECO')

@section('styles')
{{-- jsTree CSS (locale) --}}
<link rel="stylesheet" href="{{ asset('css/jstree-default.min.css') }}" />

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
        --danger: #D32F2F;
        --warning: #FF8F00;
        --transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1);
    }

    /* Layout principale */
    .hierarchy-container {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 1.5rem;
        min-height: calc(100vh - 200px);
    }

    @media (max-width: 992px) {
        .hierarchy-container {
            grid-template-columns: 1fr;
        }
    }

    /* Pannello albero */
    .tree-panel {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.08);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .tree-panel-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: white;
        padding: 1.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .tree-panel-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .tree-toolbar {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .tree-toolbar .btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
    }

    .tree-search {
        padding: 1rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .tree-search input {
        border-radius: 20px;
        padding-left: 2.5rem;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512' fill='%236c757d'%3E%3Cpath d='M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 12px center;
        background-size: 16px;
    }

    .tree-content {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    /* Customizzazione jsTree */
    .jstree-default .jstree-node {
        margin-left: 1rem;
    }

    .jstree-default .jstree-anchor {
        padding: 6px 10px;
        border-radius: 6px;
        transition: var(--transition);
    }

    .jstree-default .jstree-anchor:hover {
        background-color: var(--gray-100);
    }

    .jstree-default .jstree-clicked {
        background-color: rgba(191, 157, 94, 0.15) !important;
        border: 1px solid var(--gold);
        color: var(--navy) !important;
    }

    .jstree-default .jstree-icon.jstree-themeicon {
        margin-right: 6px;
    }

    .node-type-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 6px;
        text-transform: uppercase;
    }

    /* Pannello dettagli */
    .details-panel {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.08);
        overflow: hidden;
    }

    .details-panel-header {
        background: var(--gray-100);
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid var(--gray-200);
    }

    .details-panel-header h5 {
        margin: 0;
        font-weight: 600;
        color: var(--navy);
    }

    .details-content {
        padding: 1.5rem;
    }

    .details-placeholder {
        text-align: center;
        padding: 4rem 2rem;
        color: var(--gray-500);
    }

    .details-placeholder i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Card unità */
    .unit-card {
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .unit-card-header {
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .unit-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .unit-info h4 {
        margin: 0 0 0.25rem;
        font-weight: 700;
        color: var(--navy);
    }

    .unit-info .unit-type {
        font-size: 0.85rem;
        color: var(--gray-700);
    }

    .unit-card-body {
        padding: 1.25rem;
        border-top: 1px solid var(--gray-200);
    }

    /* Breadcrumb */
    .unit-breadcrumb {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.85rem;
        color: var(--gray-700);
        margin-bottom: 1rem;
        padding: 0.75rem 1rem;
        background: var(--gray-100);
        border-radius: 8px;
    }

    .unit-breadcrumb-item {
        display: flex;
        align-items: center;
    }

    .unit-breadcrumb-item:not(:last-child)::after {
        content: '\f105';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        margin: 0 0.5rem;
        opacity: 0.5;
    }

    .unit-breadcrumb-link {
        color: var(--navy-light);
        text-decoration: none;
        cursor: pointer;
    }

    .unit-breadcrumb-link:hover {
        color: var(--gold);
    }

    /* Stats grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .stat-card {
        background: var(--gray-100);
        border-radius: 10px;
        padding: 1rem;
        text-align: center;
    }

    .stat-card .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--navy);
        line-height: 1;
    }

    .stat-card .stat-label {
        font-size: 0.75rem;
        color: var(--gray-700);
        margin-top: 0.25rem;
        text-transform: uppercase;
    }

    /* Azioni */
    .unit-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--gray-200);
    }

    /* Modal */
    .modal-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        color: white;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    /* Form */
    .form-label {
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 2px solid var(--gray-200);
        padding: 0.6rem 1rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--gold);
        box-shadow: 0 0 0 0.2rem rgba(191, 157, 94, 0.25);
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

    .spinner-wrapper {
        text-align: center;
    }

    .spinner-wrapper .spinner-border {
        width: 3rem;
        height: 3rem;
        color: var(--navy);
    }

    /* Context menu */
    .jstree-contextmenu {
        border-radius: 8px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border: none;
        overflow: hidden;
    }

    .vakata-context li > a {
        padding: 8px 16px;
        font-size: 0.9rem;
    }

    .vakata-context li > a:hover {
        background: var(--gray-100);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-1">Gerarchia Organizzativa</h1>
            <p class="text-muted mb-0">Struttura organizzativa ad albero del Reggimento</p>
        </div>
        @if($canEdit)
        <button type="button" class="btn btn-primary" id="btnAddRoot">
            <i class="fas fa-plus me-2"></i>Aggiungi Unità Root
        </button>
        @endif
    </div>

    {{-- Container principale --}}
    <div class="hierarchy-container">
        {{-- Pannello albero --}}
        <div class="tree-panel">
            <div class="tree-panel-header">
                <h5><i class="fas fa-sitemap me-2"></i>Struttura</h5>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnExpandAll" title="Espandi tutto">
                        <i class="fas fa-expand-alt"></i>
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnCollapseAll" title="Comprimi tutto">
                        <i class="fas fa-compress-alt"></i>
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" id="btnRefresh" title="Aggiorna">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>

            <div class="tree-search">
                <input type="text" class="form-control" id="treeSearch" placeholder="Cerca unità...">
            </div>

            <div class="tree-content position-relative">
                <div id="organizationTree"></div>
                <div class="loading-overlay" id="treeLoading">
                    <div class="spinner-wrapper">
                        <div class="spinner-border" role="status"></div>
                        <p class="mt-2 mb-0">Caricamento...</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pannello dettagli --}}
        <div class="details-panel">
            <div class="details-panel-header">
                <h5><i class="fas fa-info-circle me-2"></i>Dettagli Unità</h5>
            </div>
            <div class="details-content" id="detailsContent">
                <div class="details-placeholder">
                    <i class="fas fa-hand-pointer"></i>
                    <h5>Seleziona un'unità</h5>
                    <p class="mb-0">Clicca su un nodo dell'albero per visualizzarne i dettagli.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Crea/Modifica Unità --}}
<div class="modal fade" id="unitModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="unitModalTitle">Nuova Unità</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="unitForm">
                <div class="modal-body">
                    <input type="hidden" id="unitUuid" name="uuid">
                    <input type="hidden" id="parentUuid" name="parent_uuid">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="unitName" name="name" required maxlength="150">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Codice</label>
                            <input type="text" class="form-control" id="unitCode" name="code" maxlength="50">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo <span class="text-danger">*</span></label>
                            <select class="form-select" id="unitType" name="type_id" required>
                                <option value="">Seleziona tipo...</option>
                                @foreach($unitTypes as $type)
                                <option value="{{ $type->id }}" 
                                        data-icon="{{ $type->icon }}"
                                        data-color="{{ $type->color }}">
                                    {{ $type->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ordine</label>
                            <input type="number" class="form-control" id="unitOrder" name="sort_order" min="0" value="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrizione</label>
                        <textarea class="form-control" id="unitDescription" name="description" rows="3" maxlength="1000"></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="unitActive" name="is_active" checked>
                        <label class="form-check-label" for="unitActive">Unità attiva</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary" id="btnSaveUnit">
                        <i class="fas fa-save me-2"></i>Salva
                    </button>
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
                <div class="mb-3">
                    <label class="form-label">Cosa fare con le unità figlie?</label>
                    <select class="form-select" id="childStrategy">
                        <option value="orphan">Sposta a livello root</option>
                        <option value="promote">Sposta al parent di questa unità</option>
                        <option value="cascade">Elimina anche le unità figlie</option>
                    </select>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Questa azione non può essere annullata.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="fas fa-trash me-2"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- jsTree (locale) --}}
<script src="{{ asset('js/jstree.min.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const canEdit = @json($canEdit);
    const baseUrl = '{{ route('gerarchia.index') }}';
    let currentUnit = null;
    let unitModal, deleteModal;

    // Inizializza modal
    unitModal = new bootstrap.Modal(document.getElementById('unitModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    // Carica l'albero
    loadTree();

    // Event listeners
    document.getElementById('btnExpandAll').addEventListener('click', () => $('#organizationTree').jstree('open_all'));
    document.getElementById('btnCollapseAll').addEventListener('click', () => $('#organizationTree').jstree('close_all'));
    document.getElementById('btnRefresh').addEventListener('click', loadTree);

    // Ricerca
    let searchTimeout;
    document.getElementById('treeSearch').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            $('#organizationTree').jstree('search', e.target.value);
        }, 300);
    });

    // Aggiungi root
    document.getElementById('btnAddRoot')?.addEventListener('click', () => openUnitModal(null));

    // Form submit
    document.getElementById('unitForm').addEventListener('submit', saveUnit);

    // Conferma eliminazione
    document.getElementById('btnConfirmDelete').addEventListener('click', confirmDelete);

    /**
     * Carica l'albero dalla API
     */
    function loadTree() {
        showLoading(true);

        fetch(`${baseUrl}/api/tree`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                initTree(transformTreeData(data.data));
            } else {
                showToast('Errore nel caricamento dell\'albero', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'error');
        })
        .finally(() => showLoading(false));
    }

    /**
     * Trasforma i dati per jsTree
     */
    function transformTreeData(nodes) {
        return nodes.map(node => ({
            id: node.uuid,
            text: node.name + (node.code ? ` <small class="text-muted">(${node.code})</small>` : ''),
            icon: node.type?.icon || 'fa fa-building',
            state: { opened: node.depth < 2 },
            data: node,
            children: node.children ? transformTreeData(node.children) : node.has_children,
            li_attr: {
                'data-type': node.type?.code,
                style: `--node-color: ${node.type?.color || '#0A2342'}`
            }
        }));
    }

    /**
     * Inizializza jsTree
     */
    function initTree(data) {
        $('#organizationTree').jstree('destroy');

        $('#organizationTree').jstree({
            core: {
                data: data,
                themes: {
                    name: 'default',
                    responsive: true
                },
                check_callback: canEdit,
                multiple: false
            },
            plugins: ['search', 'wholerow', canEdit ? 'contextmenu' : '', canEdit ? 'dnd' : ''].filter(Boolean),
            search: {
                show_only_matches: true,
                show_only_matches_children: true
            },
            contextmenu: canEdit ? {
                items: contextMenuItems
            } : false,
            dnd: canEdit ? {
                copy: false,
                inside_pos: 'last'
            } : false
        })
        .on('select_node.jstree', function(e, data) {
            loadUnitDetails(data.node.id);
        })
        .on('move_node.jstree', function(e, data) {
            moveNode(data.node.id, data.parent === '#' ? null : data.parent);
        });
    }

    /**
     * Menu contestuale
     */
    function contextMenuItems(node) {
        const items = {
            view: {
                label: 'Visualizza dettagli',
                icon: 'fa fa-eye',
                action: () => loadUnitDetails(node.id)
            }
        };

        if (canEdit) {
            items.create = {
                label: 'Aggiungi sotto-unità',
                icon: 'fa fa-plus',
                action: () => openUnitModal(node.id)
            };
            items.edit = {
                label: 'Modifica',
                icon: 'fa fa-edit',
                action: () => openEditModal(node.id)
            };
            items.delete = {
                label: 'Elimina',
                icon: 'fa fa-trash',
                action: () => openDeleteModal(node)
            };
        }

        return items;
    }

    /**
     * Carica i dettagli di un'unità
     */
    function loadUnitDetails(uuid) {
        const detailsContent = document.getElementById('detailsContent');
        detailsContent.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';

        fetch(`${baseUrl}/api/units/${uuid}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentUnit = data.data;
                renderUnitDetails(data.data);
            } else {
                detailsContent.innerHTML = '<div class="alert alert-danger">Errore nel caricamento</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            detailsContent.innerHTML = '<div class="alert alert-danger">Errore di connessione</div>';
        });
    }

    /**
     * Renderizza i dettagli dell'unità
     */
    function renderUnitDetails(data) {
        const unit = data.unit;
        const stats = data.stats;
        const breadcrumb = data.breadcrumb;

        const html = `
            <div class="unit-breadcrumb">
                ${breadcrumb.map((b, i) => `
                    <span class="unit-breadcrumb-item">
                        ${i < breadcrumb.length - 1 
                            ? `<a class="unit-breadcrumb-link" onclick="selectNode('${b.uuid}')">${b.name}</a>` 
                            : `<strong>${b.name}</strong>`}
                    </span>
                `).join('')}
            </div>

            <div class="unit-card">
                <div class="unit-card-header" style="border-left: 4px solid ${unit.type?.color || '#0A2342'}">
                    <div class="unit-icon" style="background-color: ${unit.type?.color || '#0A2342'}">
                        <i class="${unit.type?.icon || 'fa fa-building'}"></i>
                    </div>
                    <div class="unit-info">
                        <h4>${unit.name}${unit.code ? ` <small class="text-muted">(${unit.code})</small>` : ''}</h4>
                        <span class="unit-type">
                            ${unit.type?.name || 'Tipo sconosciuto'}
                            ${!unit.is_active ? '<span class="badge bg-secondary ms-2">Inattiva</span>' : ''}
                        </span>
                    </div>
                </div>

                ${unit.description ? `
                    <div class="unit-card-body">
                        <p class="mb-0">${unit.description}</p>
                    </div>
                ` : ''}
            </div>

            <h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Statistiche</h6>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">${stats.direct_children}</div>
                    <div class="stat-label">Sotto-unità dirette</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total_descendants}</div>
                    <div class="stat-label">Discendenti totali</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.total_militari}</div>
                    <div class="stat-label">Militari</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">${stats.depth}</div>
                    <div class="stat-label">Profondità</div>
                </div>
            </div>

            ${canEdit ? `
                <div class="unit-actions">
                    <button class="btn btn-primary btn-sm" onclick="openUnitModal('${unit.uuid}')">
                        <i class="fas fa-plus me-1"></i>Aggiungi sotto-unità
                    </button>
                    <button class="btn btn-outline-primary btn-sm" onclick="openEditModal('${unit.uuid}')">
                        <i class="fas fa-edit me-1"></i>Modifica
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="openDeleteModal({id: '${unit.uuid}', text: '${unit.name}'})">
                        <i class="fas fa-trash me-1"></i>Elimina
                    </button>
                </div>
            ` : ''}
        `;

        document.getElementById('detailsContent').innerHTML = html;
    }

    /**
     * Apre il modal per creare un'unità
     */
    window.openUnitModal = function(parentUuid) {
        document.getElementById('unitModalTitle').textContent = 'Nuova Unità';
        document.getElementById('unitForm').reset();
        document.getElementById('unitUuid').value = '';
        document.getElementById('parentUuid').value = parentUuid || '';
        document.getElementById('unitActive').checked = true;

        // Carica i tipi contenibili
        loadContainableTypes(parentUuid);

        unitModal.show();
    };

    /**
     * Apre il modal per modificare un'unità
     */
    window.openEditModal = function(uuid) {
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

                unitModal.show();
            }
        });
    };

    /**
     * Carica i tipi contenibili per un parent
     */
    function loadContainableTypes(parentUuid) {
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
            if (data.success) {
                const select = document.getElementById('unitType');
                const currentValue = select.value;
                select.innerHTML = '<option value="">Seleziona tipo...</option>';
                data.data.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    option.dataset.icon = type.icon;
                    option.dataset.color = type.color;
                    select.appendChild(option);
                });
                if (currentValue) select.value = currentValue;
            }
        });
    }

    /**
     * Salva un'unità
     */
    function saveUnit(e) {
        e.preventDefault();
        
        const uuid = document.getElementById('unitUuid').value;
        const isEdit = !!uuid;
        const url = isEdit 
            ? `${baseUrl}/api/units/${uuid}`
            : `${baseUrl}/api/units`;

        const formData = {
            name: document.getElementById('unitName').value,
            type_id: parseInt(document.getElementById('unitType').value),
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
                showToast(data.message || 'Operazione completata', 'success');
                unitModal.hide();
                loadTree();
                if (isEdit) loadUnitDetails(uuid);
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'error');
        });
    }

    /**
     * Apre il modal di conferma eliminazione
     */
    window.openDeleteModal = function(node) {
        document.getElementById('deleteUnitName').textContent = node.text || node.name;
        document.getElementById('btnConfirmDelete').dataset.uuid = node.id;
        deleteModal.show();
    };

    /**
     * Conferma eliminazione
     */
    function confirmDelete() {
        const uuid = document.getElementById('btnConfirmDelete').dataset.uuid;
        const strategy = document.getElementById('childStrategy').value;

        fetch(`${baseUrl}/api/units/${uuid}?child_strategy=${strategy}`, {
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
                showToast(data.message || 'Unità eliminata', 'success');
                deleteModal.hide();
                loadTree();
                document.getElementById('detailsContent').innerHTML = `
                    <div class="details-placeholder">
                        <i class="fas fa-hand-pointer"></i>
                        <h5>Seleziona un'unità</h5>
                        <p class="mb-0">Clicca su un nodo dell'albero per visualizzarne i dettagli.</p>
                    </div>
                `;
            } else {
                showToast(data.message || 'Errore', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'error');
        });
    }

    /**
     * Sposta un nodo
     */
    function moveNode(nodeUuid, newParentUuid) {
        fetch(`${baseUrl}/api/units/${nodeUuid}/move`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ parent_uuid: newParentUuid })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Unità spostata', 'success');
            } else {
                showToast(data.message || 'Errore nello spostamento', 'error');
                loadTree(); // Ricarica per annullare lo spostamento visuale
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Errore di connessione', 'error');
            loadTree();
        });
    }

    /**
     * Seleziona un nodo
     */
    window.selectNode = function(uuid) {
        $('#organizationTree').jstree('select_node', uuid);
    };

    /**
     * Mostra/nascondi loading
     */
    function showLoading(show) {
        document.getElementById('treeLoading').style.display = show ? 'flex' : 'none';
    }

    /**
     * Mostra toast
     */
    function showToast(message, type = 'info') {
        // Usa il sistema di toast esistente se disponibile
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            alert(message);
        }
    }
});
</script>
@endpush
