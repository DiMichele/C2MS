// Pianificazione CPT - JavaScript
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
    
    const editGiornoFine = document.getElementById('editGiornoFine');
    if (editGiornoFine && window.pageData) {
        const mese = window.pageData.mese;
        const anno = window.pageData.anno;
        
        const giornoSuccessivo = parseInt(giorno) + 1;
        const giorniNelMese = new Date(anno, mese, 0).getDate();
        
        if (giornoSuccessivo <= giorniNelMese) {
            const dataMinima = `${anno}-${mese.toString().padStart(2, '0')}-${giornoSuccessivo.toString().padStart(2, '0')}`;
            editGiornoFine.setAttribute('min', dataMinima);
        } else {
            const meseSuccessivo = mese === 12 ? 1 : mese + 1;
            const annoSuccessivo = mese === 12 ? anno + 1 : anno;
            const dataMinima = `${annoSuccessivo}-${meseSuccessivo.toString().padStart(2, '0')}-01`;
            editGiornoFine.setAttribute('min', dataMinima);
        }
        
        const dataMassima = `${anno + 1}-12-31`;
        editGiornoFine.setAttribute('max', dataMassima);
        
        editGiornoFine.value = '';
        editGiornoFine.setAttribute('placeholder', 'Seleziona data di fine (opzionale)');
        
        editGiornoFine.addEventListener('change', function() {
            updateRangePreview(giorno, mese, anno, this.value);
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
            
            let tipoServizioId = '';
            if (tipoServizioEl && tipoServizioEl.value) {
                const selectedOption = tipoServizioEl.options[tipoServizioEl.selectedIndex];
                tipoServizioId = selectedOption.getAttribute('data-id') || '';
            }
            
            const giornoFine = giornoFineEl ? giornoFineEl.value : '';
            
            if (!militareId || !giorno || !pianificazioneMensileId) {
                alert('Dati mancanti per il salvataggio');
                return;
            }
            
            const giornoInizio = giorno;
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
            
            if (giorniDaSalvare.length > 1) {
                saveDaysRange(militareId, giorniDaSalvare, tipoServizioId)
                    .then(result => {
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
                        
                        if (giornoFineEl) {
                            giornoFineEl.value = '';
                        }
                        
                        const rangePreview = document.getElementById('rangePreview');
                        if (rangePreview) {
                            rangePreview.remove();
                        }
                    })
                    .catch(error => {
                        alert('Errore nel salvataggio: ' + error.message);
                    });
            } else {
                saveSingleDay(militareId, giorno, pianificazioneMensileId, tipoServizioId)
                    .then(result => {
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
                        
                        if (result.success) {
                            updateCellContent(militareId, giorno, result.pianificazione);
                        }
                        
                        if (giornoFineEl) {
                            giornoFineEl.value = '';
                        }
                        
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
        },
        body: JSON.stringify(requestData)
    })
    .then(response => response.json());
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
        },
        body: JSON.stringify(bodyData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Errore nella risposta del server: ' + response.status);
        }
        return response.json();
    });
}

function updateCellContent(militareId, giorno, pianificazioneData) {
    const selector = '[data-militare-id="' + militareId + '"][data-giorno="' + giorno + '"]';
    const cell = document.querySelector(selector);
    
    if (!cell) return;
    
    if (pianificazioneData && pianificazioneData.tipo_servizio && pianificazioneData.tipo_servizio.codice) {
        const codice = pianificazioneData.tipo_servizio.codice;
        
        const codiciGialli = ['LS', 'LO', 'LM', 'P', 'TIR', 'TRAS', 'APS1', 'APS2', 'APS3', 'APS4', 
                              'AL-ELIX', 'AL-MCM', 'AL-BLS', 'AL-CIED', 'AL-SM', 'AL-RM', 'AL-RSPP', 
                              'AL-LEG', 'AL-SEA', 'AL-MI', 'AL-PO', 'AL-PI', 'AP-M', 'AP-A', 'AC-SW', 
                              'AC', 'PEFO'];
        
        const codiciRossi = ['RMD', 'LC', 'IS', 'TO'];
        
        let bgColor = '';
        let textColor = '';
        
        if (codiciGialli.includes(codice)) {
            bgColor = '#ffff00';
            textColor = '#000000';
        } else if (codiciRossi.includes(codice)) {
            bgColor = '#ff0000';
            textColor = '#ffffff';
        } else {
            bgColor = '#00b050';
            textColor = '#ffffff';
        }
        
        cell.style.backgroundColor = bgColor;
        cell.style.color = textColor;
        cell.style.fontWeight = '600';
        cell.style.fontSize = '10px';
        
        cell.textContent = codice;
        
    } else {
        cell.style.backgroundColor = '';
        cell.style.color = '';
        cell.textContent = '-';
    }
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
