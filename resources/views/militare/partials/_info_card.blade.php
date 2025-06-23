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
                    <span class="status-text {{ $militare->isPresente() ? 'status-presente' : 'status-assente' }}">
                        {{ $militare->isPresente() ? 'Presente' : 'Assente' }}
                    </span>
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
                    <i class="fas fa-layer-group"></i> Plotone
                </div>
                <div class="info-value">{{ $militare->plotone->nome ?? 'Non assegnato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-building"></i> Polo
                </div>
                <div class="info-value">{{ $militare->polo->nome ?? 'Non assegnato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-user-tag"></i> Ruolo
                </div>
                <div class="info-value">{{ $militare->ruoloCertificati->nome ?? 'Non specificato' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-briefcase"></i> Mansione
                </div>
                <div class="info-value">{{ $militare->mansione->nome ?? 'Non specificata' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-calendar-plus"></i> Creato il
                </div>
                <div class="info-value">{{ $militare->created_at ? $militare->created_at->format('d/m/Y') : '-' }}</div>
            </li>
            <li class="info-item">
                <div class="info-label">
                    <i class="fas fa-calendar-check"></i> Aggiornato il
                </div>
                <div class="info-value">{{ $militare->updated_at ? $militare->updated_at->format('d/m/Y') : '-' }}</div>
            </li>
        </ul>
    </div>
</div>

<style>
    .info-card {
        border-radius: 10px;
        overflow: hidden;
        background-color: white;
        box-shadow: var(--shadow-sm);
        height: 100%;
        transition: box-shadow 0.2s ease;
    }
    
    .info-card:hover {
        box-shadow: var(--shadow-md);
    }
    
    .info-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #EDF2F7;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .info-card-title {
        font-weight: 600;
        color: var(--navy);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-card-title i {
        color: var(--gold);
    }
    
    .info-card-body {
        padding: 1.5rem;
    }
    
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .info-item {
        padding: 0.75rem 0;
        border-bottom: 1px solid #EDF2F7;
        display: flex;
        align-items: flex-start;
    }
    
    .info-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .info-item:first-child {
        padding-top: 0;
    }
    
    .info-label {
        flex: 0 0 120px;
        color: #718096;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .info-label i {
        width: 16px;
        text-align: center;
        color: var(--navy);
        opacity: 0.7;
    }
    
    .info-value {
        flex-grow: 1;
        color: #2D3748;
        font-weight: 500;
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
</style> 
