<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUGECO</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0a2342;
            --primary-strong: #081b33;
            --accent: #c9a227;
            --text: #111827;
            --muted: #6b7280;
            --border: #e5e7eb;
            --surface: #ffffff;
            --surface-muted: #f8fafc;
        }

        body {
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            
            /* Background con maggiore contrasto */
            background: 
                radial-gradient(900px 420px at 15% 5%, rgba(10, 35, 66, 0.18), transparent 60%),
                radial-gradient(800px 380px at 85% 20%, rgba(10, 35, 66, 0.14), transparent 55%),
                linear-gradient(180deg, #eef3f9 0%, #dfe7f2 100%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120"><defs><pattern id="microdots" width="7" height="7" patternUnits="userSpaceOnUse"><circle cx="1.4" cy="1.4" r="0.8" fill="rgba(10,35,66,0.28)"/></pattern></defs><rect width="120" height="120" fill="url(%23microdots)"/></svg>');
            background-size: auto, auto, cover, 100px 100px;
            background-position: center;
        }

        /* Elementi decorativi geometrici */
        body::before {
            content: '';
            position: fixed;
            top: -40%;
            right: -10%;
            width: 70%;
            height: 120%;
            background: radial-gradient(ellipse, rgba(10, 35, 66, 0.14) 0%, transparent 68%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -35%;
            left: -15%;
            width: 55%;
            height: 90%;
            background: radial-gradient(ellipse, rgba(10, 35, 66, 0.10) 0%, transparent 68%);
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: var(--surface);
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 
                0 20px 40px rgba(15, 23, 42, 0.12),
                0 2px 8px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            backdrop-filter: blur(6px);
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, #143152 100%);
            padding: 32px 40px;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 48px;
            height: 3px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 2px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 4px;
            color: #fff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .login-subtitle {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.75);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 10px;
            font-weight: 500;
        }

        .login-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.08);
        }

        .form-control::placeholder {
            color: #9aa3af;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .form-hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 6px;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            margin-top: 6px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 28px 0;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .form-check-label {
            font-size: 14px;
            color: var(--muted);
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: var(--primary);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: var(--primary-strong);
            box-shadow: 0 10px 22px rgba(10, 35, 66, 0.25);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 24px;
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

        .login-footer {
            padding: 18px 40px;
            background: var(--surface-muted);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 12px;
            color: var(--muted);
        }

        .footer-version {
            font-size: 12px;
            color: var(--muted);
            background: #eef2f7;
            border: 1px solid var(--border);
            padding: 4px 10px;
            border-radius: 12px;
        }

        /* Decorazione angolare */
        .corner-decoration {
            position: fixed;
            width: 180px;
            height: 180px;
            pointer-events: none;
            opacity: 0.25;
        }

        .corner-top-left {
            top: 0;
            left: 0;
            border-top: 2px solid rgba(10, 35, 66, 0.25);
            border-left: 2px solid rgba(10, 35, 66, 0.25);
        }

        .corner-bottom-right {
            bottom: 0;
            right: 0;
            border-bottom: 2px solid rgba(10, 35, 66, 0.2);
            border-right: 2px solid rgba(10, 35, 66, 0.2);
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 28px 24px;
            }
            .login-body {
                padding: 32px 24px;
            }
            .login-footer {
                padding: 16px 24px;
            }
            .login-title {
                font-size: 24px;
                letter-spacing: 3px;
            }
            .corner-decoration {
                width: 90px;
                height: 90px;
            }
        }
    </style>
</head>
<body>
    <!-- Decorazioni angolari -->
    <div class="corner-decoration corner-top-left"></div>
    <div class="corner-decoration corner-bottom-right"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1 class="login-title">SUGECO</h1>
                <p class="login-subtitle">Sistema Unico di Gestione e Controllo</p>
            </div>

            <div class="login-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            {{ $error }}
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input
                            type="text"
                            class="form-control @error('username') is-invalid @enderror"
                            id="username"
                            name="username"
                            placeholder="Inserisci username"
                            value="{{ old('username') }}"
                            required
                            autofocus
                        >
                        <div class="form-hint">Formato: nome.cognome</div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="Inserisci password"
                            required
                        >
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ricordami su questo dispositivo</label>
                    </div>

                    <button type="submit" class="btn-login" id="submitBtn">Accedi</button>
                </form>
            </div>

            <div class="login-footer">
                <span class="footer-text">Accesso riservato al personale autorizzato</span>
                <span class="footer-version">v1.0</span>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = 'Accesso in corso...';
        });
    </script>
</body>
</html>
