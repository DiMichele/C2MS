/**
 * Modulo di ricerca unificato SUGECO
 * Sistema con logiche specifiche per dashboard, organigramma e tabelle
 */
window.SUGECO = window.SUGECO || {};
window.SUGECO.Search = {
    
    /**
     * Initialize search module
     */
    init: function() {
        this.setupSearchInputs();
        window.SUGECO.Core.log('Modulo di ricerca unificato inizializzato');
    },

    /**
     * Setup all search inputs on the page
     */
    setupSearchInputs: function() {
        const searchInputs = document.querySelectorAll('[data-search-type], .search-input, [data-search-target]');
        
        searchInputs.forEach(input => {
            this.setupSearchInput(input);
        });
    },

    /**
     * Setup individual search input
     */
    setupSearchInput: function(input) {
        if (!input) return;

        input.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            const searchType = e.target.getAttribute('data-search-type') || 'table';
            const targetContainer = e.target.getAttribute('data-target-container') || e.target.getAttribute('data-search-target');
            
            // Debounce per performance
            clearTimeout(e.target.searchTimeout);
            e.target.searchTimeout = setTimeout(() => {
                this.performUnifiedSearch(query, searchType, targetContainer, e.target);
            }, 150);
        });

        window.SUGECO.Core.log('Campo di ricerca configurato: ' + (input.id || input.className));
    },

    /**
     * Unified search function for all pages
     */
    performUnifiedSearch: function(query, searchType, targetContainer, input) {
        // Determina il tipo di ricerca basato sulla pagina e tipo
        if (searchType === 'dashboard' || this.isDashboardPage()) {
            // Solo ricerca dashboard, non continuare con altre ricerche
            this.searchDashboard(query, input);
            return;
        } else if (searchType === 'organigramma' || this.isOrganigrammaPage()) {
            // Solo ricerca organigramma, non continuare con altre ricerche
            this.searchOrganigramma(query);
            return;
        } else {
            // Per tutte le altre pagine (tabelle), usa filtro DOM
            this.searchWithDOMFilter(query, targetContainer, input);
        }
    },

    /**
     * Check if current page is dashboard
     */
    isDashboardPage: function() {
        return window.location.pathname === '/' || window.location.pathname.includes('/dashboard');
    },

    /**
     * Check if current page is organigramma
     */
    isOrganigrammaPage: function() {
        return window.location.pathname.includes('/organigramma');
    },

    /**
     * Search for dashboard - shows suggestions with redirect
     */
    searchDashboard: function(query, input) {
        if (!query || query.trim().length === 0) {
            this.hideDashboardSuggestions();
            return;
        }

        // Chiamata AJAX per ottenere i militari
        this.fetchMilitariSuggestions(query, input);
    },

    /**
     * Fetch militari suggestions via AJAX
     */
    fetchMilitariSuggestions: function(query, input) {
        const url = window.SUGECO.Core.buildUrl(`/militare/search?query=${encodeURIComponent(query)}`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showDashboardSuggestions(data.data, input);
            } else {
                throw new Error(data.message || 'Errore nella ricerca');
            }
        })
        .catch(error => {
            window.SUGECO.Core.log('Dashboard search error: ' + error.message, 'error');
            this.hideDashboardSuggestions();
        });
    },

    /**
     * Show dashboard suggestions dropdown
     */
    showDashboardSuggestions: function(militari, input) {
        // Rimuovi suggerimenti esistenti
        this.hideDashboardSuggestions();
        
        if (militari.length === 0) {
            this.showNoResultsSuggestion(input);
            return;
        }

        // Crea container suggerimenti
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'dashboard-suggestions';
        suggestionsContainer.className = 'dashboard-suggestions';
        suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        `;

        // Aggiungi militari ai suggerimenti
        militari.slice(0, 8).forEach((militare, index) => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item';
            suggestionItem.style.cssText = `
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                align-items: center;
                transition: background-color 0.2s;
            `;
            
            suggestionItem.innerHTML = `
                <div class="suggestion-avatar" style="width: 40px; height: 40px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-weight: bold;">
                    ${militare.nome.charAt(0)}${militare.cognome.charAt(0)}
                </div>
                <div class="suggestion-info" style="flex: 1;">
                    <div style="font-weight: 600; color: #333;">${militare.grado ? militare.grado.nome : ''} ${militare.cognome} ${militare.nome}</div>
                    <div style="font-size: 0.85em; color: #666;">
                        ${militare.plotone ? militare.plotone.nome : 'N/A'} • ${militare.polo ? militare.polo.nome : 'N/A'}
                    </div>
                </div>
                <div class="suggestion-action" style="color: #007bff;">
                    <i class="fas fa-arrow-right"></i>
                </div>
            `;

            // Hover effect
            suggestionItem.addEventListener('mouseenter', () => {
                suggestionItem.style.backgroundColor = '#f8f9fa';
            });
            suggestionItem.addEventListener('mouseleave', () => {
                suggestionItem.style.backgroundColor = '';
            });

            // Click handler - redirect to militare page or open modal based on page
            suggestionItem.addEventListener('click', () => {
                // Add loading state
                suggestionItem.style.opacity = '0.7';
                suggestionItem.style.pointerEvents = 'none';
                
                if (this.isDashboardPage()) {
                    // For dashboard, redirect to show the biografic card
                    window.SUGECO.Core.log('Apertura scheda biografica per: ' + militare.cognome + ' ' + militare.nome);
                    window.location.href = window.SUGECO.Core.buildUrl(`/militare/${militare.id}`);
                } else {
                    // For other pages, redirect normally
                    window.location.href = window.SUGECO.Core.buildUrl(`/militare/${militare.id}`);
                }
            });

            suggestionsContainer.appendChild(suggestionItem);
        });

        // Aggiungi al DOM
        const searchContainer = input.closest('.search-container') || input.parentElement;
        searchContainer.style.position = 'relative';
        searchContainer.appendChild(suggestionsContainer);
    },

    /**
     * Show no results suggestion
     */
    showNoResultsSuggestion: function(input) {
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'dashboard-suggestions';
        suggestionsContainer.className = 'dashboard-suggestions';
        suggestionsContainer.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            padding: 20px;
            text-align: center;
            color: #666;
        `;
        
        suggestionsContainer.innerHTML = `
            <i class="fas fa-search fa-2x mb-2" style="color: #ccc;"></i>
            <div>Nessun militare trovato</div>
        `;

        const searchContainer = input.closest('.search-container') || input.parentElement;
        searchContainer.appendChild(suggestionsContainer);
    },

    /**
     * Hide dashboard suggestions
     */
    hideDashboardSuggestions: function() {
        const existing = document.getElementById('dashboard-suggestions');
        if (existing) {
            existing.remove();
        }
    },

    /**
     * Search organigramma elements - shows dropdown with matching militari
     */
    searchOrganigramma: function(query) {
        const filter = query.toLowerCase();
        
        // Reset all highlights
        this.clearOrganigrammaHighlights();
        
        if (filter === '') {
            return;
        }

        // Determine current view (poli or plotoni)
        const currentView = this.getCurrentOrganigrammaView();
        
        // Find militare items only in current view
        const viewSelector = currentView === 'poli' ? '#poliView .militare-item' : '#plotoniView .militare-item';
        const militareItems = document.querySelectorAll(viewSelector);
        const foundMilitari = [];
        
        militareItems.forEach(item => {
            const nameElement = item.querySelector('.militare-nome');
            if (nameElement) {
                const text = nameElement.textContent.toLowerCase();
                
                if (this.matchesInitials(text, filter)) {
                    const militareData = this.extractMilitareData(item);
                    foundMilitari.push({
                        element: item,
                        data: militareData
                    });
                }
            }
        });

        // Show dropdown with results
        this.showOrganigrammaDropdown(foundMilitari, filter);
    },

    /**
     * Highlight a militare item in organigramma
     */
    highlightMilitareItem: function(item) {
        item.classList.add('militare-found');
        item.style.cssText += `
            background: linear-gradient(135deg, #e8f5e8, #c8e6c9) !important;
            border: 3px solid #4caf50 !important;
            border-radius: 12px !important;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4) !important;
            transform: scale(1.05) !important;
            transition: all 0.4s ease !important;
            position: relative !important;
            z-index: 10 !important;
        `;
        
        // Add animated search indicator
        if (!item.querySelector('.search-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'search-indicator';
            indicator.innerHTML = '<i class="fas fa-crosshairs"></i>';
            indicator.style.cssText = `
                position: absolute;
                top: -8px;
                right: -8px;
                background: #4caf50;
                color: white;
                border-radius: 50%;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
                animation: searchPulse 2s infinite;
                box-shadow: 0 2px 8px rgba(76, 175, 80, 0.5);
            `;
            item.appendChild(indicator);
            
            // Add CSS animation if not exists
            if (!document.getElementById('search-animations')) {
                const style = document.createElement('style');
                style.id = 'search-animations';
                style.textContent = `
                    @keyframes searchPulse {
                        0% { transform: scale(1); }
                        50% { transform: scale(1.2); }
                        100% { transform: scale(1); }
                    }
                    @keyframes containerHighlight {
                        0% { background-color: rgba(76, 175, 80, 0.1); }
                        50% { background-color: rgba(76, 175, 80, 0.2); }
                        100% { background-color: rgba(76, 175, 80, 0.1); }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    },

    /**
     * Highlight parent container (plotone/polo)
     */
    highlightParentContainer: function(item) {
        const parentContainer = item.closest('.node-figlio');
        if (parentContainer) {
            parentContainer.classList.add('container-with-found');
            parentContainer.style.cssText += `
                border: 2px solid #4caf50 !important;
                border-radius: 12px !important;
                box-shadow: 0 4px 16px rgba(76, 175, 80, 0.2) !important;
                animation: containerHighlight 3s infinite !important;
                transition: all 0.3s ease !important;
            `;
            
            // Add header indicator
            const header = parentContainer.querySelector('.node-figlio-header');
            if (header && !header.querySelector('.container-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'container-indicator';
                indicator.innerHTML = ' <i class="fas fa-search text-success"></i>';
                header.appendChild(indicator);
            }
        }
    },

    /**
     * Ensure militare is visible in the organigramma (handle horizontal scrolling)
     */
    ensureMilitareIsVisible: function(item) {
        const parentContainer = item.closest('.node-figlio');
        if (!parentContainer) return;
        
        const scrollContainer = parentContainer.closest('.nodi-figli');
        if (!scrollContainer) return;
        
        // Calculate if the container is in view
        const containerRect = parentContainer.getBoundingClientRect();
        const scrollRect = scrollContainer.getBoundingClientRect();
        
        // If container is not fully visible, scroll to it
        if (containerRect.left < scrollRect.left || containerRect.right > scrollRect.right) {
            const scrollLeft = parentContainer.offsetLeft - (scrollContainer.offsetWidth / 2) + (parentContainer.offsetWidth / 2);
            scrollContainer.scrollTo({
                left: Math.max(0, scrollLeft),
                behavior: 'smooth'
            });
        }
        
        // Update scroll indicators if they exist
        this.updateScrollIndicators(scrollContainer);
    },

    /**
     * Update scroll indicators after programmatic scroll
     */
    updateScrollIndicators: function(container) {
        const indicator = container.parentElement.querySelector('.scroll-indicator');
        if (!indicator) return;
        
        const dots = indicator.querySelectorAll('.indicator-dot');
        if (dots.length === 0) return;
        
        const itemWidth = container.firstElementChild ? container.firstElementChild.offsetWidth : 0;
        const gap = 20; // Gap between items
        const itemsPerView = 3;
        const currentIndex = Math.round(container.scrollLeft / ((itemWidth + gap) * itemsPerView));
        
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    },

    /**
     * Clear all organigramma highlights
     */
    clearOrganigrammaHighlights: function() {
        // Clear militare items
        const militareItems = document.querySelectorAll('.militare-item');
        militareItems.forEach(item => {
            item.classList.remove('militare-found');
            item.style.background = '';
            item.style.border = '';
            item.style.borderRadius = '';
            item.style.boxShadow = '';
            item.style.transform = '';
            item.style.transition = '';
            item.style.position = '';
            item.style.zIndex = '';
            
            const indicator = item.querySelector('.search-indicator');
            if (indicator) {
                indicator.remove();
            }
        });
        
        // Clear parent containers
        const containers = document.querySelectorAll('.node-figlio');
        containers.forEach(container => {
            container.classList.remove('container-with-found');
            container.style.border = '';
            container.style.borderRadius = '';
            container.style.boxShadow = '';
            container.style.animation = '';
            container.style.transition = '';
            
            const containerIndicator = container.querySelector('.container-indicator');
            if (containerIndicator) {
                containerIndicator.remove();
            }
        });
        
        this.hideOrganigrammaDropdown();
    },

    /**
     * Scroll to highlighted militare
     */
    scrollToMilitare: function(item) {
        const container = item.closest('.node-figlio');
        if (container) {
            // First scroll the container into view vertically
            container.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Then scroll the militare item into view within the container
            setTimeout(() => {
                item.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'nearest',
                    inline: 'nearest'
                });
            }, 300);
        }
    },

    /**
     * Get current organigramma view (poli or plotoni)
     */
    getCurrentOrganigrammaView: function() {
        const poliView = document.getElementById('poliView');
        const plotoniView = document.getElementById('plotoniView');
        
        if (poliView && !poliView.classList.contains('hidden')) {
            return 'poli';
        } else if (plotoniView && !plotoniView.classList.contains('hidden')) {
            return 'plotoni';
        }
        return 'plotoni'; // default
    },

    /**
     * Extract militare data from DOM element
     */
    extractMilitareData: function(item) {
        const nameElement = item.querySelector('.militare-nome');
        const gradoElement = item.querySelector('.militare-grado');
        const parentContainer = item.closest('.node-figlio');
        const headerElement = parentContainer ? parentContainer.querySelector('.node-figlio-header') : null;
        
        return {
            nome: nameElement ? nameElement.textContent.trim() : '',
            grado: gradoElement ? gradoElement.textContent.trim() : '',
            unita: headerElement ? headerElement.textContent.trim() : '',
            href: nameElement ? nameElement.getAttribute('href') : ''
        };
    },

    /**
     * Show organigramma dropdown with militari results
     */
    showOrganigrammaDropdown: function(foundMilitari, query) {
        this.hideOrganigrammaDropdown();
        
        if (foundMilitari.length === 0) {
            this.showOrganigrammaNoResults(query);
            return;
        }

        const searchContainer = document.querySelector('.search-container');
        if (!searchContainer) return;

        const dropdown = document.createElement('div');
        dropdown.id = 'organigramma-dropdown';
        dropdown.className = 'organigramma-dropdown';
        dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        `;

        // Header
        const header = document.createElement('div');
        header.style.cssText = `
            padding: 12px 16px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
            font-size: 0.9em;
        `;
        header.textContent = foundMilitari.length === 1 ? '1 militare trovato' : `${foundMilitari.length} militari trovati`;
        dropdown.appendChild(header);

        // Results
        foundMilitari.forEach((militare, index) => {
            const item = document.createElement('div');
            item.className = 'dropdown-militare-item';
            item.style.cssText = `
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #f0f0f0;
                display: flex;
                align-items: center;
                transition: background-color 0.2s;
            `;
            
            item.innerHTML = `
                <div class="militare-avatar" style="width: 36px; height: 36px; border-radius: 50%; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-weight: bold; font-size: 0.8em;">
                    ${this.getInitials(militare.data.nome)}
                </div>
                <div class="militare-details" style="flex: 1;">
                    <div style="font-weight: 600; color: #333; margin-bottom: 2px;">${militare.data.nome}</div>
                    <div style="font-size: 0.8em; color: #666;">${militare.data.unita}</div>
                </div>
                <div class="militare-action" style="color: #007bff;">
                    <i class="fas fa-crosshairs"></i>
                </div>
            `;

            // Hover effects
            item.addEventListener('mouseenter', () => {
                item.style.backgroundColor = '#f8f9fa';
            });
            item.addEventListener('mouseleave', () => {
                item.style.backgroundColor = '';
            });

            // Click handler
            item.addEventListener('click', () => {
                this.selectMilitareFromDropdown(militare.element);
                this.hideOrganigrammaDropdown();
            });

            dropdown.appendChild(item);
        });

        searchContainer.appendChild(dropdown);
    },

    /**
     * Show no results message for organigramma
     */
    showOrganigrammaNoResults: function(query) {
        const searchContainer = document.querySelector('.search-container');
        if (!searchContainer) return;

        const noResults = document.createElement('div');
        noResults.id = 'organigramma-dropdown';
        noResults.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            padding: 20px;
            text-align: center;
            color: #666;
        `;
        
        const currentView = this.getCurrentOrganigrammaView();
        const viewName = currentView === 'poli' ? 'poli' : 'plotoni';
        
        noResults.innerHTML = `
            <i class="fas fa-search fa-2x mb-2" style="color: #ccc;"></i>
            <div style="font-weight: 600; margin-bottom: 4px;">Nessun militare trovato</div>
            <div style="font-size: 0.85em;">Nessun risultato per "${query}" nei ${viewName} correnti</div>
        `;

        searchContainer.appendChild(noResults);
    },

    /**
     * Hide organigramma dropdown
     */
    hideOrganigrammaDropdown: function() {
        const existing = document.getElementById('organigramma-dropdown');
        if (existing) {
            existing.remove();
        }
    },

    /**
     * Select militare from dropdown and highlight it
     */
    selectMilitareFromDropdown: function(militareElement) {
        // Clear all previous highlights
        this.clearOrganigrammaHighlights();
        
        // Highlight ONLY the selected militare (not the container) in ocra color for 3 seconds
        this.highlightMilitareTemporary(militareElement);
        
        // Scroll to militare
        this.scrollToMilitare(militareElement);
        this.ensureMilitareIsVisible(militareElement);
    },

    /**
     * Highlight militare temporarily in subtle ocra color for 3 seconds - minimal style
     */
    highlightMilitareTemporary: function(item) {
        // Apply subtle ocra highlighting - minimal and professional
        item.classList.add('militare-selected-temp');
        item.style.cssText += `
            background: linear-gradient(135deg, #faf6f0, #f5f0e6) !important;
            border: 2px solid #d4a574 !important;
            border-radius: 8px !important;
            box-shadow: 0 3px 12px rgba(212, 165, 116, 0.25) !important;
            transform: scale(1.02) !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            z-index: 5 !important;
        `;
        
        // Remove highlight after 3 seconds
        setTimeout(() => {
            this.clearMilitareTemporaryHighlight(item);
        }, 3000);
    },

    /**
     * Clear temporary militare highlight
     */
    clearMilitareTemporaryHighlight: function(item) {
        item.classList.remove('militare-selected-temp');
        item.style.background = '';
        item.style.border = '';
        item.style.borderRadius = '';
        item.style.boxShadow = '';
        item.style.transform = '';
        item.style.transition = '';
        item.style.position = '';
        item.style.zIndex = '';
    },

    /**
     * Get initials from full name
     */
    getInitials: function(fullName) {
        const parts = fullName.trim().split(' ');
        if (parts.length >= 2) {
            return parts[0].charAt(0) + parts[parts.length - 1].charAt(0);
        }
        return parts[0] ? parts[0].charAt(0) : '?';
    },

    /**
     * Search using DOM filtering - Funzione unificata per tabelle
     */
    searchWithDOMFilter: function(query, targetContainer, input) {
        const table = this.getTargetTable(input, targetContainer);
        if (!table) {
            window.SUGECO.Core.log('Tabella target non trovata', 'warn');
            return;
        }

        this.filterTable(query, table);
    },

    /**
     * Get target table for search
     */
    getTargetTable: function(input, targetContainer) {
        // Prima prova con il target specificato
        if (targetContainer) {
            const target = document.getElementById(targetContainer) || document.querySelector(targetContainer);
            if (target) {
                if (target.tagName === 'TABLE') return target;
                if (target.tagName === 'TBODY') return target.closest('table');
                return target.querySelector('table');
            }
        }

        // Fallback: cerca la prima tabella nella pagina
        return document.querySelector('.table, table');
    },

    /**
     * Filter table based on search text with initial letter matching
     */
    filterTable: function(searchText, table) {
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const filter = searchText.toLowerCase();
        let hasResults = false;

        rows.forEach(row => {
            // Skip header rows
            if (row.querySelector('th')) return;

            let shouldShow = false;

            if (filter === '') {
                // Query vuota: mostra tutte le righe
                shouldShow = true;
            } else {
                // Cerca nelle iniziali
                const text = row.textContent.toLowerCase();
                shouldShow = this.matchesInitials(text, filter);
            }

            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) hasResults = true;
        });

        // Gestisce il messaggio "nessun risultato"
        this.toggleNoResultsMessage(table, !hasResults && filter !== '');
    },

    /**
     * Check if text matches initials search criteria
     */
    matchesInitials: function(text, filter) {
        // Per le altre pagine, cerca nelle iniziali dei nomi
        // Estrai tutte le parole significative (lunghezza > 1)
        const words = text.split(/\s+/).filter(word => word.length > 1);
        
        // Cerca se qualsiasi parola inizia con il filtro (funziona con 1 carattere)
        for (let i = 0; i < words.length; i++) {
            const word = words[i];
            if (word.startsWith(filter)) {
                return true;
            }
        }
        
        // Se il filtro ha almeno 2 caratteri, controlla anche le iniziali combinate
        if (filter.length >= 2) {
            for (let i = 0; i < words.length - 1; i++) {
                const word1 = words[i];
                const word2 = words[i + 1];
                
                // Controlla iniziali combinate (es. "MR" per "Mario Rossi")
                const initials1 = word1.charAt(0) + word2.charAt(0); // Nome + Cognome
                const initials2 = word2.charAt(0) + word1.charAt(0); // Cognome + Nome
                
                if (initials1.startsWith(filter) || initials2.startsWith(filter)) {
                    return true;
                }
            }
        }

        return false;
    },

    /**
     * Show/hide "no results" message
     */
    toggleNoResultsMessage: function(table, show) {
        let noResultsMsg = document.getElementById('noResultsMessage');
        
        if (show && !noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'noResultsMessage';
            noResultsMsg.className = 'alert alert-info mt-3';
            noResultsMsg.innerHTML = `
                <div class="d-flex flex-column align-items-center py-3">
                    <i class="fas fa-search fa-2x mb-2 text-muted"></i>
                    <strong>Nessun risultato trovato</strong>
                    <p class="mb-0 text-muted">Prova a modificare i criteri di ricerca</p>
                </div>
            `;
            table.parentNode.insertBefore(noResultsMsg, table.nextSibling);
        }
        
        if (noResultsMsg) {
            noResultsMsg.style.display = show ? 'block' : 'none';
        }
    }
};

// Setup click outside to hide suggestions
document.addEventListener('click', (e) => {
    const dashboardSuggestions = document.getElementById('dashboard-suggestions');
    const organigrammaDropdown = document.getElementById('organigramma-dropdown');
    const searchInput = e.target.closest('.search-input, [data-search-type], .search-input-clean');
    const dropdownItem = e.target.closest('.dropdown-militare-item');
    
    if (dashboardSuggestions && !searchInput) {
        window.SUGECO.Search.hideDashboardSuggestions();
    }
    
    if (organigrammaDropdown && !searchInput && !dropdownItem) {
        window.SUGECO.Search.hideOrganigrammaDropdown();
    }
});

// Auto-inizializzazione quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.SUGECO !== 'undefined' && typeof window.SUGECO.Search !== 'undefined') {
        window.SUGECO.Search.init();
    }
});
