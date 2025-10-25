{{--
|--------------------------------------------------------------------------
| Filtri specifici per sezione Militari
|--------------------------------------------------------------------------
| @version 1.0
| @author Michele Di Gennaro
--}}

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['grado', 'status', 'ruolo', 'mansione'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

@component('components.filters.filter-base', ['formAction' => route('anagrafica.index'), 'activeCount' => $activeCount, 'filterActive' => $filterActive])
    {{-- Filtro Grado --}}
    <div class="col-md-3 mb-3">
        <label for="grado" class="form-label">
            <i class="fas fa-medal me-1"></i> Grado
        </label>
        <div class="select-wrapper">
            <select name="grado" id="grado" class="form-select filter-select {{ request()->filled('grado') ? 'applied' : '' }}">
                <option value="">Tutti i gradi</option>
                @foreach(\App\Models\Grado::orderBy('nome')->get() as $grado)
                    <option value="{{ $grado->id }}" {{ request('grado') == $grado->id ? 'selected' : '' }}>
                        {{ $grado->nome }}
                    </option>
                @endforeach
            </select>
            @if(request()->filled('grado'))
                <span class="clear-filter" data-filter="grado" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Status --}}
    <div class="col-md-3 mb-3">
        <label for="status" class="form-label">
            <i class="fas fa-user-check me-1"></i> Status
        </label>
        <div class="select-wrapper">
            <select name="status" id="status" class="form-select filter-select {{ request()->filled('status') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="attivo" {{ request('status') == 'attivo' ? 'selected' : '' }}>Attivo</option>
                <option value="inattivo" {{ request('status') == 'inattivo' ? 'selected' : '' }}>Inattivo</option>
            </select>
            @if(request()->filled('status'))
                <span class="clear-filter" data-filter="status" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Ruolo --}}
    <div class="col-md-3 mb-3">
        <label for="ruolo" class="form-label">
            <i class="fas fa-user-tag me-1"></i> Ruolo
        </label>
        <div class="select-wrapper">
            <select name="ruolo" id="ruolo" class="form-select filter-select {{ request()->filled('ruolo') ? 'applied' : '' }}">
                <option value="">Tutti i ruoli</option>
                @foreach(\App\Models\Ruolo::orderBy('nome')->get() as $ruolo)
                    <option value="{{ $ruolo->id }}" {{ request('ruolo') == $ruolo->id ? 'selected' : '' }}>
                        {{ $ruolo->nome }}
                    </option>
                @endforeach
            </select>
            @if(request()->filled('ruolo'))
                <span class="clear-filter" data-filter="ruolo" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Mansione --}}
    <div class="col-md-3 mb-3">
        <label for="mansione" class="form-label">
            <i class="fas fa-briefcase me-1"></i> Mansione
        </label>
        <div class="select-wrapper">
            <select name="mansione" id="mansione" class="form-select filter-select {{ request()->filled('mansione') ? 'applied' : '' }}">
                <option value="">Tutte le mansioni</option>
                @foreach(\App\Models\Mansione::orderBy('nome')->get() as $mansione)
                    <option value="{{ $mansione->id }}" {{ request('mansione') == $mansione->id ? 'selected' : '' }}>
                        {{ $mansione->nome }}
                    </option>
                @endforeach
            </select>
            @if(request()->filled('mansione'))
                <span class="clear-filter" data-filter="mansione" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Pulsante Applica Filtri --}}
    <div class="filter-item filter-buttons">
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-filter me-1"></i> Applica Filtri
        </button>
    </div>
@endcomponent 
