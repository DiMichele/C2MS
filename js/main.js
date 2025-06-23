/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Main JavaScript file for application initialization
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Main application initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the main application
    if (typeof window.C2MS !== 'undefined') {
        initializeApplication();
    } else {
        // Fallback if core is not loaded
        setTimeout(initializeApplication, 100);
    }
});

function initializeApplication() {
    window.C2MS.Core.log('Application initializing...');
    
    // Initialize based on page type
    const pageInfo = window.C2MS.Core.detectPageType();
    
    // Initialize search functionality
    if (typeof window.C2MS.Search !== 'undefined') {
        window.C2MS.Search.init();
    }
    
    // Initialize filters
    if (typeof window.C2MS.Filters !== 'undefined') {
        window.C2MS.Filters.init();
    }
    
    // Initialize militare-specific features
    if (pageInfo.isMilitare && typeof window.C2MS.Militare !== 'undefined') {
        window.C2MS.Militare.init();
    }
    
    // Initialize certificate tooltips for certificate pages
    if (pageInfo.isCertificati && typeof window.C2MS.CertificateTooltips !== 'undefined') {
        window.C2MS.CertificateTooltips.init();
    }
    
    // Initialize autosave functionality
    if (typeof window.C2MS.Autosave !== 'undefined') {
        window.C2MS.Autosave.init();
    }
    
    // Initialize toast system
    if (typeof window.C2MS.Toast !== 'undefined') {
        window.C2MS.Toast.init();
    }
    
    window.C2MS.Core.log('Application initialized');
}

/**
 * Legacy compatibility function
 * @deprecated Use window.C2MS.Core.detectPageType() instead
 */
function detectPageType() {
    const pageInfo = window.C2MS.Core.detectPageType();
            window.C2MS.Core.log('Page type detected: ' + pageInfo.path);
    return pageInfo;
}

/**
 * Initialize page-specific functionality
 * @deprecated Functionality moved to individual modules
 */
function initializePageSpecificFeatures() {
    // This function is kept for compatibility but functionality
    // has been moved to individual modules
    window.C2MS.Core.log('Legacy initializePageSpecificFeatures called - functionality moved to modules');
}

// Export for global access (legacy compatibility)
window.initializeApplication = initializeApplication;
window.detectPageType = detectPageType;
