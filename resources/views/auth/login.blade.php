<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUGECO</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('vendor/css/google-fonts.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Oswald:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="{{ asset('vendor/css/fontawesome.min.css') }}">
    
    <style>
        :root {
            --navy-dark: #0a1628;
            --navy: #0a2342;
            --navy-light: #1a3a5c;
            --olive: #2c5f2d;
            --olive-light: #3d7a3e;
            --gold: #c9a227;
            --gold-light: #d4b44a;
            --cream: #f8f6f0;
            --white: #ffffff;
            --gray-100: #f7f8fa;
            --gray-200: #e9ecef;
            --gray-500: #6c757d;
            --danger: #dc3545;
            --success: #198754;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--navy-dark);
            overflow: hidden;
        }

        /* ===== LEFT PANEL - BRANDING ===== */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 50%, var(--navy-light) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        /* Geometric pattern overlay */
        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(30deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%, rgba(201, 162, 39, 0.03)),
                linear-gradient(150deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%, rgba(201, 162, 39, 0.03)),
                linear-gradient(30deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%, rgba(201, 162, 39, 0.03)),
                linear-gradient(150deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%, rgba(201, 162, 39, 0.03));
            background-size: 80px 140px;
            background-position: 0 0, 0 0, 40px 70px, 40px 70px;
            opacity: 0.8;
        }

        /* Decorative lines */
        .brand-panel::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            border: 1px solid rgba(201, 162, 39, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: var(--white);
        }

        /* Shield/Emblem icon */
        .brand-emblem {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            position: relative;
        }

        .brand-emblem svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 4px 20px rgba(201, 162, 39, 0.3));
        }

        .brand-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 64px;
            font-weight: 700;
            letter-spacing: 12px;
            margin-bottom: 16px;
            text-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            background: linear-gradient(180deg, var(--white) 0%, rgba(255,255,255,0.8) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-tagline {
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 50px;
        }

        .brand-description {
            font-size: 15px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
            max-width: 400px;
            margin: 0 auto;
        }

        .brand-features {
            margin-top: 50px;
            display: flex;
            gap: 40px;
            justify-content: center;
        }

        .feature-item {
            text-align: center;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            border: 1px solid rgba(201, 162, 39, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            color: var(--gold);
            font-size: 20px;
            background: rgba(201, 162, 39, 0.05);
        }

        .feature-label {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== RIGHT PANEL - LOGIN FORM ===== */
        .login-panel {
            width: 480px;
            min-width: 480px;
            background: var(--white);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .login-subtitle {
            font-size: 15px;
            color: var(--gray-500);
        }

        /* Form styles */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input-wrapper {
            position: relative;
        }

        .form-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 16px;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-100);
            transition: all 0.2s ease;
            color: var(--navy);
        }

        .form-control::placeholder {
            color: var(--gray-500);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--navy);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.1);
        }

        .form-control:focus + .form-input-icon,
        .form-input-wrapper:focus-within .form-input-icon {
            color: var(--navy);
        }

        .form-control.is-invalid {
            border-color: var(--danger);
            background: #fff5f5;
        }

        .form-hint {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 6px;
        }

        .invalid-feedback {
            font-size: 13px;
            color: var(--danger);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Remember checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid var(--gray-200);
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-check-input:checked {
            background-color: var(--navy);
            border-color: var(--navy);
        }

        .form-check-label {
            font-size: 14px;
            color: var(--gray-500);
            cursor: pointer;
        }

        /* Submit button */
        .btn-login {
            width: 100%;
            padding: 16px 24px;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--white);
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(10, 35, 66, 0.35);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login i {
            margin-right: 10px;
        }

        /* Loading state */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 10px;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        .btn-login.loading .btn-text {
            opacity: 0.7;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert i {
            font-size: 18px;
        }

        /* Footer */
        .login-footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid var(--gray-200);
            text-align: center;
        }

        .login-footer-text {
            font-size: 13px;
            color: var(--gray-500);
        }

        .version-badge {
            display: inline-block;
            background: var(--gray-100);
            color: var(--gray-500);
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 12px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .brand-panel {
                display: none;
            }
            
            .login-panel {
                width: 100%;
                min-width: unset;
                max-width: 500px;
                margin: 0 auto;
            }

            body {
                background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 100%);
                padding: 20px;
                align-items: center;
                justify-content: center;
            }

            .login-panel {
                border-radius: 16px;
                box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
            }
        }

        @media (max-width: 480px) {
            .login-panel {
                padding: 40px 24px;
            }

            .login-title {
                font-size: 24px;
            }

            .brand-logo {
                font-size: 48px;
                letter-spacing: 8px;
            }
        }

        /* Animation on load */
        .login-panel {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Left Panel - Branding -->
    <div class="brand-panel">
        <div class="brand-content">
            <!-- Shield Emblem -->
            <div class="brand-emblem">
                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M50 5L90 20V45C90 70 70 90 50 95C30 90 10 70 10 45V20L50 5Z" 
                          stroke="#c9a227" stroke-width="2" fill="rgba(201, 162, 39, 0.1)"/>
                    <path d="M50 15L80 27V45C80 65 65 80 50 85C35 80 20 65 20 45V27L50 15Z" 
                          stroke="#c9a227" stroke-width="1" fill="none" opacity="0.5"/>
                    <text x="50" y="58" text-anchor="middle" fill="#c9a227" 
                          font-family="Oswald" font-size="24" font-weight="600">S</text>
                </svg>
            </div>

            <h1 class="brand-logo">SUGECO</h1>
            <p class="brand-tagline">Sistema Unico di Gestione e Controllo</p>
            
            <p class="brand-description">
                Piattaforma integrata per la gestione operativa del personale militare. 
                Controllo presenze, pianificazione attività e monitoraggio scadenze.
            </p>

            <div class="brand-features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <span class="feature-label">Sicuro</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="feature-label">Multi-Utente</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="feature-label">Efficiente</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="login-panel">
        <div class="login-header">
            <h2 class="login-title">Accedi al Sistema</h2>
            <p class="login-subtitle">Inserisci le tue credenziali per continuare</p>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Error Messages -->
        @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <span>
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </span>
            </div>
        @endif

        <!-- Login Form -->
        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <!-- Username -->
            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="form-input-wrapper">
                    <input
                        type="text"
                        class="form-control @error('username') is-invalid @enderror"
                        id="username"
                        name="username"
                        placeholder="nome.cognome"
                        value="{{ old('username') }}"
                        required
                        autofocus
                    >
                    <i class="fas fa-user form-input-icon"></i>
                </div>
                @error('username')
                    <div class="invalid-feedback d-block">
                        <i class="fas fa-times-circle"></i> {{ $message }}
                    </div>
                @enderror
                <p class="form-hint">Formato: nome.cognome</p>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="form-input-wrapper">
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                    >
                    <i class="fas fa-lock form-input-icon"></i>
                </div>
                @error('password')
                    <div class="invalid-feedback d-block">
                        <i class="fas fa-times-circle"></i> {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="form-check">
                <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="remember" 
                    name="remember"
                >
                <label class="form-check-label" for="remember">
                    Mantieni la sessione attiva
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login" id="submitBtn">
                <span class="spinner"></span>
                <span class="btn-text">
                    <i class="fas fa-sign-in-alt"></i>
                    Accedi
                </span>
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <p class="login-footer-text">
                Accesso riservato al personale autorizzato
            </p>
            <span class="version-badge">
                <i class="fas fa-code-branch me-1"></i> v2.1.0
            </span>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Loading animation on submit
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
        });

        // Auto-hide success messages
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.transition = 'all 0.4s ease';
                successAlert.style.opacity = '0';
                successAlert.style.transform = 'translateY(-10px)';
                setTimeout(() => successAlert.remove(), 400);
            }, 4000);
        }

        // Focus effect enhancement
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
