# C2MS - Sistema di Gestione Militare Digitale

Sistema completo per la gestione digitale delle informazioni militari, certificati, presenze e organigramma.

## Caratteristiche

- **Gestione Militari**: Anagrafica completa con foto profilo, gradi, ruoli e mansioni
- **Certificati**: Gestione certificati lavoratori e idoneità con scadenze
- **Presenze**: Sistema di controllo presenze giornaliere
- **Organigramma**: Visualizzazione gerarchica dell'organizzazione
- **Board Attività**: Gestione attività e progetti con Kanban board
- **Valutazioni**: Sistema di valutazione militari con note
- **Eventi**: Gestione eventi e attività programmate

## Requisiti

- **XAMPP** (Apache + MySQL + PHP)
- PHP 8.1+
- MySQL 5.7+
- Composer

## Installazione

### 1. Clona il repository
```bash
git clone https://github.com/DiMichele/C2MS.git
cd C2MS
```

### 2. Installa le dipendenze PHP
```bash
composer install
```

### 3. Configura l'ambiente
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configura il database
Modifica il file `.env` con le credenziali del tuo database:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=c2ms
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Importa il database
```bash
# Crea il database
mysql -u root -p -e "CREATE DATABASE c2ms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importa i dati
mysql -u root -p c2ms < database/sql/c2ms_database.sql
```

### 6. Esegui le migrazioni (se necessario)
```bash
php artisan migrate
```

### 7. Popola il database con dati di test
```bash
php artisan db:seed
```

### 8. Crea le cartelle per i militari
```bash
php artisan militari:create-directories
```

### 9. Crea il link simbolico per lo storage
```bash
php artisan storage:link
```

### 10. Accedi all'applicazione
Apri il browser e vai su: `http://localhost/C2MS`

## Dati di Test Inclusi

Il database include:
- **20 militari** con gradi, ruoli e mansioni
- **3 plotoni** e **7 poli** organizzativi
- **Certificati** con stati realistici (attivi, in scadenza, scaduti)
- **Idoneità** e valutazioni
- **Eventi** e attività di esempio
- **Presenze** simulate

## Struttura del Progetto

```
C2MS/
├── app/
│   ├── Console/Commands/     # Comandi Artisan personalizzati
│   ├── Http/Controllers/     # Controller MVC
│   ├── Models/              # Modelli Eloquent
│   ├── Services/            # Servizi business logic
│   └── Traits/              # Traits riutilizzabili
├── database/
│   ├── migrations/          # Migrazioni database
│   ├── seeders/            # Seeder per dati di test
│   └── sql/                # Dump database
├── resources/views/         # Viste Blade
├── public/                 # File pubblici
└── storage/                # File upload e cache
```

## Funzionalità Principali

### Gestione Militari
- Anagrafica completa con foto profilo
- Gestione gradi, ruoli e mansioni
- Sistema di valutazioni e note
- Certificati e idoneità

### Organigramma
- Visualizzazione gerarchica
- Cache automatica
- Filtri per reparto/grado

### Board Attività
- Kanban board per gestione progetti
- Drag & drop per spostamento attività
- Allegati e link
- Assegnazione militari

### Certificati
- Gestione certificati lavoratori
- Sistema idoneità
- Controllo scadenze
- Export dati

## Sicurezza

**Nota**: Questo sistema è progettato per uso interno e non include sistema di autenticazione. È destinato a un singolo utente amministratore.

## Licenza

Questo progetto è sviluppato per uso interno.

## Sviluppatore

**Michele Di Gennaro**
- GitHub: [@DiMichele](https://github.com/DiMichele)

---

**C2MS** - Sistema di Gestione Militare Digitale v1.0
