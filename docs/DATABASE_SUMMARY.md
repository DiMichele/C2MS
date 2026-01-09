# 📊 SUGECO Database - Riepilogo Esecutivo

## 🎯 Situazione Attuale

### Statistiche
| Metrica | Valore |
|---------|--------|
| **Tabelle totali** | 50 |
| **Tabelle attive** | 35 |
| **Tabelle da rimuovere** | 6 |
| **Tabelle di sistema** | 9 |

### 🟢 Punti di Forza
- ✅ Sistema di permessi ben strutturato (RBAC)
- ✅ Gestione anagrafica militare completa
- ✅ CPT funzionante con pianificazione giornaliera
- ✅ Sistema scadenze centralizzato
- ✅ Board attività Kanban

### 🔴 Problemi Identificati

1. **Tabelle Ridondanti**
   - `certificati`, `certificati_lavoratori`, `idoneita` → sostituiti da `scadenze_militari`
   - `presenze` → non utilizzata (gestita via CPT)
   - `uffici`, `incarichi` → tabelle vuote

2. **Confusione Nomenclatura**
   - `roles` (sistema) vs `ruoli` (operativi)
   - `approntamenti` dovrebbe chiamarsi `teatri_operativi`

3. **Duplicazione Sistemi Scadenze**
   - `scadenze_militari` (principale, denormalizzato)
   - `scadenze_corsi_spp` + `scadenze_poligoni` + `scadenze_idoneita` (normalizzati)

---

## 🛠️ Azioni Raccomandate

### Priorità Alta ⚡
1. **Eseguire backup completo**
2. **Rimuovere tabelle non utilizzate** (migrazione già creata)
3. **Aggiungere commenti alle tabelle principali**

### Priorità Media 📋
4. Consolidare sistema scadenze
5. Rinominare `approntamenti` → `teatri_operativi`
6. Documentare differenza `roles` vs `ruoli`

### Priorità Bassa 📝
7. Implementare o rimuovere tabelle vuote (`eventi`, `poligoni`, etc.)
8. Ottimizzare query frequenti

---

## 📁 Documenti Creati

| File | Descrizione |
|------|-------------|
| `docs/DATABASE_SCHEMA.md` | Schema visivo completo con ASCII art |
| `docs/DATABASE_CLEANUP_GUIDE.md` | Guida pulizia con script SQL |
| `docs/DATABASE_ERD.dbml` | Diagramma ER per dbdiagram.io |
| `database/migrations/2025_12_22_cleanup_unused_tables.php` | Migrazione pulizia automatica |

---

## 📌 Come Visualizzare lo Schema ERD

1. Vai su **https://dbdiagram.io**
2. Copia il contenuto di `docs/DATABASE_ERD.dbml`
3. Incolla nell'editor
4. Visualizza il diagramma interattivo!

---

## 🔐 Tabelle Principali (Da Conoscere)

### Per Gestione Utenti
```
users → role_user → roles → permission_role → permissions
```

### Per Anagrafica
```
militari → gradi, compagnie, plotoni, poli, mansioni, ruoli
```

### Per CPT
```
pianificazioni_mensili → pianificazioni_giornaliere → tipi_servizio → codici_servizio_gerarchia
```

### Per Scadenze
```
militari → scadenze_militari (+ configurazione_corsi_spp, tipi_poligono, tipi_idoneita)
```

---

*Generato il 22 Dicembre 2025 - SUGECO v1.0*

