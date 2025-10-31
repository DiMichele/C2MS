/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * Filters functionality for tables and lists
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Filters module
window.SUGECO.Filters = {
    /**
     * Initialize filters functionality
     */
    init: function() {
        window.SUGECO.Core.log('Filters module initialized');
        this.initToggleFilters();
        this.initFilterSelects();
        this.initClearButtons();
        this.initResetAllButton();
        this.updateActiveFiltersCount();
    },

    /**
     * Initialize toggle filters functionality
     */
    initToggleFilters: function() {
        const toggleButton = document.getElementById('toggle-filters');
        const filtersContainer = document.querySelector('.filters-container');
        
        if (!toggleButton || !filtersContainer) {
            window.SUGECO.Core.log('Toggle filters elements not found, skipping initialization', 'warn');
            return;
        }
        
        window.SUGECO.Core.log('Filter toggle initialized');
        
        // Check if filters are initially visible
        const isInitiallyVisible = !filtersContainer.classList.contains('filters-hidden');
        
        // Set initial button text
        this.updateToggleButtonText(toggleButton, isInitiallyVisible);
        
        // Add click event listener
        toggleButton.addEventListener('click', (e) => {
            e.preventDefault();
            
            const isCurrentlyVisible = !filtersContainer.classList.contains('filters-hidden');
            
            if (isCurrentlyVisible) {
                // Hide filters
                filtersContainer.classList.add('filters-hidden');
                this.updateToggleButtonText(toggleButton, false);
            } else {
                // Show filters
                filtersContainer.classList.remove('filters-hidden');
                this.updateToggleButtonText(toggleButton, true);
            }
            
            const isVisible = !filtersContainer.classList.contains('filters-hidden');
            window.SUGECO.Core.log('Filters toggled: ' + (isVisible ? 'visible' : 'hidden'));
        });
    },

    /**
     * Update toggle button text
     * @param {Element} button - Toggle button element
     * @param {boolean} isVisible - Whether filters are visible
     */
    updateToggleButtonText: function(button, isVisible) {
        const icon = button.querySelector('i');
        const text = button.querySelector('.button-text');
        
        if (isVisible) {
            if (icon) icon.className = 'fas fa-eye-slash';
            if (text) text.textContent = 'Nascondi Filtri';
        } else {
            if (icon) icon.className = 'fas fa-eye';
            if (text) text.textContent = 'Mostra Filtri';
        }
    },

    /**
     * Initialize filter select elements
     */
    initFilterSelects: function() {
        const filterSelects = document.querySelectorAll('.filter-select');
        
        window.SUGECO.Core.log(`Found ${filterSelects.length} filter selects`);
        
        filterSelects.forEach(select => {
            // Check if filter is already applied
            if (select.value && select.value !== '') {
                window.SUGECO.Core.log(`Filter ${select.name} is applied with value: ${select.value}`);
            }
            
            select.addEventListener('change', function() {
                window.SUGECO.Core.log(`Filter ${this.name} changed to: ${this.value}`);
                
                // Find the parent form
                const form = this.closest('form');
                if (form) {
                    // Reset page to 1 when filtering
                    const pageInput = form.querySelector('input[name="page"]');
                    if (pageInput) {
                        pageInput.value = '1';
                    }
                    
                    window.SUGECO.Core.log('Submitting form due to filter change');
                    form.submit();
                } else {
                    window.SUGECO.Core.log('No form found for filter select', 'warn');
                }
            });
        });
    },

    /**
     * Initialize clear filter buttons
     */
    initClearButtons: function() {
        const clearButtons = document.querySelectorAll('.clear-filter');
        
        window.SUGECO.Core.log(`Found ${clearButtons.length} clear filter buttons`);
        
        clearButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                const filterName = button.getAttribute('data-filter');
                window.SUGECO.Core.log(`Clearing filter: ${filterName}`);
                
                // Find the corresponding select element
                const select = document.querySelector(`select[name="${filterName}"]`);
                if (select) {
                    select.value = '';
                    
                    // Submit the form
                    const form = select.closest('form');
                    if (form) {
                        window.SUGECO.Core.log('Submitting form after clearing filter');
                        form.submit();
                    } else {
                        // Fallback: redirect with cleared parameter
                        window.SUGECO.Core.log('Using URL fallback for filter clearing');
                        const url = new URL(window.location);
                        url.searchParams.delete(filterName);
                        url.searchParams.delete('page'); // Reset page
                        window.location.href = url.toString();
                    }
                } else {
                    window.SUGECO.Core.log(`Select element not found for filter: ${filterName}`, 'warn');
                }
            });
        });
    },

    /**
     * Initialize reset all filters button
     */
    initResetAllButton: function() {
        const resetAllBtns = document.querySelectorAll('.reset-all-filters');
        
        window.SUGECO.Core.log(`Found ${resetAllBtns.length} reset all buttons`);
        
        resetAllBtns.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                window.SUGECO.Core.log('Reset all filters clicked');
                
                // Clear all filter selects
                const filterSelects = document.querySelectorAll('.filter-select');
                filterSelects.forEach(select => {
                    select.value = '';
                });
                
                // Submit form or redirect
                const form = document.querySelector('.filters-form');
                if (form) {
                    form.submit();
                } else {
                    // Fallback: redirect to clean URL
                    const cleanUrl = window.location.pathname;
                    window.location.href = cleanUrl;
                }
            });
        });
    },

    /**
     * Update active filters count display
     */
    updateActiveFiltersCount: function() {
        const filterSelects = document.querySelectorAll('.filter-select');
        let activeCount = 0;
        
        filterSelects.forEach(select => {
            if (select.value && select.value !== '') {
                activeCount++;
            }
        });
        
        window.SUGECO.Core.log(`Found ${activeCount} active filters`);
        
        // Update filters count display
        const filtersCount = document.querySelector('.filters-count');
        if (filtersCount) {
            if (activeCount > 0) {
                filtersCount.textContent = `(${activeCount} filtri attivi)`;
                filtersCount.style.display = 'inline';
            } else {
                filtersCount.style.display = 'none';
            }
        }
        
        // Update toggle button to show active filters
        const toggleButton = document.getElementById('toggle-filters');
        if (toggleButton && activeCount > 0) {
            toggleButton.classList.add('has-active-filters');
        } else if (toggleButton) {
            toggleButton.classList.remove('has-active-filters');
        }
    }
};

// Initialize module when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.Filters.init();
});
