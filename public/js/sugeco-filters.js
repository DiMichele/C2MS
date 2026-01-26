/**
 * SUGECO Filters - Sistema unificato di filtraggio
 * 
 * Gestisce sia filtri server-side (con submit) che client-side (istantanei).
 * Include gestione dipendenze tra filtri (es. Compagnia -> Plotone).
 * 
 * @version 3.0
 * @author SUGECO Team
 * 
 * ============================================================================
 * UTILIZZO
 * ============================================================================
 * 
 * 1. FILTRI SERVER-SIDE (default - ricarica pagina):
 *    Basta aggiungere class="filter-select" ai select.
 *    Il form verrà inviato automaticamente al cambio filtro.
 * 
 * 2. FILTRI CLIENT-SIDE (istantanei - no ricarica):
 *    a) Aggiungi class="filter-local" al form
 *    b) Aggiungi data-nosubmit="true" ai select
 *    c) Aggiungi data-attributes alle righe tabella:
 *       <tr data-compagnia-id="1" data-grado-id="5" data-patenti="2,3,4">
 * 
 * 3. FILTRI SPECIALI (logica custom):
 *    SUGECO.Filters.registerSpecialFilter('nomeFilter', function(row, value) {
 *        return true/false;
 *    });
 * 
 * 4. DIPENDENZA COMPAGNIA -> PLOTONE:
 *    Gestita automaticamente se esistono select #compagnia e #plotone_id
 *    con options che hanno data-compagnia-id
 */

window.SUGECO = window.SUGECO || {};

window.SUGECO.Filters = (function() {
    'use strict';
    
    // ========================================================================
    // CONFIGURAZIONE
    // ========================================================================
    
    const config = {
        // Selettori
        formSelector: 'form.filter-local',
        tableSelector: null,
        rowSelector: 'tr.filterable-row, tr.militare-row, tbody tr[data-militare-id], tbody tr[data-id]',
        
        // UI - Messaggio standard "Nessun militare trovato"
        noResultsMessage: `
            <td colspan="100" class="text-center py-5">
                <div class="d-flex flex-column align-items-center empty-state">
                    <i class="fas fa-users-slash fa-3x mb-3 text-muted"></i>
                    <p class="lead mb-3">Nessun militare trovato</p>
                    <p class="text-muted mb-3">Prova a modificare i criteri di ricerca o i filtri applicati.</p>
                </div>
            </td>
        `,
        
        // Mapping filtro -> data-attribute (se diverso dalla convenzione)
        filterMapping: {},
        
        // Filtri speciali con logica custom
        specialFilters: {},
        
        // Debug
        debug: false
    };
    
    // State per client-side filtering
    let clientSideForm = null;
    let clientSideSelects = [];
    let clientSideRows = [];
    let clientSideInitialized = false;
    
    // ========================================================================
    // UTILITY
    // ========================================================================
    
    function log(...args) {
        if (config.debug || window.SUGECO?.Core?.debug) {
            console.log('[SUGECO.Filters]', ...args);
        }
    }
    
    /**
     * Converte nome filtro in nome data-attribute
     * Es: "grado_id" -> "gradoId", "compagnia" -> "compagniaId"
     */
    function filterNameToDataAttr(filterName) {
        let baseName = filterName.replace(/_id$/, '');
        baseName = baseName.replace(/_([a-z])/g, (m, l) => l.toUpperCase());
        return baseName + 'Id';
    }
    
    /**
     * Ottiene valore data-attribute dalla riga
     */
    function getRowDataValue(row, filterName) {
        // Mapping personalizzato
        if (config.filterMapping[filterName]) {
            return row.dataset[config.filterMapping[filterName]] || '';
        }
        
        const dataAttrName = filterNameToDataAttr(filterName);
        
        // Prova con Id
        if (row.dataset[dataAttrName] !== undefined) {
            return row.dataset[dataAttrName];
        }
        
        // Prova senza Id
        const baseAttrName = dataAttrName.replace(/Id$/, '');
        if (row.dataset[baseAttrName] !== undefined) {
            return row.dataset[baseAttrName];
        }
        
        // Prova nome diretto
        if (row.dataset[filterName] !== undefined) {
            return row.dataset[filterName];
        }
        
        return '';
    }
    
    /**
     * Verifica se riga soddisfa un filtro
     */
    function rowMatchesFilter(row, filterName, filterValue) {
        if (!filterValue) return true;
        
        // Filtro speciale
        if (config.specialFilters[filterName]) {
            return config.specialFilters[filterName](row, filterValue);
        }
        
        const rowValue = getRowDataValue(row, filterName);
        
        // Valori multipli (virgola)
        if (rowValue.includes(',')) {
            return rowValue.split(',').map(v => v.trim()).includes(filterValue);
        }
        
        // Valore "libero"
        if (filterValue === 'libero') {
            return !rowValue || rowValue === '';
        }
        
        return rowValue === filterValue;
    }
    
    // ========================================================================
    // DIPENDENZA COMPAGNIA -> PLOTONE
    // ========================================================================
    
    let allPlotoniOptions = [];
    
    function initCompagniaPlotoneDependency() {
        const compagniaSelect = document.getElementById('compagnia');
        const plotoneSelect = document.getElementById('plotone_id');
        
        if (!compagniaSelect || !plotoneSelect) return;
        
        // Salva tutte le opzioni plotone (esclusa la prima)
        allPlotoniOptions = Array.from(plotoneSelect.options).slice(1).map(opt => ({
            value: opt.value,
            text: opt.text,
            compagniaId: opt.dataset.compagniaId,
            selected: opt.selected
        }));
        
        log('Compagnia-Plotone dependency initialized with', allPlotoniOptions.length, 'plotoni');
    }
    
    function updatePlotoneOptions(compagniaValue, isClientSide = false) {
        const plotoneSelect = document.getElementById('plotone_id');
        if (!plotoneSelect) return;
        
        const plotoneAttuale = plotoneSelect.value;
        
        if (!compagniaValue) {
            // Nessuna compagnia: disabilita plotone
            plotoneSelect.disabled = true;
            plotoneSelect.title = 'Seleziona prima una compagnia';
            
            if (isClientSide) {
                // Client-side: nascondi opzioni con display
                const options = plotoneSelect.querySelectorAll('option');
                options.forEach(opt => {
                    if (!opt.value) {
                        opt.textContent = 'Seleziona compagnia';
                    }
                    opt.style.display = '';
                });
            } else {
                // Server-side: rimuovi opzioni
                while (plotoneSelect.options.length > 1) {
                    plotoneSelect.remove(1);
                }
                plotoneSelect.options[0].text = 'Seleziona prima compagnia';
            }
            plotoneSelect.value = '';
            return;
        }
        
        // Compagnia selezionata: abilita plotone
        plotoneSelect.disabled = false;
        plotoneSelect.title = '';
        
        if (isClientSide) {
            // Client-side: usa display per nascondere/mostrare
            const options = plotoneSelect.querySelectorAll('option');
            options.forEach(opt => {
                if (!opt.value) {
                    opt.textContent = 'Tutti';
                    return;
                }
                opt.style.display = (opt.dataset.compagniaId === compagniaValue) ? '' : 'none';
            });
            
            // Reset se plotone attuale non visibile
            const currentOpt = plotoneSelect.querySelector(`option[value="${plotoneSelect.value}"]`);
            if (currentOpt && currentOpt.style.display === 'none') {
                plotoneSelect.value = '';
            }
        } else {
            // Server-side: ricostruisci opzioni
            plotoneSelect.options[0].text = 'Tutti';
            
            while (plotoneSelect.options.length > 1) {
                plotoneSelect.remove(1);
            }
            
            allPlotoniOptions.forEach(plotone => {
                if (plotone.compagniaId === compagniaValue) {
                    const option = document.createElement('option');
                    option.value = plotone.value;
                    option.text = plotone.text;
                    option.dataset.compagniaId = plotone.compagniaId;
                    if (plotone.value === plotoneAttuale) {
                        option.selected = true;
                    }
                    plotoneSelect.add(option);
                }
            });
            
            // Reset se plotone attuale non più visibile
            const plotoneVisibile = Array.from(plotoneSelect.options).some(
                opt => opt.value === plotoneAttuale && opt.value !== ''
            );
            if (!plotoneVisibile && plotoneAttuale) {
                plotoneSelect.value = '';
            }
        }
    }
    
    // ========================================================================
    // CLIENT-SIDE FILTERING
    // ========================================================================
    
    function applyClientSideFilters() {
        if (!clientSideForm || clientSideRows.length === 0) return;
        
        // Raccogli valori filtri
        const filters = {};
        clientSideSelects.forEach(select => {
            filters[select.name || select.id] = select.value || '';
        });
        
        log('Applicando filtri client-side:', filters);
        
        let visibleCount = 0;
        
        clientSideRows.forEach(row => {
            let visible = true;
            
            for (const [name, value] of Object.entries(filters)) {
                if (value && !rowMatchesFilter(row, name, value)) {
                    visible = false;
                    break;
                }
            }
            
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });
        
        log('Righe visibili:', visibleCount, '/', clientSideRows.length);
        
        updateNoResultsMessage(visibleCount);
        updateActiveFiltersUI();
    }
    
    function updateNoResultsMessage(count) {
        const table = document.querySelector(config.tableSelector || 'table');
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        
        let noResultsRow = document.getElementById('noResultsRow');
        
        if (count === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.id = 'noResultsRow';
                noResultsRow.innerHTML = config.noResultsMessage;
                tbody.appendChild(noResultsRow);
            }
            noResultsRow.style.display = '';
        } else if (noResultsRow) {
            noResultsRow.style.display = 'none';
        }
    }
    
    function updateActiveFiltersUI() {
        let activeCount = 0;
        
        clientSideSelects.forEach(select => {
            const wrapper = select.closest('.select-wrapper');
            const clearBtn = wrapper?.querySelector('.clear-filter');
            
            if (select.value) {
                activeCount++;
                select.classList.add('applied');
                if (clearBtn) clearBtn.style.display = '';
            } else {
                select.classList.remove('applied');
                if (clearBtn) clearBtn.style.display = 'none';
            }
        });
        
        // Pulsante "Rimuovi tutti"
        const removeAllBtn = clientSideForm?.querySelector('.btn-danger, .reset-all-filters');
        if (removeAllBtn) {
            if (activeCount > 0) {
                removeAllBtn.style.display = '';
                removeAllBtn.textContent = `Rimuovi tutti i filtri (${activeCount})`;
            } else {
                removeAllBtn.style.display = 'none';
            }
        }
    }
    
    function initClientSideListeners() {
        // Event listener per i select
        clientSideSelects.forEach(select => {
            select.addEventListener('change', function() {
                if (this.id === 'compagnia') {
                    updatePlotoneOptions(this.value, true);
                }
                applyClientSideFilters();
            });
        });
        
        // Pulsante "Rimuovi tutti"
        const removeAllBtn = clientSideForm.querySelector('.btn-danger, .reset-all-filters');
        if (removeAllBtn) {
            removeAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                clientSideSelects.forEach(select => {
                    select.value = '';
                    select.classList.remove('applied');
                });
                
                updatePlotoneOptions('', true);
                applyClientSideFilters();
            });
        }
        
        // Clear buttons singoli
        clientSideForm.querySelectorAll('.clear-filter').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const filterName = this.dataset.filter;
                const select = document.getElementById(filterName) || 
                              document.querySelector(`[name="${filterName}"]`);
                
                if (select) {
                    select.value = '';
                    select.classList.remove('applied');
                    
                    if (filterName === 'compagnia') {
                        updatePlotoneOptions('', true);
                    }
                    
                    applyClientSideFilters();
                }
                
                this.style.display = 'none';
            });
        });
        
        // Previeni submit
        clientSideForm.addEventListener('submit', e => e.preventDefault());
    }
    
    function detectTable() {
        const selectors = [
            '#pianificazioneTable', '#militariTable', '#scadenzeTable',
            '#dataTable', 'table.sugeco-table', 'table.table', 'table'
        ];
        
        for (const sel of selectors) {
            const table = document.querySelector(sel);
            if (table?.querySelector('tbody tr')) return sel;
        }
        return 'table';
    }
    
    function initClientSide(options = {}) {
        Object.assign(config, options);
        
        clientSideForm = document.querySelector(config.formSelector);
        if (!clientSideForm) {
            log('Form client-side non trovato');
            return false;
        }
        
        clientSideSelects = Array.from(clientSideForm.querySelectorAll('select'));
        if (clientSideSelects.length === 0) {
            log('Nessun select nel form client-side');
            return false;
        }
        
        if (!config.tableSelector) {
            config.tableSelector = detectTable();
        }
        
        const table = document.querySelector(config.tableSelector);
        if (table) {
            clientSideRows = Array.from(table.querySelectorAll(config.rowSelector));
        }
        
        if (clientSideRows.length === 0) {
            log('Nessuna riga trovata');
            return false;
        }
        
        log('Client-side init:', {
            selects: clientSideSelects.length,
            rows: clientSideRows.length
        });
        
        initClientSideListeners();
        updateActiveFiltersUI();
        
        clientSideInitialized = true;
        return true;
    }
    
    // ========================================================================
    // SERVER-SIDE FILTERING
    // ========================================================================
    
    function initServerSide() {
        const filterSelects = document.querySelectorAll('.filter-select');
        
        log('Server-side init:', filterSelects.length, 'selects');
        
        filterSelects.forEach(select => {
            // Skip se client-side
            if (select.classList.contains('filter-local') || 
                select.classList.contains('filter-nosubmit') ||
                select.dataset.nosubmit) {
                return;
            }
            
            const form = select.closest('form');
            if (form?.classList.contains('filter-local') ||
                form?.classList.contains('filter-nosubmit') ||
                form?.getAttribute('onsubmit') === 'return false;') {
                return;
            }
            
            select.addEventListener('change', function() {
                log('Filter changed:', this.name, '=', this.value);
                
                // Gestisci compagnia-plotone
                if (this.id === 'compagnia') {
                    updatePlotoneOptions(this.value, false);
                }
                
                const parentForm = this.closest('form');
                if (parentForm) {
                    const pageInput = parentForm.querySelector('input[name="page"]');
                    if (pageInput) pageInput.value = '1';
                    parentForm.submit();
                }
            });
        });
        
        // Clear buttons (solo per form server-side)
        document.querySelectorAll('.clear-filter').forEach(btn => {
            // Skip se il pulsante è dentro un form filter-local (gestito dal JS della pagina)
            const parentForm = btn.closest('form');
            if (parentForm?.classList.contains('filter-local')) {
                return; // Non aggiungere listener - gestito dalla pagina
            }
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const filterName = this.dataset.filter;
                const select = document.querySelector(`select[name="${filterName}"]`) || 
                              document.getElementById(filterName);
                
                if (select) {
                    select.value = '';
                    
                    const form = select.closest('form');
                    
                    if (form) {
                        form.submit();
                    } else {
                        const url = new URL(window.location);
                        url.searchParams.delete(filterName);
                        url.searchParams.delete('page');
                        window.location.href = url.toString();
                    }
                }
            });
        });
        
        // Reset all buttons (solo per form server-side)
        document.querySelectorAll('.reset-all-filters').forEach(btn => {
            // Skip se il pulsante è dentro un form filter-local (gestito dal JS della pagina)
            const parentForm = btn.closest('form');
            if (parentForm?.classList.contains('filter-local')) {
                return; // Non aggiungere listener - gestito dalla pagina
            }
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                
                document.querySelectorAll('.filter-select').forEach(sel => {
                    // Skip select in form filter-local
                    if (!sel.closest('form')?.classList.contains('filter-local')) {
                        sel.value = '';
                    }
                });
                
                const form = document.querySelector('.filters-form');
                if (form && !form.classList.contains('filter-local')) {
                    form.submit();
                } else {
                    // Cerca un form non-local da inviare
                    const anyForm = document.querySelector('form:not(.filter-local)');
                    if (anyForm && anyForm.querySelector('.filter-select')) {
                        anyForm.submit();
                    }
                }
            });
        });
        
        // Toggle filtri
        const toggleBtn = document.getElementById('toggle-filters');
        const filtersContainer = document.querySelector('.filters-container');
        
        if (toggleBtn && filtersContainer) {
            const updateToggleText = (visible) => {
                const icon = toggleBtn.querySelector('i');
                const text = toggleBtn.querySelector('.button-text');
                if (icon) icon.className = visible ? 'fas fa-eye-slash' : 'fas fa-eye';
                if (text) text.textContent = visible ? 'Nascondi Filtri' : 'Mostra Filtri';
            };
            
            updateToggleText(!filtersContainer.classList.contains('filters-hidden'));
            
            toggleBtn.addEventListener('click', e => {
                e.preventDefault();
                filtersContainer.classList.toggle('filters-hidden');
                updateToggleText(!filtersContainer.classList.contains('filters-hidden'));
            });
        }
        
        // Update active count
        updateServerSideActiveCount();
    }
    
    function updateServerSideActiveCount() {
        const filterSelects = document.querySelectorAll('.filter-select');
        let activeCount = 0;
        
        filterSelects.forEach(select => {
            if (select.value) activeCount++;
        });
        
        const filtersCount = document.querySelector('.filters-count');
        if (filtersCount) {
            filtersCount.textContent = activeCount > 0 ? `(${activeCount} filtri attivi)` : '';
            filtersCount.style.display = activeCount > 0 ? 'inline' : 'none';
        }
        
        const toggleBtn = document.getElementById('toggle-filters');
        if (toggleBtn) {
            toggleBtn.classList.toggle('has-active-filters', activeCount > 0);
        }
    }
    
    // ========================================================================
    // SCADENZE FILTERS (specializzazione)
    // ========================================================================
    
    const scadenzeConfig = {
        tableId: null,
        searchInputId: null,
        filterSelectors: []
    };
    
    function initScadenze(cfg) {
        scadenzeConfig.tableId = cfg.tableId || 'scadenzeTable';
        scadenzeConfig.searchInputId = cfg.searchInputId || 'searchInput';
        scadenzeConfig.filterSelectors = cfg.filterSelectors || [];
        
        // Event listeners filtri
        scadenzeConfig.filterSelectors.forEach(filter => {
            const select = document.getElementById(filter.selectId);
            if (select) {
                select.addEventListener('change', applyScadenzeFilters);
            }
        });
        
        // Ricerca
        const searchInput = document.getElementById(scadenzeConfig.searchInputId);
        if (searchInput) {
            searchInput.addEventListener('input', applyScadenzeFilters);
        }
        
        // Reset
        const resetBtn = document.getElementById('resetAllFilters');
        if (resetBtn) {
            resetBtn.addEventListener('click', resetScadenzeFilters);
        }
        
        log('Scadenze filters init');
    }
    
    function applyScadenzeFilters() {
        const table = document.getElementById(scadenzeConfig.tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr');
        const searchInput = document.getElementById(scadenzeConfig.searchInputId);
        const searchTerm = searchInput?.value.toLowerCase() || '';
        
        const filters = {};
        let activeCount = 0;
        
        scadenzeConfig.filterSelectors.forEach(f => {
            const select = document.getElementById(f.selectId);
            if (select?.value) {
                filters[f.campo] = select.value;
                activeCount++;
            }
        });
        
        let visibleCount = 0;
        
        rows.forEach(row => {
            let show = true;
            
            // Ricerca nome
            if (searchTerm) {
                const nome = (row.dataset.militareNome || '').toLowerCase();
                if (!nome.includes(searchTerm)) show = false;
            }
            
            // Filtri stato celle
            if (show) {
                const cells = row.querySelectorAll('td.scadenza-cell');
                
                for (const campo in filters) {
                    cells.forEach(cell => {
                        if (cell.dataset.campo?.includes(campo)) {
                            if (cell.dataset.stato !== filters[campo]) {
                                show = false;
                            }
                        }
                    });
                }
            }
            
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        
        // No results
        const noResults = document.getElementById('noResultsMessage');
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
        
        // Reset button
        const resetBtn = document.getElementById('resetAllFilters');
        if (resetBtn) {
            resetBtn.style.display = (activeCount > 0 || searchTerm) ? 'inline-block' : 'none';
        }
    }
    
    function resetScadenzeFilters() {
        scadenzeConfig.filterSelectors.forEach(f => {
            const select = document.getElementById(f.selectId);
            if (select) select.value = '';
        });
        
        const searchInput = document.getElementById(scadenzeConfig.searchInputId);
        if (searchInput) searchInput.value = '';
        
        applyScadenzeFilters();
    }
    
    function getVisibleMilitareIds() {
        const table = document.getElementById(scadenzeConfig.tableId);
        if (!table) return [];
        
        return Array.from(table.querySelectorAll('tbody tr:not([style*="display: none"])'))
            .map(row => row.dataset.militareId)
            .filter(Boolean);
    }
    
    function exportScadenzeExcel(baseUrl) {
        const allRows = document.querySelectorAll('#' + scadenzeConfig.tableId + ' tbody tr');
        const visibleIds = getVisibleMilitareIds();
        
        if (visibleIds.length > 0 && visibleIds.length < allRows.length) {
            window.location.href = baseUrl + '?ids=' + visibleIds.join(',');
        } else {
            window.location.href = baseUrl;
        }
    }
    
    // ========================================================================
    // INIZIALIZZAZIONE
    // ========================================================================
    
    function init() {
        log('Initializing SUGECO.Filters');
        
        // Init dipendenza compagnia-plotone
        initCompagniaPlotoneDependency();
        
        // Init stato plotone iniziale
        const compagniaSelect = document.getElementById('compagnia');
        const form = compagniaSelect?.closest('form');
        if (compagniaSelect) {
            const isClientSide = form?.classList.contains('filter-local');
            updatePlotoneOptions(compagniaSelect.value, isClientSide);
        }
        
        // Cerca form client-side
        const localForms = document.querySelectorAll('form.filter-local');
        if (localForms.length > 0) {
            setTimeout(() => initClientSide(), 50);
        }
        
        // Init server-side
        initServerSide();
        
        log('SUGECO.Filters initialized');
    }
    
    // ========================================================================
    // PUBLIC API
    // ========================================================================
    
    return {
        init,
        
        // Client-side
        initClientSide,
        applyFilters: applyClientSideFilters,
        refresh: applyClientSideFilters,
        reset: function() {
            clientSideSelects.forEach(s => s.value = '');
            applyClientSideFilters();
        },
        
        // Filtri speciali
        registerSpecialFilter: function(name, fn) {
            config.specialFilters[name] = fn;
        },
        
        // Configurazione
        setConfig: function(key, value) {
            config[key] = value;
        },
        setNoResultsMessage: function(msg) {
            config.noResultsMessage = msg;
        },
        
        // Scadenze
        initScadenze,
        applyScadenzeFilters,
        resetScadenzeFilters,
        getVisibleMilitareIds,
        exportScadenzeExcel,
        
        // Compagnia-Plotone
        updatePlotoneOptions
    };
})();

// Alias per retrocompatibilità
window.ClientSideFilters = {
    init: (opts) => window.SUGECO.Filters.initClientSide(opts),
    refresh: () => window.SUGECO.Filters.refresh(),
    reset: () => window.SUGECO.Filters.reset(),
    registerSpecialFilter: (n, f) => window.SUGECO.Filters.registerSpecialFilter(n, f),
    applyFilters: () => window.SUGECO.Filters.applyFilters()
};

// Alias per scadenze
window.SUGECO.ScadenzeFilters = {
    init: (cfg) => window.SUGECO.Filters.initScadenze(cfg),
    applyFilters: () => window.SUGECO.Filters.applyScadenzeFilters(),
    resetFilters: () => window.SUGECO.Filters.resetScadenzeFilters(),
    getVisibleMilitareIds: () => window.SUGECO.Filters.getVisibleMilitareIds(),
    exportExcel: (url) => window.SUGECO.Filters.exportScadenzeExcel(url)
};

// Auto-init
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.Filters.init();
});
