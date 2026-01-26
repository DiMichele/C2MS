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

    // Eliminazione
    document.querySelectorAll('.delete-plotone-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_plotone_id').value = this.dataset.id;
            document.getElementById('deletePlotoneNome').textContent = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('deletePlotoneModal')).show();
        });
    });

    const confirmDeletePlotone = document.getElementById('confirmDeletePlotone');
    if (confirmDeletePlotone) {
        confirmDeletePlotone.addEventListener('click', function() {
            const plotoneId = document.getElementById('delete_plotone_id').value;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
            
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
                    bootstrap.Modal.getInstance(document.getElementById('deletePlotoneModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    confirmDeletePlotone.disabled = false;
                    confirmDeletePlotone.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                confirmDeletePlotone.disabled = false;
                confirmDeletePlotone.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
            });
        });
    }

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

    // Eliminazione
    document.querySelectorAll('.delete-ufficio-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_ufficio_id').value = this.dataset.id;
            document.getElementById('deleteUfficioNome').textContent = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('deleteUfficioModal')).show();
        });
    });

    const confirmDeleteUfficio = document.getElementById('confirmDeleteUfficio');
    if (confirmDeleteUfficio) {
        confirmDeleteUfficio.addEventListener('click', function() {
            const ufficioId = document.getElementById('delete_ufficio_id').value;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
            
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
                    bootstrap.Modal.getInstance(document.getElementById('deleteUfficioModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    confirmDeleteUfficio.disabled = false;
                    confirmDeleteUfficio.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                confirmDeleteUfficio.disabled = false;
                confirmDeleteUfficio.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
            });
        });
    }

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

    // Eliminazione
    document.querySelectorAll('.delete-incarico-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_incarico_id').value = this.dataset.id;
            document.getElementById('deleteIncaricoNome').textContent = this.dataset.nome;
            new bootstrap.Modal(document.getElementById('deleteIncaricoModal')).show();
        });
    });

    const confirmDeleteIncarico = document.getElementById('confirmDeleteIncarico');
    if (confirmDeleteIncarico) {
        confirmDeleteIncarico.addEventListener('click', function() {
            const incaricoId = document.getElementById('delete_incarico_id').value;
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Eliminazione...';
            
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
                    bootstrap.Modal.getInstance(document.getElementById('deleteIncaricoModal')).hide();
                    location.reload();
                } else {
                    alert('Errore: ' + data.message);
                    confirmDeleteIncarico.disabled = false;
                    confirmDeleteIncarico.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
                }
            })
            .catch(error => {
                alert('Errore: ' + error.message);
                confirmDeleteIncarico.disabled = false;
                confirmDeleteIncarico.innerHTML = '<i class="fas fa-trash me-1"></i>Elimina';
            });
        });
    }

})();

