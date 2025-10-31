# Script per avviare Cloudflare Tunnel
# Uso: .\avvia-tunnel.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "AVVIO CLOUDFLARE TUNNEL" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
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

Write-Host "Avvio tunnel..." -ForegroundColor Yellow
Write-Host "Attendi 5-10 secondi per ottenere URL..." -ForegroundColor Cyan
Write-Host ""

# Avvia il tunnel
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
    
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "TUNNEL ATTIVO!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "URL Pubblico:" -ForegroundColor Yellow
    Write-Host "$tunnelUrl/SUGECO/public" -ForegroundColor Cyan
    
    Write-Host ""
    Write-Host "Copia questo URL e usalo da qualsiasi dispositivo!" -ForegroundColor White
    Write-Host ""
    Write-Host "Per fermare il tunnel:" -ForegroundColor Yellow
    Write-Host "Premi CTRL+C o chiudi questa finestra PowerShell" -ForegroundColor Gray
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
