/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Error handler for common JavaScript errors
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Ensure C2MS namespace exists
window.C2MS = window.C2MS || {};

// Error Handler module
window.C2MS.ErrorHandler = {
    initialized: false,
    
    /**
     * Initialize error handling
     */
    init: function() {
        // Prevent double initialization
        if (this.initialized) {
            return;
        }
        
        this.setupGlobalErrorHandlers();
        this.handleVendorJSErrors();
        this.setupUnhandledPromiseRejection();
        
        this.initialized = true;
        
        if (window.C2MS_CONFIG?.DEBUG) {
            console.log('[C2MS] Error handler initialized');
        }
    },

    /**
     * Setup global error handlers
     */
    setupGlobalErrorHandlers: function() {
        // Global error handler
        window.onerror = (message, source, lineno, colno, error) => {
            this.logError('Global Error', {
                message: message,
                source: source,
                line: lineno,
                column: colno,
                error: error
            });
            
            // Don't prevent default error handling
            return false;
        };
    },

    /**
     * Handle vendor.js specific errors
     */
    handleVendorJSErrors: function() {
        // Listen for unhandled promise rejections (common with vendor.js)
        window.addEventListener('unhandledrejection', (event) => {
            const error = event.reason;
            const errorMessage = error?.message || 'Unknown error';
            
            // Check if it's the specific vendor.js error
            if (errorMessage.includes('listener indicated an asynchronous response') ||
                errorMessage.includes('message channel closed') ||
                (error?.stack && error.stack.includes('vendor.js'))) {
                
                // Log the error but prevent it from appearing in console
                this.logError('Vendor.js Error (suppressed)', {
                    message: errorMessage,
                    type: 'Promise rejection',
                    suppressed: true,
                    note: 'This error is commonly caused by browser extensions and does not affect functionality'
                });
                
                // Prevent the error from showing in console
                event.preventDefault();
                return;
            }
            
            // Log other promise rejections normally
            this.logError('Unhandled Promise Rejection', {
                message: errorMessage,
                error: error
            });
        });
    },

    /**
     * Setup unhandled promise rejection handler
     */
    setupUnhandledPromiseRejection: function() {
        window.addEventListener('rejectionhandled', (event) => {
            // This fires when a promise rejection is handled after being unhandled
            if (window.C2MS_CONFIG?.DEBUG) {
                console.log('[C2MS] Promise rejection handled:', event.reason);
            }
        });
    },

    /**
     * Log error with context
     * @param {string} type - Error type
     * @param {Object} details - Error details
     */
    logError: function(type, details) {
        if (!window.C2MS_CONFIG?.DEBUG) {
            return;
        }

        console.group(`[C2MS] ${type}`);
        
        if (details.suppressed) {
            console.log('This error has been suppressed to avoid console spam');
        }
        
        console.log('Details:', details);
        console.log('Timestamp:', new Date().toISOString());
        console.log('User Agent:', navigator.userAgent);
        console.log('URL:', window.location.href);
        
        console.groupEnd();
    },

    /**
     * Handle fetch errors gracefully
     * @param {Response} response - Fetch response
     * @returns {Response} - The same response
     */
    handleFetchError: function(response) {
        if (!response.ok) {
            this.logError('Fetch Error', {
                status: response.status,
                statusText: response.statusText,
                url: response.url
            });
        }
        return response;
    },

    /**
     * Wrap fetch to handle errors automatically
     */
    wrapFetch: function() {
        const originalFetch = window.fetch;
        
        window.fetch = (...args) => {
            return originalFetch(...args)
                .then(response => this.handleFetchError(response))
                .catch(error => {
                    this.logError('Network Error', {
                        message: error.message,
                        error: error,
                        url: args[0]
                    });
                    throw error;
                });
        };
    },

    /**
     * Show user-friendly error message
     * @param {string} message - Error message to show
     */
    showUserError: function(message) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error');
        } else {
            // Fallback to alert if toast is not available
            alert(message);
        }
    },

    /**
     * Check if browser extensions might be causing issues
     * @returns {boolean} - True if extensions detected
     */
    detectProblematicExtensions: function() {
        // Check for common extension indicators
        const indicators = [
            'chrome-extension:',
            'moz-extension:',
            '__firefox__',
            '__chrome__'
        ];
        
        const scripts = Array.from(document.scripts);
        const hasExtensionScripts = scripts.some(script => 
            indicators.some(indicator => script.src.includes(indicator))
        );
        
        if (hasExtensionScripts && window.C2MS_CONFIG?.DEBUG) {
            console.log('[C2MS] Browser extensions detected - they might cause vendor.js errors');
        }
        
        return hasExtensionScripts;
    }
};

// Initialize error handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.C2MS.ErrorHandler.init();
    window.C2MS.ErrorHandler.detectProblematicExtensions();
});

// Initialize error handler immediately for early errors
window.C2MS.ErrorHandler.init(); 