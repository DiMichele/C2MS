{{--
|--------------------------------------------------------------------------
| Header del profilo militare
|--------------------------------------------------------------------------
| Mostra le informazioni principali del militare in un header accattivante
| @version 1.0
| @author Michele Di Gennaro
--}}

<div class="profile-header">
    <div class="profile-header-content">
        <div class="profile-avatar" onclick="openPhotoModal({{ $militare->id }}, '{{ $militare->cognome }} {{ $militare->nome }}')">
            <img src="{{ route('militare.foto', $militare->id) }}" 
                 alt="Foto di {{ $militare->cognome }} {{ $militare->nome }}"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="profile-avatar-fallback" style="display: none;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="photo-overlay">
                <i class="fas fa-camera"></i>
            </div>
        </div>
        
        <div class="profile-info">
            <h1 class="profile-name">
                {{ $militare->grado->nome ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
            </h1>
            
            <div class="profile-grado">
                @if($militare->plotone)
                    <i class="fas fa-users me-1"></i>{{ $militare->plotone->nome }}
                @endif
                @if($militare->polo)
                    @if($militare->plotone) • @endif
                    <i class="fas fa-map-marker-alt me-1"></i>{{ $militare->polo->nome }}
                @endif
                @if($militare->mansione)
                    @if($militare->plotone || $militare->polo) • @endif
                    <i class="fas fa-briefcase me-1"></i>{{ $militare->mansione->nome }}
                @endif
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="profile-status {{ $militare->isPresente() ? 'present' : 'absent' }}">
                    <i class="fas fa-{{ $militare->isPresente() ? 'check-circle' : 'times-circle' }} me-1"></i>
                    {{ $militare->isPresente() ? 'Presente' : 'Assente' }}
                </div>
                
                @if($militare->valutazioni->count() > 0)
                <div class="d-flex align-items-center text-white">
                    <i class="fas fa-star me-1"></i>
                    <span class="fw-bold">{{ $militare->media_valutazioni }}</span>
                    <small class="ms-1 opacity-75">({{ $militare->valutazioni->count() }} val.)</small>
                </div>
                @endif
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="{{ route('militare.edit', $militare->id) }}" class="btn btn-light btn-sm">
                <i class="fas fa-edit me-1"></i>Modifica
            </a>
        </div>
    </div>
</div> 
