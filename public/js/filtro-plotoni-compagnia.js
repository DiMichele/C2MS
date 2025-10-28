/**
 * Script per filtrare i plotoni in base alla compagnia selezionata
 * Gestisce la dipendenza Compagnia -> Plotone nei filtri
 */

document.addEventListener('DOMContentLoaded', function() {
    const compagniaSelect = document.getElementById('compagnia');
    const plotoneSelect = document.getElementById('plotone_id');
    
    if (!compagniaSelect || !plotoneSelect) {
        return; // Non siamo in una pagina con questi filtri
    }
    
    // Salva tutte le opzioni dei plotoni all'avvio
    const tuttiIPlotoni = Array.from(plotoneSelect.options).slice(1); // Escludi "Tutti"
    
    // Mappa compagnia -> plotoni (basato sul nome del plotone)
    // Formato: "1° Plotone", "2° Plotone", "3° Plotone", "4° Plotone", "5° Plotone"
    // Ciascun plotone appartiene alla compagnia con lo stesso numero
    const plotoniPerCompagnia = {
        '1': ['1° Plotone', 'Plotone 1'],
        '2': ['2° Plotone', 'Plotone 2'],
        '3': ['3° Plotone', 'Plotone 3'],
        '4': ['4° Plotone', 'Plotone 4'],
        '5': ['5° Plotone', 'Plotone 5']
    };
    
    function filtraPlotoni() {
        const compagniaSelezionata = compagniaSelect.value;
        
        // Rimuovi tutte le opzioni tranne "Tutti"
        while (plotoneSelect.options.length > 1) {
            plotoneSelect.remove(1);
        }
        
        if (!compagniaSelezionata) {
            // Nessuna compagnia selezionata, mostra tutti i plotoni
            tuttiIPlotoni.forEach(option => {
                plotoneSelect.add(option.cloneNode(true));
            });
        } else {
            // Mostra solo i plotoni della compagnia selezionata
            const plotoniDaMostrare = plotoniPerCompagnia[compagniaSelezionata] || [];
            tuttiIPlotoni.forEach(option => {
                // Controlla se il nome del plotone contiene il numero della compagnia
                if (plotoniDaMostrare.some(nome => option.text.includes(nome) || option.text.includes(compagniaSelezionata))) {
                    plotoneSelect.add(option.cloneNode(true));
                }
            });
        }
    }
    
    // Applica il filtro quando cambia la compagnia
    compagniaSelect.addEventListener('change', function() {
        filtraPlotoni();
        // Reset del plotone selezionato se non è più visibile
        const plotoneAttualeVisibile = Array.from(plotoneSelect.options).some(
            opt => opt.selected && opt.value !== ''
        );
        if (!plotoneAttualeVisibile) {
            plotoneSelect.value = '';
        }
    });
    
    // Applica il filtro all'avvio se c'è già una compagnia selezionata
    if (compagniaSelect.value) {
        filtraPlotoni();
    }
});

