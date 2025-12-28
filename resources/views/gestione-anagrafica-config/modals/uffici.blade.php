<!-- Modal Creazione Ufficio -->
<div class="modal fade" id="createUfficioModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Nuovo Ufficio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createUfficioForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ufficio_nome" class="form-label">
                            <i class="fas fa-building me-1"></i>Nome Ufficio <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="ufficio_nome" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Crea
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modifica Ufficio -->
<div class="modal fade" id="editUfficioModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Modifica Ufficio
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUfficioForm">
                @csrf
                <input type="hidden" id="edit_ufficio_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_ufficio_nome" class="form-label">
                            <i class="fas fa-building me-1"></i>Nome Ufficio <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_ufficio_nome" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annulla
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Elimina Ufficio -->
<div class="modal fade" id="deleteUfficioModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Conferma Eliminazione
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Sei sicuro di voler eliminare l'ufficio <strong id="deleteUfficioNome"></strong>?</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Attenzione:</strong> Puoi eliminare solo uffici senza militari associati.
                </div>
                <input type="hidden" id="delete_ufficio_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annulla
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteUfficio">
                    <i class="fas fa-trash me-1"></i>Elimina
                </button>
            </div>
        </div>
    </div>
</div>

