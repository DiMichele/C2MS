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

# Cerca cloudflared in vari percorsi
$cfPath = $null

# 1. Verifica se è nel PATH
$cfInPath = Get-Command cloudflared -ErrorAction SilentlyContinue
if ($cfInPath) {
    $cfPath = $cfInPath.Source
    Write-Host "   ✅ Cloudflared trovato nel PATH: $cfPath" -ForegroundColor Green
} else {
    # 2. Verifica in TEMP
    $cfPath = "$env:TEMP\cloudflared.exe"
    if (Test-Path $cfPath) {
        Write-Host "   ✅ Cloudflared trovato in TEMP" -ForegroundColor Green
    } else {
        # 3. Scarica cloudflared
        Write-Host "2. Download Cloudflare Tunnel..." -ForegroundColor Yellow
        Write-Host ""
        
        # Rimuovi file vecchio se esiste
        if (Test-Path $cfPath) {
            Remove-Item $cfPath -Force -ErrorAction SilentlyContinue
        }
        
        try {
            $downloadUrl = "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
            Write-Host "   Download da: $downloadUrl" -ForegroundColor Gray
            Invoke-WebRequest -Uri $downloadUrl -OutFile $cfPath -UseBasicParsing -TimeoutSec 60
            Write-Host "   ✅ Download completato!" -ForegroundColor Green
            
            # Verifica che il file sia valido
            if (-not (Test-Path $cfPath)) {
                throw "File non scaricato correttamente"
            }
            
            # Verifica dimensione file (dovrebbe essere > 10MB)
            $fileSize = (Get-Item $cfPath).Length
            if ($fileSize -lt 10MB) {
                throw "File scaricato troppo piccolo, potrebbe essere corrotto"
            }
            
            Write-Host "   Dimensione file: $([math]::Round($fileSize/1MB, 2)) MB" -ForegroundColor Gray
        } catch {
            Write-Host "   ❌ Errore durante il download: $_" -ForegroundColor Red
            Write-Host ""
            Write-Host "   Soluzione alternativa:" -ForegroundColor Yellow
            Write-Host "   1. Scarica manualmente cloudflared da:" -ForegroundColor White
            Write-Host "      https://github.com/cloudflare/cloudflared/releases" -ForegroundColor Cyan
            Write-Host "   2. Salva come: $cfPath" -ForegroundColor White
            Write-Host "   3. Rilancia questo script" -ForegroundColor White
            Write-Host ""
            exit 1
        }
        Write-Host ""
    }
}

Write-Host "3. Avvio tunnel Cloudflare..." -ForegroundColor Yellow
Write-Host "   Attendi 5-10 secondi per ottenere URL..." -ForegroundColor Cyan
Write-Host ""

# Verifica che cloudflared sia eseguibile
try {
    $testRun = & $cfPath --version 2>&1
    if ($LASTEXITCODE -ne 0 -and $testRun -notmatch "cloudflared") {
        throw "Cloudflared non eseguibile"
    }
} catch {
    Write-Host "   ❌ Errore: cloudflared non è eseguibile su questo sistema" -ForegroundColor Red
    Write-Host "   File: $cfPath" -ForegroundColor Gray
    Write-Host ""
    Write-Host "   Soluzione:" -ForegroundColor Yellow
    Write-Host "   1. Rimuovi il file: $cfPath" -ForegroundColor White
    Write-Host "   2. Rilancia questo script per scaricare una nuova versione" -ForegroundColor White
    Write-Host ""
    exit 1
}

# Avvia il tunnel verso XAMPP
$job = Start-Job -ScriptBlock { 
    param($path) 
    try {
        & $path tunnel --url http://localhost:80 2>&1
    } catch {
        Write-Error "Errore esecuzione cloudflared: $_"
    }
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
