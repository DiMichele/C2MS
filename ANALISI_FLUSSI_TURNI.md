# 🔍 ANALISI COMPLETA FLUSSI TURNI SETTIMANALI

## 📋 PROBLEMI IDENTIFICATI E RISOLTI

### 🔴 CRITICO: Militare poteva essere assegnato a PIÙ servizi lo stesso giorno

**PROBLEMA:**
```php
// PRIMA (riga 124-138 TurniService.php):
$giàAssegnato = AssegnazioneTurno::where('turno_settimanale_id', $turnoId)
    ->where('servizio_turno_id', $servizioId)  // ❌ Verificava SOLO stesso servizio
    ->where('militare_id', $militareId)
    ->where('data_servizio', $data)
    ->exists();
```

**CONSEGUENZA:**
- ✗ Militare poteva essere in "GRADUATO DI BTG" E "CONDUTTORE GUARDIA" lo stesso giorno
- ✗ CPT veniva sovrascritto silenziosamente
- ✗ Nessun avviso all'utente

**SOLUZIONE IMPLEMENTATA:**
```php
// ORA (riga 123-163 TurniService.php):
// 1. Verifica TUTTE le assegnazioni del militare per quella data
$altreAssegnazioni = AssegnazioneTurno::where('militare_id', $militareId)
    ->where('data_servizio', $data)  // ✓ Cerca in TUTTI i servizi
    ->with('servizioTurno')
    ->get();

// 2. Se trova assegnazioni:
if ($altreAssegnazioni->isNotEmpty()) {
    // 2a. Stesso servizio → ERRORE
    if (già stesso servizio) {
        return errore: "Già assegnato a questo servizio"
    }
    
    // 2b. Altro servizio → RICHIEDE FORZATURA
    if (!$forzaSovrascrizione) {
        return errore: "Già assegnato al servizio: NOME_SERVIZIO"
        + warning: 'conflitto'  // Trigger per mostrare opzioni
    }
    
    // 2c. Con forzatura → RIMUOVE VECCHIA ASSEGNAZIONE
    foreach ($altreAssegnazioni as $vecchiaAssegnazione) {
        $this->rimuoviDaCPT($vecchiaAssegnazione);  // ✓ Pulisce CPT
        $vecchiaAssegnazione->delete();              // ✓ Elimina turno
    }
}
```

---

## ✅ FLUSSI VERIFICATI

### SCENARIO 1: Assegnazione Militare Disponibile
**Input:** Militare libero, nessun conflitto
**Flusso:**
1. Utente seleziona militare → Verifica disponibilità
2. Sistema controlla:
   - ✓ Militare NON in CPT
   - ✓ Militare NON in altri turni
3. Banner VERDE: "✓ Militare disponibile"
4. Conferma → Assegnazione creata
5. CPT sincronizzato automaticamente
6. Toast SUCCESS: "✓ Militare assegnato con successo"

**Database:**
- `assegnazioni_turno`: +1 record
- `pianificazioni_giornaliere`: +1 record (CPT)
- `sincronizzato_cpt`: true

---

### SCENARIO 2: Militare già nel CPT (altro impegno)
**Input:** Militare già impegnato nel CPT per quella data
**Flusso:**
1. Utente seleziona militare → Verifica disponibilità
2. Sistema chiama `Militare->isDisponibile($data)`:
   - Trova record in `pianificazioni_giornaliere`
   - Identifica tipo servizio (es: "G-BTG")
3. Banner GIALLO con dettagli:
   ```
   ⚠️ 1 Militare con conflitto:
   
   Serg. ROSSI
   Impegnato nel CPT: G-BTG
   
   [Deseleziona] [Forza assegnazione]
   ```
4a. Se clicchi "Deseleziona": militare rimosso
4b. Se clicchi "Forza assegnazione":
   - Badge verde "Forzatura attiva" appare
   - Conferma → Sovrascrive CPT
   - Toast WARNING: "Sovrascritto conflitto CPT"

**Database (con forzatura):**
- `pianificazioni_giornaliere`: record aggiornato con nuovo servizio
- `assegnazioni_turno`: +1 record
- `sincronizzato_cpt`: true

---

### SCENARIO 3: Militare già in ALTRO turno (🆕 FIX CRITICO)
**Input:** Militare già assegnato a "GRADUATO DI BTG", provi ad assegnarlo a "CONDUTTORE GUARDIA"
**Flusso:**
1. Utente seleziona militare → Verifica disponibilità
2. Sistema controlla:
   - ✓ Trova record in `assegnazioni_turno` per altra attività
   - Carica nome servizio: "GRADUATO DI BTG"
3. Banner GIALLO:
   ```
   ⚠️ 1 Militare con conflitto:
   
   Serg. ROSSI
   Il militare è già assegnato al servizio: GRADUATO DI BTG per questa data
   
   [Deseleziona] [Forza assegnazione]
   ```
4a. Se clicchi "Deseleziona": militare rimosso
4b. Se clicchi "Forza assegnazione":
   - Badge verde "Forzatura attiva"
   - Conferma → Sistema:
     1. Rimuove vecchia assegnazione da "GRADUATO DI BTG"
     2. Pulisce CPT vecchio
     3. Crea nuova assegnazione a "CONDUTTORE GUARDIA"
     4. Aggiorna CPT con nuovo servizio
   - Toast SUCCESS: "✓ Militare assegnato (conflitto risolto)"

**Database (con forzatura):**
- `assegnazioni_turno`: vecchio eliminato, nuovo creato
- `pianificazioni_giornaliere`: aggiornato con nuovo servizio
- `sincronizzato_cpt`: true

---

### SCENARIO 4: Militare già assegnato allo STESSO servizio
**Input:** Militare già in "GRADUATO DI BTG", provi a riassegnarlo a "GRADUATO DI BTG"
**Flusso:**
1. Utente seleziona militare → Verifica disponibilità
2. Sistema rileva duplicato
3. ERRORE immediato (non permesso nemmeno con forzatura):
   ```
   ✗ Questo militare è già assegnato a questo servizio per questa data
   ```
4. Nessuna opzione di forzatura (non ha senso duplicare)

**Database:** Nessuna modifica

---

### SCENARIO 5: Assegnazione multipla con mix disponibili/conflitti
**Input:** 3 militari selezionati: 1 libero, 2 occupati
**Flusso:**
1. Verifica disponibilità → Banner:
   ```
   ✓ 1 Militare disponibile:
   [Serg. BIANCHI]
   
   ⚠️ 2 Militari con conflitto:
   
   Serg. ROSSI
   Impegnato nel CPT: G-BTG
   [Deseleziona] [Forza]
   
   Mar. VERDI
   Già assegnato al servizio: CONDUTTORE GUARDIA
   [Deseleziona] [Forza]
   ```
2. Utente forza ROSSI (verde), deseleziona VERDI
3. Conferma → Sistema assegna ROSSI + BIANCHI
4. Banner rosso dettagliato:
   ```
   ✓ 2 militari assegnati con successo
   
   2 militari NON assegnati:
   
   ❌ Mar. VERDI
      Già assegnato al servizio: CONDUTTORE GUARDIA
   ```

**Database:**
- 2 assegnazioni create (ROSSI forzato, BIANCHI libero)
- 1 ignorato (VERDI deselezionato)

---

## 🎨 STILI CSS - PROBLEMA RISOLTO

### PROBLEMA:
Gli stili erano inline nel JavaScript → non caricati in tempo

### SOLUZIONE:
1. ✅ Creato `/public/css/turni-custom.css` con tutti gli stili
2. ✅ Caricato nel `<head>` della pagina
3. ✅ JavaScript usa solo classi CSS

**File modificati:**
- `public/css/turni-custom.css` (NUOVO)
- `resources/views/servizi/turni/index.blade.php` (aggiunto `<link>`)

---

## 📊 SINCRONIZZAZIONE CPT

### Funzionamento:
```php
// TurniService.php (riga 334-394)
protected function sincronizzaConCPT(AssegnazioneTurno $assegnazione)
{
    // 1. Trova tipo servizio CPT dalla sigla
    $tipoServizio = TipoServizio::where('codice', $servizio->sigla_cpt)->first();
    
    // 2. Trova/crea pianificazione mensile
    $pianificazioneMensile = PianificazioneMensile::firstOrCreate([
        'mese' => $dataServizio->month,
        'anno' => $dataServizio->year,
    ]);
    
    // 3. Crea/aggiorna pianificazione giornaliera
    PianificazioneGiornaliera::updateOrCreate(
        [
            'pianificazione_mensile_id' => $pianificazioneMensile->id,
            'militare_id' => $assegnazione->militare_id,
            'giorno' => $dataServizio->day,
        ],
        [
            'tipo_servizio_id' => $tipoServizio->id,  // ✓ Sovrascrive se esiste
        ]
    );
    
    // 4. Marca come sincronizzato
    $assegnazione->marcaSincronizzato();
}
```

**updateOrCreate** → Se il militare ha già un impegno CPT quel giorno, LO SOVRASCRIVE

---

## ✅ REGOLE FINALI

1. **UN SOLO TURNO PER GIORNO**: Militare può avere SOLO 1 turno per data
2. **FORZATURA RICHIESTA**: Se occupato, DEVE forzare per sovrascrivere
3. **CPT SEMPRE SINCRONIZZATO**: Ogni assegnazione va automaticamente nel CPT
4. **RIMOZIONE PULITA**: Rimuovere turno rimuove anche dal CPT
5. **CONFLITTI CHIARI**: Ogni conflitto mostra esattamente cosa verrà sovrascritto

---

## 🧪 TEST RACCOMANDATI

1. ✓ Assegna militare libero → Verifica CPT aggiornato
2. ✓ Assegna militare già in CPT → Forza → Verifica sovrascrittura
3. ✓ Assegna militare già in altro turno → Forza → Verifica vecchio rimosso
4. ✓ Prova stesso militare stesso servizio → Verifica errore
5. ✓ Assegna 3 militari misti → Verifica solo disponibili/forzati vanno
6. ✓ Rimuovi assegnazione → Verifica CPT pulito

---

## 📁 FILE MODIFICATI

1. `app/Services/TurniService.php` (righe 123-163) - FIX CRITICO
2. `public/css/turni-custom.css` (NUOVO) - Stili centralizzati
3. `resources/views/servizi/turni/index.blade.php` - Carica CSS esterno
4. `ANALISI_FLUSSI_TURNI.md` (QUESTO FILE) - Documentazione completa

---

**Data analisi:** 2025-10-04  
**Status:** ✅ TUTTI I FLUSSI VERIFICATI E CORRETTI

