// Test JavaScript per la pianificazione
function openEditModal(cell) {
    const militareId = cell.getAttribute('data-militare-id');
    const giorno = cell.getAttribute('data-giorno');
    
    const row = cell.closest('tr');
    if (!row) return;
    
    const gradoCell = row.cells[0];
    const cognomeCell = row.cells[1]; 
    const nomeCell = row.cells[2];
    
    if (!gradoCell || !cognomeCell || !nomeCell) return;
    
    const grado = gradoCell.textContent.trim();
    const cognomeLink = cognomeCell.querySelector('a');
    const cognome = cognomeLink ? cognomeLink.textContent.trim() : cognomeCell.textContent.trim();
    const nome = nomeCell.textContent.trim();
    
    // Solo Grado Cognome Nome (senza compagnia)
    const nomeCompleto = grado + ' ' + cognome + ' ' + nome;
    const giornoCompleto = giorno + ' Settembre';
    
    // Estrai il codice dal badge se esiste
    let codiceServizio = '';
    const badge = cell.querySelector('.badge');
    if (badge) {
        codiceServizio = badge.textContent.trim();
    }
    
    const modal = document.getElementById('editGiornoModal');
    const editMilitareId = document.getElementById('editMilitareId');
    const editGiorno = document.getElementById('editGiorno');
    const editMilitareNome = document.getElementById('editMilitareNome');
    const editGiornoLabel = document.getElementById('editGiornoLabel');
    const editTipoServizio = document.getElementById('editTipoServizio');
    
    if (!modal) return;
    
    if (editMilitareId) editMilitareId.value = militareId;
    if (editGiorno) editGiorno.value = giorno;
    if (editTipoServizio) editTipoServizio.value = codiceServizio;
    if (editMilitareNome) editMilitareNome.value = nomeCompleto;
    if (editGiornoLabel) editGiornoLabel.value = giornoCompleto;
    
    // Configura il date picker "Fino al giorno"
    const editGiornoFine = document.getElementById('editGiornoFine');
    if (editGiornoFine && window.pageData) {
        const mese = window.pageData.mese;
        const anno = window.pageData.anno;
        
        // Imposta la data minima (giorno successivo a quello selezionato)
        const giornoSuccessivo = parseInt(giorno) + 1;
        const giorniNelMese = new Date(anno, mese, 0).getDate();
        
        if (giornoSuccessivo <= giorniNelMese) {
            // Il giorno successivo è nello stesso mese
            const dataMinima = `${anno}-${mese.toString().padStart(2, '0')}-${giornoSuccessivo.toString().padStart(2, '0')}`;
            editGiornoFine.setAttribute('min', dataMinima);
        } else {
            // Il giorno successivo è nel mese successivo
            const meseSuccessivo = mese === 12 ? 1 : mese + 1;
            const annoSuccessivo = mese === 12 ? anno + 1 : anno;
            const dataMinima = `${annoSuccessivo}-${meseSuccessivo.toString().padStart(2, '0')}-01`;
            editGiornoFine.setAttribute('min', dataMinima);
        }
        
        // Imposta la data massima (un anno dopo)
        const dataMassima = `${anno + 1}-12-31`;
        editGiornoFine.setAttribute('max', dataMassima);
        
        // Reset del valore
        editGiornoFine.value = '';
        
        // Aggiungi placeholder personalizzato
        editGiornoFine.setAttribute('placeholder', 'Seleziona data di fine (opzionale)');
        
        // Aggiungi listener per mostrare il range selezionato
        editGiornoFine.addEventListener('change', function() {
            updateRangePreview(giorno, mese, anno, this.value);
        });
    }
    
    // Funzione per mostrare l'anteprima del range
    function updateRangePreview(giornoInizio, meseInizio, annoInizio, dataFine) {
        // Rimuovi precedenti indicatori
        const existingPreview = document.getElementById('rangePreview');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        if (!dataFine) return;
        
        // Calcola il range
        const dateFine = new Date(dataFine + 'T00:00:00');
        const giornoFine = dateFine.getDate();
        const meseFine = dateFine.getMonth() + 1;
        const annoFine = dateFine.getFullYear();
        
        // Conta i giorni
        const dataInizio = new Date(annoInizio, meseInizio - 1, giornoInizio);
        const dataFineObj = new Date(annoFine, meseFine - 1, giornoFine);
        const diffTime = Math.abs(dataFineObj - dataInizio);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        // Crea l'indicatore minimal
        const preview = document.createElement('div');
        preview.id = 'rangePreview';
        preview.innerHTML = `
            <i class="fas fa-calendar-alt me-1"></i>
            ${giornoInizio}/${meseInizio}/${annoInizio} → ${giornoFine}/${meseFine}/${annoFine} 
            <span class="badge ms-2">${diffDays}</span>
        `;
        
        // Inserisci dopo il date picker
        const datePicker = document.getElementById('editGiornoFine');
        datePicker.parentNode.insertBefore(preview, datePicker.nextSibling);
    }
    
            if (typeof bootstrap !== 'undefined') {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
                // Quando il modal è completamente mostrato, imposta il focus sul primo elemento
                modal.addEventListener('shown.bs.modal', function() {
                    const firstFocusable = modal.querySelector('select, input, button');
                    if (firstFocusable) {
                        firstFocusable.focus();
                    }
                }, { once: true }); // Usa once per evitare listener multipli
            }
}

function syncTableScroll() {
    const headerContainer = document.querySelector('.table-header-fixed');
    const bodyContainer = document.querySelector('.table-body-scroll');
    
    if (!headerContainer || !bodyContainer) return;
    
    bodyContainer.addEventListener('scroll', function() {
        headerContainer.style.transform = 'translateX(-' + bodyContainer.scrollLeft + 'px)';
    });
}

// Salva le modifiche
function setupSaveButton() {
    const saveBtn = document.getElementById('saveGiornoBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            const form = document.getElementById('editGiornoForm');
            if (!form) return;
            
            const militareIdEl = document.getElementById('editMilitareId');
            const giornoEl = document.getElementById('editGiorno');
            const pianificazioneMensileIdEl = document.getElementById('editPianificazioneMensileId');
            const tipoServizioEl = document.getElementById('editTipoServizio');
            const giornoFineEl = document.getElementById('editGiornoFine');
            
            const militareId = militareIdEl ? militareIdEl.value : '';
            const giorno = parseInt(giornoEl ? giornoEl.value : '');
            const pianificazioneMensileId = pianificazioneMensileIdEl ? pianificazioneMensileIdEl.value : '';
            
            // IMPORTANTE: Usa data-id invece di value per ottenere l'ID numerico
            let tipoServizioId = '';
            if (tipoServizioEl && tipoServizioEl.value) {
                const selectedOption = tipoServizioEl.options[tipoServizioEl.selectedIndex];
                tipoServizioId = selectedOption.getAttribute('data-id') || '';
                
                console.log('=== DEBUG TIPO SERVIZIO ===');
                console.log('Select value:', tipoServizioEl.value);
                console.log('Selected option:', selectedOption);
                console.log('data-id attribute:', selectedOption.getAttribute('data-id'));
                console.log('tipoServizioId finale:', tipoServizioId);
                console.log('tipo di tipoServizioId:', typeof tipoServizioId);
            }
            
            const giornoFine = giornoFineEl ? giornoFineEl.value : '';
            
            console.log('=== DATI DA INVIARE ===');
            console.log('militareId:', militareId);
            console.log('giorno:', giorno);
            console.log('pianificazioneMensileId:', pianificazioneMensileId);
            console.log('tipoServizioId:', tipoServizioId);
            console.log('giornoFine:', giornoFine);
            
            if (!militareId || !giorno || !pianificazioneMensileId) {
                alert('Dati mancanti per il salvataggio');
                return;
            }
            
            // Determina il range di giorni
            const giornoInizio = giorno;
            let giornoFineNum, meseFine, annoFine;
            
            if (giornoFine) {
                // Parse del formato ISO date "YYYY-MM-DD"
                const dateFine = new Date(giornoFine + 'T00:00:00');
                giornoFineNum = dateFine.getDate();
                meseFine = dateFine.getMonth() + 1; // I mesi in JavaScript sono 0-based
                annoFine = dateFine.getFullYear();
            } else {
                giornoFineNum = giorno;
                meseFine = window.pageData.mese;
                annoFine = window.pageData.anno;
            }
            
            // Validazione: il giorno fine deve essere >= giorno inizio (se stesso mese/anno)
            if (giornoFine && meseFine === window.pageData.mese && annoFine === window.pageData.anno && giornoFineNum < giornoInizio) {
                alert('Il giorno di fine deve essere maggiore o uguale al giorno di inizio');
                return;
            }
            
            // Salva per ogni giorno nel range
            const promises = [];
            const giorniDaSalvare = [];
            
            // Genera tutti i giorni nel range
            let currentGiorno = giornoInizio;
            let currentMese = window.pageData.mese;
            let currentAnno = window.pageData.anno;
            
            while (true) {
                giorniDaSalvare.push({
                    giorno: currentGiorno,
                    mese: currentMese,
                    anno: currentAnno
                });
                
                // Se abbiamo raggiunto il giorno fine, fermati
                if (currentGiorno === giornoFineNum && currentMese === meseFine && currentAnno === annoFine) {
                    break;
                }
                
                // Passa al giorno successivo
                const giorniNelMese = new Date(currentAnno, currentMese, 0).getDate();
                if (currentGiorno < giorniNelMese) {
                    currentGiorno++;
                } else {
                    // Passa al mese successivo
                    currentGiorno = 1;
                    if (currentMese === 12) {
                        currentMese = 1;
                        currentAnno++;
                    } else {
                        currentMese++;
                    }
                }
                
                // Protezione contro loop infiniti (max 365 giorni)
                if (giorniDaSalvare.length > 365) {
                    alert('Range troppo ampio. Massimo 365 giorni.');
                    return;
                }
            }
            
            console.log('=== GIORNI DA SALVARE ===');
            console.log('Totale giorni:', giorniDaSalvare.length);
            console.log('Array giorni:', giorniDaSalvare);
            
        // Se c'è un range di giorni, usa il nuovo endpoint batch
        if (giorniDaSalvare.length > 1) {
            console.log('📦 Chiamando saveDaysRange per', giorniDaSalvare.length, 'giorni');
            saveDaysRange(militareId, giorniDaSalvare, tipoServizioId)
                .then(result => {
                    // Chiudi il modal
                    const modal = document.getElementById('editGiornoModal');
                    if (modal && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                            
                            // Ripristina il focus alla cella che ha aperto il modal
                            setTimeout(() => {
                                const originalCell = document.querySelector(`[data-militare-id="${militareId}"][data-giorno="${giorno}"]`);
                                if (originalCell) {
                                    originalCell.focus();
                                }
                            }, 300);
                        }
                    }
                    
                    // Aggiorna solo le celle visibili (del mese corrente)
                    if (result.success) {
                        console.log('🔄 Aggiornamento celle per range...');
                        console.log('Pianificazioni ricevute:', result.pianificazioni.length);
                        console.log('Giorni da aggiornare:', giorniDaSalvare);
                        console.log('Mese corrente:', window.pageData.mese, 'Anno corrente:', window.pageData.anno);
                        
                        result.pianificazioni.forEach((data, index) => {
                            if (index < giorniDaSalvare.length) {
                                const dayData = giorniDaSalvare[index];
                                console.log(`Cella ${index}: giorno=${dayData.giorno}, mese=${dayData.mese}, anno=${dayData.anno}`);
                                
                                // Aggiorna solo se il giorno è nel mese corrente
                                if (dayData.mese === window.pageData.mese && dayData.anno === window.pageData.anno) {
                                    console.log(`✅ Aggiorno cella militare=${militareId}, giorno=${dayData.giorno}`);
                                    updateCellContent(militareId, dayData.giorno, data);
                                } else {
                                    console.log(`⏭️ Salto cella (mese/anno diverso)`);
                                }
                            }
                        });
                    }
                    
                    // Reset del campo giorno fine e rimuovi anteprima
                    if (giornoFineEl) {
                        giornoFineEl.value = '';
                    }
                    
                    // Rimuovi l'anteprima del range
                    const rangePreview = document.getElementById('rangePreview');
                    if (rangePreview) {
                        rangePreview.remove();
                    }
                })
                .catch(error => {
                    alert('Errore nel salvataggio: ' + error.message);
                });
        } else {
            // Per un singolo giorno, usa il metodo esistente
            console.log('📝 Chiamando saveSingleDay per il giorno', giorno);
            saveSingleDay(militareId, giorno, pianificazioneMensileId, tipoServizioId)
                .then(result => {
                    // Chiudi il modal
                    const modal = document.getElementById('editGiornoModal');
                    if (modal && typeof bootstrap !== 'undefined') {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                            
                            // Ripristina il focus alla cella che ha aperto il modal
                            setTimeout(() => {
                                const originalCell = document.querySelector(`[data-militare-id="${militareId}"][data-giorno="${giorno}"]`);
                                if (originalCell) {
                                    originalCell.focus();
                                }
                            }, 300);
                        }
                    }
                    
                    // Aggiorna la cella se il salvataggio è riuscito
                    if (result.success) {
                        updateCellContent(militareId, giorno, result.pianificazione);
                    }
                    
                    // Reset del campo giorno fine e rimuovi anteprima
                    if (giornoFineEl) {
                        giornoFineEl.value = '';
                    }
                    
                    // Rimuovi l'anteprima del range
                    const rangePreview = document.getElementById('rangePreview');
                    if (rangePreview) {
                        rangePreview.remove();
                    }
                    
                })
                .catch(error => {
                    alert('Errore nel salvataggio: ' + error.message);
                });
        }
    });
    }
}

// Funzione per salvare un singolo giorno
function saveSingleDay(militareId, giorno, pianificazioneMensileId, tipoServizioId, mese = null, anno = null) {
    console.log('🔵 saveSingleDay chiamata con:', { militareId, giorno, pianificazioneMensileId, tipoServizioId, mese, anno });
    
    const updateUrl = window.location.origin + '/C2MS/public/cpt/militare/' + militareId + '/update-giorno';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    console.log('📤 URL chiamata:', updateUrl);
    
    // Converti tipoServizioId in numero se è una stringa
    const tipoServizioIdNumeric = tipoServizioId ? parseInt(tipoServizioId) : null;
    
    const requestData = {
        giorno: giorno,
        pianificazione_mensile_id: pianificazioneMensileId,
        tipo_servizio_id: tipoServizioIdNumeric
    };
    
    // Aggiungi mese e anno se specificati
    if (mese && anno) {
        requestData.mese = mese;
        requestData.anno = anno;
    }
    
    console.log('=== CHIAMATA FETCH ===');
    console.log('URL:', updateUrl);
    console.log('Request Data:', requestData);
    console.log('tipo_servizio_id type:', typeof requestData.tipo_servizio_id);
    
    return fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('=== RISPOSTA SERVER ===');
        console.log('Status:', response.status);
        console.log('OK:', response.ok);
        return response.json();
    })
    .then(data => {
        console.log('=== DATI RICEVUTI ===');
        console.log('Data:', data);
        return data;
    });
}

// Funzione per salvare un range di giorni (nuovo endpoint batch)
function saveDaysRange(militareId, giorniDaSalvare, tipoServizioId) {
    console.log('🟢 saveDaysRange chiamata con:', { militareId, giorniCount: giorniDaSalvare.length, tipoServizioId });
    
    const updateUrl = window.location.origin + '/C2MS/public/cpt/militare/' + militareId + '/update-giorni-range';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    console.log('📤 URL chiamata:', updateUrl);
    
    const bodyData = {
        giorni: giorniDaSalvare,
        tipo_servizio_id: tipoServizioId || null
    };
    
    console.log('📨 Dati inviati:', bodyData);

    return fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(response => {
        console.log('📥 Risposta ricevuta:', response.status, response.statusText);
        if (!response.ok) {
            throw new Error('Errore nella risposta del server: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('✅ Dati range salvati con successo:', data);
        return data;
    })
    .catch(error => {
        console.error('❌ Errore saveDaysRange:', error);
        throw error;
    });
}

// Funzione per aggiornare il contenuto di una cella specifica
function updateCellContent(militareId, giorno, pianificazioneData) {
    console.log('🎨 updateCellContent chiamata:', { militareId, giorno });
    console.log('📋 Dati pianificazione:', pianificazioneData);
    
    const selector = '[data-militare-id="' + militareId + '"][data-giorno="' + giorno + '"]';
    console.log('🔍 Query selector:', selector);
    
    const cell = document.querySelector(selector);
    console.log('📍 Cella trovata:', cell ? 'SI ✅' : 'NO ❌');
    
    if (!cell) {
        console.error('❌ Cella non trovata per militareId=' + militareId + ', giorno=' + giorno);
        return;
    }
    
    // Se c'è una pianificazione con tipo servizio, COLORA LA CELLA
    if (pianificazioneData && pianificazioneData.tipo_servizio && pianificazioneData.tipo_servizio.codice) {
        const codice = pianificazioneData.tipo_servizio.codice;
        console.log('🎯 Codice CPT da visualizzare:', codice);
        
        // Mappa dei codici GIALLI (ASSENTE e ADD./APP./CATTEDRE)
        const codiciGialli = ['LS', 'LO', 'LM', 'P', 'TIR', 'TRAS', 'APS1', 'APS2', 'APS3', 'APS4', 
                              'AL-ELIX', 'AL-MCM', 'AL-BLS', 'AL-CIED', 'AL-SM', 'AL-RM', 'AL-RSPP', 
                              'AL-LEG', 'AL-SEA', 'AL-MI', 'AL-PO', 'AL-PI', 'AP-M', 'AP-A', 'AC-SW', 
                              'AC', 'PEFO'];
        
        // Mappa dei codici ROSSI (PROVVEDIMENTI MEDICO SANITARI e OPERAZIONE)
        const codiciRossi = ['RMD', 'LC', 'IS', 'TO'];
        
        // Determina il colore della CELLA
        let bgColor = '';
        let textColor = '';
        
        if (codiciGialli.includes(codice)) {
            bgColor = '#ffff00';
            textColor = '#000000';
        } else if (codiciRossi.includes(codice)) {
            bgColor = '#ff0000';
            textColor = '#ffffff';
        } else {
            // VERDE per tutti gli altri (SERVIZIO, SUPP.CIS/EXE)
            bgColor = '#00b050';
            textColor = '#ffffff';
        }
        
        // Applica il colore direttamente alla CELLA TD
        cell.style.backgroundColor = bgColor;
        cell.style.color = textColor;
        cell.style.fontWeight = '600';
        cell.style.fontSize = '10px';
        
        // Metti il TESTO direttamente nella cella
        cell.textContent = codice;
        
        console.log('🎨 Cella colorata:', { codice, bgColor, textColor });
        
    } else {
        // Celle vuote - rimuovi colori di sfondo e metti trattino
        cell.style.backgroundColor = '';
        cell.style.color = '';
        cell.textContent = '-';
        console.log('⚪ Cella vuota');
    }
}

// Funzione per inizializzare i tooltip
function initTooltips() {
    // Inizializza tooltips esistenti
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    syncTableScroll();
    setupSaveButton();
    initTooltips();
    setupModalAccessibility();
    
    // Inizializza il modulo Filters per l'auto-submit
    if (typeof SUGECO !== 'undefined' && typeof SUGECO.Filters !== 'undefined') {
        SUGECO.Filters.init();
    }
});

// Gestione accessibilità del modal
function setupModalAccessibility() {
    const modal = document.getElementById('editGiornoModal');
    if (!modal) return;
    
    // Quando il modal si chiude, rimuovi il focus da tutti gli elementi
    modal.addEventListener('hidden.bs.modal', function() {
        // Rimuovi il focus da tutti gli elementi all'interno del modal
        const focusableElements = modal.querySelectorAll('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        focusableElements.forEach(element => {
            element.blur();
        });
        
        // Rimuovi aria-hidden per evitare conflitti
        modal.removeAttribute('aria-hidden');
    });
    
    // Quando il modal si mostra, gestisci correttamente aria-hidden
    modal.addEventListener('shown.bs.modal', function() {
        modal.removeAttribute('aria-hidden');
    });
    
    // Quando il modal inizia a nascondersi, imposta aria-hidden
    modal.addEventListener('hide.bs.modal', function() {
        modal.setAttribute('aria-hidden', 'true');
    });
    
    // Gestione del pulsante Annulla
    const cancelBtn = modal.querySelector('[data-bs-dismiss="modal"]');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            // Trova la cella originale e ripristina il focus
            const militareId = document.getElementById('editMilitareId')?.value;
            const giorno = document.getElementById('editGiorno')?.value;
            
            if (militareId && giorno) {
                setTimeout(() => {
                    const originalCell = document.querySelector(`[data-militare-id="${militareId}"][data-giorno="${giorno}"]`);
                    if (originalCell) {
                        originalCell.focus();
                    }
                }, 300);
            }
        });
    }
}
