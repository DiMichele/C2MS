<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SUGECO - Sistema Unico di Gestione e Controllo')</title>
    
    <!-- Favicon SUGECO -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/sugeco-icon.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/sugeco-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/sugeco-icon.png') }}">
    
    <!-- Meta CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Roboto e Oswald (LOCALE per intranet) -->
    <link rel="stylesheet" href="{{ asset('vendor/css/google-fonts.css') }}">
    
    <!-- Bootstrap 5 CSS (LOCALE) -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome 6 (LOCALE) -->
    <link rel="stylesheet" href="{{ asset('vendor/css/fontawesome.min.css') }}">
    
    <!-- CSS Custom Radius per coerenza visiva -->
    <link href="{{ asset('css/custom-radius.css') }}?v={{ config('app.asset_version', time()) }}" rel="stylesheet">
    
    <!-- CSS Sistema - Caricati nell'ordine corretto delle dipendenze -->
    <link rel="stylesheet" href="{{ asset('css/global.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/filters.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/tooltips.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/toast-system.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/confirm-system.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/table-standard.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ config('app.asset_version', time()) }}">
    <link rel="stylesheet" href="{{ asset('css/militari-selector.css') }}?v={{ config('app.asset_version', time()) }}">
    
    @yield('styles')
</head>
<body>
    <!-- Header with Horizontal Navigation -->
    <header class="main-header">
        <!-- Logo a sinistra - Solo testo minimalista -->
        <a href="{{ url('/') }}" class="header-logo">
            <div class="logo-text">
                <span class="logo-letter">S</span>
                <span class="logo-letter">U</span>
                <span class="logo-letter">G</span>
                <span class="logo-letter">E</span>
                <span class="logo-letter">C</span>
                <span class="logo-letter">O</span>
            </div>
        </a>
        
        <!-- Horizontal Navigation al centro -->
        <nav class="nav-center">
            <ul class="nav-menu" id="nav-menu">
                @auth
                @if(Auth::check() && Auth::user()->hasPermission('dashboard.view'))
                <li class="nav-menu-item {{ request()->is('/') ? 'active' : '' }}">
                    <a href="{{ url('/') }}">
                        Dashboard
                    </a>
                </li>
                @endif
                @endauth
                
                <li class="nav-menu-item {{ request()->is('cpt*') || request()->is('anagrafica*') || request()->is('ruolini*') || request()->is('organigramma*') || request()->is('disponibilita*') ? 'active' : '' }}">
                    <a href="#">
                        Personale
                    </a>
                    <ul class="nav-dropdown">
                        @auth
                        @if(Auth::check() && Auth::user()->hasPermission('cpt.view'))
                        <li class="nav-dropdown-item {{ request()->is('cpt*') ? 'active' : '' }}">
                            <a href="{{ route('pianificazione.index') }}">CPT</a>
                        </li>
                        @endif
                        @if(Auth::check() && Auth::user()->hasPermission('anagrafica.view'))
                        <li class="nav-dropdown-item {{ request()->is('ruolini*') ? 'active' : '' }}">
                            <a href="{{ route('ruolini.index') }}">Ruolini</a>
                        </li>
                        @endif
                        @endauth
                        <li class="nav-dropdown-item {{ request()->is('anagrafica*') && !request()->is('anagrafica/create') ? 'active' : '' }}">
                            <a href="{{ route('anagrafica.index') }}">Anagrafica</a>
                        </li>
                        @auth
                        @if(Auth::check() && Auth::user()->hasPermission('anagrafica.view'))
                        <li class="nav-dropdown-item {{ request()->is('organigramma*') ? 'active' : '' }}">
                            <a href="{{ url('/organigramma') }}">Organigramma</a>
                        </li>
                        @endif
                        @if(Auth::check() && Auth::user()->hasPermission('cpt.view'))
                        <li class="nav-dropdown-item {{ request()->is('disponibilita*') ? 'active' : '' }}">
                            <a href="{{ route('disponibilita.index') }}">Disponibilità</a>
                        </li>
                        @endif
                        @endauth
                    </ul>
                </li>
                
                @auth
                @if(Auth::check() && Auth::user()->hasPermission('scadenze.view'))
                <li class="nav-menu-item {{ request()->is('spp/*') ? 'active' : '' }}">
                    <a href="#" onclick="event.preventDefault();">
                        SPP
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('spp/corsi-di-formazione*') ? 'active' : '' }}">
                            <a href="{{ route('spp.corsi-di-formazione') }}">Corsi di Formazione</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('spp/corsi-accordo-stato-regione*') ? 'active' : '' }}">
                            <a href="{{ route('spp.corsi-accordo-stato-regione') }}">Corsi Accordo Stato Regione</a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-menu-item {{ request()->is('poligoni*') ? 'active' : '' }}">
                    <a href="{{ route('poligoni.index') }}">
                        Poligoni
                    </a>
                </li>
                
                <li class="nav-menu-item {{ request()->is('idoneita*') ? 'active' : '' }}">
                    <a href="{{ route('idoneita.index') }}">
                        Idoneità
                    </a>
                </li>
                
                <li class="nav-menu-item {{ request()->is('impieghi-personale*') ? 'active' : '' }}">
                    <a href="{{ route('impieghi-personale.index') }}">
                        Organici
                    </a>
                </li>
                
                <li class="nav-menu-item {{ request()->is('approntamenti*') ? 'active' : '' }}">
                    <a href="{{ route('approntamenti.index') }}">
                        Approntamenti
                    </a>
                </li>
                @endif
                @endauth
                
                @auth
                @if(Auth::check() && Auth::user()->hasPermission('board.view'))
                <li class="nav-menu-item {{ request()->is('board*') ? 'active' : '' }}">
                    <a href="{{ route('board.index') }}">
                        Board Attività
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('board') && !request()->is('board/calendar') ? 'active' : '' }}">
                            <a href="{{ route('board.index') }}">Vista Board</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('board/calendar') ? 'active' : '' }}">
                            <a href="{{ route('board.calendar') }}">Calendario</a>
                        </li>
                    </ul>
                </li>
                @endif
                
                @if(Auth::check() && Auth::user()->hasPermission('servizi.view'))
                <li class="nav-menu-item {{ request()->is('servizi*') || request()->is('trasparenza*') ? 'active' : '' }}">
                    <a href="#">
                        Servizi
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('servizi/turni*') ? 'active' : '' }}">
                            <a href="{{ route('servizi.turni.index') }}">Servizi</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('trasparenza*') ? 'active' : '' }}">
                            <a href="{{ route('trasparenza.index') }}">Trasparenza Servizi</a>
                        </li>
                    </ul>
                </li>
                @endif
                
                @if(Auth::check() && Auth::user()->hasPermission('admin.access'))
                <li class="nav-menu-item {{ request()->is('admin*') || request()->is('codici-cpt*') || request()->is('gestione-ruolini*') || request()->is('gestione-spp*') || request()->is('gestione-poligoni*') || request()->is('gestione-idoneita*') || request()->is('gestione-approntamenti*') || request()->is('gestione-anagrafica-config*') || request()->is('gestione-campi-anagrafica*') ? 'active' : '' }}">
                    <a href="#">
                        Admin
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('admin/utenti*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}">Gestione Utenti</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('admin/permessi*') || request()->is('admin/ruoli*') ? 'active' : '' }}">
                            <a href="{{ route('admin.permissions.index') }}">Gestione Ruoli</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('codici-cpt*') ? 'active' : '' }}">
                            <a href="{{ route('codici-cpt.index') }}">Codici CPT</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-ruolini*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-ruolini.index') }}">Gestione Ruolini</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-spp*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-spp.index') }}">Gestione SPP</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-poligoni*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-poligoni.index') }}">Gestione Poligoni</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-idoneita*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-idoneita.index') }}">Gestione Idoneità</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-approntamenti*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-approntamenti.index') }}">Gestione Approntamenti</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('gestione-anagrafica-config*') || request()->is('gestione-campi-anagrafica*') ? 'active' : '' }}">
                            <a href="{{ route('gestione-anagrafica-config.index') }}">Gestione Anagrafica</a>
                        </li>
                        <li class="nav-dropdown-divider"></li>
                        @if(Auth::user()->hasPermission('gerarchia.view'))
                        <li class="nav-dropdown-item {{ request()->is('gerarchia-organizzativa*') ? 'active' : '' }}">
                            <a href="{{ route('gerarchia.index') }}">Gerarchia Organizzativa</a>
                        </li>
                        @endif
                        <li class="nav-dropdown-item {{ request()->is('admin/registro-attivita*') ? 'active' : '' }}">
                            <a href="{{ route('admin.audit-logs.index') }}">Registro Attività</a>
                        </li>
                    </ul>
                </li>
                @endif
                @endauth

            </ul>
        </nav>
        
        <!-- Auth Menu a destra -->
        <div class="header-auth">
            @auth
                {{-- Indicatore Unità Organizzativa Corrente --}}
                @php
                    $currentUnit = Auth::user()->organizationalUnit ?? null;
                    $unitName = $currentUnit ? $currentUnit->name : (Auth::user()->compagnia->nome ?? 'Non assegnato');
                @endphp
                <span class="unit-badge" title="Unità organizzativa corrente">
                    <i class="fas fa-sitemap"></i>
                    {{ Str::limit($unitName, 20) }}
                </span>
                
                <div class="user-menu">
                    <button onclick="window.location='{{ route('profile.index') }}'" class="btn-profile" title="Il Mio Profilo">
                        <i class="fas fa-user-circle me-1"></i>
                        {{ Auth::user()->name }}
                    </button>
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn-logout" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Login
                </a>
            @endauth
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">SUGECO - Sistema Unico di Gestione e Controllo</p>
        </div>
    </footer>
    
    <!-- Toast container -->
    <div class="toast-container"></div>
    
    <!-- jQuery (LOCALE) -->
    <script src="{{ asset('vendor/js/jquery.min.js') }}"></script>
    
    <!-- Bootstrap JS (LOCALE) -->
    <script src="{{ asset('vendor/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Configuration and Error Handling (caricati per primi) -->
    <script src="{{ asset('js/config.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/error-handler.js') }}?v={{ time() }}"></script>
    
    <!-- Scripts Sistema - Caricati nell'ordine corretto delle dipendenze -->
    <script src="{{ asset('js/sugeco-core.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/toast-system.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/confirm-system.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/sugeco-filters.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/search-fixed.js') }}?v={{ time() }}&bust={{ rand(1000,9999) }}&debug=true"></script>
    <script src="{{ asset('js/autosave.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/certificate-tooltips.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/militare.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/table-navigation.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/main.js') }}?v={{ time() }}"></script>
    
    <!-- Script per gestione filtri -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Diagnosi errori
            setTimeout(function() {
                // Controllo elementi DOM cruciali - solo se presenti
                const toggleFilters = document.getElementById('toggleFilters');
                const filtersContainer = document.getElementById('filtersContainer');
                const toggleFiltersText = document.getElementById('toggleFiltersText');
                
                // Verifica esistenza degli elementi prima di procedere
                if (!toggleFilters || !filtersContainer) {
                    // Non eseguire il codice dei filtri se gli elementi non esistono nella pagina
                    return;
                }
                
                // Aggiungi manualmente l'elemento toggleFiltersIcon se manca
                if (!document.getElementById('toggleFiltersIcon')) {
                    const icon = toggleFilters.querySelector('i');
                    if (icon) {
                        icon.id = 'toggleFiltersIcon';
                    }
                }
                
                // Aggiungi event listener manuale se necessario
                toggleFilters.addEventListener('click', function(e) {
                    if (filtersContainer) {
                        const isVisible = filtersContainer.classList.toggle('visible');
                        toggleFilters.classList.toggle('active', isVisible);
                        
                        if (toggleFiltersText) {
                            toggleFiltersText.textContent = isVisible ? 'Nascondi filtri' : 'Mostra filtri';
                        }
                        
                        const toggleIcon = document.getElementById('toggleFiltersIcon');
                        if (toggleIcon) {
                            toggleIcon.classList.toggle('fa-rotate-180', isVisible);
                        }
                        
                        // Salva stato
                        try {
                            const pageKey = window.location.pathname.split('/')[1] || 'home';
                            const storageKey = `${pageKey}FiltersOpen`;
                            localStorage.setItem(storageKey, isVisible ? 'true' : 'false');
                        } catch (e) {
                            // Errore silenzioso
                        }
                    }
                });
            }, 1000);
        });
    </script>
    
    <!-- Scripts specifici per la pagina corrente -->
    @stack('scripts')
    
    <!-- Fix globale per problemi di luminosità/opacità -->
    <script>
        (function() {
            // Funzione per rimuovere overlay e classi problematiche
            function fixGlobalOpacity() {
                // Rimuovi classi cert-modal-open se non ci sono modal attivi
                const activeModals = document.querySelectorAll('.cert-modal-container.active, .modal.show');
                if (activeModals.length === 0) {
                    document.body.classList.remove('cert-modal-open');
                }
                
                // Rimuovi overlay attivi se non ci sono modal
                const overlays = document.querySelectorAll('.cert-modal-container, .modal-overlay, .custom-confirm-overlay');
                overlays.forEach(overlay => {
                    const computedStyle = window.getComputedStyle(overlay);
                    if (computedStyle.display !== 'none' && !overlay.closest('.modal.show')) {
                        overlay.style.display = 'none';
                        overlay.classList.remove('active', 'show');
                    }
                });
                
                // Assicura che body e main-content abbiano opacità corretta
                if (document.body.style.opacity && parseFloat(document.body.style.opacity) < 1) {
                    document.body.style.opacity = '';
                }
                
                const mainContent = document.querySelector('.main-content');
                if (mainContent && mainContent.style.opacity && parseFloat(mainContent.style.opacity) < 1) {
                    mainContent.style.opacity = '';
                }
                
                // Rimuovi filtri CSS applicati al body
                if (document.body.style.filter) {
                    document.body.style.filter = '';
                }
            }
            
            // Esegui il fix quando il DOM è caricato
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fixGlobalOpacity);
            } else {
                fixGlobalOpacity();
            }
            
            // Esegui il fix dopo un breve delay
            setTimeout(fixGlobalOpacity, 100);
            setTimeout(fixGlobalOpacity, 500);
            setTimeout(fixGlobalOpacity, 1000);
            
            // Esegui quando la pagina diventa visibile
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    fixGlobalOpacity();
                }
            });
            
            // Listener per quando si chiudono i modal Bootstrap
            document.addEventListener('hidden.bs.modal', fixGlobalOpacity);
            
            // Esegui periodicamente per sicurezza (ogni 3 secondi)
            setInterval(fixGlobalOpacity, 3000);
        })();
    </script>
    
    <!-- CSS globale per forzare opacità corretta -->
    <style>
        /* Fix per problemi di opacità globale */
        body:not(.cert-modal-open):not(.modal-open) {
            opacity: 1 !important;
            filter: none !important;
        }
        
        .main-content {
            opacity: 1 !important;
        }
        
        /* Nascondi overlay non attivi */
        .cert-modal-container:not(.active),
        .modal-overlay:not(.show),
        .custom-confirm-overlay:not(.show) {
            display: none !important;
        }
        
        /* ========================================
           FEEDBACK VISIVO SALVATAGGIO GLOBALE
           ======================================== */
        /* Classe globale per feedback salvataggio con successo */
        .saved-success,
        input.saved-success,
        select.saved-success,
        textarea.saved-success,
        .form-control.saved-success,
        .form-select.saved-success {
            border-color: #28a745 !important;
            border-width: 3px !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.15) !important;
            transition: all 0.3s ease !important;
        }
        
        /* Classe globale per feedback salvataggio con errore */
        .saved-error,
        input.saved-error,
        select.saved-error,
        textarea.saved-error,
        .form-control.saved-error,
        .form-select.saved-error {
            border-color: #dc3545 !important;
            border-width: 3px !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15) !important;
            transition: all 0.3s ease !important;
        }
    </style>
    
    <!-- Fallback locale se necessario -->
    <script>
        // ========================================
        // UTILITY GLOBALE: Feedback Visivo Salvataggio
        // ========================================
        window.SUGECO = window.SUGECO || {};
        
        /**
         * Mostra feedback visivo di salvataggio su un elemento
         * @param {HTMLElement} element - L'elemento da evidenziare
         * @param {boolean} success - true per successo (verde), false per errore (rosso)
         * @param {number} duration - Durata in millisecondi (default: 2000)
         */
        window.SUGECO.showSaveFeedback = function(element, success = true, duration = 2000) {
            if (!element) return;
            
            const className = success ? 'saved-success' : 'saved-error';
            
            // Aggiungi la classe
            element.classList.add(className);
            
            // Rimuovi la classe dopo la durata specificata
            setTimeout(function() {
                element.classList.remove(className);
            }, duration);
        };
        
        // Verifica se Font Awesome è caricato
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var testIcon = document.createElement('i');
                testIcon.className = 'fas fa-home';
                testIcon.style.display = 'none';
                document.body.appendChild(testIcon);
                
                var computedStyle = window.getComputedStyle(testIcon, ':before');
                var content = computedStyle.getPropertyValue('content');
                
                if (!content || content === 'none' || content === '""') {
                    // Font Awesome non caricato, qui potresti caricare una versione locale se disponibile
                }
                
                document.body.removeChild(testIcon);
            }, 1000);
        });
    </script>
</body>
</html>



