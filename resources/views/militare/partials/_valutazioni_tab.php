<div class="tab-pane fade" id="valutazioni" role="tabpanel" aria-labelledby="valutazioni-tab">
    <div class="row">
        <!-- Sezione Valutazione Personale -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-user-edit text-primary me-2"></i>
                        La Tua Valutazione
                    </h6>
                </div>
                <div class="card-body">
                    <form id="valutazioneForm" action="{{ $valutazioneUtente ? route('militare.valutazioni.update', $militare) : route('militare.valutazioni.store', $militare) }}" method="POST">
                        @csrf
                        @if($valutazioneUtente)
                            @method('PUT')
                        @endif
                        
                        @foreach(\App\Models\MilitareValutazione::getCriteri() as $campo => $etichetta)
                        <div class="mb-4">
                            <label class="form-label fw-semibold">{{ $etichetta }}</label>
                            <div class="star-rating" data-field="{{ $campo }}">
                                @for($i = 1; $i <= 5; $i++)
                                <span class="star {{ ($valutazioneUtente && $valutazioneUtente->$campo >= $i) ? 'active' : '' }}" 
                                      data-value="{{ $i }}" 
                                      title="{{ $i }} stella{{ $i > 1 ? 'e' : '' }}">
                                    <i class="fas fa-star"></i>
                                </span>
                                @endfor
                            </div>
                            <input type="hidden" name="{{ $campo }}" value="{{ $valutazioneUtente ? $valutazioneUtente->$campo : 0 }}" id="input_{{ $campo }}">
                        </div>
                        @endforeach
                        
                        <div class="mb-4">
                            <label for="note_valutazione" class="form-label fw-semibold">Note aggiuntive</label>
                            <textarea class="form-control" id="note_valutazione" name="note" rows="3" 
                                      placeholder="Aggiungi note o commenti alla valutazione...">{{ $valutazioneUtente ? $valutazioneUtente->note : '' }}</textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                {{ $valutazioneUtente ? 'Aggiorna Valutazione' : 'Salva Valutazione' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sezione Statistiche e Valutazioni Ricevute -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        Valutazioni Ricevute
                    </h6>
                </div>
                <div class="card-body">
                    @if($militare->valutazioni->count() > 0)
                        <!-- Media Generale -->
                        <div class="text-center mb-4 p-3 bg-light rounded">
                            <h4 class="text-primary mb-1">{{ $militare->media_valutazioni }}</h4>
                            <div class="star-display mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round($militare->media_valutazioni) ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </div>
                            <small class="text-muted">Media generale ({{ $militare->valutazioni->count() }} valutazioni)</small>
                        </div>
                        
                        <!-- Dettaglio per Criterio -->
                        <div class="row g-3">
                            @foreach(\App\Models\MilitareValutazione::getCriteri() as $campo => $etichetta)
                            @php
                                $media = $militare->valutazioni->avg($campo);
                            @endphp
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">{{ $etichetta }}</span>
                                    <span class="badge bg-primary">{{ number_format($media, 1) }}</span>
                                </div>
                                <div class="star-display-small">
                                    @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= round($media) ? 'text-warning' : 'text-muted' }}"></i>
                                    @endfor
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Lista Valutazioni -->
                        <hr class="my-4">
                        <h6 class="mb-3">Storico Valutazioni</h6>
                        <div class="valutazioni-list" style="max-height: 300px; overflow-y: auto;">
                            @foreach($militare->valutazioni->sortByDesc('created_at') as $valutazione)
                            <div class="card mb-2 border-light">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong class="text-primary">{{ $valutazione->valutatore->name }}</strong>
                                            <div class="star-display-mini mt-1">
                                                @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= round($valutazione->media) ? 'text-warning' : 'text-muted' }}"></i>
                                                @endfor
                                                <span class="ms-1 small text-muted">{{ $valutazione->media }}</span>
                                            </div>
                                        </div>
                                        <small class="text-muted">{{ $valutazione->created_at->format('d/m/Y') }}</small>
                                    </div>
                                    @if($valutazione->note)
                                    <p class="small text-muted mb-0">{{ $valutazione->note }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-star-half-alt text-muted mb-3" style="font-size: 3rem;"></i>
                            <h6 class="text-muted">Nessuna valutazione ricevuta</h6>
                            <p class="small text-muted mb-0">Questo militare non ha ancora ricevuto valutazioni.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Stili per il sistema di valutazione a stelle */
.star-rating {
    display: flex;
    gap: 5px;
    margin: 8px 0;
}

.star-rating .star {
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.star-rating .star:hover,
.star-rating .star.hover {
    color: #ffc107;
    transform: scale(1.1);
}

.star-rating .star.active {
    color: #ffc107;
}

.star-display,
.star-display-small,
.star-display-mini {
    display: flex;
    gap: 2px;
}

.star-display i {
    font-size: 1.2rem;
}

.star-display-small i {
    font-size: 0.9rem;
}

.star-display-mini i {
    font-size: 0.8rem;
}

/* Animazioni per le stelle */
.star-rating .star {
    animation: starPulse 0.3s ease;
}

@keyframes starPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

/* Stili per le card delle valutazioni */
.valutazioni-list .card {
    transition: all 0.2s ease;
}

.valutazioni-list .card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .star-rating .star {
        font-size: 1.3rem;
    }
    
    .star-display i {
        font-size: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestione del sistema di valutazione a stelle
    const starRatings = document.querySelectorAll('.star-rating');
    
    starRatings.forEach(rating => {
        const stars = rating.querySelectorAll('.star');
        const field = rating.getAttribute('data-field');
        const input = document.getElementById(`input_${field}`);
        
        stars.forEach((star, index) => {
            // Hover effect
            star.addEventListener('mouseenter', () => {
                highlightStars(stars, index + 1);
            });
            
            // Click to select
            star.addEventListener('click', () => {
                const value = index + 1;
                input.value = value;
                setActiveStars(stars, value);
                
                // Animazione di feedback
                star.style.animation = 'starPulse 0.3s ease';
                setTimeout(() => {
                    star.style.animation = '';
                }, 300);
            });
        });
        
        // Reset hover effect quando si esce dal rating
        rating.addEventListener('mouseleave', () => {
            const currentValue = parseInt(input.value) || 0;
            setActiveStars(stars, currentValue);
        });
    });
    
    function highlightStars(stars, count) {
        stars.forEach((star, index) => {
            if (index < count) {
                star.classList.add('hover');
            } else {
                star.classList.remove('hover');
            }
        });
    }
    
    function setActiveStars(stars, count) {
        stars.forEach((star, index) => {
            star.classList.remove('hover');
            if (index < count) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
    }
    
    // Gestione invio form con AJAX
    const valutazioneForm = document.getElementById('valutazioneForm');
    if (valutazioneForm) {
        valutazioneForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Verifica che almeno un criterio sia stato valutato
            const inputs = this.querySelectorAll('input[type="hidden"]');
            let hasRating = false;
            
            inputs.forEach(input => {
                if (parseInt(input.value) > 0) {
                    hasRating = true;
                }
            });
            
            if (!hasRating) {
                alert('Per favore, valuta almeno un criterio prima di salvare.');
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disabilita il pulsante e mostra loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostra messaggio di successo
                    showToast('Valutazione salvata con successo!', 'success');
                    
                    // Ricarica la pagina dopo un breve delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(data.message || 'Errore durante il salvataggio', 'error');
                }
            })
            .catch(error => {
                showToast('Errore di connessione', 'error');
            })
            .finally(() => {
                // Ripristina il pulsante
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
    
    // Funzione per mostrare toast notifications
    function showToast(message, type = 'info') {
        // Crea un toast semplice se non esiste già un sistema
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Rimuovi automaticamente dopo 5 secondi
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }
});
</script> 
