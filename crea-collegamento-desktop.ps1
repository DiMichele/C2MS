# Script per creare collegamento SUGECO sul Desktop con icona personalizzata

# Percorsi
$DesktopPath = [Environment]::GetFolderPath("Desktop")
$ShortcutPath = Join-Path $DesktopPath "SUGECO.lnk"
$TargetUrl = "http://localhost/SUGECO/public/"
$IconPath = "C:\xampp\htdocs\SUGECO\public\images\sugeco-icon.ico"

# Crea oggetto WScript.Shell
$WshShell = New-Object -ComObject WScript.Shell

# Crea il collegamento
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
$Shortcut.Arguments = "--app=$TargetUrl"
$Shortcut.WorkingDirectory = "C:\xampp\htdocs\SUGECO\public"
$Shortcut.Description = "SUGECO - Sistema Unico di Gestione e Controllo"

# Imposta l'icona se esiste
if (Test-Path $IconPath) {
    $Shortcut.IconLocation = $IconPath
    Write-Host "OK Icona applicata: $IconPath" -ForegroundColor Green
} else {
    Write-Host "ATTENZIONE Icona non trovata. Usa l'icona predefinita." -ForegroundColor Yellow
    Write-Host "Salva l'icona come: $IconPath" -ForegroundColor Yellow
}

# Salva il collegamento
$Shortcut.Save()

Write-Host ""
Write-Host "OK Collegamento creato sul Desktop!" -ForegroundColor Green
Write-Host "Nome: SUGECO" -ForegroundColor Cyan
Write-Host "URL: $TargetUrl" -ForegroundColor Cyan
Write-Host ""

# Verifica se Chrome e' installato
if (-not (Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe")) {
    Write-Host "ATTENZIONE Chrome non trovato. Il collegamento usera' il browser predefinito." -ForegroundColor Yellow
    $Shortcut.TargetPath = "rundll32.exe"
    $Shortcut.Arguments = "url.dll,FileProtocolHandler $TargetUrl"
    $Shortcut.Save()
}
