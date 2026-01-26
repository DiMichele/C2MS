/**
 * SUGECO Core - Shared utilities and namespace
 */

// Create global namespace
window.SUGECO = window.SUGECO || {};

// Initialize core configuration
window.SUGECO.Core = {
    config: {
        get debounceDelay() { return window.SUGECO_CONFIG?.TIMING?.DEBOUNCE_DELAY || 300; },
        get saveDelay() { return window.SUGECO_CONFIG?.TIMING?.SAVE_DELAY || 1000; },
        get toastDuration() { return window.SUGECO_CONFIG?.TIMING?.TOAST_DURATION || 3000; },
        get debug() { return window.SUGECO_CONFIG?.DEBUG || false; }
    },
    
    /**
     * Conditional logging function
     * @param {string} message - Message to log
     * @param {string} level - Log level (log, warn, error)
     */
    log: function(message, level = 'log') {
        if (this.config.debug) {
            // Convert message to string if it's not already
            const messageStr = typeof message === 'string' ? message : String(message);
            
            // Ensure the level is valid
            const validLevels = ['log', 'warn', 'error', 'info', 'debug'];
            const levelStr = typeof level === 'string' ? level : 'log';
            const consoleMethod = validLevels.includes(levelStr) && typeof console[levelStr] === 'function' 
                ? levelStr 
                : 'log';
            
            console[consoleMethod](`[SUGECO] ${messageStr}`);
        }
    },
    
    /**
     * Initialize core functionality
     */
    init: function() {
        this.log('Core initialized');
        
        // Detect page type
        this.pageInfo = this.detectPageType();
        
        // Initialize components that should be on every page
        this.initToasts();
    },
    
    /**
     * Detect current page type based on URL
     * @returns {Object} Page type information
     */
    detectPageType: function() {
        const path = window.location.pathname;
        
        return {
            isMilitare: path.includes('militare') || path.includes('organigramma') || path === '/',
            isCertificati: path.includes('certificati') || path.includes('idoneita') || path.includes('corsi'),
            isDashboard: path === '/' || path.includes('dashboard'),
            path: path
        };
    },
    
    /**
     * Initialize toast notification system
     */
    initToasts: function() {
        // Gestito da toast-system.js per uniformità globale
    },
    
    /**
     * Create a debounced version of a function
     * @param {Function} func - Function to debounce
     * @param {number} wait - Debounce delay in ms
     * @returns {Function} Debounced function
     */
    debounce: function(func, wait) {
        let timeout;
        
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Get CSRF token from meta tag
     * @returns {string|null} CSRF token
     */
    getCsrfToken: function() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    },
    
    /**
     * Build URL with base path
     * @param {string} path - Relative path
     * @returns {string} Full URL with base path
     */
    buildUrl: function(path) {
        const baseUrl = window.SUGECO_CONFIG?.APP?.BASE_URL || '';
        return baseUrl + (path.startsWith('/') ? path : '/' + path);
    },
    
    /**
     * Get or create element by ID
     * @param {string} id - Element ID
     * @param {string} tagName - Tag name for creation if not exists
     * @param {Element} [parent=document.body] - Parent element for creation
     * @returns {Element} Element
     */
    getOrCreateElement: function(id, tagName, parent = document.body) {
        let element = document.getElementById(id);
        
        if (!element) {
            element = document.createElement(tagName);
            element.id = id;
            parent.appendChild(element);
        }
        
        return element;
    },
    
    /**
     * Format a date into Italian format
     * @param {string} dateString - Date string
     * @returns {string} Formatted date
     */
    formatDate: function(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return isNaN(date) ? '-' : date.toLocaleDateString('it-IT');
    },
    
    /**
     * Determine certificate status based on expiry date
     * @param {string} expiryDate - Expiry date string
     * @param {boolean} [isFemminile=false] - Whether to use feminine gender
     * @returns {Object} Status object with class and text
     */
    getCertificateStatus: function(expiryDate, isFemminile = false) {
        if (!expiryDate) return { 
            class: 'expired', 
            text: isFemminile ? 'Non presente' : 'Non presente' 
        };
        
        const now = new Date();
        const expiry = new Date(expiryDate);
        const diffDays = Math.ceil((expiry - now) / (1000 * 60 * 60 * 24));
        
        if (diffDays < 0) {
            return { 
                class: 'expired', 
                text: isFemminile ? 'Scaduta' : 'Scaduto' 
            };
        } else if (diffDays <= 30) {
            return { 
                class: 'expiring', 
                text: 'In scadenza' 
            };
        } else {
            return { 
                class: 'valid', 
                text: isFemminile ? 'Valida' : 'Valido' 
            };
        }
    }
};

// Initialize core when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.Core.init();
});
