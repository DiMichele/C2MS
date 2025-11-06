# Script per fermare Cloudflare Tunnel e Server Laravel
# Uso: .\ferma-tunnel.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Red
Write-Host "ARRESTO SUGECO E TUNNEL" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Red
Write-Host ""

# 1. Ferma il tunnel Cloudflare
Write-Host "1. Arresto tunnel Cloudflare..." -ForegroundColor Yellow
$cloudflared = Get-Process -Name "cloudflared" -ErrorAction SilentlyContinue

if ($cloudflared) {
    $cloudflared | Stop-Process -Force
    Write-Host "   Tunnel Cloudflare fermato!" -ForegroundColor Green
} else {
    Write-Host "   Nessun tunnel Cloudflare in esecuzione." -ForegroundColor Gray
}

Write-Host ""

# 2. Ferma anche ngrok se presente
$ngrok = Get-Process -Name "ngrok" -ErrorAction SilentlyContinue

if ($ngrok) {
    $ngrok | Stop-Process -Force
    Write-Host "   Tunnel Ngrok fermato!" -ForegroundColor Green
}

Write-Host "========================================" -ForegroundColor Green
Write-Host "TUTTI I SERVIZI SONO STATI FERMATI" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Per riavviare:" -ForegroundColor Cyan
Write-Host ".\avvia-tunnel.ps1" -ForegroundColor White
Write-Host ""

