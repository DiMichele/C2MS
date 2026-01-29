# Piano di Implementazione: Export Rapportino Excel "Maniacale"

Aggiungerò un nuovo pulsante floating nella pagina dei Ruolini che genererà un file Excel con lo stesso identico formato ministeriale del file `rapportino.xlsx`.

## 1. Modifiche UI
*   Aggiunta di un secondo pulsante floating in `[resources/views/ruolini/index.blade.php](resources/views/ruolini/index.blade.php)` posizionato sopra quello esistente.
*   Nuova icona (`fas fa-file-invoice`) e colore distintivo per differenziarlo dall'export standard.
*   Aggiornamento dei tooltip e dell'accessibilità.

## 2. Nuova Rotta
*   Definizione della rotta `ruolini.export-rapportino` in `[routes/web.php](routes/web.php)` per gestire la richiesta di download.

## 3. Logica del Controller
*   Implementazione del metodo `exportRapportino` in `[app/Http/Controllers/RuoliniController.php](app/Http/Controllers/RuoliniController.php)`.
*   **Mappatura Dati**:
    *   **Area Sinistra**: Elenco completo della forza effettiva ordinata per grado e nome.
    *   **Tabella Forza Effettiva (Top Right)**: Conteggio incrociato per Categoria (VFP1, VFP4, Ufficiali, Sottufficiali) e Sesso (Uomini/Donne).
    *   **Tabella Assenze (Middle Right)**: Matrice dinamica che mappa i codici servizio (`tipi_servizio.codice`) alle colonne specifiche del rapportino (S.I., ESTERO, L.O., ecc.).
    *   **Tabella Civili/VFP1/VFP4/PCM (Rightmost)**: Conteggio dettagliato per stato (Effettivi/Presenti/Assenti).
*   **Formattazione Maniacale**: Riproduzione fedele di bordi, colori (Giallo, Rosso, Blu Navy), dimensioni celle e legenda.

## 4. Servizi Utilizzati
*   Utilizzerò `PhpSpreadsheet` direttamente per il controllo totale del layout.
*   Sfrutterò `ExcelStyleService` per mantenere la coerenza stilistica del progetto.

---
### To-do list
1. Aggiungere il pulsante floating in `index.blade.php`. [pending]
2. Aggiungere la rotta in `web.php`. [pending]
3. Implementare la logica di esportazione in `RuoliniController.php`. [pending]
4. Verificare la mappatura dei gradi e dei codici assenza. [pending]
