{{--
|--------------------------------------------------------------------------
| Componente Tabella Idoneità
|--------------------------------------------------------------------------
| Componente per la visualizzazione della tabella delle idoneità
| Parametri:
| - $militari: Collection di militari da visualizzare
|
| @version 1.0
| @author Michele Di Gennaro
--}}

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered align-middle">
        <thead>
            <tr>
                <th scope="col">Grado</th>
                <th scope="col">Nominativo</th>
                <th scope="col">Mansione</th>
                <th scope="col">Idoneità di Mansione</th>
                <th scope="col">Idoneità SMI</th>
                <th scope="col">Idoneità</th>
                <th scope="col">PEFO</th>
                <th scope="col">Note</th>
            </tr>
        </thead>
        <tbody id="certificatiTableBody">
            @if(isset($militari))
                @forelse($militari as $m)
                    <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}">
                        <td>
                            {{ $m->grado->nome ?? 'N/A' }}
                        </td>
                        <td>
                            <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                                {{ $m->cognome }} {{ $m->nome }}
                            </a>
                        </td>
                        <td>
                            {{ $m->mansione->nome ?? 'Non specificata' }}
                        </td>
                        
                        @php
                            $idoneita_mansione = $m->idoneita->where('tipo', 'idoneita_mansione')->first();
                            $idoneita_smi = $m->idoneita->where('tipo', 'idoneita_smi')->first();
                            $idoneita = $m->idoneita->where('tipo', 'idoneita')->first();
                            $pefo = $m->idoneita->where('tipo', 'pefo')->first();
                        @endphp
                        
                        <!-- Idoneità di Mansione -->
                        <td class="text-center">
                            @if($idoneita_mansione)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita_mansione->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_mansione->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_mansione->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita_mansione->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita_mansione->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($idoneita_mansione->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $idoneita_mansione->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $idoneita_mansione->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'mansione']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Idoneità Mansione">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Idoneità SMI -->
                        <td class="text-center">
                            @if($idoneita_smi)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita_smi->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_smi->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_smi->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita_smi->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita_smi->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($idoneita_smi->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $idoneita_smi->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $idoneita_smi->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'smi']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Idoneità SMI">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Idoneità -->
                        <td class="text-center">
                            @if($idoneita)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($idoneita->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $idoneita->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $idoneita->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'idoneita']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi certificato">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- PEFO -->
                        <td class="text-center">
                            @if($pefo)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($pefo->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($pefo->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($pefo->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $pefo->file_path ? 'positive' : 'negative' }}">
                                                {{ $pefo->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($pefo->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $pefo->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $pefo->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'pefo']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi certificato">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Note -->
                        <td>
                            <div class="note-wrapper">
                                <textarea 
                                    class="auto-save-notes" 
                                    data-militare-id="{{ $m->id }}" 
                                    data-field="idoneita_note"
                                    data-autosave-url="{{ route('militare.update', $m->id) }}"
                                    data-autosave-field="idoneita_note"
                                    placeholder="Inserisci le note sull'idoneità..." 
                                    aria-label="Note idoneità per {{ $m->cognome }} {{ $m->nome }}"
                                >{{ $m->idoneita_note ?? '' }}</textarea>
                                <div class="save-indicator d-none" data-tooltip="Salvataggio in corso"><i class="fas fa-sync-alt fa-spin"></i></div>
                                <div class="save-status-text d-none"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <p class="mb-0 text-muted">Nessun militare trovato con i filtri applicati.</p>
                        </td>
                    </tr>
                @endforelse
            @elseif(isset($militare))
                @forelse($militare as $m)
                    <tr id="militare-{{ $m->id }}" class="militare-row" data-militare-id="{{ $m->id }}">
                        <td>
                            {{ $m->grado->nome ?? 'N/A' }}
                        </td>
                        <td>
                            <a href="{{ route('anagrafica.show', $m->id) }}" class="link-name">
                                {{ $m->cognome }} {{ $m->nome }}
                            </a>
                        </td>
                        <td>
                            {{ $m->mansione->nome ?? 'Non specificata' }}
                        </td>
                        
                        @php
                            $idoneita_mansione = $m->certificatiIdoneita->where('tipo', 'idoneita_mansione')->first();
                            $idoneita_smi = $m->certificatiIdoneita->where('tipo', 'idoneita_smi')->first();
                            $idoneita = $m->certificatiIdoneita->where('tipo', 'idoneita')->first();
                            $pefo = $m->certificatiIdoneita->where('tipo', 'pefo')->first();
                        @endphp
                        
                        <!-- Idoneità di Mansione -->
                        <td class="text-center">
                            @if($idoneita_mansione)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita_mansione->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_mansione->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_mansione->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita_mansione->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita_mansione->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($idoneita_mansione->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $idoneita_mansione->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $idoneita_mansione->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'mansione']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi certificato">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Idoneità SMI -->
                        <td class="text-center">
                            @if($idoneita_smi)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita_smi->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_smi->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita_smi->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita_smi->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita_smi->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($idoneita_smi->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $idoneita_smi->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $idoneita_smi->id) }}" 
                                           class="btn btn-warning" 
                                           title="Modifica certificato">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, 'smi']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi certificato">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Idoneità -->
                        <td class="text-center">
                            @if($idoneita)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($idoneita->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($idoneita->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $idoneita->file_path ? 'positive' : 'negative' }}">
                                                {{ $idoneita->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-secondary">Non presente</span>
                            @endif
                        </td>
                        
                        <!-- PEFO -->
                        <td class="text-center">
                            @if($pefo)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($pefo->data_scadenza), false));
                                        $is_in_scadenza = $giorni_rimanenti <= 30 && $giorni_rimanenti >= 0;
                                        $is_scaduto = $giorni_rimanenti < 0;
                                    @endphp
                                    
                                    @if($is_scaduto)
                                        <span class="badge bg-danger">Scaduto</span>
                                    @elseif($is_in_scadenza)
                                        <span class="badge bg-warning">In Scadenza</span>
                                    @else
                                        <span class="badge bg-success">Attivo</span>
                                    @endif
                                    
                                    <div class="cert-tooltip">
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Stato:</span>
                                            <span class="tooltip-value {{ !$is_scaduto ? ($is_in_scadenza ? 'warning' : 'positive') : 'negative' }}">
                                                {{ $is_scaduto ? 'Scaduto' : ($is_in_scadenza ? 'In Scadenza' : 'Attivo') }}
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data ottenimento:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($pefo->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($pefo->data_scadenza)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Giorni rimanenti:</span>
                                            <span class="tooltip-value {{ $giorni_rimanenti > 30 ? 'positive' : ($giorni_rimanenti >= 0 ? 'warning' : 'negative') }}">
                                                @if($giorni_rimanenti < 0)
                                                    Scaduto da {{ abs($giorni_rimanenti) }} giorni
                                                @else
                                                    {{ $giorni_rimanenti }} giorni
                                                @endif
                                            </span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">File:</span>
                                            <span class="tooltip-value {{ $pefo->file_path ? 'positive' : 'negative' }}">
                                                {{ $pefo->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-secondary">Non presente</span>
                            @endif
                        </td>
                        
                        <!-- Note -->
                        <td>
                            <div class="note-wrapper">
                                <textarea 
                                    class="auto-save-notes" 
                                    data-militare-id="{{ $m->id }}" 
                                    data-field="idoneita_note"
                                    data-autosave-url="{{ route('militare.update', $m->id) }}"
                                    data-autosave-field="idoneita_note"
                                    placeholder="Inserisci le note sull'idoneità..." 
                                    aria-label="Note idoneità per {{ $m->cognome }} {{ $m->nome }}"
                                >{{ $m->idoneita_note ?? '' }}</textarea>
                                <div class="save-indicator d-none" data-tooltip="Salvataggio in corso"><i class="fas fa-sync-alt fa-spin"></i></div>
                                <div class="save-status-text d-none"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <p class="mb-0 text-muted">Nessun militare trovato con i filtri applicati.</p>
                        </td>
                    </tr>
                @endforelse
            @else
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <p class="mb-0 text-muted">Nessun dato disponibile.</p>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div> 
