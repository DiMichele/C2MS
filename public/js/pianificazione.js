// Pianificazione CPT - JavaScript

// Funzione per calcolare il range consecutivo di un impegno
function getConsecutiveRange(row, startGiorno, codiceServizio) {
    if (!codiceServizio || codiceServizio === '-') return { start: startGiorno, end: startGiorno, count: 1 };
    
    const militareId = row.getAttribute('data-militare-id');
    let start = parseInt(startGiorno);
    let end = parseInt(startGiorno);
    
    // Cerca indietro per trovare l'inizio del range
    let currentGiorno = start - 1;
    while (currentGiorno >= 1) {
        const prevCell = row.querySelector(`[data-giorno="${currentGiorno}"]`);
        if (prevCell && prevCell.textContent.trim() === codiceServizio) {
            start = currentGiorno;
            currentGiorno--;
        } else {
            break;
        }
    }
    
    // Cerca avanti per trovare la fine del range
    currentGiorno = parseInt(startGiorno) + 1;
    const giorniNelMese = window.pageData ? new Date(window.pageData.anno, window.pageData.mese, 0).getDate() : 31;
    while (currentGiorno <= giorniNelMese) {
        const nextCell = row.querySelector(`[data-giorno="${currentGiorno}"]`);
        if (nextCell && nextCell.textContent.trim() === codiceServizio) {
            end = currentGiorno;
            currentGiorno++;
        } else {
            break;
        }
    }
    
    return { start, end, count: end - start + 1 };
}

function openEditModal(cell) {
    const militareId = cell.getAttribute('data-militare-id');
    const giorno = cell.getAttribute('data-giorno');
    const tipoServizioId = cell.getAttribute('data-tipo-servizio-id');
    
    const row = cell.closest('tr');
    if (!row) return;
    
    // L'indice delle celle corrette: 1=Grado, 2=Cognome, 3=Nome
    const gradoCell = row.cells[1];
    const cognomeCell = row.cells[2]; 
    const nomeCell = row.cells[3];
    
    if (!gradoCell || !cognomeCell || !nomeCell) return;
    
    const grado = gradoCell.textContent.trim();
    const cognomeLink = cognomeCell.querySelector('a');
    const cognome = cognomeLink ? cognomeLink.textContent.trim() : cognomeCell.textContent.trim();
    const nome = nomeCell.textContent.trim();
    
    // Formato: Grado Cognome Nome (senza Compagnia)
    const nomeCompleto = grado + ' ' + cognome + ' ' + nome;
    
    // Data formattata correttamente
    const mesiItaliani = window.pageData && window.pageData.mesiItaliani 
        ? window.pageData.mesiItaliani 
        : {1: 'Gennaio', 2: 'Febbraio', 3: 'Marzo', 4: 'Aprile', 5: 'Maggio', 6: 'Giugno', 7: 'Luglio', 8: 'Agosto', 9: 'Settembre', 10: 'Ottobre', 11: 'Novembre', 12: 'Dicembre'};
    const meseCorrente = window.pageData ? window.pageData.mese : new Date().getMonth() + 1;
    const giornoCompleto = giorno + ' ' + mesiItaliani[meseCorrente];
    
    // Prendi il codice servizio DALLA CELLA (il testo visualizzato)
    let codiceServizio = cell.textContent.trim();
    if (codiceServizio === '-') codiceServizio = '';
    
    // Calcola il range consecutivo dell'impegno corrente
    const currentRange = getConsecutiveRange(row, giorno, codiceServizio);
    // Salva il range originale per confronto quando si salva
    window.originalRange = currentRange;
    window.originalCodiceServizio = codiceServizio;
    
    const modal = document.getElementById('editGiornoModal');
    const editMilitareId = document.getElementById('editMilitareId');
    const editGiorno = document.getElementById('editGiorno');
    const editMilitareNome = document.getElementById('editMilitareNome');
    const editGiornoLabel = document.getElementById('editGiornoLabel');
    const editTipoServizio = document.getElementById('editTipoServizio');
    
    if (!modal) return;
    
    if (editMilitareId) editMilitareId.value = militareId;
    if (editGiorno) editGiorno.value = giorno;
    if (editMilitareNome) editMilitareNome.value = nomeCompleto;
    if (editGiornoLabel) editGiornoLabel.value = giornoCompleto;
    
    
    // Imposta il tipo servizio corretto usando il codice o l'ID
    if (editTipoServizio) {
        let found = false;
        
        // Prima prova a selezionare tramite l'ID (data-tipo-servizio-id)
        if (tipoServizioId && tipoServizioId.trim() !== '') {
            // Cerca l'opzione con data-id corrispondente
            for (let i = 0; i < editTipoServizio.options.length; i++) {
                const opt = editTipoServizio.options[i];
                const optId = opt.getAttribute('data-id');
                if (optId && optId === tipoServizioId) {
                    editTipoServizio.selectedIndex = i;
                    found = true;
                    break;
                }
            }
        }
        
        // Fallback: usa il codice dalla cella se non trovato tramite ID
        if (!found && codiceServizio && codiceServizio !== '-') {
            for (let i = 0; i < editTipoServizio.options.length; i++) {
                const opt = editTipoServizio.options[i];
                if (opt.value === codiceServizio) {
                    editTipoServizio.selectedIndex = i;
                    found = true;
                    break;
                }
            }
        }
        
        // Se ancora non trovato, imposta "Nessun impegno"
        if (!found) {
            editTipoServizio.value = '';
        }
    }
    
    const editGiornoFine = document.getElementById('editGiornoFine');
    if (editGiornoFine && window.pageData) {
        const mese = window.pageData.mese;
        const anno = window.pageData.anno;
        const giornoCorrente = parseInt(giorno);
        const giorniNelMese = new Date(anno, mese, 0).getDate();
        
        // Se c'è un range esistente, usa l'inizio del range come giorno di partenza
        const giornoInizioRange = (codiceServizio && currentRange.start) ? currentRange.start : giornoCorrente;
        
        // Salva il giorno di inizio del range per usarlo nel salvataggio
        window.originalRangeStart = giornoInizioRange;
        
        // La data minima è l'inizio del range (permette di modificare da qualsiasi punto)
        const dataMinima = `${anno}-${mese.toString().padStart(2, '0')}-${giornoInizioRange.toString().padStart(2, '0')}`;
        editGiornoFine.setAttribute('min', dataMinima);
        
        const dataMassima = `${anno + 1}-12-31`;
        editGiornoFine.setAttribute('max', dataMassima);
        
        // Pre-compila con la data di fine del range esistente (se c'è un range > 1 giorno)
        if (codiceServizio && currentRange.count > 1 && currentRange.end >= giornoCorrente) {
            const dataFineRange = `${anno}-${mese.toString().padStart(2, '0')}-${currentRange.end.toString().padStart(2, '0')}`;
            editGiornoFine.value = dataFineRange;
        } else {
            editGiornoFine.value = '';
        }
        
        editGiornoFine.addEventListener('change', function() {
            updateRangePreview(giornoInizioRange, mese, anno, this.value);
        });
    }
    
    function updateRangePreview(giornoInizio, meseInizio, annoInizio, dataFine) {
        const existingPreview = document.getElementById('rangePreview');
        if (existingPreview) {
            existingPreview.remove();
        }
        
        if (!dataFine) return;
        
        const dateFine = new Date(dataFine + 'T00:00:00');
        const giornoFine = dateFine.getDate();
        const meseFine = dateFine.getMonth() + 1;
        const annoFine = dateFine.getFullYear();
        
        const dataInizio = new Date(annoInizio, meseInizio - 1, giornoInizio);
        const dataFineObj = new Date(annoFine, meseFine - 1, giornoFine);
        const diffTime = Math.abs(dataFineObj - dataInizio);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
        
        const preview = document.createElement('div');
        preview.id = 'rangePreview';
        preview.innerHTML = `
            <i class="fas fa-calendar-alt me-1"></i>
            ${giornoInizio}/${meseInizio}/${annoInizio} → ${giornoFine}/${meseFine}/${annoFine} 
            <span class="badge ms-2">${diffDays}</span>
        `;
        
        const datePicker = document.getElementById('editGiornoFine');
        datePicker.parentNode.insertBefore(preview, datePicker.nextSibling);
    }
    
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.addEventListener('shown.bs.modal', function() {
            const firstFocusable = modal.querySelector('select, input, button');
            if (firstFocusable) {
                firstFocusable.focus();
            }
        }, { once: true });
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
            
            // value dell'option è l'ID numerico (tipo_servizio_id)
            let tipoServizioId = tipoServizioEl && tipoServizioEl.value ? tipoServizioEl.value : '';
            
            const giornoFine = giornoFineEl ? giornoFineEl.value : '';
            
            if (!militareId || !giorno || !pianificazioneMensileId) {
                alert('Dati mancanti per il salvataggio');
                return;
            }
            
            // Se stiamo modificando un range esistente, usa l'inizio del range originale
            // altrimenti usa il giorno cliccato
            const giornoInizio = (window.originalRangeStart && window.originalCodiceServizio) 
                ? window.originalRangeStart 
                : giorno;
            let giornoFineNum, meseFine, annoFine;
            
            if (giornoFine) {
                const dateFine = new Date(giornoFine + 'T00:00:00');
                giornoFineNum = dateFine.getDate();
                meseFine = dateFine.getMonth() + 1;
                annoFine = dateFine.getFullYear();
            } else {
                giornoFineNum = giorno;
                meseFine = window.pageData.mese;
                annoFine = window.pageData.anno;
            }
            
            if (giornoFine && meseFine === window.pageData.mese && annoFine === window.pageData.anno && giornoFineNum < giornoInizio) {
                alert('Il giorno di fine deve essere maggiore o uguale al giorno di inizio');
                return;
            }
            
            const promises = [];
            const giorniDaSalvare = [];
            
            let currentGiorno = giornoInizio;
            let currentMese = window.pageData.mese;
            let currentAnno = window.pageData.anno;
            
            while (true) {
                giorniDaSalvare.push({
                    giorno: currentGiorno,
                    mese: currentMese,
                    anno: currentAnno
                });
                
                if (currentGiorno === giornoFineNum && currentMese === meseFine && currentAnno === annoFine) {
                    break;
                }
                
                const giorniNelMese = new Date(currentAnno, currentMese, 0).getDate();
                if (currentGiorno < giorniNelMese) {
                    currentGiorno++;
                } else {
                    currentGiorno = 1;
                    if (currentMese === 12) {
                        currentMese = 1;
                        currentAnno++;
                    } else {
                        currentMese++;
                    }
                }
                
                if (giorniDaSalvare.length > 365) {
                    alert('Range troppo ampio. Massimo 365 giorni.');
                    return;
                }
            }
            
            // Calcola i giorni da rimuovere se il range è stato ridotto
            const giorniDaRimuovere = [];
            if (window.originalRange && window.originalCodiceServizio) {
                const newEnd = giornoFineNum;
                const origEnd = window.originalRange.end;
                const origStart = window.originalRange.start;
                
                // Se il nuovo range è più corto o il servizio è cambiato
                // Rimuovi l'impegno dai giorni non più inclusi
                if (origEnd > newEnd && meseFine === window.pageData.mese && annoFine === window.pageData.anno) {
                    for (let g = newEnd + 1; g <= origEnd; g++) {
                        giorniDaRimuovere.push({
                            giorno: g,
                            mese: window.pageData.mese,
                            anno: window.pageData.anno
                        });
                    }
                }
            }
            
            // Funzione per completare il salvataggio
            const completeSave = () => {
                const modal = document.getElementById('editGiornoModal');
                if (modal && typeof bootstrap !== 'undefined') {
                    const bsModal = bootstrap.Modal.getInstance(modal);
                    if (bsModal) {
                        bsModal.hide();
                        
                        setTimeout(() => {
                            const originalCell = document.querySelector(`[data-militare-id="${militareId}"][data-giorno="${giorno}"]`);
                            if (originalCell) {
                                originalCell.focus();
                            }
                        }, 300);
                    }
                }
                
                if (giornoFineEl) {
                    giornoFineEl.value = '';
                }
                
                const rangePreview = document.getElementById('rangePreview');
                if (rangePreview) {
                    rangePreview.remove();
                }
                
                // Reset range originale
                window.originalRange = null;
                window.originalCodiceServizio = null;
                window.originalRangeStart = null;
            };
            
            if (giorniDaSalvare.length > 1) {
                saveDaysRange(militareId, giorniDaSalvare, tipoServizioId)
                    .then(result => {
                        if (result.success) {
                            result.pianificazioni.forEach((data, index) => {
                                if (index < giorniDaSalvare.length) {
                                    const dayData = giorniDaSalvare[index];
                                    
                                    if (dayData.mese === window.pageData.mese && dayData.anno === window.pageData.anno) {
                                        updateCellContent(militareId, dayData.giorno, data);
                                    }
                                }
                            });
                        }
                        
                        // Rimuovi i giorni non più inclusi nel range
                        if (giorniDaRimuovere.length > 0) {
                            return saveDaysRange(militareId, giorniDaRimuovere, null);
                        }
                        return Promise.resolve({ success: true });
                    })
                    .then(removeResult => {
                        if (removeResult && removeResult.success && giorniDaRimuovere.length > 0) {
                            giorniDaRimuovere.forEach(dayData => {
                                if (dayData.mese === window.pageData.mese && dayData.anno === window.pageData.anno) {
                                    updateCellContent(militareId, dayData.giorno, null);
                                }
                            });
                        }
                        completeSave();
                    })
                    .catch(error => {
                        alert('Errore nel salvataggio: ' + error.message);
                    });
            } else {
                saveSingleDay(militareId, giorno, pianificazioneMensileId, tipoServizioId)
                    .then(result => {
                        if (result.success) {
                            updateCellContent(militareId, giorno, result.pianificazione);
                        }
                        
                        // Rimuovi i giorni non più inclusi nel range
                        if (giorniDaRimuovere.length > 0) {
                            return saveDaysRange(militareId, giorniDaRimuovere, null);
                        }
                        return Promise.resolve({ success: true });
                    })
                    .then(removeResult => {
                        if (removeResult && removeResult.success && giorniDaRimuovere.length > 0) {
                            giorniDaRimuovere.forEach(dayData => {
                                if (dayData.mese === window.pageData.mese && dayData.anno === window.pageData.anno) {
                                    updateCellContent(militareId, dayData.giorno, null);
                                }
                            });
                        }
                        completeSave();
                    })
                    .catch(error => {
                        alert('Errore nel salvataggio: ' + error.message);
                    });
            }
        });
    }
}

function saveSingleDay(militareId, giorno, pianificazioneMensileId, tipoServizioId, mese = null, anno = null) {
    const updateUrl = window.location.origin + '/SUGECO/public/cpt/militare/' + militareId + '/update-giorno';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    const tipoServizioIdNumeric = tipoServizioId ? parseInt(tipoServizioId) : null;
    
    const requestData = {
        giorno: giorno,
        pianificazione_mensile_id: pianificazioneMensileId,
        tipo_servizio_id: tipoServizioIdNumeric
    };
    
    if (mese && anno) {
        requestData.mese = mese;
        requestData.anno = anno;
    }
    
    return fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Risposta non valida dal server. Riprova.');
            }
        });
    });
}

function saveDaysRange(militareId, giorniDaSalvare, tipoServizioId) {
    const updateUrl = window.location.origin + '/SUGECO/public/cpt/militare/' + militareId + '/update-giorni-range';
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
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(bodyData)
    })
    .then(response => {
        return response.text().then(text => {
            try {
                const data = JSON.parse(text);
                if (!response.ok && data.message) {
                    throw new Error(data.message);
                }
                if (!response.ok) {
                    throw new Error('Errore nella risposta del server: ' + response.status);
                }
                return data;
            } catch (e) {
                if (e instanceof SyntaxError) {
                    throw new Error('Risposta non valida dal server. Riprova.');
                }
                throw e;
            }
        });
    });
}

function updateCellContent(militareId, giorno, pianificazioneData) {
    const selector = '[data-militare-id="' + militareId + '"][data-giorno="' + giorno + '"]';
    const cell = document.querySelector(selector);
    
    if (!cell) return;
    
    // Rimuovi eventuali dataset per l'hover
    delete cell.dataset.originalBg;
    
    if (pianificazioneData && pianificazioneData.tipo_servizio && pianificazioneData.tipo_servizio.codice) {
        const codice = pianificazioneData.tipo_servizio.codice;
        const nomeServizio = pianificazioneData.tipo_servizio.nome || codice;
        
        // Usa il colore dal tipo servizio se disponibile
        let bgColor = pianificazioneData.tipo_servizio.colore_badge || '';
        let textColor = '';
        
        // Fallback ai colori hardcoded se non c'è colore nel DB
        if (!bgColor) {
            const codiciGialli = ['LS', 'LO', 'LM', 'P', 'TIR', 'TRAS', 'APS1', 'APS2', 'APS3', 'APS4', 
                                  'AL-ELIX', 'AL-MCM', 'AL-BLS', 'AL-CIED', 'AL-SM', 'AL-RM', 'AL-RSPP', 
                                  'AL-LEG', 'AL-SEA', 'AL-MI', 'AL-PO', 'AL-PI', 'AP-M', 'AP-A', 'AC-SW', 
                                  'AC', 'PEFO'];
            
            const codiciRossi = ['RMD', 'LC', 'IS', 'TO'];
            
            if (codiciGialli.includes(codice)) {
                bgColor = '#ffff00';
            } else if (codiciRossi.includes(codice)) {
                bgColor = '#ff0000';
            } else {
                bgColor = '#00b050';
            }
        }
        
        // Calcola il colore del testo in base alla luminosità
        textColor = isLightColor(bgColor) ? '#000000' : '#ffffff';
        
        // Aggiungi classe has-impegno
        cell.classList.add('has-impegno');
        
        cell.style.backgroundColor = bgColor;
        cell.style.color = textColor;
        cell.style.fontWeight = '600';
        cell.style.fontSize = '10px';
        
        cell.textContent = codice;
        
        // Aggiorna il tooltip con le info del servizio
        cell.setAttribute('title', codice + ' - ' + nomeServizio);
        
        // Aggiorna data-tipo-servizio-id
        if (pianificazioneData.tipo_servizio.id) {
            cell.setAttribute('data-tipo-servizio-id', pianificazioneData.tipo_servizio.id);
        }
        
    } else {
        // Rimuovi classe has-impegno
        cell.classList.remove('has-impegno');
        
        // Ripristina il colore appropriato per weekend/festivi/oggi
        const isWeekend = cell.classList.contains('weekend-column');
        const isHoliday = cell.classList.contains('holiday-column');
        const isToday = cell.classList.contains('today-column');
        
        if (isWeekend || isHoliday) {
            cell.style.backgroundColor = 'rgba(255, 0, 0, 0.12)';
        } else if (isToday) {
            cell.style.backgroundColor = 'rgba(255, 220, 0, 0.20)';
        } else {
            cell.style.backgroundColor = '';
        }
        
        cell.style.color = '';
        cell.textContent = '-';
        
        // Aggiorna il tooltip
        cell.setAttribute('title', 'Nessuna pianificazione - Clicca per aggiungere');
        
        // Rimuovi data-tipo-servizio-id
        cell.removeAttribute('data-tipo-servizio-id');
    }
}

// Funzione per determinare se un colore è chiaro
function isLightColor(hexColor) {
    if (!hexColor) return true;
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
    return brightness > 128;
}

function initTooltips() {
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
    
    if (typeof SUGECO !== 'undefined' && typeof SUGECO.Filters !== 'undefined') {
        SUGECO.Filters.init();
    }
});

function setupModalAccessibility() {
    const modal = document.getElementById('editGiornoModal');
    if (!modal) return;
    
    modal.addEventListener('hidden.bs.modal', function() {
        const focusableElements = modal.querySelectorAll('button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        focusableElements.forEach(element => {
            element.blur();
        });
        
        modal.removeAttribute('aria-hidden');
    });
    
    modal.addEventListener('shown.bs.modal', function() {
        modal.removeAttribute('aria-hidden');
    });
    
    modal.addEventListener('hide.bs.modal', function() {
        modal.setAttribute('aria-hidden', 'true');
    });
    
    const cancelBtn = modal.querySelector('[data-bs-dismiss="modal"]');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
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
