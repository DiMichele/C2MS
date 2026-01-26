@section('styles')
<style>
    .form-card {
        border-radius: 10px;
        overflow: hidden;
        background-color: white;
        box-shadow: var(--shadow-sm);
        border: none;
    }
    
    .form-section {
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid var(--navy);
    }
    
    .form-section:last-child {
        margin-bottom: 0;
    }
    
    .form-section-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--navy);
        margin-bottom: 1.25rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.4rem;
        color: #4A5568;
        font-size: 0.9rem;
    }
    
    .form-control, .form-select {
        border-radius: 6px;
        border: 1px solid #E2E8F0;
        padding: 0.5rem 0.75rem;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
    }
    
    .required-field::after {
        content: ' *';
        color: #dc3545;
        font-weight: bold;
    }
    
    .form-footer {
        padding: 1.5rem;
        margin-top: 0;
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        border-radius: 0 0 10px 10px;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.85rem;
        color: #dc3545;
    }
    
    .form-text {
        font-size: 0.8rem;
        color: #718096;
    }
    
    /* Stile per checkbox delle patenti */
    .patenti-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        padding: 0.5rem;
        background: white;
        border-radius: 6px;
        border: 1px solid #E2E8F0;
    }
    
    .patenti-grid .form-check {
        min-width: 60px;
    }
    
    .patenti-grid .form-check-input:checked {
        background-color: var(--navy);
        border-color: var(--navy);
    }
    
    /* Input con icona calendario */
    input[type="date"] {
        cursor: pointer;
    }
</style>
@endsection

<!-- Header Minimal Solo Titolo -->
<div class="text-center mb-4">
    <h1 class="page-title">{{ isset($militare) ? 'Modifica Militare' : 'Nuovo Militare' }}</h1>
    @if(!isset($militare))
    <p class="text-muted">Inserisci i dati del nuovo militare. I campi contrassegnati con * sono obbligatori.</p>
    @endif
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-start">
                    <i class="fas fa-exclamation-triangle me-3 mt-1"></i>
                    <div>
                        <strong>Attenzione!</strong> Ci sono errori nel form:
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        <form method="POST" action="{{ isset($militare) ? route('anagrafica.update', $militare->id) : route('anagrafica.store') }}" id="militareForm">
            @csrf
            @if(isset($militare))
                @method('PUT')
            @endif
            
            <div class="card form-card">
                <div class="card-body p-4">
                    
                    <!-- SEZIONE 1: Dati Anagrafici -->
                    <div class="form-section">
                        <h5 class="form-section-title">Dati Anagrafici</h5>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="grado_id" class="form-label required-field">Grado</label>
                                    <select class="form-select @error('grado_id') is-invalid @enderror" id="grado_id" name="grado_id" required>
                                        <option value="">Seleziona Grado</option>
                                        @foreach($gradi as $grado)
                                            <option value="{{ $grado->id }}" {{ old('grado_id', $militare->grado_id ?? '') == $grado->id ? 'selected' : '' }}>
                                                {{ $grado->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('grado_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="cognome" class="form-label required-field">Cognome</label>
                                    <input type="text" class="form-control @error('cognome') is-invalid @enderror" id="cognome" name="cognome" value="{{ old('cognome', $militare->cognome ?? '') }}" required>
                                    @error('cognome')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nome" class="form-label required-field">Nome</label>
                                    <input type="text" class="form-control @error('nome') is-invalid @enderror" id="nome" name="nome" value="{{ old('nome', $militare->nome ?? '') }}" required>
                                    @error('nome')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label for="sesso" class="form-label required-field">Sesso</label>
                                    <select class="form-select @error('sesso') is-invalid @enderror" id="sesso" name="sesso" required>
                                        <option value="">-</option>
                                        <option value="M" {{ old('sesso', $militare->sesso ?? '') == 'M' ? 'selected' : '' }}>M</option>
                                        <option value="F" {{ old('sesso', $militare->sesso ?? '') == 'F' ? 'selected' : '' }}>F</option>
                                    </select>
                                    @error('sesso')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="data_nascita" class="form-label required-field">Data di Nascita</label>
                                    <input type="date" class="form-control @error('data_nascita') is-invalid @enderror" id="data_nascita" name="data_nascita" value="{{ old('data_nascita', isset($militare) && $militare->data_nascita ? $militare->data_nascita->format('Y-m-d') : '') }}" required>
                                    @error('data_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="luogo_nascita" class="form-label required-field">Comune di Nascita</label>
                                    <input type="text" class="form-control @error('luogo_nascita') is-invalid @enderror" id="luogo_nascita" name="luogo_nascita" value="{{ old('luogo_nascita', $militare->luogo_nascita ?? '') }}" placeholder="Es: Roma, Milano, Napoli..." autocomplete="off" required>
                                    @error('luogo_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="provincia_nascita" class="form-label required-field">Provincia</label>
                                    <input type="text" class="form-control @error('provincia_nascita') is-invalid @enderror" id="provincia_nascita" name="provincia_nascita" value="{{ old('provincia_nascita', $militare->provincia_nascita ?? '') }}" maxlength="2" placeholder="RM" style="text-transform: uppercase;" required>
                                    @error('provincia_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="anzianita" class="form-label">Anzianità</label>
                                    <input type="date" class="form-control @error('anzianita') is-invalid @enderror" id="anzianita" name="anzianita" value="{{ old('anzianita', isset($militare) && $militare->anzianita ? (is_object($militare->anzianita) ? $militare->anzianita->format('Y-m-d') : $militare->anzianita) : '') }}">
                                    @error('anzianita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="codice_fiscale" class="form-label">Codice Fiscale</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('codice_fiscale') is-invalid @enderror" id="codice_fiscale" name="codice_fiscale" value="{{ old('codice_fiscale', $militare->codice_fiscale ?? '') }}" maxlength="16" style="text-transform: uppercase; font-family: 'Courier New', monospace; font-weight: 600; letter-spacing: 1px;">
                                        <span class="input-group-text bg-secondary text-white" id="cf-status" title="Completa tutti i campi per calcolare il CF">
                                            CF
                                        </span>
                                    </div>
                                    @error('codice_fiscale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text">Calcolato automaticamente da: Cognome, Nome, Data di Nascita, Sesso e Comune</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEZIONE 2: Assegnazione Organizzativa -->
                    <div class="form-section">
                        <h5 class="form-section-title">Assegnazione Organizzativa</h5>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="compagnia_id" class="form-label">Compagnia</label>
                                    <select class="form-select @error('compagnia_id') is-invalid @enderror" id="compagnia_id" name="compagnia_id">
                                        <option value="">Seleziona Compagnia</option>
                                        @php
                                            $compagnie = \App\Models\Compagnia::orderBy('nome')->get();
                                        @endphp
                                        @foreach($compagnie as $compagnia)
                                            <option value="{{ $compagnia->id }}" {{ old('compagnia_id', $militare->compagnia_id ?? '') == $compagnia->id ? 'selected' : '' }}>
                                                {{ $compagnia->numero ?? $compagnia->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('compagnia_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="plotone_id" class="form-label">Plotone</label>
                                    <select class="form-select @error('plotone_id') is-invalid @enderror" id="plotone_id" name="plotone_id" {{ !old('compagnia_id', $militare->compagnia_id ?? '') ? 'disabled' : '' }}>
                                        <option value="">{{ !old('compagnia_id', $militare->compagnia_id ?? '') ? 'Seleziona prima una compagnia' : 'Seleziona Plotone' }}</option>
                                        @foreach($plotoni as $plotone)
                                            <option value="{{ $plotone->id }}" 
                                                    data-compagnia-id="{{ $plotone->compagnia_id }}"
                                                    {{ old('plotone_id', $militare->plotone_id ?? '') == $plotone->id ? 'selected' : '' }}>
                                                {{ $plotone->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('plotone_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="polo_id" class="form-label">Ufficio</label>
                                    <select class="form-select @error('polo_id') is-invalid @enderror" id="polo_id" name="polo_id">
                                        <option value="">Seleziona Ufficio</option>
                                        @foreach($poli as $polo)
                                            <option value="{{ $polo->id }}" {{ old('polo_id', $militare->polo_id ?? '') == $polo->id ? 'selected' : '' }}>
                                                {{ $polo->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('polo_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="mansione_id" class="form-label">Incarico</label>
                                    <select class="form-select @error('mansione_id') is-invalid @enderror" id="mansione_id" name="mansione_id">
                                        <option value="">Seleziona Incarico</option>
                                        @foreach($mansioni as $mansione)
                                            <option value="{{ $mansione->id }}" {{ old('mansione_id', $militare->mansione_id ?? '') == $mansione->id ? 'selected' : '' }}>
                                                {{ $mansione->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('mansione_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEZIONE 3: Stato e Abilitazioni -->
                    <div class="form-section">
                        <h5 class="form-section-title">Stato e Abilitazioni</h5>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="nos_status" class="form-label">NOS</label>
                                    <select class="form-select @error('nos_status') is-invalid @enderror" id="nos_status" name="nos_status">
                                        <option value="">Seleziona</option>
                                        <option value="si" {{ old('nos_status', $militare->nos_status ?? '') == 'si' ? 'selected' : '' }}>SI</option>
                                        <option value="no" {{ old('nos_status', $militare->nos_status ?? '') == 'no' ? 'selected' : '' }}>NO</option>
                                        <option value="da richiedere" {{ old('nos_status', $militare->nos_status ?? '') == 'da richiedere' ? 'selected' : '' }}>Da Richiedere</option>
                                        <option value="non previsto" {{ old('nos_status', $militare->nos_status ?? '') == 'non previsto' ? 'selected' : '' }}>Non Previsto</option>
                                        <option value="in attesa" {{ old('nos_status', $militare->nos_status ?? '') == 'in attesa' ? 'selected' : '' }}>In Attesa</option>
                                    </select>
                                    @error('nos_status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-9">
                                <div class="form-group mb-0">
                                    <label class="form-label">Patenti Militari</label>
                                    <div class="patenti-grid">
                                        @php
                                            $patentiMilitare = isset($militare) ? $militare->patenti->pluck('categoria')->toArray() : [];
                                            $patentiOld = old('patenti', []);
                                        @endphp
                                        @foreach(['2', '3', '4', '5', '6'] as $patente)
                                            <div class="form-check">
                                                <input type="checkbox" 
                                                       class="form-check-input" 
                                                       id="patente_{{ $patente }}"
                                                       name="patenti[]" 
                                                       value="{{ $patente }}"
                                                       {{ in_array($patente, $patentiMilitare) || in_array($patente, $patentiOld) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="patente_{{ $patente }}">
                                                    Patente {{ $patente }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SEZIONE 4: Contatti -->
                    <div class="form-section">
                        <h5 class="form-section-title">Informazioni di Contatto</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email_istituzionale" class="form-label">Email Istituzionale</label>
                                    <input type="email" class="form-control @error('email_istituzionale') is-invalid @enderror" id="email_istituzionale" name="email_istituzionale" value="{{ old('email_istituzionale', $militare->email_istituzionale ?? '') }}" placeholder="esempio@esercito.difesa.it">
                                    @error('email_istituzionale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="telefono" class="form-label">Numero di Cellulare</label>
                                    <input type="tel" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $militare->telefono ?? '') }}" placeholder="+39 333 1234567">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
                
                <div class="form-footer">
                    <a href="{{ route('anagrafica.index') }}" class="btn btn-outline-secondary">
                        Annulla
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ isset($militare) ? 'Aggiorna Militare' : 'Salva Militare' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
{{-- Database comuni italiani per calcolo CF --}}
<script src="{{ asset('js/comuni-italiani.js') }}?v={{ time() }}"></script>
{{-- Script calcolo codice fiscale --}}
<script src="{{ asset('js/calcola-codice-fiscale.js') }}?v={{ time() }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Uppercase automatico per provincia e codice fiscale
    const uppercaseFields = ['provincia_nascita', 'codice_fiscale', 'luogo_nascita'];
    uppercaseFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
    
    // ============================
    // GESTIONE COMPAGNIA -> PLOTONE
    // ============================
    
    const compagniaSelect = document.getElementById('compagnia_id');
    const plotoneSelect = document.getElementById('plotone_id');
    
    function updatePlotoniOptions(compagniaId) {
        const options = plotoneSelect.querySelectorAll('option');
        let hasVisibleOptions = false;
        
        options.forEach(option => {
            if (option.value === '') {
                // Aggiorna il placeholder
                if (!compagniaId) {
                    option.textContent = 'Seleziona prima una compagnia';
                } else {
                    option.textContent = 'Seleziona Plotone';
                }
                option.style.display = '';
                return;
            }
            
            const optionCompagniaId = option.getAttribute('data-compagnia-id');
            
            if (!compagniaId || optionCompagniaId == compagniaId) {
                option.style.display = '';
                hasVisibleOptions = true;
            } else {
                option.style.display = 'none';
            }
        });
        
        // Abilita/disabilita il select
        plotoneSelect.disabled = !compagniaId;
        
        // Se il plotone selezionato non appartiene alla compagnia, resetta
        if (compagniaId && plotoneSelect.value) {
            const selectedOption = plotoneSelect.querySelector(`option[value="${plotoneSelect.value}"]`);
            if (selectedOption && selectedOption.getAttribute('data-compagnia-id') != compagniaId) {
                plotoneSelect.value = '';
            }
        }
    }
    
    if (compagniaSelect && plotoneSelect) {
        compagniaSelect.addEventListener('change', function() {
            updatePlotoniOptions(this.value);
        });
        
        // Inizializza al caricamento
        updatePlotoniOptions(compagniaSelect.value);
    }
});
</script>
@endpush
