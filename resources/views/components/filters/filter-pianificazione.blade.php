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
    foreach(['grado_id', 'plotone_id', 'ufficio_id', 'mansione', 'approntamento_id', 'impegno', 'giorno'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

@component('components.filters.filter-base', ['formAction' => route('pianificazione.index'), 'activeCount' => $activeCount, 'filterActive' => $filterActive])
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

    {{-- Filtro Ufficio --}}
    <div class="col mb-2">
        <label for="ufficio_id" class="form-label small">Ufficio</label>
        <div class="select-wrapper">
            <select name="ufficio_id" id="ufficio_id" class="form-select form-select-sm filter-select {{ request()->filled('ufficio_id') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                @if(isset($uffici))
                    @foreach($uffici as $ufficio)
                        <option value="{{ $ufficio->id }}" {{ request('ufficio_id') == $ufficio->id ? 'selected' : '' }}>
                            {{ $ufficio->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('ufficio_id'))
                <span class="clear-filter" data-filter="ufficio_id" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Filtro Mansione --}}
    <div class="col mb-2">
        <label for="mansione" class="form-label small">Mansione</label>
        <div class="select-wrapper">
            <select name="mansione" id="mansione" class="form-select form-select-sm filter-select {{ request()->filled('mansione') ? 'applied' : '' }}">
                <option value="">Tutte</option>
                @if(isset($mansioni))
                    @foreach($mansioni as $mansione)
                        <option value="{{ $mansione->nome }}" {{ request('mansione') == $mansione->nome ? 'selected' : '' }}>
                            {{ $mansione->nome }}
                        </option>
                    @endforeach
                @endif
            </select>
            @if(request()->filled('mansione'))
                <span class="clear-filter" data-filter="mansione" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
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
