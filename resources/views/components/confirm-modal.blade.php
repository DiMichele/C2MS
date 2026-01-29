{{--
|--------------------------------------------------------------------------
| Componente Confirm Modal - Modal di conferma riutilizzabile
|--------------------------------------------------------------------------
| 
| Questo componente crea un modal di conferma statico nel DOM.
| Nella maggior parte dei casi, è preferibile usare il sistema JavaScript
| dinamico SUGECO.Confirm che crea il modal automaticamente.
|
| Utilizzo componente Blade (per modal statici):
|   <x-confirm-modal id="deleteConfirm" />
|   <x-confirm-modal id="saveConfirm" type="success" title="Salva" message="Vuoi salvare?" confirmText="Salva" />
|
| Utilizzo JavaScript (raccomandato):
|   // Promise-based (moderno)
|   if (await SUGECO.Confirm.delete('Eliminare questo elemento?')) { ... }
|   if (await SUGECO.Confirm.save('Salvare le modifiche?')) { ... }
|   if (await confirmAction('Sei sicuro?')) { ... }
|   
|   // Con opzioni complete
|   const confirmed = await SUGECO.Confirm.show({
|       message: 'Messaggio',
|       title: 'Titolo opzionale',
|       type: 'danger', // default, danger, warning, success, info
|       confirmText: 'Conferma',
|       cancelText: 'Annulla'
|   });
|
| Tipi disponibili:
|   - default: blu scuro (azioni generiche)
|   - danger: rosso (eliminazioni, azioni distruttive)
|   - warning: arancione (attenzione)
|   - success: verde (salvataggi, conferme positive)
|   - info: blu (informazioni)
|
| @version 1.0.0
| @author SUGECO Team
--}}

@props([
    'id' => 'confirmModal',
    'type' => 'default',
    'title' => '',
    'message' => 'Sei sicuro di voler procedere?',
    'confirmText' => 'Conferma',
    'cancelText' => 'Annulla',
    'icon' => null
])

@php
    // Configurazione icone e colori per tipo
    $typeConfig = [
        'default' => [
            'icon' => 'fas fa-question-circle fa-3x',
            'color' => 'primary',
            'buttonClass' => 'primary',
            'borderColor' => '#0a2342'
        ],
        'danger' => [
            'icon' => 'fas fa-exclamation-triangle fa-3x',
            'color' => 'danger',
            'buttonClass' => 'danger',
            'borderColor' => '#b3122e'
        ],
        'warning' => [
            'icon' => 'fas fa-exclamation-circle fa-3x',
            'color' => 'warning',
            'buttonClass' => 'warning',
            'borderColor' => '#e07a00'
        ],
        'success' => [
            'icon' => 'fas fa-check-circle fa-3x',
            'color' => 'success',
            'buttonClass' => 'success',
            'borderColor' => '#1d8f4b'
        ],
        'info' => [
            'icon' => 'fas fa-info-circle fa-3x',
            'color' => 'info',
            'buttonClass' => 'info',
            'borderColor' => '#1c6dd0'
        ]
    ];
    
    $config = $typeConfig[$type] ?? $typeConfig['default'];
    $iconClass = $icon ?? $config['icon'];
@endphp

<div class="modal fade sugeco-confirm-modal" 
     id="{{ $id }}" 
     tabindex="-1" 
     aria-labelledby="{{ $id }}Label" 
     aria-hidden="true"
     data-confirm-type="{{ $type }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content sugeco-confirm-content" data-type="{{ $type }}">
            <div class="modal-body text-center py-4">
                <div class="sugeco-confirm-icon mb-3 text-{{ $config['color'] }}">
                    <i class="{{ $iconClass }}"></i>
                </div>
                
                @if($title)
                <h5 class="sugeco-confirm-title mb-2" id="{{ $id }}Label">{{ $title }}</h5>
                @endif
                
                <p class="sugeco-confirm-message mb-0" id="{{ $id }}Message">{{ $message }}</p>
            </div>
            
            <div class="modal-footer justify-content-center border-0 pt-0 gap-2">
                <button type="button" 
                        class="btn sugeco-confirm-cancel btn-outline-secondary" 
                        data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    <span class="btn-text">{{ $cancelText }}</span>
                </button>
                <button type="button" 
                        class="btn sugeco-confirm-ok btn-{{ $config['buttonClass'] }}" 
                        id="{{ $id }}Confirm">
                    <i class="fas fa-check me-1"></i>
                    <span class="btn-text">{{ $confirmText }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- 
Script helper per usare questo modal specifico.
Normalmente si preferisce usare SUGECO.Confirm.show() che è globale.
--}}
@once
@push('scripts')
<script>
/**
 * Helper per modal di conferma statici definiti con x-confirm-modal
 * @param {string} modalId - ID del modal (senza #)
 * @param {string} [message] - Messaggio opzionale da mostrare
 * @returns {Promise<boolean>}
 */
window.showStaticConfirmModal = function(modalId, message) {
    return new Promise((resolve) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.warn(`Modal #${modalId} non trovato`);
            resolve(false);
            return;
        }
        
        // Aggiorna messaggio se fornito
        if (message) {
            const messageEl = modal.querySelector('.sugeco-confirm-message');
            if (messageEl) messageEl.textContent = message;
        }
        
        const bsModal = bootstrap.Modal.getOrCreateInstance(modal);
        const btnConfirm = modal.querySelector('.sugeco-confirm-ok');
        
        let resolved = false;
        
        const handleConfirm = () => {
            if (!resolved) {
                resolved = true;
                bsModal.hide();
                resolve(true);
            }
        };
        
        const handleHidden = () => {
            if (!resolved) {
                resolved = true;
                resolve(false);
            }
            // Cleanup
            btnConfirm.removeEventListener('click', handleConfirm);
            modal.removeEventListener('hidden.bs.modal', handleHidden);
        };
        
        btnConfirm.addEventListener('click', handleConfirm);
        modal.addEventListener('hidden.bs.modal', handleHidden, { once: true });
        
        bsModal.show();
    });
};
</script>
@endpush
@endonce
