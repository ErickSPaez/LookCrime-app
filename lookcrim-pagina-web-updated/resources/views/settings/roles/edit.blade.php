@extends('layouts.legacy')

@section('conteudo')
<div class="container">
    <h1 class="mb-3-form-title">{{ __('pages.edit_role') }}: {{ $role->name }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form action="{{ route('settings.roles.update', $role->name) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group" style="width:100%">
                    <label class="form-label">{{ __('pages.role_name') }}</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name', $role->nameLocalized()) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="width:100%">
                    <label class="form-label">{{ __('pages.permissions') }}</label>

                    <div class="lc-perm-actions" style="margin-bottom:8px;">
                        <button type="button" class="btn btn-sm" onclick="lcSelectAll(true)">Select all</button>
                        <button type="button" class="btn btn-sm" onclick="lcSelectAll(false)">Do not select any</button>
                    </div>

                    <div class="row">
                        @foreach($permissionGroups as $category => $group)
                            <div class="col-lg-4 col-md-6 lc-perm-group" style="margin-bottom:16px;">
                                @php
                                    $groupLabel = \Illuminate\Support\Facades\Lang::has('permissions.group.'.$category)
                                        ? __('permissions.group.'.$category)
                                        : ucwords(str_replace('_',' ', $category));
                                @endphp
                                <h5 style="font-weight:600;margin-bottom:8px;">{{ $groupLabel }}</h5>
                                @php
                                    $permMap = collect($group)->keyBy('name');
                                    $assignedKeys = old('permissions') ? array_keys(old('permissions')) : $assigned;
                                    $isChecked = function (string $name) use ($assignedKeys) {
                                        return in_array($name, $assignedKeys);
                                    };
                                    $renderPerm = function (string $name, array $attrs = []) use ($permMap, $isChecked) {
                                        if (!$permMap->has($name)) {
                                            return;
                                        }
                                        $label = \Illuminate\Support\Facades\Lang::has('permissions.'.$name)
                                            ? __('permissions.'.$name)
                                            : ucwords(str_replace('_',' ', $name));
                                        $checked = $isChecked($name);
                                        $attrHtml = '';
                                        foreach ($attrs as $k => $v) {
                                            $attrHtml .= ' ' . e($k) . '="' . e($v) . '"';
                                        }
                                        echo '<div class="form-check lc-perm-item"'.$attrHtml.'>';
                                        echo '<input class="form-check-input lc-perm" id="perm-'.e($name).'" type="checkbox" name="permissions['.e($name).']" value="1" '.($checked ? 'checked' : '').'>';
                                        echo '<label class="form-check-label" for="perm-'.e($name).'">'.e($label).'</label>';
                                        echo '</div>';
                                    };

                                    $legacyPerms = [];
                                    if ($category === 'registers') {
                                        $legacyPerms = ['create_registers', 'view_all_registers', 'edit_all_registers', 'delete_registers'];
                                    }
                                    if ($category === 'management') {
                                        $legacyPerms = [];
                                    }
                                @endphp

                                @if($category === 'registers')
                                    @php
                                        $renderPerm('view_page_registers');
                                        $renderPerm('create_own_registers', ['data-lc-parent' => 'view_page_registers']);
                                        $renderPerm('view_own_registers', ['data-lc-parent' => 'view_page_registers']);
                                        $renderPerm('edit_own_registers', ['data-lc-parent' => 'view_own_registers']);
                                        $renderPerm('delete_own_registers', ['data-lc-parent' => 'view_own_registers']);
                                        $renderPerm('view_any_registers', ['data-lc-parent' => 'view_page_registers']);
                                        $renderPerm('edit_any_registers', ['data-lc-parent' => 'view_any_registers']);
                                        $renderPerm('delete_any_registers', ['data-lc-parent' => 'view_any_registers']);

                                        foreach ($legacyPerms as $legacyName) {
                                            $renderPerm($legacyName, ['style' => 'display:none;']);
                                        }
                                    @endphp
                                @elseif($category === 'management')
                                    @php
                                        $renderPerm('view_page_management');
                                        $renderPerm('create_user', ['data-lc-parent' => 'view_page_management']);
                                        $renderPerm('edit_user', ['data-lc-parent' => 'view_page_management']);
                                        $renderPerm('ban_user', ['data-lc-parent' => 'view_page_management']);
                                        foreach ($legacyPerms as $legacyName) {
                                            $renderPerm($legacyName, ['style' => 'display:none;']);
                                        }
                                    @endphp
                                @elseif($category === 'roles')
                                    @php
                                        $renderPerm('view_page_settings_roles');
                                        $renderPerm('create_role', ['data-lc-parent' => 'view_page_settings_roles']);
                                        $renderPerm('edit_role', ['data-lc-parent' => 'view_page_settings_roles']);
                                        $renderPerm('delete_role', ['data-lc-parent' => 'view_page_settings_roles']);
                                    @endphp
                                @else
                                    @foreach($group as $perm)
                                        @php
                                            $name = $perm->name;
                                            $checked = in_array($name, $assignedKeys);
                                            $label = \Illuminate\Support\Facades\Lang::has('permissions.'.$name)
                                                ? __('permissions.'.$name)
                                                : ucwords(str_replace('_',' ', $name));
                                        @endphp
                                        <div class="form-check lc-perm-item">
                                            <input class="form-check-input lc-perm" id="perm-{{ $name }}" type="checkbox" name="permissions[{{ $name }}]" value="1" {{ $checked ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm-{{ $name }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn-lookcrim" type="submit">{{ __('pages.save') }}</button>
                <a href="{{ route('settings.roles.index') }}" class="btn-secondary">{{ __('pages.back') }}</a>
            </div>
        </form>
        @can('delete_role')
        <form action="{{ route('settings.roles.destroy', $role->name) }}" method="POST" style="display:inline-block;margin-top:12px;" onsubmit="return confirm('{{ __('pages.confirm_delete_role') }}');">
            @csrf
            @method('DELETE')
            <button class="btn-danger" type="submit">{{ __('pages.delete') }}</button>
        </form>
        @endcan
    </div>
</div>
@endsection

@section('pagestyles')
<style>
.lc-perm-group{min-width:280px;padding-right:16px;}
.lc-perm-item{display:flex;align-items:flex-start;gap:8px;}
.lc-perm-item .form-check-input{margin-top:2px;}
.lc-perm-actions{margin-bottom:10px;}
.lc-perm-item[data-lc-parent]{
    margin-left:18px;
    padding-left:10px;
    border-left:2px solid rgba(0,0,0,0.08);
}
#perm-view_page_registers + label,
#perm-view_page_management + label,
#perm-view_page_settings_roles + label,
#perm-view_own_registers + label,
#perm-view_any_registers + label{
    font-weight:600;
}
</style>
@endsection

@section('pagescripts')
<script>
function lcSelectAll(val){
    document.querySelectorAll('.lc-perm').forEach(cb => { cb.checked = !!val; });
    lcRefreshPermUI();
}

function lcSetChildrenEnabled(parentName, enabled){
    document.querySelectorAll('[data-lc-parent="'+parentName+'"] input.lc-perm').forEach(cb => {
        if (!enabled) cb.checked = false;
    });
    document.querySelectorAll('[data-lc-parent="'+parentName+'"]').forEach(el => {
        el.style.display = enabled ? '' : 'none';
    });
}

function lcIsChecked(name){
    const el = document.getElementById('perm-'+name);
    return !!(el && el.checked);
}

function lcRefreshPermUI(){
    // Registers
    const viewPageRegisters = lcIsChecked('view_page_registers');
    lcSetChildrenEnabled('view_page_registers', viewPageRegisters);
    if (!viewPageRegisters){
        ['view_own_registers','view_any_registers'].forEach(n => {
            const el = document.getElementById('perm-'+n);
            if (el) el.checked = false;
        });
    }
    lcSetChildrenEnabled('view_own_registers', viewPageRegisters && lcIsChecked('view_own_registers'));
    lcSetChildrenEnabled('view_any_registers', viewPageRegisters && lcIsChecked('view_any_registers'));

    // Management
    const viewPageManagement = lcIsChecked('view_page_management');
    lcSetChildrenEnabled('view_page_management', viewPageManagement);

    // Roles
    const viewPageRoles = lcIsChecked('view_page_settings_roles');
    lcSetChildrenEnabled('view_page_settings_roles', viewPageRoles);
}

document.addEventListener('change', function(e){
    if (e.target && e.target.classList && e.target.classList.contains('lc-perm')) {
        lcRefreshPermUI();
    }
});

document.addEventListener('DOMContentLoaded', function(){
    lcRefreshPermUI();
});
</script>
@endsection
