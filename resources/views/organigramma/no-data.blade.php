@extends('layouts.app')
@section('title', 'Organigramma - SUGECO')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-sitemap fa-5x text-muted"></i>
                    </div>
                    
                    @if(isset($activeUnit))
                    <h3 class="mb-3" style="color: var(--navy);">
                        {{ $activeUnit->name }}
                    </h3>
                    @endif
                    
                    <p class="text-muted lead mb-4">
                        {{ $message ?? 'Nessun dato disponibile per questa unità.' }}
                    </p>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="{{ route('organigramma.index') }}" class="btn btn-primary">
                            <i class="fas fa-sitemap me-2"></i>
                            Organigramma Gerarchico
                        </a>
                        <a href="{{ route('gerarchia.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>
                            Gestione Gerarchia
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
