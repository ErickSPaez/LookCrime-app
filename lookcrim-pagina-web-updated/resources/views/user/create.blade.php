@extends('layouts.legacy')

@section('conteudo')
    <div class="container">
        <h1 class="mb-3-form-title">{{ __('Create User') }}</h1>

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
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Name') }}</label>
                        <input class="form-input" type="text" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Email') }}</label>
                        <input class="form-input" type="email" name="email" value="{{ old('email') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <small class="form-text text-muted">{{ __('The user will receive an email to choose their password.') }}</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Nickname') }}</label>
                        <input class="form-input" type="text" name="nickname" value="{{ old('nickname') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Institution') }}</label>
                        <input class="form-input" type="text" name="institution" value="{{ old('institution') }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" style="width:100%">
                        <label class="form-label">{{ __('Role') }}</label>
                        <select class="form-input" name="role" id="role-select">
                            @php $selectedRole = old('role', 'user'); @endphp
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ $selectedRole === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ', $role)) }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">{{ __('pages.permissions_from_role') }}</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn-lookcrim" type="submit">{{ __('Create') }}</button>
                    <a href="{{ route('users-list') }}" class="btn-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
