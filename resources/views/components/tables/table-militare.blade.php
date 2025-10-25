{{--
|--------------------------------------------------------------------------
| Componente Tabella Militare 
|--------------------------------------------------------------------------
| Componente per la visualizzazione della tabella dei militari
| Parametri:
| - $militari: Collection di militari da visualizzare
|
| @version 1.0
| @author Michele Di Gennaro
--}}

<div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead>
            <tr>
                <th scope="col" style="width: 50px" class="text-center">#</th>
                <th scope="col">Grado</th>
                <th scope="col">Nominativo</th>
                <th scope="col">Ruolo</th>
                <th scope="col">Status</th>
                <th scope="col" style="width: 120px">Azioni</th>
            </tr>
        </thead>
        <tbody id="militariTableBody">
            @forelse($militari as $index => $militare)
                <tr id="militare-{{ $militare->id }}" class="militare-row" data-militare-id="{{ $militare->id }}">
                    <td class="text-center">
                        {{ ($militari->currentPage() - 1) * $militari->perPage() + $index + 1 }}
                    </td>
                    <td>
                        <span class="grado-badge" style="background-color: {{ $militare->grado?->colore ?? '#ccc' }}">
                            {{ $militare->grado?->nome ?? 'N/A' }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('anagrafica.show', $militare->id) }}" class="link-name">
                            {{ $militare->cognome }} {{ $militare->nome }}
                        </a>
                    </td>
                    <td>
                        {{ $militare->ruolo?->nome ?? 'Non specificato' }}
                    </td>
                    <td>
                        <span class="status-badge {{ $militare->status == 'attivo' ? 'active' : 'inactive' }}">
                            {{ $militare->status == 'attivo' ? 'Attivo' : 'Inattivo' }}
                        </span>
                    </td>
                    <td>
                        <div class="d-flex justify-content-center action-buttons">
                            <a href="{{ route('anagrafica.show', $militare->id) }}" class="action-btn" data-tooltip="Visualizza" aria-label="Visualizza dettagli">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('militare.edit', $militare->id) }}" class="action-btn edit" data-tooltip="Modifica" aria-label="Modifica">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('militare.destroy', $militare->id) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="action-btn delete" data-tooltip="Elimina" aria-label="Elimina" data-toggle="modal" data-target="#deleteModal" data-id="{{ $militare->id }}" data-name="{{ $militare->cognome }} {{ $militare->nome }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center empty-state">
                            <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                            <p class="lead mb-3">Nessun militare trovato con i filtri selezionati.</p>
                            <a href="{{ route('anagrafica.index') }}" class="btn btn-outline-primary mt-2">
                                <i class="fas fa-times-circle me-1"></i> Rimuovi tutti i filtri
                            </a>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (method_exists($militari, 'hasPages') && $militari->hasPages())
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        Mostrando {{ $militari->firstItem() ?? 0 }}-{{ $militari->lastItem() ?? 0 }} di {{ $militari->total() }} risultati
    </div>
    <div>
        {{ $militari->links() }}
    </div>
</div>
@endif 
