@extends('layouts.legacy')

@section('conteudo')
<div class="container">
    <h1 class="mb-3-form-title">{{ __('pages.edit_role') }}: {{ $role->slug }}</h1>

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
        <form action="{{ route('settings.roles.update', $role->slug) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">{{ __('pages.name_en') }}</label>
                    <input class="form-input" type="text" name="name_en" value="{{ old('name_en', $role->name_en) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('pages.name_pt') }}</label>
                    <input class="form-input" type="text" name="name_pt" value="{{ old('name_pt', $role->name_pt) }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group" style="width:100%">
                    <label class="form-label">{{ __('pages.permissions') }}</label>
                    <div class="lc-permissions-grid">
                        @foreach($permissionsList as $perm)
                            @php $isChecked = old("permissions.$perm", $perms[$perm] ?? false); @endphp
                            <label class="perm-item">
                                <input type="checkbox" name="permissions[{{ $perm }}]" value="1" {{ $isChecked ? 'checked' : '' }}>
                                <span>{{ ucwords(str_replace('_',' ', $perm)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn-lookcrim" type="submit">{{ __('pages.save') }}</button>
                <a href="{{ route('settings.roles.index') }}" class="btn-secondary">{{ __('pages.back') }}</a>
            </div>
        </form>
        <form action="{{ route('settings.roles.destroy', $role->slug) }}" method="POST" style="display:inline-block;margin-top:12px;" onsubmit="return confirm('{{ __('pages.confirm_delete_role') }}');">
            @csrf
            @method('DELETE')
            <button class="btn-danger" type="submit">{{ __('pages.delete') }}</button>
        </form>
    </div>
</div>
@endsection
