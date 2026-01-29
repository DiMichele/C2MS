# Script per avviare Ngrok Tunnel
# Uso: .\avvia-tunnel-ngrok.ps1

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "AVVIO SUGECO CON NGROK TUNNEL" -ForegroundColor Green
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

# Cerca ngrok
$ngrokPath = $null

# 1. Verifica se è nel PATH
$ngrokInPath = Get-Command ngrok -ErrorAction SilentlyContinue
if ($ngrokInPath) {
    $ngrokPath = $ngrokInPath.Source
    Write-Host "2. ✅ Ngrok trovato: $ngrokPath" -ForegroundColor Green
} else {
    Write-Host "   ❌ Ngrok non trovato nel PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "   Soluzione:" -ForegroundColor Yellow
    Write-Host "   1. Installa ngrok da: https://ngrok.com/download" -ForegroundColor White
    Write-Host "   2. Oppure aggiungi ngrok al PATH" -ForegroundColor White
    Write-Host ""
    exit 1
}

Write-Host ""
Write-Host "3. Verifica autenticazione ngrok..." -ForegroundColor Yellow

# Verifica se ngrok è autenticato (controlla entrambi i percorsi: nuovo e legacy)
$ngrokConfigNew = "$env:LOCALAPPDATA\ngrok\ngrok.yml"
$ngrokConfigOld = "$env:USERPROFILE\.ngrok2\ngrok.yml"

if ((Test-Path $ngrokConfigNew) -or (Test-Path $ngrokConfigOld)) {
    Write-Host "   ✅ Ngrok autenticato" -ForegroundColor Green
} else {
    Write-Host "   ⚠️  Ngrok non sembra essere autenticato" -ForegroundColor Yellow
    Write-Host "   Per autenticarti:" -ForegroundColor Cyan
    Write-Host "   1. Vai su https://dashboard.ngrok.com/get-started/your-authtoken" -ForegroundColor White
    Write-Host "   2. Copia il tuo authtoken" -ForegroundColor White
    Write-Host "   3. Esegui: ngrok config add-authtoken <TUO_TOKEN>" -ForegroundColor White
    Write-Host ""
    Write-Host "   Continuo comunque (potrebbe funzionare senza autenticazione)..." -ForegroundColor Gray
}

Write-Host ""
Write-Host "4. Avvio tunnel Ngrok..." -ForegroundColor Yellow
Write-Host "   Attendi 3-5 secondi per ottenere URL..." -ForegroundColor Cyan
Write-Host ""

# Ferma eventuali tunnel ngrok già attivi
$existingNgrok = Get-Process -Name "ngrok" -ErrorAction SilentlyContinue
if ($existingNgrok) {
    Write-Host "   Fermo tunnel ngrok esistenti..." -ForegroundColor Yellow
    $existingNgrok | Stop-Process -Force
    Start-Sleep -Seconds 2
}

# Avvia ngrok verso XAMPP (porta 80)
$ngrokProcess = Start-Process -FilePath $ngrokPath -ArgumentList "http", "80" -NoNewWindow -PassThru -RedirectStandardOutput "$env:TEMP\ngrok-output.txt" -RedirectStandardError "$env:TEMP\ngrok-error.txt"

# Aspetta che ngrok si avvii
Start-Sleep -Seconds 5

# Leggi l'output di ngrok dall'API locale
try {
    $ngrokApi = Invoke-RestMethod -Uri "http://127.0.0.1:4040/api/tunnels" -ErrorAction Stop
    $publicUrl = $ngrokApi.tunnels[0].public_url
    
    if ($publicUrl) {
        # Salva l'URL in un file
        $publicUrl | Out-File -FilePath "tunnel-url.txt" -Encoding UTF8
        
        Write-Host "========================================" -ForegroundColor Green
        Write-Host "TUNNEL ATTIVO!" -ForegroundColor Green
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        
        Write-Host "URL Pubblico:" -ForegroundColor Yellow
        Write-Host "$publicUrl" -ForegroundColor Cyan
        
        Write-Host ""
        Write-Host "Server Locale: http://localhost/SUGECO/public" -ForegroundColor Gray
        Write-Host ""
        Write-Host "Copia l'URL pubblico e usalo da qualsiasi dispositivo!" -ForegroundColor White
        Write-Host ""
        Write-Host "Dashboard Ngrok: http://127.0.0.1:4040" -ForegroundColor Gray
        Write-Host ""
        Write-Host "Per fermare tutto:" -ForegroundColor Yellow
        Write-Host "Esegui: .\ferma-tunnel.ps1" -ForegroundColor Gray
        Write-Host "Oppure premi CTRL+C in questa finestra" -ForegroundColor Gray
        Write-Host ""
        Write-Host "========================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "Tunnel in esecuzione... (Premi CTRL+C per fermare)" -ForegroundColor Cyan
        Write-Host ""
        
        # Mantieni il processo attivo
        Wait-Process -Id $ngrokProcess.Id -ErrorAction SilentlyContinue
    } else {
        throw "URL non trovato"
    }
} catch {
    Write-Host "   ⚠️  Non riesco a recuperare l'URL automaticamente" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "   Soluzione:" -ForegroundColor Cyan
    Write-Host "   1. Apri il browser e vai su: http://127.0.0.1:4040" -ForegroundColor White
    Write-Host "   2. Trova l'URL pubblico nella dashboard ngrok" -ForegroundColor White
    Write-Host ""
    Write-Host "   Oppure controlla l'output qui sotto:" -ForegroundColor Cyan
    Write-Host ""
    
    if (Test-Path "$env:TEMP\ngrok-output.txt") {
        Get-Content "$env:TEMP\ngrok-output.txt" | Select-Object -Last 20
    }
    if (Test-Path "$env:TEMP\ngrok-error.txt") {
        Get-Content "$env:TEMP\ngrok-error.txt" | Select-Object -Last 20
    }
    
    Write-Host ""
    Write-Host "Tunnel potrebbe essere attivo comunque. Controlla la dashboard!" -ForegroundColor Yellow
    Write-Host ""
    
    # Mantieni il processo attivo
    Wait-Process -Id $ngrokProcess.Id -ErrorAction SilentlyContinue
}

