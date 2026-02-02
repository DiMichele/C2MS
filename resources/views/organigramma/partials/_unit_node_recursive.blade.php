{{-- 
    Partial ricorsivo per renderizzare un nodo unità e tutti i suoi figli fino ai militari
    Richiede: $unit, $allUnits, $depth
--}}
@php
    $children = $allUnits->where('parent_id', $unit->id);
    $hasChildren = $children->count() > 0;
    $militari = $unit->militari ?? collect();
    $hasMilitari = $militari->count() > 0;
    $hasContent = $hasChildren || $hasMilitari;
    
    // Calcola il totale militari nel sottoalbero
    $totalMilitariInSubtree = $militari->count();
    $idsToCheck = [$unit->id];
    while (!empty($idsToCheck)) {
        $currentId = array_shift($idsToCheck);
        $childUnits = $allUnits->where('parent_id', $currentId);
        foreach ($childUnits as $childUnit) {
            $totalMilitariInSubtree += $childUnit->militari->count();
            $idsToCheck[] = $childUnit->id;
        }
    }
    
    $typeColor = $unit->type->color ?? '#0A2342';
    $typeIcon = $unit->type->icon ?? 'fa-building';
@endphp

<div class="unit-node depth-{{ $depth }}" data-unit-id="{{ $unit->id }}" data-name="{{ strtolower($unit->name) }}">
    <div class="unit-node-header">
        @if($hasContent)
        <div class="expand-icon">
            <i class="fas fa-chevron-right"></i>
        </div>
        @else
        <div class="expand-icon" style="opacity: 0.3;">
            <i class="fas fa-minus"></i>
        </div>
        @endif
        
        <div class="unit-node-icon" style="background-color: {{ $typeColor }}">
            <i class="fas {{ $typeIcon }}"></i>
        </div>
        
        <div class="unit-node-info">
            <div class="unit-node-name">{{ $unit->name }}</div>
            <div class="unit-node-type">{{ $unit->type->name ?? 'Unità' }}</div>
        </div>
        
        @if($totalMilitariInSubtree > 0)
        <div class="unit-node-count">
            <i class="fas fa-users"></i>
            <span>{{ $totalMilitariInSubtree }}</span>
        </div>
        @endif
    </div>
    
    @if($hasChildren)
    <div class="unit-children">
        @foreach($children as $child)
            @include('organigramma.partials._unit_node_recursive', [
                'unit' => $child, 
                'allUnits' => $allUnits,
                'depth' => $depth + 1
            ])
        @endforeach
    </div>
    @endif
    
    @if($hasMilitari && !$hasChildren)
    {{-- Mostra militari solo se non ci sono sotto-unità (foglie dell'albero) --}}
    <div class="militari-list">
        @foreach($militari->sortBy(fn($m) => ($m->grado->ordine ?? 999) . $m->cognome . $m->nome) as $militare)
        <div class="militare-item" 
             data-name="{{ strtolower($militare->cognome . ' ' . $militare->nome) }}">
            <div class="militare-avatar">
                {{ strtoupper(substr($militare->nome ?? '', 0, 1)) }}{{ strtoupper(substr($militare->cognome ?? '', 0, 1)) }}
            </div>
            <div class="militare-info">
                <div class="militare-name">
                    {{ $militare->grado->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                </div>
                <div class="militare-grado">{{ $militare->grado->nome ?? 'N/D' }}</div>
            </div>
            @if($militare->polo_id)
            <span class="militare-badge ufficio" title="Ufficio: {{ $militare->polo->nome ?? 'N/D' }}">
                <i class="fas fa-briefcase me-1"></i>{{ Str::limit($militare->polo->nome ?? 'Ufficio', 15) }}
            </span>
            @endif
            @if(method_exists($militare, 'isPresente'))
                @if($militare->isPresente())
                <span class="militare-badge presente">Presente</span>
                @else
                <span class="militare-badge assente">Assente</span>
                @endif
            @endif
        </div>
        @endforeach
    </div>
    @elseif($hasMilitari && $hasChildren)
    {{-- Se ci sono sia sotto-unità che militari diretti, mostra militari come sezione --}}
    <div class="unit-children">
        <div class="unit-node depth-{{ $depth + 1 }}">
            <div class="unit-node-header">
                <div class="expand-icon">
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="unit-node-icon" style="background-color: #6B7280">
                    <i class="fas fa-user-friends"></i>
                </div>
                <div class="unit-node-info">
                    <div class="unit-node-name">Militari Diretti</div>
                    <div class="unit-node-type">Assegnati a {{ $unit->name }}</div>
                </div>
                <div class="unit-node-count">
                    <i class="fas fa-users"></i>
                    <span>{{ $militari->count() }}</span>
                </div>
            </div>
            <div class="militari-list">
                @foreach($militari->sortBy(fn($m) => ($m->grado->ordine ?? 999) . $m->cognome . $m->nome) as $militare)
                <div class="militare-item" 
                     data-name="{{ strtolower($militare->cognome . ' ' . $militare->nome) }}">
                    <div class="militare-avatar">
                        {{ strtoupper(substr($militare->nome ?? '', 0, 1)) }}{{ strtoupper(substr($militare->cognome ?? '', 0, 1)) }}
                    </div>
                    <div class="militare-info">
                        <div class="militare-name">
                            {{ $militare->grado->abbreviazione ?? '' }} {{ $militare->cognome }} {{ $militare->nome }}
                        </div>
                        <div class="militare-grado">{{ $militare->grado->nome ?? 'N/D' }}</div>
                    </div>
                    @if($militare->polo_id)
                    <span class="militare-badge ufficio" title="Ufficio: {{ $militare->polo->nome ?? 'N/D' }}">
                        <i class="fas fa-briefcase me-1"></i>{{ Str::limit($militare->polo->nome ?? 'Ufficio', 15) }}
                    </span>
                    @endif
                    @if(method_exists($militare, 'isPresente'))
                        @if($militare->isPresente())
                        <span class="militare-badge presente">Presente</span>
                        @else
                        <span class="militare-badge assente">Assente</span>
                        @endif
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
