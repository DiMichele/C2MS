<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUGECO</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            
            /* Background con immagine e overlay */
            background: 
                linear-gradient(135deg, rgba(10, 35, 66, 0.92) 0%, rgba(22, 58, 95, 0.88) 100%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="%230a2342"/><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            background-size: cover, 100px 100px;
            background-position: center;
        }

        /* Elementi decorativi geometrici */
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 150%;
            background: radial-gradient(ellipse, rgba(201, 162, 39, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -30%;
            left: -20%;
            width: 60%;
            height: 100%;
            background: radial-gradient(ellipse, rgba(201, 162, 39, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .login-header {
            background: linear-gradient(135deg, #0a2342 0%, #163a5f 100%);
            padding: 36px 40px;
            text-align: center;
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: #c9a227;
            border-radius: 2px;
        }

        .login-title {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #fff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .login-subtitle {
            font-size: 11px;
            color: #c9a227;
            letter-spacing: 3px;
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
            color: #0a2342;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-control:focus {
            outline: none;
            border-color: #0a2342;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .form-hint {
            font-size: 12px;
            color: #9ca3af;
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
            accent-color: #0a2342;
        }

        .form-check-label {
            font-size: 14px;
            color: #6b7280;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #0a2342 0%, #163a5f 100%);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(10, 35, 66, 0.35);
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
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 12px;
            color: #9ca3af;
        }

        .footer-version {
            font-size: 12px;
            color: #9ca3af;
            background: #e5e7eb;
            padding: 4px 10px;
            border-radius: 12px;
        }

        /* Decorazione angolare */
        .corner-decoration {
            position: fixed;
            width: 200px;
            height: 200px;
            pointer-events: none;
            opacity: 0.5;
        }

        .corner-top-left {
            top: 0;
            left: 0;
            border-top: 3px solid rgba(201, 162, 39, 0.3);
            border-left: 3px solid rgba(201, 162, 39, 0.3);
        }

        .corner-bottom-right {
            bottom: 0;
            right: 0;
            border-bottom: 3px solid rgba(201, 162, 39, 0.3);
            border-right: 3px solid rgba(201, 162, 39, 0.3);
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
                font-size: 26px;
                letter-spacing: 6px;
            }
            .corner-decoration {
                width: 100px;
                height: 100px;
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
                <p class="login-subtitle">Sistema di Gestione e Controllo</p>
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
                <span class="footer-version">v2.1</span>
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
