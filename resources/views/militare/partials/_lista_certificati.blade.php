<!-- Lista dei certificati -->
<div class="certificati-lista">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-certificate me-2"></i> Certificati</h5>
            <button class="btn btn-sm btn-outline-primary" id="toggleCertificatiForm">
                <i class="fas fa-plus-circle me-1"></i> Nuovo
            </button>
        </div>
        <div class="card-body">
            @if(count($certificati) > 0)
                <div class="certificati-grid">
                    @foreach($certificati as $certificato)
                        <div class="certificato-item">
                            <div class="certificato-header {{ strtolower($certificato->tipo_certificato) }}">
                                <div class="certificato-tipo">
                                    <i class="fas {{ $certificato->tipo_certificato === 'Medico' ? 'fa-file-medical' : 
                                        ($certificato->tipo_certificato === 'Permesso' ? 'fa-file-contract' : 
                                        ($certificato->tipo_certificato === 'Congedo' ? 'fa-calendar-alt' : 
                                        ($certificato->tipo_certificato === 'Convalescenza' ? 'fa-procedures' : 'fa-file-alt'))) }} me-1"></i>
                                    {{ $certificato->tipo_certificato }}
                                </div>
                                <div class="certificato-stato 
                                    {{ \Carbon\Carbon::parse($certificato->data_fine)->isPast() ? 'scaduto' : 'attivo' }}">
                                    {{ \Carbon\Carbon::parse($certificato->data_fine)->isPast() ? 'Scaduto' : 'Attivo' }}
                                </div>
                            </div>
                            <div class="certificato-content">
                                <div class="certificato-date">
                                    <span>
                                        <i class="fas fa-calendar-day me-1"></i> 
                                        {{ \Carbon\Carbon::parse($certificato->data_inizio)->format('d/m/Y') }} 
                                        - {{ \Carbon\Carbon::parse($certificato->data_fine)->format('d/m/Y') }}
                                    </span>
                                </div>
                                @if($certificato->note)
                                    <div class="certificato-note">
                                        {{ $certificato->note }}
                                    </div>
                                @endif
                                <div class="certificato-actions">
                                    @if($certificato->file_path)
                                        <a href="{{ Storage::url($certificato->file_path) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                            <i class="fas fa-file-download"></i> Visualizza
                                        </a>
                                    @endif
                                    <form action="{{ route('certificati.destroy', $certificato->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Sei sicuro di voler eliminare questo certificato?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <p>Nessun certificato registrato</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleCertificatiForm');
        const certificatiForm = document.querySelector('.certificati-form');
        
        // Nascondi il form all'inizio
        certificatiForm.style.display = 'none';
        
        toggleBtn.addEventListener('click', function() {
            if (certificatiForm.style.display === 'none') {
                certificatiForm.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-minus-circle me-1"></i> Chiudi';
            } else {
                certificatiForm.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i> Nuovo';
            }
        });
    });
</script> 
