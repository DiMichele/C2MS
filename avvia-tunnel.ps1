# Script per avviare Cloudflare Tunnel
# Uso: .\avvia-tunnel.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "AVVIO SUGECO CON CLOUDFLARE TUNNEL" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""

Write-Host "1. Verifica XAMPP..." -ForegroundColor Yellow

# Verifica che XAMPP Apache sia attivo
$apache = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if ($apache) {
    Write-Host "   ✅ XAMPP Apache attivo" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  XAMPP non in esecuzione. Avvia XAMPP prima di continuare!" -ForegroundColor Red
    Write-Host "   Premi un tasto per uscire..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    exit
}
Write-Host ""

$cfPath = "$env:TEMP\cloudflared.exe"

# Verifica se cloudflared esiste
if (-not (Test-Path $cfPath)) {
    Write-Host "Scarico Cloudflare Tunnel..." -ForegroundColor Yellow
    Write-Host ""
    $downloadUrl = "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
    Invoke-WebRequest -Uri $downloadUrl -OutFile $cfPath -UseBasicParsing
    Write-Host "Download completato!" -ForegroundColor Green
    Write-Host ""
}

Write-Host "   Attendi 5-10 secondi per ottenere URL..." -ForegroundColor Cyan
Write-Host ""

# Avvia il tunnel verso XAMPP
Write-Host "2. Avvio tunnel Cloudflare..." -ForegroundColor Yellow
$job = Start-Job -ScriptBlock { 
    param($path) 
    & $path tunnel --url http://localhost:80 2>&1 
} -ArgumentList $cfPath

# Aspetta l'URL
Start-Sleep -Seconds 8

# Recupera l'output
$output = Receive-Job $job

# Cerca l'URL nel output
$urlLine = $output | Where-Object { $_ -match "https://.*\.trycloudflare\.com" }

if ($urlLine -match "(https://[^\s]+\.trycloudflare\.com)") {
    $tunnelUrl = $matches[1]
    
    # Salva l'URL in un file per recuperarlo facilmente
    $tunnelUrl | Out-File -FilePath "tunnel-url.txt" -Encoding UTF8
    
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "TUNNEL ATTIVO!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "URL Pubblico:" -ForegroundColor Yellow
    Write-Host "$tunnelUrl" -ForegroundColor Cyan
    
    Write-Host ""
    Write-Host "Server Locale: http://localhost/SUGECO/public" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Copia l'URL pubblico e usalo da qualsiasi dispositivo!" -ForegroundColor White
    Write-Host ""
    Write-Host "Per fermare tutto:" -ForegroundColor Yellow
    Write-Host "Esegui: .\ferma-tunnel.ps1" -ForegroundColor Gray
    Write-Host "Oppure premi CTRL+C in questa finestra" -ForegroundColor Gray
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Green
    
    # Mantieni il tunnel attivo
    Write-Host ""
    Write-Host "Tunnel in esecuzione... (Premi CTRL+C per fermare)" -ForegroundColor Cyan
    Write-Host ""
    Wait-Job $job
} else {
    Write-Host "Non riesco a recuperare URL automaticamente." -ForegroundColor Yellow
    Write-Host "Controlla output qui sotto per trovare URL:" -ForegroundColor Cyan
    Write-Host ""
    $output | Select-Object -Last 15
    
    # Mantieni il tunnel attivo comunque
    Wait-Job $job
}
