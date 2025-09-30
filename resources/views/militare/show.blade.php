{{--
|--------------------------------------------------------------------------
| Pagina di dettaglio del militare
|--------------------------------------------------------------------------
| Visualizza tutti i dettagli del militare con le relative informazioni e certificati
| @version 1.0
| @author Michele Di Gennaro
--}}

@extends('layouts.app')
@section('title', 'Dettaglio Militare - C2MS')

@section('styles')
<style>
    .profile-header {
        background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
        color: white;
        box-shadow: 0 10px 25px rgba(10, 35, 66, 0.15);
    }
    
    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 30%;
        background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDMwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTEwMCAwTDEwMCAzMDBMMCAwTDEwMCAwWiIgZmlsbD0iI0JGOUQ1RSIgZmlsbC1vcGFjaXR5PSIwLjEiLz48cGF0aCBkPSJNMjAwIDMwMEwyMDAgMEwzMDAgMzAwTDIwMCAzMDBaIiBmaWxsPSIjQkY5RDVFIiBmaWxsLW9wYWNpdHk9IjAuMSIvPjxjaXJjbGUgY3g9IjE1MCIgY3k9IjE1MCIgcj0iNTAiIHN0cm9rZT0iI0JGOUQ1RSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2Utb3BhY2l0eT0iMC4zIi8+PC9zdmc+') no-repeat right center;
        background-size: cover;
        opacity: 0.1;
        z-index: 0;
    }
    
    .profile-header-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 2rem;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        color: var(--navy);
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        border: 4px solid rgba(255, 255, 255, 0.3);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .profile-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    }
    
    .photo-overlay {
        position: absolute;
        top: 2px;
        left: 2px;
        right: 2px;
        bottom: 2px;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s ease;
        color: white;
        border-radius: 6px;
    }
    
    .profile-avatar:hover .photo-overlay {
        opacity: 1;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        position: absolute;
        top: 0;
        left: 0;
    }
    
    .profile-avatar-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: 0;
        left: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
        border-radius: 50%;
    }
    
    .profile-info {
        flex-grow: 1;
    }
    
    .profile-name {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .profile-grado {
        font-size: 1.1rem;
        opacity: 0.9;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .profile-status {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .profile-status.present {
        background-color: rgba(52, 103, 81, 0.3);
        border: 2px solid rgba(52, 103, 81, 0.5);
    }
    
    .profile-status.absent {
        background-color: rgba(172, 14, 40, 0.3);
        border: 2px solid rgba(172, 14, 40, 0.5);
    }
    
    .profile-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
        justify-content: flex-end;
    }
    
    .profile-actions .btn {
        border-radius: 25px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .profile-actions .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.15);
    }
    
    .info-card {
        border-radius: 15px;
        overflow: hidden;
        background-color: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        height: 100%;
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
    }
    
    .info-card:hover {
        box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        transform: translateY(-2px);
    }
    
    .info-card-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #EDF2F7;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
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
        border-bottom: 1px solid #EDF2F7;
        display: flex;
        align-items: flex-start;
        transition: all 0.2s ease;
    }
    
    .info-item:hover {
        background-color: rgba(10, 35, 66, 0.02);
        border-radius: 8px;
        margin: 0 -0.5rem;
        padding-left: 1.5rem;
        padding-right: 1.5rem;
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
        color: #718096;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
    }
    
    .info-label i {
        width: 18px;
        text-align: center;
        color: var(--navy);
        opacity: 0.7;
    }
    
    .info-value {
        flex-grow: 1;
        color: #2D3748;
        font-weight: 500;
        font-size: 0.95rem;
    }
    
    .certificate-card {
        border: 1px solid #EDF2F7;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        background: white;
    }
    
    .certificate-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        transform: translateY(-3px);
        border-color: var(--navy-light);
    }
    
    .certificate-card:last-child {
        margin-bottom: 0;
    }
    
    .certificate-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .certificate-title {
        font-weight: 700;
        color: #2D3748;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.05rem;
    }
    
    .certificate-title i {
        color: var(--gold);
        font-size: 1.1rem;
    }
    
    .certificate-status {
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .certificate-status.valid {
        background-color: rgba(52, 103, 81, 0.15);
        color: var(--success);
        border: 2px solid rgba(52, 103, 81, 0.3);
    }
    
    .certificate-status.expiring {
        background-color: rgba(245, 158, 11, 0.15);
        color: var(--warning);
        border: 2px solid rgba(245, 158, 11, 0.3);
    }
    
    .certificate-status.expired {
        background-color: rgba(172, 14, 40, 0.15);
        color: var(--error);
        border: 2px solid rgba(172, 14, 40, 0.3);
    }
    
    .certificate-details {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
        font-size: 0.9rem;
    }
    
    .certificate-detail {
        display: flex;
        flex-direction: column;
    }
    
    .certificate-detail-label {
        color: #718096;
        margin-bottom: 0.4rem;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .certificate-detail-value {
        color: #2D3748;
        font-weight: 600;
    }
    
    .file-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        border-radius: 15px;
        font-weight: 600;
    }
    
    .file-badge.present {
        background-color: rgba(52, 103, 81, 0.15);
        color: var(--success);
        border: 1px solid rgba(52, 103, 81, 0.3);
    }
    
    .file-badge.missing {
        background-color: rgba(172, 14, 40, 0.15);
        color: var(--error);
        border: 1px solid rgba(172, 14, 40, 0.3);
    }
    
    .notes-form {
        margin-bottom: 0;
    }
    
    .notes-textarea {
        width: 100%;
        padding: 1rem;
        border: 2px solid #E2E8F0;
        border-radius: 12px;
        resize: none;
        min-height: 140px;
        transition: all 0.3s ease;
        font-family: inherit;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .notes-textarea:focus {
        border-color: var(--navy-light);
        box-shadow: 0 0 0 4px rgba(10, 35, 66, 0.1);
        outline: none;
    }

    .save-indicator {
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 1rem 1.5rem;
        background-color: #333;
        color: white;
        border-radius: 25px;
        z-index: 1000;
        animation: slideInUp 0.4s ease;
        font-weight: 600;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .save-indicator.success {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .save-indicator.error {
        background: linear-gradient(135deg, #dc3545, #e74c3c);
    }

    @keyframes slideInUp {
        from { 
            opacity: 0; 
            transform: translateY(100px);
        }
        to { 
            opacity: 1; 
            transform: translateY(0);
        }
    }
    
    /* Miglioramenti per i tab */
    .nav-tabs {
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 0;
    }
    
    .nav-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        background: none;
        color: #6c757d;
        font-weight: 600;
        padding: 1rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        border-color: transparent;
        background-color: rgba(10, 35, 66, 0.05);
        color: var(--navy);
    }
    
    .nav-tabs .nav-link.active {
        background-color: white;
        color: var(--navy);
        border-bottom-color: var(--gold);
        font-weight: 700;
    }
    
    .tab-content {
        padding: 2rem;
        background: white;
        border-radius: 0 0 15px 15px;
    }
    
    /* Responsive improvements */
    /* Stili per l'header migliorato */
    .page-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 2rem;
        border: 1px solid #dee2e6;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .header-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--navy);
        display: flex;
        align-items: center;
    }
    
    .header-avatar {
        max-width: 160px;
        max-height: 160px;
        min-width: 120px;
        min-height: 120px;
        border-radius: 12px;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 4px solid #fff;
        box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }
    
    .header-avatar:hover {
        transform: scale(1.03);
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    
    .header-avatar img {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 8px;
    }
    
    .header-avatar-fallback {
        font-size: 4rem;
    }
    
    .header-photo-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        border-radius: 8px;
        color: white;
        font-size: 2.5rem;
    }
    
    .header-avatar:hover .header-photo-overlay {
        opacity: 1;
    }
    
    .header-subtitle {
        font-size: 1rem;
        margin-top: 0.5rem;
    }
    
    .header-actions {
        text-align: right;
    }
    
    /* Stili per le statistiche rimossi - interfaccia più pulita */

    @media (max-width: 768px) {
        .page-header {
            padding: 1.5rem;
        }
        
        .page-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
        }
        
        .header-actions {
            text-align: left;
            margin-top: 1rem;
        }
        
        .header-title {
            font-size: 1.5rem;
        }
        
        .header-avatar {
            max-width: 120px;
            max-height: 120px;
            min-width: 80px;
            min-height: 80px;
            border-radius: 10px;
        }
        
        .header-avatar img {
            border-radius: 6px;
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        
        .header-avatar-fallback {
            font-size: 3rem;
        }
        
        .header-photo-overlay {
            font-size: 2rem;
            border-radius: 6px;
        }
        
        .profile-header {
            padding: 1.5rem;
        }
        
        .profile-header-content {
            flex-direction: column;
            text-align: center;
            gap: 1.5rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            font-size: 3rem;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .profile-name {
            font-size: 1.5rem;
        }
        
        .profile-actions {
            justify-content: center;
            width: 100%;
        }
        
        .info-card-body {
            padding: 1.5rem;
        }
        
        .certificate-details {
            grid-template-columns: 1fr;
        }
    }
    
    /* Stili per il modal foto profilo */
    .current-photo-container {
        text-align: center;
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 2rem;
        background: #f8f9fa;
    }
    
    .current-photo {
        max-width: 100%;
        max-height: 300px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.3s ease;
    }
    
    .current-photo:hover {
        transform: scale(1.05);
    }
    
    .no-photo-placeholder {
        text-align: center;
        color: #6c757d;
        font-size: 3rem;
    }
    
    .no-photo-placeholder p {
        font-size: 1rem;
        margin-top: 1rem;
    }
    
    .upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 10px;
        padding: 3rem 2rem;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f8f9fa;
    }
    
    .upload-area:hover {
        border-color: var(--navy);
        background: rgba(10, 35, 66, 0.05);
    }
    
    .upload-area.dragover {
        border-color: var(--navy);
        background: rgba(10, 35, 66, 0.1);
        transform: scale(1.02);
    }
    
    .upload-area i {
        font-size: 3rem;
        color: #6c757d;
        display: block;
    }
    
    .preview-image {
        max-width: 100%;
        max-height: 250px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .modal-lg .modal-body {
        padding: 2rem;
    }
    
    /* Overlay per zoom foto */
    .photo-zoom-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        cursor: pointer;
    }
    
    .photo-zoom-overlay img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    }
    
    /* Header senza container - Design pulito */
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
    
    .profile-badge {
        align-self: flex-start;
    }
    
    .profile-badge .badge {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
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
    
    /* Stili per i pulsanti azione */
    .action-buttons-center {
        display: inline-flex;
        gap: 0.75rem;
        justify-content: center;
        margin-top: 2rem;
    }
    
    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 2px solid;
        background: white;
        cursor: pointer;
        font-size: 1rem;
        text-decoration: none;
    }
    
    .action-btn-edit {
        border-color: #ffc107;
        color: #ffc107;
    }
    
    .action-btn-edit:hover {
        background: #ffc107;
        color: #212529;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    }
    
    .action-btn-delete {
        border-color: #dc3545;
        color: #dc3545;
    }
    
    .action-btn-delete:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
    }
    
    .action-btn:active {
        transform: translateY(0);
    }
</style>
@endsection

@section('content')
<!-- Header centralizzato -->
<div class="text-center mb-5">
    <h1 class="page-title mb-5">{{ $militare->grado->sigla ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</h1>
    
    <div class="action-buttons-center">
        <a href="{{ route('anagrafica.edit', $militare->id) }}" class="action-btn action-btn-edit" data-bs-toggle="tooltip" data-bs-placement="top" title="Modifica">
            <i class="fas fa-edit"></i>
        </a>
        <button type="button" class="action-btn action-btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal" data-bs-toggle="tooltip" data-bs-placement="top" title="Elimina">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</div>



<div class="row g-4">
    <!-- Informazioni Generali -->
    <div class="col-lg-5 col-xl-4">
        @include('militare.partials._info_card')
    </div>
    
    <!-- Panoramica Certificati -->
    <div class="col-lg-7 col-xl-8">
        <div class="info-card">
            <div class="info-card-header">
                <h5 class="info-card-title">
                    <i class="fas fa-file-alt"></i> Panoramica Certificati
                </h5>
            </div>
            <div class="info-card-body">
                    @include('militare.partials._certificates_tab')
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Conferma Eliminazione</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Sei sicuro di voler eliminare <strong>{{ $militare->grado->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}</strong>?</p>
                <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Questa azione non può essere annullata e comporterà l'eliminazione di tutti i dati associati: certificati, idoneità e informazioni personali.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <form action="{{ route('anagrafica.destroy', $militare->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Elimina</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Inizializza i tooltip di Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
