<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUGECO</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('vendor/css/google-fonts.css') }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="{{ asset('vendor/css/fontawesome.min.css') }}">
    
    <style>
        :root {
            --navy: #0a2342;
            --navy-light: #1a3a5c;
            --gold: #c9a227;
            --gold-light: #d4b44a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            padding: 20px;
            position: relative;
        }

        /* Subtle pattern overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 30px 30px;
            pointer-events: none;
        }

        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        /* Header con gradiente */
        .login-header {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            padding: 40px 35px 35px;
            text-align: center;
            position: relative;
        }

        /* Decorative line */
        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--gold);
            border-radius: 2px;
        }

        .login-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 48px;
            font-weight: 700;
            letter-spacing: 10px;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .login-subtitle {
            font-size: 13px;
            color: var(--gold);
            letter-spacing: 3px;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Body */
        .login-body {
            padding: 40px 35px 35px;
        }

        .welcome-text {
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-text h2 {
            font-size: 22px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 5px;
        }

        .welcome-text p {
            font-size: 14px;
            color: #666;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: var(--gold);
            font-size: 12px;
        }

        .form-control {
            padding: 14px 16px;
            font-size: 15px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            transition: all 0.2s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--navy);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.08);
            outline: none;
        }

        .form-control::placeholder {
            color: #aaa;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff8f8;
        }

        .form-check {
            margin: 20px 0 25px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #ddd;
            border-radius: 4px;
            margin-right: 8px;
        }

        .form-check-input:checked {
            background-color: var(--navy);
            border-color: var(--navy);
        }

        .form-check-label {
            font-size: 14px;
            color: #666;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 15px 24px;
            font-size: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: white;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(10, 35, 66, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Alerts */
        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-hint {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
        }

        /* Footer */
        .login-footer {
            padding: 20px 35px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 12px;
            color: #888;
        }

        .footer-version {
            font-size: 11px;
            color: #aaa;
            background: #eee;
            padding: 4px 10px;
            border-radius: 12px;
        }

        /* Loading state */
        .btn-login.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .btn-login.loading .btn-icon {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 25px 28px;
            }
            .login-body {
                padding: 30px 25px 25px;
            }
            .login-logo {
                font-size: 40px;
                letter-spacing: 8px;
            }
            .login-footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="login-logo">SUGECO</div>
            <div class="login-subtitle">Sistema di Gestione e Controllo</div>
        </div>

        <!-- Body -->
        <div class="login-body">
            <div class="welcome-text">
                <h2>Benvenuto</h2>
                <p>Inserisci le credenziali per accedere</p>
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
                    <i class="fas fa-exclamation-circle"></i>
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
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
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
                    @error('username')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-times-circle"></i> {{ $message }}
                        </div>
                    @enderror
                    <div class="form-hint">Formato: nome.cognome</div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="••••••••"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback d-block">
                            <i class="fas fa-times-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Mantieni la sessione attiva
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="spinner"></span>
                    <i class="fas fa-sign-in-alt btn-icon"></i>
                    <span>Accedi</span>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <span class="footer-text">
                <i class="fas fa-shield-alt me-1"></i>
                Accesso riservato
            </span>
            <span class="footer-version">v2.1.0</span>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
        });

        // Auto-hide success
        const successAlert = document.querySelector('.alert-success');
        if (successAlert) {
            setTimeout(() => {
                successAlert.style.opacity = '0';
                successAlert.style.transition = 'opacity 0.3s';
            }, 4000);
        }
    </script>
</body>
</html>
