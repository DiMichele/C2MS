# 🚀 Guida Rapida - Gestione CPT

## 📍 Come Accedere

1. Login con account **admin.sistema** (password: `admin123`)
2. Menu **Admin** → **Gestione CPT**
3. URL diretto: `http://localhost/C2MS/public/gestione-cpt`

---

## ⚡ Azioni Rapide

### ➕ Creare un Nuovo Codice

1. Click su **"Nuovo Codice"** (pulsante verde in alto)
2. Inserisci:
   - **Codice**: es. "FP" (sarà automaticamente maiuscolo)
   - **Colore**: scegli dal picker o click sui suggerimenti emoji
   - **Attività Specifica**: es. "Franco di Presenza"
   - **Categoria Impiego**: seleziona dalla lista
3. (Opzionale) Compila gerarchia e descrizione
4. Click **"Salva Codice"**

### ✏️ Modificare un Codice

1. Nella tabella, click sull'icona **matita** 🖊️
2. Modifica i campi desiderati
3. Click **"Salva Modifiche"**

### 👁️ Attivare/Disattivare

- Click sull'icona **occhio** nella colonna Azioni
- I codici disattivati non saranno utilizzabili nel CPT

### 📋 Duplicare un Codice

- Click sull'icona **doppio foglio** 📄📄
- Ottimo per creare varianti di codici esistenti

### 🗑️ Eliminare un Codice

- Click sull'icona **cestino** 🗑️
- ⚠️ Non puoi eliminare codici già utilizzati

---

## 🎨 Colori Consigliati

Click direttamente sulle emoji nella sezione colore:

- 🟢 Verde CPT → DISPONIBILE/SERVIZIO (TO, S-UI)
- 🟡 Giallo CPT → ASSENTE (lo, p, lm)
- 🔴 Rosso CPT → NON IMPIEGABILE (RMD)
- 🟠 Arancione CPT → APPRONTAMENTI (KOSOVO, LCC)
- 🔵 Blu CPT → SERVIZI SPECIALI (PDT1, G1)
- ⚫ Nero → COMANDO (S-CG, S-SG)

---

## 🔍 Ricerca e Filtri

**Ricerca rapida**: Digita nel campo "Ricerca Codice/Descrizione"

**Filtri disponibili**:
- Macro Attività
- Tipo Attività
- Categoria Impiego
- Stato (Attivo/Inattivo)

**Reset filtri**: Click sull'icona 🔄

---

## 💾 Esportare i Codici

Click su **"Esporta CSV"** → Scarica file Excel compatibile

---

## ⚠️ Note Importanti

1. **Codici univoci**: Non puoi creare due codici con lo stesso nome
2. **Uso in servizi**: Se un codice è usato, non puoi eliminarlo (solo disattivarlo)
3. **Aggiornamenti automatici**: Le modifiche si riflettono subito nel CPT
4. **Solo Admin**: Solo gli amministratori possono accedere a questa pagina

---

## 🎯 Esempi Pratici

### Creare "Franco di Presenza"

```
Codice: FP
Colore: 🟡 Giallo (#ffff00)
Macro Attività: ASSENTE
Tipo Attività: PERMESSO
Attività Specifica: Franco di Presenza
Categoria Impiego: INDISPONIBILE
Ordine: 0
Stato: ✅ Attivo
```

### Creare "Servizio Guardia 3"

```
Codice: S-G3
Colore: 🟢 Verde (#00b050)
Macro Attività: SERVIZIO
Tipo Attività: GUARDIA
Attività Specifica: Servizio Guardia 3
Categoria Impiego: PRESENTE_SERVIZIO
Ordine: 0
Stato: ✅ Attivo
```

---

## ❓ FAQ

**Q: Posso cambiare il colore di un codice esistente?**  
A: Sì! Modifica il codice e scegli un nuovo colore.

**Q: Come riordino i codici?**  
A: Usa il campo "Ordine" (numero basso = prima posizione)

**Q: I codici disattivati spariscono?**  
A: No, restano nel database ma non sono usabili nel CPT

**Q: Posso importare codici da Excel?**  
A: Non ancora, ma puoi crearli manualmente o usare la duplicazione

---

📄 **Documentazione completa**: Vedi `GESTIONE_CPT_IMPLEMENTAZIONE.md`

