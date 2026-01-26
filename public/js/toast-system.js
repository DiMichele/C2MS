/**
 * SUGECO Toast - Notification system
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Toast system module
window.SUGECO.Toast = {
    container: null,

    /**
     * Initialize toast system
     */
    init: function() {
        this.createContainer();
        this.setupGlobalToastFunction();
        window.SUGECO.Core?.log('Toast System initialized');
    },

    /**
     * Create toast container
     */
    createContainer: function() {
        if (!this.container) {
            this.container = document.querySelector('.toast-container');
        }

        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },

    /**
     * Setup global toast function
     */
    setupGlobalToastFunction: function() {
        window.showToast = (...args) => {
            const normalized = this.normalizeArgs(args);
            this.show(normalized.message, normalized.type, normalized.duration);
        };
    },

    /**
     * Normalize arguments to support legacy signatures
     */
    normalizeArgs: function(args) {
        const types = ['success', 'error', 'warning', 'info'];
        const [arg1, arg2, arg3] = args;

        // showToast('success', 'Messaggio')
        if (types.includes(arg1) && typeof arg2 === 'string') {
            return { message: arg2, type: arg1, duration: arg3 || 3000 };
        }

        // showToast('Titolo', 'Messaggio', 'error')
        if (typeof arg1 === 'string' && typeof arg2 === 'string' && types.includes(arg3)) {
            return { message: `${arg1}: ${arg2}`, type: arg3, duration: 3000 };
        }

        // showToast('Messaggio', 'success')
        if (typeof arg1 === 'string' && types.includes(arg2)) {
            return { message: arg1, type: arg2, duration: arg3 || 3000 };
        }

        // showToast('Messaggio')
        return { message: String(arg1 ?? ''), type: 'info', duration: 3000 };
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
        toast.className = `sugeco-toast sugeco-toast--${type}`;

        // Create toast content
        const icon = this.getIcon(type);
        toast.innerHTML = `
            <div class="sugeco-toast__content">
                <div class="sugeco-toast__icon">${icon}</div>
                <div class="sugeco-toast__message">${message}</div>
                <button class="sugeco-toast__close" type="button" aria-label="Chiudi">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        // Add to container
        this.container.appendChild(toast);

        // Show toast with animation
        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });

        // Setup close button
        const closeButton = toast.querySelector('.sugeco-toast__close');
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

        toast.classList.remove('is-visible');
        toast.classList.add('is-hiding');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 250);
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
    window.SUGECO.Toast.success(message);
};

window.showError = function(message) {
    window.SUGECO.Toast.error(message);
};

window.showWarning = function(message) {
    window.SUGECO.Toast.warning(message);
};

window.showInfo = function(message) {
    window.SUGECO.Toast.info(message);
};

// Auto-inizializzazione quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        window.SUGECO.Toast.init();
    });
} else {
    window.SUGECO.Toast.init();
}
