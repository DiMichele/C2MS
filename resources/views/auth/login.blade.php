<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SUGECO</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Oswald:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="{{ asset('vendor/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="{{ asset('vendor/css/fontawesome.min.css') }}">
    
    <style>
        :root {
            --navy: #0a2342;
            --navy-dark: #061628;
            --navy-light: #163a5f;
            --gold: #c9a227;
            --gold-light: #e4c254;
            --gold-dark: #a68920;
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
            background: #f0f2f5;
        }

        /* Left Panel - Decorative */
        .brand-panel {
            flex: 0 0 45%;
            background: linear-gradient(160deg, var(--navy-dark) 0%, var(--navy) 50%, var(--navy-light) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        /* Geometric Pattern */
        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(30deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%),
                linear-gradient(150deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%),
                linear-gradient(30deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%),
                linear-gradient(150deg, rgba(201, 162, 39, 0.03) 12%, transparent 12.5%, transparent 87%, rgba(201, 162, 39, 0.03) 87.5%);
            background-size: 80px 140px;
            background-position: 0 0, 0 0, 40px 70px, 40px 70px;
        }

        /* Decorative Lines */
        .brand-panel::after {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            transform: translateY(-50%);
            width: 3px;
            height: 200px;
            background: linear-gradient(180deg, transparent, var(--gold), transparent);
        }

        .brand-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        /* Logo Icon */
        .brand-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            box-shadow: 0 15px 40px rgba(201, 162, 39, 0.3);
            transform: rotate(-5deg);
        }

        .brand-icon i {
            font-size: 40px;
            color: var(--navy-dark);
        }

        .brand-title {
            font-family: 'Oswald', sans-serif;
            font-size: 56px;
            font-weight: 700;
            letter-spacing: 12px;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .brand-tagline {
            font-size: 14px;
            color: var(--gold);
            letter-spacing: 4px;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 50px;
        }

        /* Features List */
        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 30px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }

        .feature-item i {
            width: 36px;
            height: 36px;
            background: rgba(201, 162, 39, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gold);
            font-size: 14px;
        }

        /* Right Panel - Form */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: white;
        }

        .form-container {
            width: 100%;
            max-width: 400px;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .form-header p {
            font-size: 15px;
            color: #6c757d;
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--navy);
            margin-bottom: 10px;
            display: block;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            font-size: 16px;
            transition: color 0.2s;
        }

        .form-control {
            width: 100%;
            padding: 16px 16px 16px 48px;
            font-size: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            transition: all 0.2s ease;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--navy);
            background: white;
            box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.08);
            outline: none;
        }

        .form-control:focus + i,
        .input-wrapper:focus-within i {
            color: var(--navy);
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .invalid-feedback {
            font-size: 12px;
            color: #dc3545;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-hint {
            font-size: 12px;
            color: #adb5bd;
            margin-top: 6px;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 28px 0;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .form-check-input:checked {
            background-color: var(--navy);
            border-color: var(--navy);
        }

        .form-check-label {
            font-size: 14px;
            color: #6c757d;
            cursor: pointer;
        }

        /* Submit Button */
        .btn-login {
            width: 100%;
            padding: 16px 24px;
            font-size: 15px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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
            box-shadow: 0 10px 30px rgba(10, 35, 66, 0.25);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
        }

        .btn-login .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .btn-login.loading .spinner {
            display: block;
        }

        .btn-login.loading .btn-text,
        .btn-login.loading .btn-icon {
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
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

        /* Footer */
        .form-footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-footer-text {
            font-size: 12px;
            color: #adb5bd;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-footer-text i {
            color: var(--gold);
        }

        .version-badge {
            font-size: 11px;
            color: #6c757d;
            background: #e9ecef;
            padding: 4px 12px;
            border-radius: 20px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .brand-panel {
                display: none;
            }
            
            .form-panel {
                background: linear-gradient(160deg, var(--navy-dark) 0%, var(--navy) 100%);
            }
            
            .form-container {
                background: white;
                padding: 40px 30px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            }
        }

        @media (max-width: 480px) {
            .form-panel {
                padding: 20px;
            }
            
            .form-container {
                padding: 30px 24px;
            }
            
            .form-header h1 {
                font-size: 24px;
            }
            
            .form-footer {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }
        }

        /* Animation on load */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container {
            animation: fadeInUp 0.5s ease;
        }

        .brand-content {
            animation: fadeInUp 0.6s ease 0.1s both;
        }
    </style>
</head>
<body>
    <!-- Brand Panel -->
    <div class="brand-panel">
        <div class="brand-content">
            <div class="brand-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="brand-title">SUGECO</h1>
            <p class="brand-tagline">Sistema di Gestione e Controllo</p>
            
            <div class="feature-list">
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <span>Gestione Personale Militare</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Pianificazione Operativa</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Dashboard e Reportistica</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-lock"></i>
                    <span>Accesso Sicuro e Controllato</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Panel -->
    <div class="form-panel">
        <div class="form-container">
            <div class="form-header">
                <h1>Accedi al sistema</h1>
                <p>Inserisci le tue credenziali per continuare</p>
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
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrapper">
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
                        <i class="fas fa-user"></i>
                    </div>
                    @error('username')
                        <div class="invalid-feedback">
                            <i class="fas fa-times-circle"></i> {{ $message }}
                        </div>
                    @enderror
                    <div class="form-hint">Formato: nome.cognome</div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            class="form-control @error('password') is-invalid @enderror" 
                            id="password" 
                            name="password" 
                            placeholder="••••••••"
                            required
                        >
                        <i class="fas fa-lock"></i>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">
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
                    <i class="fas fa-arrow-right btn-icon"></i>
                    <span class="btn-text">Accedi</span>
                </button>
            </form>

            <!-- Footer -->
            <div class="form-footer">
                <span class="form-footer-text">
                    <i class="fas fa-shield-alt"></i>
                    Connessione protetta
                </span>
                <span class="version-badge">v2.1.0</span>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
        });

        // Auto-hide success alert
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
