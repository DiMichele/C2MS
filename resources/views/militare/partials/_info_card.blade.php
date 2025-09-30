{{--
|--------------------------------------------------------------------------
| Scheda informazioni generali del militare
|--------------------------------------------------------------------------
| Mostra le informazioni principali in una card ben strutturata
| @version 1.0
| @author Michele Di Gennaro
--}}

<div class="info-card">
    <div class="info-card-header">
        <h5 class="info-card-title">
            <i class="fas fa-info-circle"></i> Informazioni Generali
        </h5>
    </div>
    <div class="info-card-body">
        <ul class="info-list">
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-circle"></i> Stato
                </div>
                <div class="info-value">
                    @php
                        // Ottieni lo stato dal CPT per la data odierna
                        $oggi = now();
                        $giornoOggi = $oggi->day;
                        $meseOggi = $oggi->month;
                        $annoOggi = $oggi->year;
                        
                        // Trova la pianificazione giornaliera per oggi
                        $pianificazioneOggi = $militare->pianificazioniGiornaliere()
                            ->whereHas('pianificazioneMensile', function($q) use ($annoOggi, $meseOggi) {
                                $q->where('anno', $annoOggi)
                                  ->where('mese', $meseOggi);
                            })
                            ->where('giorno', $giornoOggi)
                            ->with('tipoServizio')
                            ->first();
                        
                        $tipoServizio = $pianificazioneOggi ? $pianificazioneOggi->tipoServizio : null;
                    @endphp
                    @if($tipoServizio)
                        <span class="badge" style="background-color: {{ $tipoServizio->colore_badge ?? '#6c757d' }}; color: white;">
                            {{ $tipoServizio->nome }}
                        </span>
                    @else
                        <span class="badge bg-success">Libero</span>
                    @endif
                </div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-medal"></i> Grado
                </div>
                <div class="info-value">{{ $militare->grado->nome ?? 'Non assegnato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-user"></i> Cognome
                </div>
                <div class="info-value">{{ $militare->cognome }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-user"></i> Nome
                </div>
                <div class="info-value">{{ $militare->nome }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-layer-group"></i> Plotone
                </div>
                <div class="info-value">{{ $militare->plotone->nome ?? 'Non assegnato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-building"></i> Ufficio
                </div>
                <div class="info-value">{{ $militare->polo->nome ?? 'Non assegnato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-briefcase"></i> Incarico
                </div>
                <div class="info-value">{{ $militare->mansione->nome ?? 'Non specificato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-calendar-alt"></i> Anzianità
                </div>
                <div class="info-value">{{ $militare->anzianita ?? '-' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-envelope"></i> Email
                </div>
                <div class="info-value">
                    @if($militare->email_istituzionale)
                        <a href="mailto:{{ $militare->email_istituzionale }}" class="text-decoration-none">
                            {{ $militare->email_istituzionale }}
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-phone"></i> Cellulare
                </div>
                <div class="info-value">
                    @if($militare->telefono)
                        <a href="tel:{{ $militare->telefono }}" class="text-decoration-none">
                            {{ $militare->telefono }}
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
            </li>
        </ul>
    </div>
</div>

<style>
    .info-card {
        border-radius: 12px;
        overflow: hidden;
        background-color: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
        height: 100%;
        transition: all 0.3s ease;
    }
    
    .info-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    .info-card-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }
    
    .info-card-title {
        font-weight: 700;
        color: var(--navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.1rem;
    }
    
    .info-card-title i {
        color: var(--gold);
        font-size: 1.2rem;
    }
    
    .info-card-body {
        padding: 2rem;
    }
    
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .info-item {
        padding: 1rem 0;
        border-bottom: 1px solid #f1f3f5;
        display: flex;
        align-items: flex-start;
        transition: all 0.2s ease;
    }
    
    .info-item:hover {
        background-color: rgba(10, 35, 66, 0.02);
        margin: 0 -1rem;
        padding-left: 2rem;
        padding-right: 2rem;
        border-radius: 8px;
    }
    
    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .info-item:first-child {
        padding-top: 0;
    }
    
    .info-label {
        flex: 0 0 140px;
        color: #6c757d;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .info-label i {
        width: 18px;
        text-align: center;
        color: var(--navy);
        opacity: 0.6;
    }
    
    .info-value {
        flex-grow: 1;
        color: #2D3748;
        font-weight: 500;
        font-size: 1rem;
        word-break: break-word;
    }
    
    .info-value a {
        color: var(--navy);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    
    .info-value a:hover {
        color: var(--navy-light);
        text-decoration: underline;
    }
    
    .status-text {
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .status-presente {
        color: #28a745;
    }
    
    .status-assente {
        color: #dc3545;
    }
    
    @media (max-width: 768px) {
        .info-card-header {
            padding: 1.25rem 1.5rem;
        }
        
        .info-card-body {
            padding: 1.5rem;
        }
        
        .info-item {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .info-label {
            flex: initial;
            font-size: 0.8rem;
        }
        
        .info-value {
            font-size: 0.95rem;
        }
    }
</style> 
