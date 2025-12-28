# ============================================
# SCRIPT: Scarica risorse per uso OFFLINE/INTRANET
# ============================================
# Questo script scarica tutte le librerie esterne
# per permettere al sito di funzionare senza internet
# ============================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " SUGECO - Download Risorse Offline" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Crea le cartelle necessarie
$folders = @(
    "public/vendor/css",
    "public/vendor/js", 
    "public/vendor/fonts",
    "public/vendor/webfonts"
)

foreach ($folder in $folders) {
    if (!(Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
        Write-Host "[+] Creata cartella: $folder" -ForegroundColor Green
    }
}

# Funzione per scaricare file con retry
function Download-File {
    param($url, $output)
    
    $maxRetries = 3
    $retry = 0
    
    while ($retry -lt $maxRetries) {
        try {
            Write-Host "    Scaricando: $output" -ForegroundColor Gray
            Invoke-WebRequest -Uri $url -OutFile $output -TimeoutSec 30
            
            if ((Get-Item $output).Length -gt 0) {
                Write-Host "    [OK] $output" -ForegroundColor Green
                return $true
            }
        } catch {
            $retry++
            Write-Host "    [!] Tentativo $retry fallito per $output" -ForegroundColor Yellow
        }
    }
    
    Write-Host "    [X] ERRORE: $output" -ForegroundColor Red
    return $false
}

Write-Host ""
Write-Host "1. Scaricando Bootstrap 5.3.0..." -ForegroundColor Yellow
Download-File "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" "public/vendor/css/bootstrap.min.css"
Download-File "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" "public/vendor/js/bootstrap.bundle.min.js"

Write-Host ""
Write-Host "2. Scaricando jQuery 3.6.0..." -ForegroundColor Yellow
Download-File "https://code.jquery.com/jquery-3.6.0.min.js" "public/vendor/js/jquery.min.js"

Write-Host ""
Write-Host "3. Scaricando Font Awesome 6.4.0..." -ForegroundColor Yellow
Download-File "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" "public/vendor/css/fontawesome.min.css"

# Font Awesome webfonts (necessari per le icone)
$faFonts = @(
    "fa-solid-900.woff2",
    "fa-solid-900.ttf",
    "fa-regular-400.woff2",
    "fa-regular-400.ttf",
    "fa-brands-400.woff2",
    "fa-brands-400.ttf"
)

foreach ($font in $faFonts) {
    Download-File "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/$font" "public/vendor/webfonts/$font"
}

Write-Host ""
Write-Host "4. Scaricando Select2..." -ForegroundColor Yellow
Download-File "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" "public/vendor/css/select2.min.css"
Download-File "https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" "public/vendor/js/select2.min.js"
Download-File "https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" "public/vendor/css/select2-bootstrap-5-theme.min.css"

Write-Host ""
Write-Host "5. Scaricando Sortable.js..." -ForegroundColor Yellow
Download-File "https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js" "public/vendor/js/sortable.min.js"

Write-Host ""
Write-Host "6. Scaricando FullCalendar..." -ForegroundColor Yellow
Download-File "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" "public/vendor/css/fullcalendar.min.css"
Download-File "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js" "public/vendor/js/fullcalendar.min.js"
Download-File "https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/it.min.js" "public/vendor/js/fullcalendar-it.min.js"

Write-Host ""
Write-Host "7. Scaricando Google Fonts (Roboto, Oswald)..." -ForegroundColor Yellow

# Scarica i font di Google
$googleFontsCSS = @"
/* Roboto - Self-hosted */
@font-face {
    font-family: 'Roboto';
    font-style: normal;
    font-weight: 300;
    font-display: swap;
    src: url('../fonts/roboto-v30-latin-300.woff2') format('woff2');
}
@font-face {
    font-family: 'Roboto';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('../fonts/roboto-v30-latin-regular.woff2') format('woff2');
}
@font-face {
    font-family: 'Roboto';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('../fonts/roboto-v30-latin-500.woff2') format('woff2');
}
@font-face {
    font-family: 'Roboto';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('../fonts/roboto-v30-latin-700.woff2') format('woff2');
}

/* Oswald - Self-hosted */
@font-face {
    font-family: 'Oswald';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('../fonts/oswald-v49-latin-regular.woff2') format('woff2');
}
@font-face {
    font-family: 'Oswald';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('../fonts/oswald-v49-latin-500.woff2') format('woff2');
}
@font-face {
    font-family: 'Oswald';
    font-style: normal;
    font-weight: 600;
    font-display: swap;
    src: url('../fonts/oswald-v49-latin-600.woff2') format('woff2');
}
@font-face {
    font-family: 'Oswald';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('../fonts/oswald-v49-latin-700.woff2') format('woff2');
}
"@

$googleFontsCSS | Out-File -FilePath "public/vendor/css/google-fonts.css" -Encoding UTF8

# Scarica i file font da Google Fonts Helper (gwfh.mranftl.com)
$robotoFonts = @(
    @{url="https://fonts.gstatic.com/s/roboto/v30/KFOlCnqEu92Fr1MmSU5fBBc4.woff2"; file="roboto-v30-latin-300.woff2"},
    @{url="https://fonts.gstatic.com/s/roboto/v30/KFOmCnqEu92Fr1Mu4mxK.woff2"; file="roboto-v30-latin-regular.woff2"},
    @{url="https://fonts.gstatic.com/s/roboto/v30/KFOlCnqEu92Fr1MmEU9fBBc4.woff2"; file="roboto-v30-latin-500.woff2"},
    @{url="https://fonts.gstatic.com/s/roboto/v30/KFOlCnqEu92Fr1MmWUlfBBc4.woff2"; file="roboto-v30-latin-700.woff2"}
)

$oswaldFonts = @(
    @{url="https://fonts.gstatic.com/s/oswald/v53/TK3_WkUHHAIjg75cFRf3bXL8LICs1_FvsUZiZQ.woff2"; file="oswald-v49-latin-regular.woff2"},
    @{url="https://fonts.gstatic.com/s/oswald/v53/TK3_WkUHHAIjg75cFRf3bXL8LICs18NvsUZiZQ.woff2"; file="oswald-v49-latin-500.woff2"},
    @{url="https://fonts.gstatic.com/s/oswald/v53/TK3_WkUHHAIjg75cFRf3bXL8LICs1y9osUZiZQ.woff2"; file="oswald-v49-latin-600.woff2"},
    @{url="https://fonts.gstatic.com/s/oswald/v53/TK3_WkUHHAIjg75cFRf3bXL8LICs1xZosUZiZQ.woff2"; file="oswald-v49-latin-700.woff2"}
)

foreach ($font in $robotoFonts) {
    Download-File $font.url "public/vendor/fonts/$($font.file)"
}

foreach ($font in $oswaldFonts) {
    Download-File $font.url "public/vendor/fonts/$($font.file)"
}

Write-Host ""
Write-Host "8. Correggendo percorsi Font Awesome..." -ForegroundColor Yellow

# Correggi i percorsi dei webfonts in fontawesome.min.css
$faCSS = Get-Content "public/vendor/css/fontawesome.min.css" -Raw
$faCSS = $faCSS -replace '../webfonts/', '../webfonts/'
$faCSS | Out-File -FilePath "public/vendor/css/fontawesome.min.css" -Encoding UTF8
Write-Host "    [OK] Percorsi corretti" -ForegroundColor Green

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Download completato!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Ora esegui: php artisan view:clear" -ForegroundColor Yellow
Write-Host ""

