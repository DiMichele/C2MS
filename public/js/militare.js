/**
 * SUGECO Militare - Military personnel module
 */

// Ensure SUGECO namespace exists
window.SUGECO = window.SUGECO || {};

// Militare module
window.SUGECO.Militare = {
    /**
     * Initialize militare-specific functionality
     */
    init: function() {
        window.SUGECO.Core.log('Militare module initialized');
        this.initDeleteModal();
    },

    /**
     * Initialize delete confirmation modal
     */
    initDeleteModal: function() {
        const deleteModal = document.getElementById('deleteModal');
        const deleteButtons = document.querySelectorAll('[data-bs-toggle="modal"][data-bs-target="#deleteModal"]');
        
        if (!deleteModal || deleteButtons.length === 0) {
            return;
        }
        
        window.SUGECO.Core.log('Delete modal initialized');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const militareId = button.getAttribute('data-militare-id');
                const militareNome = button.getAttribute('data-militare-nome');
                
                if (militareId && militareNome) {
                    this.setupDeleteModal(militareId, militareNome);
                }
            });
        });
    },

    /**
     * Setup delete modal with militare information
     * @param {string} militareId - Militare ID
     * @param {string} militareNome - Militare name
     */
    setupDeleteModal: function(militareId, militareNome) {
        const deleteModal = document.getElementById('deleteModal');
        const confirmButton = deleteModal.querySelector('#confirmDelete');
        const militareNameSpan = deleteModal.querySelector('#militareToDelete');
        
        if (militareNameSpan) {
            militareNameSpan.textContent = militareNome;
        }
        
        if (confirmButton) {
            // Remove existing event listeners
            const newConfirmButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);
            
            // Add new event listener
            newConfirmButton.addEventListener('click', () => {
                this.deleteMilitare(militareId);
            });
        }
    },

    /**
     * Delete militare via AJAX
     * @param {string} militareId - Militare ID to delete
     */
    deleteMilitare: function(militareId) {
        const csrfToken = window.SUGECO.Core.getCsrfToken();
        
        if (!csrfToken) {
            if (typeof window.showToast === 'function') {
                window.showToast('Errore: token CSRF non trovato', 'error');
            }
            return;
        }
        
        // Show loading state
        const confirmButton = document.getElementById('confirmDelete');
        if (confirmButton) {
            confirmButton.disabled = true;
            confirmButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminazione...';
        }
        
        fetch(window.SUGECO.Core.buildUrl(`/militare/${militareId}`), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                if (typeof window.showToast === 'function') {
                    window.showToast('Militare eliminato con successo', 'success');
                }
                
                // Close modal
                const deleteModal = document.getElementById('deleteModal');
                if (deleteModal && window.bootstrap) {
                    const modal = bootstrap.Modal.getInstance(deleteModal);
                    if (modal) {
                        modal.hide();
                    }
                }
                
                // Redirect or refresh page
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } else {
                throw new Error(data.message || 'Errore durante l\'eliminazione');
            }
        })
        .catch(error => {
            window.SUGECO.Core.log('Delete error:', 'error');
            if (window.SUGECO.Core.config.debug) {
                window.SUGECO.Core.log(error.message, 'error');
            }
            
            // Show error message
            if (typeof window.showToast === 'function') {
                window.showToast('Errore durante l\'eliminazione del militare', 'error');
            }
            
            // Reset button state
            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.innerHTML = 'Elimina';
            }
        });
    }
};

// Initialize module when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.SUGECO.Militare.init();
});
