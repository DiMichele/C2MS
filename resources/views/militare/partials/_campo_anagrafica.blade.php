@php
    // Helper per ottenere il valore del campo
    $valore = null;
    $militare = $militare ?? null;
    $campo = $campo ?? null;
    $gradi = $gradi ?? collect();
    $plotoni = $plotoni ?? collect();
    $poli = $poli ?? collect();
    
    if ($militare && $campo) {
        // Per campi speciali, usa i dati diretti del militare
        switch($campo->nome_campo) {
            case 'compagnia':
                $valore = $militare->compagnia_id;
                break;
            case 'grado':
                $valore = $militare->grado_id;
                break;
            case 'cognome':
                $valore = $militare->cognome;
                break;
            case 'nome':
                $valore = $militare->nome;
                break;
            case 'plotone':
                $valore = $militare->plotone_id;
                break;
            case 'ufficio':
                $valore = $militare->polo_id;
                break;
            case 'incarico':
                $valore = $militare->mansione_id;
                break;
            case 'nos':
                $valore = $militare->nos_status;
                break;
            case 'anzianita':
                $valore = $militare->anzianita ? $militare->anzianita->format('Y-m-d') : '';
                break;
            case 'data_nascita':
                $valore = $militare->data_nascita ? $militare->data_nascita->format('Y-m-d') : '';
                break;
            case 'email_istituzionale':
                $valore = $militare->email_istituzionale;
                break;
            case 'telefono':
                $valore = $militare->telefono;
                break;
            case 'codice_fiscale':
                $valore = $militare->codice_fiscale;
                break;
            default:
                // Per campi custom, usa il metodo getValoreCampoCustom
                $valore = $militare->getValoreCampoCustom($campo->nome_campo);
                break;
        }
    }
@endphp

@switch($campo->nome_campo)
    @case('compagnia')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field compagnia-select" 
                    data-field="compagnia" 
                    data-militare-id="{{ $militare->id }}" 
                    data-row-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                @php
                    // Usa le compagnie dal database se disponibili, altrimenti usa quelle di default
                    $compagnie = $compagnie ?? collect();
                    if ($compagnie->isEmpty()) {
                        // Fallback: crea una collection vuota se non è stata passata
                        $compagnie = \App\Models\Compagnia::orderBy('nome')->get();
                    }
                @endphp
                @foreach($compagnie as $compagnia)
                    <option value="{{ $compagnia->id }}" {{ $valore == $compagnia->id ? 'selected' : '' }}>
                        {{ $compagnia->numero ?? $compagnia->nome }}
                    </option>
                @endforeach
            </select>
        </td>
        @break
    
    @case('grado')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field" 
                    data-field="grado_id" 
                    data-militare-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                @foreach($gradi as $grado)
                    <option value="{{ $grado->id }}" {{ $valore == $grado->id ? 'selected' : '' }}>
                        {{ $grado->abbreviazione ?? $grado->nome }}
                    </option>
                @endforeach
            </select>
        </td>
        @break
    
    @case('cognome')
        <td>
            <a href="{{ route('anagrafica.show', $militare->id) }}" class="link-name">
                {{ $valore }}
            </a>
        </td>
        @break
    
    @case('nome')
        <td>
            <a href="{{ route('anagrafica.show', $militare->id) }}" class="link-name">
                {{ $valore }}
            </a>
        </td>
        @break
    
    @case('plotone')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field plotone-select" 
                    data-field="plotone_id" 
                    data-militare-id="{{ $militare->id }}" 
                    data-row-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                @foreach($plotoni as $plotone)
                    <option value="{{ $plotone->id }}" 
                            data-compagnia-id="{{ $plotone->compagnia_id }}" 
                            {{ $valore == $plotone->id ? 'selected' : '' }}>
                        {{ $plotone->nome }}
                    </option>
                @endforeach
            </select>
        </td>
        @break
    
    @case('ufficio')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field" 
                    data-field="polo_id" 
                    data-militare-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                @foreach($poli as $polo)
                    <option value="{{ $polo->id }}" {{ $valore == $polo->id ? 'selected' : '' }}>
                        {{ $polo->nome }}
                    </option>
                @endforeach
            </select>
        </td>
        @break
    
    @case('incarico')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field" 
                    data-field="mansione_id" 
                    data-militare-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                @foreach(\App\Models\Mansione::all() as $mansione)
                    <option value="{{ $mansione->id }}" {{ $valore == $mansione->id ? 'selected' : '' }}>
                        {{ $mansione->nome }}
                    </option>
                @endforeach
            </select>
        </td>
        @break
    
    @case('patenti')
        <td class="text-center">
            <div class="patenti-container">
                @php
                    $patentiMilitare = $militare->patenti->pluck('categoria')->toArray();
                @endphp
                <div class="patenti-row">
                    @foreach(['2', '3'] as $patente)
                        <div class="form-check form-check-inline mb-0">
                            <input type="checkbox" 
                                   class="form-check-input patente-checkbox" 
                                   id="patente_{{ $militare->id }}_{{ $patente }}"
                                   data-militare-id="{{ $militare->id }}" 
                                   data-patente="{{ $patente }}" 
                                   {{ in_array($patente, $patentiMilitare) ? 'checked' : '' }}
                                   {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}
                                   style="cursor: pointer;">
                            <label class="form-check-label" 
                                   for="patente_{{ $militare->id }}_{{ $patente }}" 
                                   style="cursor: pointer;">
                                {{ $patente }}
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="patenti-row">
                    @foreach(['4', '5', '6'] as $patente)
                        <div class="form-check form-check-inline mb-0">
                            <input type="checkbox" 
                                   class="form-check-input patente-checkbox" 
                                   id="patente_{{ $militare->id }}_{{ $patente }}"
                                   data-militare-id="{{ $militare->id }}" 
                                   data-patente="{{ $patente }}" 
                                   {{ in_array($patente, $patentiMilitare) ? 'checked' : '' }}
                                   {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}
                                   style="cursor: pointer;">
                            <label class="form-check-label" 
                                   for="patente_{{ $militare->id }}_{{ $patente }}" 
                                   style="cursor: pointer;">
                                {{ $patente }}
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </td>
        @break
    
    @case('nos')
        <td class="text-center">
            <select class="form-select form-select-sm editable-field" 
                    data-field="nos_status" 
                    data-militare-id="{{ $militare->id }}" 
                    style="width: 100%;" 
                    {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                <option value="">--</option>
                <option value="si" {{ $valore == 'si' ? 'selected' : '' }}>SI</option>
                <option value="no" {{ $valore == 'no' ? 'selected' : '' }}>NO</option>
                <option value="da richiedere" {{ $valore == 'da richiedere' ? 'selected' : '' }}>Da Richiedere</option>
                <option value="non previsto" {{ $valore == 'non previsto' ? 'selected' : '' }}>Non Previsto</option>
                <option value="in attesa" {{ $valore == 'in attesa' ? 'selected' : '' }}>In Attesa</option>
            </select>
        </td>
        @break
    
    @case('istituti')
        {{-- Se il campo istituti è di tipo checkbox, usa il sistema generico --}}
        @if($campo->tipo_campo === 'checkbox')
            @php
                $valore = $militare->getValoreCampoCustom($campo->nome_campo);
            @endphp
            <td class="text-center">
                @if($campo->opzioni && count($campo->opzioni) > 0)
                    {{-- Checkbox multipli con opzioni dal database --}}
                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                        @php
                            $valoriSelezionati = is_array($valore) ? $valore : ($valore ? explode(',', $valore) : []);
                        @endphp
                        @foreach($campo->opzioni as $opzione)
                            <div class="form-check">
                                <input type="checkbox"
                                       class="form-check-input campo-custom-field"
                                       data-militare-id="{{ $militare->id }}"
                                       data-campo-nome="{{ $campo->nome_campo }}"
                                       data-opzione="{{ $opzione }}"
                                       value="{{ $opzione }}"
                                       {{ in_array($opzione, $valoriSelezionati) ? 'checked' : '' }}
                                       {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                                <label class="form-check-label" style="font-size: 0.85rem;">
                                    {{ $opzione }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Checkbox singolo --}}
                    <div class="form-check d-flex justify-content-center mb-0">
                        <input type="checkbox"
                               class="form-check-input campo-custom-field"
                               data-militare-id="{{ $militare->id }}"
                               data-campo-nome="{{ $campo->nome_campo }}"
                               {{ $valore ? 'checked' : '' }}
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    </div>
                @endif
            </td>
        @else
            {{-- Comportamento legacy per istituti di tipo text --}}
            <td class="text-center">
                @php
                    $istituti = $militare->istituti ?? [];
                    // Usa le opzioni dal database se disponibili, altrimenti usa quelle di default
                    $istitutiOptions = ($campo->opzioni && count($campo->opzioni) > 0) ? $campo->opzioni : ['104', 'Orario flessibile', 'Esente alzabandiera'];
                @endphp
                <div class="istituti-container" data-militare-id="{{ $militare->id }}" style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
                    @foreach($istitutiOptions as $istituto)
                        <label class="istituto-checkbox" style="display: flex; align-items: center; gap: 4px; font-size: 0.75rem; cursor: pointer; white-space: nowrap;">
                            <input type="checkbox" 
                                   class="istituto-input" 
                                   value="{{ $istituto }}" 
                                   data-militare-id="{{ $militare->id }}"
                                   {{ in_array($istituto, $istituti) ? 'checked' : '' }}
                                   {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}
                                   style="cursor: pointer;">
                            <span>{{ $istituto }}</span>
                        </label>
                    @endforeach
                </div>
            </td>
        @endif
        @break
    
    @default
        {{-- Campi generici basati sul tipo_campo --}}
        <td class="text-center">
            @switch($campo->tipo_campo)
                @case('select')
                    <select class="form-select form-select-sm campo-custom-field" 
                            data-militare-id="{{ $militare->id }}"
                            data-campo-nome="{{ $campo->nome_campo }}"
                            {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                        <option value="">--</option>
                        @if($campo->opzioni)
                            @foreach($campo->opzioni as $opzione)
                                <option value="{{ $opzione }}" {{ $valore == $opzione ? 'selected' : '' }}>
                                    {{ $opzione }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    @break
                
                
                @case('checkbox')
                    @if($campo->opzioni && count($campo->opzioni) > 0)
                        {{-- Checkbox multipli con opzioni --}}
                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                            @php
                                $valoriSelezionati = is_array($valore) ? $valore : ($valore ? explode(',', $valore) : []);
                            @endphp
                            @foreach($campo->opzioni as $opzione)
                                <div class="form-check">
                                    <input type="checkbox" 
                                           class="form-check-input campo-custom-field"
                                           data-militare-id="{{ $militare->id }}"
                                           data-campo-nome="{{ $campo->nome_campo }}"
                                           data-opzione="{{ $opzione }}"
                                           value="{{ $opzione }}"
                                           {{ in_array($opzione, $valoriSelezionati) ? 'checked' : '' }}
                                           {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                                    <label class="form-check-label" style="font-size: 0.85rem;">
                                        {{ $opzione }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    @else
                        {{-- Checkbox singolo --}}
                        <div class="form-check d-flex justify-content-center mb-0">
                            <input type="checkbox" 
                                   class="form-check-input campo-custom-field"
                                   data-militare-id="{{ $militare->id }}"
                                   data-campo-nome="{{ $campo->nome_campo }}"
                                   {{ $valore ? 'checked' : '' }}
                                   {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                        </div>
                    @endif
                    @break
                
                @case('textarea')
                    <textarea class="form-control form-control-sm campo-custom-field"
                              data-militare-id="{{ $militare->id }}"
                              data-campo-nome="{{ $campo->nome_campo }}"
                              rows="2"
                              {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>{{ $valore }}</textarea>
                    @break
                
                @case('email')
                    @if(in_array($campo->nome_campo, ['email_istituzionale']))
                        <input type="email" 
                               class="form-control form-control-sm editable-field"
                               data-field="{{ $campo->nome_campo }}"
                               data-militare-id="{{ $militare->id }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @else
                        <input type="email" 
                               class="form-control form-control-sm campo-custom-field"
                               data-militare-id="{{ $militare->id }}"
                               data-campo-nome="{{ $campo->nome_campo }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @endif
                    @break
                
                @case('tel')
                    @if(in_array($campo->nome_campo, ['telefono']))
                        <input type="tel" 
                               class="form-control form-control-sm editable-field"
                               data-field="{{ $campo->nome_campo }}"
                               data-militare-id="{{ $militare->id }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @else
                        <input type="tel" 
                               class="form-control form-control-sm campo-custom-field"
                               data-militare-id="{{ $militare->id }}"
                               data-campo-nome="{{ $campo->nome_campo }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @endif
                    @break
                
                @case('date')
                    @if(in_array($campo->nome_campo, ['anzianita', 'data_nascita']))
                        <input type="date" 
                               class="form-control form-control-sm editable-field"
                               data-field="{{ $campo->nome_campo }}"
                               data-militare-id="{{ $militare->id }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @else
                        <input type="date" 
                               class="form-control form-control-sm campo-custom-field"
                               data-militare-id="{{ $militare->id }}"
                               data-campo-nome="{{ $campo->nome_campo }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @endif
                    @break
                
                @case('number')
                    <input type="number" 
                           class="form-control form-control-sm campo-custom-field"
                           data-militare-id="{{ $militare->id }}"
                           data-campo-nome="{{ $campo->nome_campo }}"
                           value="{{ $valore }}"
                           {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @break
                
                @default
                    {{-- text o altri tipi --}}
                    @if($campo->nome_campo == 'codice_fiscale')
                        <strong>{{ $valore ?? 'N/A' }}</strong>
                    @else
                        <input type="{{ $campo->tipo_campo }}" 
                               class="form-control form-control-sm campo-custom-field"
                               data-militare-id="{{ $militare->id }}"
                               data-campo-nome="{{ $campo->nome_campo }}"
                               value="{{ $valore }}"
                               {{ auth()->check() && auth()->user()->hasPermission('anagrafica.edit') ? '' : 'disabled' }}>
                    @endif
            @endswitch
        </td>
@endswitch

