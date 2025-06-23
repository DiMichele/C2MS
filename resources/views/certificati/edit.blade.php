@extends('layouts.app')

@section('title', 'Modifica Certificato')

@section('styles')
<style>
    /* Stili identici alla pagina biografica */
    .profile-photo {
        max-width: 160px;
        max-height: 160px;
        border-radius: 8px;
        border: 2px solid #e9ecef;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        display: inline-block;
        background: transparent;
        flex-shrink: 0;
    }

    .profile-photo:hover {
        border-color: #007bff;
        box-shadow: 0 2px 8px rgba(0,123,255,0.15);
    }

    .profile-image {
        max-width: 160px;
        max-height: 160px;
        width: auto;
        height: auto;
        display: block;
        border-radius: 6px;
    }

    .photo-fallback {
        width: 160px;
        height: 160px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 6px;
    }

    .photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s ease;
        border-radius: 6px;
    }

    .profile-photo:hover .photo-overlay {
        opacity: 1;
    }

    .photo-overlay i {
        color: white;
        font-size: 1.5rem;
    }

    .profile-title {
        font-size: 2rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        line-height: 1.1;
    }

    .profile-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 160px;
    }

    .profile-actions-right {
        flex-shrink: 0;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 1.5rem;
            align-items: flex-start;
        }
        
        .profile-photo {
            max-width: 120px;
            max-height: 120px;
        }
        
        .profile-image {
            max-width: 120px;
            max-height: 120px;
        }
        
        .photo-fallback {
            width: 120px;
            height: 120px;
        }
        
        .profile-info {
            height: auto;
            min-height: 120px;
            justify-content: center;
        }
        
        .profile-title {
            font-size: 1.75rem;
        }
    }

    @media (max-width: 768px) {
        .profile-photo {
            max-width: 100px;
            max-height: 100px;
        }
        
        .profile-image {
            max-width: 100px;
            max-height: 100px;
        }
        
        .photo-fallback {
            width: 100px;
            height: 100px;
            font-size: 2.5rem;
        }
        
        .profile-info {
            min-height: 100px;
        }
        
        .profile-title {
            font-size: 1.5rem;
        }
        
        .btn-group .btn {
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
        }
    }

    /* Container */
    .institutional-container {
        padding: 1rem 0;
    }

    /* Area Contenuto */
    .institutional-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
    }

    .content-wrapper {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    /* Form styles */
    .form-section {
        padding: 2.5rem;
        margin-bottom: 0;
        border-bottom: 1px solid #e9ecef;
        position: relative;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        position: relative;
    }

    .section-icon {
        width: 35px;
        height: 35px;
        background: #0a2342;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #0a2342;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .section-line {
        flex: 1;
        height: 1px;
        background: #dee2e6;
        margin-left: 1rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-control:focus {
        border-color: #0a2342;
        box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.1);
    }

    .form-select {
        border: 2px solid #e9ecef;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    .form-select:focus {
        border-color: #0a2342;
        box-shadow: 0 0 0 0.2rem rgba(10, 35, 66, 0.1);
    }

    /* Upload area */
    .upload-area {
        border: 3px dashed #dee2e6;
        border-radius: 16px;
        padding: 3.5rem 2rem;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
        transition: all 0.4s ease;
        cursor: pointer;
        position: relative;
        overflow: hidden;
    }

    .upload-area::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(10, 35, 66, 0.1), transparent);
        transition: left 0.6s ease;
    }

    .upload-area:hover::before {
        left: 100%;
    }

    .upload-area:hover {
        border-color: #0a2342;
        background: linear-gradient(135deg, #e8f4fd 0%, #d4edda 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(10, 35, 66, 0.15);
    }

    .upload-area.dragover {
        border-color: #0a2342;
        background: linear-gradient(135deg, #cfe2ff 0%, #b3d9ff 100%);
        transform: scale(1.02);
    }

    .upload-icon {
        font-size: 4rem;
        color: #0a2342;
        margin-bottom: 1.5rem;
        opacity: 0.7;
        transition: all 0.3s ease;
    }

    .upload-area:hover .upload-icon {
        opacity: 1;
        transform: scale(1.1);
    }

    .upload-text {
        color: #0a2342;
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 0.8rem;
        font-family: 'Oswald', sans-serif;
        letter-spacing: 0.3px;
    }

    .upload-hint {
        color: #6c757d;
        font-size: 1rem;
        font-style: italic;
    }

    .current-file {
        background: linear-gradient(135deg, #e8f5e8 0%, #d4edda 100%);
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.1);
        transition: all 0.3s ease;
    }

    .current-file:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(40, 167, 69, 0.15);
    }

    .current-file i {
        font-size: 2.5rem;
        color: #28a745;
        background: rgba(40, 167, 69, 0.1);
        padding: 1rem;
        border-radius: 10px;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .current-file-info h5 {
        margin: 0 0 0.5rem 0;
        color: #155724;
        font-weight: 600;
        font-size: 1.1rem;
        font-family: 'Oswald', sans-serif;
        letter-spacing: 0.3px;
    }

    .current-file-info p {
        margin: 0 0 1rem 0;
        color: #495057;
        font-size: 0.95rem;
        font-style: italic;
    }

    /* Buttons */
    .btn-primary {
        background: #0a2342;
        border-color: #0a2342;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: #1e3a5f;
        border-color: #1e3a5f;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(10, 35, 66, 0.3);
    }

    .btn-success {
        background: #28a745 !important;
        border-color: #28a745 !important;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-success:hover {
        background: #218838 !important;
        border-color: #218838 !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-secondary {
        background: #6c757d;
        border-color: #6c757d;
        padding: 0.75rem 2rem;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-secondary:hover {
        background: #5a6268;
        border-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
    }

    .btn-danger {
        background: #dc3545;
        border-color: #dc3545;
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-danger:hover {
        background: #c82333;
        border-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    }

    /* Modal Styles */
    .institutional-modal {
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(10, 35, 66, 0.3);
        border: none;
        overflow: hidden;
    }

    .institutional-modal .modal-header {
        background: linear-gradient(135deg, #0a2342 0%, #1e3a5f 100%);
        color: white;
        border-bottom: none;
        padding: 1.5rem 2rem;
    }

    .institutional-modal .modal-title {
        font-family: 'Oswald', sans-serif;
        font-weight: 600;
        font-size: 1.3rem;
        letter-spacing: 0.3px;
    }

    .institutional-modal .btn-close {
        filter: invert(1);
        opacity: 0.8;
    }

    .institutional-modal .btn-close:hover {
        opacity: 1;
    }

    .upload-section {
        padding: 1rem 0;
    }

    .upload-info {
        margin-top: 1rem;
        text-align: center;
    }

    .preview-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e9ecef;
    }

    .preview-container {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 12px;
        border: 2px solid #e9ecef;
    }

    .photo-preview {
        max-width: 120px;
        max-height: 120px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .preview-info h6 {
        margin: 0 0 1rem 0;
        color: #0a2342;
        font-family: 'Oswald', sans-serif;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }
        
        .military-profile-section {
            flex-direction: column;
            text-align: center;
        }
        
        .action-buttons {
            flex-direction: column;
            width: 100%;
        }
        
        .form-section {
            padding: 1.5rem;
        }
        
        .section-header {
            flex-direction: column;
            text-align: center;
            gap: 0.8rem;
        }
        
        .section-line {
            display: none;
        }
        
        .upload-area {
            padding: 2.5rem 1.5rem;
        }
    }
</style>
@endsection

@section('content')
<div class="institutional-container">
    <!-- Header identico alla pagina biografica -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <div class="profile-photo me-4" onclick="openPhotoModal({{ $militare->id }}, '{{ $militare->cognome }} {{ $militare->nome }}')">
                <img src="{{ route('militare.foto', $militare->id) }}?t={{ time() }}" 
                     alt="Foto di {{ $militare->cognome }} {{ $militare->nome }}"
                     class="profile-image"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="photo-fallback" style="display: none;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="photo-overlay">
                    <i class="fas fa-camera"></i>
                </div>
            </div>
            
            <div class="profile-info">
                <h1 class="profile-title mb-1">{{ $militare->grado->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</h1>
            </div>
        </div>
        
        <div class="profile-actions-right">
            <div class="btn-group">
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Indietro
                </a>
                @if($certificato->file_path)
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                    <i class="fas fa-trash me-1"></i>Elimina
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="institutional-content">
        <div class="content-wrapper">
            <form action="{{ route('certificati.update', $certificato->id) }}" method="POST" enctype="multipart/form-data" id="certificatoForm">
                @csrf
                @method('PUT')

                <!-- Sezione Informazioni Certificato -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h3 class="section-title">Informazioni Certificato</h3>
                        <div class="section-line"></div>
                    </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_ottenimento" class="form-label">Data Ottenimento *</label>
                            <input type="date" 
                                   class="form-control @error('data_ottenimento') is-invalid @enderror" 
                                   id="data_ottenimento" 
                                   name="data_ottenimento" 
                                   value="{{ old('data_ottenimento', $certificato->data_ottenimento instanceof \DateTime ? $certificato->data_ottenimento->format('Y-m-d') : $certificato->data_ottenimento) }}" 
                                   required>
                            @error('data_ottenimento')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="data_scadenza" class="form-label">Data Scadenza</label>
                            <input type="date" 
                                   class="form-control @error('data_scadenza') is-invalid @enderror" 
                                   id="data_scadenza" 
                                   name="data_scadenza" 
                                   value="{{ old('data_scadenza', $certificato->data_scadenza instanceof \DateTime ? $certificato->data_scadenza->format('Y-m-d') : $certificato->data_scadenza) }}">
                            @error('data_scadenza')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Se non specificata, sarà calcolata automaticamente (1 anno dalla data di ottenimento)</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="ente_rilascio" class="form-label">Ente di Rilascio</label>
                    <input type="text" 
                           class="form-control @error('ente_rilascio') is-invalid @enderror" 
                           id="ente_rilascio" 
                           name="ente_rilascio" 
                           value="{{ old('ente_rilascio', $certificato->ente_rilascio) }}" 
                           placeholder="Es. Comando Militare, Ente Formativo...">
                    @error('ente_rilascio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

                <!-- Sezione File Certificato -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <h3 class="section-title">File Certificato</h3>
                        <div class="section-line"></div>
                    </div>

                @if($certificato->file_path)
                <div class="current-file">
                    <i class="fas fa-file-pdf"></i>
                    <div class="current-file-info">
                        <h5>File Attuale</h5>
                        <p>{{ basename($certificato->file_path) }}</p>
                        <div class="mt-2">
                            <a href="{{ Storage::url($certificato->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-eye me-1"></i>Visualizza
                            </a>
                            <a href="{{ Storage::url($certificato->file_path) }}" download class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download me-1"></i>Scarica
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                <div class="upload-area" onclick="document.getElementById('file').click()">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">
                        @if($certificato->file_path)
                            Clicca qui per sostituire il certificato
                        @else
                            Clicca qui per caricare il certificato
                        @endif
                    </div>
                    <div class="upload-hint">Oppure trascina il file qui (PDF, JPG, PNG - Max 10MB)</div>
                    <input type="file" 
                           class="d-none @error('file') is-invalid @enderror" 
                           id="file" 
                           name="file" 
                           accept=".pdf,.jpg,.jpeg,.png">
                </div>
                
                @error('file')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
                
                <div id="file-info" class="mt-3" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-file me-2"></i>
                        <span id="file-name"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pulsanti Azione -->
            <div class="text-center">
                <button type="submit" class="btn btn-primary me-3">
                    <i class="fas fa-save me-2"></i>Aggiorna Certificato
                </button>
                <a href="{{ url()->previous() }}" class="btn btn-secondary me-3">
                    <i class="fas fa-times me-2"></i>Annulla
                </a>
                @if($certificato->file_path)
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash me-2"></i>Elimina Certificato
                </button>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Form nascosto per eliminazione -->
<form id="deleteForm" action="{{ route('certificati.destroy', $certificato->id) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
// Auto-calcolo data scadenza
document.getElementById('data_ottenimento').addEventListener('change', function() {
    const dataOttenimento = new Date(this.value);
    const dataScadenzaField = document.getElementById('data_scadenza');
    
    if (!dataScadenzaField.value && dataOttenimento) {
        // Aggiungi 1 anno alla data di ottenimento
        const dataScadenza = new Date(dataOttenimento);
        dataScadenza.setFullYear(dataScadenza.getFullYear() + 1);
        
        // Formatta la data per l'input
        const year = dataScadenza.getFullYear();
        const month = String(dataScadenza.getMonth() + 1).padStart(2, '0');
        const day = String(dataScadenza.getDate()).padStart(2, '0');
        
        dataScadenzaField.value = `${year}-${month}-${day}`;
    }
});

// Gestione upload file
const fileInput = document.getElementById('file');
const uploadArea = document.querySelector('.upload-area');
const fileInfo = document.getElementById('file-info');
const fileName = document.getElementById('file-name');

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        const file = this.files[0];
        fileName.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        fileInfo.style.display = 'block';
        uploadArea.style.borderColor = '#28a745';
        uploadArea.style.background = '#f8fff9';
    }
});

function removeFile() {
    fileInput.value = '';
    fileInfo.style.display = 'none';
    uploadArea.style.borderColor = '#dee2e6';
    uploadArea.style.background = '#f8f9fa';
}

// Drag & Drop
uploadArea.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

uploadArea.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
});

// Validazione form
document.getElementById('certificatoForm').addEventListener('submit', function(e) {
    const dataOttenimento = new Date(document.getElementById('data_ottenimento').value);
    const dataScadenza = new Date(document.getElementById('data_scadenza').value);
    const oggi = new Date();
    
    // Rimuovi ore per confronto solo date
    oggi.setHours(0, 0, 0, 0);
    dataOttenimento.setHours(0, 0, 0, 0);
    dataScadenza.setHours(0, 0, 0, 0);
    
    if (dataOttenimento > oggi) {
        e.preventDefault();
        alert('La data di ottenimento non può essere futura!');
        return false;
    }
    
    if (document.getElementById('data_scadenza').value && dataScadenza <= dataOttenimento) {
        e.preventDefault();
        alert('La data di scadenza deve essere successiva alla data di ottenimento!');
        return false;
    }
});

// Conferma eliminazione
function confirmDelete() {
    if (confirm('Sei sicuro di voler eliminare questo certificato? Questa azione non può essere annullata.')) {
        document.getElementById('deleteForm').submit();
    }
}

// Funzione per aprire modal foto (se presente)
function openPhotoModal(militareId, nomeCompleto) {

}
</script>
@endsection
