# SUGECO - Guida alla Sicurezza

Questa guida documenta tutte le misure di sicurezza implementate nel progetto SUGECO e fornisce istruzioni per la corretta configurazione in ambiente di produzione.

## Indice

1. [Vulnerabilità Risolte](#vulnerabilità-risolte)
2. [Middleware di Sicurezza](#middleware-di-sicurezza)
3. [Configurazione Apache](#configurazione-apache)
4. [Configurazione HTTPS](#configurazione-https)
5. [Checklist di Produzione](#checklist-di-produzione)
6. [Monitoraggio e Manutenzione](#monitoraggio-e-manutenzione)
7. [Sistema di Audit Log](#sistema-di-audit-log)

---

## Vulnerabilità Risolte

### 1. Apache /server-info e /server-status

**Problema:** Endpoint che espongono informazioni sensibili sulla configurazione del server.

**Soluzione:**
- Bloccati a livello `.htaccess` con regole RewriteRule
- Bloccati a livello Apache in `config/apache-security.conf`

### 2. Directory Listing

**Problema:** Apache mostra il contenuto delle directory senza file index.

**Soluzione:**
- `Options -Indexes` in tutti i file `.htaccess`
- Configurazione globale in `apache-security.conf`

### 3. HTTP senza Redirect a HTTPS

**Problema:** Le credenziali vengono trasmesse in chiaro su HTTP.

**Soluzione:**
- Middleware `ForceHttps` che reindirizza automaticamente a HTTPS in produzione
- Regole `.htaccess` per redirect a livello Apache (commentate, da abilitare)
- Variabile d'ambiente `FORCE_HTTPS` per controllo granulare

### 4. Credenziali in Chiaro (Form Login)

**Problema:** Il form di login invia credenziali su connessione non criptata.

**Soluzione:**
- Risolto automaticamente con il redirect HTTPS
- Header HSTS per forzare HTTPS nelle connessioni future

### 5. Script CGI di Test (printenv.pl)

**Problema:** Script CGI che espone variabili d'ambiente del server.

**Soluzione:**
- Bloccato accesso a `/cgi-bin/` nei file `.htaccess`
- Bloccati file `.pl` e `.cgi` a livello Apache
- Disabilitata esecuzione CGI (`Options -ExecCGI`)

### 6. VirtualHost Misconfiguration

**Problema:** Il server risponde a richieste con Host header manipolato.

**Soluzione:**
- Validazione Host header nel `.htaccess` principale
- Lista di host permessi (localhost, IP locali, tunnel configurati)
- Configurazione VirtualHost di default in `apache-security.conf`

---

## Middleware di Sicurezza

### SecurityHeaders

**File:** `app/Http/Middleware/SecurityHeaders.php`

Aggiunge header di sicurezza HTTP a tutte le risposte:

| Header | Valore | Protezione |
|--------|--------|------------|
| X-Frame-Options | SAMEORIGIN | Clickjacking |
| X-Content-Type-Options | nosniff | MIME sniffing |
| X-XSS-Protection | 1; mode=block | XSS (legacy) |
| Referrer-Policy | strict-origin-when-cross-origin | Information leakage |
| Permissions-Policy | geolocation=(), ... | Feature policy |
| Content-Security-Policy | default-src 'self'; ... | XSS, injection |
| Strict-Transport-Security | max-age=31536000 | Downgrade attacks |

### ForceHttps

**File:** `app/Http/Middleware/ForceHttps.php`

- Reindirizza HTTP → HTTPS in produzione
- Configurabile tramite `FORCE_HTTPS` in `.env`
- Supporta proxy (ngrok, Cloudflare, load balancer)
- Esclude endpoint di health check

---

## Configurazione Apache

### Metodo 1: Include del file di configurazione

1. Apri `C:\xampp\apache\conf\httpd.conf`
2. Aggiungi alla fine del file:

```apache
Include "C:/xampp/htdocs/SUGECO/config/apache-security.conf"
```

3. Riavvia Apache dal XAMPP Control Panel

### Metodo 2: Modifica diretta di httpd.conf

Se preferisci non usare Include, applica manualmente queste modifiche:

#### Disabilita server-info e server-status

Cerca e commenta queste righe:

```apache
# LoadModule info_module modules/mod_info.so
# LoadModule status_module modules/mod_status.so
```

Oppure nega l'accesso:

```apache
<Location /server-info>
    Require all denied
</Location>

<Location /server-status>
    Require all denied
</Location>
```

#### Disabilita CGI

```apache
<Directory "C:/xampp/cgi-bin">
    Options -ExecCGI
    Require all denied
</Directory>
```

#### Nascondi informazioni server

```apache
ServerTokens Prod
ServerSignature Off
TraceEnable Off
```

---

## Configurazione HTTPS

### Sviluppo Locale (XAMPP)

1. Abilita SSL in XAMPP:
   - Apri `C:\xampp\apache\conf\httpd.conf`
   - Decommenta: `LoadModule ssl_module modules/mod_ssl.so`
   - Decommenta: `Include conf/extra/httpd-ssl.conf`

2. XAMPP include certificati self-signed in `C:\xampp\apache\conf\ssl.crt`

3. Accedi tramite `https://localhost/SUGECO/public`

### Produzione

1. Ottieni un certificato SSL (Let's Encrypt gratuito o commerciale)

2. Configura il VirtualHost SSL:

```apache
<VirtualHost *:443>
    ServerName sugeco.tuodominio.com
    DocumentRoot "C:/path/to/SUGECO/public"
    
    SSLEngine on
    SSLCertificateFile "/path/to/certificate.crt"
    SSLCertificateKeyFile "/path/to/private.key"
    SSLCertificateChainFile "/path/to/chain.crt"
    
    # Protocolli sicuri
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder on
    
    <Directory "C:/path/to/SUGECO/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Configura `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sugeco.tuodominio.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
```

---

## Checklist di Produzione

### Prima del Deploy

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Genera nuova `APP_KEY`: `php artisan key:generate`
- [ ] `FORCE_HTTPS=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Configura `ALLOWED_HOSTS` con i domini permessi
- [ ] Cambia credenziali database (no root senza password!)
- [ ] Configura un sistema di backup del database

### Configurazione Apache

- [ ] Applica `config/apache-security.conf`
- [ ] Verifica che server-info e server-status siano disabilitati
- [ ] Verifica che directory listing sia disabilitato
- [ ] Configura certificato SSL valido
- [ ] Abilita HSTS decommendando la riga in `.htaccess`

### Test di Sicurezza

- [ ] Esegui nuovamente la scansione di vulnerabilità
- [ ] Verifica header di sicurezza: https://securityheaders.com
- [ ] Verifica configurazione SSL: https://www.ssllabs.com/ssltest/
- [ ] Testa redirect HTTP → HTTPS
- [ ] Testa accesso a /server-info, /server-status (devono essere bloccati)

### Monitoraggio

- [ ] Configura logging degli errori
- [ ] Configura alerting per errori critici
- [ ] Implementa backup automatici
- [ ] Pianifica aggiornamenti di sicurezza regolari

---

## Monitoraggio e Manutenzione

### Log di Sicurezza

I log di Apache si trovano in `C:\xampp\apache\logs\`:
- `error.log` - Errori del server
- `access.log` - Tutte le richieste

### Aggiornamenti

1. **Laravel e Dipendenze:**
   ```bash
   composer update
   php artisan migrate
   ```

2. **XAMPP/Apache:**
   - Scarica l'ultima versione da https://www.apachefriends.org
   - Backup della configurazione prima dell'aggiornamento

### Scansioni Periodiche

Esegui scansioni di vulnerabilità regolari:
- Tool gratuiti: OWASP ZAP, Nikto
- Servizi online: Acunetix, Qualys

---

---

## Note Tecniche sulla Sicurezza

### Cookie XSRF-TOKEN e HttpOnly

Il cookie `XSRF-TOKEN` di Laravel **NON** ha il flag `HttpOnly`. Questo è **intenzionale** e non è una vulnerabilità.

**Perché?**

Laravel usa il meccanismo "Double Submit Cookie" per la protezione CSRF:

1. Il server genera un token CSRF e lo salva nella sessione
2. Lo stesso token viene inviato come cookie `XSRF-TOKEN` (leggibile da JavaScript)
3. JavaScript legge il cookie e lo invia come header `X-XSRF-TOKEN` nelle richieste AJAX
4. Il server verifica che l'header corrisponda al token in sessione

Se `XSRF-TOKEN` avesse `HttpOnly`, JavaScript non potrebbe leggerlo e tutte le richieste AJAX fallirebbero.

**Protezioni implementate:**

- Il cookie di **sessione** (`sugeco_session`) ha `HttpOnly` = true (protegge i dati della sessione)
- Entrambi i cookie hanno `SameSite=Lax` (protezione CSRF aggiuntiva)
- In produzione, entrambi hanno `Secure=true` (solo HTTPS)

### HTTP TRACE/TRACK

Il metodo HTTP TRACE è disabilitato a tre livelli:

1. **Apache globale:** `TraceEnable Off` in `apache-security.conf`
2. **Rewrite rules:** Blocco in `.htaccess` principale e `public/.htaccess`
3. **Sottodirectory:** Blocco in tutti i file `.htaccess` delle sottodirectory

### Pagine di Errore

Sono state create pagine di errore personalizzate che:

- Non espongono informazioni tecniche (stack trace, path, versioni)
- Forniscono messaggi user-friendly
- Mantengono il branding dell'applicazione
- Funzionano anche quando l'applicazione non è completamente caricata (503)

Pagine create: `403.blade.php`, `404.blade.php`, `419.blade.php`, `500.blade.php`, `503.blade.php`

---

## Sistema di Audit Log

### Panoramica

SUGECO include un sistema completo di **Audit Log** che traccia tutte le operazioni degli utenti nel sistema. Questo permette di:

- Sapere **chi** ha fatto **cosa** e **quando**
- Tracciare modifiche ai dati sensibili (militari, utenti, certificati)
- Monitorare accessi e tentativi di accesso falliti
- Garantire accountability e compliance

### Accesso ai Log

Il registro attività è accessibile dal menu: **Admin** → **Registro Attività**

Solo gli utenti con ruolo **Admin** possono visualizzare i log.

### Eventi Tracciati

| Evento | Icona | Descrizione |
|--------|-------|-------------|
| **Accesso** | 🟢 | Login riuscito al sistema |
| **Uscita** | ⚫ | Logout dal sistema |
| **Accesso fallito** | 🔴 | Tentativo di login con credenziali errate |
| **Creazione** | 🔵 | Creazione di un nuovo record |
| **Modifica** | 🔷 | Aggiornamento di un record esistente |
| **Eliminazione** | 🔴 | Eliminazione di un record |
| **Esportazione** | 🟡 | Download di dati in Excel/CSV |
| **Cambio password** | 🟡 | Modifica della password utente |
| **Modifica permessi** | 🟣 | Cambio di ruolo o permessi |
| **Errore Sistema** | 🔴 | Errori applicativi (500, eccezioni non gestite) |

### Tracciamento Errori Automatico

Il sistema traccia automaticamente tutti gli **errori applicativi** (errori 500, eccezioni non gestite) nel registro attività. Questo include:

- **URL che ha generato l'errore**
- **Utente coinvolto** (se autenticato)
- **Tipo di eccezione** e messaggio
- **File e riga** dove si è verificato l'errore
- **Stack trace riassuntivo**

**Eccezioni NON tracciate** (per evitare rumore):
- Errori di autenticazione (redirect al login)
- Errori di autorizzazione (403)
- Pagine non trovate (404)
- Errori di validazione form
- Token CSRF scaduti (419)

Per vedere gli errori nel registro:
1. Vai in **Admin** → **Registro Attività**
2. Filtra per **Stato** → **Fallito**
3. Oppure filtra per **Tipo Dato** → **Sistema**

### Controller Integrati

L'audit è integrato in questi controller:

| Controller | Operazioni Tracciate |
|------------|---------------------|
| **MilitareController** | Creazione, modifica, eliminazione militari; upload foto; modifica patenti; esportazione Excel |
| **AdminController** | Gestione utenti (CRUD); reset password; gestione ruoli e permessi |
| **BoardController** | Creazione, modifica, eliminazione attività; assegnazione/rimozione militari |
| **ProfileController** | Cambio password personale |
| **ScadenzeController** | Aggiornamento scadenze certificati |
| **PianificazioneController** | Modifiche al CPT |
| **LoginController** | Login, logout, tentativi falliti |

### Interfaccia del Registro

L'interfaccia offre:

- **Statistiche rapide**: Operazioni oggi, accessi, errori, utenti attivi
- **Filtri avanzati**: Per utente, tipo azione, data, compagnia, tipo dato
- **Ricerca testuale**: Cerca per descrizione, nome utente, IP
- **Dettaglio operazione**: Visualizza vecchi/nuovi valori per le modifiche
- **Esportazione CSV**: Scarica i log filtrati

### Uso Programmatico

Per registrare eventi nei tuoi controller:

```php
use App\Services\AuditService;

// Creazione
AuditService::logCreate($entity, "Descrizione opzionale");

// Modifica
AuditService::logUpdate($entity, $oldValues, $newValues, "Descrizione opzionale");

// Eliminazione
AuditService::logDelete($entity, "Descrizione opzionale");

// Esportazione
AuditService::logExport('tipo_dato', $count, "Descrizione opzionale");

// Cambio password
AuditService::logPasswordChange($user);

// Evento generico
AuditService::log('action', 'descrizione', $entity, ['metadata' => $data]);
```

### Manutenzione e Archiviazione dei Log

#### Gestione a Lungo Termine

Con il tempo, i log di audit possono crescere fino a centinaia di migliaia di record, causando:
- **Performance degradate**: Query più lente sul database
- **Spazio su disco**: Occupazione crescente
- **Usabilità**: Difficoltà nel trovare informazioni rilevanti

**Soluzione implementata**: Sistema automatico di archiviazione e rotazione.

#### Archiviazione Automatica

Il sistema include un comando schedulato che:

1. **Archivia** i log vecchi in file CSV (prima di eliminarli)
2. **Elimina** automaticamente i log più vecchi di X giorni
3. **Mantiene** solo i log recenti nel database per performance ottimali

**Configurazione**:
- Retention: **365 giorni** (1 anno) - configurabile
- Frequenza pulizia: **Mensile** (primo giorno del mese alle 2:00)
- Archivio: File CSV salvati in `storage/app/archives/audit-logs/`

#### Esecuzione Manuale

Per pulire manualmente i log:

```bash
# Simulazione (dry-run) - mostra cosa verrebbe fatto senza eseguire
php artisan audit:clean --days=365 --archive=true --dry-run

# Esecuzione reale con archiviazione
php artisan audit:clean --days=365 --archive=true

# Esecuzione senza archiviazione (elimina direttamente)
php artisan audit:clean --days=365 --archive=false

# Esecuzione senza conferma
php artisan audit:clean --days=365 --archive=true --force

# Usa configurazione da config/audit.php
php artisan audit:clean
```

**Test del sistema**:

```bash
# 1. Verifica che il comando esista
php artisan list | grep audit

# 2. Simula la pulizia (non elimina nulla)
php artisan audit:clean --days=30 --archive=true --dry-run

# 3. Verifica i file archiviati
ls -lh storage/app/archives/audit-logs/
```

#### Configurazione Retention

Modifica `.env` per cambiare la retention policy:

```env
# Mantieni i log per 2 anni invece di 1
AUDIT_RETENTION_DAYS=730

# Disabilita archiviazione (elimina direttamente)
AUDIT_ARCHIVE_BEFORE_DELETE=false
```

#### File Archiviati

I file CSV archiviati sono salvati in:
```
storage/app/archives/audit-logs/
```

Formato nome file: `audit_logs_YYYY-MM-DD_YYYY-MM-DD_HHMMSS.csv`

**Consiglio**: Esegui backup periodici della directory `archives/` su supporto esterno o cloud.

#### Statistiche e Monitoraggio

Il comando fornisce statistiche dettagliate:
- Numero di log eliminati
- Spazio liberato (stimato)
- File di archivio creati
- Log rimanenti nel database

#### Scheduler Laravel

Per attivare la pulizia automatica, configura il cron job:

```bash
# Aggiungi al crontab del server
* * * * * cd /path/to/SUGECO && php artisan schedule:run >> /dev/null 2>&1
```

**Nota**: Su Windows/XAMPP, usa Task Scheduler per eseguire `php artisan schedule:run` ogni minuto.

#### Gestione a Lungo Termine (20+ anni)

**Problema**: Con centinaia di migliaia di operazioni nel tempo, il database diventa lento e occupa molto spazio.

**Soluzione implementata**:

1. **Archiviazione automatica**: I log vecchi vengono esportati in CSV prima di essere eliminati
2. **Retention configurabile**: Mantieni solo gli ultimi X giorni nel database (default: 365 giorni)
3. **Pulizia schedulata**: Eseguita automaticamente ogni mese
4. **File CSV conservabili**: I file archiviati possono essere salvati su supporto esterno

**Esempio scenario 20 anni**:

- **Operazioni giornaliere**: ~500 operazioni/giorno
- **Operazioni annue**: ~182,500 operazioni/anno
- **Operazioni in 20 anni**: ~3,650,000 operazioni

**Con il sistema implementato**:
- Database mantiene solo **ultimi 365 giorni** (~182,500 record)
- **Restanti 19 anni** archiviati in CSV (~3,467,500 record)
- **Performance database**: Sempre ottimali (solo log recenti)
- **Storico completo**: Disponibile nei file CSV archiviati

**Raccomandazioni**:
- Esegui backup mensili della directory `storage/app/archives/audit-logs/`
- Conserva i file CSV su supporto esterno o cloud
- Per compliance, mantieni gli archivi per almeno 7-10 anni

#### Monitoraggio e Statistiche

Per monitorare la crescita dei log e vedere statistiche dettagliate:

```bash
# Visualizza statistiche complete
php artisan audit:stats
```

Questo comando mostra:
- Totale log nel database
- Distribuzione per tipo di azione
- Distribuzione per stato (successo/fallito/attenzione)
- Log per periodo (oggi, settimana, mese, anno)
- Dimensione stimata del database
- Log che verrebbero eliminati con la retention policy attuale

**Esempio output**:
```
📊 Statistiche Generali:
   • Totale log nel database: 45,230
   • Log più vecchio: 01/01/2024 08:15:32
   • Log più recente: 27/01/2025 14:32:10
   • Periodo coperto: 391 giorni

⚙️  Retention Policy:
   • Retention configurata: 365 giorni
   • Log che verrebbero eliminati: 8,450
   • Spazio che verrebbe liberato: 4.12 MB
```

### Tabella Database

La tabella `audit_logs` contiene:

| Campo | Descrizione |
|-------|-------------|
| `user_id` | ID dell'utente che ha eseguito l'azione |
| `user_name` | Nome utente al momento dell'azione (storico) |
| `action` | Tipo di azione (login, create, update, delete, etc.) |
| `description` | Descrizione leggibile dell'azione |
| `entity_type` | Tipo di dato coinvolto (militare, user, etc.) |
| `entity_id` | ID del record coinvolto |
| `old_values` | Valori prima della modifica (JSON) |
| `new_values` | Valori dopo la modifica (JSON) |
| `ip_address` | Indirizzo IP dell'utente |
| `compagnia_id` | Compagnia dell'utente |
| `status` | Esito (success, failed, warning) |
| `created_at` | Data e ora dell'operazione |

---

## Contatti e Supporto

Per segnalazioni di vulnerabilità di sicurezza, contattare immediatamente il team di sviluppo.

**IMPORTANTE:** Non divulgare pubblicamente vulnerabilità prima che siano state corrette.
