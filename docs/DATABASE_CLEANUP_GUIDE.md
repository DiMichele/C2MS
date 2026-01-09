# 🧹 SUGECO - Guida alla Pulizia del Database

> **Guida completa per ottimizzare e pulire il database SUGECO**

---

## ⚠️ IMPORTANTE: Prima di Iniziare

### Backup Obbligatorio

```bash
# Esegui SEMPRE un backup completo prima di qualsiasi operazione
mysqldump -u root sugeco_db > backup_sugeco_$(date +%Y%m%d_%H%M%S).sql
```

---

## 📋 Tabelle da Rimuovere

### 1. `certificati` ❌

**Motivo**: Sostituita completamente da `scadenze_militari`

```sql
-- Verifica se contiene dati
SELECT COUNT(*) as records FROM certificati;

-- Se vuota, rimuovi
DROP TABLE IF EXISTS certificati;
```

### 2. `certificati_lavoratori` ❌

**Motivo**: Sostituita da `scadenze_militari` + `configurazione_corsi_spp`

```sql
-- Verifica se contiene dati
SELECT COUNT(*) as records FROM certificati_lavoratori;

-- Se vuota, rimuovi
DROP TABLE IF EXISTS certificati_lavoratori;
```

### 3. `idoneita` ❌

**Motivo**: Sostituita da `scadenze_militari` (colonne idoneita_*) + `tipi_idoneita`

```sql
-- Verifica se contiene dati
SELECT COUNT(*) as records FROM idoneita;

-- Se vuota, rimuovi
DROP TABLE IF EXISTS idoneita;
```

### 4. `presenze` ❌

**Motivo**: La gestione presenze è ora nel CPT (`pianificazioni_giornaliere`)

```sql
-- Verifica se contiene dati
SELECT COUNT(*) as records FROM presenze;

-- Se vuota, rimuovi
DROP TABLE IF EXISTS presenze;
```

### 5. `uffici` ❌

**Motivo**: Tabella mai utilizzata, vuota

```sql
DROP TABLE IF EXISTS uffici;
```

### 6. `incarichi` ❌

**Motivo**: Tabella mai utilizzata, vuota

```sql
DROP TABLE IF EXISTS incarichi;
```

---

## 🔄 Tabelle da Consolidare

### Sistema Scadenze

Attualmente esistono 3 sistemi paralleli per le scadenze:

| Sistema | Tabelle | Stato |
|---------|---------|-------|
| Denormalizzato | `scadenze_militari` | ✅ PRINCIPALE |
| Normalizzato Corsi | `scadenze_corsi_spp` + `configurazione_corsi_spp` | ⚠️ Parziale |
| Normalizzato Poligoni | `scadenze_poligoni` + `tipi_poligono` | ⚠️ Parziale |
| Normalizzato Idoneità | `scadenze_idoneita` + `tipi_idoneita` | ⚠️ Parziale |

**Raccomandazione**: Mantenere `scadenze_militari` come fonte principale e usare le tabelle normalizzate solo per la configurazione (durate, nomi, etc.).

---

## 📝 Script SQL di Pulizia Completo

```sql
-- ============================================
-- SUGECO DATABASE CLEANUP SCRIPT
-- Data: 2025-12-22
-- ============================================

-- 1. BACKUP PRIMA DI TUTTO!
-- mysqldump -u root sugeco_db > backup_before_cleanup.sql

-- 2. VERIFICA TABELLE VUOTE
SELECT 'certificati' as tabella, COUNT(*) as records FROM certificati
UNION ALL
SELECT 'certificati_lavoratori', COUNT(*) FROM certificati_lavoratori
UNION ALL
SELECT 'idoneita', COUNT(*) FROM idoneita
UNION ALL
SELECT 'presenze', COUNT(*) FROM presenze
UNION ALL
SELECT 'uffici', COUNT(*) FROM uffici
UNION ALL
SELECT 'incarichi', COUNT(*) FROM incarichi
UNION ALL
SELECT 'eventi', COUNT(*) FROM eventi
UNION ALL
SELECT 'poligoni', COUNT(*) FROM poligoni
UNION ALL
SELECT 'nos_storico', COUNT(*) FROM nos_storico
UNION ALL
SELECT 'cpt_dashboard_views', COUNT(*) FROM cpt_dashboard_views;

-- 3. RIMUOVI TABELLE VUOTE/NON UTILIZZATE
-- (Esegui solo dopo aver verificato che sono vuote)

-- Rimuovi foreign keys prima
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS certificati;
DROP TABLE IF EXISTS certificati_lavoratori;
DROP TABLE IF EXISTS idoneita;
DROP TABLE IF EXISTS presenze;
DROP TABLE IF EXISTS uffici;
DROP TABLE IF EXISTS incarichi;

SET FOREIGN_KEY_CHECKS = 1;

-- 4. AGGIUNGI COMMENTI DESCRITTIVI ALLE TABELLE
ALTER TABLE militari 
    COMMENT = 'Anagrafica principale militari';

ALTER TABLE scadenze_militari 
    COMMENT = 'PRINCIPALE - Date conseguimento scadenze';

ALTER TABLE tipi_servizio 
    COMMENT = 'Tipi servizio/assenza per CPT';

ALTER TABLE roles 
    COMMENT = 'SISTEMA - Ruoli autenticazione utenti';

ALTER TABLE ruoli 
    COMMENT = 'OPERATIVI - Ruoli funzionali militari';

ALTER TABLE approntamenti 
    COMMENT = 'Teatri Operativi (ex approntamenti)';

-- 5. OTTIMIZZA TABELLE
OPTIMIZE TABLE militari;
OPTIMIZE TABLE scadenze_militari;
OPTIMIZE TABLE pianificazioni_giornaliere;
OPTIMIZE TABLE tipi_servizio;
OPTIMIZE TABLE users;

-- 6. VERIFICA INTEGRITÀ
-- Militari senza scadenze
SELECT m.id, m.cognome, m.nome 
FROM militari m 
LEFT JOIN scadenze_militari sm ON m.id = sm.militare_id 
WHERE sm.id IS NULL;

-- Crea record scadenze mancanti
INSERT INTO scadenze_militari (militare_id, created_at, updated_at)
SELECT m.id, NOW(), NOW()
FROM militari m 
LEFT JOIN scadenze_militari sm ON m.id = sm.militare_id 
WHERE sm.id IS NULL;
```

---

## 🔧 Procedura di Manutenzione Regolare

### Mensile

```sql
-- Pulisci sessioni scadute
DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(NOW() - INTERVAL 30 DAY);

-- Ottimizza tabelle principali
OPTIMIZE TABLE militari, scadenze_militari, pianificazioni_giornaliere;

-- Verifica integrità FK
SELECT 
    TABLE_NAME, 
    CONSTRAINT_NAME, 
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_SCHEMA = 'sugeco_db' 
AND REFERENCED_TABLE_NAME IS NOT NULL;
```

### Trimestrale

```sql
-- Pulisci cache
DELETE FROM cache WHERE expiration < UNIX_TIMESTAMP(NOW());

-- Archivia pianificazioni vecchie (opzionale)
-- Prima crea tabella archivio, poi sposta i dati

-- Verifica dimensioni tabelle
SELECT 
    table_name AS "Tabella",
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS "Dimensione (MB)"
FROM information_schema.tables 
WHERE table_schema = 'sugeco_db'
ORDER BY (data_length + index_length) DESC;
```

---

## 📊 Monitoraggio

### Query per Dashboard Admin

```sql
-- Statistiche database
SELECT 
    (SELECT COUNT(*) FROM militari) as totale_militari,
    (SELECT COUNT(*) FROM users) as totale_utenti,
    (SELECT COUNT(*) FROM pianificazioni_giornaliere) as totale_pianificazioni,
    (SELECT COUNT(*) FROM scadenze_militari WHERE 
        idoneita_mans_data_conseguimento IS NOT NULL AND
        DATE_ADD(idoneita_mans_data_conseguimento, INTERVAL 1 YEAR) < CURDATE()
    ) as scadenze_in_ritardo;
```

---

## ✅ Checklist Post-Pulizia

- [ ] Backup eseguito e verificato
- [ ] Tabelle non utilizzate rimosse
- [ ] Tabelle ottimizzate
- [ ] Integrità referenziale verificata
- [ ] Applicazione testata
- [ ] Documentazione aggiornata

---

*Documento creato: 22 Dicembre 2025 - SUGECO Team*

