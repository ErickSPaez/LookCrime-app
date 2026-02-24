@extends('layouts.legacy')

@section('titulo_browser', __('auth.profile') . ' - LookCrim')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('auth.profile') }}</h1>
        <hr class="interior-title-line register-line-title" style="margin-bottom:18px;">

        @if (session('status') === 'profile-updated')
            <div class="alert-success" style="margin-bottom:12px;">{{ __('Saved.') }}</div>
        @endif
        @if (session('status') === 'password-updated')
            <div class="alert-success" style="margin-bottom:12px;">{{ __('Saved.') }}</div>
        @endif
        @if (session('status') === 'verification-link-sent')
            <div class="alert-success" style="margin-bottom:12px;">{{ __('A new verification link has been sent to your email address.') }}</div>
        @endif

        {{-- 1) Profile information --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <h5 class="card-title" style="margin-bottom:8px;">{{ __('Profile Information') }}</h5>
                <p class="card-text" style="margin-bottom:16px;">{{ __("Update your account's profile information and email address.") }}</p>

                <form id="send-verification" method="post" action="{{ route('verification.send') }}" style="display:none;">
                    @csrf
                </form>

                <form method="post" action="{{ route('profile.update') }}">
                    @csrf
                    @method('patch')

                    <div class="form-group">
                        <label for="name">{{ __('Name') }}</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}"
                            required
                            autocomplete="name"
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="email">{{ __('Email') }}</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}"
                            required
                            autocomplete="username"
                        >
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror

                        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                            <small class="form-text text-muted" style="margin-top:8px;">
                                {{ __('Your email address is unverified.') }}
                                <button type="submit" form="send-verification" class="btn btn-link" style="padding:0;vertical-align:baseline;">{{ __('Click here to re-send the verification email.') }}</button>
                            </small>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-lookcrim">{{ __('Save') }}</button>
                </form>
            </div>
        </div>

        {{-- 2) Update password --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <h5 class="card-title" style="margin-bottom:8px;">{{ __('Update Password') }}</h5>
                <p class="card-text" style="margin-bottom:16px;">{{ __('Ensure your account is using a long, random password to stay secure.') }}</p>

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    @method('put')

                    <div class="form-group">
                        <label for="update_password_current_password">{{ __('Current Password') }}</label>
                        <input
                            id="update_password_current_password"
                            name="current_password"
                            type="password"
                            class="form-control {{ $errors->updatePassword->has('current_password') ? 'is-invalid' : '' }}"
                            autocomplete="current-password"
                        >
                        @if ($errors->updatePassword->has('current_password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="update_password_password">{{ __('New Password') }}</label>
                        <input
                            id="update_password_password"
                            name="password"
                            type="password"
                            class="form-control {{ $errors->updatePassword->has('password') ? 'is-invalid' : '' }}"
                            autocomplete="new-password"
                        >
                        @if ($errors->updatePassword->has('password'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
                        <input
                            id="update_password_password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="form-control {{ $errors->updatePassword->has('password_confirmation') ? 'is-invalid' : '' }}"
                            autocomplete="new-password"
                        >
                        @if ($errors->updatePassword->has('password_confirmation'))
                            <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-lookcrim">{{ __('Save') }}</button>
                </form>
            </div>
        </div>

        {{-- 3) Delete account --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <h5 class="card-title" style="margin-bottom:8px;">{{ __('Delete Account') }}</h5>
                <p class="card-text" style="margin-bottom:16px;">{{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}</p>

                <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('{{ __('Are you sure you want to delete your account?') }}');">
                    @csrf
                    @method('delete')

                    <div class="form-group">
                        <label for="delete_password">{{ __('Password') }}</label>
                        <input
                            id="delete_password"
                            name="password"
                            type="password"
                            class="form-control {{ $errors->userDeletion->has('password') ? 'is-invalid' : '' }}"
                            placeholder="{{ __('Password') }}"
                        >
                        @if ($errors->userDeletion->has('password'))
                            <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-danger">{{ __('Delete Account') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
