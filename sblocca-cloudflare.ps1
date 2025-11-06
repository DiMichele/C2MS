# Script per sbloccare Cloudflare Tunnel
# Richiede privilegi di amministratore

# Verifica se è amministratore
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "Richiedo privilegi di amministratore..." -ForegroundColor Yellow
    Start-Process powershell -Verb RunAs -ArgumentList "-NoExit", "-Command", "cd '$PWD'; .\sblocca-cloudflare.ps1"
    exit
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "SBLOCCO CLOUDFLARE TUNNEL" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

$cfPath = "$env:TEMP\cloudflared.exe"

Write-Host "Aggiungo eccezione in Windows Defender..." -ForegroundColor Yellow
try {
    Add-MpPreference -ExclusionPath $cfPath -ErrorAction Stop
    Write-Host "Eccezione aggiunta con successo!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Ora puoi chiudere questa finestra e lanciare:" -ForegroundColor Cyan
    Write-Host ".\avvia-tunnel.ps1" -ForegroundColor White
} catch {
    Write-Host "Errore durante l'aggiunta dell'eccezione:" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

Write-Host ""
Write-Host "Premi un tasto per chiudere..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

