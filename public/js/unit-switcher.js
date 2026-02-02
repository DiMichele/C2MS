/**
 * SUGECO - Unit Switcher (Floating Version)
 * 
 * Gestisce il cambio dell'unità organizzativa attiva.
 * Componente floating posizionato sotto la navbar.
 * 
 * Usa event delegation per evitare conflitti con Bootstrap e altri script.
 */

(function() {
    'use strict';

    var initialized = false;

    // Inizializza il switcher quando il DOM è pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initUnitSwitcher();
        });
    } else {
        // DOM già caricato
        initUnitSwitcher();
    }

    /**
     * Inizializza il componente unit switcher floating.
     * Usa event delegation sul document per massima compatibilità.
     */
    function initUnitSwitcher() {
        if (initialized) return;
        
        const toggle = document.getElementById('unitSwitcherToggle');
        const dropdown = document.getElementById('unitSwitcherDropdown');
        
        if (!toggle || !dropdown) {
            return;
        }

        initialized = true;

        // Event delegation: cattura click a livello document nella fase di capture
        document.addEventListener('click', handleGlobalClick, true);

        // Chiudi dropdown con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDropdown();
            }
        });
    }

    /**
     * Gestisce tutti i click a livello globale.
     * Usa event delegation per evitare conflitti con Bootstrap.
     */
    function handleGlobalClick(e) {
        const target = e.target;
        const floating = document.getElementById('unitSwitcherFloating');
        
        // Click sul toggle button o suoi figli
        const toggleBtn = target.closest('#unitSwitcherToggle');
        if (toggleBtn) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            toggleDropdown();
            return false;
        }
        
        // Click su un'opzione del dropdown
        const option = target.closest('.unit-option');
        if (option && option.closest('#unitSwitcherDropdown')) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const unitId = option.dataset.unitId;
            const unitName = option.dataset.unitName;
            
            if (unitId && !option.classList.contains('active')) {
                switchUnit(unitId, unitName);
            }
            
            closeDropdown();
            return false;
        }
        
        // Click fuori dal componente: chiudi dropdown
        if (floating && !floating.contains(target)) {
            closeDropdown();
        }
    }

    /**
     * Alterna lo stato del dropdown.
     */
    function toggleDropdown() {
        const toggle = document.getElementById('unitSwitcherToggle');
        const dropdown = document.getElementById('unitSwitcherDropdown');
        
        if (!toggle || !dropdown) return;
        
        const isOpen = dropdown.classList.contains('open');
        
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    }

    /**
     * Apre il dropdown.
     */
    function openDropdown() {
        const toggle = document.getElementById('unitSwitcherToggle');
        const dropdown = document.getElementById('unitSwitcherDropdown');
        
        if (!toggle || !dropdown) return;
        
        toggle.classList.add('open');
        dropdown.classList.add('open');
    }

    /**
     * Chiude il dropdown.
     */
    function closeDropdown() {
        const toggle = document.getElementById('unitSwitcherToggle');
        const dropdown = document.getElementById('unitSwitcherDropdown');
        
        if (!toggle || !dropdown) return;
        
        toggle.classList.remove('open');
        dropdown.classList.remove('open');
    }

    /**
     * Cambia l'unità attiva.
     * 
     * @param {number} unitId - ID dell'unità da attivare
     * @param {string} unitName - Nome dell'unità (opzionale)
     */
    function switchUnit(unitId, unitName) {
        const toggle = document.getElementById('unitSwitcherToggle');
        
        // Disabilita il toggle durante il caricamento
        if (toggle) {
            toggle.style.pointerEvents = 'none';
            toggle.style.opacity = '0.7';
        }

        // Mostra indicatore di caricamento
        showLoadingIndicator();

        // Ottieni il token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            console.error('CSRF token not found');
            hideLoadingIndicator();
            if (toggle) {
                toggle.style.pointerEvents = '';
                toggle.style.opacity = '';
            }
            return;
        }

        // Usa l'URL della route fornito da Laravel
        const apiUrl = window.SUGECO_API?.unitSwitch || '/unit/switch';

        // Effettua la richiesta di switch
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ unit_id: parseInt(unitId) }),
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Errore nel cambio unità');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Aggiorna il nome dell'unità corrente nel toggle
                const currentUnitSpan = document.getElementById('currentUnitName');
                if (currentUnitSpan && unitName) {
                    currentUnitSpan.textContent = unitName;
                }

                // Mostra messaggio di successo
                if (typeof showToast === 'function') {
                    showToast('Unità cambiata: ' + data.unit.name, 'success');
                }

                // Ricarica la pagina per applicare i nuovi filtri
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            } else {
                throw new Error(data.message || 'Errore nel cambio unità');
            }
        })
        .catch(error => {
            console.error('Errore switch unità:', error);
            
            // Mostra errore
            if (typeof showToast === 'function') {
                showToast('Errore nel cambio unità: ' + error.message, 'error');
            } else {
                alert('Errore nel cambio unità: ' + error.message);
            }

            // Ripristina il toggle
            hideLoadingIndicator();
            if (toggle) {
                toggle.style.pointerEvents = '';
                toggle.style.opacity = '';
            }
        });
    }

    /**
     * Mostra un indicatore di caricamento.
     */
    function showLoadingIndicator() {
        // Crea overlay di caricamento se non esiste
        let overlay = document.getElementById('unit-switch-loading');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'unit-switch-loading';
            overlay.className = 'unit-switch-loading-overlay';
            overlay.innerHTML = `
                <div class="unit-switch-loading-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Cambio unità in corso...</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }

        overlay.style.display = 'flex';
    }

    /**
     * Nasconde l'indicatore di caricamento.
     */
    function hideLoadingIndicator() {
        const overlay = document.getElementById('unit-switch-loading');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Forza la reinizializzazione del componente.
     */
    function reinit() {
        initialized = false;
        initUnitSwitcher();
    }

    // Esponi funzioni globalmente
    window.SUGECOUnitSwitcher = {
        switchUnit: switchUnit,
        init: initUnitSwitcher,
        reinit: reinit,
        open: openDropdown,
        close: closeDropdown,
        toggle: toggleDropdown,
    };

})();
