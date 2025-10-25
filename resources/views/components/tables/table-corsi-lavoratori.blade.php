{{--
|--------------------------------------------------------------------------
| Componente Tabella Corsi Lavoratori
|--------------------------------------------------------------------------
| Componente per la visualizzazione della tabella dei corsi lavoratori
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
                <th scope="col">Ruolo</th>
                <th scope="col">Corso 4H</th>
                <th scope="col">Corso 8H</th>
                <th scope="col">Corso Preposti</th>
                <th scope="col">Corso Dirigenti</th>
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
                            {{ $m->ruoloCertificati->nome ?? 'Non specificato' }}
                        </td>
                        
                        @php
                            $cert4h = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_4h')->first();
                            $cert8h = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_8h')->first();
                            $certPreposti = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_preposti')->first();
                            $certDirigenti = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_dirigenti')->first();
                        @endphp
                        
                        <!-- Corso 4H -->
                        <td class="text-center">
                            @if($cert4h)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($cert4h->data_scadenza), false));
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
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert4h->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert4h->data_scadenza)->format('d/m/Y') }}</span>
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
                                            <span class="tooltip-value {{ $cert4h->file_path ? 'positive' : 'negative' }}">
                                                {{ $cert4h->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($cert4h->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $cert4h->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $cert4h->id) }}" 
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
                                        <a href="{{ route('certificati.create', [$m->id, '4h']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Corso 4H">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Corso 8H -->
                        <td class="text-center">
                            @if($cert8h)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($cert8h->data_scadenza), false));
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
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert8h->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert8h->data_scadenza)->format('d/m/Y') }}</span>
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
                                            <span class="tooltip-value {{ $cert8h->file_path ? 'positive' : 'negative' }}">
                                                {{ $cert8h->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($cert8h->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $cert8h->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.edit', $cert8h->id) }}" 
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
                                        <a href="{{ route('certificati.create', [$m->id, '8h']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Corso 8H">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Corso Preposti -->
                        <td class="text-center">
                            @if($m->ruoloCertificati && ($m->ruoloCertificati->nome === 'Preposto' || $m->ruoloCertificati->nome === 'Dirigente'))
                                @if($certPreposti)
                                    <div class="cert-badge">
                                        @php
                                            $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($certPreposti->data_scadenza), false));
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
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certPreposti->created_at)->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Data scadenza:</span>
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certPreposti->data_scadenza)->format('d/m/Y') }}</span>
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
                                                <span class="tooltip-value {{ $certPreposti->file_path ? 'positive' : 'negative' }}">
                                                    {{ $certPreposti->file_path ? 'Caricato' : 'Non caricato' }}
                                                </span>
                                            </div>
                                            @if($certPreposti->file_path)
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Azioni:</span>
                                                <span class="tooltip-actions">
                                                    <a href="{{ route('certificati.edit', $certPreposti->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.edit', $certPreposti->id) }}" 
                                               class="btn btn-warning" 
                                               title="Modifica certificato">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="cert-missing">
                                        <span class="badge bg-danger mb-2">Richiesto</span>
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.create', [$m->id, 'preposti']) }}" 
                                               class="btn btn-success" 
                                               title="Aggiungi Corso Preposti">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Non richiesto</span>
                            @endif
                        </td>
                        
                        <!-- Corso Dirigenti -->
                        <td class="text-center">
                            @if($m->ruoloCertificati && $m->ruoloCertificati->nome === 'Dirigente')
                                @if($certDirigenti)
                                    <div class="cert-badge">
                                        @php
                                            $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($certDirigenti->data_scadenza), false));
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
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certDirigenti->created_at)->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Data scadenza:</span>
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certDirigenti->data_scadenza)->format('d/m/Y') }}</span>
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
                                                <span class="tooltip-value {{ $certDirigenti->file_path ? 'positive' : 'negative' }}">
                                                    {{ $certDirigenti->file_path ? 'Caricato' : 'Non caricato' }}
                                                </span>
                                            </div>
                                            @if($certDirigenti->file_path)
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Azioni:</span>
                                                <span class="tooltip-actions">
                                                    <a href="{{ route('certificati.edit', $certDirigenti->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.edit', $certDirigenti->id) }}" 
                                               class="btn btn-warning" 
                                               title="Modifica certificato">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="cert-missing">
                                        <span class="badge bg-danger mb-2">Richiesto</span>
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.create', [$m->id, 'dirigenti']) }}" 
                                               class="btn btn-success" 
                                               title="Aggiungi Corso Dirigenti">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Non richiesto</span>
                            @endif
                        </td>
                        
                        <!-- Note -->
                        <td>
                            <div class="note-wrapper">
                                <textarea 
                                    class="auto-save-notes" 
                                    data-militare-id="{{ $m->id }}" 
                                    data-field="certificati_note"
                                    data-autosave-url="{{ route('militare.update', $m->id) }}"
                                    data-autosave-field="certificati_note"
                                    placeholder="Inserisci le note sui certificati..." 
                                    aria-label="Note certificati per {{ $m->cognome }} {{ $m->nome }}"
                                >{{ $m->certificati_note ?? '' }}</textarea>
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
                            {{ $m->ruoloCertificati->nome ?? 'Non specificato' }}
                        </td>
                        
                        @php
                            $cert4h = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_4h')->first();
                            $cert8h = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_8h')->first();
                            $certPreposti = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_preposti')->first();
                            $certDirigenti = $m->certificatiLavoratori->where('tipo', 'corsi_lavoratori_dirigenti')->first();
                        @endphp
                        
                        <!-- Corso 4H -->
                        <td class="text-center">
                            @if($cert4h)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($cert4h->data_scadenza), false));
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
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert4h->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert4h->data_scadenza)->format('d/m/Y') }}</span>
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
                                            <span class="tooltip-value {{ $cert4h->file_path ? 'positive' : 'negative' }}">
                                                {{ $cert4h->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($cert4h->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $cert4h->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @if($cert4h)
                                <div class="cert-actions mt-1">
                                    <a href="{{ route('certificati.edit', $cert4h->id) }}" 
                                       class="btn btn-warning" 
                                       title="Modifica certificato">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                </div>
                                @else
                                <div class="cert-actions mt-1">
                                    <a href="{{ route('certificati.create', [$m->id, '4h']) }}" 
                                       class="btn btn-success" 
                                       title="Aggiungi certificato">
                                        <i class="fas fa-upload"></i>
                                    </a>
                                </div>
                                @endif
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, '4h']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Corso 4H">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Corso 8H -->
                        <td class="text-center">
                            @if($cert8h)
                                <div class="cert-badge">
                                    @php
                                        $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($cert8h->data_scadenza), false));
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
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert8h->created_at)->format('d/m/Y') }}</span>
                                        </div>
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Data scadenza:</span>
                                            <span class="tooltip-value">{{ \Carbon\Carbon::parse($cert8h->data_scadenza)->format('d/m/Y') }}</span>
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
                                            <span class="tooltip-value {{ $cert8h->file_path ? 'positive' : 'negative' }}">
                                                {{ $cert8h->file_path ? 'Caricato' : 'Non caricato' }}
                                            </span>
                                        </div>
                                        @if($cert8h->file_path)
                                        <div class="tooltip-row">
                                            <span class="tooltip-label">Azioni:</span>
                                            <span class="tooltip-actions">
                                                <a href="{{ route('certificati.edit', $cert8h->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @if($cert8h)
                                <div class="cert-actions mt-1">
                                    <a href="{{ route('certificati.edit', $cert8h->id) }}" 
                                       class="btn btn-warning" 
                                       title="Modifica certificato">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                </div>
                                @else
                                <div class="cert-actions mt-1">
                                    <a href="{{ route('certificati.create', [$m->id, '8h']) }}" 
                                       class="btn btn-success" 
                                       title="Aggiungi certificato">
                                        <i class="fas fa-upload"></i>
                                    </a>
                                </div>
                                @endif
                            @else
                                <div class="cert-missing">
                                    <span class="badge bg-secondary mb-2">Non presente</span>
                                    <div class="cert-actions">
                                        <a href="{{ route('certificati.create', [$m->id, '8h']) }}" 
                                           class="btn btn-success" 
                                           title="Aggiungi Corso 8H">
                                            <i class="fas fa-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </td>
                        
                        <!-- Corso Preposti -->
                        <td class="text-center">
                            @if($m->ruoloCertificati && ($m->ruoloCertificati->nome === 'Preposto' || $m->ruoloCertificati->nome === 'Dirigente'))
                                @if($certPreposti)
                                    <div class="cert-badge">
                                        @php
                                            $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($certPreposti->data_scadenza), false));
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
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certPreposti->created_at)->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Data scadenza:</span>
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certPreposti->data_scadenza)->format('d/m/Y') }}</span>
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
                                                <span class="tooltip-value {{ $certPreposti->file_path ? 'positive' : 'negative' }}">
                                                    {{ $certPreposti->file_path ? 'Caricato' : 'Non caricato' }}
                                                </span>
                                            </div>
                                            @if($certPreposti->file_path)
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Azioni:</span>
                                                <span class="tooltip-actions">
                                                    <a href="{{ route('certificati.edit', $certPreposti->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.edit', $certPreposti->id) }}" 
                                               class="btn btn-warning" 
                                               title="Modifica certificato">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="cert-missing">
                                        <span class="badge bg-danger mb-2">Richiesto</span>
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.create', [$m->id, 'preposti']) }}" 
                                               class="btn btn-success" 
                                               title="Aggiungi Corso Preposti">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Non richiesto</span>
                            @endif
                        </td>
                        
                        <!-- Corso Dirigenti -->
                        <td class="text-center">
                            @if($m->ruoloCertificati && $m->ruoloCertificati->nome === 'Dirigente')
                                @if($certDirigenti)
                                    <div class="cert-badge">
                                        @php
                                            $giorni_rimanenti = intval(\Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($certDirigenti->data_scadenza), false));
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
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certDirigenti->created_at)->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Data scadenza:</span>
                                                <span class="tooltip-value">{{ \Carbon\Carbon::parse($certDirigenti->data_scadenza)->format('d/m/Y') }}</span>
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
                                                <span class="tooltip-value {{ $certDirigenti->file_path ? 'positive' : 'negative' }}">
                                                    {{ $certDirigenti->file_path ? 'Caricato' : 'Non caricato' }}
                                                </span>
                                            </div>
                                            @if($certDirigenti->file_path)
                                            <div class="tooltip-row">
                                                <span class="tooltip-label">Azioni:</span>
                                                <span class="tooltip-actions">
                                                    <a href="{{ route('certificati.edit', $certDirigenti->id) }}" class="tooltip-action-btn edit" title="Modifica">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                </span>
                                            </div>
                                            @endif
                                        </div>
                                        
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.edit', $certDirigenti->id) }}" 
                                               class="btn btn-warning" 
                                               title="Modifica certificato">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="cert-missing">
                                        <span class="badge bg-danger mb-2">Richiesto</span>
                                        <div class="cert-actions">
                                            <a href="{{ route('certificati.create', [$m->id, 'dirigenti']) }}" 
                                               class="btn btn-success" 
                                               title="Aggiungi Corso Dirigenti">
                                                <i class="fas fa-upload"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="badge bg-secondary">Non richiesto</span>
                            @endif
                        </td>
                        
                        <!-- Note -->
                        <td>
                            <div class="note-wrapper">
                                <textarea 
                                    class="auto-save-notes" 
                                    data-militare-id="{{ $m->id }}" 
                                    data-field="certificati_note"
                                    data-autosave-url="{{ route('militare.update', $m->id) }}"
                                    data-autosave-field="certificati_note"
                                    placeholder="Inserisci le note sui certificati..." 
                                    aria-label="Note certificati per {{ $m->cognome }} {{ $m->nome }}"
                                >{{ $m->certificati_note ?? '' }}</textarea>
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
