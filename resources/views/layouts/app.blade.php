<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'C2MS: Gestione e Controllo Digitale a Supporto del Comando')</title>
    
    <!-- Meta CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Roboto e Oswald -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 - CDN Primario con Fallback -->
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Fallback Font Awesome 6 da altro CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" 
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" 
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- CSS Sistema - Caricati nell'ordine corretto delle dipendenze -->
    <link rel="stylesheet" href="{{ asset('css/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/filters.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tooltips.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/toast-system.css') }}">
    <link href="{{ asset('css/dashboard.css') }}" rel="stylesheet">
    
    @yield('styles')
</head>
<body>
    <!-- Header with Horizontal Navigation -->
    <header class="main-header">
        <!-- Logo a sinistra - Solo testo minimalista -->
        <a href="{{ url('/') }}" class="header-logo">
            <div class="logo-text">
                <span class="logo-letter">C</span>
                <span class="logo-letter">2</span>
                <span class="logo-letter">M</span>
                <span class="logo-letter">S</span>
            </div>
        </a>
        
        <!-- Horizontal Navigation al centro -->
        <nav class="nav-center">
            <ul class="nav-menu" id="nav-menu">
                <li class="nav-menu-item {{ request()->is('/') ? 'active' : '' }}">
                    <a href="{{ url('/') }}">
                        Dashboard
                    </a>
                </li>
                

                
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
                
                <li class="nav-menu-item {{ request()->is('militare*') || request()->is('organigramma*') ? 'active' : '' }}">
                    <a href="{{ url('/militare') }}">
                        Elenco Personale
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('militare*') && !request()->is('militare/create') ? 'active' : '' }}">
                            <a href="{{ url('/militare') }}">Forza Effettiva</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('organigramma*') ? 'active' : '' }}">
                            <a href="{{ url('/organigramma') }}">Organigramma</a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-menu-item {{ request()->is('certificati*') ? 'active' : '' }}">
                    <a href="{{ route('certificati.corsi_lavoratori') }}">
                        Gestione Certificati
                    </a>
                    <ul class="nav-dropdown">
                        <li class="nav-dropdown-item {{ request()->is('certificati/corsi-lavoratori*') ? 'active' : '' }}">
                            <a href="{{ route('certificati.corsi_lavoratori') }}">Corsi Lavoratori</a>
                        </li>
                        <li class="nav-dropdown-item {{ request()->is('certificati/idoneita*') ? 'active' : '' }}">
                            <a href="{{ route('certificati.idoneita') }}">Idoneità</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        
        <!-- Spazio vuoto a destra per bilanciare il layout -->
        <div class="header-spacer"></div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p class="mb-0">C2MS: Gestione e Controllo Digitale a Supporto del Comando &copy; {{ date('Y') }}</p>
        </div>
    </footer>
    
    <!-- Toast container -->
    <div class="toast-container"></div>
    
    <!-- jQuery (necessario per molti plugin) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Configuration and Error Handling (caricati per primi) -->
    <script src="{{ asset('js/config.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/error-handler.js') }}?v={{ time() }}"></script>
    
    <!-- Scripts Sistema - Caricati nell'ordine corretto delle dipendenze -->
    <script src="{{ asset('js/c2ms-core.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/toast-system.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/filters.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/search-fixed.js') }}?v={{ time() }}&bust={{ rand(1000,9999) }}&debug=true"></script>
    <script src="{{ asset('js/autosave.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/certificate-tooltips.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/militare.js') }}?v={{ time() }}"></script>
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
    
    <!-- Fallback locale se necessario -->
    <script>
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
