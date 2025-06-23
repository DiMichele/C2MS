<!-- Form per aggiungere note -->
<div class="note-form mb-4">
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-sticky-note me-2"></i> Note personali</h5>
        </div>
        <div class="card-body">
            <form id="noteForm">
                @csrf
                <input type="hidden" name="militare_id" id="note_militare_id" value="{{ $militare->id }}">
                <div class="note-textarea-container">
                    <textarea 
                        class="form-control note-textarea" 
                        id="noteContent" 
                        name="contenuto" 
                        placeholder="Aggiungi una nota personale su questo militare..."
                        rows="6"
                    >{{ $nota->contenuto ?? '' }}</textarea>
                    <div class="note-save-indicator" id="noteSaveIndicator">
                        <span class="saved-icon"><i class="fas fa-check-circle"></i> Salvato</span>
                        <span class="saving-icon"><i class="fas fa-spinner fa-spin"></i> Salvataggio...</span>
                    </div>
                </div>
            </form>
            <div class="note-info text-muted mt-2">
                <small>Le note vengono salvate automaticamente mentre scrivi. Sono visibili solo a te.</small>
            </div>
        </div>
    </div>
</div> 
