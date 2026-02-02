@extends('layouts.app')

@section('title', 'Crea Nuovo Ruolo')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header text-center">
                <h1 class="page-title">CREA NUOVO RUOLO</h1>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm" style="border-radius: 0; border: none;">
                <div class="card-header" style="background: linear-gradient(135deg, #0a2342 0%, #1a3a5a 100%); color: white; border-radius: 0;">
                    <h5 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Informazioni Ruolo
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf

                        <div class="mb-4">
                            <label for="display_name" class="form-label fw-bold">Nome Ruolo *</label>
                            <input type="text" 
                                   class="form-control @error('display_name') is-invalid @enderror" 
                                   id="display_name" 
                                   name="display_name" 
                                   value="{{ old('display_name') }}"
                                   placeholder="es: Responsabile Logistica"
                                   required>
                            @error('display_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Campo nascosto per il nome tecnico -->
                        <input type="hidden" id="name" name="name" value="">
                        
                        <!-- Select Macro-entità di appartenenza -->
                        <div class="mb-4">
                            <label for="organizational_unit_id" class="form-label fw-bold">
                                Macro-entità di appartenenza *
                            </label>
                            <select class="form-select @error('organizational_unit_id') is-invalid @enderror" 
                                    id="organizational_unit_id" 
                                    name="organizational_unit_id">
                                <option value="" data-is-global="true" {{ old('organizational_unit_id') === null ? 'selected' : '' }}>
                                    -- Globale (visibile in tutte le unità) --
                                </option>
                                @foreach($organizationalUnits->where('depth', 1) as $unit)
                                    <option value="{{ $unit->id }}" 
                                            {{ old('organizational_unit_id') == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Seleziona la macro-entità a cui appartiene il ruolo o lascia "Globale" per un ruolo valido in tutte le unità
                            </div>
                            @error('organizational_unit_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Campo nascosto per is_global -->
                        <input type="hidden" id="is_global" name="is_global" value="{{ old('is_global', '1') }}">

                        <hr class="my-4">

                        <h6 class="mb-3 fw-bold"><i class="fas fa-key me-2"></i>Permessi</h6>
                        
                        @php
                            // Raggruppa i permessi per categoria e separa lettura/scrittura
                            $categorizedPerms = [];
                            foreach($permissions as $perm) {
                                // Escludi permessi admin
                                if (str_starts_with($perm->name, 'admin.')) {
                                    continue;
                                }
                                
                                $category = $perm->category;
                                if (!isset($categorizedPerms[$category])) {
                                    $categorizedPerms[$category] = [
                                        'read' => [],
                                        'write' => []
                                    ];
                                }
                                
                                if ($perm->type === 'read') {
                                    $categorizedPerms[$category]['read'][] = $perm;
                                } else {
                                    $categorizedPerms[$category]['write'][] = $perm;
                                }
                            }
                            
                            ksort($categorizedPerms);
                        @endphp
                        
                        @foreach($categorizedPerms as $category => $perms)
                        <div class="mb-4">
                            <h6 class="text-uppercase text-muted small mb-3" style="font-weight: 600;">
                                {{ strtoupper($category) }}
                            </h6>
                            <div class="row">
                                <!-- Colonna Lettura -->
                                <div class="col-md-6">
                                    <div class="border rounded p-3" style="background-color: #f8f9fa;">
                                        <h6 class="text-center mb-3" style="color: #0dcaf0;">
                                            <i class="fas fa-eye me-2"></i>Lettura
                                        </h6>
                                        @forelse($perms['read'] as $permission)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}"
                                                   id="perm-{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                {{ $permission->display_name }}
                                            </label>
                                        </div>
                                        @empty
                                        <p class="text-muted small mb-0">Nessun permesso di lettura</p>
                                        @endforelse
                                    </div>
                                </div>
                                
                                <!-- Colonna Modifica -->
                                <div class="col-md-6">
                                    <div class="border rounded p-3" style="background-color: #fff9e6;">
                                        <h6 class="text-center mb-3" style="color: #ffc107;">
                                            <i class="fas fa-edit me-2"></i>Modifica
                                        </h6>
                                        @forelse($perms['write'] as $permission)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="permissions[]" 
                                                   value="{{ $permission->id }}"
                                                   id="perm-{{ $permission->id }}"
                                                   {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm-{{ $permission->id }}">
                                                {{ $permission->display_name }}
                                            </label>
                                        </div>
                                        @empty
                                        <p class="text-muted small mb-0">Nessun permesso di modifica</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        <hr class="my-4">

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Annulla
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Crea Ruolo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Genera automaticamente il nome tecnico dal nome visualizzato
document.getElementById('display_name').addEventListener('input', function() {
    const displayName = this.value;
    const technicalName = displayName
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Rimuovi accenti
        .replace(/[^a-z0-9\s]/g, '') // Rimuovi caratteri speciali
        .trim()
        .replace(/\s+/g, '_'); // Sostituisci spazi con underscore
    document.getElementById('name').value = technicalName;
});

// Gestione is_global basato sulla selezione della macro-entità
document.getElementById('organizational_unit_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const isGlobal = selectedOption.dataset.isGlobal === 'true' || this.value === '';
    document.getElementById('is_global').value = isGlobal ? '1' : '0';
});

// Inizializza al caricamento della pagina
document.addEventListener('DOMContentLoaded', function() {
    const unitSelect = document.getElementById('organizational_unit_id');
    const isGlobal = unitSelect.value === '';
    document.getElementById('is_global').value = isGlobal ? '1' : '0';
});
</script>
@endsection
