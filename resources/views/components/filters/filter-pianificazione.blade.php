{{--
|--------------------------------------------------------------------------
| Filtri specifici per sezione Pianificazione
|--------------------------------------------------------------------------
| @version 1.1
| @author Michele Di Gennaro
--}}

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['compagnia', 'grado_id', 'plotone_id', 'ufficio_id', 'patente', 'approntamento_id', 'impegno', 'disponibile', 'compleanno', 'giorno'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

<div class="filter-card mb-4">
    <div class="filter-card-header d-flex justify-content-between align-items-center">
        <div>
            Filtri avanzati
        </div>
    </div>
    <div class="card-body p-3">
        <form id="filtroForm" action="{{ route('pianificazione.index') }}" method="GET" class="filter-local">
            {{-- RIGA 1: Compagnia, Grado, Plotone, Ufficio, Teatro Operativo --}}
            <div class="row row-cols-2 row-cols-md-5 g-2 mb-2">
                {{-- Filtro Compagnia --}}
                <div class="col">
                    <label for="compagnia" class="form-label small mb-1">Compagnia</label>
                    <div class="select-wrapper">
                        <select name="compagnia" id="compagnia" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutte</option>
                            @if(isset($compagnie))
                                @foreach($compagnie as $compagnia)
                                    <option value="{{ $compagnia->id }}">
                                        {{ $compagnia->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="compagnia" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Grado --}}
                <div class="col">
                    <label for="grado_id" class="form-label small mb-1">Grado</label>
                    <div class="select-wrapper">
                        <select name="grado_id" id="grado_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            @if(isset($gradi))
                                @foreach($gradi as $grado)
                                    <option value="{{ $grado->id }}">
                                        {{ $grado->sigla }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="grado_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>
                
                {{-- Filtro Plotone --}}
                <div class="col">
                    <label for="plotone_id" class="form-label small mb-1">Plotone</label>
                    <div class="select-wrapper">
                        <select name="plotone_id" id="plotone_id" 
                                class="form-select form-select-sm filter-select"
                                data-nosubmit="true"
                                disabled
                                title="Seleziona prima una compagnia">
                            <option value="">Seleziona compagnia</option>
                            @if(isset($plotoni))
                                @foreach($plotoni as $plotone)
                                    <option value="{{ $plotone->id }}" 
                                            data-compagnia-id="{{ $plotone->compagnia_id }}">
                                        {{ $plotone->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="plotone_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Ufficio --}}
                <div class="col">
                    <label for="ufficio_id" class="form-label small mb-1">Ufficio</label>
                    <div class="select-wrapper">
                        <select name="ufficio_id" id="ufficio_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            @if(isset($uffici))
                                @foreach($uffici as $ufficio)
                                    <option value="{{ $ufficio->id }}">
                                        {{ $ufficio->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="ufficio_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Teatro Operativo --}}
                <div class="col">
                    <label for="approntamento_id" class="form-label small mb-1">Teatro Operativo</label>
                    <div class="select-wrapper">
                        <select name="approntamento_id" id="approntamento_id" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            <option value="libero">Libero</option>
                            @if(isset($approntamenti))
                                @foreach($approntamenti as $approntamento)
                                    <option value="{{ $approntamento->id }}">
                                        {{ $approntamento->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="approntamento_id" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>
            </div>

            {{-- RIGA 2: Patente, Impegno, Disponibile, Compleanno, Giorno --}}
            <div class="row row-cols-2 row-cols-md-5 g-2">
                {{-- Filtro Patente --}}
                <div class="col">
                    <label for="patente" class="form-label small mb-1">Patente</label>
                    <div class="select-wrapper">
                        <select name="patente" id="patente" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutte</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                        <span class="clear-filter" data-filter="patente" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Impegno --}}
                <div class="col">
                    <label for="impegno" class="form-label small mb-1">Impegno</label>
                    <div class="select-wrapper">
                        <select name="impegno" id="impegno" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            <option value="libero">Libero</option>
                            @if(isset($impegniPerCategoria))
                                @foreach($impegniPerCategoria as $categoria => $impegniCategoria)
                                    <optgroup label="{{ $categoria }}">
                                        @foreach($impegniCategoria as $impegno)
                                            <option value="{{ $impegno->codice }}">
                                                {{ $impegno->codice }} - {{ $impegno->nome }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            @elseif(isset($impegni))
                                @foreach($impegni as $impegno)
                                    <option value="{{ $impegno->codice }}">
                                        {{ $impegno->codice }} - {{ $impegno->nome }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="impegno" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Disponibile (ex Stato Oggi) --}}
                <div class="col">
                    <label for="disponibile" class="form-label small mb-1">Disponibile</label>
                    <div class="select-wrapper">
                        <select name="disponibile" id="disponibile" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                        <span class="clear-filter" data-filter="disponibile" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Compleanno --}}
                <div class="col">
                    <label for="compleanno" class="form-label small mb-1">Compleanno</label>
                    <div class="select-wrapper">
                        <select name="compleanno" id="compleanno" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            <option value="oggi">Oggi</option>
                            <option value="ultimi_2">Ultimi 2 giorni</option>
                            <option value="prossimi_2">Prossimi 2 giorni</option>
                        </select>
                        <span class="clear-filter" data-filter="compleanno" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>

                {{-- Filtro Giorno --}}
                <div class="col">
                    <label for="giorno" class="form-label small mb-1">Giorno</label>
                    <div class="select-wrapper">
                        <select name="giorno" id="giorno" class="form-select form-select-sm filter-select" data-nosubmit="true">
                            <option value="">Tutti</option>
                            @if(isset($giorniMese))
                                @foreach($giorniMese as $giorno)
                                    <option value="{{ $giorno['giorno'] }}">
                                        {{ $giorno['giorno'] }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <span class="clear-filter" data-filter="giorno" title="Rimuovi filtro" style="display: none;">&times;</span>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-center mt-3">
                <a href="#" class="btn btn-danger btn-sm" style="display: none;">
                    Rimuovi tutti i filtri (0)
                </a>
            </div>
        </form>
    </div>
</div>
