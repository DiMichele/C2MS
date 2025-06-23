<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            max-width: 600px;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .error-header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .error-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .error-body {
            padding: 30px;
            background-color: white;
        }
        .error-message {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .error-details {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin-bottom: 20px;
        }
        .btn-back {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        .btn-back:hover {
            background-color: #0b5ed7;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-header">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>Si è verificato un errore</h1>
            </div>
            <div class="error-body">
                <div class="error-message">
                    @if(isset($exception))
                        {{ $exception->getMessage() ?: 'Si è verificato un errore imprevisto.' }}
                    @else
                        Si è verificato un errore imprevisto.
                    @endif
                </div>
                
                @if(config('app.debug') && isset($exception))
                    <div class="error-details">
                        {{ $exception->getTraceAsString() }}
                    </div>
                @endif
                
                <div class="text-center">
                    <a href="{{ url('/') }}" class="btn-back">
                        <i class="fas fa-home me-2"></i> Torna alla home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
