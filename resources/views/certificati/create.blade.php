@extends('layouts.app')

@section('title', 'Carica Certificato')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Nuovo Certificato</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('certificati.store') }}" method="POST" id="certificatoForm">
                        @csrf
                        <input type="hidden" name="militare_id" value="{{ $certificato->militare_id }}">
                        <input type="hidden" name="tipo" value="{{ $certificato->tipo }}">
                        <input type="hidden" name="file_path" id="file_path">

                        <div class="mb-3">
                            <label for="data_ottenimento" class="form-label">Data Ottenimento</label>
                            <input type="date" class="form-control" id="data_ottenimento" name="data_ottenimento" required>
                        </div>

                        <div class="mb-3">
                            <label for="data_scadenza" class="form-label">Data Scadenza (opzionale - calcolata automaticamente)</label>
                            <input type="date" class="form-control" id="data_scadenza" name="data_scadenza">
                            <small class="text-muted">
                                @if(str_contains($certificato->tipo, 'corsi_lavoratori'))
                                    La scadenza predefinita è di 5 anni dalla data di ottenimento
                                @else
                                    La scadenza predefinita è di 1 anno dalla data di ottenimento
                                @endif
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="file" class="form-label">File Certificato (opzionale)</label>
                            <input type="file" class="form-control" id="file" accept=".pdf">
                            <div id="uploadProgress" class="progress mt-2 d-none">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ url()->previous() }}" class="btn btn-secondary">Annulla</a>
                            <button type="submit" class="btn btn-primary">Salva Certificato</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('certificatoForm');
    const fileInput = document.getElementById('file');
    const filePathInput = document.getElementById('file_path');
    const progressBar = document.querySelector('#uploadProgress .progress-bar');
    const progressContainer = document.getElementById('uploadProgress');
    const dataOttenimentoInput = document.getElementById('data_ottenimento');
    const dataScadenzaInput = document.getElementById('data_scadenza');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Gestione upload file
    fileInput.addEventListener('change', async function(e) {
        if (!this.files.length) return;

        const file = this.files[0];
        const formData = new FormData();
        formData.append('file', file);

        progressContainer.classList.remove('d-none');
        progressBar.style.width = '0%';

        try {
            const response = await fetch('/certificati/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                filePathInput.value = data.path;
                progressBar.style.width = '100%';
                progressBar.classList.remove('bg-danger');
                progressBar.classList.add('bg-success');
            } else {
                throw new Error(data.error || 'Errore durante il caricamento');
            }
        } catch (error) {
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('bg-danger');
            alert('Errore durante il caricamento del file: ' + error.message);
        }
    });

    // Calcolo automatico data scadenza
    dataOttenimentoInput.addEventListener('change', function() {
        if (!dataScadenzaInput.value) {
            const dataOttenimento = new Date(this.value);
            const tipo = '{{ $certificato->tipo }}';
            
            // Aggiungi 5 anni per i corsi lavoratori, 1 anno per le idoneità
            if (tipo.includes('corsi_lavoratori')) {
                dataOttenimento.setFullYear(dataOttenimento.getFullYear() + 5);
            } else {
                dataOttenimento.setFullYear(dataOttenimento.getFullYear() + 1);
            }
            
            dataScadenzaInput.value = dataOttenimento.toISOString().split('T')[0];
        }
    });
});
</script>
@endpush

@endsection
