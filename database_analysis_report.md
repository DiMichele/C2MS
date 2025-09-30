# REPORT ANALISI DATABASE C2MS vs CPT.xlsx

## 📋 DATI MANCANTI NEL DATABASE

### 1. PATENTI E ABILITAZIONI
**Problema**: Il file Excel contiene informazioni dettagliate sulle patenti possedute dai militari
**Dati Excel**: "2-3-6A ABIL", "3-4-4A-6A", "2 ABILIT.", etc.
**Soluzione**: Creare tabella `patenti_militari` per gestire le patenti possedute

### 2. CATEGORIA MILITARE  
**Problema**: Manca il campo categoria nel modello Militare
**Dati Excel**: "U" (Ufficiali), "SU" (Sottufficiali), "GRAD" (Graduati)
**Soluzione**: Aggiungere campo `categoria` alla tabella `militari`

### 3. APPRONTAMENTI/MISSIONI
**Problema**: Non esiste gestione degli approntamenti/missioni
**Dati Excel**: "KOSOVO", "CJ CBRN", "CENTURIA", "GIBUTI", "LIBIA", etc.
**Soluzione**: Creare tabella `approntamenti` e collegamento ai militari

### 4. SERVIZI GIORNALIERI DETTAGLIATI
**Problema**: Il sistema presenze attuale è semplice (Presente/Assente)
**Dati Excel**: Codici dettagliati per ogni giorno ("TO", "lo", "S-UI", "p", "MCM", "C-IED")
**Soluzione**: Espandere tabella `presenze` con campo `tipo_servizio` e tabella `tipi_servizio`

### 5. CALENDARIO MENSILE STRUTTURATO
**Problema**: Non esiste una vista calendario mensile strutturata
**Dati Excel**: Calendario completo di Settembre 2025 con assegnazioni giornaliere
**Soluzione**: Creare sistema di pianificazione mensile

## 🗂️ NUOVE TABELLE NECESSARIE

### `patenti_militari`
- id, militare_id, tipo_patente, data_ottenimento, data_scadenza, note

### `approntamenti` 
- id, nome, descrizione, data_inizio, data_fine, stato

### `militare_approntamenti`
- id, militare_id, approntamento_id, ruolo, data_assegnazione

### `tipi_servizio`
- id, codice, nome, descrizione, colore_badge

### Modifica `presenze`
- Aggiungere: tipo_servizio_id, note_servizio

### `pianificazioni_mensili`
- id, anno, mese, militare_id, data_creazione, stato

### `pianificazioni_giornaliere`
- id, pianificazione_mensile_id, giorno, tipo_servizio_id, note

## 🔄 CAMPI DA AGGIUNGERE

### Tabella `militari`
- `categoria` ENUM('U', 'SU', 'GRAD') - Categoria militare
- `numero_matricola` VARCHAR(20) - Numero progressivo/matricola
- `approntamento_principale_id` INT - Approntamento principale assegnato

## 📊 STATISTICHE FILE EXCEL
- **Militari totali**: 146
- **Gradi diversi**: 10+ (Cap., 1° LGT, SERG. MAGG., etc.)
- **Categorie**: 3 (U, SU, GRAD)  
- **Approntamenti**: 10+ (KOSOVO, CJ CBRN, CENTURIA, etc.)
- **Codici servizio**: 9+ (TO, lo, S-UI, p, MCM, C-IED, etc.)
- **Periodo**: Settembre 2025 (18 giorni)
