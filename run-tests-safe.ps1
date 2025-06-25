# Script PowerShell per eseguire i test in modo sicuro
# Questo script crea automaticamente un backup del database prima di eseguire i test

Write-Host "🔍 Controllo configurazione test..." -ForegroundColor Blue

# Controlla se PHPUnit è configurato correttamente
$phpunitConfig = Get-Content "phpunit.xml" | Select-String "DB_CONNECTION.*sqlite"
if (-not $phpunitConfig) {
    Write-Host "⚠️  ATTENZIONE: PHPUnit non è configurato per usare SQLite in memoria!" -ForegroundColor Yellow
    $continue = Read-Host "Vuoi continuare comunque? (y/N)"
    if ($continue -ne "y" -and $continue -ne "Y") {
        Write-Host "❌ Test annullati per sicurezza" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host "✅ PHPUnit configurato correttamente per SQLite in memoria" -ForegroundColor Green
}

# Crea backup del database se la configurazione non è sicura
if (-not $phpunitConfig) {
    Write-Host "📦 Creazione backup del database..." -ForegroundColor Blue
    php artisan db:backup
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Errore nella creazione del backup!" -ForegroundColor Red
        exit 1
    }
}

Write-Host "🧪 Esecuzione test..." -ForegroundColor Blue

# Esegui i test
php artisan test

$testResult = $LASTEXITCODE

# Mostra risultato
if ($testResult -eq 0) {
    Write-Host "✅ Tutti i test sono passati!" -ForegroundColor Green
} else {
    Write-Host "❌ Alcuni test sono falliti (Exit code: $testResult)" -ForegroundColor Red
    
    # Offri il ripristino del backup se era stato creato
    if (-not $phpunitConfig) {
        Write-Host "💡 Se il database è stato modificato dai test, puoi ripristinare il backup con:" -ForegroundColor Yellow
        Write-Host "   php artisan db:backup --restore" -ForegroundColor Cyan
    }
}

Write-Host "🏁 Test completati" -ForegroundColor Blue
exit $testResult 