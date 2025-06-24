# Setup Backup su Google Drive

Questo documento spiega come configurare i backup automatici e manuali del progetto C2MS su Google Drive.

## Opzione 1: Backup Automatico con GitHub Actions (Consigliata)

### Prerequisiti
1. Account Google con Google Drive
2. Repository GitHub (già configurato)

### Configurazione

#### 1. Crea un Service Account Google
1. Vai su [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuovo progetto o seleziona uno esistente
3. Abilita l'API Google Drive
4. Vai su "Credenziali" → "Crea credenziali" → "Account di servizio"
5. Scarica il file JSON delle credenziali

#### 2. Configura i Secrets GitHub
1. Vai su GitHub → Il tuo repository → Settings → Secrets and variables → Actions
2. Aggiungi questi secrets:
   - `GOOGLE_DRIVE_CREDENTIALS`: Il contenuto del file JSON delle credenziali
   - `GOOGLE_DRIVE_FOLDER_ID`: L'ID della cartella Google Drive dove salvare i backup

#### 3. Ottieni l'ID della cartella Google Drive
1. Crea una cartella su Google Drive chiamata "C2MS-Backups"
2. Apri la cartella e copia l'ID dall'URL (la parte dopo `/folders/`)
3. Condividi la cartella con l'email del service account (editor permissions)

### Come funziona
- Il backup si attiva automaticamente ad ogni push sul branch `main`
- Crea un archivio compresso del progetto
- Lo carica su Google Drive con timestamp
- Esclude file non necessari (.git, node_modules, cache, etc.)

## Opzione 2: Backup Manuale con Script PowerShell

### Prerequisiti
1. Google Drive Desktop installato
2. PowerShell (già presente su Windows)

### Installazione Google Drive Desktop
1. Scarica da: https://www.google.com/drive/download/
2. Installa e accedi con il tuo account Google
3. Scegli la sincronizzazione "Mirror files" o "Stream files"

### Utilizzo dello Script

#### Backup Base (senza foto militari)
```powershell
.\backup-to-drive.ps1
```

#### Backup Completo (con foto militari)
```powershell
.\backup-to-drive.ps1 -IncludeStorage
```

#### Backup in cartella personalizzata
```powershell
.\backup-to-drive.ps1 -GoogleDrivePath "C:\Users\TuoNome\Google Drive\MieiBackup\C2MS"
```

### Cosa include il backup
- ✅ Codice sorgente completo
- ✅ Database SQL
- ✅ Configurazioni (escluso .env per sicurezza)
- ✅ Documentazione
- ✅ File README con istruzioni di ripristino
- ❌ File temporanei e cache
- ❌ node_modules e vendor (ricostruibili)
- ❌ .git (già su GitHub)

## Opzione 3: Sincronizzazione Diretta (Semplice)

### Setup Rapido
1. Installa Google Drive Desktop
2. Crea una cartella "C2MS" nella tua cartella Google Drive
3. Copia manualmente i file del progetto quando necessario

### Vantaggi
- Semplice da configurare
- Sincronizzazione in tempo reale
- Accesso da qualsiasi dispositivo

### Svantaggi
- Sincronizza anche file non necessari
- Può essere lento con molti file
- Nessun controllo di versione

## Raccomandazioni

### Per uso personale
- Usa l'**Opzione 2** (Script PowerShell) per backup regolari
- Esegui il backup prima di modifiche importanti
- Mantieni almeno 3-5 backup recenti

### Per uso professionale
- Usa l'**Opzione 1** (GitHub Actions) per backup automatici
- Configura anche backup locali aggiuntivi
- Considera l'uso di più servizi cloud per ridondanza

## Automazione Backup Locale

Puoi anche creare un task schedulato per eseguire il backup automaticamente:

```powershell
# Crea un task che esegue il backup ogni giorno alle 18:00
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-File C:\xampp\htdocs\C2MS\backup-to-drive.ps1"
$trigger = New-ScheduledTaskTrigger -Daily -At 18:00
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName "C2MS-Backup" -Action $action -Trigger $trigger -Settings $settings
```

## Ripristino da Backup

1. Scarica il file ZIP del backup da Google Drive
2. Estrai in una nuova cartella
3. Leggi il file `README-BACKUP.txt` per le istruzioni specifiche
4. Configura il file `.env`
5. Importa il database
6. Esegui `composer install`
7. Esegui `php artisan key:generate`

## Troubleshooting

### "Google Drive Desktop non trovato"
- Installa Google Drive Desktop dal sito ufficiale
- Riavvia PowerShell dopo l'installazione

### "Accesso negato a Google Drive"
- Verifica che Google Drive Desktop sia loggato
- Controlla i permessi della cartella di destinazione

### Backup troppo grandi
- Usa `-IncludeStorage $false` per escludere le foto
- Pulisci i file di cache prima del backup
- Considera backup incrementali 