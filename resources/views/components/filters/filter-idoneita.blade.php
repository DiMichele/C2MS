{{--
|--------------------------------------------------------------------------
| Filtri specifici per sezione Idoneità
|--------------------------------------------------------------------------
| @version 1.0
| @author Michele Di Gennaro
--}}

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['mansione', 'certificati_registrati', 'stato_file', 'valido', 'in_scadenza', 'scaduti', 'idoneita_mansione', 'idoneita_smi', 'idoneita'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

@component('components.filters.filter-base', ['formAction' => route('certificati.idoneita'), 'activeCount' => $activeCount, 'filterActive' => $filterActive])
    {{-- Filtro Mansione --}}
    <div class="col-md-3 mb-3">
        <label for="mansione" class="form-label">
            <i class="fas fa-briefcase me-1"></i> Mansione
        </label>
        <div class="select-wrapper">
            <select name="mansione" id="mansione" class="form-select filter-select {{ request()->filled('mansione') ? 'applied' : '' }}">
                <option value="">Tutte le mansioni</option>
                @foreach(\App\Models\Mansione::orderBy('nome')->get() as $mansione)
                    <option value="{{ $mansione->nome }}" {{ request('mansione') == $mansione->nome ? 'selected' : '' }}>
                        {{ $mansione->nome }}
                    </option>
                @endforeach
            </select>
            @if(request()->filled('mansione'))
                <span class="clear-filter" data-filter="mansione" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Certificati --}}
    <div class="col-md-3 mb-3">
        <label for="certificati_registrati" class="form-label">
            <i class="fas fa-file-alt me-1"></i> Certificati
        </label>
        <div class="select-wrapper">
            <select name="certificati_registrati" id="certificati_registrati" class="form-select filter-select {{ request()->filled('certificati_registrati') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="1" {{ request('certificati_registrati') == '1' ? 'selected' : '' }}>Tutti registrati</option>
                <option value="0" {{ request('certificati_registrati') == '0' ? 'selected' : '' }}>Alcuni mancanti</option>
            </select>
            @if(request()->filled('certificati_registrati'))
                <span class="clear-filter" data-filter="certificati_registrati" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Stato File --}}
    <div class="col-md-3 mb-3">
        <label for="stato_file" class="form-label">
            <i class="fas fa-file-pdf me-1"></i> Stato File
        </label>
        <div class="select-wrapper">
            <select name="stato_file" id="stato_file" class="form-select filter-select {{ request()->filled('stato_file') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="1" {{ request('stato_file') == '1' ? 'selected' : '' }}>File mancanti</option>
                <option value="0" {{ request('stato_file') == '0' ? 'selected' : '' }}>Tutti i file presenti</option>
            </select>
            @if(request()->filled('stato_file'))
                <span class="clear-filter" data-filter="stato_file" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Idoneità Mansione --}}
    <div class="col-md-3 mb-3">
        <label for="idoneita_mansione" class="form-label">
            <i class="fas fa-user-md me-1"></i> Idoneità di Mansione
        </label>
        <div class="select-wrapper">
            <select name="idoneita_mansione" id="idoneita_mansione" class="form-select filter-select {{ request()->filled('idoneita_mansione') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="attiva" {{ request('idoneita_mansione') == 'attiva' ? 'selected' : '' }}>Valida</option>
                <option value="in_scadenza" {{ request('idoneita_mansione') == 'in_scadenza' ? 'selected' : '' }}>In scadenza</option>
                <option value="scaduta" {{ request('idoneita_mansione') == 'scaduta' ? 'selected' : '' }}>Scaduta</option>
            </select>
            @if(request()->filled('idoneita_mansione'))
                <span class="clear-filter" data-filter="idoneita_mansione" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Idoneità SMI --}}
    <div class="col-md-3 mb-3">
        <label for="idoneita_smi" class="form-label">
            <i class="fas fa-heartbeat me-1"></i> Idoneità SMI
        </label>
        <div class="select-wrapper">
            <select name="idoneita_smi" id="idoneita_smi" class="form-select filter-select {{ request()->filled('idoneita_smi') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="attiva" {{ request('idoneita_smi') == 'attiva' ? 'selected' : '' }}>Valida</option>
                <option value="in_scadenza" {{ request('idoneita_smi') == 'in_scadenza' ? 'selected' : '' }}>In scadenza</option>
                <option value="scaduta" {{ request('idoneita_smi') == 'scaduta' ? 'selected' : '' }}>Scaduta</option>
            </select>
            @if(request()->filled('idoneita_smi'))
                <span class="clear-filter" data-filter="idoneita_smi" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Idoneità (PEFO rinominato in Idoneità) --}}
    <div class="col-md-3 mb-3">
        <label for="idoneita" class="form-label">
            <i class="fas fa-running me-1"></i> Idoneità
        </label>
        <div class="select-wrapper">
            <select name="idoneita" id="idoneita" class="form-select filter-select {{ request()->filled('idoneita') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="attiva" {{ request('idoneita') == 'attiva' ? 'selected' : '' }}>Valida</option>
                <option value="in_scadenza" {{ request('idoneita') == 'in_scadenza' ? 'selected' : '' }}>In scadenza</option>
                <option value="scaduta" {{ request('idoneita') == 'scaduta' ? 'selected' : '' }}>Scaduta</option>
            </select>
            @if(request()->filled('idoneita'))
                <span class="clear-filter" data-filter="idoneita" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
@endcomponent 
