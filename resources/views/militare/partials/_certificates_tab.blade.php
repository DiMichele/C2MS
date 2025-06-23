{{--
|--------------------------------------------------------------------------
| Dashboard Statistiche Certificazioni
|--------------------------------------------------------------------------
| Analisi completa dello stato certificativo senza elenchi dettagliati
| @version 6.0 - Statistics Only
| @author Michele Di Gennaro
--}}

<div class="tab-pane fade" id="certificates" role="tabpanel" aria-labelledby="certificates-tab">
    
    @php
        $totalCert = $militare->certificatiLavoratori->count();
        $validCert = $militare->certificatiLavoratori->filter(fn($c) => !$c->isScaduto())->count();
        $expiringCert = $militare->certificatiLavoratori->filter(fn($c) => $c->isInScadenza())->count();
        $expiredCert = $militare->certificatiLavoratori->filter(fn($c) => $c->isScaduto())->count();
        
        $totalIdon = $militare->idoneita->count();
        $validIdon = $militare->idoneita->filter(fn($i) => !$i->isScaduto())->count();
        $expiringIdon = $militare->idoneita->filter(fn($i) => $i->isInScadenza())->count();
        $expiredIdon = $militare->idoneita->filter(fn($i) => $i->isScaduto())->count();
        
        // Calcolo percentuali
        $certValidPerc = $totalCert > 0 ? round(($validCert / $totalCert) * 100) : 0;
        $idonValidPerc = $totalIdon > 0 ? round(($validIdon / $totalIdon) * 100) : 0;
    @endphp



    <!-- Statistiche Principali -->
    <div class="stats-grid">
        <!-- Certificati Lavoratori -->
        <div class="stat-card">
            <div class="stat-header">
                <h6 class="stat-title">Certificati Lavoratori</h6>
                <span class="stat-percentage {{ $certValidPerc >= 80 ? 'excellent' : ($certValidPerc >= 60 ? 'good' : 'warning') }}">
                    {{ $certValidPerc }}%
                </span>
            </div>
            <div class="stat-details">
                <div class="detail-row">
                    <span class="detail-label">Totali</span>
                    <span class="detail-value">{{ $totalCert }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Validi</span>
                    <span class="detail-value valid">{{ $validCert }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">In scadenza</span>
                    <span class="detail-value warning">{{ $expiringCert }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Scaduti</span>
                    <span class="detail-value expired">{{ $expiredCert }}</span>
                </div>

            </div>
        </div>

        <!-- Idoneità -->
        <div class="stat-card">
            <div class="stat-header">
                <h6 class="stat-title">Idoneità</h6>
                <span class="stat-percentage {{ $idonValidPerc >= 80 ? 'excellent' : ($idonValidPerc >= 60 ? 'good' : 'warning') }}">
                    {{ $idonValidPerc }}%
                </span>
            </div>
            <div class="stat-details">
                <div class="detail-row">
                    <span class="detail-label">Totali</span>
                    <span class="detail-value">{{ $totalIdon }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Valide</span>
                    <span class="detail-value valid">{{ $validIdon }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">In scadenza</span>
                    <span class="detail-value warning">{{ $expiringIdon }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Scadute</span>
                    <span class="detail-value expired">{{ $expiredIdon }}</span>
                </div>

            </div>
        </div>


    </div>

    <!-- Riepilogo Azioni -->
    @if(($expiredCert + $expiredIdon + $expiringCert + $expiringIdon) > 0)
    <div class="action-summary">
        <h6 class="action-title">Azioni Richieste</h6>
        <div class="action-items">
            @if($expiredCert + $expiredIdon > 0)
            <div class="action-item urgent">
                <span class="action-icon">⚠️</span>
                <span class="action-text">
                    <strong>{{ $expiredCert + $expiredIdon }} certificazioni scadute</strong> - Rinnovo urgente richiesto
                </span>
            </div>
            @endif
            @if($expiringCert + $expiringIdon > 0)
            <div class="action-item warning">
                <span class="action-icon">📅</span>
                <span class="action-text">
                    <strong>{{ $expiringCert + $expiringIdon }} certificazioni in scadenza</strong> - Pianificare rinnovo
                </span>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

<style>


    /* Grid Statistiche */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-top: 1rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .stat-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--navy);
        margin: 0;
    }
    
    .stat-percentage {
        font-size: 1.5rem;
        font-weight: 700;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        color: white;
    }
    
    .stat-percentage.excellent {
        background: #28a745;
    }
    
    .stat-percentage.good {
        background: #17a2b8;
    }
    
    .stat-percentage.warning {
        background: #ffc107;
        color: #212529;
    }
    

    
    .stat-details {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f8f9fa;
    }
    
    .detail-row:last-child {
        border-bottom: none;
    }
    
    .detail-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .detail-value {
        font-size: 1rem;
        font-weight: 600;
        color: var(--navy);
    }
    
    .detail-value.valid {
        color: #28a745;
    }
    
    .detail-value.warning {
        color: #ffc107;
    }
    
    .detail-value.expired {
        color: #dc3545;
    }

    /* Riepilogo Azioni */
    .action-summary {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 1.5rem;
    }
    
    .action-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--navy);
        margin-bottom: 1rem;
    }
    
    .action-items {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    .action-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: 8px;
        border-left: 4px solid;
    }
    
    .action-item.urgent {
        background: rgba(220, 53, 69, 0.05);
        border-left-color: #dc3545;
    }
    
    .action-item.warning {
        background: rgba(255, 193, 7, 0.05);
        border-left-color: #ffc107;
    }
    
    .action-item.info {
        background: rgba(23, 162, 184, 0.05);
        border-left-color: #17a2b8;
    }
    
    .action-icon {
        font-size: 1.5rem;
        flex-shrink: 0;
    }
    
    .action-text {
        font-size: 0.95rem;
        color: #495057;
        line-height: 1.4;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .status-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .action-item {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }
    }
</style> 
