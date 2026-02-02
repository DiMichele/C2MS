# SUGECO - Sistema Unico di Gestione e Controllo
*Sistema Unico di Gestione e Controllo*

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-11.x-red)](https://laravel.com)
[![License](https://img.shields.io/badge/License-Proprietary-yellow)](LICENSE)

## 📋 Panoramica

SUGECO è un sistema completo di gestione militare digitale progettato per ottimizzare la gestione del personale, certificazioni, presenze e operazioni. Il sistema fornisce un'interfaccia moderna e intuitiva per il controllo completo delle attività di comando.

## ✨ Caratteristiche Principali

### 🎖️ **Gestione Militari**
- Anagrafica completa (nome, cognome, grado, ruolo, mansione)
- Organizzazione per plotoni e poli
- Upload e gestione foto profilo
- Sistema valutazioni con punteggi numerici
- Note multiple (generali, certificati, idoneità, aggiuntive)

### 📜 **Sistema Certificazioni**
- **Certificati Lavoratori**: corso lavoratori, preposti, dirigenti
- **Idoneità**: PEFO, porto d'armi, guida, sanitarie, varie
- Stati: attivo, in scadenza (30gg), scaduto, non posseduto
- Upload file PDF/immagini per ogni certificato
- Filtri per grado, plotone, mansione, stato

### 📊 **Board Attività Kanban**
- Colonne personalizzabili (Todo, In Progress, Review, Done)
- Drag & drop per spostamento attività
- Gestione date inizio/fine
- Assegnazione militari alle attività
- Upload allegati con gestione file
- Vista calendario delle attività

### 📈 **Dashboard Operativa**
- KPI in tempo reale (totale militari, presenti/assenti oggi)
- Ricerca rapida militari
- Grafico a ciambella presenze
- Cache per performance
- Lista certificati/idoneità in scadenza

### 🏗️ **Organigramma**
- Visualizzazione gerarchica Compagnia → Plotone → Polo
- Conteggi militari per livello
- Indicatori presenza/assenza
- Cache automatica

### 📅 **Eventi e Presenze**
- Sistema presenze giornaliere (Presente/Assente)
- Gestione eventi con date e località
- Controllo sovrapposizioni eventi/assenze
- Creazione eventi per militari multipli

### 🗂️ **Gestione Assenze**
- Creazione e gestione assenze
- Controllo conflitti con eventi
- Tipologie assenze personalizzabili

## 🛠️ Stack Tecnologico

### Backend
- **Laravel 11.x** - Framework PHP
- **PHP 8.2+** - Linguaggio di programmazione
- **MySQL 5.7+** - Database relazionale
- **Eloquent ORM** - Mappatura oggetti-relazionale

### Frontend
- **Blade Templates** - Engine di templating
- **TailwindCSS** - Framework CSS
- **JavaScript ES6+** - Scripting lato client
- **Drag & Drop API** - Interfacce interattive

### Sviluppo e Test
- **PHPUnit 11.x** - Testing framework
- **Faker** - Generazione dati di test
- **Vite** - Build tool e asset bundling
- **Composer** - Gestione dipendenze PHP

## 📦 Requisiti di Sistema

- **XAMPP** (Apache + MySQL + PHP) o stack equivalente
- **PHP 8.2+** con estensioni: PDO, mbstring, fileinfo, gd
- **MySQL 5.7+** o MariaDB 10.3+
- **Composer 2.x** - Gestione dipendenze
- **Node.js 18+** e **npm** - Per asset building (opzionale)

## 🚀 Installazione

### 1. Clone del Repository
```bash
git clone https://github.com/DiMichele/SUGECO.git
cd SUGECO
```

### 2. Installazione Dipendenze
```bash
# Dipendenze PHP
composer install

# Dipendenze JavaScript (opzionale)
npm install
```

### 3. Configurazione Ambiente
```bash
# Copia il file di configurazione
cp .env.example .env

# Genera la chiave di cifratura dell'applicazione
php artisan key:generate
```

### 4. Configurazione Database
Modifica il file `.env` con le credenziali del database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=c2ms
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Setup Database
```bash
# Crea il database
mysql -u root -p -e "CREATE DATABASE c2ms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Opzione A: Importa database completo con dati
mysql -u root -p c2ms < database/sql/c2ms_database.sql

# Opzione B: Migrazione con seeder
php artisan migrate:fresh --seed
```

### 6. Configurazione Storage e Permessi
```bash
# Crea le cartelle per i militari
php artisan militari:create-directories

# Crea il link simbolico per lo storage
php artisan storage:link

# Imposta i permessi (Linux/Mac)
chmod -R 775 storage bootstrap/cache
```

### 7. Ottimizzazione (Produzione)
```bash
# Cache delle configurazioni
php artisan config:cache

# Cache delle rotte
php artisan route:cache

# Cache delle viste
php artisan view:cache

# Ottimizzazione Composer
composer install --optimize-autoloader --no-dev
```

### 8. Accesso all'Applicazione
Apri il browser e vai su: `http://localhost/SUGECO/public`

### 9. Gerarchia organizzativa (11° Reggimento Trasmissioni)
Per allineare la struttura organizzativa alla gerarchia del **11° Reggimento Trasmissioni** (Comando di Reggimento, Battaglione Leonessa, Battaglione Tonale, CCSL, Comando alla Sede), eseguire la migrazione gerarchia da zero:

```bash
php artisan hierarchy:migrate --fresh
```

Per ricreare anche le assegnazioni militari alle unità:

```bash
php artisan hierarchy:migrate --fresh --with-assignments
```

**Nota:** `--fresh` cancella la gerarchia esistente (unit_closure, unit_assignments, organizational_units) e ricrea la struttura 11°. I dati di militari, utenti e altre tabelle non vengono modificati.

## 🗂️ Struttura del Progetto

```
SUGECO/
├── app/
│   ├── Console/Commands/         # Comandi Artisan personalizzati
│   │   ├── BackupDatabase.php    # Backup automatico database
│   │   ├── CreateMilitariDirectories.php
│   │   ├── ImportPresenze.php    # Import dati presenze
│   │   └── RefreshOrganigrammaCache.php
│   ├── Http/Controllers/         # Controller MVC
│   │   ├── AssenzeController.php # Gestione assenze
│   │   ├── BoardController.php   # Kanban board attività
│   │   ├── CertificatiController.php # Certificazioni
│   │   ├── DashboardController.php # Dashboard principale
│   │   ├── EventiController.php  # Eventi e calendario
│   │   ├── MilitareController.php # Gestione militari
│   │   ├── NoteController.php    # Note aggiuntive
│   │   └── OrganigrammaController.php
│   ├── Models/                   # Modelli Eloquent
│   │   ├── Militare.php         # Modello principale militare
│   │   ├── BoardActivity.php    # Attività board
│   │   ├── CertificatiLavoratori.php
│   │   ├── Idoneita.php         # Idoneità operative
│   │   ├── Evento.php           # Eventi e attività
│   │   └── [altri 14+ modelli]
│   ├── Services/                 # Business Logic
│   │   ├── CertificatiService.php # Logica certificazioni
│   │   └── MilitareService.php   # Logica militari
│   └── Traits/                   # Trait riutilizzabili
│       ├── CertificatoTrait.php  # Funzionalità certificati
│       └── DateRangeFilterable.php
├── database/
│   ├── migrations/              # Migrazioni database (13 file)
│   ├── seeders/                 # Seeder dati di test
│   │   ├── MilitariSeeder.php   # 20+ militari realistici
│   │   ├── BoardActivitiesSeeder.php
│   │   └── [altri 6 seeder]
│   └── sql/                     # Dump database completo
├── resources/views/             # Viste Blade organizzate
│   ├── militare/               # Gestione militari
│   ├── certificati/            # Gestione certificazioni
│   ├── board/                  # Kanban board
│   ├── components/             # Componenti riutilizzabili
│   └── layouts/                # Layout principali
├── public/                     # File pubblici
│   ├── css/                    # Stili personalizzati
│   └── js/                     # JavaScript applicazione
├── tests/                      # Suite di test completa
│   ├── Unit/                   # Test unitari (5 file)
│   └── TestCase.php            # Base class test
└── storage/                    # File upload e cache
```

## 🧹 File Obsoleti da Rimuovere

### File di Test e Debug
- `public/test-routing.php` - File di test routing non più necessario
- `docs/ottimizzazione_blade.md` - Documentazione obsoleta delle ottimizzazioni Blade

### Comandi per Pulizia
```bash
# Rimuovi file obsoleti
rm public/test-routing.php
rm -rf docs/

# Verifica cartelle vuote
find . -type d -empty -name "partials" -path "*/bacheca/*"
```

## 📊 Entità del Database (19 Modelli)

### **Entità Principali**
- `Militare` - Entità centrale con anagrafica completa
- `Grado` - Gerarchia militare (Soldato → Maggiore)
- `Plotone/Polo` - Organizzazione strutturale operativa
- `Ruolo/Mansione` - Funzioni e specializzazioni

### **Sistema Certificazioni**
- `CertificatiLavoratori` - Corso lavoratori, preposti, dirigenti
- `Idoneita` - PEFO, porto d'armi, guida, sanitarie, varie
- `RuoloCertificati` - Collegamento ruoli-certificati

### **Gestione Operativa**
- `Evento` - Eventi e missioni programmate
- `Assenza` - Permessi e assenze militari
- `Presenza` - Tracciamento presenze giornaliere
- `MilitareValutazione` - Sistema valutazioni numeriche

### **Board Kanban**
- `BoardActivity` - Attività e progetti
- `BoardColumn` - Colonne organizzative
- `ActivityAttachment` - Documenti allegati

### **Sistema Organizzativo**
- `Compagnia` - Livello organizzativo superiore
- `Nota` - Note aggiuntive sui militari
- `User` - Utente sistema (monoutente)

## 🧪 Sistema di Test

### Test Unitari (5 file, 70+ test)
- **MilitareModelTest.php**: Relazioni, scopes, metodi business
- **MilitareServiceTest.php**: Filtri, ricerca, CRUD
- **CertificatiServiceTest.php**: Logica certificazioni
- **EventoModelTest.php**: Gestione eventi
- **GradoModelTest.php**: Gerarchia militare

### Esecuzione Test
```bash
# Test completi con coverage
./run-tests.ps1

# Test sicuri (backup automatico)
./run-tests-safe.ps1

# Test specifici
vendor/bin/phpunit tests/Unit/MilitareModelTest.php
```

## 📊 Dati di Test Inclusi

Il sistema include un dataset completo per test e demo:
- **20+ Militari** con gradi realistici (da Soldato a Maggiore)
- **3 Plotoni** organizzativi con assegnazioni
- **7 Poli** operativi specializzati
- **Certificati** con stati diversificati e scadenze
- **Idoneità** operative complete
- **Eventi** e attività programmate
- **Valutazioni** strutturate e note operative

## ⚙️ Comandi Artisan Personalizzati

```bash
# Backup database automatico
php artisan db:backup

# Creazione directory militari
php artisan militari:create-directories

# Import presenze da file
php artisan presenze:import

# Refresh cache organigramma
php artisan organigramma:refresh-cache

# Migrazione file militari
php artisan militari:migrate-files
```

## 🔧 Configurazione Avanzata

### Cache e Performance
```bash
# Ottimizzazione cache completa
php artisan optimize

# Clear cache completa
php artisan optimize:clear
```

### Backup e Sicurezza
- Backup automatico database prima dei test
- Storage privato per documenti sensibili
- Validazione input completa
- Gestione errori strutturata

### Environment Variables Chiave
```env
# Database
DB_CONNECTION=mysql
DB_DATABASE=c2ms

# Cache (per performance)
CACHE_DRIVER=file
SESSION_DRIVER=database

# File Storage
FILESYSTEM_DISK=local
```

## 🔒 Sicurezza e Note

> **Sistema Monoutente**: SUGECO è progettato per uso interno da parte di un singolo operatore. Non include sistema di autenticazione multi-utente per semplicità d'uso in ambiente controllato.

### Misure di Sicurezza Implementate
- Validazione rigorosa input utente
- Protezione CSRF su tutti i form
- Storage privato per documenti sensibili
- Sanitizzazione output per prevenire XSS

## 📈 Roadmap e Sviluppi Futuri

- [ ] **API REST**: Endpoint per integrazioni esterne
- [ ] **Mobile App**: Versione mobile nativa
- [ ] **Reportistica Avanzata**: Export PDF e Excel
- [ ] **Integrazione Calendar**: Sincronizzazione calendario esterno
- [ ] **Sistema Notifiche**: Email e push notifications
- [ ] **Multi-tenant**: Supporto per più compagnie

## 🤝 Contributi e Supporto

### Sviluppatore Principale
**Michele Di Gennaro**
- GitHub: [@DiMichele](https://github.com/DiMichele)
- Email: michele.digennaro@example.com

### Come Contribuire
1. Fork del repository
2. Creazione branch feature (`git checkout -b feature/nuova-funzionalita`)
3. Commit delle modifiche (`git commit -am 'Aggiunta nuova funzionalità'`)
4. Push del branch (`git push origin feature/nuova-funzionalita`)
5. Creazione Pull Request

### Segnalazione Bug
Usa il sistema di [Issues GitHub](https://github.com/DiMichele/SUGECO/issues) per segnalare bug o richiedere nuove funzionalità.

## 📝 Licenza

Questo progetto è sviluppato per uso interno e proprietario. Tutti i diritti riservati.

---

**SUGECO v1.0** - *Sistema Unico di Gestione e Controllo*

*Sviluppato con ❤️ per l'efficienza operativa*
