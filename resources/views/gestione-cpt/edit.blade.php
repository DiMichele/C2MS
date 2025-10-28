@extends('layouts.app')

@section('title', 'Modifica Codice CPT')

@section('content')
<style>
/* Stili uniformi */
.form-control, .form-select {
    border-radius: 4px;
    border-color: rgba(10, 35, 66, 0.20);
}

.form-control:focus, .form-select:focus {
    border-color: #0a2342;
    box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.15);
}

.form-label.required::after {
    content: " *";
    color: #dc3545;
}

.form-control-color {
    height: 50px;
    width: 80px;
    border: 2px solid rgba(10, 35, 66, 0.20);
    border-radius: 4px;
    cursor: pointer;
}

.color-presets {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.btn-color-preset {
    width: 48px;
    height: 48px;
    padding: 4px;
    border: 3px solid transparent;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-color-preset:hover {
    transform: scale(1.1);
    border-color: rgba(10, 35, 66, 0.30);
}

.btn-color-preset.selected {
    border-color: #0a2342;
    box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.15);
}

.btn-color-preset span {
    display: block;
    width: 100%;
    height: 100%;
    border-radius: 2px;
    border: 1px solid rgba(0,0,0,0.1);
}

.badge-preview-large {
    display: inline-block;
    padding: 1rem 2.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    border-radius: 4px;
    text-align: center;
    font-family: 'Courier New', monospace;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
</style>

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">MODIFICA CODICE CPT</h1>
</div>

<div class="d-flex justify-content-start mb-3">
    <a href="{{ route('codici-cpt.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Torna all'Elenco
    </a>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-6 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if($errors->any())
                    <div class="alert alert-danger">
                        <h6 class="alert-heading"><i class="fas fa-exclamation-circle"></i> Errori:</h6>
                        <ul class="mb-0 small">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($codice->tipiServizio()->count() > 0)
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Questo codice è utilizzato in <strong>{{ $codice->tipiServizio()->count() }}</strong> servizio/i.
                    </div>
                @endif

                <form action="{{ route('codici-cpt.update', $codice) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Codice -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">Codice/Sigla</label>
                        <input type="text" 
                               name="codice" 
                               class="form-control form-control-lg" 
                               value="{{ old('codice', $codice->codice) }}" 
                               maxlength="20" 
                               required
                               style="text-transform: uppercase;">
                        <div class="form-text">Codice breve univoco (max 20 caratteri)</div>
                    </div>

                    <!-- Categoria -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">Categoria</label>
                        <select name="macro_attivita" class="form-select form-select-lg" required>
                            <option value="">-- Seleziona categoria --</option>
                            @php
                                $categorieDefault = ['DISPONIBILE', 'ASSENTE', 'SERVIZIO', 'APPRONTAMENTI', 'NON_IMPIEGABILE'];
                                $categorieEsistenti = $macroAttivita->toArray();
                                $tutteCategorie = array_unique(array_merge($categorieDefault, $categorieEsistenti));
                                sort($tutteCategorie);
                            @endphp
                            @foreach($tutteCategorie as $cat)
                                <option value="{{ $cat }}" {{ old('macro_attivita', $codice->macro_attivita) == $cat ? 'selected' : '' }}>
                                    {{ $cat }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            @if(request()->old('macro_attivita') && request()->old('macro_attivita') != $codice->macro_attivita)
                                <span class="text-warning">⚠️ Cambiando categoria, il codice sarà riposizionato</span>
                            @else
                                L'ordine all'interno della categoria viene mantenuto
                            @endif
                        </div>
                    </div>

                    <!-- Descrizione -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">Descrizione</label>
                        <input type="text" 
                               name="attivita_specifica" 
                               class="form-control" 
                               value="{{ old('attivita_specifica', $codice->attivita_specifica) }}" 
                               maxlength="200" 
                               required>
                        <div class="form-text">Descrizione completa dell'attività</div>
                    </div>

                    <!-- Tipo Impiego -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">Tipo di Impiego</label>
                        <select name="impiego" class="form-select" required>
                            <option value="">-- Seleziona tipo --</option>
                            @foreach($impieghi as $value => $label)
                                <option value="{{ $value }}" {{ old('impiego', $codice->impiego) == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Determina la disponibilità del militare</div>
                    </div>

                    <!-- Colore -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold required">Colore Cella CPT</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <input type="color" 
                                   name="colore_badge" 
                                   class="form-control-color" 
                                   value="{{ old('colore_badge', $codice->colore_badge) }}" 
                                   id="colorPicker" 
                                   required>
                            <div class="flex-fill">
                                <input type="text" 
                                       class="form-control" 
                                       id="colorHex" 
                                       value="{{ old('colore_badge', $codice->colore_badge) }}" 
                                       maxlength="7" 
                                       readonly>
                            </div>
                        </div>
                        
                        <!-- Preset Colori CPT -->
                        <div class="color-presets">
                            <small class="text-muted d-block mb-2">Colori predefiniti CPT:</small>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#00b050' ? 'selected' : '' }}" data-color="#00b050" title="Verde - Disponibile/Servizio">
                                    <span style="background: #00b050;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#ffff00' ? 'selected' : '' }}" data-color="#ffff00" title="Giallo - Assente">
                                    <span style="background: #ffff00;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#ff0000' ? 'selected' : '' }}" data-color="#ff0000" title="Rosso - Non Impiegabile">
                                    <span style="background: #ff0000;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#ffc000' ? 'selected' : '' }}" data-color="#ffc000" title="Arancione - Approntamenti">
                                    <span style="background: #ffc000;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#0070c0' ? 'selected' : '' }}" data-color="#0070c0" title="Blu - Servizi Speciali">
                                    <span style="background: #0070c0;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#000000' ? 'selected' : '' }}" data-color="#000000" title="Nero - Comando">
                                    <span style="background: #000000;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#92d050' ? 'selected' : '' }}" data-color="#92d050" title="Verde Chiaro">
                                    <span style="background: #92d050;"></span>
                                </button>
                                <button type="button" class="btn-color-preset {{ $codice->colore_badge == '#6c757d' ? 'selected' : '' }}" data-color="#6c757d" title="Grigio - Default">
                                    <span style="background: #6c757d;"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Anteprima -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Anteprima</label>
                        <div class="text-center py-4 bg-light rounded">
                            <span id="badgePreview" class="badge-preview-large">
                                {{ old('codice', $codice->codice) }}
                            </span>
                        </div>
                    </div>

                    <!-- Pulsanti -->
                    <div class="d-flex gap-2 justify-content-between">
                        <a href="{{ route('codici-cpt.index') }}" class="btn btn-outline-secondary px-4">
                            Annulla
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-1"></i> Salva Modifiche
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('colorPicker');
    const colorHex = document.getElementById('colorHex');
    const badgePreview = document.getElementById('badgePreview');
    const codiceInput = document.querySelector('input[name="codice"]');

    colorPicker.addEventListener('input', function() {
        colorHex.value = this.value;
        updatePreview();
    });

    codiceInput.addEventListener('input', function() {
        badgePreview.textContent = this.value.toUpperCase() || 'CODICE';
    });

    document.querySelectorAll('.btn-color-preset').forEach(btn => {
        btn.addEventListener('click', function() {
            const color = this.dataset.color;
            colorPicker.value = color;
            colorHex.value = color;
            updatePreview();
            
            document.querySelectorAll('.btn-color-preset').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
        });
    });

    function updatePreview() {
        const color = colorPicker.value;
        const textColor = isLightColor(color) ? '#000' : '#fff';
        badgePreview.style.backgroundColor = color;
        badgePreview.style.color = textColor;
    }

    function isLightColor(color) {
        const hex = color.replace('#', '');
        const r = parseInt(hex.substr(0, 2), 16);
        const g = parseInt(hex.substr(2, 2), 16);
        const b = parseInt(hex.substr(4, 2), 16);
        const brightness = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return brightness > 128;
    }

    updatePreview();
});
</script>
@endsection
