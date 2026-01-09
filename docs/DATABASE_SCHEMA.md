# 📊 SUGECO - Schema Database

> **Sistema Unico di Gestione e Controllo**  
> Versione: 1.0 | Ultimo aggiornamento: 22 Dicembre 2025

---

## 🎯 Panoramica

Questo documento descrive la struttura completa del database SUGECO, identificando le relazioni tra le tabelle, le ridondanze e fornendo una guida per la manutenzione.

---

## 📋 Indice

1. [Schema Visivo](#-schema-visivo)
2. [Tabelle Core](#-tabelle-core)
3. [Tabelle di Sistema](#-tabelle-di-sistema)
4. [Tabelle Ridondanti/Da Rimuovere](#-tabelle-ridondantida-rimuovere)
5. [Guida Rapida per Amministratori](#-guida-rapida-per-amministratori)

---

## 🗺️ Schema Visivo

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              🏛️ SUGECO DATABASE SCHEMA                                  │
└─────────────────────────────────────────────────────────────────────────────────────────┘

╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                                 👥 GESTIONE UTENTI                                        ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║    ┌──────────────┐         ┌──────────────┐         ┌──────────────┐                    ║
║    │    users     │◄───────►│  role_user   │◄───────►│    roles     │                    ║
║    │              │   N:M   │   (pivot)    │   N:M   │              │                    ║
║    │ • id         │         │ • role_id    │         │ • id         │                    ║
║    │ • username   │         │ • user_id    │         │ • name       │                    ║
║    │ • email      │                                  │ • compagnia_id│                   ║
║    │ • password   │                                  └───────┬──────┘                    ║
║    │ • compagnia_id◄─────────────────────────────────────────┘                           ║
║    └──────────────┘                                          │                           ║
║                                                              ▼                           ║
║                                                    ┌──────────────────┐                  ║
║    ┌──────────────┐                               │ permission_role  │                  ║
║    │ permissions  │◄─────────────────────────────►│    (pivot)       │                  ║
║    │              │              N:M              │ • permission_id  │                  ║
║    │ • id         │                               │ • role_id        │                  ║
║    │ • name       │                               └──────────────────┘                  ║
║    │ • category   │                                                                      ║
║    └──────────────┘                                                                      ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                              👤 ANAGRAFICA MILITARE                                       ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║                              ┌────────────────────┐                                       ║
║     ┌──────────┐            │     militari       │            ┌──────────┐              ║
║     │ compagnie│◄───────────│                    │───────────►│  gradi   │              ║
║     │          │     FK     │ • id               │     FK     │          │              ║
║     │ • id     │            │ • nome             │            │ • id     │              ║
║     │ • nome   │            │ • cognome          │            │ • nome   │              ║
║     └────┬─────┘            │ • grado_id      ──►├────────────│ • ordine │              ║
║          │                  │ • compagnia_id  ──►├────────────│ • categoria             ║
║          │                  │ • plotone_id    ──►├──┐         └──────────┘              ║
║          │                  │ • polo_id       ──►├──┼─┐                                 ║
║          │                  │ • mansione_id   ──►├──┼─┼─┐     ┌──────────┐              ║
║          │                  │ • ruolo_id      ──►├──┼─┼─┼────►│  ruoli   │              ║
║          │                  │ • appront..._id ──►├──┼─┼─┼─┐   │(operativi)              ║
║          │                  │ • nos_status       │  │ │ │ │   └──────────┘              ║
║          │                  │ • data_nascita     │  │ │ │ │                             ║
║          │                  │ • codice_fiscale   │  │ │ │ │   ┌──────────┐              ║
║          │                  │ • statuti (JSON)   │  │ │ │ └──►│approntam.│              ║
║          │                  └────────────────────┘  │ │ │     │(T.O.)    │              ║
║          │                           │              │ │ │     └──────────┘              ║
║          ▼                           │              │ │ │                               ║
║   ┌──────────┐   ┌──────────┐       │              │ │ │     ┌──────────┐              ║
║   │ plotoni  │   │   poli   │◄──────┼──────────────┘ │ └────►│ mansioni │              ║
║   │          │   │          │       │                │       │          │              ║
║   │ • id     │   │ • id     │       │                │       │ • id     │              ║
║   │ • nome   │   │ • nome   │       │                │       │ • nome   │              ║
║   │ • comp..◄┴───┤ • comp..◄┴───────┴────────────────┘       └──────────┘              ║
║   └──────────┘   └──────────┘                                                           ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                              📅 PIANIFICAZIONE (CPT)                                      ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║    ┌─────────────────────┐         ┌──────────────────────────┐                          ║
║    │pianificazioni_mensili│◄───────│ pianificazioni_giornaliere│                          ║
║    │                     │   1:N   │                          │                          ║
║    │ • id                │         │ • id                     │                          ║
║    │ • anno              │         │ • pianif_mensile_id   ──►│                          ║
║    │ • mese              │         │ • militare_id         ──►├────┐                     ║
║    │ • nome              │         │ • giorno (1-31)          │    │                     ║
║    │ • stato             │         │ • tipo_servizio_id    ──►├──┐ │                     ║
║    └─────────────────────┘         │ • note                   │  │ │                     ║
║                                    └──────────────────────────┘  │ │                     ║
║                                                                  │ │                     ║
║                                                                  ▼ │                     ║
║    ┌─────────────────────────┐         ┌─────────────────────┐   │ │                     ║
║    │codici_servizio_gerarchia│◄────────│    tipi_servizio    │◄──┘ │                     ║
║    │                         │   FK    │                     │     │                     ║
║    │ • id                    │         │ • id                │     │                     ║
║    │ • codice                │         │ • codice_gerarchia_id     │    ┌──────────┐     ║
║    │ • descrizione           │         │ • codice            │     └───►│ militari │     ║
║    │ • macro_attivita        │         │ • nome              │          │          │     ║
║    │ • colore_badge          │         │ • colore_badge      │          └──────────┘     ║
║    └─────────────────────────┘         │ • categoria         │                           ║
║                                        │ • attivo            │                           ║
║                                        └─────────────────────┘                           ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                                 📋 SCADENZE                                               ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║  ⚠️  NOTA: Il sistema scadenze è in fase di refactoring.                                 ║
║      La tabella principale è `scadenze_militari` (struttura denormalizzata).             ║
║      Le tabelle normalizzate sono per future estensioni.                                 ║
║                                                                                           ║
║    ┌─────────────────────────────────────────────────────────────────────┐               ║
║    │                      scadenze_militari                              │               ║
║    │  (TABELLA PRINCIPALE - contiene tutte le date conseguimento)       │               ║
║    ├─────────────────────────────────────────────────────────────────────┤               ║
║    │ • id                                                                │               ║
║    │ • militare_id ─────────────────────────────────────────────────────►├─► militari    ║
║    │                                                                     │               ║
║    │ ═══ IDONEITÀ SANITARIE ═══                                         │               ║
║    │ • idoneita_mans_data_conseguimento                                  │               ║
║    │ • idoneita_smi_data_conseguimento                                   │               ║
║    │ • ecg_data_conseguimento                                            │               ║
║    │ • prelievi_data_conseguimento                                       │               ║
║    │ • pefo_data_conseguimento                                           │               ║
║    │                                                                     │               ║
║    │ ═══ CORSI FORMAZIONE ═══                                           │               ║
║    │ • lavoratore_4h_data_conseguimento                                  │               ║
║    │ • lavoratore_8h_data_conseguimento                                  │               ║
║    │ • preposto_data_conseguimento                                       │               ║
║    │ • dirigenti_data_conseguimento                                      │               ║
║    │ • antincendio_data_conseguimento                                    │               ║
║    │ • blsd_data_conseguimento                                           │               ║
║    │ • primo_soccorso_aziendale_data_conseguimento                       │               ║
║    │                                                                     │               ║
║    │ ═══ ACCORDO STATO-REGIONE ═══                                      │               ║
║    │ • abilitazione_trattori_data_conseguimento                          │               ║
║    │ • abilitazione_mmt_data_conseguimento                               │               ║
║    │ • abilitazione_muletto_data_conseguimento                           │               ║
║    │ • abilitazione_ple_data_conseguimento                               │               ║
║    │                                                                     │               ║
║    │ ═══ POLIGONO ═══                                                   │               ║
║    │ • tiri_approntamento_data_conseguimento                             │               ║
║    │ • mantenimento_arma_lunga_data_conseguimento                        │               ║
║    │ • mantenimento_arma_corta_data_conseguimento                        │               ║
║    │ • poligono_approntamento_data_conseguimento                         │               ║
║    │ • poligono_mantenimento_data_conseguimento                          │               ║
║    └─────────────────────────────────────────────────────────────────────┘               ║
║                                                                                           ║
║    ┌─────────────────────────────────────────────────────────────────────┐               ║
║    │                   TABELLE CONFIGURAZIONE                            │               ║
║    ├─────────────────────────────────────────────────────────────────────┤               ║
║    │                                                                     │               ║
║    │  ┌────────────────────┐    ┌────────────────┐    ┌──────────────┐  │               ║
║    │  │configurazione_corsi│    │ tipi_poligono  │    │tipi_idoneita │  │               ║
║    │  │      _spp          │    │                │    │              │  │               ║
║    │  │                    │    │ • codice       │    │ • codice     │  │               ║
║    │  │ • codice_corso     │    │ • nome         │    │ • nome       │  │               ║
║    │  │ • nome_corso       │    │ • durata_mesi  │    │ • durata_mesi│  │               ║
║    │  │ • durata_anni      │    │ • punteggio_min│    │ • attivo     │  │               ║
║    │  │ • tipo             │    │ • attivo       │    └──────────────┘  │               ║
║    │  │ • attivo           │    └────────────────┘                      │               ║
║    │  └────────────────────┘                                            │               ║
║    │                                                                     │               ║
║    └─────────────────────────────────────────────────────────────────────┘               ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                                 🗂️ BOARD ATTIVITÀ                                        ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║    ┌─────────────────┐         ┌─────────────────────┐                                   ║
║    │  board_columns  │◄────────│   board_activities  │                                   ║
║    │                 │   FK    │                     │                                   ║
║    │ • id            │         │ • id                │                                   ║
║    │ • name          │         │ • column_id      ──►│                                   ║
║    │ • slug          │         │ • title             │         ┌────────────────────┐    ║
║    │ • position      │         │ • description       │◄────────│ activity_attachments│   ║
║    └─────────────────┘         │ • data_inizio       │   1:N   │                    │    ║
║                                │ • data_fine         │         │ • file_path        │    ║
║                                │ • compagnia_id   ──►├─────────│ • file_name        │    ║
║                                │ • created_by     ──►├──►users └────────────────────┘    ║
║                                └────────────────────┬┘                                   ║
║                                         │                                               ║
║                                         │   N:M                                         ║
║                                         ▼                                               ║
║                                ┌─────────────────────┐                                   ║
║                                │  activity_militare  │                                   ║
║                                │      (pivot)        │                                   ║
║                                │ • activity_id       │                                   ║
║                                │ • militare_id    ──►├───────────► militari             ║
║                                └─────────────────────┘                                   ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                              📆 TURNI SETTIMANALI                                        ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║    ┌─────────────────────┐         ┌─────────────────────┐                               ║
║    │  turni_settimanali  │◄────────│  assegnazioni_turno │                               ║
║    │                     │   1:N   │                     │                               ║
║    │ • id                │         │ • id                │        ┌─────────────────┐    ║
║    │ • anno              │         │ • turno_sett_id  ──►│        │  servizi_turno  │    ║
║    │ • numero_settimana  │         │ • militare_id    ──►├────────│                 │    ║
║    │ • data_inizio       │         │ • servizio_id    ──►├───────►│ • id            │    ║
║    │ • data_fine         │         │ • data_servizio     │        │ • nome          │    ║
║    │ • stato             │         │ • giorno_settimana  │        │ • codice        │    ║
║    └─────────────────────┘         └─────────────────────┘        │ • num_posti     │    ║
║                                                                    └─────────────────┘    ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝


╔═══════════════════════════════════════════════════════════════════════════════════════════╗
║                            📑 CONFIGURAZIONI DINAMICHE                                   ║
╠═══════════════════════════════════════════════════════════════════════════════════════════╣
║                                                                                           ║
║    ┌──────────────────────────────┐         ┌─────────────────────────┐                  ║
║    │configurazione_campi_anagrafica│◄────────│valori_campi_anagrafica │                  ║
║    │                              │   1:N   │                         │                  ║
║    │ • id                         │         │ • id                    │                  ║
║    │ • nome_campo                 │         │ • militare_id        ──►├──► militari      ║
║    │ • etichetta                  │         │ • configurazione_id  ──►│                  ║
║    │ • tipo_campo                 │         │ • valore                │                  ║
║    │ • opzioni (JSON)             │         └─────────────────────────┘                  ║
║    │ • attivo                     │                                                      ║
║    └──────────────────────────────┘                                                      ║
║                                                                                           ║
║    ┌──────────────────────────────┐                                                      ║
║    │   configurazione_ruolini     │                                                      ║
║    │                              │                                                      ║
║    │ • id                         │                                                      ║
║    │ • tipo_servizio_id        ──►├──► tipi_servizio                                     ║
║    │ • stato_presenza             │    (determina se il servizio conta come presente)    ║
║    └──────────────────────────────┘                                                      ║
║                                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════════════════════╝
```

---

## 📦 Tabelle Core

### Gestione Utenti e Permessi

| Tabella | Descrizione | Relazioni |
|---------|-------------|-----------|
| `users` | Utenti del sistema | → compagnie, ↔ roles |
| `roles` | Ruoli (admin, comandante, etc.) | → compagnie, ↔ permissions |
| `permissions` | Permessi singoli | ↔ roles |
| `role_user` | Pivot ruoli-utenti | → users, → roles |
| `permission_role` | Pivot permessi-ruoli | → permissions, → roles |

### Anagrafica Militare

| Tabella | Descrizione | Relazioni |
|---------|-------------|-----------|
| `militari` | Dati anagrafici militari | → gradi, → compagnie, → plotoni, → poli, → mansioni, → ruoli |
| `gradi` | Gradi militari con ordine gerarchico | ← militari |
| `compagnie` | Compagnie (110^, 124^, 127^) | ← militari, ← plotoni, ← poli |
| `plotoni` | Plotoni delle compagnie | → compagnie, ← militari |
| `poli` | Poli/Uffici delle compagnie | → compagnie, ← militari |
| `mansioni` | Mansioni operative | ← militari |
| `ruoli` | Ruoli operativi (non confondere con `roles`!) | ← militari |
| `approntamenti` | Teatri Operativi (ex approntamenti) | ← militari |
| `patenti_militari` | Patenti dei militari | → militari |

### Pianificazione (CPT)

| Tabella | Descrizione | Relazioni |
|---------|-------------|-----------|
| `pianificazioni_mensili` | Calendari mensili CPT | ← pianificazioni_giornaliere |
| `pianificazioni_giornaliere` | Assegnazioni giornaliere | → pianificazioni_mensili, → militari, → tipi_servizio |
| `tipi_servizio` | Tipi di servizio/assenza | → codici_servizio_gerarchia |
| `codici_servizio_gerarchia` | Gerarchia codici con colori badge | ← tipi_servizio |

### Scadenze

| Tabella | Descrizione | Stato |
|---------|-------------|-------|
| `scadenze_militari` | **TABELLA PRINCIPALE** - tutte le date conseguimento | ✅ ATTIVA |
| `configurazione_corsi_spp` | Configurazione corsi SPP | ✅ ATTIVA |
| `tipi_poligono` | Configurazione tipi poligono | ✅ ATTIVA |
| `tipi_idoneita` | Configurazione tipi idoneità | ✅ ATTIVA |

### Board e Attività

| Tabella | Descrizione | Relazioni |
|---------|-------------|-----------|
| `board_columns` | Colonne del kanban board | ← board_activities |
| `board_activities` | Attività/card del board | → board_columns, → users, ↔ militari |
| `activity_attachments` | Allegati delle attività | → board_activities |
| `activity_militare` | Pivot attività-militari | → board_activities, → militari |

### Turni

| Tabella | Descrizione | Relazioni |
|---------|-------------|-----------|
| `turni_settimanali` | Settimane di turno | ← assegnazioni_turno |
| `servizi_turno` | Tipi di servizio turno | ← assegnazioni_turno |
| `assegnazioni_turno` | Assegnazioni ai turni | → turni_settimanali, → militari, → servizi_turno |

---

## ⚙️ Tabelle di Sistema

Queste tabelle sono gestite automaticamente da Laravel e **NON devono essere modificate manualmente**:

| Tabella | Descrizione |
|---------|-------------|
| `migrations` | Storico migrazioni database |
| `sessions` | Sessioni utente attive |
| `cache` | Cache applicazione |
| `cache_locks` | Lock della cache |
| `jobs` | Job in coda |
| `job_batches` | Batch di job |
| `failed_jobs` | Job falliti |
| `personal_access_tokens` | Token API (Sanctum) |
| `password_reset_tokens` | Token reset password |

---

## ⚠️ Tabelle Ridondanti/Da Rimuovere

### 🔴 DA RIMUOVERE (non utilizzate)

| Tabella | Motivo | Azione |
|---------|--------|--------|
| `certificati` | Sostituita da `scadenze_militari` | Creare backup e rimuovere |
| `certificati_lavoratori` | Sostituita da `scadenze_militari` | Creare backup e rimuovere |
| `idoneita` | Sostituita da `scadenze_militari` | Creare backup e rimuovere |
| `presenze` | Non utilizzata (gestita via CPT) | Verificare e rimuovere |
| `uffici` | Tabella vuota, mai utilizzata | Rimuovere |
| `incarichi` | Tabella vuota, mai utilizzata | Rimuovere |

### 🟡 DA VALUTARE (potenzialmente ridondanti)

| Tabella | Situazione | Decisione |
|---------|------------|-----------|
| `scadenze_poligoni` | Nuova struttura, parzialmente usata | Decidere se migrare da `scadenze_militari` |
| `scadenze_idoneita` | Nuova struttura, parzialmente usata | Decidere se migrare da `scadenze_militari` |
| `scadenze_corsi_spp` | Usata parallelamente a `scadenze_militari` | Unificare i dati |
| `poligoni` | Storico poligoni, vuota | Implementare o rimuovere |
| `eventi` | Gestione eventi, vuota | Implementare o rimuovere |
| `nos_storico` | Storico NOS, vuota | Implementare o rimuovere |
| `cpt_dashboard_views` | Viste dashboard, vuota | Implementare o rimuovere |

### 🟢 DA RINOMINARE (per chiarezza)

| Tabella Attuale | Nome Suggerito | Motivo |
|-----------------|----------------|--------|
| `approntamenti` | `teatri_operativi` | Riflette la nuova nomenclatura |
| `ruoli` | `ruoli_operativi` | Evita confusione con `roles` (sistema) |

---

## 📖 Guida Rapida per Amministratori

### Problemi Comuni e Soluzioni

#### 1. "Militare non visibile in CPT"
**Causa**: Manca l'assegnazione in `pianificazioni_giornaliere`
**Soluzione**: 
```sql
-- Verifica se il militare ha pianificazioni
SELECT * FROM pianificazioni_giornaliere WHERE militare_id = [ID];
```

#### 2. "Scadenza non aggiornata"
**Causa**: Data in `scadenze_militari` non corretta
**Soluzione**:
```sql
-- Aggiorna la data conseguimento
UPDATE scadenze_militari 
SET [campo]_data_conseguimento = '[YYYY-MM-DD]'
WHERE militare_id = [ID];
```

#### 3. "Utente senza permessi"
**Causa**: Ruolo non assegnato o permessi mancanti
**Soluzione**:
```sql
-- Verifica ruoli utente
SELECT r.name FROM roles r 
JOIN role_user ru ON r.id = ru.role_id 
WHERE ru.user_id = [ID];

-- Assegna ruolo
INSERT INTO role_user (role_id, user_id) VALUES ([ROLE_ID], [USER_ID]);
```

#### 4. "Colore badge errato nel CPT"
**Causa**: `colore_badge` in `codici_servizio_gerarchia` o `tipi_servizio`
**Soluzione**:
```sql
-- Aggiorna colore
UPDATE codici_servizio_gerarchia 
SET colore_badge = '#XXXXXX' 
WHERE id = [ID];
```

### Query Utili

```sql
-- Conta militari per compagnia
SELECT c.nome, COUNT(m.id) as totale 
FROM compagnie c 
LEFT JOIN militari m ON c.id = m.compagnia_id 
GROUP BY c.id;

-- Scadenze in scadenza nei prossimi 30 giorni
SELECT m.cognome, m.nome, sm.* 
FROM scadenze_militari sm
JOIN militari m ON sm.militare_id = m.id
WHERE sm.idoneita_mans_data_conseguimento IS NOT NULL
AND DATE_ADD(sm.idoneita_mans_data_conseguimento, INTERVAL 1 YEAR) 
    BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY);

-- Militari senza scadenze
SELECT m.cognome, m.nome 
FROM militari m 
LEFT JOIN scadenze_militari sm ON m.id = sm.militare_id 
WHERE sm.id IS NULL;
```

---

## 📊 Statistiche Database

| Metrica | Valore |
|---------|--------|
| Tabelle totali | ~50 |
| Tabelle core attive | ~30 |
| Tabelle da rimuovere | ~6 |
| Tabelle sistema | ~9 |

---

*Documento generato automaticamente - SUGECO Database Schema v1.0*

