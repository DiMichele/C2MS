/**
 * C2MS: Gestione e Controllo Digitale a Supporto del Comando
 * Certificate Tooltips Module - Handles certificate details modal/tooltips
 * 
 * @version 1.0
 * @author Michele Di Gennaro
 */

// Define certificate tooltips namespace
window.C2MS = window.C2MS || {};
window.C2MS.CertificateTooltips = {
    // DOM elements cache
    elements: {
        modalContainer: null,
        modalSubtitle: null,
        modalContent: null,
        modalGrado: null,
        modalNome: null
    },
    
    // Configuration
    config: {
        nullValue: "N/D",
        allowedFields: [
            'Stato', 
            'Data caricamento', 
            'Data scadenza', 
            'Giorni rimanenti', 
            'File'
        ]
    },
    
    // State tracking
    previouslyFocusedElement: null,
    
    /**
     * Initialize certificate tooltips
     */
    init: function() {
        window.C2MS.Core.log('Certificate Tooltips module initialized');
        
        // Initialize modal container
        this.initModalContainer();
        
        // Setup certificate tooltips
        this.setupCertificateModals();
        
        // Initialize note tooltips
        this.initNoteTooltips();
    },
    
    /**
     * Create and initialize the modal container
     */
    initModalContainer: function() {
        // Check if modal already exists
        if (document.getElementById('cert-modal-container')) {
            this.elements.modalContainer = document.getElementById('cert-modal-container');
            this.cacheElements();
            return;
        }
        
        // Create modal HTML
        const modalHTML = `
            <div class="cert-modal">
                <div class="cert-modal-header">
                    <h3 class="cert-modal-subtitle"></h3>
                    <button class="cert-modal-close" aria-label="Chiudi">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="cert-modal-militare">
                    <div class="cert-modal-militare-info">
                        <span class="cert-modal-grado"></span>
                        <span class="cert-modal-nome"></span>
                    </div>
                </div>
                <div class="cert-modal-content"></div>
            </div>
        `;
        
        // Create container
        this.elements.modalContainer = document.createElement('div');
        this.elements.modalContainer.id = 'cert-modal-container';
        this.elements.modalContainer.className = 'cert-modal-container';
        this.elements.modalContainer.innerHTML = modalHTML;
        this.elements.modalContainer.setAttribute('role', 'dialog');
        this.elements.modalContainer.setAttribute('aria-modal', 'true');
        this.elements.modalContainer.setAttribute('aria-hidden', 'true');
        document.body.appendChild(this.elements.modalContainer);
        
        // Cache elements
        this.cacheElements();
        
        // Setup event listeners
        this.setupEventListeners();
    },
    
    /**
     * Cache DOM elements for better performance
     */
    cacheElements: function() {
        this.elements.modalSubtitle = this.elements.modalContainer.querySelector('.cert-modal-subtitle');
        this.elements.modalContent = this.elements.modalContainer.querySelector('.cert-modal-content');
        this.elements.modalGrado = this.elements.modalContainer.querySelector('.cert-modal-grado');
        this.elements.modalNome = this.elements.modalContainer.querySelector('.cert-modal-nome');
    },
    
    /**
     * Setup event listeners for the modal
     */
    setupEventListeners: function() {
        // Close button
        const closeBtn = this.elements.modalContainer.querySelector('.cert-modal-close');
        closeBtn.addEventListener('click', () => this.hideModal());
        
        // Click outside modal
        this.elements.modalContainer.addEventListener('click', (e) => {
            if (e.target === this.elements.modalContainer) {
                this.hideModal();
            }
        });
        
        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.elements.modalContainer.classList.contains('active')) {
                this.hideModal();
            }
        });
    },
    
    /**
     * Setup certificate modals
     */
    setupCertificateModals: function() {
        // Event delegation for all certificates
        document.addEventListener('click', (e) => {
            // Check if target is a valid element
            if (!e.target || typeof e.target.closest !== 'function') return;
            
            // Find closest trigger
            const triggerElement = e.target.closest('.cert-badge, .certificate-cell, .cert-status');
            if (!triggerElement) return;
            
            // Check for tooltip
            const tooltip = triggerElement.querySelector('.cert-tooltip, .certificate-tooltip');
            if (!tooltip) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            // Setup trigger
            this.setupTrigger(triggerElement);
            
            // Get certificate info
            const certType = this.getCertificateType(triggerElement);
            const militareInfo = this.getMilitareInfo(triggerElement);
            
            // Extract data from tooltip
            const tooltipData = this.extractTooltipData(tooltip);
            
            // Format data to HTML
            const content = this.formatDataToHTML(tooltipData);
            
            // Show modal
            this.showModal(certType, content, militareInfo.grado, militareInfo.nome);
        });
        
        // Initialize all triggers
        this.initializeTriggers();
        
        // Setup certificate action buttons to prevent modal opening
        this.setupCertificateActions();
        
        // Setup hover prevention for certificate actions
        this.setupHoverPrevention();
    },
    
    /**
     * Configure an element as a trigger
     * @param {HTMLElement} element - Element to set up as trigger
     */
    setupTrigger: function(element) {
        if (!element.classList.contains('cert-modal-trigger')) {
            element.classList.add('cert-modal-trigger');
            element.setAttribute('tabindex', '0');
            
            // Ensure element has position: relative
            element.style.position = 'relative';
            
            // Remove title attribute to avoid native tooltip
            if (element.hasAttribute('title')) {
                element.removeAttribute('title');
            }
            
            // Add role for accessibility
            if (!element.hasAttribute('role')) {
                element.setAttribute('role', 'button');
            }
            
            // Prevent outline on mousedown
            element.addEventListener('mousedown', function(e) {
                e.preventDefault();
                this.style.outline = 'none';
            });
        }
    },
    
    /**
     * Initialize all triggers in the page
     */
    initializeTriggers: function() {
        const certElements = document.querySelectorAll('.cert-badge, .certificate-cell, .cert-status');
        
        certElements.forEach(element => {
            const tooltip = element.querySelector('.cert-tooltip, .certificate-tooltip');
            if (!tooltip) return;
            
            // Set up as trigger
            this.setupTrigger(element);
            
            // Keyboard accessibility
            element.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    e.target.click();
                }
            });
            
            // Hide native tooltip
            tooltip.style.display = 'none';
        });
    },
    
    /**
     * Setup certificate action buttons to prevent modal opening
     */
    setupCertificateActions: function() {
        // Event delegation for certificate action buttons
        document.addEventListener('click', (e) => {
            // Check if target is a valid element
            if (!e.target || typeof e.target.closest !== 'function') return;
            
            // Check if click is on a certificate action button or its children
            const actionButton = e.target.closest('.cert-actions a, .cert-actions button');
            if (actionButton) {
                // Stop propagation to prevent cert-badge click handler from firing
                e.stopPropagation();
                // Allow the default action (navigation to edit/create page)
                return true;
            }
            
            // Check if click is on certificate action container
            const actionContainer = e.target.closest('.cert-actions');
            if (actionContainer) {
                // Stop propagation to prevent cert-badge click handler from firing
                e.stopPropagation();
                return false;
            }
        }, true); // Use capture phase to ensure this runs before other handlers
    },
    
    /**
     * Setup hover prevention for certificate action buttons
     */
    setupHoverPrevention: function() {
        // Keep button tooltips but ensure they don't conflict with badge tooltips
        // (We'll handle this with CSS and event management instead of removing titles)
        
        // Remove title attribute from cert-badge elements to prevent conflicting tooltips
        document.querySelectorAll('.cert-badge[title]').forEach(badge => {
            badge.removeAttribute('title');
        });
        
        // Hide tooltips when hovering over action buttons or their children
        document.addEventListener('mouseenter', (e) => {
            // Check if target is a valid element
            if (!e.target || typeof e.target.closest !== 'function') return;
            
            // Check for any element inside cert-actions (buttons, icons, etc.)
            const actionElement = e.target.closest('.cert-actions, .cert-actions *, .cert-actions a, .cert-actions button');
            if (actionElement) {
                const certBadge = e.target.closest('.cert-badge');
                if (certBadge) {
                    const tooltip = certBadge.querySelector('.cert-tooltip');
                    if (tooltip) {
                        tooltip.style.opacity = '0 !important';
                        tooltip.style.pointerEvents = 'none !important';
                        tooltip.style.height = '0 !important';
                        tooltip.style.visibility = 'hidden !important';
                        // Add a class to track this state
                        tooltip.classList.add('force-hidden');
                    }
                }
            }
        }, true);
        
        // Restore tooltip behavior when leaving action buttons
        document.addEventListener('mouseleave', (e) => {
            // Check if target is a valid element
            if (!e.target || typeof e.target.closest !== 'function') return;
            
            const actionElement = e.target.closest('.cert-actions, .cert-actions *, .cert-actions a, .cert-actions button');
            if (actionElement) {
                const certBadge = e.target.closest('.cert-badge');
                if (certBadge) {
                    const tooltip = certBadge.querySelector('.cert-tooltip');
                    if (tooltip && tooltip.classList.contains('force-hidden')) {
                        // Small delay to check if we're still in the badge
                        setTimeout(() => {
                            // Check if mouse is still in cert-badge but not in any action element
                            const isStillInBadge = certBadge.matches(':hover');
                            const isInActions = certBadge.querySelector('.cert-actions:hover, .cert-actions *:hover');
                            
                            if (!isInActions) {
                                // Reset styles to let CSS hover rules take effect
                                tooltip.style.opacity = '';
                                tooltip.style.pointerEvents = '';
                                tooltip.style.height = '';
                                tooltip.style.visibility = '';
                                tooltip.classList.remove('force-hidden');
                            }
                        }, 10);
                    }
                }
            }
        }, true);
        
        // Additional check: continuously monitor cert-badge hover state
        document.addEventListener('mouseover', (e) => {
            // Check if target is a valid element
            if (!e.target || typeof e.target.closest !== 'function') return;
            
            const certBadge = e.target.closest('.cert-badge');
            if (certBadge) {
                const tooltip = certBadge.querySelector('.cert-tooltip');
                const isInActions = certBadge.querySelector('.cert-actions:hover, .cert-actions *:hover');
                
                if (tooltip && isInActions) {
                    tooltip.style.opacity = '0 !important';
                    tooltip.style.pointerEvents = 'none !important';
                    tooltip.style.height = '0 !important';
                    tooltip.style.visibility = 'hidden !important';
                    tooltip.classList.add('force-hidden');
                }
            }
        });
    },
    
    /**
     * Extract tooltip data from the original tooltip
     * @param {HTMLElement} tooltip - Original tooltip element
     * @returns {Object} Extracted data
     */
    extractTooltipData: function(tooltip) {
        const data = {};
        
        try {
            // Handle different tooltip formats
            const rows = tooltip.querySelectorAll('.tooltip-row, .certificate-tooltip-row');
            
            if (rows && rows.length > 0) {
                // Format 1: row structure
                rows.forEach(row => {
                    const label = row.querySelector('.tooltip-label, .certificate-tooltip-label');
                    const value = row.querySelector('.tooltip-value, .certificate-tooltip-value');
                    
                    if (label && value) {
                        const fieldName = label.textContent.trim().replace(':', '');
                        data[fieldName] = value.textContent.trim();
                    }
                });
            } else {
                // Format 2: separate elements
                this.config.allowedFields.forEach(field => {
                    const selector = `.tooltip-${field.toLowerCase().replace(/\s+/g, '')}, .certificate-tooltip-${field.toLowerCase().replace(/\s+/g, '')}`;
                    const element = tooltip.querySelector(selector);
                    if (element) {
                        data[field] = element.textContent.trim();
                    }
                });
            }
            
            // If no data found, try extracting from HTML content
            if (Object.keys(data).length === 0) {
                const html = tooltip.innerHTML;
                this.config.allowedFields.forEach(field => {
                    // Look for patterns like "Status: Valid" or "Status - Valid"
                    const regex = new RegExp(`${field}[:\\s-]+([^<\\n]+)`, 'i');
                    const match = html.match(regex);
                    if (match && match[1]) {
                        data[field] = match[1].trim();
                    }
                });
            }
        } catch (error) {
            window.C2MS.Core.log('Error extracting tooltip data:', 'error');
            if (window.C2MS.Core.config.debug) {
                window.C2MS.Core.log(error.message, 'error');
            }
        }
        
        return data;
    },
    
    /**
     * Get certificate type from element
     * @param {HTMLElement} element - Trigger element
     * @returns {string} Certificate type
     */
    getCertificateType: function(element) {
        // Check data attribute
        if (element.dataset.certType) {
            return element.dataset.certType;
        }
        
        // Fallback: look in table column header
        try {
            const cell = element.closest('td');
            if (cell) {
                const colIndex = Array.from(cell.parentNode.children).indexOf(cell);
                const table = element.closest('table');
                if (table) {
                    const tableHeader = table.querySelector(`thead th:nth-child(${colIndex + 1})`);
                    if (tableHeader) {
                        return tableHeader.textContent.trim();
                    }
                }
            }
        } catch (err) {
            window.C2MS.Core.log('Error getting certificate type:', 'error');
            if (window.C2MS.Core.config.debug) {
                window.C2MS.Core.log(err.message, 'error');
            }
        }
        
        // Default
        return 'Dettagli Certificato';
    },
    
    /**
     * Get militare info from element
     * @param {HTMLElement} element - Trigger element
     * @returns {Object} Militare info (grado, nome)
     */
    getMilitareInfo: function(element) {
        // Check data attributes
        if (element.dataset.grado && element.dataset.nome) {
            return {
                grado: element.dataset.grado,
                nome: element.dataset.nome
            };
        }
        
        // Fallback: look in table row
        let grado = '', nome = '';
        try {
            const row = element.closest('tr');
            if (row) {
                const gradoCell = row.querySelector('td:first-child');
                if (gradoCell) {
                    grado = gradoCell.textContent.trim();
                }
                
                const nomeCell = row.querySelector('td:nth-child(2)');
                if (nomeCell) {
                    nome = nomeCell.textContent.trim();
                }
            }
        } catch (err) {
            window.C2MS.Core.log('Error getting militare info:', 'error');
            if (window.C2MS.Core.config.debug) {
                window.C2MS.Core.log(err.message, 'error');
            }
        }
        
        // Default values if not found
        return { 
            grado: grado || 'Militare', 
            nome: nome || 'Non specificato' 
        };
    },
    
    /**
     * Format data to HTML for display
     * @param {Object} data - Data to format
     * @returns {string} Formatted HTML
     */
    formatDataToHTML: function(data) {
        let html = '<ul class="cert-modal-field-list">';
        
        // Ensure fields are in desired order
        this.config.allowedFields.forEach(field => {
            // Use value from data or null value if not present
            const value = data[field] !== undefined ? data[field] : this.config.nullValue;
            
            // Get CSS classes for the value
            const valueClass = this.getValueClass(field, value);
            
            // Create CSS-safe class name
            const fieldClass = field.toLowerCase().replace(/\s+/g, '');
            
            // Create list item
            html += `
                <li class="cert-modal-field ${fieldClass}">
                    <span class="cert-modal-field-label">${field}</span>
                    <span class="cert-modal-field-value ${valueClass}">${value}</span>
                </li>
            `;
        });
        
        html += '</ul>';
        return html;
    },
    
    /**
     * Show modal with data
     * @param {string} certType - Certificate type
     * @param {string} content - HTML content
     * @param {string} grado - Militare rank
     * @param {string} nome - Militare name
     */
    showModal: function(certType, content, grado, nome) {
        // Store currently focused element
        this.previouslyFocusedElement = document.activeElement;
        
        // Populate modal
        this.elements.modalSubtitle.textContent = certType || 'Dettagli Certificato';
        this.elements.modalContent.innerHTML = content;
        this.elements.modalGrado.textContent = grado || '';
        this.elements.modalNome.textContent = nome || '';
        
        // Show modal with animation
        this.elements.modalContainer.className = 'cert-modal-container';
        this.elements.modalContainer.setAttribute('aria-hidden', 'false');
        this.elements.modalContainer.style.display = 'flex';
        document.body.classList.add('cert-modal-open');
        
        // Activate animation in next frame
        requestAnimationFrame(() => {
            this.elements.modalContainer.classList.add('active');
            
            // Focus close button for accessibility
            const closeButton = this.elements.modalContainer.querySelector('.cert-modal-close');
            if (closeButton) closeButton.focus();
        });
    },
    
    /**
     * Hide modal
     */
    hideModal: function() {
        // Deactivate modal
        this.elements.modalContainer.classList.remove('active');
        document.body.classList.remove('cert-modal-open');
        this.elements.modalContainer.setAttribute('aria-hidden', 'true');
        
        // Wait for animation to finish
        setTimeout(() => {
            this.elements.modalContainer.style.display = 'none';
            
            // Restore focus
            if (this.previouslyFocusedElement) {
                this.previouslyFocusedElement.focus();
            }
        }, 200);
    },
    
    /**
     * Get CSS class for value based on field and value
     * @param {string} field - Field name
     * @param {string} value - Field value
     * @returns {string} CSS class
     */
    getValueClass: function(field, value) {
        // If no value or null, don't add classes
        if (value === this.config.nullValue || value === '-' || !value) return '';
        
        let valueClass = '';
        
        // Specific classes by field
        switch (field) {
            case 'Stato':
                if (value.toLowerCase().includes('scadut')) {
                    valueClass = 'negative';
                } else if (value.toLowerCase().includes('valido') || 
                          value.toLowerCase().includes('attivo')) {
                    valueClass = 'positive';
                } else if (value.toLowerCase().includes('scadenza') || 
                          value.toLowerCase().includes('in scadenza')) {
                    valueClass = 'warning';
                }
                break;
                
            case 'Data caricamento':
            case 'Data scadenza':
                valueClass = 'date';
                break;
                
            case 'Giorni rimanenti':
                valueClass = 'days';
                if (value.toLowerCase().includes('scadut')) {
                    valueClass += ' negative';
                } else {
                    const daysMatch = value.match(/\d+/);
                    if (daysMatch) {
                        const days = parseInt(daysMatch[0], 10);
                        if (days <= 30) {
                            valueClass += ' warning';
                        } else {
                            valueClass += ' positive';
                        }
                    }
                }
                break;
                
            case 'File':
                if (value.toLowerCase().includes('caricato') && !value.toLowerCase().includes('non')) {
                    valueClass = 'positive';
                } else if (value.toLowerCase().includes('non')) {
                    valueClass = 'negative';
                }
                break;
        }
        
        return valueClass;
    },
    
    // Inizializza i tooltip delle note
    initNoteTooltips: function() {
        document.querySelectorAll('.notes-cell').forEach(function(cell) {
            cell.addEventListener('mouseenter', function() {
                const tooltip = this.querySelector('.notes-tooltip');
                if (tooltip) {
                    // Posizionamento del tooltip
                    const cellRect = this.getBoundingClientRect();
                    const tooltipWidth = 250;
                    
                    // Calcola la posizione orizzontale
                    let leftPos = (cellRect.width / 2) - (tooltipWidth / 2);
                    
                    // Gestisce overflow orizzontale
                    const windowWidth = window.innerWidth;
                    const tooltipRight = cellRect.left + leftPos + tooltipWidth;
                    
                    if (tooltipRight > windowWidth - 20) {
                        leftPos = windowWidth - tooltipWidth - cellRect.left - 20;
                    }
                    
                    if (cellRect.left + leftPos < 20) {
                        leftPos = 20 - cellRect.left;
                    }
                    
                    tooltip.style.left = `${leftPos}px`;
                }
            });
        });
    }
};

// Initialize module when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.C2MS.CertificateTooltips.init();
});
