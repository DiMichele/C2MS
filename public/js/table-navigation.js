/**
 * SUGECO Table Navigation
 * Sistema di navigazione con frecce per tabelle scrollabili orizzontalmente
 * Riutilizzabile su tutte le pagine con tabelle a colonne fisse
 * 
 * Uso:
 * 1. Wrappare la tabella con .sugeco-table-nav-container
 * 2. Includere questo script
 * 3. Chiamare SUGECO.TableNav.init() o usare data-table-nav="auto"
 */

(function() {
    'use strict';
    
    // Namespace SUGECO
    window.SUGECO = window.SUGECO || {};
    
    /**
     * TableNav - Sistema di navigazione tabelle
     */
    window.SUGECO.TableNav = {
        
        /**
         * Inizializza la navigazione per tutte le tabelle con data-table-nav
         * o per un container specifico
         */
        init: function(containerSelector) {
            const containers = containerSelector 
                ? document.querySelectorAll(containerSelector)
                : document.querySelectorAll('[data-table-nav="auto"], .sugeco-table-nav-container');
            
            containers.forEach(container => {
                this.setupContainer(container);
            });
        },
        
        /**
         * Setup di un singolo container
         */
        setupContainer: function(container) {
            // Trova il wrapper della tabella
            let wrapper = container.querySelector('.sugeco-table-wrapper');
            
            // Se non esiste, cerca direttamente un elemento con overflow-x
            if (!wrapper) {
                wrapper = container.querySelector('.table-responsive, .table-container');
            }
            
            if (!wrapper) {
                console.warn('TableNav: wrapper tabella non trovato in', container);
                return;
            }
            
            // Verifica se la navigazione è già stata inizializzata
            if (container.dataset.tableNavInitialized === 'true') {
                return;
            }
            
            // Marca come inizializzato
            container.dataset.tableNavInitialized = 'true';
            
            // Crea le frecce di navigazione se non esistono
            this.createNavigationArrows(container, wrapper);
            
            // Crea gli indicatori di scroll
            this.createScrollIndicators(container, wrapper);
            
            // Setup degli event listeners
            this.setupEventListeners(container, wrapper);
            
            // Aggiorna lo stato iniziale
            setTimeout(() => {
                this.updateNavigationState(container, wrapper);
            }, 100);
        },
        
        /**
         * Crea le frecce di navigazione
         */
        createNavigationArrows: function(container, wrapper) {
            // Verifica se le frecce esistono già
            if (container.querySelector('.table-nav-prev')) {
                return;
            }
            
            // Freccia sinistra
            const prevBtn = document.createElement('button');
            prevBtn.className = 'table-nav-arrow table-nav-prev';
            prevBtn.type = 'button';
            prevBtn.setAttribute('aria-label', 'Scorri a sinistra');
            prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            
            // Freccia destra
            const nextBtn = document.createElement('button');
            nextBtn.className = 'table-nav-arrow table-nav-next';
            nextBtn.type = 'button';
            nextBtn.setAttribute('aria-label', 'Scorri a destra');
            nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            
            // Inserisci le frecce nel container
            container.insertBefore(prevBtn, container.firstChild);
            container.appendChild(nextBtn);
            
            // Event listeners per le frecce
            prevBtn.addEventListener('click', () => {
                this.scrollTable(wrapper, 'prev');
            });
            
            nextBtn.addEventListener('click', () => {
                this.scrollTable(wrapper, 'next');
            });
        },
        
        /**
         * Crea gli indicatori di scroll (progress bar o dots)
         */
        createScrollIndicators: function(container, wrapper) {
            // Verifica se gli indicatori esistono già
            if (container.querySelector('.table-scroll-progress')) {
                return;
            }
            
            // Usa una progress bar (più elegante per tabelle)
            const progressContainer = document.createElement('div');
            progressContainer.className = 'table-scroll-progress';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'table-scroll-progress-bar';
            
            progressContainer.appendChild(progressBar);
            container.appendChild(progressContainer);
        },
        
        /**
         * Setup event listeners per lo scroll
         */
        setupEventListeners: function(container, wrapper) {
            // Aggiorna stato durante lo scroll
            wrapper.addEventListener('scroll', () => {
                this.updateNavigationState(container, wrapper);
            });
            
            // Supporto per resize della finestra
            let resizeTimeout;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => {
                    this.updateNavigationState(container, wrapper);
                }, 100);
            });
            
            // Supporto per drag to scroll su desktop
            this.setupDragScroll(wrapper);
        },
        
        /**
         * Drag to scroll su desktop
         */
        setupDragScroll: function(wrapper) {
            let isDown = false;
            let startX;
            let scrollLeft;
            
            wrapper.addEventListener('mousedown', (e) => {
                // Ignora se il target è un elemento interattivo
                if (e.target.closest('button, input, select, a, .scadenza-cell')) {
                    return;
                }
                isDown = true;
                wrapper.style.cursor = 'grabbing';
                startX = e.pageX - wrapper.offsetLeft;
                scrollLeft = wrapper.scrollLeft;
            });
            
            wrapper.addEventListener('mouseleave', () => {
                isDown = false;
                wrapper.style.cursor = '';
            });
            
            wrapper.addEventListener('mouseup', () => {
                isDown = false;
                wrapper.style.cursor = '';
            });
            
            wrapper.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - wrapper.offsetLeft;
                const walk = (x - startX) * 1.5;
                wrapper.scrollLeft = scrollLeft - walk;
            });
        },
        
        /**
         * Scorri la tabella nella direzione specificata
         */
        scrollTable: function(wrapper, direction) {
            // Calcola lo scroll: 80% della larghezza visibile
            const scrollAmount = wrapper.clientWidth * 0.8;
            
            const targetScroll = direction === 'next' 
                ? wrapper.scrollLeft + scrollAmount
                : wrapper.scrollLeft - scrollAmount;
            
            wrapper.scrollTo({
                left: targetScroll,
                behavior: 'smooth'
            });
        },
        
        /**
         * Aggiorna lo stato delle frecce e della progress bar
         */
        updateNavigationState: function(container, wrapper) {
            const prevBtn = container.querySelector('.table-nav-prev');
            const nextBtn = container.querySelector('.table-nav-next');
            const progressBar = container.querySelector('.table-scroll-progress-bar');
            
            // Calcola la posizione di scroll
            const scrollLeft = wrapper.scrollLeft;
            const scrollWidth = wrapper.scrollWidth;
            const clientWidth = wrapper.clientWidth;
            const maxScroll = scrollWidth - clientWidth;
            
            // Se non c'è bisogno di scroll, nascondi le frecce
            if (maxScroll <= 10) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
                if (progressBar && progressBar.parentElement) {
                    progressBar.parentElement.style.display = 'none';
                }
                // Rimuovi il padding dal container se non serve scroll
                container.style.padding = '0';
                return;
            } else {
                if (prevBtn) prevBtn.style.display = 'flex';
                if (nextBtn) nextBtn.style.display = 'flex';
                if (progressBar && progressBar.parentElement) {
                    progressBar.parentElement.style.display = 'block';
                }
                container.style.padding = '';
            }
            
            // Aggiorna stato frecce
            if (prevBtn) {
                if (scrollLeft <= 10) {
                    prevBtn.classList.add('disabled');
                } else {
                    prevBtn.classList.remove('disabled');
                }
            }
            
            if (nextBtn) {
                if (scrollLeft >= maxScroll - 10) {
                    nextBtn.classList.add('disabled');
                } else {
                    nextBtn.classList.remove('disabled');
                }
            }
            
            // Aggiorna progress bar
            if (progressBar) {
                const progressPercent = maxScroll > 0 
                    ? (scrollLeft / maxScroll) * 100 
                    : 0;
                progressBar.style.width = `${progressPercent}%`;
            }
        },
        
        /**
         * Scorri a una posizione specifica (es. inizio, fine)
         */
        scrollTo: function(wrapper, position) {
            let targetScroll;
            
            switch(position) {
                case 'start':
                    targetScroll = 0;
                    break;
                case 'end':
                    targetScroll = wrapper.scrollWidth - wrapper.clientWidth;
                    break;
                case 'center':
                    targetScroll = (wrapper.scrollWidth - wrapper.clientWidth) / 2;
                    break;
                default:
                    targetScroll = parseInt(position) || 0;
            }
            
            wrapper.scrollTo({
                left: targetScroll,
                behavior: 'smooth'
            });
        }
    };
    
    // Auto-inizializzazione al caricamento DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Delay per assicurarsi che la tabella sia renderizzata
            setTimeout(function() {
                SUGECO.TableNav.init();
            }, 200);
        });
    } else {
        // DOM già caricato - delay per sicurezza
        setTimeout(function() {
            SUGECO.TableNav.init();
        }, 200);
    }
    
    // Re-inizializza dopo un caricamento completo della pagina
    window.addEventListener('load', function() {
        setTimeout(function() {
            SUGECO.TableNav.init();
        }, 300);
    });
    
})();
