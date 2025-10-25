# 📊 REPORT REVISIONE DATABASE C2MS
**Data Analisi**: 1 Ottobre 2025  
**Versione Sistema**: 2.1.0

---

## 🎯 OBIETTIVO DELLA REVISIONE

Pulire e ottimizzare il database rimuovendo tabelle obsolete e mantenendo solo quelle necessarie per le funzionalità richieste.

---

## 📋 TABELLE ANALIZZATE (39 totali)

### ✅ TABELLE ESSENZIALI DA MANTENERE (20)

#### **Core System (9 tabelle)**
1. **militari** - 145 records ✅ **ESSENZIALE**
   - Tabella principale con dati militari
   
2. **gradi** - 23 records ✅ **ESSENZIALE**
   - Gradi militari
   
3. **plotoni** - 4 records ✅ **ESSENZIALE**
   - Plotoni di assegnazione
   
4. **poli** - 4 records ✅ **ESSENZIALE**
   - Uffici/Poli
   
5. **mansioni** - 15 records ✅ **ESSENZIALE**
   - Incarichi
   
6. **compagnie** - 2 records ✅ **ESSENZIALE**
   - Compagnie militari
   
7. **ruoli** - 7 records ✅ **ESSENZIALE**
   - Ruoli certificati
   
8. **users** - 1 record ✅ **ESSENZIALE**
   - Utenti del sistema
   
9. **sessions** - 1 record ✅ **ESSENZIALE**
   - Sessioni utente

#### **Approntamenti (2 tabelle)**
10. **approntamenti** - 16 records ✅ **MANTENERE**
    - Missioni/operazioni
    
11. **militare_approntamenti** - 0 records ✅ **MANTENERE**
    - Relazione militari-approntamenti (vuota ma funzionale)

#### **Pianificazione/CPT (5 tabelle)**
12. **pianificazioni_mensili** - 8 records ✅ **MANTENERE**
    - Calendari mensili
    
13. **pianificazioni_giornaliere** - 451 records ✅ **MANTENERE**
    - Impegni giornalieri (CPT)
    
14. **tipi_servizio** - 47 records ✅ **MANTENERE**
    - Codici servizio (TO, lo, S-UI, ecc.)
    
15. **codici_servizio_gerarchia** - 23 records ✅ **MANTENERE**
    - Gerarchia codici servizio
    
16. **cpt_dashboard_views** - 0 records ✅ **MANTENERE**
    - Configurazioni vista CPT

#### **Patenti (1 tabella)**
17. **patenti_militari** - 128 records ✅ **MANTENERE**
    - Patenti possedute

#### **Laravel System (3 tabelle)**
18. **migrations** - 24 records ✅ **ESSENZIALE**
19. **cache** - 9 records ✅ **ESSENZIALE**
20. **password_reset_tokens** - 0 records ✅ **ESSENZIALE**

---

### ⚠️ TABELLE DA CREARE/RIPRISTINARE (1)

21. **assenze** - TABELLA MANCANTE! ❌
    - **PROBLEMA**: Il codice usa questa tabella ma NON ESISTE nel database
    - **IMPATTO**: AssenzeController fallisce
    - **AZIONE**: Creare migration per tabella assenze

---

### 🗑️ TABELLE OBSOLETE/INUTILIZZATE DA RIMUOVERE (7)

#### **Board/Kanban (4 tabelle) - FUNZIONALITÀ NON RICHIESTA**
22. **board_activities** - 7 records ❌ **RIMUOVERE**
23. **board_columns** - 4 records ❌ **RIMUOVERE**
24. **activity_attachments** - 0 records ❌ **RIMUOVERE**
25. **activity_militare** - 1 record ❌ **RIMUOVERE**
    - Sistema Kanban/Board non richiesto nelle specifiche

#### **Certificati (duplicati/obsoleti)**
26. **certificati** - 0 records ❌ **RIMUOVERE**
    - Tabella vuota e duplicata
    - Già gestito da certificati_lavoratori

#### **Valutazioni**
27. **militare_valutazioni** - 0 records ⚠️ **DA VALUTARE**
    - Tabella vuota ma potrebbe servire in futuro
    - SUGGERIMENTO: Chiedere se serve

#### **Note**
28. **notas** - 0 records ⚠️ **DA VALUTARE**
    - Tabella vuota ma funzionale
    - SUGGERIMENTO: Chiedere se serve

---

### 📊 TABELLE FUTURE PREVISTE (Non ancora create)

Le seguenti tabelle sono già implementate e DOVREBBERO ESSERE MANTENUTE:

29. **idoneita** - 0 records ✅ **MANTENERE**
    - Per PEFO/Idoneità SMI/Idoneità Mansione
    
30. **certificati_lavoratori** - 0 records ✅ **MANTENERE**
    - Certificati lavoratori
    
31. **poligoni** - 0 records ✅ **MANTENERE**
    - Gestione poligoni di tiro
    
32. **tipi_poligono** - 7 records ✅ **MANTENERE**
    - Tipologie di poligono

33. **nos_storico** - 0 records ✅ **MANTENERE**
    - Storico modifiche NOS

---

### 🔧 TABELLE SISTEMA LARAVEL (Standard - Da Mantenere)

34. **cache_locks** - 0 records ✅
35. **failed_jobs** - 0 records ✅
36. **job_batches** - 0 records ✅
37. **jobs** - 0 records ✅
38. **personal_access_tokens** - 0 records ✅
39. **presenze** - 0 records ✅ **MANTENERE**
    - Per tracking presenze future

---

## 🎯 RACCOMANDAZIONI FINALI

### ✅ AZIONI IMMEDIATE NECESSARIE

1. **CREARE tabella `assenze`** ⚠️ CRITICO
   ```sql
   CREATE TABLE `assenze` (
     `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
     `militare_id` bigint(20) unsigned NOT NULL,
     `tipologia` varchar(255) NOT NULL,
     `data_inizio` date NOT NULL,
     `data_fine` date NOT NULL,
     `orario_inizio` time DEFAULT NULL,
     `orario_fine` time DEFAULT NULL,
     `stato` varchar(50) NOT NULL DEFAULT 'Richiesta Ricevuta',
     `created_at` timestamp NULL DEFAULT NULL,
     `updated_at` timestamp NULL DEFAULT NULL,
     PRIMARY KEY (`id`),
     KEY `assenze_militare_id_foreign` (`militare_id`),
     CONSTRAINT `assenze_militare_id_foreign` FOREIGN KEY (`militare_id`) 
       REFERENCES `militari` (`id`) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
   ```

2. **RIMUOVERE tabelle Board/Kanban** (4 tabelle)
   - board_activities
   - board_columns
   - activity_attachments
   - activity_militare

3. **RIMUOVERE tabella `certificati`** (duplicato vuoto)

### ⚠️ DA VALUTARE CON L'UTENTE

1. **militare_valutazioni** - Serve per valutazioni future?
2. **notas** - Serve per sistema note?

### 📊 RIEPILOGO NUMERICO

- **Tabelle totali**: 39
- **Da mantenere**: 28 (71.8%)
- **Da rimuovere**: 5 (12.8%)
- **Da creare**: 1 (2.6%)
- **Da valutare**: 2 (5.1%)
- **Sistema Laravel**: 8 (20.5%)

---

## 🔄 DOPO LA PULIZIA

Il database risultante avrà **29 tabelle** (28 esistenti + 1 nuova):

### Struttura Finale Organizzata:

**MILITARI & ORGANIZZAZIONE (7)**
- militari, gradi, plotoni, poli, mansioni, compagnie, ruoli

**APPRONTAMENTI (2)**
- approntamenti, militare_approntamenti

**PIANIFICAZIONE/CPT (5)**
- pianificazioni_mensili, pianificazioni_giornaliere, tipi_servizio, 
  codici_servizio_gerarchia, cpt_dashboard_views

**CERTIFICATI & IDONEITÀ (3)**
- certificati_lavoratori, idoneita, patenti_militari

**POLIGONI (2)**
- poligoni, tipi_poligono

**PRESENZE & ASSENZE (2)**
- presenze, assenze

**NOS (1)**
- nos_storico

**OPZIONALI (2)**
- militare_valutazioni, notas

**SISTEMA (5)**
- users, sessions, migrations, cache, password_reset_tokens

---

## ✨ BENEFICI DELLA PULIZIA

1. **Performance migliorate** - Meno tabelle da gestire
2. **Backup più veloci** - Database più snello
3. **Manutenzione semplificata** - Struttura chiara
4. **Meno confusione** - Solo tabelle utilizzate
5. **Documentazione accurata** - Allineamento codice-DB


