(function() {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!csrfToken) {
        console.error('CSRF token not found!');
        return;
    }
    
    // Helper per ottenere il base URL
    function getBaseUrl() {
        // Cerca di usare l'URL base dalla pagina
        const baseTag = document.querySelector('base');
        if (baseTag) return baseTag.href.replace(/\/$/, '');
        
        // Fallback: costruisci l'URL base dal pathname
        const path = window.location.pathname;
        const match = path.match(/^(\/[^\/]+\/public)/);
        return match ? match[1] : '';
    }
    
    const baseUrl = getBaseUrl();
    
    // Helper per salvare e ripristinare il tab attivo
    function saveActiveTab() {
        const activeTab = document.querySelector('#configTabs .nav-link.active');
        if (activeTab) {
            localStorage.setItem('activeAnagraficaTab', activeTab.id);
        }
    }
    
    function restoreActiveTab() {
        const savedTab = localStorage.getItem('activeAnagraficaTab');
        if (savedTab) {
            const tabButton = document.getElementById(savedTab);
            if (tabButton && typeof bootstrap !== 'undefined') {
                // Usa setTimeout per assicurarsi che Bootstrap sia pronto
                setTimeout(() => {
                    try {
                        const tab = new bootstrap.Tab(tabButton);
                        tab.show();
                    } catch (e) {
                        // Fallback: click manuale
                        tabButton.click();
                    }
                }, 100);
            }
        }
    }
    
    // Salva il tab quando cambia
    document.querySelectorAll('#configTabs .nav-link').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            localStorage.setItem('activeAnagraficaTab', this.id);
        });
    });
    
    // Ripristina il tab all'avvio
    document.addEventListener('DOMContentLoaded', restoreActiveTab);

    // ==================== PLOTONI ====================
    
    // Creazione
    const createPlotoneForm = document.getElementById('createPlotoneForm');
    if (createPlotoneForm) {
        createPlotoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
            
            saveActiveTab();

            fetch(baseUrl + '/gestione-anagrafica-config/plotoni', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('plotone_nome').value,
                    compagnia_id: document.getElementById('plotone_compagnia_id').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createPlotoneModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
            });
        });
    }

    // Modifica
    document.querySelectorAll('.edit-plotone-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_plotone_id').value = this.dataset.id;
            document.getElementById('edit_plotone_nome').value = this.dataset.nome;
            document.getElementById('edit_plotone_compagnia_id').value = this.dataset.compagniaId;
            new bootstrap.Modal(document.getElementById('editPlotoneModal')).show();
        });
    });

    const editPlotoneForm = document.getElementById('editPlotoneForm');
    if (editPlotoneForm) {
        editPlotoneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const plotoneId = document.getElementById('edit_plotone_id').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/plotoni/${plotoneId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('edit_plotone_nome').value,
                    compagnia_id: document.getElementById('edit_plotone_compagnia_id').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editPlotoneModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
            });
        });
    }

    // Eliminazione - usa sistema conferma unificato SUGECO.Confirm
    document.querySelectorAll('.delete-plotone-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const plotoneId = this.dataset.id;
            const nome = this.dataset.nome;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare il plotone "${nome}"? Puoi eliminare solo plotoni senza militari associati.`);
            if (!confirmed) return;
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/plotoni/${plotoneId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showError('Errore: ' + data.message);
                }
            })
            .catch(error => {
                showError('Errore: ' + error.message);
            });
        });
    });

    // ==================== UFFICI ====================
    
    // Creazione
    const createUfficioForm = document.getElementById('createUfficioForm');
    if (createUfficioForm) {
        createUfficioForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
            
            saveActiveTab();

            fetch(baseUrl + '/gestione-anagrafica-config/uffici', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('ufficio_nome').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createUfficioModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
            });
        });
    }

    // Modifica
    document.querySelectorAll('.edit-ufficio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_ufficio_id').value = this.dataset.id;
            document.getElementById('edit_ufficio_nome').value = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('editUfficioModal')).show();
        });
    });

    const editUfficioForm = document.getElementById('editUfficioForm');
    if (editUfficioForm) {
        editUfficioForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const ufficioId = document.getElementById('edit_ufficio_id').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/uffici/${ufficioId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('edit_ufficio_nome').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editUfficioModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
            });
        });
    }

    // Eliminazione - usa sistema conferma unificato SUGECO.Confirm
    document.querySelectorAll('.delete-ufficio-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const ufficioId = this.dataset.id;
            const nome = this.dataset.nome;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare l'ufficio "${nome}"? Puoi eliminare solo uffici senza militari associati.`);
            if (!confirmed) return;
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/uffici/${ufficioId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showError('Errore: ' + data.message);
                }
            })
            .catch(error => {
                showError('Errore: ' + error.message);
            });
        });
    });

    // ==================== INCARICHI ====================
    
    // Creazione
    const createIncaricoForm = document.getElementById('createIncaricoForm');
    if (createIncaricoForm) {
        createIncaricoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Creazione...';
            
            saveActiveTab();

            fetch(baseUrl + '/gestione-anagrafica-config/incarichi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('incarico_nome').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('createIncaricoModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Crea';
            });
        });
    }

    // Modifica
    document.querySelectorAll('.edit-incarico-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_incarico_id').value = this.dataset.id;
            document.getElementById('edit_incarico_nome').value = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('editIncaricoModal')).show();
        });
    });

    const editIncaricoForm = document.getElementById('editIncaricoForm');
    if (editIncaricoForm) {
        editIncaricoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const incaricoId = document.getElementById('edit_incarico_id').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvataggio...';
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/incarichi/${incaricoId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    nome: document.getElementById('edit_incarico_nome').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editIncaricoModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salva';
            });
        });
    }

    // Eliminazione - usa sistema conferma unificato SUGECO.Confirm
    document.querySelectorAll('.delete-incarico-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const incaricoId = this.dataset.id;
            const nome = this.dataset.nome;
            
            const confirmed = await SUGECO.Confirm.delete(`Eliminare l'incarico "${nome}"? Puoi eliminare solo incarichi senza militari associati.`);
            if (!confirmed) return;
            
            saveActiveTab();

            fetch(`${baseUrl}/gestione-anagrafica-config/incarichi/${incaricoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showError('Errore: ' + data.message);
                }
            })
            .catch(error => {
                showError('Errore: ' + error.message);
            });
        });
    });

})();

