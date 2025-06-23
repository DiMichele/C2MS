{{--
|--------------------------------------------------------------------------
| Filtri specifici per sezione Corsi Lavoratori
|--------------------------------------------------------------------------
| @version 1.0
| @author Michele Di Gennaro
--}}

@php
    // Check if any filters are active
    $activeFilters = [];
    foreach(['ruolo', 'certificati_registrati', 'stato_file', 'valido', 'in_scadenza', 'scaduti', 'cert_4h', 'cert_8h', 'cert_preposti', 'cert_dirigenti'] as $filter) {
        if(request()->filled($filter)) $activeFilters[] = $filter;
    }
    $activeCount = count($activeFilters);
    $filterActive = $activeCount > 0;
@endphp

@component('components.filters.filter-base', ['formAction' => route('certificati.corsi_lavoratori'), 'activeCount' => $activeCount, 'filterActive' => $filterActive])
    {{-- Filtro Ruolo --}}
    <div class="col-md-3 mb-3">
        <label for="ruolo" class="form-label">
            <i class="fas fa-user-tag me-1"></i> Ruolo
        </label>
        <div class="select-wrapper">
            <select name="ruolo" id="ruolo" class="form-select filter-select {{ request()->filled('ruolo') ? 'applied' : '' }}">
                <option value="">Tutti i ruoli</option>
                <option value="Lavoratore" {{ request('ruolo') == 'Lavoratore' ? 'selected' : '' }}>Lavoratore</option>
                <option value="Preposto" {{ request('ruolo') == 'Preposto' ? 'selected' : '' }}>Preposto</option>
                <option value="Dirigente" {{ request('ruolo') == 'Dirigente' ? 'selected' : '' }}>Dirigente</option>
            </select>
            @if(request()->filled('ruolo'))
                <span class="clear-filter" data-filter="ruolo" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
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
    
    {{-- Certificato 4H --}}
    <div class="col-md-3 mb-3">
        <label for="cert_4h" class="form-label">
            <i class="fas fa-certificate me-1"></i> Corso 4H
        </label>
        <div class="select-wrapper">
            <select name="cert_4h" id="cert_4h" class="form-select filter-select {{ request()->filled('cert_4h') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="valido" {{ request('cert_4h') == 'valido' ? 'selected' : '' }}>Valido</option>
                <option value="scaduto" {{ request('cert_4h') == 'scaduto' ? 'selected' : '' }}>Scaduto</option>
                <option value="assente" {{ request('cert_4h') == 'assente' ? 'selected' : '' }}>Assente</option>
            </select>
            @if(request()->filled('cert_4h'))
                <span class="clear-filter" data-filter="cert_4h" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Certificato 8H --}}
    <div class="col-md-3 mb-3">
        <label for="cert_8h" class="form-label">
            <i class="fas fa-certificate me-1"></i> Corso 8H
        </label>
        <div class="select-wrapper">
            <select name="cert_8h" id="cert_8h" class="form-select filter-select {{ request()->filled('cert_8h') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="valido" {{ request('cert_8h') == 'valido' ? 'selected' : '' }}>Valido</option>
                <option value="scaduto" {{ request('cert_8h') == 'scaduto' ? 'selected' : '' }}>Scaduto</option>
                <option value="assente" {{ request('cert_8h') == 'assente' ? 'selected' : '' }}>Assente</option>
            </select>
            @if(request()->filled('cert_8h'))
                <span class="clear-filter" data-filter="cert_8h" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>

    {{-- Certificato Preposti --}}
    <div class="col-md-3 mb-3">
        <label for="cert_preposti" class="form-label">
            <i class="fas fa-user-check me-1"></i> Corso Preposti
        </label>
        <div class="select-wrapper">
            <select name="cert_preposti" id="cert_preposti" class="form-select filter-select {{ request()->filled('cert_preposti') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="valido" {{ request('cert_preposti') == 'valido' ? 'selected' : '' }}>Valido</option>
                <option value="scaduto" {{ request('cert_preposti') == 'scaduto' ? 'selected' : '' }}>Scaduto</option>
                <option value="assente" {{ request('cert_preposti') == 'assente' ? 'selected' : '' }}>Assente</option>
            </select>
            @if(request()->filled('cert_preposti'))
                <span class="clear-filter" data-filter="cert_preposti" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
    
    {{-- Certificato Dirigenti --}}
    <div class="col-md-3 mb-3">
        <label for="cert_dirigenti" class="form-label">
            <i class="fas fa-user-tie me-1"></i> Corso Dirigenti
        </label>
        <div class="select-wrapper">
            <select name="cert_dirigenti" id="cert_dirigenti" class="form-select filter-select {{ request()->filled('cert_dirigenti') ? 'applied' : '' }}">
                <option value="">Tutti</option>
                <option value="valido" {{ request('cert_dirigenti') == 'valido' ? 'selected' : '' }}>Valido</option>
                <option value="scaduto" {{ request('cert_dirigenti') == 'scaduto' ? 'selected' : '' }}>Scaduto</option>
                <option value="assente" {{ request('cert_dirigenti') == 'assente' ? 'selected' : '' }}>Assente</option>
            </select>
            @if(request()->filled('cert_dirigenti'))
                <span class="clear-filter" data-filter="cert_dirigenti" title="Rimuovi questo filtro"><i class="fas fa-times"></i></span>
            @endif
        </div>
    </div>
@endcomponent 
