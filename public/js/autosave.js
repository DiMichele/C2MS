/**
 * SUGECO Autosave - Form auto-save functionality
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Autosave module
window.SUGECO.Autosave = {
    csrfToken: null,
    
    /**
     * Initialize autosave functionality
     */
    init: function() {
        window.SUGECO.Core.log('Autosave module initialized');
        
        // Get CSRF token
        this.csrfToken = window.SUGECO.Core.getCsrfToken();
        if (!this.csrfToken) {
            window.SUGECO.Core.log('CSRF token not found. Autosave will not function properly.', 'error');
            return;
        }
        
        // Initialize autosave for different input types
        this.initTextareaAutosave();
        this.initSelectAutosave();
    },

    /**
     * Initialize autosave for textarea elements
     */
    initTextareaAutosave: function() {
        const textareas = document.querySelectorAll('textarea[data-autosave-url]');
        
        if (textareas.length === 0) {
            return;
        }
        
        window.SUGECO.Core.log(`Initializing autosave for ${textareas.length} textarea(s)`);
        
        textareas.forEach(textarea => {
            const url = textarea.getAttribute('data-autosave-url');
            const field = textarea.getAttribute('data-autosave-field');
            
            if (!url || !field) {
                window.SUGECO.Core.log('Missing data attributes for autosave textarea', 'warn');
                return;
            }
            
            // Track last saved value to prevent unnecessary saves
            let lastSavedValue = textarea.value;
            let isSaving = false;
            
            // Create debounced save function
            const debouncedSave = window.SUGECO.Core.debounce((value) => {
                if (value !== lastSavedValue && !isSaving) {
                    this.saveTextareaValue(url, field, value, textarea, () => {
                        lastSavedValue = value;
                        isSaving = false;
                    });
                    isSaving = true;
                }
            }, window.SUGECO.Core.config.saveDelay);
            
            // Add input event listener
            textarea.addEventListener('input', (e) => {
                const value = e.target.value;
                debouncedSave(value);
                
                // Only show saving state if value actually changed
                if (value !== lastSavedValue) {
                    this.showSavingState(textarea);
                }
            });
            
            // Remove blur event to avoid duplicate saves
            // The debounced input event is sufficient
        });
    },

    /**
     * Save textarea value via AJAX
     * @param {string} url - Save URL
     * @param {string} field - Field name
     * @param {string} value - Value to save
     * @param {Element} textarea - Textarea element
     * @param {Function} callback - Callback after save completion
     */
    saveTextareaValue: function(url, field, value, textarea, callback) {
        // Show saving indicator
        this.showSavingState(textarea);
        
        const data = {
            [field]: value,
            _token: this.csrfToken
        };
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showSavedState(textarea);
                
                // Toast disabled to avoid spam
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            window.SUGECO.Core.log('Save error:', 'error');
            if (window.SUGECO.Core.config.debug) {
                window.SUGECO.Core.log(error.message, 'error');
            }
            this.showErrorState(textarea);
            
            // Toast disabled to avoid spam
        })
        .finally(() => {
            // Execute callback to update tracking variables
            if (typeof callback === 'function') {
                callback();
            }
        });
    },

    /**
     * Initialize autosave for select elements
     */
    initSelectAutosave: function() {
        const selects = document.querySelectorAll('select[data-autosave-url]');
        
        if (selects.length === 0) {
            return;
        }
        
        window.SUGECO.Core.log(`Initializing autosave for ${selects.length} select(s)`);
        
        selects.forEach(select => {
            const url = select.getAttribute('data-autosave-url');
            const field = select.getAttribute('data-autosave-field');
            
            if (!url || !field) {
                window.SUGECO.Core.log('Missing data attributes for autosave select', 'warn');
                return;
            }
            
            // Add change event listener
            select.addEventListener('change', (e) => {
                const value = e.target.value;
                this.saveSelectValue(url, field, value, select);
            });
        });
    },

    /**
     * Save select value via AJAX
     * @param {string} url - Save URL
     * @param {string} field - Field name
     * @param {string} value - Value to save
     * @param {Element} select - Select element
     */
    saveSelectValue: function(url, field, value, select) {
        // Show saving indicator
        this.showSavingState(select);
        
        const data = {
            [field]: value,
            _token: this.csrfToken
        };
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showSavedState(select);
                
                // Update related UI elements if needed
                this.updateRelatedUI(select, field, value);
                
                // Show success toast if available
                // Toast disabled to avoid spam
            } else {
                throw new Error(data.message || 'Save failed');
            }
        })
        .catch(error => {
            window.SUGECO.Core.log('Save error:', 'error');
            if (window.SUGECO.Core.config.debug) {
                window.SUGECO.Core.log(error.message, 'error');
            }
            this.showErrorState(select);
            
            // Show error toast if available
            // Toast disabled to avoid spam
        });
    },

    /**
     * Show saving state indicator
     * @param {Element} element - Input element
     */
    showSavingState: function(element) {
        // Remove existing state classes
        element.classList.remove('autosave-saved', 'autosave-error');
        element.classList.add('autosave-saving');
        
        // Update indicator if exists
        const indicator = this.getOrCreateIndicator(element);
        indicator.innerHTML = '<i class="fas fa-spinner fa-spin text-warning"></i> Salvando...';
        indicator.className = 'autosave-indicator saving';
    },

    /**
     * Show saved state indicator
     * @param {Element} element - Input element
     */
    showSavedState: function(element) {
        element.classList.remove('autosave-saving', 'autosave-error');
        element.classList.add('autosave-saved');
        
        const indicator = this.getOrCreateIndicator(element);
        indicator.innerHTML = '<i class="fas fa-check text-success"></i> Salvato';
        indicator.className = 'autosave-indicator saved';
        
        // Hide indicator after delay
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => {
                indicator.innerHTML = '';
                indicator.style.opacity = '1';
            }, 300);
        }, 2000);
    },

    /**
     * Show error state indicator
     * @param {Element} element - Input element
     */
    showErrorState: function(element) {
        element.classList.remove('autosave-saving', 'autosave-saved');
        element.classList.add('autosave-error');
        
        const indicator = this.getOrCreateIndicator(element);
        indicator.innerHTML = '<i class="fas fa-exclamation-triangle text-danger"></i> Errore';
        indicator.className = 'autosave-indicator error';
    },

    /**
     * Get or create save indicator element
     * @param {Element} element - Input element
     * @returns {Element} Indicator element
     */
    getOrCreateIndicator: function(element) {
        const existingIndicator = element.parentNode.querySelector('.autosave-indicator');
        
        if (existingIndicator) {
            return existingIndicator;
        }
        
        const indicator = document.createElement('div');
        indicator.className = 'autosave-indicator';
        indicator.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            pointer-events: none;
            z-index: 10;
            transition: opacity 0.3s ease;
        `;
        
        // Make parent relative if needed
        const parent = element.parentNode;
        if (getComputedStyle(parent).position === 'static') {
            parent.style.position = 'relative';
        }
        
        parent.appendChild(indicator);
        return indicator;
    },

    /**
     * Update related UI elements after save
     * @param {Element} select - Select element
     * @param {string} field - Field name
     * @param {string} value - New value
     */
    updateRelatedUI: function(select, field, value) {
        try {
            // Update role-related UI
            if (field === 'ruolo_id') {
                const selectedOption = select.querySelector(`option[value="${value}"]`);
                const roleName = selectedOption ? selectedOption.textContent : 'Nessun ruolo';
                
                // Update role display in profile header if exists
                const roleDisplay = document.querySelector('.role-display');
                if (roleDisplay) {
                    roleDisplay.textContent = roleName;
                }
            }
            
            // Update mansione-related UI
            if (field === 'mansione_id') {
                const selectedOption = select.querySelector(`option[value="${value}"]`);
                const mansioneName = selectedOption ? selectedOption.textContent : 'Nessuna mansione';
                
                // Update mansione display if exists
                const mansioneDisplay = document.querySelector('.mansione-display');
                if (mansioneDisplay) {
                    mansioneDisplay.textContent = mansioneName;
                }
            }
        } catch (e) {
            window.SUGECO.Core.log('Error updating UI for role:', 'warn');
            if (window.SUGECO.Core.config.debug) {
                window.SUGECO.Core.log(e.message, 'error');
            }
        }
    }
};

// Initialize module when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.Autosave.init();
});
