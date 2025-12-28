/**
 * Script per filtrare i plotoni in base alla compagnia selezionata
 * Gestisce la dipendenza Compagnia -> Plotone nei filtri
 * Usa l'attributo data-compagnia-id per determinare l'appartenenza
 * Il filtro plotone è DISABILITATO finché non si seleziona una compagnia
 */

document.addEventListener('DOMContentLoaded', function() {
    const compagniaSelect = document.getElementById('compagnia');
    const plotoneSelect = document.getElementById('plotone_id');
    
    if (!compagniaSelect || !plotoneSelect) {
        return; // Non siamo in una pagina con questi filtri
    }
    
    // Salva tutte le opzioni dei plotoni all'avvio (escludi la prima opzione)
    const tuttiIPlotoni = Array.from(plotoneSelect.options).slice(1).map(option => ({
        value: option.value,
        text: option.text,
        compagniaId: option.getAttribute('data-compagnia-id'),
        selected: option.selected
    }));
    
    function aggiornaStatoPlotone() {
        const compagniaSelezionata = compagniaSelect.value;
        const plotoneAttuale = plotoneSelect.value;
        
        // Se nessuna compagnia selezionata, disabilita il select
        if (!compagniaSelezionata) {
            plotoneSelect.disabled = true;
            plotoneSelect.title = 'Seleziona prima una compagnia';
            
            // Rimuovi tutte le opzioni tranne la prima
            while (plotoneSelect.options.length > 1) {
                plotoneSelect.remove(1);
            }
            plotoneSelect.options[0].text = 'Seleziona prima compagnia';
            plotoneSelect.value = '';
            return;
        }
        
        // Abilita il select
        plotoneSelect.disabled = false;
        plotoneSelect.title = '';
        plotoneSelect.options[0].text = 'Tutti';
        
        // Rimuovi tutte le opzioni tranne "Tutti"
        while (plotoneSelect.options.length > 1) {
            plotoneSelect.remove(1);
        }
        
        // Filtra e aggiungi i plotoni della compagnia selezionata
        tuttiIPlotoni.forEach(plotone => {
            if (plotone.compagniaId === compagniaSelezionata) {
                const option = document.createElement('option');
                option.value = plotone.value;
                option.text = plotone.text;
                option.setAttribute('data-compagnia-id', plotone.compagniaId);
                
                // Mantieni la selezione se il plotone è ancora visibile
                if (plotone.value === plotoneAttuale) {
                    option.selected = true;
                }
                
                plotoneSelect.add(option);
            }
        });
        
        // Se il plotone attualmente selezionato non è più visibile, resetta
        const plotoneVisibile = Array.from(plotoneSelect.options).some(
            opt => opt.value === plotoneAttuale && opt.value !== ''
        );
        if (!plotoneVisibile && plotoneAttuale) {
            plotoneSelect.value = '';
        }
    }
    
    // Applica il filtro quando cambia la compagnia
    compagniaSelect.addEventListener('change', function() {
        aggiornaStatoPlotone();
    });
    
    // Applica lo stato iniziale
    aggiornaStatoPlotone();
});
