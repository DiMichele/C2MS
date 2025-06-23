<!-- Form per i certificati -->
<div class="certificati-form">
    <div class="card certificato-card">
        <div class="card-header">
            <h5><i class="fas fa-file-medical me-2"></i> Aggiungi un certificato</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('certificati.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="militare_id" value="{{ $militare->id }}">
                
                <div class="mb-3">
                    <label for="tipo_certificato" class="form-label">Tipo Certificato</label>
                    <select class="form-select" id="tipo_certificato" name="tipo_certificato" required>
                        <option value="" selected disabled>Seleziona tipo...</option>
                        <option value="Medico">Certificato Medico</option>
                        <option value="Permesso">Permesso</option>
                        <option value="Congedo">Congedo</option>
                        <option value="Convalescenza">Convalescenza</option>
                        <option value="Altro">Altro</option>
                    </select>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="data_inizio" class="form-label">Data Inizio</label>
                        <input type="date" class="form-control" id="data_inizio" name="data_inizio" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="data_fine" class="form-label">Data Fine</label>
                        <input type="date" class="form-control" id="data_fine" name="data_fine" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="note" class="form-label">Note</label>
                    <textarea class="form-control" id="note" name="note" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="file_certificato" class="form-label">File Certificato (facoltativo)</label>
                    <input class="form-control" type="file" id="file_certificato" name="file_certificato">
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Salva Certificato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div> 
