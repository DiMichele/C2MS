@extends('layouts.app')

@section('title', 'Registro Attività')

@section('content')
<div class="container-fluid">
    {{-- INTESTAZIONE --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-history text-primary me-2"></i>Registro Attività
            </h1>
            <p class="text-muted mb-0">Cronologia di tutte le operazioni eseguite nel sistema</p>
        </div>
        <div>
            <a href="{{ route('audit-logs.export', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i>Esporta CSV
            </a>
        </div>
    </div>

    {{-- STATISTICHE RAPIDE --}}
    <div class="row mb-4">
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-primary">{{ number_format($stats['today_total']) }}</div>
                    <small class="text-muted">Operazioni oggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-success">{{ number_format($stats['today_logins']) }}</div>
                    <small class="text-muted">Accessi oggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-danger">{{ number_format($stats['today_failed']) }}</div>
                    <small class="text-muted">Accessi falliti</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-info">{{ number_format($stats['week_changes']) }}</div>
                    <small class="text-muted">Modifiche settimana</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-warning">{{ number_format($stats['active_users_today']) }}</div>
                    <small class="text-muted">Utenti attivi oggi</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="h2 mb-1 text-secondary">{{ number_format($logs->total()) }}</div>
                    <small class="text-muted">Totale risultati</small>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTRI --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filtri di Ricerca
                <button class="btn btn-sm btn-link float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse">
                    <i class="fas fa-chevron-down"></i>
                </button>
            </h5>
        </div>
        <div class="collapse show" id="filtersCollapse">
            <div class="card-body">
                <form method="GET" action="{{ route('audit-logs.index') }}">
                    <div class="row g-3">
                        {{-- Ricerca testuale --}}
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-search me-1"></i>Cerca
                            </label>
                            <input type="text" class="form-control" name="search" 
                                   value="{{ request('search') }}" 
                                   placeholder="Cerca per descrizione, utente, IP...">
                        </div>

                        {{-- Utente --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user me-1"></i>Utente
                            </label>
                            <select class="form-select" name="user_id">
                                <option value="">Tutti</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipo azione --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-bolt me-1"></i>Tipo Azione
                            </label>
                            <select class="form-select" name="action">
                                <option value="">Tutte</option>
                                @foreach($actions as $key => $label)
                                    <option value="{{ $key }}" {{ request('action') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Tipo entità --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-cube me-1"></i>Tipo Dato
                            </label>
                            <select class="form-select" name="entity_type">
                                <option value="">Tutti</option>
                                @foreach($entityTypes as $key => $label)
                                    <option value="{{ $key }}" {{ request('entity_type') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Stato --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-check-circle me-1"></i>Stato
                            </label>
                            <select class="form-select" name="status">
                                <option value="">Tutti</option>
                                <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>✓ Successo</option>
                                <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>✗ Fallito</option>
                                <option value="warning" {{ request('status') == 'warning' ? 'selected' : '' }}>⚠ Attenzione</option>
                            </select>
                        </div>

                        {{-- Data da --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar me-1"></i>Da
                            </label>
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
                        </div>

                        {{-- Data a --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar me-1"></i>A
                            </label>
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
                        </div>

                        {{-- Compagnia --}}
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-building me-1"></i>Compagnia
                            </label>
                            <select class="form-select" name="compagnia_id">
                                <option value="">Tutte</option>
                                @foreach($compagnie as $compagnia)
                                    <option value="{{ $compagnia->id }}" {{ request('compagnia_id') == $compagnia->id ? 'selected' : '' }}>
                                        {{ $compagnia->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Pulsanti --}}
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Filtra
                            </button>
                            <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- TABELLA LOG --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 160px;">Data/Ora</th>
                            <th style="width: 150px;">Utente</th>
                            <th style="width: 120px;">Azione</th>
                            <th>Descrizione</th>
                            <th style="width: 100px;">Dato</th>
                            <th style="width: 80px;">Stato</th>
                            <th style="width: 100px;">IP</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="{{ $log->status === 'failed' ? 'table-danger' : ($log->status === 'warning' ? 'table-warning' : '') }}">
                                <td>
                                    <div class="fw-semibold">{{ $log->created_at->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    @if($log->user)
                                        <a href="{{ route('audit-logs.user', $log->user) }}" class="text-decoration-none">
                                            {{ $log->user_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">{{ $log->user_name ?? 'Sistema' }}</span>
                                    @endif
                                    @if($log->compagnia)
                                        <br><small class="text-muted">{{ $log->compagnia->nome }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->action_color }} d-inline-flex align-items-center">
                                        <i class="fas {{ $log->action_icon }} me-1"></i>
                                        {{ $log->action_label }}
                                    </span>
                                </td>
                                <td>
                                    <span title="{{ $log->description }}">
                                        {{ Str::limit($log->description, 60) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->entity_type)
                                        <span class="badge bg-light text-dark">
                                            {{ $log->entity_label }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->status === 'success')
                                        <span class="badge bg-success">OK</span>
                                    @elseif($log->status === 'failed')
                                        <span class="badge bg-danger">Fallito</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Attenzione</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted font-monospace">{{ $log->ip_address ?? '-' }}</small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#logDetailModal{{ $log->id }}"
                                            title="Dettagli">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">Nessun log trovato con i filtri selezionati</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINAZIONE --}}
        @if($logs->hasPages())
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Mostrati {{ $logs->firstItem() }}-{{ $logs->lastItem() }} di {{ number_format($logs->total()) }} risultati
                    </small>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- MODALI DETTAGLIO --}}
@foreach($logs as $log)
<div class="modal fade" id="logDetailModal{{ $log->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas {{ $log->action_icon }} text-{{ $log->action_color }} me-2"></i>
                    Dettaglio Operazione
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">DATA E ORA</label>
                        <p class="mb-0">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">UTENTE</label>
                        <p class="mb-0">{{ $log->user_name ?? 'Sistema' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">AZIONE</label>
                        <p class="mb-0">
                            <span class="badge bg-{{ $log->action_color }}">{{ $log->action_label }}</span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">STATO</label>
                        <p class="mb-0">
                            @if($log->status === 'success')
                                <span class="badge bg-success">Completato con successo</span>
                            @elseif($log->status === 'failed')
                                <span class="badge bg-danger">Operazione fallita</span>
                            @else
                                <span class="badge bg-warning text-dark">Completato con avvisi</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold text-muted small">DESCRIZIONE</label>
                        <p class="mb-0">{{ $log->description }}</p>
                    </div>
                    @if($log->entity_type)
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small">TIPO DATO</label>
                            <p class="mb-0">{{ $log->entity_label }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-muted small">NOME</label>
                            <p class="mb-0">{{ $log->entity_name ?? 'ID: ' . $log->entity_id }}</p>
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">INDIRIZZO IP</label>
                        <p class="mb-0 font-monospace">{{ $log->ip_address ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold text-muted small">PAGINA</label>
                        <p class="mb-0 small">{{ $log->url ?? '-' }}</p>
                    </div>
                    @if($log->old_values || $log->new_values)
                        <div class="col-12">
                            <hr>
                            <label class="form-label fw-semibold text-muted small">DETTAGLI MODIFICA</label>
                            <div class="row">
                                @if($log->old_values)
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header py-2 bg-danger text-white">
                                                <small class="fw-semibold">Valori Precedenti</small>
                                            </div>
                                            <div class="card-body py-2">
                                                <pre class="mb-0 small" style="max-height: 200px; overflow: auto;">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($log->new_values)
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-header py-2 bg-success text-white">
                                                <small class="fw-semibold">Valori Nuovi</small>
                                            </div>
                                            <div class="card-body py-2">
                                                <pre class="mb-0 small" style="max-height: 200px; overflow: auto;">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<style>
.badge.bg-purple {
    background-color: #6f42c1 !important;
    color: white !important;
}
.table > tbody > tr > td {
    vertical-align: middle;
}
</style>
@endsection
