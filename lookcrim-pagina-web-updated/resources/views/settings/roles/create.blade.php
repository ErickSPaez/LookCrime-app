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
                    <div class="lc-permissions-grid">
                        @foreach($permissionsList as $perm)
                            <label class="perm-item">
                                <input type="checkbox" name="permissions[{{ $perm }}]" value="1" {{ old('permissions.'.$perm) ? 'checked' : '' }}>
                                <span>{{ ucwords(str_replace('_',' ', $perm)) }}</span>
                            </label>
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
