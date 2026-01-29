/**
 * SUGECO Confirm - Sistema unificato per dialoghi di conferma
 * 
 * Fornisce un sistema centralizzato per mostrare dialoghi di conferma
 * con stili e comportamenti uniformi in tutta l'applicazione.
 * 
 * @version 1.0.0
 * @author SUGECO Team
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Confirm system module
window.SUGECO.Confirm = {
    modalElement: null,
    bsModal: null,
    currentResolve: null,
    isInitialized: false,

    /**
     * Initialize confirm system
     */
    init: function() {
        if (this.isInitialized) return;
        
        this.createModal();
        this.setupEventListeners();
        this.setupGlobalFunctions();
        this.isInitialized = true;
        
        window.SUGECO.Core?.log('Confirm System initialized');
    },

    /**
     * Create the modal element
     */
    createModal: function() {
        const modalId = 'sugecoConfirmModal';
        this.modalElement = document.getElementById(modalId);
        
        if (!this.modalElement) {
            this.modalElement = document.createElement('div');
            this.modalElement.id = modalId;
            this.modalElement.className = 'modal fade sugeco-confirm-modal';
            this.modalElement.setAttribute('tabindex', '-1');
            this.modalElement.setAttribute('aria-hidden', 'true');
            this.modalElement.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content sugeco-confirm-content">
                        <div class="modal-body text-center py-4">
                            <div class="sugeco-confirm-icon mb-3">
                                <i class="fas fa-question-circle fa-3x"></i>
                            </div>
                            <h5 class="sugeco-confirm-title mb-2"></h5>
                            <p class="sugeco-confirm-message mb-0"></p>
                        </div>
                        <div class="modal-footer justify-content-center border-0 pt-0 gap-2">
                            <button type="button" class="btn sugeco-confirm-cancel" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>
                                <span class="btn-text">Annulla</span>
                            </button>
                            <button type="button" class="btn sugeco-confirm-ok">
                                <i class="fas fa-check me-1"></i>
                                <span class="btn-text">Conferma</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(this.modalElement);
        }
        
        // Initialize Bootstrap Modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            this.bsModal = new bootstrap.Modal(this.modalElement, {
                backdrop: 'static',
                keyboard: true
            });
        }
    },

    /**
     * Setup event listeners
     */
    setupEventListeners: function() {
        const btnOk = this.modalElement.querySelector('.sugeco-confirm-ok');
        const btnCancel = this.modalElement.querySelector('.sugeco-confirm-cancel');
        
        btnOk.addEventListener('click', () => {
            this.resolveAndHide(true);
        });
        
        btnCancel.addEventListener('click', () => {
            this.resolveAndHide(false);
        });
        
        // Handle modal hidden event (backdrop click, escape key)
        this.modalElement.addEventListener('hidden.bs.modal', () => {
            if (this.currentResolve) {
                this.currentResolve(false);
                this.currentResolve = null;
            }
        });
        
        // Handle escape key
        this.modalElement.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.resolveAndHide(false);
            }
        });
    },

    /**
     * Setup global convenience functions
     */
    setupGlobalFunctions: function() {
        // Main global function
        window.confirmAction = (message, options) => this.show({ message, ...options });
        
        // Type-specific shortcuts
        window.confirmDelete = (message) => this.delete(message);
        window.confirmSave = (message) => this.save(message);
        window.confirmWarning = (message, confirmText) => this.warning(message, confirmText);
        
        // Legacy support for confermaAzione
        window.confermaAzione = (messaggio, testoConferma = 'Conferma') => {
            return this.show({ message: messaggio, confirmText: testoConferma });
        };
    },

    /**
     * Resolve current promise and hide modal
     * @param {boolean} result - The result to resolve with
     */
    resolveAndHide: function(result) {
        if (this.currentResolve) {
            const resolve = this.currentResolve;
            this.currentResolve = null;
            this.bsModal?.hide();
            resolve(result);
        }
    },

    /**
     * Show a confirmation dialog
     * @param {Object} options - Configuration options
     * @param {string} options.message - The message to display
     * @param {string} [options.title] - Optional title
     * @param {string} [options.type='default'] - Type: 'default', 'danger', 'warning', 'success', 'info'
     * @param {string} [options.confirmText='Conferma'] - Text for confirm button
     * @param {string} [options.cancelText='Annulla'] - Text for cancel button
     * @param {string} [options.icon] - Custom icon class (FontAwesome)
     * @returns {Promise<boolean>} - Resolves to true if confirmed, false otherwise
     */
    show: function(options = {}) {
        return new Promise((resolve) => {
            // Ensure modal is initialized
            if (!this.isInitialized) {
                this.init();
            }
            
            // Default options
            const config = {
                message: options.message || 'Sei sicuro di voler procedere?',
                title: options.title || '',
                type: options.type || 'default',
                confirmText: options.confirmText || 'Conferma',
                cancelText: options.cancelText || 'Annulla',
                icon: options.icon || null
            };
            
            // Update modal content
            this.updateModal(config);
            
            // Store resolve function
            this.currentResolve = resolve;
            
            // Show modal
            this.bsModal?.show();
            
            // Focus confirm button for accessibility
            setTimeout(() => {
                this.modalElement.querySelector('.sugeco-confirm-ok')?.focus();
            }, 150);
        });
    },

    /**
     * Update modal with configuration
     * @param {Object} config - Configuration object
     */
    updateModal: function(config) {
        const iconEl = this.modalElement.querySelector('.sugeco-confirm-icon i');
        const titleEl = this.modalElement.querySelector('.sugeco-confirm-title');
        const messageEl = this.modalElement.querySelector('.sugeco-confirm-message');
        const btnOk = this.modalElement.querySelector('.sugeco-confirm-ok');
        const btnCancel = this.modalElement.querySelector('.sugeco-confirm-cancel');
        const content = this.modalElement.querySelector('.sugeco-confirm-content');
        
        // Get type configuration
        const typeConfig = this.getTypeConfig(config.type);
        
        // Update icon
        iconEl.className = config.icon || typeConfig.icon;
        this.modalElement.querySelector('.sugeco-confirm-icon').className = 
            `sugeco-confirm-icon mb-3 text-${typeConfig.color}`;
        
        // Update title (hide if empty)
        if (config.title) {
            titleEl.textContent = config.title;
            titleEl.style.display = 'block';
        } else {
            titleEl.style.display = 'none';
        }
        
        // Update message
        messageEl.textContent = config.message;
        
        // Update buttons
        btnOk.querySelector('.btn-text').textContent = config.confirmText;
        btnCancel.querySelector('.btn-text').textContent = config.cancelText;
        
        // Update button styles based on type
        btnOk.className = `btn sugeco-confirm-ok btn-${typeConfig.buttonClass}`;
        btnCancel.className = `btn sugeco-confirm-cancel btn-outline-secondary`;
        
        // Update content border
        content.setAttribute('data-type', config.type);
    },

    /**
     * Get configuration for a specific type
     * @param {string} type - Type name
     * @returns {Object} Type configuration
     */
    getTypeConfig: function(type) {
        const types = {
            default: {
                icon: 'fas fa-question-circle fa-3x',
                color: 'primary',
                buttonClass: 'primary'
            },
            danger: {
                icon: 'fas fa-exclamation-triangle fa-3x',
                color: 'danger',
                buttonClass: 'danger'
            },
            warning: {
                icon: 'fas fa-exclamation-circle fa-3x',
                color: 'warning',
                buttonClass: 'warning'
            },
            success: {
                icon: 'fas fa-check-circle fa-3x',
                color: 'success',
                buttonClass: 'success'
            },
            info: {
                icon: 'fas fa-info-circle fa-3x',
                color: 'info',
                buttonClass: 'info'
            }
        };
        
        return types[type] || types.default;
    },

    /**
     * Show delete confirmation dialog
     * @param {string} [message] - Custom message
     * @returns {Promise<boolean>}
     */
    delete: function(message) {
        return this.show({
            message: message || 'Sei sicuro di voler eliminare questo elemento?',
            title: 'Conferma eliminazione',
            type: 'danger',
            confirmText: 'Elimina',
            icon: 'fas fa-trash-alt fa-3x'
        });
    },

    /**
     * Show save confirmation dialog
     * @param {string} [message] - Custom message
     * @returns {Promise<boolean>}
     */
    save: function(message) {
        return this.show({
            message: message || 'Salvare le modifiche effettuate?',
            title: 'Conferma salvataggio',
            type: 'success',
            confirmText: 'Salva',
            icon: 'fas fa-save fa-3x'
        });
    },

    /**
     * Show warning confirmation dialog
     * @param {string} message - Warning message
     * @param {string} [confirmText='Procedi'] - Confirm button text
     * @returns {Promise<boolean>}
     */
    warning: function(message, confirmText = 'Procedi') {
        return this.show({
            message: message,
            title: 'Attenzione',
            type: 'warning',
            confirmText: confirmText
        });
    },

    /**
     * Show action confirmation dialog (generic)
     * @param {string} message - Message
     * @param {string} [confirmText='Conferma'] - Confirm button text
     * @returns {Promise<boolean>}
     */
    action: function(message, confirmText = 'Conferma') {
        return this.show({
            message: message,
            confirmText: confirmText
        });
    }
};

// Auto-inizializzazione quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.SUGECO.Confirm.init();
    });
} else {
    window.SUGECO.Confirm.init();
}
