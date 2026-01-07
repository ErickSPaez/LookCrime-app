@extends('layouts.legacy')

@section('conteudo')
<div class="container">
    <h1 class="mb-3-form-title">{{ __('pages.create_role') }}</h1>

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
        <form action="{{ route('settings.roles.store') }}" method="POST">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('pages.slug') }}</label>
                    <input class="form-input" type="text" name="slug" value="{{ old('slug') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('pages.role_name') }}</label>
                    <input class="form-input" type="text" name="name" value="{{ old('name') }}" required>
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
                                @foreach($group as $perm)
                                    @php
                                        $name = $perm->name;
                                        $label = \Illuminate\Support\Facades\Lang::has('permissions.'.$name)
                                            ? __('permissions.'.$name)
                                            : ucwords(str_replace('_',' ', $name));
                                    @endphp
                                    <div class="form-check lc-perm-item">
                                        <input class="form-check-input lc-perm" id="perm-{{ $name }}" type="checkbox" name="permissions[{{ $name }}]" value="1" {{ old('permissions.'.$name) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="perm-{{ $name }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn-lookcrim" type="submit">{{ __('pages.create') }}</button>
                <a href="{{ route('settings.roles.index') }}" class="btn-secondary">{{ __('pages.back') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('pagestyles')
<style>
.lc-perm-group{min-width:280px;padding-right:16px;}
.lc-perm-item{display:flex;align-items:flex-start;gap:8px;}
.lc-perm-item .form-check-input{margin-top:2px;}
.lc-perm-actions{margin-bottom:10px;}
</style>
@endsection

@section('pagescripts')
<script>
function lcSelectAll(val){
    document.querySelectorAll('.lc-perm').forEach(cb => { cb.checked = !!val; });
}
</script>
@endsection
