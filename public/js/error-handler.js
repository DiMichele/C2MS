/**
 * SUGECO Error Handler - Global error management
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Error Handler module
window.SUGECO.ErrorHandler = {
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
        
        if (window.SUGECO_CONFIG?.DEBUG) {
            console.log('[SUGECO] Error handler initialized');
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
            if (window.SUGECO_CONFIG?.DEBUG) {
                console.log('[SUGECO] Promise rejection handled:', event.reason);
            }
        });
    },

    /**
     * Log error with context
     * @param {string} type - Error type
     * @param {Object} details - Error details
     */
    logError: function(type, details) {
        if (!window.SUGECO_CONFIG?.DEBUG) {
            return;
        }

        console.group(`[SUGECO] ${type}`);
        
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
        
        if (hasExtensionScripts && window.SUGECO_CONFIG?.DEBUG) {
            console.log('[SUGECO] Browser extensions detected - they might cause vendor.js errors');
        }
        
        return hasExtensionScripts;
    }
};

// Initialize error handler when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.ErrorHandler.init();
    window.SUGECO.ErrorHandler.detectProblematicExtensions();
});

// Initialize error handler immediately for early errors
window.SUGECO.ErrorHandler.init(); 