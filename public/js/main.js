/**
 * SUGECO - Main Application Entry Point
 * @version 1.0
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.SUGECO !== 'undefined') {
        initializeApplication();
    } else {
        setTimeout(initializeApplication, 100);
    }
});

function initializeApplication() {
    window.SUGECO.Core.log('Application initializing...');
    
    const pageInfo = window.SUGECO.Core.detectPageType();
    
    // Initialize modules if available
    const modules = [
        { name: 'Search', init: true },
        { name: 'Filters', init: true },
        { name: 'Militare', init: pageInfo.isMilitare },
        { name: 'CertificateTooltips', init: pageInfo.isCertificati },
        { name: 'Autosave', init: true },
        { name: 'Toast', init: true }
    ];
    
    modules.forEach(module => {
        if (module.init && window.SUGECO[module.name]) {
            try {
                window.SUGECO[module.name].init();
            } catch (e) {
                window.SUGECO.Core.log(`Error initializing ${module.name}: ${e.message}`, 'error');
            }
        }
    });
    
    window.SUGECO.Core.log('Application initialized');
}

// Global access for compatibility
window.initializeApplication = initializeApplication;
