# Script per creare collegamento SUGECO sul Desktop (versione semplice)

$DesktopPath = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $DesktopPath "SUGECO.lnk"
$TargetUrl = "http://localhost/SUGECO/public/"

Write-Host "Creazione collegamento SUGECO sul Desktop..." -ForegroundColor Cyan

# Crea oggetto WScript.Shell
$WshShell = New-Object -ComObject WScript.Shell
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)

# Verifica browser disponibili
$browsers = @{
    "Chrome" = "C:\Program Files\Google\Chrome\Application\chrome.exe"
    "ChromeX86" = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
    "Edge" = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
    "Firefox" = "C:\Program Files\Mozilla Firefox\firefox.exe"
}

$browserFound = $false
foreach ($browser in $browsers.GetEnumerator()) {
    if (Test-Path $browser.Value) {
        $Shortcut.TargetPath = $browser.Value
        $Shortcut.Arguments = "--app=$TargetUrl"
        Write-Host "OK Browser trovato: $($browser.Key)" -ForegroundColor Green
        $browserFound = $true
        break
    }
}

if (-not $browserFound) {
    # Usa browser predefinito
    $Shortcut.TargetPath = "rundll32.exe"
    $Shortcut.Arguments = "url.dll,FileProtocolHandler $TargetUrl"
    Write-Host "Uso browser predefinito del sistema" -ForegroundColor Yellow
}

$Shortcut.WorkingDirectory = "C:\xampp\htdocs\SUGECO\public"
$Shortcut.Description = "SUGECO - Sistema Unico di Gestione e Controllo"
$Shortcut.Save()

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "   COLLEGAMENTO CREATO CON SUCCESSO!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Nome: SUGECO" -ForegroundColor Cyan
Write-Host "Posizione: Desktop" -ForegroundColor Cyan
Write-Host "URL: $TargetUrl" -ForegroundColor Cyan
Write-Host ""
Write-Host "Il collegamento usera' l'icona del browser." -ForegroundColor Yellow
Write-Host "Per icona personalizzata, leggi: ISTRUZIONI-ICONA.txt" -ForegroundColor Yellow
Write-Host ""


