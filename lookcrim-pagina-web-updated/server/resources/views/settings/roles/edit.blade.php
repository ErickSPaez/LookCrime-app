@extends('layouts.legacy')

@section('conteudo')
<div class="main-website-interior user-management-panel">
    <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('pages.edit_role') }}: {{ $role->name }}</h1>
    <hr class="interior-title-line register-line-title" style="margin-bottom:10px;">
    <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 18px 0;">
        <a class="btn btn-lookcrim-white btn-sm" href="{{ route('settings.roles.index') }}">{{ __('pages.back') }}</a>
    </div>

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
                        @php
                            $preferredOrder = ['registers','management','roles','cities'];
                            $permissionGroupsArr = $permissionGroups instanceof \Illuminate\Support\Collection
                                ? $permissionGroups->all()
                                : (array) $permissionGroups;
                            $categories = array_values(array_unique(array_merge($preferredOrder, array_keys($permissionGroupsArr))));
                        @endphp
                        @foreach($categories as $category)
                            @continue(!isset($permissionGroupsArr[$category]))
                            @php $group = $permissionGroupsArr[$category]; @endphp
                            <div class="col-lg-6 col-md-6 lc-perm-group" style="margin-bottom:16px;">
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

                                        // Cross-city permissions (city restriction bypass)
                                        $renderPerm('view_any_city_registers', ['data-lc-parent' => 'view_page_registers']);
                                        $renderPerm('create_any_city_registers', ['data-lc-parent' => 'view_any_city_registers']);
                                        $renderPerm('edit_any_city_registers', ['data-lc-parent' => 'view_any_city_registers']);
                                        $renderPerm('delete_any_city_registers', ['data-lc-parent' => 'view_any_city_registers']);

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
                                @elseif($category === 'cities')
                                    @php
                                        $renderPerm('view_page_settings_city');
                                        $renderPerm('create_city', ['data-lc-parent' => 'view_page_settings_city']);
                                        $renderPerm('edit_city', ['data-lc-parent' => 'view_page_settings_city']);
                                        $renderPerm('delete_city', ['data-lc-parent' => 'view_page_settings_city']);
                                    @endphp
                                    @if(($isChecked)('view_any_city'))
                                        <input type="hidden" name="permissions[view_any_city]" value="1">
                                    @endif
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
                <a href="{{ route('settings.roles.index') }}" class="btn-secondary">{{ __('pages.cancel') }}</a>
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
#perm-view_page_settings_city + label,
#perm-view_own_registers + label,
#perm-view_any_registers + label,
#perm-view_any_city_registers + label{
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
        ['view_own_registers','view_any_registers','view_any_city_registers'].forEach(n => {
            const el = document.getElementById('perm-'+n);
            if (el) el.checked = false;
        });
    }
    lcSetChildrenEnabled('view_own_registers', viewPageRegisters && lcIsChecked('view_own_registers'));
    lcSetChildrenEnabled('view_any_registers', viewPageRegisters && lcIsChecked('view_any_registers'));
    lcSetChildrenEnabled('view_any_city_registers', viewPageRegisters && lcIsChecked('view_any_city_registers'));

    // Management
    const viewPageManagement = lcIsChecked('view_page_management');
    lcSetChildrenEnabled('view_page_management', viewPageManagement);

    // Roles
    const viewPageRoles = lcIsChecked('view_page_settings_roles');
    lcSetChildrenEnabled('view_page_settings_roles', viewPageRoles);

    // Cities
    const viewPageCity = lcIsChecked('view_page_settings_city');
    lcSetChildrenEnabled('view_page_settings_city', viewPageCity);
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
