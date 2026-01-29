<!-- Modal Creazione Plotone -->
<div class="modal fade" id="createPlotoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Nuovo Plotone
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPlotoneForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="plotone_nome" class="form-label">
                            <i class="fas fa-users me-1"></i>Nome Plotone <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="plotone_nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="plotone_compagnia_id" class="form-label">
                            <i class="fas fa-flag me-1"></i>Compagnia <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="plotone_compagnia_id" required>
                            <option value="">Seleziona compagnia...</option>
                            @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}">{{ $compagnia->nome }}</option>
                            @endforeach
                        </select>
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

<!-- Modal Modifica Plotone -->
<div class="modal fade" id="editPlotoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #0A2342 0%, #1a3a5a 100%); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Modifica Plotone
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPlotoneForm">
                @csrf
                <input type="hidden" id="edit_plotone_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_plotone_nome" class="form-label">
                            <i class="fas fa-users me-1"></i>Nome Plotone <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="edit_plotone_nome" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_plotone_compagnia_id" class="form-label">
                            <i class="fas fa-flag me-1"></i>Compagnia <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_plotone_compagnia_id" required>
                            @foreach($compagnie as $compagnia)
                            <option value="{{ $compagnia->id }}">{{ $compagnia->nome }}</option>
                            @endforeach
                        </select>
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

<!-- Modal elimina plotone gestito da SUGECO.Confirm -->

