{{--
|--------------------------------------------------------------------------
| Sezione Dati Anagrafici - Design Minimalista
|--------------------------------------------------------------------------
| @version 3.0
--}}

<div class="dettaglio-sezione">
    <div class="sezione-header" data-bs-toggle="collapse" data-bs-target="#collapse-anagrafica" aria-expanded="true">
        <div class="sezione-titolo">Dati Anagrafici</div>
        <div class="sezione-badge-group">
            <span class="sezione-chevron"></span>
        </div>
    </div>
    
    <div id="collapse-anagrafica" class="collapse show sezione-content">
        <div class="row">
            <div class="col-md-6">
                <table class="tabella-dati">
                    <tr>
                        <td class="td-label">Grado</td>
                        <td class="td-value">{{ $militare->grado->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Categoria</td>
                        <td class="td-value">{{ $militare->categoria ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Matricola</td>
                        <td class="td-value">{{ $militare->numero_matricola ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Data di nascita</td>
                        <td class="td-value">{{ $militare->data_nascita ? $militare->data_nascita->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Luogo di nascita</td>
                        <td class="td-value">
                            {{ $militare->luogo_nascita ?? '-' }}
                            @if($militare->provincia_nascita)
                                ({{ $militare->provincia_nascita }})
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="td-label">Codice Fiscale</td>
                        <td class="td-value" style="font-family: monospace;">{{ $militare->codice_fiscale ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Sesso</td>
                        <td class="td-value">{{ $militare->sesso ?? '-' }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="tabella-dati">
                    <tr>
                        <td class="td-label">Compagnia</td>
                        <td class="td-value">{{ $militare->compagnia->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Plotone</td>
                        <td class="td-value">{{ $militare->plotone->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Ufficio</td>
                        <td class="td-value">{{ $militare->polo->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Incarico</td>
                        <td class="td-value">{{ $militare->mansione->nome ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Anzianità</td>
                        <td class="td-value">{{ $militare->anzianita ? $militare->anzianita->format('d/m/Y') : '-' }}</td>
                    </tr>
                    <tr>
                        <td class="td-label">Email</td>
                        <td class="td-value">
                            @if($militare->email)
                                <a href="mailto:{{ $militare->email }}">{{ $militare->email }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="td-label">Telefono</td>
                        <td class="td-value">
                            @if($militare->telefono)
                                <a href="tel:{{ $militare->telefono }}">{{ $militare->telefono }}</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        @if($militare->nos_status || $militare->note)
        <hr class="my-3">
        <div class="row">
            @if($militare->nos_status)
            <div class="col-md-6">
                <table class="tabella-dati">
                    <tr>
                        <td class="td-label">NOS Status</td>
                        <td class="td-value">
                            <span class="badge bg-{{ $militare->nos_status == 'attivo' ? 'success' : 'secondary' }}">
                                {{ ucfirst($militare->nos_status) }}
                            </span>
                        </td>
                    </tr>
                    {{-- Campo nos_scadenza rimosso dalla tabella militari --}}
                </table>
            </div>
            @endif
            @if($militare->note)
            <div class="col-md-6">
                <table class="tabella-dati">
                    <tr>
                        <td class="td-label">Note</td>
                        <td class="td-value">{{ $militare->note }}</td>
                    </tr>
                </table>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
