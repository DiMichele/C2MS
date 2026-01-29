{{--
    Componente per la selezione dei militari con verifica disponibilità automatica
    
    Parametri:
    - $id: identificatore univoco del componente (es: 'turni', 'board')
    - $showPlotone: mostrare colonna plotone (default: true)
    - $multiSelect: permetti selezione multipla (default: true)
    - $maxHeight: altezza massima tabelle (default: '350px')
    
    Uso:
    @include('components.selettore-militari-disponibilita', [
        'id' => 'turni',
        'showPlotone' => true,
        'multiSelect' => true
    ])
    
    JavaScript necessario nel chiamante:
    - caricaMilitariDisponibilita(data, excludeActivityId) deve essere implementata
    - Ascolta l'evento 'militariSelezionatiChange' per aggiornare lo stato dei pulsanti
--}}

@php
    $componentId = $id ?? 'default';
    $showPlotone = $showPlotone ?? true;
    $multiSelect = $multiSelect ?? true;
    $maxHeight = $maxHeight ?? '350px';
@endphp

<div id="selettoreMilitari_{{ $componentId }}" class="selettore-militari-disponibilita">
    <!-- Stato caricamento -->
    <div id="loadingMilitari_{{ $componentId }}" class="text-center py-4 d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Caricamento...</span>
        </div>
        <p class="text-muted mt-2 mb-0">Verifica disponibilità in corso...</p>
    </div>

    <!-- Messaggio iniziale -->
    <div id="messaggioIniziale_{{ $componentId }}" class="alert alert-info mb-0">
        <i class="fas fa-info-circle me-2"></i>
        Seleziona una data per visualizzare la disponibilità dei militari.
    </div>

    <!-- Contenuto tabs -->
    <div id="contenutoTabs_{{ $componentId }}" class="d-none">
        <!-- Tabs Disponibili/Non Disponibili -->
        <ul class="nav nav-tabs mb-3" id="tabMilitari_{{ $componentId }}" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-disponibili-{{ $componentId }}" data-bs-toggle="tab" 
                        data-bs-target="#panel-disponibili-{{ $componentId }}" type="button" role="tab">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    Disponibili <span class="badge bg-success ms-1" id="countDisponibili_{{ $componentId }}">0</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-non-disponibili-{{ $componentId }}" data-bs-toggle="tab" 
                        data-bs-target="#panel-non-disponibili-{{ $componentId }}" type="button" role="tab">
                    <i class="fas fa-times-circle text-danger me-1"></i>
                    Non Disponibili <span class="badge bg-danger ms-1" id="countNonDisponibili_{{ $componentId }}">0</span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Panel Disponibili -->
            <div class="tab-pane fade show active" id="panel-disponibili-{{ $componentId }}" role="tabpanel">
                @if($multiSelect)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <input type="checkbox" class="form-check-input me-2" id="selectAllDisponibili_{{ $componentId }}" checked>
                        <label class="form-check-label" for="selectAllDisponibili_{{ $componentId }}">Seleziona tutti</label>
                    </div>
                    <small class="text-muted" id="contatoreSelezionati_{{ $componentId }}">0 selezionati</small>
                </div>
                @endif
                <div class="table-responsive" style="max-height: {{ $maxHeight }};">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                @if($multiSelect)<th width="40"></th>@endif
                                <th>Grado</th>
                                <th>Cognome</th>
                                <th>Nome</th>
                                @if($showPlotone)<th>Plotone</th>@endif
                            </tr>
                        </thead>
                        <tbody id="tabellaDisponibili_{{ $componentId }}">
                            <tr>
                                <td colspan="{{ ($multiSelect ? 1 : 0) + 3 + ($showPlotone ? 1 : 0) }}" class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-2x mb-2 d-block opacity-50"></i>
                                    Carica i dati selezionando una data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Panel Non Disponibili -->
            <div class="tab-pane fade" id="panel-non-disponibili-{{ $componentId }}" role="tabpanel">
                <div class="alert alert-secondary mb-2 py-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Questi militari non sono disponibili per la data selezionata. Il motivo è indicato per ciascuno.</small>
                </div>
                <div class="table-responsive" style="max-height: {{ $maxHeight }};">
                    <table class="table table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Grado</th>
                                <th>Cognome</th>
                                <th>Nome</th>
                                <th>Motivo Non Disponibilità</th>
                                @if($showPlotone)<th>Plotone</th>@endif
                            </tr>
                        </thead>
                        <tbody id="tabellaNonDisponibili_{{ $componentId }}">
                            <tr>
                                <td colspan="{{ 4 + ($showPlotone ? 1 : 0) }}" class="text-center text-muted py-3">
                                    Carica i dati selezionando una data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Gestore del componente selettore militari con disponibilità
 */
window.SelettoreMilitariDisponibilita = window.SelettoreMilitariDisponibilita || {};

window.SelettoreMilitariDisponibilita['{{ $componentId }}'] = {
    componentId: '{{ $componentId }}',
    routeMilitariDisponibilita: '{{ route("servizi.turni.militari-disponibilita") }}',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
    multiSelect: {{ $multiSelect ? 'true' : 'false' }},
    showPlotone: {{ $showPlotone ? 'true' : 'false' }},
    militariSelezionati: new Set(),
    datiMilitari: { disponibili: [], non_disponibili: [] },

    /**
     * Inizializza il componente
     */
    init: function() {
        this.bindEvents();
    },

    /**
     * Collega gli eventi
     */
    bindEvents: function() {
        const self = this;
        const selectAll = document.getElementById('selectAllDisponibili_' + this.componentId);
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                self.toggleSelectAll(this.checked);
            });
        }
    },

    /**
     * Carica i militari con disponibilità per una data
     */
    caricaMilitari: async function(data, excludeActivityId = null) {
        const loading = document.getElementById('loadingMilitari_' + this.componentId);
        const messaggio = document.getElementById('messaggioIniziale_' + this.componentId);
        const contenuto = document.getElementById('contenutoTabs_' + this.componentId);

        // Mostra loading
        loading.classList.remove('d-none');
        messaggio.classList.add('d-none');
        contenuto.classList.add('d-none');

        try {
            const response = await fetch(this.routeMilitariDisponibilita, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    data: data,
                    exclude_activity_id: excludeActivityId
                })
            });

            const result = await response.json();

            if (result.success) {
                this.datiMilitari = result;
                this.popolaTabelle(result);
                
                // Mostra contenuto
                contenuto.classList.remove('d-none');
            } else {
                throw new Error(result.message || 'Errore nel caricamento');
            }
        } catch (error) {
            console.error('Errore caricamento militari:', error);
            messaggio.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-2"></i>Errore nel caricamento. Riprova.';
            messaggio.classList.remove('d-none', 'alert-info');
            messaggio.classList.add('alert-danger');
        } finally {
            loading.classList.add('d-none');
        }
    },

    /**
     * Popola le tabelle disponibili e non disponibili
     */
    popolaTabelle: function(result) {
        const tbodyDisp = document.getElementById('tabellaDisponibili_' + this.componentId);
        const tbodyNonDisp = document.getElementById('tabellaNonDisponibili_' + this.componentId);
        const countDisp = document.getElementById('countDisponibili_' + this.componentId);
        const countNonDisp = document.getElementById('countNonDisponibili_' + this.componentId);
        const selectAll = document.getElementById('selectAllDisponibili_' + this.componentId);
        
        const self = this;
        this.militariSelezionati.clear();

        // Aggiorna contatori
        countDisp.textContent = result.totale_disponibili || 0;
        countNonDisp.textContent = result.totale_non_disponibili || 0;

        // Popola tabella disponibili
        if (!result.disponibili || result.disponibili.length === 0) {
            const colspan = (this.multiSelect ? 1 : 0) + 3 + (this.showPlotone ? 1 : 0);
            tbodyDisp.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted py-4">
                        <i class="fas fa-user-slash fa-2x mb-2 d-block opacity-50"></i>
                        Nessun militare disponibile per questa data
                    </td>
                </tr>
            `;
        } else {
            tbodyDisp.innerHTML = result.disponibili.map(m => {
                // Pre-seleziona tutti
                self.militariSelezionati.add(m.id);
                
                let plotoneCol = this.showPlotone ? `<td><small class="text-muted">${m.plotone || '-'}</small></td>` : '';
                let checkboxCol = this.multiSelect ? 
                    `<td><input type="checkbox" class="form-check-input militare-checkbox-${this.componentId}" value="${m.id}" data-nome="${m.grado} ${m.cognome} ${m.nome}" checked></td>` : '';
                
                return `
                    <tr data-militare-id="${m.id}">
                        ${checkboxCol}
                        <td><strong>${m.grado}</strong></td>
                        <td>${m.cognome}</td>
                        <td>${m.nome}</td>
                        ${plotoneCol}
                    </tr>
                `;
            }).join('');

            // Bind checkbox events
            this.bindCheckboxEvents();
        }

        // Popola tabella non disponibili
        if (!result.non_disponibili || result.non_disponibili.length === 0) {
            const colspan = 4 + (this.showPlotone ? 1 : 0);
            tbodyNonDisp.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted py-3">
                        <i class="fas fa-thumbs-up text-success me-2"></i>
                        Tutti i militari sono disponibili!
                    </td>
                </tr>
            `;
        } else {
            tbodyNonDisp.innerHTML = result.non_disponibili.map(m => {
                // Badge colorato per motivo
                let motivoClass = 'bg-secondary';
                let motivoIcon = '';
                
                if (m.codice_impegno) {
                    motivoClass = 'bg-warning text-dark';
                    motivoIcon = '<i class="fas fa-calendar-day me-1"></i>';
                } else if (m.motivo?.toLowerCase().includes('impegnato')) {
                    motivoClass = 'bg-warning text-dark';
                    motivoIcon = '<i class="fas fa-briefcase me-1"></i>';
                }

                let plotoneCol = this.showPlotone ? `<td><small class="text-muted">${m.plotone || '-'}</small></td>` : '';

                return `
                    <tr class="table-light" data-militare-id="${m.id}">
                        <td>${m.grado}</td>
                        <td>${m.cognome}</td>
                        <td>${m.nome}</td>
                        <td>
                            <span class="badge ${motivoClass}">
                                ${motivoIcon}${m.motivo}
                            </span>
                        </td>
                        ${plotoneCol}
                    </tr>
                `;
            }).join('');
        }

        // Reset select all
        if (selectAll) {
            selectAll.checked = result.disponibili?.length > 0;
        }

        // Aggiorna contatore selezionati
        this.aggiornaContatoreSelezionati();

        // Emetti evento
        this.emitSelezionatiChange();
    },

    /**
     * Collega eventi checkbox
     */
    bindCheckboxEvents: function() {
        const self = this;
        const checkboxes = document.querySelectorAll('.militare-checkbox-' + this.componentId);
        
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    self.militariSelezionati.add(parseInt(this.value));
                } else {
                    self.militariSelezionati.delete(parseInt(this.value));
                }
                self.aggiornaContatoreSelezionati();
                self.aggiornaSelectAll();
                self.emitSelezionatiChange();
            });
        });
    },

    /**
     * Seleziona/deseleziona tutti
     */
    toggleSelectAll: function(checked) {
        const checkboxes = document.querySelectorAll('.militare-checkbox-' + this.componentId);
        
        checkboxes.forEach(cb => {
            cb.checked = checked;
            if (checked) {
                this.militariSelezionati.add(parseInt(cb.value));
            } else {
                this.militariSelezionati.delete(parseInt(cb.value));
            }
        });

        this.aggiornaContatoreSelezionati();
        this.emitSelezionatiChange();
    },

    /**
     * Aggiorna stato checkbox "seleziona tutti"
     */
    aggiornaSelectAll: function() {
        const selectAll = document.getElementById('selectAllDisponibili_' + this.componentId);
        const checkboxes = document.querySelectorAll('.militare-checkbox-' + this.componentId);
        const checkedCount = document.querySelectorAll('.militare-checkbox-' + this.componentId + ':checked').length;
        
        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = checkedCount === checkboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
        }
    },

    /**
     * Aggiorna contatore selezionati
     */
    aggiornaContatoreSelezionati: function() {
        const contatore = document.getElementById('contatoreSelezionati_' + this.componentId);
        if (contatore) {
            contatore.textContent = this.militariSelezionati.size + ' selezionati';
        }
    },

    /**
     * Emette evento quando cambia la selezione
     */
    emitSelezionatiChange: function() {
        const event = new CustomEvent('militariSelezionatiChange', {
            detail: {
                componentId: this.componentId,
                militariIds: Array.from(this.militariSelezionati),
                count: this.militariSelezionati.size
            }
        });
        document.dispatchEvent(event);
    },

    /**
     * Restituisce gli ID dei militari selezionati
     */
    getMilitariSelezionati: function() {
        return Array.from(this.militariSelezionati);
    },

    /**
     * Restituisce i dati dei militari selezionati
     */
    getDatiMilitariSelezionati: function() {
        return this.datiMilitari.disponibili.filter(m => this.militariSelezionati.has(m.id));
    },

    /**
     * Reset del componente
     */
    reset: function() {
        const messaggio = document.getElementById('messaggioIniziale_' + this.componentId);
        const contenuto = document.getElementById('contenutoTabs_' + this.componentId);
        
        messaggio.classList.remove('d-none', 'alert-danger');
        messaggio.classList.add('alert-info');
        messaggio.innerHTML = '<i class="fas fa-info-circle me-2"></i>Seleziona una data per visualizzare la disponibilità dei militari.';
        
        contenuto.classList.add('d-none');
        this.militariSelezionati.clear();
        this.datiMilitari = { disponibili: [], non_disponibili: [] };
        
        this.emitSelezionatiChange();
    }
};

// Auto-init quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    window.SelettoreMilitariDisponibilita['{{ $componentId }}'].init();
});
</script>

<style>
.selettore-militari-disponibilita .nav-tabs .nav-link {
    font-size: 0.9rem;
}

.selettore-militari-disponibilita .nav-tabs .nav-link.active {
    font-weight: 600;
}

.selettore-militari-disponibilita .table th {
    font-size: 0.85rem;
    font-weight: 600;
    color: #495057;
}

.selettore-militari-disponibilita .table td {
    font-size: 0.9rem;
    vertical-align: middle;
}

.selettore-militari-disponibilita .badge {
    font-size: 0.75rem;
}

.selettore-militari-disponibilita .table-responsive {
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.selettore-militari-disponibilita .spinner-border {
    width: 2rem;
    height: 2rem;
}
</style>
