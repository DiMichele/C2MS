# Script per convertire PNG in ICO per Windows

Add-Type -AssemblyName System.Drawing

$pngPath = "public\images\sugeco-icon.png"
$icoPath = "public\images\sugeco-icon.ico"

Write-Host "Conversione icona PNG -> ICO..." -ForegroundColor Cyan

try {
    # Carica l'immagine PNG
    $img = [System.Drawing.Image]::FromFile((Resolve-Path $pngPath))
    
    # Crea bitmap quadrato
    $size = [Math]::Min($img.Width, $img.Height)
    $bitmap = New-Object System.Drawing.Bitmap($size, $size)
    $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
    $graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    
    # Disegna l'immagine centrata
    $x = ($size - $img.Width) / 2
    $y = ($size - $img.Height) / 2
    $graphics.DrawImage($img, $x, $y, $img.Width, $img.Height)
    
    # Salva come ICO
    $icon = [System.Drawing.Icon]::FromHandle($bitmap.GetHicon())
    $fileStream = [System.IO.File]::Create((Resolve-Path "public\images").Path + "\sugeco-icon.ico")
    $icon.Save($fileStream)
    $fileStream.Close()
    
    Write-Host "OK Icona convertita con successo!" -ForegroundColor Green
    Write-Host "Salvata in: $icoPath" -ForegroundColor Green
    
    # Pulisci risorse
    $graphics.Dispose()
    $bitmap.Dispose()
    $img.Dispose()
    
} catch {
    Write-Host "ERRORE durante la conversione: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "ALTERNATIVA: Usa un convertitore online" -ForegroundColor Yellow
    Write-Host "1. Vai su: https://convertio.co/it/png-ico/" -ForegroundColor Cyan
    Write-Host "2. Carica: public\images\sugeco-icon.png" -ForegroundColor Cyan
    Write-Host "3. Scarica il file .ico nella stessa cartella" -ForegroundColor Cyan
}
