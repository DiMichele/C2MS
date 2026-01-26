@extends('layouts.app')

@section('title', 'Gestione Configurazione Anagrafica')

@section('content')
<div class="container-fluid">
    <!-- Header Centrato -->
    <div class="text-center mb-4">
        <h1 class="page-title">Gestione Configurazione Anagrafica</h1>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs justify-content-center mb-4" id="configTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="plotoni-tab" data-bs-toggle="tab" data-bs-target="#plotoni" type="button" role="tab">
                <i class="fas fa-users me-2"></i>Plotoni
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="uffici-tab" data-bs-toggle="tab" data-bs-target="#uffici" type="button" role="tab">
                <i class="fas fa-building me-2"></i>Uffici
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="incarichi-tab" data-bs-toggle="tab" data-bs-target="#incarichi" type="button" role="tab">
                <i class="fas fa-briefcase me-2"></i>Incarichi
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="campi-tab" data-bs-toggle="tab" data-bs-target="#campi" type="button" role="tab">
                <i class="fas fa-columns me-2"></i>Campi
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="configTabsContent">
        
        <!-- TAB PLOTONI -->
        <div class="tab-pane fade show active" id="plotoni" role="tabpanel">
            <div class="table-container-ruolini" style="max-width: 1000px; margin: 0 auto;">
                <table class="sugeco-table">
                    <thead>
                        <tr>
                            <th>Nome Plotone</th>
                            <th>Compagnia</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plotoni as $plotone)
                        <tr>
                            <td><strong>{{ $plotone->nome }}</strong></td>
                            <td>{{ $plotone->compagnia->nome ?? 'N/A' }}</td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-plotone-btn" 
                                        data-id="{{ $plotone->id }}"
                                        data-nome="{{ $plotone->nome }}"
                                        data-compagnia-id="{{ $plotone->compagnia_id }}"
                                        title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-plotone-btn" 
                                        data-id="{{ $plotone->id }}"
                                        data-nome="{{ $plotone->nome }}"
                                        title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                <p class="mb-0">Nessun plotone configurato</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- FAB per Plotoni -->
            <button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createPlotoneModal" title="Nuovo Plotone">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <!-- TAB UFFICI -->
        <div class="tab-pane fade" id="uffici" role="tabpanel">
            <div class="table-container-ruolini" style="max-width: 800px; margin: 0 auto;">
                <table class="sugeco-table">
                    <thead>
                        <tr>
                            <th>Nome Ufficio</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uffici as $ufficio)
                        <tr>
                            <td><strong>{{ $ufficio->nome }}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-ufficio-btn" 
                                        data-id="{{ $ufficio->id }}"
                                        data-nome="{{ $ufficio->nome }}"
                                        title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-ufficio-btn" 
                                        data-id="{{ $ufficio->id }}"
                                        data-nome="{{ $ufficio->nome }}"
                                        title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                <p class="mb-0">Nessun ufficio configurato</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- FAB per Uffici -->
            <button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createUfficioModal" title="Nuovo Ufficio">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <!-- TAB INCARICHI -->
        <div class="tab-pane fade" id="incarichi" role="tabpanel">
            <div class="table-container-ruolini" style="max-width: 800px; margin: 0 auto;">
                <table class="sugeco-table">
                    <thead>
                        <tr>
                            <th>Nome Incarico</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incarichi as $incarico)
                        <tr>
                            <td><strong>{{ $incarico->nome }}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-incarico-btn" 
                                        data-id="{{ $incarico->id }}"
                                        data-nome="{{ $incarico->nome }}"
                                        title="Modifica">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-incarico-btn" 
                                        data-id="{{ $incarico->id }}"
                                        data-nome="{{ $incarico->nome }}"
                                        title="Elimina">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-5">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                <p class="mb-0">Nessun incarico configurato</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- FAB per Incarichi -->
            <button class="fab fab-success" data-bs-toggle="modal" data-bs-target="#createIncaricoModal" title="Nuovo Incarico">
                <i class="fas fa-plus"></i>
            </button>
        </div>

        <!-- TAB CAMPI -->
        <div class="tab-pane fade" id="campi" role="tabpanel">
            @include('gestione-campi-anagrafica.index', ['isTab' => true, 'campi' => $campi])
        </div>

    </div>
</div>

<!-- MODALS PLOTONI -->
@include('gestione-anagrafica-config.modals.plotoni')

<!-- MODALS UFFICI -->
@include('gestione-anagrafica-config.modals.uffici')

<!-- MODALS INCARICHI -->
@include('gestione-anagrafica-config.modals.incarichi')

@endsection

<style>
/* Stili specifici per questa pagina */
/* (Stili base tabelle in table-standard.css) */
</style>

@push('scripts')
<script src="{{ asset('js/gestione-anagrafica-config.js') }}"></script>
@endpush

