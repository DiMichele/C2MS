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
            const tipoServizioId = tipoServizioEl ? tipoServizioEl.value : '';
            const giornoFine = giornoFineEl ? giornoFineEl.value : '';
            
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
            
        // Se c'è un range di giorni, usa il nuovo endpoint batch
        if (giorniDaSalvare.length > 1) {
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
                        result.pianificazioni.forEach((data, index) => {
                            if (index < giorniDaSalvare.length) {
                                const dayData = giorniDaSalvare[index];
                                // Aggiorna solo se il giorno è nel mese corrente
                                if (dayData.mese === window.pageData.mese && dayData.anno === window.pageData.anno) {
                                    updateCellContent(militareId, dayData.giorno, data);
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
    const updateUrl = window.location.origin + '/C2MS/public/cpt/militare/' + militareId + '/update-giorno';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const requestData = {
        giorno: giorno,
        pianificazione_mensile_id: pianificazioneMensileId,
        tipo_servizio_id: tipoServizioId || null
    };
    
    // Aggiungi mese e anno se specificati
    if (mese && anno) {
        requestData.mese = mese;
        requestData.anno = anno;
    }
    
    return fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json());
}

// Funzione per salvare un range di giorni (nuovo endpoint batch)
function saveDaysRange(militareId, giorniDaSalvare, tipoServizioId) {
    const updateUrl = window.location.origin + '/C2MS/public/cpt/militare/' + militareId + '/update-giorni-range';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const bodyData = {
        giorni: giorniDaSalvare,
        tipo_servizio_id: tipoServizioId || null
    };

    return fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(bodyData)
    })
    .then(response => response.json());
}

// Funzione per aggiornare il contenuto di una cella specifica
function updateCellContent(militareId, giorno, pianificazioneData) {
    const cell = document.querySelector('[data-militare-id="' + militareId + '"][data-giorno="' + giorno + '"]');
    if (!cell) return;
    
    // Pulisci il contenuto attuale
    cell.innerHTML = '';
    
    // Se c'è una pianificazione con tipo servizio, aggiungi il badge
    if (pianificazioneData && pianificazioneData.tipo_servizio && pianificazioneData.tipo_servizio.codice) {
        const codice = pianificazioneData.tipo_servizio.codice;
        
        // Determina il colore basato sul codice
        let colore = 'light';
        let inlineStyle = '';
        let nomeCompleto = '';
        
        // Mappa completa dei codici con nomi e colori - CODICI CORRETTI DALL'IMMAGINE
        const codiciMap = {
            // ASSENTE - Giallo
            'LS': { nome: 'LICENZA STRAORD.', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'LO': { nome: 'LICENZA ORD.', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'LM': { nome: 'LICENZA DI MATERNITA\'', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'P': { nome: 'PERMESSINO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'TIR': { nome: 'TIROCINIO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'TRAS': { nome: 'TRASFERITO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            
            // PROVVEDIMENTI MEDICO SANITARI - Rosso
            'RMD': { nome: 'RIPOSO MEDICO DOMICILIARE', colore: 'cpt-rosso', style: 'background-color: #ff0000 !important; color: white !important;' },
            'LC': { nome: 'LICENZA DI CONVALESCENZA', colore: 'cpt-rosso', style: 'background-color: #ff0000 !important; color: white !important;' },
            'IS': { nome: 'ISOLAMENTO/QUARANTENA', colore: 'cpt-rosso', style: 'background-color: #ff0000 !important; color: white !important;' },
            
            // SERVIZIO - Verde
            'S-G1': { nome: 'GUARDIA D\'AVANZO LUNGA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-G2': { nome: 'GUARDIA D\'AVANZO CORTA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-SA': { nome: 'SORVEGLIANZA D\'AVANZO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-CD1': { nome: 'CONDUTTORE GUARDIA LUNGO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-CD2': { nome: 'CONDUTTORE PIAN DEL TERMINE CORTO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-SG': { nome: 'SOTTUFFICIALE DI GIORNATA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-CG': { nome: 'COMANDANTE DELLA GUARDIA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-UI': { nome: 'UFFICIALE DI ISPEZIONE', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-UP': { nome: 'UFFICIALE DI PICCHETTO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-AE': { nome: 'AREE ESTERNE', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-ARM': { nome: 'ARMIERE DI SERVIZIO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'SI-GD': { nome: 'SERVIZIO ISOLATO-GUARDIA DISTACCATA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'SI': { nome: 'SERVIZIO ISOLATO-CAPOMACCHINA/CAU', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'SI-VM': { nome: 'VISITA MEDICA', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            'S-PI': { nome: 'PRONTO IMPIEGO', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' },
            
            // OPERAZIONE - Rosso
            'TO': { nome: 'TEATRO OPERATIVO', colore: 'cpt-rosso', style: 'background-color: #ff0000 !important; color: white !important;' },
            
            // ADD./APP./CATTEDRE - Giallo
            'APS1': { nome: 'PRELIEVI', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'APS2': { nome: 'VACCINI', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'APS3': { nome: 'ECG', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'APS4': { nome: 'IDONEITA', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-ELIX': { nome: 'ELITRASPORTO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-MCM': { nome: 'MCM', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-BLS': { nome: 'BLS', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-CIED': { nome: 'C-IED', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-SM': { nome: 'STRESS MANAGEMENT', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-RM': { nome: 'RAPPORTO MEDIA', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-RSPP': { nome: 'RSPP', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-LEG': { nome: 'ASPETTI LEGALI', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-SEA': { nome: 'SEXUAL EXPLOITATION AND ABUSE', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-MI': { nome: 'MALATTIE INFETTIVE', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-PO': { nome: 'PROPAGANDA OSTILE', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AL-PI': { nome: 'PUBBLICA INFORMAZIONE E COMUNICAZIONE', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AP-M': { nome: 'MANTENIMENTO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AP-A': { nome: 'APPRONTAMENTO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AC-SW': { nome: 'CORSO IN SMART WORKING', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'AC': { nome: 'CORSO SERVIZIO ISOLATO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            'PEFO': { nome: 'PEFO', colore: 'cpt-giallo', style: 'background-color: #ffff00 !important; color: black !important;' },
            
            // SUPP.CIS/EXE - Verde
            'EXE': { nome: 'ESERCITAZIONE', colore: 'cpt-verde', style: 'background-color: #00b050 !important; color: white !important;' }
        };
        
        // Trova il codice nella mappa
        if (codiciMap[codice]) {
            colore = codiciMap[codice].colore;
            inlineStyle = codiciMap[codice].style;
            nomeCompleto = codiciMap[codice].nome;
        } else {
            // Default per codici non riconosciuti
            colore = 'cpt-verde';
            inlineStyle = 'background-color: #00b050 !important; color: white !important;';
            nomeCompleto = codice;
        }
        
        // Crea il badge
        const badge = document.createElement('span');
        badge.className = 'badge ' + colore;
        badge.style.cssText = 'font-size: 9px; padding: 2px 4px; ' + inlineStyle;
        badge.textContent = codice;
        
        // Aggiungi tooltip per mostrare il nome completo
        badge.setAttribute('data-bs-toggle', 'tooltip');
        badge.setAttribute('data-bs-placement', 'top');
        badge.setAttribute('title', nomeCompleto);
        
        cell.appendChild(badge);
        
        // Reinizializza i tooltip per il nuovo badge
        if (typeof bootstrap !== 'undefined') {
            new bootstrap.Tooltip(badge);
        }
    } else {
        // Se non c'è tipo servizio (Nessun impegno), mostra solo il trattino
        cell.innerHTML = '<span class="text-muted" style="font-size: 10px;">-</span>';
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
    if (typeof C2MS !== 'undefined' && typeof C2MS.Filters !== 'undefined') {
        C2MS.Filters.init();
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
