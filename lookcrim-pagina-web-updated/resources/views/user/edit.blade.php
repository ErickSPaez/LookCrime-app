@extends('layouts.legacy')

@section('conteudo')
    <div class="container">
        <h1 class="mb-3-form-title">{{ __('Edit User') }} #{{ $user->id }}</h1>

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
                        <label class="form-label">{{ __('New Password (leave blank to keep)') }}</label>
                        <input class="form-input" type="password" name="password">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Confirm Password') }}</label>
                        <input class="form-input" type="password" name="password_confirmation">
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
                    <div class="form-group" style="flex:0 0 auto; align-items:center;">
                        <label class="form-label">{{ __('Admin') }}</label>
                        <input type="checkbox" name="admin" value="1" {{ $user->admin ? 'checked' : '' }}>
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
