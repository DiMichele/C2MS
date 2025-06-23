<div class="tab-pane fade show active" id="valutazioni" role="tabpanel" aria-labelledby="valutazioni-tab">
                    <!-- Criteri di Valutazione -->
                    @foreach(\App\Models\MilitareValutazione::getCriteri() as $campo => $etichetta)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $etichetta }}</label>
                        <div class="rating-container" data-field="{{ $campo }}">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="rating-item {{ ($valutazioneUtente && $valutazioneUtente->$campo >= $i) ? 'rating-active' : '' }}" 
                                  data-value="{{ $i }}" 
                                  title="{{ $i }}/5">
                                <i class="fas fa-star"></i>
                            </span>
                            @endfor
                        </div>
                        <input type="hidden" name="{{ $campo }}" value="{{ $valutazioneUtente ? $valutazioneUtente->$campo : 0 }}" id="input_{{ $campo }}">
                    </div>
                    @endforeach
                    
                    <!-- Note Positive -->
                    <div class="mb-3">
                        <label for="note_positive" class="form-label fw-semibold text-success">
                            <i class="fas fa-thumbs-up me-1"></i>Note Positive
                        </label>
                        <textarea class="form-control border-success autosave-field" 
                                  id="note_positive" 
                                  data-field="note_positive"
                                  rows="3" 
                                  placeholder="Punti di forza, comportamenti positivi, risultati eccellenti...">{{ $valutazioneUtente ? $valutazioneUtente->note_positive : '' }}</textarea>
                    </div>
                    
                    <!-- Note Negative / Aree di Miglioramento -->
                    <div class="mb-3">
                        <label for="note_negative" class="form-label fw-semibold text-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>Aree di Miglioramento
                        </label>
                        <textarea class="form-control border-warning autosave-field" 
                                  id="note_negative" 
                                  data-field="note_negative"
                                  rows="3" 
                                  placeholder="Aspetti da migliorare, suggerimenti, obiettivi futuri...">{{ $valutazioneUtente ? $valutazioneUtente->note_negative : '' }}</textarea>
                    </div>

                    <!-- Riepilogo Valutazione - Sempre visibile -->
                    <div class="mt-4 p-3 bg-light rounded" id="valutazione-summary">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h6 class="mb-1">Media Valutazione</h6>
                                <div class="d-flex align-items-center">
                                    <span class="badge fs-6 me-2 
                                    @if($valutazioneUtente)
                                        @if($militare->media_valutazioni >= 4.5) bg-success text-white
                                        @elseif($militare->media_valutazioni >= 3.5) bg-primary text-white  
                                        @elseif($militare->media_valutazioni >= 2.5) bg-warning text-dark
                                        @elseif($militare->media_valutazioni > 0) bg-danger text-white
                                        @else bg-secondary text-white
                                        @endif
                                    @else bg-secondary text-white
                                    @endif" id="media-badge">{{ $valutazioneUtente ? $militare->media_valutazioni : '0.0' }}</span>
                                    <div class="star-display" id="star-display">
                                        @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $valutazioneUtente && $i <= round($militare->media_valutazioni) ? 'text-warning' : 'text-muted' }}" data-star="{{ $i }}"></i>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <small class="text-muted" id="last-update">
                                    @if($valutazioneUtente)
                                        Ultima modifica: {{ $valutazioneUtente->updated_at->format('d/m/Y H:i') }}
                                    @else
                                        Nessuna valutazione presente
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
</div>

<style>
/* Stili per il sistema di valutazione con stelle - Migliorato */
.rating-container {
    display: flex !important;
    gap: 8px !important;
    margin: 8px 0 !important;
    padding: 4px 0 !important;
    align-items: center !important;
}

.rating-container .rating-item {
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    position: relative !important;
    user-select: none !important;
    display: inline-block !important;
}

.rating-container .rating-item i {
    font-size: 1.5rem !important;
    color: #e2e8f0 !important;
    transition: all 0.2s ease !important;
}

.rating-container .rating-item:hover i {
    color: #ffc107 !important;
    transform: scale(1.15) !important;
}

.rating-container .rating-item.rating-hover i {
    color: #ffc107 !important;
    transform: scale(1.15) !important;
}

.rating-container .rating-item.rating-active i {
    color: #ffc107 !important;
    text-shadow: 0 0 8px rgba(255, 193, 7, 0.6) !important;
}

/* Stili per le textarea con autosalvataggio */
.autosave-field {
    transition: border-color 0.3s ease;
}

.autosave-field.saving {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
}

.autosave-field.saved {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

/* Stili per le textarea delle note */
.border-success:focus {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

.border-warning:focus {
    border-color: #ffc107 !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25) !important;
}

/* Responsive */
@media (max-width: 768px) {
    .rating-container .rating-item i {
        font-size: 1.3rem !important;
    }
    
    .rating-container {
        gap: 6px !important;
    }
}

/* Indicatore di salvataggio */
.save-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 8px 16px;
    background: #28a745;
    color: white;
    border-radius: 20px;
    font-size: 12px;
    z-index: 1000;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.save-indicator.show {
    opacity: 1;
    transform: translateY(0);
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const militareId = {{ $militare->id }};
    
    // Trova tutti i contenitori di valutazione
    const ratingContainers = document.querySelectorAll('.rating-container');
    
    // Gestione pallini di valutazione
    ratingContainers.forEach((container) => {
        const field = container.getAttribute('data-field');
        const items = container.querySelectorAll('.rating-item');
        const input = document.getElementById(`input_${field}`);
        
        if (!input || items.length === 0) {
            return;
        }
        
        const currentValue = parseInt(input.value) || 0;
        updateRating(items, currentValue);
        
        // Aggiungi event listeners
        items.forEach((item, itemIndex) => {
            const value = itemIndex + 1;
            
            // Click event con autosalvataggio
            item.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                

                
                input.value = value;
                updateRating(items, value);
                
                // Feedback visivo
                item.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 200);
                
                // Autosalvataggio
                saveField(field, value);
                
                // Aggiorna media in tempo reale
                updateMediaInRealTime();
            });
            
            // Hover events
            item.addEventListener('mouseenter', function() {
                highlightRating(items, value);
            });
            
            container.addEventListener('mouseleave', function() {
                const currentValue = parseInt(input.value) || 0;
                updateRating(items, currentValue);
            });
        });
    });
    
    // Gestione autosalvataggio per le textarea
    const textareas = document.querySelectorAll('.autosave-field');
    textareas.forEach(textarea => {
        let timeout;
        
        textarea.addEventListener('input', function() {
            const field = this.getAttribute('data-field');
            const value = this.value;
            
            // Mostra stato "salvando"
            this.classList.add('saving');
            this.classList.remove('saved');
            
            // Debounce per evitare troppe richieste
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                saveField(field, value, this);
            }, 1000); // Salva dopo 1 secondo di inattività
        });
    });
    
    function updateRating(items, count) {
        items.forEach((item, index) => {
            item.classList.remove('rating-active', 'rating-hover');
            if (index < count) {
                item.classList.add('rating-active');
            }
        });
    }
    
    function highlightRating(items, count) {
        items.forEach((item, index) => {
            item.classList.remove('rating-active', 'rating-hover');
            if (index < count) {
                item.classList.add('rating-hover');
            }
        });
    }
    
    function saveField(field, value, element = null) {
        fetch(`/militare/${militareId}/valutazioni/field`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                field: field,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                
                if (element) {
                    element.classList.remove('saving');
                    element.classList.add('saved');
                    setTimeout(() => {
                        element.classList.remove('saved');
                    }, 2000);
                }
                
                showSaveIndicator('Salvato automaticamente');
            }
        })
        .catch(error => {
            if (element) {
                element.classList.remove('saving');
            }
        });
    }
    
    function showSaveIndicator(message) {
        // Rimuovi indicatori esistenti
        document.querySelectorAll('.save-indicator').forEach(el => el.remove());
        
        const indicator = document.createElement('div');
        indicator.className = 'save-indicator';
        indicator.innerHTML = `<i class="fas fa-check me-1"></i>${message}`;
        
        document.body.appendChild(indicator);
        
        // Mostra
        setTimeout(() => indicator.classList.add('show'), 100);
        
        // Nascondi dopo 2 secondi
        setTimeout(() => {
            indicator.classList.remove('show');
            setTimeout(() => indicator.remove(), 300);
        }, 2000);
    }
    
    function updateMediaInRealTime() {
        // Calcola la media dei valori attuali
        const criteri = ['precisione_lavoro', 'affidabilita', 'capacita_tecnica', 'collaborazione', 'iniziativa', 'autonomia'];
        let totale = 0;
        let count = 0;
        
        criteri.forEach(criterio => {
            const input = document.getElementById(`input_${criterio}`);
            if (input) {
                const valore = parseInt(input.value) || 0;
                if (valore > 0) {
                    totale += valore;
                    count++;
                }
            }
        });
        
        const media = count > 0 ? (totale / count) : 0;
        const mediaArrotondata = Math.round(media * 10) / 10; // Arrotonda a 1 decimale
        
        // Aggiorna il badge della media
        const mediaBadge = document.getElementById('media-badge');
        if (mediaBadge) {
            mediaBadge.textContent = mediaArrotondata.toFixed(1);
            
            // Rimuovi tutte le classi di colore esistenti e aggiungi quella corretta
            mediaBadge.className = 'badge fs-6 me-2';
            if (mediaArrotondata >= 4.5) {
                mediaBadge.classList.add('bg-success', 'text-white');
            } else if (mediaArrotondata >= 3.5) {
                mediaBadge.classList.add('bg-primary', 'text-white');
            } else if (mediaArrotondata >= 2.5) {
                mediaBadge.classList.add('bg-warning', 'text-dark');
            } else if (mediaArrotondata > 0) {
                mediaBadge.classList.add('bg-danger', 'text-white');
            } else {
                mediaBadge.classList.add('bg-secondary', 'text-white');
            }
        }
        
        // Aggiorna le stelle
        const starDisplay = document.getElementById('star-display');
        if (starDisplay) {
            const stars = starDisplay.querySelectorAll('i[data-star]');
            stars.forEach(star => {
                const starNumber = parseInt(star.getAttribute('data-star'));
                star.className = 'fas fa-star';
                if (starNumber <= Math.round(media)) {
                    star.classList.add('text-warning');
                } else {
                    star.classList.add('text-muted');
                }
            });
        }
        
        // Aggiorna timestamp
        const lastUpdate = document.getElementById('last-update');
        if (lastUpdate && count > 0) {
            const now = new Date();
            const timeString = now.toLocaleString('it-IT', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            lastUpdate.textContent = `Ultima modifica: ${timeString}`;
        }
        
        // Aggiorna anche l'header della pagina
        const headerBadge = document.getElementById('header-valutazione-badge');
        const headerMediaValue = document.getElementById('header-media-value');
        if (headerBadge && headerMediaValue) {
            if (count > 0) {
                headerBadge.style.display = '';
                headerMediaValue.textContent = mediaArrotondata.toFixed(1);
            } else {
                headerBadge.style.display = 'none';
            }
        }
        

    }
});
</script> 
