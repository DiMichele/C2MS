# Script per fermare Ngrok Tunnel
# Uso: .\ferma-tunnel.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Red
Write-Host "ARRESTO SUGECO E TUNNEL NGROK" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

# Ferma ngrok
Write-Host "Arresto tunnel Ngrok..." -ForegroundColor Yellow
$ngrok = Get-Process -Name "ngrok" -ErrorAction SilentlyContinue

if ($ngrok) {
    $ngrok | Stop-Process -Force
    Write-Host "   ✅ Tunnel Ngrok fermato!" -ForegroundColor Green
} else {
    Write-Host "   Nessun tunnel Ngrok in esecuzione." -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "TUNNEL FERMATO" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Per riavviare:" -ForegroundColor Cyan
Write-Host ".\avvia-tunnel-ngrok.ps1" -ForegroundColor White
Write-Host ""

