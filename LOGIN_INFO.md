# 🔐 Sistema di Login C2MS

## Credenziali di Accesso

### 👤 Amministratore
- **Email:** `admin@sige.it`
- **Password:** `admin123`

### 👤 Comandante
- **Email:** `comandante@sige.it`
- **Password:** `coman123`

### 👤 Operatore
- **Email:** `operatore@sige.it`
- **Password:** `oper123`

---

## 🌐 URL di Accesso

### Locale
- **Login:** http://localhost/C2MS/public/login
- **Anagrafica:** http://localhost/C2MS/public/anagrafica

### Tunnel (quando attivo)
- Eseguire `avvia-tunnel.ps1` per ottenere l'URL pubblico
- Aggiungere `/login` all'URL del tunnel

---

## 📋 Configurazione del Sistema

### ✅ Pagine Pubbliche (accessibili senza login)
- **Anagrafica** - Visualizzazione lista militari
- **Dettaglio Militare** - Visualizzazione singolo militare

### 🔒 Pagine Protette (richiedono autenticazione)
- **Dashboard** - Pagina principale con statistiche
- **CPT** - Controllo Presenza Truppe
- **Board Attività** - Gestione attività e calendario
- **Servizi e Turni** - Gestione turni settimanali
- **Trasparenza Servizi** - Report trasparenza servizi
- **Scadenze** - Gestione scadenze certificati militari
- **Ruolini** - Visualizzazione ruolini
- **Organigramma** - Struttura organizzativa
- **Modifica Anagrafica** - Creazione/modifica/eliminazione militari
- **Upload Foto** - Caricamento foto profilo militari
- **Export Excel** - Esportazione dati

---

## 🔧 Funzionalità di Sicurezza

### Rate Limiting
- Massimo **5 tentativi di login** ogni 5 minuti
- Protezione contro attacchi brute force

### Session Security
- Rigenerazione automatica della sessione dopo login
- Invalidazione completa della sessione dopo logout
- CSRF Token protection su tutte le richieste POST

### Remember Me
- Opzione "Ricordami" per sessioni persistenti
- Cookie sicuri con scadenza estesa

---

## 🎨 Caratteristiche dell'Interfaccia di Login

- **Design Moderno** - Gradiente blu/verde militare
- **Responsive** - Ottimizzato per desktop e mobile
- **Animazioni** - Transizioni fluide e feedback visivi
- **Validazione** - Controllo campi in tempo reale
- **Messaggi di Errore** - Feedback chiaro e preciso
- **Loading State** - Indicatore di caricamento durante l'autenticazione

---

## 📱 Menu di Navigazione

### Utente Non Loggato
- Visualizza pulsante **"Login"** nell'header
- Accesso limitato alla sola Anagrafica

### Utente Loggato
- Visualizza **nome utente** e **icona profilo** nell'header
- Pulsante **"Logout"** per disconnessione rapida
- Accesso completo a tutte le funzionalità del sistema

---

## 🚀 Come Testare

1. **Aprire il browser** e navigare su `http://localhost/C2MS/public`
2. **Tentare di accedere** a una pagina protetta (es. Dashboard)
3. **Verifica redirect** automatico alla pagina di login
4. **Effettuare login** con una delle credenziali sopra
5. **Verifica accesso** - Dovresti essere reindirizzato alla Dashboard
6. **Controllare navbar** - Dovresti vedere il tuo nome e il pulsante logout
7. **Testare logout** - Cliccare sul pulsante logout
8. **Verifica redirect** - Dovresti essere riportato al login

---

## 📝 Note Tecniche

### File Modificati
- ✅ `app/Http/Controllers/Auth/LoginController.php` - Controller di autenticazione
- ✅ `resources/views/auth/login.blade.php` - Vista login
- ✅ `resources/views/layouts/app.blade.php` - Menu auth nella navbar
- ✅ `routes/web.php` - Routes pubbliche e protette
- ✅ `css/layout.css` - Stili per menu auth
- ✅ `database/seeders/UsersSeeder.php` - Utenti di test

### Middleware Applicato
```php
Route::middleware(['auth'])->group(function () {
    // Tutte le rotte protette
});
```

### Rotte Pubbliche
```php
Route::get('/anagrafica', [MilitareController::class, 'index']);
Route::get('/anagrafica/{militare}', [MilitareController::class, 'show']);
Route::get('/login', [LoginController::class, 'showLoginForm']);
Route::post('/login', [LoginController::class, 'login']);
```

### Rotte di Logout
```php
Route::post('/logout', [LoginController::class, 'logout']);
```

---

## ⚠️ Importante

- Le password sono salvate con **Hash sicuro** (bcrypt)
- Non condividere le credenziali in ambienti di produzione
- Cambiare le password di default prima del deploy in produzione
- Implementare gestione ruoli/permessi per controllo accessi granulare (future feature)

---

## 🔄 Prossimi Sviluppi Suggeriti

1. **Sistema di Ruoli** - Admin, Comandante, Operatore, Visualizzatore
2. **Permessi Granulari** - Controllo accessi per singola funzionalità
3. **Password Recovery** - Reset password via email
4. **Two-Factor Authentication** - Autenticazione a due fattori
5. **Audit Log** - Tracciamento accessi e modifiche
6. **Gestione Profilo** - Modifica dati utente e cambio password

---

**Versione:** 1.0  
**Data:** 25 Ottobre 2025  
**Sistema:** C2MS - Gestione e Controllo Digitale

