/**
 * PEFO - JavaScript per gestione pagina PEFO
 */

// Variabili globali
let currentMilitareId = null;
let currentPrenotazioneId = null;
let militariSelezionati = [];

// URL base per le API
const BASE_URL = window.location.origin + '/SUGECO/public';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content;

// ==========================================
// TOGGLE SEZIONI
// ==========================================

function showSection(section) {
    const tabellaContent = document.getElementById('sezioneTabellaContent');
    const prenotazioniContent = document.getElementById('sezionePrenotazioniContent');
    const btnTabella = document.getElementById('btnTabellaMilitari');
    const btnPrenotazioni = document.getElementById('btnPrenotazioni');

    if (section === 'tabella') {
        tabellaContent.style.display = 'block';
        prenotazioniContent.style.display = 'none';
        btnTabella.classList.add('active');
        btnPrenotazioni.classList.remove('active');
    } else {
        tabellaContent.style.display = 'none';
        prenotazioniContent.style.display = 'block';
        btnTabella.classList.remove('active');
        btnPrenotazioni.classList.add('active');
        loadPrenotazioni();
    }
}

// ==========================================
// TABELLA MILITARI
// ==========================================

async function loadMilitari() {
    const tbody = document.getElementById('tabellaMilitariBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Caricamento...</td></tr>';

    const params = new URLSearchParams();
    
    const search = document.getElementById('searchMilitare')?.value;
    if (search) params.append('search', search);
    
    const compagnia = document.getElementById('filterCompagnia')?.value;
    if (compagnia) params.append('compagnia_id', compagnia);
    
    const grado = document.getElementById('filterGrado')?.value;
    if (grado) params.append('grado_id', grado);
    
    const unita = document.getElementById('filterUnita')?.value;
    if (unita) params.append('unit_id', unita);
    
    const statoAgility = document.getElementById('filterStatoAgility')?.value;
    if (statoAgility) params.append('stato_agility', statoAgility);
    
    const statoResistenza = document.getElementById('filterStatoResistenza')?.value;
    if (statoResistenza) params.append('stato_resistenza', statoResistenza);

    try {
        const response = await fetch(BASE_URL + '/pefo/militari?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        const data = await response.json();

        if (data.success) {
            renderMilitariTable(data.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Errore nel caricamento</td></tr>';
        }
    } catch (error) {
        console.error('Errore:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Errore di connessione</td></tr>';
    }
}

function renderMilitariTable(militari) {
    const tbody = document.getElementById('tabellaMilitariBody');
    
    if (!militari || militari.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-search me-2"></i>Nessun militare trovato</td></tr>';
        return;
    }

    let html = '';
    militari.forEach(function(m) {
        const cursorStyle = window.canEdit ? 'cursor: pointer;' : '';
        const nomeCompleto = m.grado + ' ' + m.cognome + ' ' + m.nome;
        
        // Agility
        const statoAgilityCSS = 'stato-pefo-' + m.stato_agility;
        const dataAgilityText = m.data_agility || 'Non presente';
        const dataAgilityRaw = m.data_agility_raw || '';
        
        // Resistenza
        const statoResistenzaCSS = 'stato-pefo-' + m.stato_resistenza;
        const dataResistenzaText = m.data_resistenza || 'Non presente';
        const dataResistenzaRaw = m.data_resistenza_raw || '';
        
        html += '<tr data-militare-id="' + m.id + '">';
        html += '<td>' + (m.unita || '-') + '</td>';
        html += '<td><strong>' + (m.compagnia || '-') + '</strong></td>';
        html += '<td>' + m.grado + '</td>';
        html += '<td><a href="' + BASE_URL + '/anagrafica/' + m.id + '" class="link-name">' + m.cognome + '</a></td>';
        html += '<td>' + m.nome + '</td>';
        
        // Colonna Agility
        if (window.canEdit) {
            html += '<td class="stato-pefo-cell ' + statoAgilityCSS + '" style="' + cursorStyle + '" onclick="openModificaPefoModal(' + m.id + ', \'' + nomeCompleto.replace(/'/g, "\\'") + '\', \'' + dataAgilityRaw + '\', \'agility\')">' + dataAgilityText + '</td>';
        } else {
            html += '<td class="stato-pefo-cell ' + statoAgilityCSS + '">' + dataAgilityText + '</td>';
        }
        
        // Colonna Resistenza
        if (window.canEdit) {
            html += '<td class="stato-pefo-cell ' + statoResistenzaCSS + '" style="' + cursorStyle + '" onclick="openModificaPefoModal(' + m.id + ', \'' + nomeCompleto.replace(/'/g, "\\'") + '\', \'' + dataResistenzaRaw + '\', \'resistenza\')">' + dataResistenzaText + '</td>';
        } else {
            html += '<td class="stato-pefo-cell ' + statoResistenzaCSS + '">' + dataResistenzaText + '</td>';
        }
        
        html += '</tr>';
    });

    tbody.innerHTML = html;
}

// Event listeners per filtri
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filtri
    var toggleFilters = document.getElementById('toggleFilters');
    if (toggleFilters) {
        toggleFilters.addEventListener('click', function() {
            var filtersContainer = document.getElementById('filtersContainer');
            var toggleText = document.getElementById('toggleFiltersText');
            
            if (filtersContainer.style.display === 'none') {
                filtersContainer.style.display = 'block';
                toggleText.textContent = 'Nascondi filtri';
            } else {
                filtersContainer.style.display = 'none';
                toggleText.textContent = 'Mostra filtri';
            }
        });
    }

    // Ricerca con debounce
    var searchTimeout;
    var searchInput = document.getElementById('searchMilitare');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadMilitari, 300);
        });
    }

    // Filtri
    ['filterCompagnia', 'filterGrado', 'filterUnita', 'filterStatoAgility', 'filterStatoResistenza'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', loadMilitari);
        }
    });

    // Export Excel
    var exportBtn = document.getElementById('exportExcel');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportExcel);
    }
    
    // Select all checkbox
    var selectAll = document.getElementById('selectAllMilitari');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var rows = document.querySelectorAll('#tabellaMilitariDisponibiliBody tr');
            var isChecked = this.checked;
            militariSelezionati = [];
            
            rows.forEach(function(row) {
                var checkbox = row.querySelector('input[type="checkbox"]');
                var idMatch = row.getAttribute('onclick');
                if (checkbox && idMatch) {
                    var match = idMatch.match(/toggleMilitareSelection\((\d+)/);
                    if (match) {
                        var militareId = parseInt(match[1]);
                        checkbox.checked = isChecked;
                        if (isChecked) {
                            row.classList.add('selected');
                            if (!militariSelezionati.includes(militareId)) {
                                militariSelezionati.push(militareId);
                            }
                        } else {
                            row.classList.remove('selected');
                        }
                    }
                }
            });
            document.getElementById('countSelezionati').textContent = militariSelezionati.length;
        });
    }
    
    // Ricerca militari disponibili
    var searchDisponibili = document.getElementById('searchMilitariDisponibili');
    if (searchDisponibili) {
        var searchTimeoutDisp;
        searchDisponibili.addEventListener('input', function() {
            clearTimeout(searchTimeoutDisp);
            searchTimeoutDisp = setTimeout(loadMilitariDisponibili, 300);
        });
    }
    
    // Filtro compagnia modal
    var filterModal = document.getElementById('filterCompagniaModal');
    if (filterModal) {
        filterModal.addEventListener('change', loadMilitariDisponibili);
    }
});

// ==========================================
// MODAL MODIFICA DATA PEFO
// ==========================================

function openModificaPefoModal(militareId, militareNome, dataAttualeRaw, tipo) {
    if (!window.canEdit) return;
    
    currentMilitareId = militareId;
    
    // Aggiorna il titolo e label in base al tipo
    const tipoLabel = tipo === 'agility' ? 'Agility' : 'Resistenza';
    document.getElementById('modalTitoloPefo').textContent = 'Modifica Data ' + tipoLabel;
    document.getElementById('modalLabelData').textContent = 'Data ' + tipoLabel + ':';
    
    document.getElementById('modalMilitareInfo').textContent = militareNome;
    document.getElementById('modalMilitareId').value = militareId;
    document.getElementById('modalTipoPefo').value = tipo;
    document.getElementById('modalDataPefo').value = dataAttualeRaw || '';
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('modalModificaPefo').classList.add('show');
}

async function saveDataPefo() {
    const militareId = document.getElementById('modalMilitareId').value;
    const tipo = document.getElementById('modalTipoPefo').value;
    const nuovaData = document.getElementById('modalDataPefo').value;
    
    if (!militareId || !tipo) return;
    
    try {
        var response = await fetch(BASE_URL + '/pefo/militari/' + militareId + '/update-data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                tipo: tipo,
                data: nuovaData || null
            })
        });

        var data = await response.json();

        if (data.success) {
            showToast(data.message || 'Data aggiornata con successo', 'success');
            closeAllModals();
            
            // Aggiorna solo la riga del militare modificato invece di ricaricare tutta la tabella
            updateMilitareRow(militareId, tipo, data.data);
        } else {
            showToast(data.message || 'Errore durante il salvataggio', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Aggiorna la riga di un militare dopo la modifica di una data
 */
function updateMilitareRow(militareId, tipo, dataAggiornata) {
    const row = document.querySelector('tr[data-militare-id="' + militareId + '"]');
    if (!row) {
        // Se la riga non è visibile (per filtri), ricarica tutta la tabella
        loadMilitari();
        return;
    }
    
    // Determina quale colonna aggiornare (6 = Agility, 7 = Resistenza)
    const colIndex = tipo === 'agility' ? 5 : 6; // 0-based, quindi 5 e 6
    const cell = row.cells[colIndex];
    
    if (!cell) {
        loadMilitari();
        return;
    }
    
    // Calcola il nuovo stato
    const dataFormatted = dataAggiornata.data || 'Non presente';
    const dataRaw = dataAggiornata.data_raw;
    
    let statoClass = 'stato-pefo-mancante';
    if (dataRaw) {
        const annoData = parseInt(dataRaw.split('-')[0]);
        const annoCorrente = new Date().getFullYear();
        statoClass = (annoData >= annoCorrente) ? 'stato-pefo-valido' : 'stato-pefo-scaduto';
    }
    
    // Aggiorna la cella
    cell.className = 'stato-pefo-cell ' + statoClass;
    cell.textContent = dataFormatted;
    
    // Ri-applica il click handler se l'utente può modificare
    if (window.canEdit) {
        cell.style.cursor = 'pointer';
        // Recupera il nome completo dalla riga
        const gradoCell = row.cells[2];
        const cognomeCell = row.cells[3];
        const nomeCell = row.cells[4];
        const nomeCompleto = gradoCell.textContent + ' ' + cognomeCell.textContent + ' ' + nomeCell.textContent;
        
        cell.onclick = function() {
            openModificaPefoModal(militareId, nomeCompleto.replace(/'/g, "\\'"), dataRaw || '', tipo);
        };
    }
}

// ==========================================
// PRENOTAZIONI
// ==========================================

async function loadPrenotazioni() {
    var container = document.getElementById('listaPrenotazioni');
    container.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i>Caricamento...</div>';

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni', {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var data = await response.json();

        if (data.success) {
            renderPrenotazioni(data.data);
            document.getElementById('countPrenotazioni').textContent = data.count + ' prenotazioni';
        } else {
            container.innerHTML = '<div class="alert alert-danger">Errore nel caricamento</div>';
        }
    } catch (error) {
        console.error('Errore:', error);
        container.innerHTML = '<div class="alert alert-danger">Errore di connessione</div>';
    }
}

function renderPrenotazioni(prenotazioni) {
    var container = document.getElementById('listaPrenotazioni');
    
    if (!prenotazioni || prenotazioni.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-times"></i><h4>Nessuna prenotazione trovata</h4><p>Crea una nuova prenotazione per iniziare</p></div>';
        return;
    }

    var html = '';
    prenotazioni.forEach(function(p) {
        html += '<div class="prenotazione-card collapsed" data-prenotazione-id="' + p.id + '">';
        
        // Header cliccabile per espandere/collassare
        html += '<div class="prenotazione-header" onclick="togglePrenotazione(' + p.id + ')">';
        html += '<div class="prenotazione-header-left">';
        html += '<i class="fas fa-chevron-right expand-icon" id="expand-icon-' + p.id + '"></i>';
        html += '<h4>' + p.nome + '</h4>';
        // Badge riepilogo
        html += '<span class="badge-militari">' + p.numero_militari + ' militari';
        if (p.numero_militari > 0 && p.numero_militari_da_confermare > 0) {
            html += ' <span class="text-warning">(' + p.numero_militari_da_confermare + ' da confermare)</span>';
        }
        html += '</span>';
        html += '</div>';
        html += '<div class="prenotazione-header-right">';
        // Pulsante export per questa prenotazione
        if (p.numero_militari > 0) {
            html += '<button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); exportPrenotazioneExcel(' + p.id + ')" title="Esporta Excel"><i class="fas fa-file-excel"></i></button>';
        }
        html += '</div>';
        html += '</div>';
        
        // Body collassabile
        html += '<div class="prenotazione-body-collapsible" id="prenotazione-body-' + p.id + '">';
        
        // Info prenotazione
        html += '<div class="prenotazione-info">';
        html += '<div class="prenotazione-info-item"><i class="fas fa-calendar"></i><span>' + p.data_completa + '</span></div>';
        
        // Riepilogo militari con stato conferma
        var militariInfo = p.numero_militari + ' militari';
        if (p.numero_militari > 0) {
            militariInfo += ' (' + p.numero_militari_confermati + ' confermati';
            if (p.numero_militari_da_confermare > 0) {
                militariInfo += ', ' + p.numero_militari_da_confermare + ' da confermare';
            }
            militariInfo += ')';
        }
        html += '<div class="prenotazione-info-item"><i class="fas fa-users"></i><span>' + militariInfo + '</span></div>';
        html += '</div>';
        
        // Tabella militari
        if (p.militari.length > 0) {
            html += '<div class="militari-list"><table class="table table-sm mb-0"><thead><tr>';
            html += '<th>Grado</th><th>Cognome</th><th>Nome</th><th>Data Nascita</th><th>Età</th><th>Stato</th><th></th>';
            html += '</tr></thead><tbody>';
            
            p.militari.forEach(function(m) {
                var rowClass = m.confermato ? 'militare-confermato' : 'militare-da-confermare';
                html += '<tr class="' + rowClass + '">';
                html += '<td>' + m.grado + '</td>';
                html += '<td>' + m.cognome + '</td>';
                html += '<td>' + m.nome + '</td>';
                html += '<td>' + m.data_nascita + '</td>';
                html += '<td>' + (m.eta || '-') + '</td>';
                
                // Badge stato
                if (m.confermato) {
                    html += '<td><span class="badge-confermato">Confermato</span></td>';
                } else {
                    html += '<td><span class="badge-da-confermare">Da confermare</span></td>';
                }
                
                // Azioni
                html += '<td>';
                if (!m.confermato) {
                    html += '<button class="btn-conferma-militare" onclick="confermaMilitare(' + p.id + ', ' + m.id + ')" title="Conferma"><i class="fas fa-check"></i></button> ';
                }
                if (p.stato === 'attivo') {
                    html += '<button class="btn-rimuovi-militare" onclick="rimuoviMilitare(' + p.id + ', ' + m.id + ')" title="Rimuovi"><i class="fas fa-times"></i></button>';
                }
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        } else {
            html += '<p class="text-muted"><em>Nessun militare assegnato</em></p>';
        }
        
        // Footer con azioni
        html += '<div class="prenotazione-footer"><div>';
        
        if (p.stato === 'attivo' && !p.is_passata) {
            html += '<button class="btn btn-sm btn-primary" onclick="openAggiungiMilitariModal(' + p.id + ', \'' + p.nome.replace(/'/g, "\\'") + '\', \'' + p.data_prenotazione + '\')"><i class="fas fa-user-plus me-1"></i>Aggiungi Militari</button>';
        }
        
        // Pulsante conferma tutti (se ci sono militari da confermare)
        if (p.numero_militari_da_confermare > 0 && window.canGestisciPrenotazioni) {
            html += '<button class="btn-conferma-tutti" onclick="confermaAllMilitari(' + p.id + ')"><i class="fas fa-check-double me-1"></i>Conferma tutti</button>';
        }
        
        html += '</div><div>';
        
        if (window.canGestisciPrenotazioni && p.stato === 'attivo') {
            html += '<button class="btn btn-sm btn-danger" onclick="eliminaPrenotazione(' + p.id + ')"><i class="fas fa-trash me-1"></i>Elimina</button>';
        }
        
        html += '</div></div>';
        
        html += '</div>'; // Close prenotazione-body-collapsible
        html += '</div>'; // Close prenotazione-card
    });

    container.innerHTML = html;
}

/**
 * Toggle espansione/collasso di una prenotazione
 */
function togglePrenotazione(prenotazioneId) {
    var card = document.querySelector('.prenotazione-card[data-prenotazione-id="' + prenotazioneId + '"]');
    var body = document.getElementById('prenotazione-body-' + prenotazioneId);
    var icon = document.getElementById('expand-icon-' + prenotazioneId);
    
    if (card.classList.contains('collapsed')) {
        card.classList.remove('collapsed');
        card.classList.add('expanded');
        body.style.maxHeight = body.scrollHeight + 'px';
        icon.classList.remove('fa-chevron-right');
        icon.classList.add('fa-chevron-down');
    } else {
        card.classList.remove('expanded');
        card.classList.add('collapsed');
        body.style.maxHeight = '0';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-right');
    }
}

/**
 * Export Excel per una singola prenotazione
 */
function exportPrenotazioneExcel(prenotazioneId) {
    window.location.href = BASE_URL + '/pefo/prenotazioni/' + prenotazioneId + '/export-excel';
}

// ==========================================
// MODAL CREA PRENOTAZIONE
// ==========================================

function openCreaPrenotazioneModal() {
    document.getElementById('inputTipoProva').value = '';
    document.getElementById('inputDataPrenotazione').value = '';
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('modalCreaPrenotazione').classList.add('show');
}

async function creaPrenotazione() {
    var tipoProva = document.getElementById('inputTipoProva').value;
    var data = document.getElementById('inputDataPrenotazione').value;

    if (!tipoProva || !data) {
        showToast('Seleziona tipo prova e data della prenotazione', 'warning');
        return;
    }

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                tipo_prova: tipoProva,
                data_prenotazione: data
            })
        });

        var result = await response.json();

        if (result.success) {
            showToast('Prenotazione creata con successo', 'success');
            closeAllModals();
            loadPrenotazioni();
        } else {
            showToast(result.message || 'Errore nella creazione', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

// ==========================================
// MODAL AGGIUNGI MILITARI
// ==========================================

function openAggiungiMilitariModal(prenotazioneId, nomePrenotazione, dataPrenotazione) {
    currentPrenotazioneId = prenotazioneId;
    militariSelezionati = [];
    
    document.getElementById('modalPrenotazioneInfo').innerHTML = '<strong>' + nomePrenotazione + '</strong> - ' + formatDate(dataPrenotazione);
    document.getElementById('searchMilitariDisponibili').value = '';
    document.getElementById('filterCompagniaModal').value = '';
    document.getElementById('countSelezionati').textContent = '0';
    
    loadMilitariDisponibili();
    
    document.getElementById('modalOverlay').classList.add('show');
    document.getElementById('modalAggiungiMilitari').classList.add('show');
}

async function loadMilitariDisponibili() {
    var tbody = document.getElementById('tabellaMilitariDisponibiliBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center">Caricamento...</td></tr>';

    var params = new URLSearchParams();
    
    var search = document.getElementById('searchMilitariDisponibili')?.value;
    if (search) params.append('search', search);
    
    var compagnia = document.getElementById('filterCompagniaModal')?.value;
    if (compagnia) params.append('compagnia_id', compagnia);

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + currentPrenotazioneId + '/militari-disponibili?' + params.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var data = await response.json();

        if (data.success) {
            renderMilitariDisponibili(data.data);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Errore</td></tr>';
        }
    } catch (error) {
        console.error('Errore:', error);
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Errore di connessione</td></tr>';
    }
}

function renderMilitariDisponibili(militari) {
    var tbody = document.getElementById('tabellaMilitariDisponibiliBody');
    
    if (!militari || militari.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">Nessun militare disponibile</td></tr>';
        return;
    }

    var html = '';
    militari.forEach(function(m) {
        var isSelected = militariSelezionati.includes(m.id);
        html += '<tr class="' + (isSelected ? 'selected' : '') + '" onclick="toggleMilitareSelection(' + m.id + ', this)">';
        html += '<td><input type="checkbox" ' + (isSelected ? 'checked' : '') + ' onclick="event.stopPropagation(); toggleMilitareSelection(' + m.id + ', this.closest(\'tr\'))"></td>';
        html += '<td>' + m.grado + '</td>';
        html += '<td>' + m.cognome + '</td>';
        html += '<td>' + m.nome + '</td>';
        html += '<td>' + m.data_nascita + '</td>';
        html += '<td>' + (m.eta || '-') + '</td>';
        html += '<td>' + m.compagnia + '</td>';
        html += '</tr>';
    });

    tbody.innerHTML = html;
    
    var selectAllCheckbox = document.getElementById('selectAllMilitari');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
}

function toggleMilitareSelection(militareId, row) {
    var index = militariSelezionati.indexOf(militareId);
    var checkbox = row.querySelector('input[type="checkbox"]');
    
    if (index > -1) {
        militariSelezionati.splice(index, 1);
        row.classList.remove('selected');
        if (checkbox) checkbox.checked = false;
    } else {
        militariSelezionati.push(militareId);
        row.classList.add('selected');
        if (checkbox) checkbox.checked = true;
    }
    document.getElementById('countSelezionati').textContent = militariSelezionati.length;
}

async function aggiungiMilitariSelezionati() {
    if (militariSelezionati.length === 0) {
        showToast('Seleziona almeno un militare', 'warning');
        return;
    }

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + currentPrenotazioneId + '/militari', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            body: JSON.stringify({
                militari_ids: militariSelezionati
            })
        });

        var result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            closeAllModals();
            loadPrenotazioni();
        } else {
            showToast(result.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

// ==========================================
// AZIONI PRENOTAZIONI
// ==========================================

async function rimuoviMilitare(prenotazioneId, militareId) {
    if (!confirm('Rimuovere questo militare dalla prenotazione?')) return;

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + prenotazioneId + '/militari/' + militareId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var result = await response.json();

        if (result.success) {
            showToast('Militare rimosso', 'success');
            loadPrenotazioni();
        } else {
            showToast(result.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Conferma un singolo militare in una prenotazione
 */
async function confermaMilitare(prenotazioneId, militareId) {
    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + prenotazioneId + '/militari/' + militareId + '/conferma', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var result = await response.json();

        if (result.success) {
            showToast('Militare confermato!', 'success');
            loadPrenotazioni(); // Ricarica per aggiornare la vista
        } else {
            showToast(result.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

/**
 * Conferma tutti i militari in una prenotazione
 */
async function confermaAllMilitari(prenotazioneId) {
    if (!confirm('Confermare tutti i militari? Le date PEFO verranno aggiornate.')) return;

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + prenotazioneId + '/conferma-tutti', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var result = await response.json();

        if (result.success) {
            showToast(result.message || 'Tutti i militari confermati!', 'success');
            loadPrenotazioni();
        } else {
            showToast(result.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

async function eliminaPrenotazione(prenotazioneId) {
    if (!confirm('Eliminare questa prenotazione?')) return;

    try {
        var response = await fetch(BASE_URL + '/pefo/prenotazioni/' + prenotazioneId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            }
        });

        var result = await response.json();

        if (result.success) {
            showToast('Prenotazione eliminata', 'success');
            loadPrenotazioni();
        } else {
            showToast(result.message || 'Errore', 'error');
        }
    } catch (error) {
        console.error('Errore:', error);
        showToast('Errore di connessione', 'error');
    }
}

// ==========================================
// EXPORT EXCEL
// ==========================================

function exportExcel() {
    var tabellaVisible = document.getElementById('sezioneTabellaContent').style.display !== 'none';
    
    if (tabellaVisible) {
        var visibleRows = document.querySelectorAll('#tabellaMilitari tbody tr:not([style*="display: none"])');
        var militareIds = [];
        visibleRows.forEach(function(row) {
            var id = row.getAttribute('data-militare-id');
            if (id) militareIds.push(id);
        });
        
        var url = BASE_URL + '/pefo/militari/export-excel';
        if (militareIds.length > 0) {
            url += '?ids=' + militareIds.join(',');
        }
        window.location.href = url;
    } else {
        window.location.href = BASE_URL + '/pefo/prenotazioni/export-excel';
    }
}

// ==========================================
// UTILITA
// ==========================================

function closeAllModals() {
    document.getElementById('modalOverlay').classList.remove('show');
    var modals = document.querySelectorAll('.pefo-modal');
    modals.forEach(function(modal) {
        modal.classList.remove('show');
    });
    currentMilitareId = null;
    currentPrenotazioneId = null;
    militariSelezionati = [];
}

function formatDate(dateString) {
    if (!dateString) return '';
    var date = new Date(dateString);
    return date.toLocaleDateString('it-IT', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function showToast(message, type) {
    type = type || 'info';
    
    if (window.SUGECO && window.SUGECO.Toast) {
        window.SUGECO.Toast.show(message, type);
    } else if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        var toast = document.createElement('div');
        var alertClass = type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info';
        var icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle';
        
        toast.className = 'alert alert-' + alertClass + ' position-fixed';
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 250px;';
        toast.innerHTML = '<i class="fas fa-' + icon + ' me-2"></i>' + message;
        document.body.appendChild(toast);
        
        setTimeout(function() {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }
}

// Chiudi modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllModals();
    }
});
