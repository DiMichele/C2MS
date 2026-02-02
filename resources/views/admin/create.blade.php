@extends('layouts.app')

@section('title', 'Nuovo Utente')

@section('styles')
<style>
.form-container {
    max-width: 900px;
    margin: 0 auto;
}

.form-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.form-header {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    color: white;
    padding: 2rem;
    text-align: center;
}

.form-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.5px;
}

.form-body {
    padding: 2.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--navy);
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.1);
}

.form-text {
    font-size: 0.85rem;
    color: #6c757d;
    margin-top: 0.35rem;
}

.alert-info-custom {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border: none;
    border-left: 4px solid #2196f3;
    border-radius: 8px;
    padding: 1rem 1.25rem;
    color: #0d47a1;
    font-size: 0.9rem;
}

.btn-group-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #f0f0f0;
}

.btn-primary-custom {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
    border: none;
    color: white;
    padding: 0.875rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(10, 35, 66, 0.2);
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(10, 35, 66, 0.3);
    color: white;
}

.btn-secondary-custom {
    background: white;
    border: 2px solid #dee2e6;
    color: #6c757d;
    padding: 0.875rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.btn-secondary-custom:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
    color: #495057;
}

.section-divider {
    display: flex;
    align-items: center;
    margin: 2rem 0 1.5rem 0;
    font-weight: 600;
    color: var(--navy);
    font-size: 1rem;
}

.section-divider::before,
.section-divider::after {
    content: '';
    flex: 1;
    height: 2px;
    background: linear-gradient(to right, transparent, #d4af37, transparent);
}

.section-divider::before {
    margin-right: 1rem;
}

.section-divider::after {
    margin-left: 1rem;
}
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h1>Crea Nuovo Utente</h1>
            </div>
            <div class="form-body">
                <form action="{{ route('admin.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="name" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Mario Rossi" required autofocus>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control @error('username') is-invalid @enderror" 
                               id="username" name="username" value="{{ old('username') }}" 
                               placeholder="mario.rossi" style="text-transform:lowercase" required>
                        <div class="form-text">Formato: nome.cognome (tutto minuscolo, senza spazi)</div>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert-info-custom mb-4">
                        La password di default sarà: <strong>11Reggimento</strong>
                        <br><small>L'utente sarà invitato a cambiarla al primo accesso.</small>
                    </div>

                    <div class="section-divider">
                        <span>Assegnazione Unità e Ruolo</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label for="organizational_unit_id" class="form-label">
                                Unità Organizzativa *
                            </label>
                            <select class="form-select @error('organizational_unit_id') is-invalid @enderror" 
                                    id="organizational_unit_id" 
                                    name="organizational_unit_id">
                                <option value="">-- Globale (accesso a tutte le unità) --</option>
                                @foreach($organizationalUnits as $unit)
                                    <option value="{{ $unit->id }}" 
                                            {{ old('organizational_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Seleziona un'unità specifica o lascia "Globale" per accesso completo
                            </div>
                            @error('organizational_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label for="role_id" class="form-label">
                                Ruolo *
                                <small class="text-muted" id="roleLoadingIndicator" style="display: none;">
                                    Caricamento ruoli...
                                </small>
                            </label>
                            <select class="form-select @error('role_id') is-invalid @enderror" 
                                    id="role_id" name="role_id" required>
                                <option value="">-- Seleziona ruolo --</option>
                                @if(old('organizational_unit_id'))
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                                data-is-global="{{ $role->is_global ? 'true' : 'false' }}"
                                                data-unit-id="{{ $role->organizational_unit_id }}"
                                                {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                            {{ $role->display_name }}
                                            @if($role->is_global)
                                                (Globale)
                                            @endif
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="roleHelpText">
                                I ruoli disponibili dipendono dall'unità selezionata
                            </div>
                        </div>
                    </div>

                    {{-- Legacy: compagnia nascosta per retrocompatibilità --}}
                    <input type="hidden" id="compagnia_id" name="compagnia_id" value="">

                    <div class="btn-group-actions">
                        <button type="submit" class="btn-primary-custom">
                            Crea Utente
                        </button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn-secondary-custom">
                            Annulla
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Forza minuscolo per username
document.getElementById('username').addEventListener('input', function() {
    this.value = this.value.toLowerCase();
});

// Carica ruoli quando cambia l'unità organizzativa
document.getElementById('organizational_unit_id').addEventListener('change', async function() {
    const unitId = this.value;
    const roleSelect = document.getElementById('role_id');
    const loadingIndicator = document.getElementById('roleLoadingIndicator');
    const roleHelpText = document.getElementById('roleHelpText');
    
    // Reset ruoli
    roleSelect.innerHTML = '<option value="">Caricamento ruoli...</option>';
    roleSelect.disabled = true;
    
    // Se non è selezionata nessuna unità (globale), mostra tutti i ruoli globali
    if (!unitId) {
        // Carica solo ruoli globali
        loadingIndicator.style.display = 'inline';
        
        try {
            // Usa un endpoint speciale per i ruoli globali o filtra lato client
            roleSelect.innerHTML = '<option value="">-- Seleziona ruolo --</option>';
            
            // Filtra i ruoli globali (implementazione semplificata)
            @foreach($roles as $role)
                @if($role->is_global)
                    const option{{ $role->id }} = document.createElement('option');
                    option{{ $role->id }}.value = {{ $role->id }};
                    option{{ $role->id }}.textContent = '{{ $role->display_name }} (Globale)';
                    option{{ $role->id }}.dataset.isGlobal = 'true';
                    roleSelect.appendChild(option{{ $role->id }});
                @endif
            @endforeach
            
            roleHelpText.textContent = 'Ruoli globali disponibili per accesso completo';
            roleHelpText.classList.remove('text-danger');
            roleHelpText.classList.add('text-muted');
        } finally {
            loadingIndicator.style.display = 'none';
            roleSelect.disabled = false;
        }
        return;
    }
    
    // Mostra indicatore di caricamento
    loadingIndicator.style.display = 'inline';
    
    try {
        const response = await fetch(`{{ url('/admin/ruoli/per-unita') }}/${unitId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Errore nel caricamento dei ruoli');
        }
        
        const data = await response.json();
        
        // Popola il select con i ruoli
        roleSelect.innerHTML = '<option value="">-- Seleziona ruolo --</option>';
        
        if (data.roles && data.roles.length > 0) {
            data.roles.forEach(role => {
                const option = document.createElement('option');
                option.value = role.id;
                option.textContent = role.display_name;
                if (role.is_global) {
                    option.textContent += ' (Globale)';
                }
                option.dataset.isGlobal = role.is_global ? 'true' : 'false';
                option.dataset.unitId = role.organizational_unit_id || '';
                roleSelect.appendChild(option);
            });
            
            roleHelpText.textContent = `${data.roles.length} ruoli disponibili`;
            roleHelpText.classList.remove('text-danger');
            roleHelpText.classList.add('text-muted');
        } else {
            roleSelect.innerHTML = '<option value="">Nessun ruolo disponibile</option>';
            roleHelpText.textContent = 'Nessun ruolo configurato per questa unità';
            roleHelpText.classList.add('text-danger');
            roleHelpText.classList.remove('text-muted');
        }
        
    } catch (error) {
        console.error('Errore caricamento ruoli:', error);
        roleSelect.innerHTML = '<option value="">Errore nel caricamento</option>';
        roleHelpText.textContent = 'Errore nel caricamento dei ruoli';
        roleHelpText.classList.add('text-danger');
        roleHelpText.classList.remove('text-muted');
    } finally {
        loadingIndicator.style.display = 'none';
        roleSelect.disabled = false;
    }
});

// Inizializza
document.addEventListener('DOMContentLoaded', function() {
    // Se c'è un valore old per l'unità, carica i ruoli
    const unitSelect = document.getElementById('organizational_unit_id');
    if (unitSelect.value) {
        unitSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
