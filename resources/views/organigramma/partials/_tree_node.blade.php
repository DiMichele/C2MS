{{-- 
    Partial per renderizzare un nodo dell'albero gerarchico
    Richiede: $node (array con id, name, type, children, militari_count)
--}}
@php
    $hasChildren = !empty($node['children']);
    $typeObj = $node['type'] ?? null;
    $iconColor = $typeObj?->color ?? '#0A2342';
    $icon = $typeObj?->icon ?? 'fa-building';
    $typeName = $typeObj?->name ?? 'Unità';
@endphp

<li class="tree-item">
    @if($hasChildren)
    <span class="toggle-children" title="Espandi/Comprimi">
        <i class="fas fa-chevron-down"></i>
    </span>
    @endif
    
    <div class="tree-node" 
         data-unit-id="{{ $node['id'] }}"
         data-name="{{ $node['name'] }}"
         data-type="{{ $typeName }}"
         data-count="{{ $node['militari_count'] ?? 0 }}">
        <div class="node-icon" style="background-color: {{ $iconColor }}">
            <i class="fas {{ $icon }}"></i>
        </div>
        <div>
            <div class="node-name">{{ $node['name'] }}</div>
            <div class="node-type">
                {{ $typeName }}
                @if(!empty($node['code']))
                    <span class="ms-1 text-muted">({{ $node['code'] }})</span>
                @endif
            </div>
        </div>
        @if(($node['militari_count'] ?? 0) > 0)
        <span class="node-count">
            <i class="fas fa-users"></i>
            {{ $node['militari_count'] }}
        </span>
        @endif
    </div>
    
    @if($hasChildren)
    <ul class="tree-children">
        @foreach($node['children'] as $child)
            @include('organigramma.partials._tree_node', ['node' => $child])
        @endforeach
    </ul>
    @endif
</li>
