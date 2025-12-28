@section('styles')
<style>
    /* Stili rimossi per form-header - ora uso lo stile minimal */
    
    .form-card {
        border-radius: 10px;
        overflow: hidden;
        background-color: white;
        box-shadow: var(--shadow-sm);
    }
    
    .form-section {
        margin-bottom: 2rem;
    }
    
    .form-section-title {
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--light-sand);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .form-section-title i {
        color: var(--gold);
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 500;
        margin-bottom: 0.5rem;
        color: #4A5568;
    }
    
    .form-control {
        border-radius: 6px;
        border: 1px solid #E2E8F0;
        padding: 0.625rem 0.75rem;
        font-size: 1rem;
        transition: all 0.2s ease;
    }
    
    .form-control:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
    }
    
    .form-select {
        border-radius: 6px;
        border: 1px solid #E2E8F0;
        padding: 0.625rem 0.75rem;
        font-size: 1rem;
        transition: all 0.2s ease;
        background-position: right 0.75rem center;
    }
    
    .form-select:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 3px rgba(10, 35, 66, 0.1);
    }
    
    .required-field::after {
        content: ' *';
        color: var(--error);
    }
    
    .form-footer {
        padding-top: 1.5rem;
        margin-top: 1.5rem;
        border-top: 1px solid #E2E8F0;
        display: flex;
        justify-content: space-between;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: var(--error);
    }
    
    .form-floating > .form-control,
    .form-floating > .form-select {
        height: 58px;
    }
    
    .form-floating > label {
        padding: 1rem 0.75rem;
    }
    
    .form-text {
        font-size: 0.875rem;
        color: #718096;
    }
</style>
@endsection

        <!-- Header Minimal Solo Titolo -->
        <div class="text-center mb-4">
            <h1 class="page-title">{{ isset($militare) ? 'Modifica Militare' : 'Nuovo Militare' }}</h1>
        </div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                    <div>
                        <strong>Attenzione!</strong> Ci sono errori nel form.
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
        
        <form method="POST" action="{{ isset($militare) ? route('anagrafica.update', $militare->id) : route('anagrafica.store') }}">
            @csrf
            @if(isset($militare))
                @method('PUT')
            @endif
            
            <div class="card form-card">
                <div class="card-body p-4">
                    <!-- Informazioni Personali -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-user"></i> Informazioni Personali
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="grado_id" class="form-label required-field">Grado</label>
                                    <select class="form-select @error('grado_id') is-invalid @enderror" id="grado_id" name="grado_id" required>
                                        <option value="">-- Seleziona Grado --</option>
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="data_nascita" class="form-label">Data di Nascita</label>
                                    <input type="date" class="form-control @error('data_nascita') is-invalid @enderror" id="data_nascita" name="data_nascita" value="{{ old('data_nascita', isset($militare) && $militare->data_nascita ? $militare->data_nascita->format('Y-m-d') : '') }}">
                                    @error('data_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="sesso" class="form-label">Sesso</label>
                                    <select class="form-select @error('sesso') is-invalid @enderror" id="sesso" name="sesso">
                                        <option value="">--</option>
                                        <option value="M" {{ old('sesso', $militare->sesso ?? '') == 'M' ? 'selected' : '' }}>M</option>
                                        <option value="F" {{ old('sesso', $militare->sesso ?? '') == 'F' ? 'selected' : '' }}>F</option>
                                    </select>
                                    @error('sesso')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="luogo_nascita" class="form-label">Comune di Nascita</label>
                                    <input type="text" class="form-control @error('luogo_nascita') is-invalid @enderror" id="luogo_nascita" name="luogo_nascita" value="{{ old('luogo_nascita', $militare->luogo_nascita ?? '') }}" placeholder="Es: Roma, Milano, Napoli..." autocomplete="off">
                                    @error('luogo_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Inserisci il nome del comune</small>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="provincia_nascita" class="form-label">Provincia</label>
                                    <input type="text" class="form-control @error('provincia_nascita') is-invalid @enderror" id="provincia_nascita" name="provincia_nascita" value="{{ old('provincia_nascita', $militare->provincia_nascita ?? '') }}" maxlength="2" placeholder="RM" style="text-transform: uppercase;">
                                    @error('provincia_nascita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="codice_fiscale" class="form-label">
                                        <i class="fas fa-id-card me-1 text-primary"></i>Codice Fiscale
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control @error('codice_fiscale') is-invalid @enderror" id="codice_fiscale" name="codice_fiscale" value="{{ old('codice_fiscale', $militare->codice_fiscale ?? '') }}" maxlength="16" style="text-transform: uppercase; font-family: 'Courier New', monospace; font-weight: 700; letter-spacing: 2px; font-size: 1.1rem;">
                                        <span class="input-group-text bg-secondary text-white" id="cf-status" title="Completa tutti i campi per calcolare il CF">
                                            <i class="fas fa-calculator"></i>
                                        </span>
                                    </div>
                                    @error('codice_fiscale')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-magic me-1"></i>Calcolato automaticamente da: Cognome, Nome, Data di Nascita, Sesso e Comune
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informazioni Organizzative -->
                    <div class="form-section">
                        <h5 class="form-section-title">
                            <i class="fas fa-sitemap"></i> Informazioni Organizzative
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="plotone_id" class="form-label">Plotone</label>
                                    <select class="form-select @error('plotone_id') is-invalid @enderror" id="plotone_id" name="plotone_id">
                                        <option value="">-- Seleziona Plotone --</option>
                                        @foreach($plotoni as $plotone)
                                            <option value="{{ $plotone->id }}" {{ old('plotone_id', $militare->plotone_id ?? '') == $plotone->id ? 'selected' : '' }}>
                                                {{ $plotone->nome }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('plotone_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="polo_id" class="form-label">Ufficio</label>
                                    <select class="form-select @error('polo_id') is-invalid @enderror" id="polo_id" name="polo_id">
                                        <option value="">-- Seleziona Ufficio --</option>
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="compagnia" class="form-label">Compagnia</label>
                                    <select class="form-select @error('compagnia') is-invalid @enderror" id="compagnia" name="compagnia">
                                        <option value="">-- Seleziona Compagnia --</option>
                                        <option value="110" {{ old('compagnia', $militare->compagnia ?? '') == '110' ? 'selected' : '' }}>110</option>
                                        <option value="124" {{ old('compagnia', $militare->compagnia ?? '') == '124' ? 'selected' : '' }}>124</option>
                                        <option value="127" {{ old('compagnia', $militare->compagnia ?? '') == '127' ? 'selected' : '' }}>127</option>
                                    </select>
                                    @error('compagnia')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mansione_id" class="form-label">Incarico</label>
                                    <select class="form-select @error('mansione_id') is-invalid @enderror" id="mansione_id" name="mansione_id">
                                        <option value="">-- Seleziona Incarico --</option>
                                        @foreach(\App\Models\Mansione::all() as $mansione)
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
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="anzianita" class="form-label">Anzianità</label>
                                    <input type="text" class="form-control @error('anzianita') is-invalid @enderror" id="anzianita" name="anzianita" value="{{ old('anzianita', $militare->anzianita ?? '') }}" placeholder="Es: 2024">
                                    @error('anzianita')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nos_status" class="form-label">NOS</label>
                                    <select class="form-select @error('nos_status') is-invalid @enderror" id="nos_status" name="nos_status">
                                        <option value="">-- Seleziona NOS --</option>
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
                        </div>
                    </div>
                    
                    <!-- Informazioni di Contatto -->
                    <div class="form-section mb-0">
                        <h5 class="form-section-title">
                            <i class="fas fa-address-card"></i> Informazioni di Contatto
                        </h5>
                        
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
                                <div class="form-group">
                                    <label for="telefono" class="form-label">Numero di Cellulare</label>
                                    <input type="tel" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $militare->telefono ?? '') }}" placeholder="+39 333 1234567">
                                    @error('telefono')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <a href="{{ route('anagrafica.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Annulla
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>{{ isset($militare) ? 'Aggiorna Militare' : 'Salva Militare' }}
                        </button>
                    </div>
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
});
</script>
@endpush
