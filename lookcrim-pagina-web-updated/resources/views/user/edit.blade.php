@extends('layouts.legacy')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('Edit User') }} #{{ $user->id }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:10px;">
        <div style="display:flex;justify-content:flex-end;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 18px 0;">
            <a class="btn btn-lookcrim-white btn-sm" href="{{ route('users-list') }}">{{ __('pages.back') }}</a>
        </div>

        @if (session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

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
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input class="form-input" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>
                        <input class="form-input" type="text" name="nickname" value="{{ old('nickname', $user->nickname) }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Institution') }}</label>
                        <input class="form-input" type="text" name="institution" value="{{ old('institution', $user->institution) }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select class="form-input" name="role" id="role-select">
                            @php $selectedRole = old('role', $user->roles->first()->name ?? 'user'); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $role)) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ __('pages.permissions_from_role') }}</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('pages.city_name') }}</label>
                        <select class="form-input" name="city_id" required>
                            <option value="" disabled {{ old('city_id', $user->city_id) ? '' : 'selected' }}>—</option>
                            @foreach($cities as $city)
                                @php $selectedCityId = old('city_id', $user->city_id); @endphp
                                <option value="{{ $city->id }}" {{ (string)$selectedCityId === (string)$city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn-lookcrim" type="submit">{{ __('Save') }}</button>
                    <a href="{{ route('users-list') }}" class="btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
