# C2MS Test Suite Runner
# Script PowerShell per eseguire la suite completa di test

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "   C2MS - Test Suite Execution" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host ""

# Verifica che l'ambiente sia configurato
Write-Host "🔍 Verificando ambiente..." -ForegroundColor Yellow

if (!(Test-Path "vendor/bin/phpunit")) {
    Write-Host "❌ PHPUnit non trovato. Eseguire: composer install" -ForegroundColor Red
    exit 1
}

if (!(Test-Path ".env")) {
    Write-Host "⚠️  File .env non trovato. Copiando da .env.example..." -ForegroundColor Yellow
    Copy-Item ".env.example" ".env"
    Write-Host "📝 Configurare .env per i test" -ForegroundColor Yellow
}

Write-Host "✅ Ambiente verificato" -ForegroundColor Green
Write-Host ""

# Preparazione database di test
Write-Host "🗄️  Preparando database di test..." -ForegroundColor Yellow
php artisan key:generate --env=testing --force
php artisan migrate:fresh --env=testing --force
Write-Host "✅ Database di test preparato" -ForegroundColor Green
Write-Host ""

# Esecuzione test unitari
Write-Host "🧪 Eseguendo Test Unitari..." -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Gray

$unitTestStart = Get-Date
vendor/bin/phpunit tests/Unit --testdox --colors=always --coverage-text --coverage-html=coverage/unit
$unitTestEnd = Get-Date
$unitTestDuration = $unitTestEnd - $unitTestStart

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Test Unitari completati con successo" -ForegroundColor Green
} else {
    Write-Host "❌ Test Unitari falliti" -ForegroundColor Red
}

Write-Host ""

# Esecuzione test feature
Write-Host "🌟 Eseguendo Test Feature..." -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Gray

$featureTestStart = Get-Date
vendor/bin/phpunit tests/Feature --testdox --colors=always --coverage-text --coverage-html=coverage/feature
$featureTestEnd = Get-Date
$featureTestDuration = $featureTestEnd - $featureTestStart

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Test Feature completati con successo" -ForegroundColor Green
} else {
    Write-Host "❌ Test Feature falliti" -ForegroundColor Red
}

Write-Host ""

# Esecuzione completa di tutti i test
Write-Host "🚀 Eseguendo Suite Completa..." -ForegroundColor Cyan
Write-Host "----------------------------------------" -ForegroundColor Gray

$fullTestStart = Get-Date
vendor/bin/phpunit --testdox --colors=always --coverage-text --coverage-html=coverage/full --coverage-clover=coverage/clover.xml
$fullTestEnd = Get-Date
$fullTestDuration = $fullTestEnd - $fullTestStart

if ($LASTEXITCODE -eq 0) {
    Write-Host "✅ Suite completa completata con successo" -ForegroundColor Green
    $overallResult = "SUCCESS"
} else {
    Write-Host "❌ Suite completa fallita" -ForegroundColor Red
    $overallResult = "FAILED"
}

Write-Host ""

# Report finale
Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "           REPORT FINALE" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

Write-Host "📊 Risultato Generale: " -NoNewline
if ($overallResult -eq "SUCCESS") {
    Write-Host $overallResult -ForegroundColor Green
} else {
    Write-Host $overallResult -ForegroundColor Red
}

Write-Host ""
Write-Host "⏱️  Tempi di Esecuzione:" -ForegroundColor Yellow
Write-Host "   • Test Unitari:     $($unitTestDuration.TotalSeconds.ToString('F2'))s" -ForegroundColor White
Write-Host "   • Test Feature:     $($featureTestDuration.TotalSeconds.ToString('F2'))s" -ForegroundColor White
Write-Host "   • Suite Completa:   $($fullTestDuration.TotalSeconds.ToString('F2'))s" -ForegroundColor White

Write-Host ""
Write-Host "📁 Report di Coverage:" -ForegroundColor Yellow
Write-Host "   • HTML Report:      coverage/full/index.html" -ForegroundColor White
Write-Host "   • Clover XML:       coverage/clover.xml" -ForegroundColor White

Write-Host ""

# Performance metrics
if ($fullTestDuration.TotalSeconds -lt 30) {
    Write-Host "🚀 Performance: ECCELLENTE (< 30s)" -ForegroundColor Green
} elseif ($fullTestDuration.TotalSeconds -lt 60) {
    Write-Host "⚡ Performance: BUONA (< 60s)" -ForegroundColor Yellow
} else {
    Write-Host "🐌 Performance: DA MIGLIORARE (> 60s)" -ForegroundColor Red
}

Write-Host ""

# Suggerimenti finali
Write-Host "💡 Suggerimenti:" -ForegroundColor Cyan
Write-Host "   • Aprire coverage/full/index.html per il report dettagliato" -ForegroundColor White
Write-Host "   • Eseguire test specifici: vendor/bin/phpunit tests/Unit/NomeTest.php" -ForegroundColor White
Write-Host "   • Debug test: vendor/bin/phpunit --debug tests/Feature/NomeTest.php" -ForegroundColor White

Write-Host ""
Write-Host "🎯 Test Suite C2MS completata!" -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Cyan 