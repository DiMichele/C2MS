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
            background: var(--navy);
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        /* Header */
        .login-header {
            background: var(--navy);
            padding: 32px 30px;
            text-align: center;
        }

        .login-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 42px;
            font-weight: 700;
            letter-spacing: 8px;
            color: white;
            margin-bottom: 6px;
        }

        .login-subtitle {
            font-size: 12px;
            color: var(--gold);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Body */
        .login-body {
            padding: 32px 30px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 6px;
        }

        .form-control {
            padding: 12px 14px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            border-color: var(--navy);
            box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-right: none;
            color: #666;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--navy);
        }

        .form-check-input:checked {
            background-color: var(--navy);
            border-color: var(--navy);
        }

        .form-check-label {
            font-size: 13px;
            color: #666;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: white;
            background: var(--navy);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-login:hover {
            background: var(--navy-light);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login i {
            margin-right: 8px;
        }

        /* Alerts */
        .alert {
            padding: 12px 14px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .invalid-feedback {
            font-size: 12px;
            margin-top: 4px;
        }

        .form-hint {
            font-size: 11px;
            color: #888;
            margin-top: 4px;
        }

        /* Footer */
        .login-footer {
            padding: 16px 30px;
            background: #f8f9fa;
            text-align: center;
            border-top: 1px solid #eee;
        }

        .login-footer-text {
            font-size: 11px;
            color: #888;
            margin: 0;
        }

        /* Loading */
        .btn-login.loading {
            opacity: 0.7;
            pointer-events: none;
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
            <!-- Success Message -->
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif

            <!-- Error Messages -->
            @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    @foreach($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf

                <!-- Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
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
                    </div>
                    @error('username')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Formato: nome.cognome</div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••"
                            required
                        >
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ricordami</label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-login" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>Accedi
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p class="login-footer-text">Accesso riservato al personale autorizzato</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').classList.add('loading');
        });
    </script>
</body>
</html>
