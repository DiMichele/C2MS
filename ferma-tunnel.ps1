# Script per fermare Cloudflare Tunnel
# Uso: ./ferma-tunnel.ps1

Write-Host "`n========================================" -ForegroundColor Red
Write-Host "🛑 FERMO CLOUDFLARE TUNNEL" -ForegroundColor Red
Write-Host "========================================`n" -ForegroundColor Red

# Ferma tutti i processi cloudflared
$cloudflared = Get-Process | Where-Object {$_.ProcessName -like "*cloudflared*"}

if ($cloudflared) {
    $cloudflared | Stop-Process -Force
    Write-Host "✅ Tunnel Cloudflare fermato!" -ForegroundColor Green
    Write-Host "   Il sito non è più accessibile online.`n" -ForegroundColor White
} else {
    Write-Host "⚠️  Nessun tunnel Cloudflare in esecuzione.`n" -ForegroundColor Yellow
}

# Ferma anche ngrok se presente
$ngrok = Get-Process | Where-Object {$_.ProcessName -like "*ngrok*"}

if ($ngrok) {
    $ngrok | Stop-Process -Force
    Write-Host "✅ Tunnel Ngrok fermato!" -ForegroundColor Green
}

Write-Host "========================================" -ForegroundColor Green
Write-Host "📊 Stato: Sito OFFLINE da internet" -ForegroundColor Red
Write-Host "💻 Localhost: http://localhost/SUGECO/public" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

