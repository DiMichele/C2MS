/**
 * Modulo di ricerca avanzato SUGECO
 * Logiche specifiche per dashboard (suggerimenti) e organigramma (evidenziazione)
 */
window.SUGECO = window.SUGECO || {};
window.SUGECO.SearchEnhanced = {
    
    /**
     * Initialize enhanced search module
     */
    init: function() {
        this.setupEnhancedSearch();
        window.SUGECO.Core.log('Modulo di ricerca avanzato inizializzato');
    },

    /**
     * Setup enhanced search for specific pages
     */
    setupEnhancedSearch: function() {
        const searchInputs = document.querySelectorAll('[data-search-type]');
        
        searchInputs.forEach(input => {
            // Aggiungi nuovo listener avanzato
            input.addEventListener('input', (e) => this.handleInput(e));
        });
    },

    /**
     * Handle search input with enhanced logic
     */
    handleInput: function(e) {
        const input = e.target;
        const query = input.value.trim();
        const searchType = input.getAttribute('data-search-type');
        
        // Debounce
        clearTimeout(input.enhancedTimeout);
        input.enhancedTimeout = setTimeout(() => {
            this.performEnhancedSearch(query, searchType, input);
        }, 150);
    },

    /**
     * Perform enhanced search based on page type
     */
    performEnhancedSearch: function(query, searchType, input) {
        if (this.isDashboardPage()) {
            this.searchDashboard(query, input);
        } else if (searchType === 'organigramma' || this.isOrganigrammaPage()) {
            this.searchOrganigramma(query);
        }
        // Per le altre pagine, il sistema base gestisce già tutto
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
     * Dashboard search with suggestions and redirect
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
        const url = `/militare/search?query=${encodeURIComponent(query)}`;
        
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

            // Click handler - redirect to militare page
            suggestionItem.addEventListener('click', () => {
                window.location.href = `/militare/${militare.id}`;
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
     * Search organigramma elements - highlights matching militari
     */
    searchOrganigramma: function(query) {
        const filter = query.toLowerCase();
        
        // Reset all highlights
        this.clearOrganigrammaHighlights();
        
        if (filter === '') {
            return;
        }

        // Find all militare items
        const militareItems = document.querySelectorAll('.militare-item');
        let foundCount = 0;
        
        militareItems.forEach(item => {
            const nameElement = item.querySelector('.militare-nome');
            if (nameElement) {
                const text = nameElement.textContent.toLowerCase();
                
                if (this.matchesInitials(text, filter)) {
                    this.highlightMilitareItem(item);
                    foundCount++;
                    
                    // Scroll to first match
                    if (foundCount === 1) {
                        this.scrollToMilitare(item);
                    }
                }
            }
        });

        // Show result count for organigramma
        this.showOrganigrammaResultCount(foundCount);
    },

    /**
     * Check if text matches initials search criteria
     */
    matchesInitials: function(text, filter) {
        const words = text.split(/\s+/).filter(word => word.length > 1);
        
        // Cerca se qualsiasi parola inizia con il filtro
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
                
                const initials1 = word1.charAt(0) + word2.charAt(0);
                const initials2 = word2.charAt(0) + word1.charAt(0);
                
                if (initials1.startsWith(filter) || initials2.startsWith(filter)) {
                    return true;
                }
            }
        }

        return false;
    },

    /**
     * Highlight a militare item in organigramma
     */
    highlightMilitareItem: function(item) {
        item.style.cssText += `
            background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
            border: 2px solid #ffc107 !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3) !important;
            transform: scale(1.02) !important;
            transition: all 0.3s ease !important;
        `;
        
        // Add a small indicator
        if (!item.querySelector('.search-indicator')) {
            const indicator = document.createElement('div');
            indicator.className = 'search-indicator';
            indicator.innerHTML = '<i class="fas fa-search" style="color: #856404;"></i>';
            indicator.style.cssText = `
                position: absolute;
                top: 5px;
                right: 5px;
                background: #fff3cd;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
            `;
            item.style.position = 'relative';
            item.appendChild(indicator);
        }
    },

    /**
     * Clear all organigramma highlights
     */
    clearOrganigrammaHighlights: function() {
        const militareItems = document.querySelectorAll('.militare-item');
        militareItems.forEach(item => {
            item.style.background = '';
            item.style.border = '';
            item.style.borderRadius = '';
            item.style.boxShadow = '';
            item.style.transform = '';
            item.style.transition = '';
            
            const indicator = item.querySelector('.search-indicator');
            if (indicator) {
                indicator.remove();
            }
        });
        
        this.hideOrganigrammaResultCount();
    },

    /**
     * Scroll to highlighted militare
     */
    scrollToMilitare: function(item) {
        const container = item.closest('.node-figlio');
        if (container) {
            container.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    },

    /**
     * Show organigramma result count
     */
    showOrganigrammaResultCount: function(count) {
        this.hideOrganigrammaResultCount();
        
        const searchContainer = document.querySelector('.search-container');
        if (searchContainer) {
            const countElement = document.createElement('div');
            countElement.id = 'organigramma-result-count';
            countElement.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                background: #007bff;
                color: white;
                padding: 8px 12px;
                border-radius: 0 0 8px 8px;
                font-size: 0.85em;
                font-weight: 500;
                z-index: 100;
            `;
            countElement.textContent = `${count} militare${count !== 1 ? 'i' : ''} trovato${count !== 1 ? 'i' : ''}`;
            searchContainer.appendChild(countElement);
        }
    },

    /**
     * Hide organigramma result count
     */
    hideOrganigrammaResultCount: function() {
        const existing = document.getElementById('organigramma-result-count');
        if (existing) {
            existing.remove();
        }
    }
};

// Setup click outside to hide suggestions
document.addEventListener('click', (e) => {
    const dashboardSuggestions = document.getElementById('dashboard-suggestions');
    const searchInput = e.target.closest('.search-input, [data-search-type]');
    
    if (dashboardSuggestions && !searchInput) {
        window.SUGECO.SearchEnhanced.hideDashboardSuggestions();
    }
});

// Auto-inizializzazione quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (typeof window.SUGECO !== 'undefined' && typeof window.SUGECO.SearchEnhanced !== 'undefined') {
            window.SUGECO.SearchEnhanced.init();
        }
    }, 300); // Dopo il modulo base
});
