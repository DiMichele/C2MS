{{-- Partial per la tabella dei permessi - Stile SUGECO --}}
<div class="permissions-table-wrapper">
    <table class="sugeco-table">
        <thead>
            <tr>
                <th style="min-width: 180px; text-align: left; padding-left: 1.5rem;">
                    <i class="fas fa-user-tag me-2"></i>Ruolo
                </th>
                
                @foreach($categoryData['permissions'] as $pageName => $pageData)
                <th title="{{ $pageData['display_name'] }}" style="min-width: 100px;">
                    {{ Str::limit(strtoupper($pageData['display_name']), 16) }}
                </th>
                @endforeach
                
                <th style="width: 70px;">
                    <i class="fas fa-trash-alt"></i>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($roles as $role)
            @php
                $isProtectedRole = ($role->name === 'amministratore');
            @endphp
            <tr>
                <td style="text-align: left; padding-left: 1.5rem;">
                    <div class="d-flex flex-column">
                        <strong style="color: var(--navy);">{{ $role->display_name }}</strong>
                        @if($isProtectedRole)
                        <span class="role-badge protected mt-1">
                            <i class="fas fa-lock"></i> Protetto
                        </span>
                        @endif
                        @if($role->is_global)
                        <span class="role-badge global mt-1">
                            <i class="fas fa-globe"></i> Globale
                        </span>
                        @endif
                    </div>
                </td>
                
                @foreach($categoryData['permissions'] as $pageName => $pageData)
                <td>
                    <form action="{{ route('admin.roles.permissions.update', $role) }}" 
                          method="POST" 
                          class="permission-form"
                          data-role-id="{{ $role->id }}"
                          data-protected="{{ $isProtectedRole ? 'true' : 'false' }}">
                        @csrf
                        
                        @php
                            $viewPerm = collect($pageData['items'])->firstWhere('type', 'read');
                            $editPerm = collect($pageData['items'])->firstWhere('type', 'write');
                        @endphp
                        
                        <div class="permission-icons">
                            @if($viewPerm)
                            <div>
                                <input class="permission-checkbox" 
                                       type="checkbox" 
                                       name="permissions[]" 
                                       value="{{ $viewPerm->id }}"
                                       id="perm-{{ $role->id }}-{{ $viewPerm->id }}"
                                       data-role-id="{{ $role->id }}"
                                       @if($role->permissions->contains($viewPerm->id)) checked @endif
                                       @if($isProtectedRole) disabled @endif
                                       style="display: none;">
                                <label class="icon-permission {{ $isProtectedRole ? 'protected-permission' : '' }}" 
                                       for="perm-{{ $role->id }}-{{ $viewPerm->id }}" 
                                       title="{{ $viewPerm->display_name ?? 'Visualizza' }}"
                                       style="{{ $isProtectedRole ? 'cursor: not-allowed; opacity: 0.6;' : '' }}">
                                    <i class="fas fa-eye" style="color: {{ $role->permissions->contains($viewPerm->id) || $isProtectedRole ? '#0dcaf0' : '#ccc' }};"></i>
                                </label>
                            </div>
                            @endif
                            
                            @if($editPerm)
                            <div>
                                <input class="permission-checkbox" 
                                       type="checkbox" 
                                       name="permissions[]" 
                                       value="{{ $editPerm->id }}"
                                       id="perm-{{ $role->id }}-{{ $editPerm->id }}"
                                       data-role-id="{{ $role->id }}"
                                       @if($role->permissions->contains($editPerm->id)) checked @endif
                                       @if($isProtectedRole) disabled @endif
                                       style="display: none;">
                                <label class="icon-permission {{ $isProtectedRole ? 'protected-permission' : '' }}" 
                                       for="perm-{{ $role->id }}-{{ $editPerm->id }}" 
                                       title="{{ $editPerm->display_name ?? 'Modifica' }}"
                                       style="{{ $isProtectedRole ? 'cursor: not-allowed; opacity: 0.6;' : '' }}">
                                    <i class="fas fa-edit" style="color: {{ $role->permissions->contains($editPerm->id) || $isProtectedRole ? '#ffc107' : '#ccc' }};"></i>
                                </label>
                            </div>
                            @endif
                            
                            @if(!$viewPerm && !$editPerm)
                                @foreach($pageData['items'] as $singlePerm)
                                <div>
                                    <input class="permission-checkbox" 
                                           type="checkbox" 
                                           name="permissions[]" 
                                           value="{{ $singlePerm->id }}"
                                           id="perm-{{ $role->id }}-{{ $singlePerm->id }}"
                                           data-role-id="{{ $role->id }}"
                                           @if($role->permissions->contains($singlePerm->id)) checked @endif
                                           @if($isProtectedRole) disabled @endif
                                           style="display: none;">
                                    <label class="icon-permission {{ $isProtectedRole ? 'protected-permission' : '' }}" 
                                           for="perm-{{ $role->id }}-{{ $singlePerm->id }}" 
                                           title="{{ $singlePerm->display_name ?? $singlePerm->name }}"
                                           style="{{ $isProtectedRole ? 'cursor: not-allowed; opacity: 0.6;' : '' }}">
                                        <i class="fas fa-check-circle" style="color: {{ $role->permissions->contains($singlePerm->id) || $isProtectedRole ? '#198754' : '#ccc' }};"></i>
                                    </label>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </form>
                </td>
                @endforeach
                
                <td>
                    @php
                        $isSystemRole = ($role->name === 'amministratore');
                        $usersCount = $role->users()->count();
                    @endphp
                    
                    @if(!$isSystemRole)
                        <form action="{{ route('admin.roles.destroy', $role) }}" 
                              method="POST" 
                              class="d-inline delete-role-form"
                              data-role-name="{{ $role->display_name }}"
                              data-users-count="{{ $usersCount }}">
                            @csrf
                            @method('DELETE')
                            <button type="button" 
                                    class="action-btn delete delete-role-btn" 
                                    title="Elimina ruolo">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @else
                        <span class="text-muted" title="Non eliminabile" style="opacity: 0.5;">
                            <i class="fas fa-lock"></i>
                        </span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
