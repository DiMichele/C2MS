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
            background: #0a2342;
            padding: 20px;
        }

        .login-card {
            background: #fff;
            width: 100%;
            max-width: 400px;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .login-header {
            background: #0a2342;
            padding: 32px 40px;
            text-align: center;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 6px;
            color: #fff;
            margin: 0;
        }

        .login-subtitle {
            font-size: 11px;
            color: #c9a227;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 8px;
        }

        .login-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px 14px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0a2342;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-hint {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            margin-top: 4px;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 24px 0;
        }

        .form-check-input {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .form-check-label {
            font-size: 13px;
            color: #666;
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            background: #0a2342;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover {
            background: #163a5f;
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 4px;
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

        .login-footer {
            padding: 16px 40px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-text {
            font-size: 11px;
            color: #999;
        }

        .footer-version {
            font-size: 11px;
            color: #999;
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 24px 24px;
            }
            .login-body {
                padding: 30px 24px;
            }
            .login-footer {
                padding: 14px 24px;
            }
            .login-title {
                font-size: 24px;
                letter-spacing: 4px;
            }
        }
    </style>
</head>
<body>
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
                        placeholder="nome.cognome"
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
                        placeholder="••••••••"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ricordami</label>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">Accedi</button>
            </form>
        </div>

        <div class="login-footer">
            <span class="footer-text">Accesso riservato</span>
            <span class="footer-version">v2.1.0</span>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').textContent = 'Accesso in corso...';
        });
    </script>
</body>
</html>
