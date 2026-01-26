/**
 * SUGECO Config - Centralized application configuration
 */

// Global configuration object
window.SUGECO_CONFIG = {
    /**
     * Debug mode - set to false in production
     * Controls console logging and error reporting
     */
    DEBUG: false,
    
    /**
     * API endpoints configuration
     */
    API: {
        BASE_URL: null, // Will be set dynamically
        TIMEOUT: 30000, // 30 seconds
        RETRY_ATTEMPTS: 3
    },
    
    /**
     * Application base URL - Auto-detected
     */
    APP: {
        BASE_URL: null // Will be set dynamically
    },
    
    /**
     * UI timing configuration
     */
    TIMING: {
        DEBOUNCE_DELAY: 300,      // Debounce delay for search/filters
        SAVE_DELAY: 1000,         // Auto-save delay
        TOAST_DURATION: 3000,     // Toast notification duration
        ANIMATION_DURATION: 300,  // CSS animation duration
        TOOLTIP_DELAY: 500        // Tooltip show delay
    },
    
    /**
     * Pagination and limits
     */
    PAGINATION: {
        DEFAULT_PER_PAGE: 25,
        MAX_PER_PAGE: 100,
        SEARCH_LIMIT: 10
    },
    
    /**
     * File upload configuration
     */
    UPLOAD: {
        MAX_FILE_SIZE: 10 * 1024 * 1024, // 10MB
        ALLOWED_TYPES: ['pdf', 'jpg', 'jpeg', 'png'],
        CHUNK_SIZE: 1024 * 1024 // 1MB chunks for large files
    },
    
    /**
     * Cache configuration
     */
    CACHE: {
        DEFAULT_TTL: 300, // 5 minutes
        SEARCH_TTL: 60,   // 1 minute for search results
        USER_DATA_TTL: 900 // 15 minutes for user data
    },
    
    /**
     * Security settings
     */
    SECURITY: {
        CSRF_HEADER: 'X-CSRF-TOKEN',
        CONTENT_TYPE: 'application/json',
        ACCEPT: 'application/json'
    },
    
    /**
     * Feature flags
     */
    FEATURES: {
        ENABLE_AUTOSAVE: true,
        ENABLE_OFFLINE_MODE: false,
        ENABLE_REAL_TIME_UPDATES: false,
        ENABLE_ADVANCED_SEARCH: true,
        ENABLE_BULK_OPERATIONS: true
    },
    
    /**
     * Validation rules
     */
    VALIDATION: {
        MIN_SEARCH_LENGTH: 2,
        MAX_NOTE_LENGTH: 2000,
        PASSWORD_MIN_LENGTH: 8
    },
    
    /**
     * Keyboard shortcuts
     */
    SHORTCUTS: {
        SEARCH: 'Ctrl+K',
        SAVE: 'Ctrl+S',
        ESCAPE: 'Escape',
        HELP: 'F1'
    },
    
    /**
     * Date and time formats
     */
    FORMATS: {
        DATE: 'DD/MM/YYYY',
        DATETIME: 'DD/MM/YYYY HH:mm',
        TIME: 'HH:mm'
    },
    
    /**
     * Error messages
     */
    MESSAGES: {
        NETWORK_ERROR: 'Errore di connessione. Verifica la tua connessione internet.',
        PERMISSION_DENIED: 'Non hai i permessi necessari per questa operazione.',
        SESSION_EXPIRED: 'Errore di connessione. Ricarica la pagina.',
        GENERIC_ERROR: 'Si è verificato un errore imprevisto. Riprova più tardi.',
        SAVE_SUCCESS: 'Dati salvati con successo',
        DELETE_SUCCESS: 'Elemento eliminato con successo',
        UPDATE_SUCCESS: 'Aggiornamento completato con successo'
    }
};

/**
 * Environment detection
 * Debug mode disabled for production
 */
(function() {
    // Debug mode is permanently disabled
    window.SUGECO_CONFIG.DEBUG = false;
    
    // Remove any debug setting from localStorage
    localStorage.removeItem('c2ms_debug');
})();

/**
 * Configuration helper functions
 */
window.SUGECO_CONFIG.helpers = {
    /**
     * Get configuration value with dot notation
     * @param {string} path - Configuration path (e.g., 'TIMING.DEBOUNCE_DELAY')
     * @param {*} defaultValue - Default value if path not found
     * @returns {*} Configuration value
     */
    get: function(path, defaultValue = null) {
        const keys = path.split('.');
        let value = window.SUGECO_CONFIG;
        
        for (const key of keys) {
            if (value && typeof value === 'object' && key in value) {
                value = value[key];
            } else {
                return defaultValue;
            }
        }
        
        return value;
    },
    
    /**
     * Set configuration value with dot notation
     * @param {string} path - Configuration path
     * @param {*} value - Value to set
     */
    set: function(path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        let target = window.SUGECO_CONFIG;
        
        for (const key of keys) {
            if (!(key in target) || typeof target[key] !== 'object') {
                target[key] = {};
            }
            target = target[key];
        }
        
        target[lastKey] = value;
    },
    
    /**
     * Toggle debug mode
     * @param {boolean} enabled - Enable/disable debug mode
     */
    setDebug: function(enabled) {
        window.SUGECO_CONFIG.DEBUG = enabled;
        localStorage.setItem('c2ms_debug', enabled.toString());
    },
    
    /**
     * Get all configuration as JSON string
     * @returns {string} Configuration JSON
     */
    export: function() {
        return JSON.stringify(window.SUGECO_CONFIG, null, 2);
    },
    
    /**
     * Load configuration from JSON string
     * @param {string} configJson - Configuration JSON
     */
    import: function(configJson) {
        try {
            const config = JSON.parse(configJson);
            Object.assign(window.SUGECO_CONFIG, config);
        } catch (e) {
            // Errore silenzioso
        }
    }
};

/**
 * Auto-detect application base URL
 * This makes the application portable regardless of directory name
 */
(function() {
    // Get current path
    const currentPath = window.location.pathname;
    
    // Try to detect the base path by looking for common Laravel files/routes
    let basePath = '';
    
    // Check if we're in the root (e.g., /index.php or /)
    if (currentPath === '/' || currentPath === '/index.php') {
        basePath = '';
    } else {
        // Extract base path from current URL
        // Examples:
        // /SUGECO/ -> /SUGECO
        // /SUGECO/militare -> /SUGECO
        // /my-project/militare/1 -> /my-project
        
        const pathParts = currentPath.split('/').filter(part => part !== '');
        
        if (pathParts.length > 0) {
            // Check if first part looks like a Laravel route
            const firstPart = pathParts[0];
            const laravelRoutes = ['militare', 'certificati', 'organigramma', 'assenze', 'eventi', 'board', 'api'];
            
            if (laravelRoutes.includes(firstPart)) {
                // We're at root level (no subfolder)
                basePath = '';
            } else {
                // First part is likely the application folder
                basePath = '/' + firstPart;
            }
        }
    }
    
    // Set the detected base URLs
    window.SUGECO_CONFIG.APP.BASE_URL = basePath;
    window.SUGECO_CONFIG.API.BASE_URL = basePath + '/api';
    
    // Debug log
    if (window.SUGECO_CONFIG.DEBUG) {
        console.log('[SUGECO] Auto-detected base URL:', basePath);
        console.log('[SUGECO] API base URL:', window.SUGECO_CONFIG.API.BASE_URL);
    }
})();

