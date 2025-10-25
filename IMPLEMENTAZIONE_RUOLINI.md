# Implementazione Pagina Ruolini

## Descrizione
È stata implementata la pagina **Ruolini** che mostra il personale presente e assente per una data selezionata, organizzato per categorie militari (Ufficiali, Sottufficiali, Graduati, Volontari), con possibilità di navigare tra date diverse e filtrare per Compagnia/Plotone.

## File Creati/Modificati

### 1. Controller
- **File**: `app/Http/Controllers/RuoliniController.php`
- **Funzionalità**:
  - Gestione selezione data (navigazione tra giorni)
  - Recupera tutti i militari con filtri per Compagnia/Plotone
  - Divide i militari per categoria di grado:
    - **Ufficiali**: Colonnello, Tenente Colonnello, Maggiore, Capitano, Tenente
    - **Sottufficiali**: Luogotenenti, Marescialli, Sergenti
    - **Graduati**: Graduati di vario livello
    - **Volontari**: Soldati, VFP e altri
  - Per ogni categoria calcola:
    - **Presenti**: militari senza impegni
    - **Assenti**: militari con impegni e relative motivazioni
  - Controlla impegni da tre fonti:
    - CPT (Pianificazione Giornaliera)
    - Turni Settimanali
    - Board Attività

### 2. Vista
- **File**: `resources/views/ruolini/index.blade.php`
- **Caratteristiche**:
  - **Navigazione Date**:
    - Selettore data con input type="date"
    - Pulsanti navigazione giorno precedente/successivo
    - Pulsante "Oggi" per tornare rapidamente alla data odierna
    - Visualizzazione data in formato italiano esteso
  
  - **Filtri**:
    - Filtro per Compagnia
    - Filtro per Plotone
    - Pulsante Reset
  
  - **Card Riepilogo**:
    - 4 card (una per categoria) con colori distintivi:
      - Ufficiali: rosso
      - Sottufficiali: giallo
      - Graduati: azzurro
      - Volontari: grigio
    - Ogni card mostra: presenti, assenti e totale
  
  - **Sezioni per Categoria**:
    - Ogni categoria ha due colonne:
      - **Colonna Presenti** (bordo verde)
      - **Colonna Assenti** (bordo rosso)
    - Tabelle con elenco dettagliato militari
    - Badge motivazioni per gli assenti
    - Link ai dettagli del militare

### 3. Rotte
- **File**: `routes/web.php`
- **Rotta**: `/ruolini` → `RuoliniController@index`
- **Nome rotta**: `ruolini.index`
- **Parametri URL**:
  - `data`: Data selezionata (Y-m-d)
  - `compagnia_id`: ID compagnia per filtro
  - `plotone_id`: ID plotone per filtro

### 4. Menu
- **File**: `resources/views/layouts/app.blade.php`
- **Posizione**: Menu Personale → Sotto CPT
- **Ordine**:
  1. CPT
  2. **Ruolini**
  3. Anagrafica
  4. Scadenze
  5. Organigramma

## Funzionalità Principali

### 1. Navigazione Temporale
- Visualizza ruolini per qualsiasi data (passato, presente, futuro)
- Navigazione rapida tra giorni con frecce ← →
- Pulsante "Oggi" per tornare alla data corrente
- Input date per selezione data specifica

### 2. Organizzazione per Categorie
Ogni categoria militare è visualizzata separatamente con:
- **Card riepilogo** con statistiche aggregate
- **Sezione dettagliata** con due colonne affiancate (presenti/assenti)
- **Colori distintivi** per identificazione rapida:
  - 🔴 Ufficiali (rosso)
  - 🟡 Sottufficiali (giallo)
  - 🔵 Graduati (azzurro)
  - ⚫ Volontari (grigio)

### 3. Visualizzazione Impegni
Gli impegni (motivazioni assenze) sono visualizzati con badge colorati:
- **CPT** (grigio scuro): Impegni da Pianificazione Giornaliera
- **Turno** (blu): Impegni da Turni Settimanali
- **ATT** (verde): Impegni da Board Attività
- Tooltip al passaggio del mouse per dettagli completi

### 4. Filtri Dinamici
- Filtro per Compagnia (mantiene data selezionata)
- Filtro per Plotone (mantiene data selezionata)
- Pulsante Reset per rimuovere filtri
- Filtri persistenti durante la navigazione tra date

## Statistiche Visualizzate

Per ogni categoria:
- **Presenti**: Numero militari senza impegni
- **Assenti**: Numero militari con impegni
- **Totale**: Somma di presenti e assenti

### Card Riepilogo
Mostra in formato grafico i numeri per rapida consultazione:
```
┌────────────────────┐
│   Ufficiali        │
├────────────────────┤
│  5        │    2   │
│ Presenti  │ Assenti│
├────────────────────┤
│   Totale: 7        │
└────────────────────┘
```

## Utilizzo

### Accesso Base
1. Vai su **Personale → Ruolini** dal menu principale
2. Visualizzi automaticamente i ruolini della data odierna
3. Vedi tutte le categorie con relativi presenti e assenti

### Navigazione Date
1. Usa le **frecce** per spostarti giorno per giorno
2. Clicca sul **campo data** per selezionare una data specifica
3. Clicca su **Oggi** per tornare alla data corrente

### Applicare Filtri
1. Seleziona una **Compagnia** dal menu a tendina
2. Oppure seleziona un **Plotone** specifico
3. Clicca **Reset** per rimuovere i filtri

### Visualizzare Dettagli
1. **Passa il mouse** sui badge delle motivazioni per vedere descrizione completa
2. **Clicca sul nome** del militare per vedere la scheda anagrafica completa
3. Ogni categoria mostra la lista ordinata per grado (dal più alto al più basso)

## Note Tecniche

### Gestione Categorie
Il controller determina automaticamente la categoria basandosi sul campo `categoria` del grado:
- Se il grado ha categoria "Ufficiali" → va in Ufficiali
- Se il grado ha categoria "Sottufficiali" → va in Sottufficiali
- Se il grado ha categoria "Graduati" → va in Graduati
- Tutti gli altri (Soldati, VFP, ecc.) → vanno in Volontari

### Gestione Date
- Data default: oggi
- Formato URL: Y-m-d (2025-10-22)
- Formato visualizzazione: italiano esteso (martedì 22 ottobre 2025)
- Persistenza: i filtri rimangono attivi durante la navigazione tra date

### Performance
La query carica in eager loading tutte le relazioni necessarie:
- Grado (con categoria)
- Plotone → Compagnia
- Compagnia diretta
- Pianificazioni giornaliere
- Assegnazioni turno
- Board activities con militari

### Compatibilità
- Compatibile con tutte le versioni del database
- Non richiede migrazioni aggiuntive
- Utilizza le relazioni esistenti
- Supporta militari con o senza plotone

## Layout Responsive

La pagina si adatta a diversi dispositivi:
- **Desktop**: Tutte le card affiancate, tabelle complete
- **Tablet**: Card in griglia 2x2, tabelle scrollabili
- **Mobile**: Card impilate verticalmente, tabelle ottimizzate

## Miglioramenti Implementati

Rispetto alla versione iniziale:

1. ✅ **Organizzazione per Categorie**: Diviso per Ufficiali, Sottufficiali, Graduati, Volontari
2. ✅ **Conteggi per Categoria**: Presenti e assenti per ogni categoria
3. ✅ **Terminologia Corretta**: "Assenti" invece di "Con Impegni"
4. ✅ **Navigazione Temporale**: Possibilità di vedere qualsiasi giorno
5. ✅ **Card Riepilogo**: Statistiche visive per rapida consultazione
6. ✅ **Layout Affiancato**: Presenti e assenti side-by-side per confronto immediato
7. ✅ **Colori Distintivi**: Ogni categoria ha il proprio colore
8. ✅ **Tooltip Informativi**: Dettagli completi al passaggio del mouse

## Possibili Sviluppi Futuri

1. **Export Excel**: Esportazione ruolini per data/categoria
2. **Stampa**: Layout ottimizzato per stampa
3. **Selezione Range**: Visualizzare più giorni contemporaneamente
4. **Grafici**: Statistiche visuali con chart.js
5. **Filtro Rapido**: Ricerca per nome militare
6. **Notifiche**: Alert per categorie con troppe assenze
7. **Storico**: Confronto con date precedenti
8. **Download PDF**: Generazione PDF ruolini firmabile

## Testing

### Test Funzionali
- ✅ Visualizzazione corretta per data odierna
- ✅ Navigazione tra giorni (avanti/indietro)
- ✅ Selezione data specifica
- ✅ Filtro per compagnia
- ✅ Filtro per plotone
- ✅ Reset filtri
- ✅ Divisione corretta per categorie
- ✅ Conteggio presenti/assenti
- ✅ Visualizzazione impegni con tooltip
- ✅ Link alla scheda militare

### Test Edge Cases
- ✅ Militari senza grado → vanno in Volontari
- ✅ Militari senza plotone → visualizzati correttamente
- ✅ Date passate → visualizzazione corretta
- ✅ Date future → visualizzazione corretta
- ✅ Categorie senza militari → non visualizzate
- ✅ Militari con multipli impegni → tutti visualizzati

## Autore
Sistema: C2MS - Gestione e Controllo Digitale a Supporto del Comando  
Implementazione completata: 22 Ottobre 2025  
Versione: 2.0 (con categorie e navigazione temporale)
