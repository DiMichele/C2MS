# ============================================
# SCRIPT: Configura SUGECO per Intranet
# ============================================
# Eseguire come Amministratore!
# ============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " SUGECO - Configurazione Intranet" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Verifica se eseguito come amministratore
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "[!] ATTENZIONE: Esegui questo script come Amministratore!" -ForegroundColor Red
    Write-Host "    Clicca destro su PowerShell -> 'Esegui come amministratore'" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Premi INVIO per uscire"
    exit
}

# 1. Trova IP del computer
Write-Host "1. Rilevamento indirizzo IP..." -ForegroundColor Yellow
$ipAddresses = Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notmatch "Loopback" -and $_.IPAddress -notmatch "^169\." }
$mainIP = ($ipAddresses | Where-Object { $_.PrefixOrigin -eq "Dhcp" -or $_.PrefixOrigin -eq "Manual" } | Select-Object -First 1).IPAddress

if (-not $mainIP) {
    $mainIP = $ipAddresses[0].IPAddress
}

Write-Host "   [OK] IP del server: $mainIP" -ForegroundColor Green
Write-Host ""

# 2. Configura Firewall Windows
Write-Host "2. Configurazione Firewall Windows..." -ForegroundColor Yellow

# Rimuovi regole esistenti (se presenti)
Remove-NetFirewallRule -DisplayName "SUGECO HTTP" -ErrorAction SilentlyContinue
Remove-NetFirewallRule -DisplayName "SUGECO HTTPS" -ErrorAction SilentlyContinue
Remove-NetFirewallRule -DisplayName "Apache HTTP" -ErrorAction SilentlyContinue

# Crea nuove regole
New-NetFirewallRule -DisplayName "SUGECO HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow -Profile Any | Out-Null
New-NetFirewallRule -DisplayName "Apache HTTP" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow -Program "C:\xampp\apache\bin\httpd.exe" -Profile Any | Out-Null

Write-Host "   [OK] Firewall configurato (porta 80 aperta)" -ForegroundColor Green
Write-Host ""

# 3. Configura Apache per accettare connessioni esterne
Write-Host "3. Configurazione Apache..." -ForegroundColor Yellow

$httpdConf = "C:\xampp\apache\conf\httpd.conf"
$httpdVhosts = "C:\xampp\apache\conf\extra\httpd-vhosts.conf"

# Verifica che Apache sia configurato per ascoltare su tutte le interfacce
$httpdContent = Get-Content $httpdConf -Raw
if ($httpdContent -match "Listen 127.0.0.1:80") {
    $httpdContent = $httpdContent -replace "Listen 127.0.0.1:80", "Listen 80"
    $httpdContent | Set-Content $httpdConf -Encoding UTF8
    Write-Host "   [OK] Apache configurato per accettare connessioni esterne" -ForegroundColor Green
} else {
    Write-Host "   [OK] Apache gia' configurato correttamente" -ForegroundColor Green
}

# 4. Configura VirtualHost per SUGECO
Write-Host ""
Write-Host "4. Configurazione VirtualHost SUGECO..." -ForegroundColor Yellow

$vhostConfig = @"

# ================================
# SUGECO - Virtual Host Intranet
# ================================
<VirtualHost *:80>
    ServerName sugeco.local
    ServerAlias $mainIP localhost
    DocumentRoot "C:/xampp/htdocs/SUGECO/public"
    
    <Directory "C:/xampp/htdocs/SUGECO/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/sugeco-error.log"
    CustomLog "logs/sugeco-access.log" common
</VirtualHost>
"@

# Verifica se il vhost esiste gia'
$vhostsContent = Get-Content $httpdVhosts -Raw -ErrorAction SilentlyContinue
if ($vhostsContent -notmatch "SUGECO") {
    Add-Content -Path $httpdVhosts -Value $vhostConfig
    Write-Host "   [OK] VirtualHost SUGECO aggiunto" -ForegroundColor Green
} else {
    Write-Host "   [OK] VirtualHost SUGECO gia' presente" -ForegroundColor Green
}

# 5. Configura Laravel .env
Write-Host ""
Write-Host "5. Configurazione Laravel..." -ForegroundColor Yellow

$envFile = "C:\xampp\htdocs\SUGECO\.env"
if (Test-Path $envFile) {
    $envContent = Get-Content $envFile -Raw
    
    # Aggiorna APP_URL
    $envContent = $envContent -replace "APP_URL=http://localhost[^\r\n]*", "APP_URL=http://$mainIP/SUGECO/public"
    
    $envContent | Set-Content $envFile -Encoding UTF8
    Write-Host "   [OK] APP_URL aggiornato a http://$mainIP/SUGECO/public" -ForegroundColor Green
}

# 6. Riavvia Apache
Write-Host ""
Write-Host "6. Riavvio Apache..." -ForegroundColor Yellow

$apacheService = Get-Service -Name "Apache2.4" -ErrorAction SilentlyContinue
if ($apacheService) {
    Restart-Service -Name "Apache2.4"
    Write-Host "   [OK] Apache riavviato" -ForegroundColor Green
} else {
    # Prova con XAMPP Control Panel
    Write-Host "   [!] Riavvia Apache manualmente dal pannello XAMPP" -ForegroundColor Yellow
}

# Riepilogo finale
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " CONFIGURAZIONE COMPLETATA!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host " Gli altri utenti possono connettersi a:" -ForegroundColor White
Write-Host ""
Write-Host "   http://$mainIP/SUGECO/public" -ForegroundColor Yellow
Write-Host ""
Write-Host " Oppure (se imposti DNS/hosts):" -ForegroundColor White
Write-Host ""
Write-Host "   http://sugeco.local" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "NOTA: Assicurati che:" -ForegroundColor White
Write-Host "  1. XAMPP Apache sia avviato" -ForegroundColor Gray
Write-Host "  2. MySQL sia avviato" -ForegroundColor Gray
Write-Host "  3. I PC siano nella stessa rete" -ForegroundColor Gray
Write-Host ""

Read-Host "Premi INVIO per terminare"

