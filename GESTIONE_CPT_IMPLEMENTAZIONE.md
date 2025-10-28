# 🎯 GESTIONE CPT - Implementazione Completa

**Data**: 28 Ottobre 2025  
**Sistema**: C2MS - Gestione e Controllo Digitale  
**Versione**: 1.0

---

## ✅ IMPLEMENTAZIONE COMPLETATA

È stata implementata con successo la pagina **Gestione CPT** che permette di gestire completamente i codici, le categorie, le sigle e i colori utilizzati nel sistema CPT (Controllo Presenza Truppe).

---

## 🎨 FUNZIONALITÀ PRINCIPALI

### 1. **Visualizzazione Codici CPT**
- ✅ Elenco completo di tutti i codici con paginazione
- ✅ Visualizzazione colori e badge in tempo reale
- ✅ Statistiche aggregate (totali, attivi, inattivi, per categoria)
- ✅ Stato attivo/inattivo per ogni codice

### 2. **Ricerca e Filtri Avanzati**
- ✅ Ricerca per codice o descrizione
- ✅ Filtro per Macro Attività
- ✅ Filtro per Tipo Attività
- ✅ Filtro per Categoria Impiego
- ✅ Filtro per stato (Attivo/Inattivo)
- ✅ Ordinamento personalizzabile

### 3. **Creazione Nuovi Codici**
- ✅ Form completo con validazione
- ✅ Codice/Sigla univoco (auto-maiuscolo)
- ✅ Selezione colore con color picker
- ✅ Suggerimenti colori predefiniti CPT:
  - 🟢 Verde CPT (#00b050)
  - 🟡 Giallo CPT (#ffff00)
  - 🔴 Rosso CPT (#ff0000)
  - 🟠 Arancione CPT (#ffc000)
  - 🔵 Blu CPT (#0070c0)
  - ⚫ Nero (#000000)
  - 🟢 Verde Chiaro (#92d050)
  - ⚪ Grigio (#6c757d)
- ✅ Gerarchia a 3 livelli (Macro → Tipo → Attività Specifica)
- ✅ Categorie di impiego:
  - DISPONIBILE
  - INDISPONIBILE
  - NON_DISPONIBILE
  - PRESENTE_SERVIZIO
  - DISPONIBILE_ESIGENZA
- ✅ Anteprima badge in tempo reale
- ✅ Ordine di visualizzazione personalizzabile
- ✅ Stato attivo/inattivo

### 4. **Modifica Codici Esistenti**
- ✅ Modifica completa di tutti i campi
- ✅ Validazione univocità codice
- ✅ Avviso utilizzo codice (se collegato a servizi)
- ✅ Anteprima modifiche in tempo reale

### 5. **Gestione Codici**
- ✅ **Attiva/Disattiva**: cambia stato senza eliminare
- ✅ **Duplica**: crea copia per modifiche rapide
- ✅ **Elimina**: con controllo sicurezza (verifica utilizzo)
- ✅ **Esporta CSV**: esporta tutti i codici

### 6. **Sicurezza**
- ✅ Accesso limitato a utenti Admin
- ✅ Protezione CSRF su tutte le form
- ✅ Validazione completa lato server
- ✅ Controllo utilizzo prima dell'eliminazione

---

## 📁 FILE CREATI/MODIFICATI

### Controller
**File**: `app/Http/Controllers/GestioneCptController.php`

**Metodi implementati**:
- `index()` - Elenco codici con filtri e statistiche
- `create()` - Form creazione nuovo codice
- `store()` - Salvataggio nuovo codice
- `edit()` - Form modifica codice esistente
- `update()` - Aggiornamento codice
- `destroy()` - Eliminazione codice (con controlli)
- `toggleAttivo()` - Attiva/disattiva codice
- `duplicate()` - Duplica codice esistente
- `export()` - Esporta codici in CSV
- `updateOrder()` - Aggiorna ordine codici (per futuro drag & drop)

### Viste Blade

#### 1. **Index** - `resources/views/gestione-cpt/index.blade.php`
- Tabella completa con tutti i codici
- Card statistiche
- Filtri avanzati
- Azioni rapide (modifica, attiva/disattiva, duplica, elimina)
- Modal conferma eliminazione
- Paginazione

#### 2. **Create** - `resources/views/gestione-cpt/create.blade.php`
- Form completo per nuovo codice
- Color picker con suggerimenti
- Autocomplete per gerarchia
- Anteprima badge in tempo reale
- Validazione client-side

#### 3. **Edit** - `resources/views/gestione-cpt/edit.blade.php`
- Form modifica identico a create
- Info utilizzo del codice
- Stessi controlli e validazioni

### Stili CSS
**File**: `public/css/gestione-cpt.css`

**Caratteristiche**:
- Design moderno e professionale
- Card statistiche con gradienti
- Anteprima colori e badge
- Responsive per mobile
- Animazioni smooth
- Tooltip informativi

### Rotte
**File**: `routes/web.php` (aggiunte righe 296-312)

```php
Route::prefix('gestione-cpt')
    ->name('gestione-cpt.')
    ->middleware('permission:admin.access')
    ->group(function () {
        Route::get('/', [GestioneCptController::class, 'index'])->name('index');
        Route::get('/create', [GestioneCptController::class, 'create'])->name('create');
        Route::post('/', [GestioneCptController::class, 'store'])->name('store');
        Route::get('/{codice}/edit', [GestioneCptController::class, 'edit'])->name('edit');
        Route::put('/{codice}', [GestioneCptController::class, 'update'])->name('update');
        Route::delete('/{codice}', [GestioneCptController::class, 'destroy'])->name('destroy');
        Route::patch('/{codice}/toggle', [GestioneCptController::class, 'toggleAttivo'])->name('toggle');
        Route::post('/{codice}/duplicate', [GestioneCptController::class, 'duplicate'])->name('duplicate');
        Route::get('/export', [GestioneCptController::class, 'export'])->name('export');
        Route::post('/update-order', [GestioneCptController::class, 'updateOrder'])->name('update-order');
    });
```

### Menu Navigazione
**File**: `resources/views/layouts/app.blade.php` (righe 151-153)

Aggiunta voce "Gestione CPT" nel menu Admin:
```
Admin
├── Gestione Utenti
├── Gestione Ruoli
└── Gestione CPT ← NUOVO
```

---

## 🔐 PERMESSI RICHIESTI

**Permesso necessario**: `admin.access`

Solo gli utenti con ruolo **Amministratore** o **Admin** possono accedere alla pagina di Gestione CPT.

---

## 🚀 COME UTILIZZARE

### Accedere alla Gestione CPT

1. **Login** con account amministratore
2. Cliccare su **Admin** nel menu principale
3. Selezionare **Gestione CPT**

### Creare un Nuovo Codice

1. Cliccare su **"Nuovo Codice"**
2. Compilare i campi:
   - **Codice/Sigla**: es. "TO", "S-UI", "lo" (univoco)
   - **Colore**: scegliere dal color picker o suggerimenti
   - **Macro Attività**: es. "DISPONIBILE", "ASSENTE" (opzionale)
   - **Tipo Attività**: es. "LICENZA", "PERMESSO" (opzionale)
   - **Attività Specifica**: descrizione dettagliata (obbligatorio)
   - **Descrizione Impiego**: note aggiuntive (opzionale)
   - **Categoria Impiego**: seleziona la disponibilità (obbligatorio)
   - **Ordine**: numero per ordinamento (0 = default)
   - **Stato**: Attivo/Inattivo
3. Vedere l'**anteprima badge** in tempo reale
4. Cliccare **"Salva Codice"**

### Modificare un Codice Esistente

1. Nell'elenco, cliccare sull'icona **"Modifica"** (matita)
2. Modificare i campi desiderati
3. Cliccare **"Salva Modifiche"**

### Duplicare un Codice

1. Cliccare sull'icona **"Duplica"** (due fogli)
2. Il sistema crea una copia con nome `CODICE_COPIA`
3. Si apre automaticamente la pagina di modifica
4. Modificare i dettagli e attivare quando pronto

### Attivare/Disattivare un Codice

1. Cliccare sull'icona **"Occhio"** per cambiare stato
2. I codici disattivati:
   - Restano nel database
   - Non sono utilizzabili nel CPT
   - Appaiono in grigio nell'elenco

### Eliminare un Codice

1. Cliccare sull'icona **"Cestino"**
2. Confermare nell'alert
3. **ATTENZIONE**: Non si può eliminare un codice se è utilizzato in servizi

### Filtrare e Cercare

1. Usare la barra **"Ricerca"** per codice o descrizione
2. Selezionare filtri per:
   - Macro Attività
   - Tipo Attività
   - Categoria Impiego
   - Stato (Attivo/Inattivo)
3. Cliccare **"Filtra"**
4. Cliccare l'icona **"Reset"** per pulire i filtri

### Esportare i Codici

1. Cliccare **"Esporta CSV"**
2. Il file `codici_cpt_YYYY-MM-DD.csv` viene scaricato
3. Aprire con Excel o LibreOffice

---

## 🎨 STRUTTURA GERARCHIA CODICI

I codici CPT seguono una gerarchia a 3 livelli:

```
MACRO ATTIVITÀ (livello 1)
├── TIPO ATTIVITÀ (livello 2)
    └── ATTIVITÀ SPECIFICA (livello 3) + CODICE
```

### Esempio Pratico:

```
ASSENTE
├── LICENZA
    ├── Licenza Ordinaria → lo
    ├── Licenza Speciale → ls
    └── Licenza Malattia → lm
├── PERMESSO
    └── Permesso Personale → p
```

```
DISPONIBILE
└── TURNO
    └── Turno Ordinario → TO
```

```
SERVIZIO
├── GUARDIA
    ├── Servizio Unità Interna → S-UI
    ├── Servizio Unità Periferica → S-UP
    └── Servizio G1 → S-G1
└── COMANDO
    └── Comando Generale → S-CG
```

---

## 🎨 COLORI STANDARD CPT

I colori seguono lo standard del CPT Excel originale:

| Colore | Hex | Utilizzo | Esempi |
|--------|-----|----------|--------|
| 🟢 Verde CPT | `#00b050` | DISPONIBILE / SERVIZIO | TO, S-UI, S-G1, SI |
| 🟡 Giallo CPT | `#ffff00` | ASSENTE | lo, ls, lm, p, fp |
| 🔴 Rosso CPT | `#ff0000` | NON IMPIEGABILE | RMD |
| 🟠 Arancione CPT | `#ffc000` | APPRONTAMENTI | KOSOVO, MCM, C-IED, LCC |
| 🔵 Blu CPT | `#0070c0` | SERVIZI SPECIALI | PDT1, PDT2, CD2 |
| ⚫ Nero | `#000000` | COMANDO | LCC, S-SG, S-CG |
| 🟢 Verde Chiaro | `#92d050` | DISPONIBILE ESIGENZA | AE |
| ⚪ Grigio | `#6c757d` | ALTRO / DEFAULT | CENTURIA, TIROCINIO |

---

## 📊 CATEGORIE IMPIEGO

Le 5 categorie determinano la disponibilità del militare:

1. **DISPONIBILE** 🟢
   - Il militare è disponibile per impieghi
   - Esempio: TO (Turno Ordinario)

2. **INDISPONIBILE** 🔴
   - Il militare NON è disponibile
   - Esempio: lo (Licenza Ordinaria), p (Permesso)

3. **NON_DISPONIBILE** ⚫
   - Il militare è assente per lungo periodo
   - Esempio: KOSOVO, MCM (Missioni)

4. **PRESENTE_SERVIZIO** 🔵
   - Il militare è impegnato in servizio
   - Esempio: S-UI, S-G1 (Servizi)

5. **DISPONIBILE_ESIGENZA** 🟡
   - Disponibile solo su richiesta specifica
   - Esempio: AE (Addestramento Esigenza)

---

## 🔄 INTEGRAZIONE CON CPT

I codici gestiti qui sono utilizzati automaticamente in:

1. **Pianificazione CPT** (`/cpt`)
   - I codici appaiono nei dropdown di selezione
   - I colori sono applicati automaticamente alle celle
   - Solo i codici ATTIVI sono selezionabili

2. **Ruolini** (`/ruolini`)
   - Le motivazioni di assenza usano questi codici
   - I badge colorati seguono lo schema qui definito

3. **Servizi e Turni** (`/servizi/turni`)
   - I tipi di servizio possono essere collegati a questi codici
   - Eredita automaticamente colori e categorie

4. **Board Attività** (`/board`)
   - Le attività possono utilizzare questi codici
   - Visualizzazione coerente dei colori

---

## ⚙️ FUNZIONALITÀ AVANZATE (Future)

Funzionalità pianificate per versioni future:

- [ ] **Drag & Drop**: Riordina codici trascinandoli
- [ ] **Import CSV**: Importa codici da file Excel
- [ ] **Storico Modifiche**: Log di tutte le modifiche ai codici
- [ ] **Template Preconfigurati**: Set di codici predefiniti
- [ ] **Gruppi di Codici**: Raggruppa codici correlati
- [ ] **Permessi Granulari**: Permessi specifici per categorie
- [ ] **API REST**: Endpoint per integrazioni esterne

---

## 🐛 TROUBLESHOOTING

### "Permesso negato"
**Soluzione**: Verifica di avere il permesso `admin.access`

### "Codice già esistente"
**Soluzione**: I codici devono essere univoci. Scegli un altro codice o modifica quello esistente.

### "Impossibile eliminare il codice"
**Soluzione**: Il codice è utilizzato in uno o più servizi. Disattivalo invece di eliminarlo.

### "I colori non si vedono nel CPT"
**Soluzione**: 
1. Verifica che il codice sia ATTIVO
2. Ricarica la pagina CPT (Ctrl+F5)
3. Controlla che il codice sia collegato correttamente ai tipi di servizio

### "Il color picker non funziona"
**Soluzione**: Usa browser moderni (Chrome, Firefox, Edge aggiornati)

---

## 📞 SUPPORTO

Per assistenza o segnalazione bug:
- Contatta l'amministratore di sistema
- Consulta la documentazione tecnica
- Verifica i log in `storage/logs/laravel.log`

---

## 📋 CHECKLIST POST-IMPLEMENTAZIONE

- [x] Controller creato e testato
- [x] Viste create (index, create, edit)
- [x] Rotte configurate
- [x] Menu aggiornato
- [x] CSS personalizzato
- [x] Permessi configurati
- [x] Validazione implementata
- [x] Messaggi di feedback
- [x] Documentazione completa

---

## 🎉 CONCLUSIONE

La pagina **Gestione CPT** è ora completamente operativa e permette di:
- ✅ Creare nuovi codici/categorie
- ✅ Modificare codici esistenti
- ✅ Gestire sigle e colori
- ✅ Organizzare la gerarchia
- ✅ Controllare l'utilizzo
- ✅ Esportare i dati

Tutti i cambiamenti si riflettono automaticamente in tutto il sistema CPT!

---

**Versione Documento**: 1.0  
**Ultimo Aggiornamento**: 28 Ottobre 2025  
**Autore**: Sistema C2MS

