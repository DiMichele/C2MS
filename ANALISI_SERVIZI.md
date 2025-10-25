# ANALISI COMPLETA SISTEMA SERVIZI

## 📋 STRUTTURA FILE EXCEL ANALIZZATI

### 1. **TURNI.XLSX** - Pianificazione Settimanale

**Struttura:**
- **Riga 1**: Intestazione reparto
- **Riga 6**: Header settimana (GIOVEDI → MERCOLEDI)
- **Riga 7**: Date (formato numerico Excel: 45946-45952)
- **Riga 8**: Graduato di BTG assegnato per ogni giorno
- **Righe successive**: Servizi organizzati per tipo

**Tipi di Servizio Identificati:**
1. **GRADUATO DI BTG** (Riga 9)
   - 1 posto per giorno
   - Esempio: "GRD. SC. SCIPIO"

2. **NUCLEO VIGILANZA ARMATA D'AVANZO** (Riga 11)
   - Formato: "//" indica nessun assegnato

3. **CONDUTTORE GUARDIA** (Riga 13-18)
   - **NUCLEO SORV D AVANZO** (Multi-posto)
     - Esempio: "SOL. ANDRETTI", "SOL. TRESCA", "SOL. CASINELLI"
   - Orario: 07:30 - 17:00

4. **VIGILANZA PIAN DEL TERMINE** (Riga 20-21)
   - 2 posti
   - Esempio: "1° GRD. CHIARUCCI", "GRD. DE ROSA"

5. **ADDETTO ANTINCENDIO** (Riga 23-24)
   - Esempio: "1° GRD. FRICANO"

6. **VIGILANZA SETTIMANALE CETLI** (Riga 25-26)

7. **CORRIERE** (Riga 27)

8. **NUCLEO DIFESA IMMEDIATA** (Riga 28)

**Note:**
- "//" = Nessun assegnato
- Formato settimana: Giovedì-Mercoledì
- Assegnazioni per grado e cognome

---

### 2. **TRASPARENZA SERVIZI** - Storico Completo

**Struttura File:**
- **13 Fogli**: GENNAIO, FEBBRAIO, ..., DICEMBRE, TOTALI
- **2438 righe** x **49 colonne** (AW)
- **5552 righe totali** (tutte le righe di tutti i fogli)

**Struttura Foglio Mensile:**

#### Header (Righe 1-3):
- **Riga 1, Col A**: "TRASPARENZA SERVIZI 2025"
- **Riga 2, Col D**: Nome mese (es: "GENNAIO")
- **Riga 3**: Header colonne
  - **Col A-C**: Dati militare
  - **Col D-AH**: Giorni (1-31)
  - **Col AI-AS**: Tipi servizio (contatori)
  - **Col AT-AV**: Totali
  - **Col AW**: Altre attività

#### Colonne Dati Militare (A-C):
- **A**: Numero progressivo (1, 2, 3, ...)
- **B**: Grado (es: "Cap.", "1° Grd.", "Sol.")
- **C**: Cognome Nome (es: "CACCAMO MATTIA")

#### Colonne Giorni (D-AH, indice 4-34):
- Una colonna per ogni giorno del mese (1-31)
- **Valore cella**: CODICE SERVIZIO (es: "S-SI", "G1", "PDT1")
- Se vuoto = nessun servizio quel giorno

#### Colonne Contatori Servizi (AI-AS, indice 35-45):
| Colonna | Tipo Servizio | Sigla CPT |
|---------|---------------|-----------|
| AI | Servizio Interno | S-SI |
| AJ | Servizio Ufficiale Permanente | S-UP |
| AK | Servizio Comando Guardio | S-CG |
| AL | Servizio Generale | SG |
| AM | Guardio 1 | G1 |
| AN | Guardio 2 | G2 |
| AO | Pian del Termine 1 | PDT1 |
| AP | Pian del Termine 2 | PDT2 |
| AQ | Servizio CD1 | S-CD1 / S.CD1 |
| AR | Servizio CD2 | S-CD2 |
| AS | Servizio Armeria | SA |

**Note**: Queste colonne contengono il CONTATORE (quante volte nel mese)

#### Colonne Totali (AT-AV, indice 46-48):
| Colonna | Tipo | Descrizione |
|---------|------|-------------|
| AT | FERIALI | Giorni feriali lavorati |
| AU | FESTIVI | Giorni festivi lavorati |
| AV | SUPERFESTIVI | Giorni superfestivi lavorati |

#### Colonna Altre Attività (AW, indice 49):
- Attività speciali (es: "STRADE SICURE")

---

## 🗄️ DATABASE - STATO ATTUALE

### Tabelle Esistenti Rilevanti:

1. **`militari`** ✅
   - Dati anagrafici completi
   - Già usata per anagrafica e CPT

2. **`pianificazione_mensile`** ✅
   - Gestione mesi (mese, anno, stato)
   - Già usata per CPT

3. **`pianificazioni_giornaliere`** ✅
   - Link: pianificazione_mensile_id + militare_id + giorno
   - Campo: tipo_servizio_id
   - Già usata per CPT con codici impegni

4. **`tipi_servizio`** ✅
   - codice, nome, descrizione
   - Già popolata con codici CPT
   - **DA ESTENDERE** con servizi turni

5. **`codici_servizio_gerarchia`** ✅
   - Gerarchia codici servizi
   - Già implementata per CPT

### Tabelle da Creare:

#### 1. **`servizi_turno`** (NUOVA)
```sql
CREATE TABLE servizi_turno (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,              -- "GRADUATO DI BTG", "VIGILANZA PDT"
    codice VARCHAR(20) NOT NULL UNIQUE,      -- "G-BTG", "PDT1", "S-SI"
    sigla_cpt VARCHAR(10),                   -- Sigla per aggiornare CPT (es: "S-SI")
    descrizione TEXT,
    num_posti INT DEFAULT 1,                 -- Numero posti disponibili
    tipo ENUM('singolo', 'multiplo'),        -- Se ammette multipli assegnati
    orario_inizio TIME,                      -- Es: 07:30
    orario_fine TIME,                        -- Es: 17:00
    ordine INT DEFAULT 0,                    -- Per ordinamento visualizzazione
    attivo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 2. **`turni_settimanali`** (NUOVA)
```sql
CREATE TABLE turni_settimanali (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_inizio DATE NOT NULL,               -- Data inizio settimana (giovedì)
    data_fine DATE NOT NULL,                 -- Data fine settimana (mercoledì)
    anno INT NOT NULL,
    numero_settimana INT NOT NULL,           -- Settimana dell'anno
    stato ENUM('bozza', 'pubblicato', 'archiviato') DEFAULT 'bozza',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_data_inizio (data_inizio),
    INDEX idx_anno_settimana (anno, numero_settimana)
);
```

#### 3. **`assegnazioni_turno`** (NUOVA)
```sql
CREATE TABLE assegnazioni_turno (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    turno_settimanale_id BIGINT UNSIGNED NOT NULL,
    servizio_turno_id BIGINT UNSIGNED NOT NULL,
    militare_id BIGINT UNSIGNED NOT NULL,
    data_servizio DATE NOT NULL,
    giorno_settimana VARCHAR(20),            -- "GIOVEDI", "VENERDI", etc.
    note TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (turno_settimanale_id) REFERENCES turni_settimanali(id) ON DELETE CASCADE,
    FOREIGN KEY (servizio_turno_id) REFERENCES servizi_turno(id) ON DELETE CASCADE,
    FOREIGN KEY (militare_id) REFERENCES militari(id) ON DELETE CASCADE,
    INDEX idx_data (data_servizio),
    INDEX idx_militare (militare_id),
    INDEX idx_servizio (servizio_turno_id),
    UNIQUE KEY unique_assegnazione (turno_settimanale_id, servizio_turno_id, militare_id, data_servizio)
);
```

#### 4. **`trasparenza_servizi`** (NUOVA)
```sql
CREATE TABLE trasparenza_servizi (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    militare_id BIGINT UNSIGNED NOT NULL,
    mese INT NOT NULL,                       -- 1-12
    anno INT NOT NULL,
    servizio_turno_id BIGINT UNSIGNED,       -- FK a servizi_turno
    data_servizio DATE NOT NULL,
    giorno INT NOT NULL,                     -- 1-31
    tipo_giorno ENUM('feriale', 'festivo', 'superfestivo') DEFAULT 'feriale',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (militare_id) REFERENCES militari(id) ON DELETE CASCADE,
    FOREIGN KEY (servizio_turno_id) REFERENCES servizi_turno(id) ON DELETE SET NULL,
    INDEX idx_militare_mese (militare_id, anno, mese),
    INDEX idx_data (data_servizio),
    UNIQUE KEY unique_servizio_giorno (militare_id, data_servizio)
);
```

---

## 🔄 INTEGRAZIONE CON CPT

### Logica di Sincronizzazione:

Quando un militare viene assegnato a un servizio tramite **Turni**:

1. **Crea record in `assegnazioni_turno`**
2. **Crea record in `trasparenza_servizi`**
3. **AGGIORNA `pianificazioni_giornaliere`**:
   - Trova la pianificazione mensile per (anno, mese)
   - Crea/Aggiorna il record per (pianificazione_mensile_id, militare_id, giorno)
   - Imposta `tipo_servizio_id` con il servizio corrispondente

### Mappatura Servizi Turni → Codici CPT:

| Servizio Turno | Codice Turno | Sigla CPT | tipo_servizio.codice |
|----------------|--------------|-----------|---------------------|
| Servizio Interno | S-SI | S-SI | S-SI |
| Servizio UP | S-UP | S-UP | S-UP |
| Servizio Comando Guardio | S-CG | S-CG | S-CG |
| Servizio Generale | SG | SG | (da creare) |
| Guardio 1 | G1 | G1 | (da creare) |
| Guardio 2 | G2 | G2 | (da creare) |
| Pian del Termine 1 | PDT1 | PDT1 | (da creare) |
| Pian del Termine 2 | PDT2 | PDT2 | (da creare) |
| Servizio CD1 | S-CD1 | S-CD1 | S-CD1 |
| Servizio CD2 | S-CD2 | S-CD2 | S-CD2 |
| Servizio Armeria | SA | SA | S-ARM |

**Note**: Alcuni codici esistono già in `tipi_servizio`, altri vanno aggiunti.

---

## 🎯 FUNZIONALITÀ DA IMPLEMENTARE

### 1. **Gestione Turni Settimanali** (`/turni`)

#### Pagina Principale:
- **Vista settimanale** stile tabella (emula Turni.xlsx)
- **Header**: Giorni settimana (Giovedì → Mercoledì) con date
- **Righe**: Servizi con posti disponibili
- **Celle**: Militari assegnati (select/autocomplete)
- **Navigazione**: Frecce prev/next settimana
- **Azioni**:
  - Seleziona militare dal dropdown (filtrato per disponibilità)
  - Rimuovi assegnazione (X)
  - Copia settimana precedente
  - Export Excel (formato originale)

#### Funzionalità Speciali:
- **Validazioni**:
  - Non assegnare stesso militare a più servizi stesso giorno
  - Rispetta numero posti disponibili per servizio
  - Controlla se militare già impegnato (da CPT)
- **Sincronizzazione CPT**:
  - Quando salvi turno → aggiorna automaticamente CPT con sigla servizio
- **Colori/Indicatori**:
  - Verde: Posto assegnato
  - Giallo: Posto libero
  - Rosso: Militare non disponibile (già impegnato)

### 2. **Trasparenza Servizi** (`/trasparenza-servizi`)

#### Pagina Principale:
- **Vista mensile** stile tabella (emula TRASPARENZA xlsx)
- **Selettore mese/anno** (come CPT)
- **Colonne**:
  - #, Grado, Cognome Nome
  - Giorni 1-31 (con codice servizio)
  - Contatori servizi (S-SI, S-UP, G1, G2, etc.)
  - Totali (FERIALI, FESTIVI, SUPERFESTIVI)
- **Filtri**:
  - Per compagnia
  - Per grado
  - Per plotone
  - Per tipo servizio
- **Export Excel**:
  - Formato identico a file originale
  - Multi-foglio (un foglio per mese + TOTALI)
  - Formattazione colori e bordi

#### Calcoli Automatici:
- **Contatori servizi**: Conta quante volte militare ha fatto ogni servizio nel mese
- **Totali feriali/festivi**: Conta in base al tipo di giorno
- **Foglio TOTALI**: Somma di tutti i mesi dell'anno

### 3. **Navbar e Routing**

```
Navbar:
├── Dashboard
├── Anagrafica
├── CPT
├── **SERVIZI** (NUOVO)
│   ├── **Turni Settimanali**
│   └── **Trasparenza Servizi**
├── Eventi
├── Note
└── Organigramma
```

**Route**:
- `/servizi/turni` - Gestione turni settimanali
- `/servizi/turni/{turnoId}` - Dettaglio turno specifico
- `/servizi/turni/export-excel` - Export Excel turni
- `/servizi/trasparenza` - Vista trasparenza servizi
- `/servizi/trasparenza/export-excel` - Export Excel trasparenza

---

## 📊 CONTROLLER E SERVICE LAYER

### Controllers:
1. **`ServiziTurnoController`**
   - CRUD servizi turno (tipi di servizio)
   - Gestione configurazione servizi

2. **`TurniController`**
   - Index: vista settimanale
   - Store: salva turno settimana
   - Update: modifica assegnazioni
   - Destroy: elimina turno
   - Copy: copia settimana precedente
   - ExportExcel: export formato originale

3. **`TrasparenzaController`**
   - Index: vista mensile trasparenza
   - Show: dettaglio militare mese
   - ExportExcel: export multi-foglio

### Services:
1. **`TurniService`**
   - `getTurnoSettimana($dataInizio)`: recupera/crea turno settimana
   - `assegnaMilitare($turnoId, $servizioId, $militareId, $data)`: assegna militare
   - `rimuoviAssegnazione($assegnazioneId)`: rimuovi assegnazione
   - `copiaSettimana($turnoId)`: copia turno settimana precedente
   - `sincronizzaCPT($assegnazione)`: aggiorna pianificazione_giornaliera

2. **`TrasparenzaService`**
   - `getTrasparen zaMese($mese, $anno)`: recupera dati mese
   - `calcolaContatori($militareId, $mese, $anno)`: calcola contatori servizi
   - `calcolaTotaliAnno($anno)`: calcola totali annuali
   - `exportExcelMultiFoglio($anno)`: genera Excel multi-foglio

---

## 🎨 UI/UX

### Design Patterns:
- **Stile CPT**: Tabella fixed header + body scrollabile
- **Colori servizi**: Badge colorati per tipi servizio
- **Inline editing**: Click su cella per assegnare/modificare
- **Dropdown smart**: Filtra militari per disponibilità
- **Toast notifications**: Feedback operazioni
- **Loading states**: Spinner durante salvataggio

### Componenti Riusabili:
- `components/servizi/turno-cell.blade.php`: Cella assegnazione turno
- `components/servizi/trasparenza-row.blade.php`: Riga trasparenza
- `components/filters/filter-servizi.blade.php`: Filtri servizi

---

## ✅ CHECKLIST IMPLEMENTAZIONE

### Fase 1: Database (2-3 ore)
- [ ] Migration `servizi_turno`
- [ ] Migration `turni_settimanali`
- [ ] Migration `assegnazioni_turno`
- [ ] Migration `trasparenza_servizi`
- [ ] Seeder servizi turno (popolamento iniziale)
- [ ] Aggiornamento `tipi_servizio` con nuovi codici

### Fase 2: Models e Relationships (1-2 ore)
- [ ] Model `ServizioTurno`
- [ ] Model `TurnoSettimanale`
- [ ] Model `AssegnazioneTurno`
- [ ] Model `TrasparenzaServizio`
- [ ] Relationships tra models

### Fase 3: Services (3-4 ore)
- [ ] `TurniService` con metodi base
- [ ] `TrasparenzaService` con calcoli
- [ ] Integrazione sincronizzazione CPT
- [ ] Validazioni e business logic

### Fase 4: Controllers (2-3 ore)
- [ ] `TurniController` CRUD
- [ ] `TrasparenzaController` viste
- [ ] Export Excel turni
- [ ] Export Excel trasparenza multi-foglio

### Fase 5: Views (4-5 ore)
- [ ] `servizi/turni/index.blade.php` - Vista settimanale
- [ ] `servizi/trasparenza/index.blade.php` - Vista mensile
- [ ] Componenti riusabili
- [ ] JavaScript interazioni inline

### Fase 6: Routes e Navbar (30 min)
- [ ] Routes `web.php`
- [ ] Aggiornamento navbar con dropdown Servizi

### Fase 7: Testing (2-3 ore)
- [ ] Test assegnazione turni
- [ ] Test sincronizzazione CPT
- [ ] Test calcoli trasparenza
- [ ] Test export Excel

---

## 🚀 STIMA TEMPI

- **Database + Models**: 3-4 ore
- **Services + Logic**: 4-5 ore
- **Controllers**: 2-3 ore
- **Views + UI**: 5-6 ore
- **Testing**: 2-3 ore

**TOTALE**: **16-21 ore** (2-3 giorni lavorativi)

---

## 📝 NOTE TECNICHE

### Performance:
- Eager loading per evitare N+1 queries
- Cache contatori trasparenza (1 ora)
- Index database su colonne filtrate

### Sicurezza:
- Validazione input
- CSRF protection
- Permissions (future: solo admin può modificare)

### Compatibilità:
- Export Excel formato .xlsx
- Compatibile con PhpSpreadsheet già installato
- Responsive design (mobile-friendly)

---

## 🎯 PRIORITÀ

1. **Alta**: Gestione turni settimanali + sincronizzazione CPT
2. **Media**: Trasparenza servizi vista mensile
3. **Bassa**: Export Excel perfetto (può essere iterativo)


