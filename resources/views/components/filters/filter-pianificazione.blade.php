{{--
|--------------------------------------------------------------------------
| Filtri specifici per sezione Pianificazione
|--------------------------------------------------------------------------
| @version 1.0
| @author Michele Di Gennaro
--}}

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['compagnia', 'grado_id', 'plotone_id', 'patente', 'approntamento_id', 'impegno', 'compleanno', 'giorno'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

@component('components.filters.filter-base', ['formAction' => route('pianificazione.index'), 'activeCount' => $activeCount, 'filterActive' => $filterActive])
    {{-- Filtro Compagnia --}}
    <div class="col mb-2">
        <label for="compagnia" class="form-label small">Compagnia</label>
        <div class="select-wrapper">
            <select name="compagnia" id="compagnia" class="form-select form-select-sm filter-select {{ request()->filled('compagnia') ? 'applied' : '' }}">
                <option value="">Tutte</option>
                <option value="1" {{ request('compagnia') == '1' ? 'selected' : '' }}>1a</option>
                <option value="2" {{ request('compagnia') == '2' ? 'selected' : '' }}>2a</option>
                <option value="3" {{ request('compagnia') == '3' ? 'selected' : '' }}>3a</option>
                <option value="4" {{ request('compagnia') == '4' ? 'selected' : '' }}>4a</option>
                <option value="5" {{ request('compagnia') == '5' ? 'selected' : '' }}>5a</option>
            </select>
            @if(request()->filled('compagnia'))
                <span class="clear-filter" data-filter="compagnia" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Grado --}}
    <div class="col mb-2">
        <label for="grado_id" class="form-label small">Grado</label>
        <div class="select-wrapper">
            <select name="grado_id" id="grado_id" class="form-select form-select-sm filter-select {{ request()->filled('grado_id') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                @if(isset($gradi))
                    @foreach($gradi as $grado)
                        <option value="{{ $grado->id }}" {{ request('grado_id') == $grado->id ? 'selected' : '' }}>
                            {{ $grado->sigla }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('grado_id'))
                <span class="clear-filter" data-filter="grado_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Filtro Plotone --}}
    <div class="col mb-2">
        <label for="plotone_id" class="form-label small">Plotone</label>
        <div class="select-wrapper">
            <select name="plotone_id" id="plotone_id" class="form-select form-select-sm filter-select {{ request()->filled('plotone_id') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                @if(isset($plotoni))
                    @foreach($plotoni as $plotone)
                        <option value="{{ $plotone->id }}" {{ request('plotone_id') == $plotone->id ? 'selected' : '' }}>
                            {{ $plotone->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('plotone_id'))
                <span class="clear-filter" data-filter="plotone_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Patente --}}
    <div class="col mb-2">
        <label for="patente" class="form-label small">Patente</label>
        <div class="select-wrapper">
            <select name="patente" id="patente" class="form-select form-select-sm filter-select {{ request()->filled('patente') ? 'applied' : '' }}">
                <option value="">Tutte</option>
                <option value="2" {{ request('patente') == '2' ? 'selected' : '' }}>2</option>
                <option value="3" {{ request('patente') == '3' ? 'selected' : '' }}>3</option>
                <option value="4" {{ request('patente') == '4' ? 'selected' : '' }}>4</option>
                <option value="5" {{ request('patente') == '5' ? 'selected' : '' }}>5</option>
                <option value="6" {{ request('patente') == '6' ? 'selected' : '' }}>6</option>
            </select>
            @if(request()->filled('patente'))
                <span class="clear-filter" data-filter="patente" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Approntamento --}}
    <div class="col mb-2">
        <label for="approntamento_id" class="form-label small">Approntamento</label>
        <div class="select-wrapper">
            <select name="approntamento_id" id="approntamento_id" class="form-select form-select-sm filter-select {{ request()->filled('approntamento_id') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="libero" {{ request('approntamento_id') == 'libero' ? 'selected' : '' }}>Libero</option>
                @if(isset($approntamenti))
                    @foreach($approntamenti as $approntamento)
                        <option value="{{ $approntamento->id }}" {{ request('approntamento_id') == $approntamento->id ? 'selected' : '' }}>
                            {{ $approntamento->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('approntamento_id'))
                <span class="clear-filter" data-filter="approntamento_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Impegno --}}
    <div class="col mb-2">
        <label for="impegno" class="form-label small">Impegno</label>
        <div class="select-wrapper">
            <select name="impegno" id="impegno" class="form-select form-select-sm filter-select {{ request()->filled('impegno') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="libero" {{ request('impegno') == 'libero' ? 'selected' : '' }}>Libero</option>
                @if(isset($impegni))
                    @foreach($impegni as $impegno)
                        <option value="{{ $impegno->codice }}" {{ request('impegno') == $impegno->codice ? 'selected' : '' }}>
                            {{ $impegno->codice }} - {{ $impegno->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('impegno'))
                <span class="clear-filter" data-filter="impegno" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Compleanno --}}
    <div class="col mb-2">
        <label for="compleanno" class="form-label small">Compleanno</label>
        <div class="select-wrapper">
            <select name="compleanno" id="compleanno" class="form-select form-select-sm filter-select {{ request()->filled('compleanno') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="oggi" {{ request('compleanno') == 'oggi' ? 'selected' : '' }}>Oggi</option>
                <option value="ultimi_2" {{ request('compleanno') == 'ultimi_2' ? 'selected' : '' }}>Ultimi 2 giorni</option>
                <option value="prossimi_2" {{ request('compleanno') == 'prossimi_2' ? 'selected' : '' }}>Prossimi 2 giorni</option>
            </select>
            @if(request()->filled('compleanno'))
                <span class="clear-filter" data-filter="compleanno" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Giorno --}}
    <div class="col mb-2">
        <label for="giorno" class="form-label small">Giorno</label>
        <div class="select-wrapper">
            <select name="giorno" id="giorno" class="form-select form-select-sm filter-select {{ request()->filled('giorno') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                @if(isset($giorniMese))
                    @foreach($giorniMese as $giorno)
                        <option value="{{ $giorno['giorno'] }}" {{ request('giorno') == $giorno['giorno'] ? 'selected' : '' }}>
                            {{ $giorno['giorno'] }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('giorno'))
                <span class="clear-filter" data-filter="giorno" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
@endcomponent
