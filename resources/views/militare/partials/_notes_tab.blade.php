{{--
|--------------------------------------------------------------------------
| Tab delle note del militare
|--------------------------------------------------------------------------
| Visualizza e permette la modifica di tutte le note relative al militare
| @version 1.0
| @author Michele Di Gennaro
--}}

<div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
    <!-- Note Generali del Militare -->
    <div class="mb-4">
        <h6 class="mb-3">
            <i class="fas fa-user text-primary me-2"></i>
            Note Generali Militare
        </h6>
        <textarea 
            class="form-control auto-save-notes" 
            data-militare-id="{{ $militare->id }}"
            data-field="note"
            data-autosave-url="{{ route('militare.update', $militare->id) }}"
            data-autosave-field="note"
            rows="4"
            placeholder="Note generali sul militare..."
        >{{ $militare->note ?? '' }}</textarea>
    </div>

    <!-- Note sui Certificati -->
    <div class="mb-4">
        <h6 class="mb-3">
            <i class="fas fa-file-alt text-success me-2"></i>
            Note sui Certificati
        </h6>
        <textarea 
            class="form-control auto-save-notes" 
            data-militare-id="{{ $militare->id }}"
            data-field="certificati_note"
            data-autosave-url="{{ route('militare.update', $militare->id) }}"
            data-autosave-field="certificati_note"
            rows="4"
            placeholder="Note specifiche sui certificati e corsi..."
        >{{ $militare->certificati_note ?? '' }}</textarea>
    </div>

    <!-- Note sulle Idoneità -->
    <div class="mb-4">
        <h6 class="mb-3">
            <i class="fas fa-shield-alt text-warning me-2"></i>
            Note sulle Idoneità
        </h6>
        <textarea 
            class="form-control auto-save-notes" 
            data-militare-id="{{ $militare->id }}"
            data-field="idoneita_note"
            data-autosave-url="{{ route('militare.update', $militare->id) }}"
            data-autosave-field="idoneita_note"
            rows="4"
            placeholder="Note specifiche sulle idoneità..."
        >{{ $militare->idoneita_note ?? '' }}</textarea>
    </div>
</div>

<style>
    .auto-save-notes {
        width: 100%;
        min-height: 100px;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        resize: vertical;
        font-size: 0.95rem;
        line-height: 1.5;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .auto-save-notes:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
        outline: none;
    }
    
    .auto-save-notes.autosave-saving {
        border-color: #ffc107;
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.25);
    }
    
    .auto-save-notes.autosave-saved {
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25);
    }
    
    .auto-save-notes.autosave-error {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25);
    }
    
    .auto-save-notes::placeholder {
        color: #a0aec0;
    }
    
    .autosave-indicator {
        font-size: 12px;
        font-weight: 500;
    }
    
    .autosave-indicator.saving {
        color: #ffc107;
    }
    
    .autosave-indicator.saved {
        color: #28a745;
    }
    
    .autosave-indicator.error {
        color: #dc3545;
    }
</style> 
