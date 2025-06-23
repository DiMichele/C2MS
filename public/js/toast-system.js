/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Toast notification system
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Ensure C2MS namespace exists
window.C2MS = window.C2MS || {};

// Toast system module
window.C2MS.Toast = {
    container: null,
    
    /**
     * Initialize toast system
     */
    init: function() {
        this.createContainer();
        this.setupGlobalToastFunction();
        window.C2MS.Core.log('Toast System initialized');
    },
    
    /**
     * Create toast container
     */
    createContainer: function() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    
    /**
     * Setup global toast function
     */
    setupGlobalToastFunction: function() {
        window.showToast = (message, type = 'info', duration = 3000) => {
            this.show(message, type, duration);
        };
    },
    
    /**
     * Show a toast notification
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, error, warning, info)
     * @param {number} duration - Duration in milliseconds
     */
    show: function(message, type = 'info', duration = 3000) {
        this.createContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        // Create toast content
        const icon = this.getIcon(type);
        toast.innerHTML = `
            <div class="toast-content">
                ${icon}
                <span class="toast-message">${message}</span>
            </div>
            <button class="toast-close" type="button">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        // Add to container
        this.container.appendChild(toast);
        
        // Show toast with animation
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);
        
        // Setup close button
        const closeButton = toast.querySelector('.toast-close');
        closeButton.addEventListener('click', () => {
            this.hide(toast);
        });
        
        // Auto-hide after duration
        setTimeout(() => {
            this.hide(toast);
        }, duration);
        
        return toast;
    },
    
    /**
     * Hide a toast
     * @param {Element} toast - Toast element to hide
     */
    hide: function(toast) {
        if (!toast || !toast.parentNode) return;
        
        toast.classList.remove('show');
        toast.classList.add('hiding');
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    },
    
    /**
     * Get icon for toast type
     * @param {string} type - Toast type
     * @returns {string} Icon HTML
     */
    getIcon: function(type) {
        const icons = {
            success: '<i class="fas fa-check-circle"></i>',
            error: '<i class="fas fa-exclamation-circle"></i>',
            warning: '<i class="fas fa-exclamation-triangle"></i>',
            info: '<i class="fas fa-info-circle"></i>'
        };
        
        return icons[type] || icons.info;
    },
    
    /**
     * Show success toast
     * @param {string} message - Success message
     */
    success: function(message) {
        this.show(message, 'success');
    },
    
    /**
     * Show error toast
     * @param {string} message - Error message
     */
    error: function(message) {
        this.show(message, 'error', 5000); // Longer duration for errors
    },
    
    /**
     * Show warning toast
     * @param {string} message - Warning message
     */
    warning: function(message) {
        this.show(message, 'warning', 4000);
    },
    
    /**
     * Show info toast
     * @param {string} message - Info message
     */
    info: function(message) {
        this.show(message, 'info');
    },
    
    /**
     * Clear all toasts
     */
    clearAll: function() {
        if (this.container) {
            const toasts = this.container.querySelectorAll('.toast');
            toasts.forEach(toast => {
                this.hide(toast);
            });
        }
    }
};

// Alias globali per facilità d'uso
window.showSuccess = function(message) {
    window.C2MS.Toast.success(message);
};

window.showError = function(message) {
    window.C2MS.Toast.error(message);
};

window.showWarning = function(message) {
    window.C2MS.Toast.warning(message);
};

window.showInfo = function(message) {
    window.C2MS.Toast.info(message);
};

// Auto-inizializzazione quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
}); 
