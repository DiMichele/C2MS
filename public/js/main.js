/**
 * SUGECO: Sistema Unico di Gestione e Controllo
 * Main JavaScript file for application initialization
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Main application initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the main application
    if (typeof window.SUGECO !== 'undefined') {
        initializeApplication();
    } else {
        // Fallback if core is not loaded
        setTimeout(initializeApplication, 100);
    }
});

function initializeApplication() {
    window.SUGECO.Core.log('Application initializing...');
    
    // Initialize based on page type
    const pageInfo = window.SUGECO.Core.detectPageType();
    
    // Initialize search functionality
    if (typeof window.SUGECO.Search !== 'undefined') {
        window.SUGECO.Search.init();
    }
    
    // Initialize filters
    if (typeof window.SUGECO.Filters !== 'undefined') {
        window.SUGECO.Filters.init();
    }
    
    // Initialize militare-specific features
    if (pageInfo.isMilitare && typeof window.SUGECO.Militare !== 'undefined') {
        window.SUGECO.Militare.init();
    }
    
    // Initialize certificate tooltips for certificate pages
    if (pageInfo.isCertificati && typeof window.SUGECO.CertificateTooltips !== 'undefined') {
        window.SUGECO.CertificateTooltips.init();
    }
    
    // Initialize autosave functionality
    if (typeof window.SUGECO.Autosave !== 'undefined') {
        window.SUGECO.Autosave.init();
    }
    
    // Initialize toast system
    if (typeof window.SUGECO.Toast !== 'undefined') {
        window.SUGECO.Toast.init();
    }
    
    window.SUGECO.Core.log('Application initialized');
}

/**
 * Legacy compatibility function
 * @deprecated Use window.SUGECO.Core.detectPageType() instead
 */
function detectPageType() {
    const pageInfo = window.SUGECO.Core.detectPageType();
            window.SUGECO.Core.log('Page type detected: ' + pageInfo.path);
    return pageInfo;
}

/**
 * Initialize page-specific functionality
 * @deprecated Functionality moved to individual modules
 */
function initializePageSpecificFeatures() {
    // This function is kept for compatibility but functionality
    // has been moved to individual modules
    window.SUGECO.Core.log('Legacy initializePageSpecificFeatures called - functionality moved to modules');
}

// Export for global access (legacy compatibility)
window.initializeApplication = initializeApplication;
window.detectPageType = detectPageType;
