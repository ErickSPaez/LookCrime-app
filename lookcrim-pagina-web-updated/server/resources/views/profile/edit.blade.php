@extends('layouts.legacy')

@section('titulo_browser', __('auth.profile') . ' - LookCrim')

@section('conteudo')
    <div class="main-website-interior user-management-panel">
        @php
            $lcFallbackUrl = url('/');
            $lcPrevious = url()->previous();
            $lcBackUrl = (is_string($lcPrevious) && str_starts_with($lcPrevious, url('/')))
                ? $lcPrevious
                : $lcFallbackUrl;
        @endphp

        <div class="lc-title-row">
            <a class="lc-back-link" href="{{ $lcBackUrl }}">&larr; {{ __('pages.back') }}</a>
            <h1 class="font-title-for-customization register-title" style="margin:0;text-align:center;">{{ __('auth.profile') }}</h1>
            <span class="lc-back-link lc-back-link--spacer" aria-hidden="true">&larr; {{ __('pages.back') }}</span>
        </div>

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

        @if (session('status') === 'email-change-sent')
            <div class="alert-success" style="margin-bottom:12px;">{{ __('We sent a verification link to your new email address.') }}</div>
        @endif

        @if (session('status') === 'email-change-same')
            <div class="alert alert-danger" style="margin-bottom:12px;">{{ __('The new email must be different from your current email.') }}</div>
        @endif

        @if (session('status') === 'email-change-confirmed')
            <div class="alert-success" style="margin-bottom:12px;">{{ __('Your email address was changed successfully.') }}</div>
        @endif

        @if (session('status') === 'email-change-invalid')
            <div class="alert alert-danger" style="margin-bottom:12px;">{{ __('The email change link is invalid or expired.') }}</div>
        @endif
        {{-- 1) Profile information --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <h5 class="card-title" style="margin-bottom:8px;">{{ __('Profile Information') }}</h5>
                <p class="card-text" style="margin-bottom:16px;">{{ __("Update your account's profile name.") }}</p>

                <div class="form-group">
                <label>{{ __('Current Name') }}</label>
                <input
                    type="text"
                    class="form-control"
                    value="{{ $user->name }}"
                    disabled
            >
        </div>

        <form method="post" action="{{ route('profile.update') }}">
            @csrf
            @method('patch')

            <div class="form-group">
                <label for="name">{{ __('New Name') }}</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}"
                    required
                    autocomplete="name"
                >

                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-lookcrim">{{ __('Save') }}</button>
        </form>
    </div>

        {{-- 2) Change email --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <h5 class="card-title" style="margin-bottom:8px;">{{ __('Change Email') }}</h5>
                <p class="card-text" style="margin-bottom:16px;">
                    {{ __('Enter your current password and your new email address. We will send a confirmation link to the new email.') }}
                </p>

                <div class="form-group">
                    <label>{{ __('Current Email') }}</label>
                    <input
                        type="email"
                        class="form-control"
                        value="{{ $user->email }}"
                        disabled
                    >
                </div>

                @if ($user->pending_email)
                    <div class="alert-success" style="margin-bottom:12px;">
                        {{ __('Pending email confirmation:') }} {{ $user->pending_email }}
                    </div>
                @endif

                <form method="post" action="{{ route('profile.email-change') }}">
                    @csrf

                    <div class="form-group">
                        <label for="email_change_current_password">{{ __('Current Password') }}</label>
                        <input
                            id="email_change_current_password"
                            name="current_password"
                            type="password"
                            class="form-control {{ $errors->emailChange->has('current_password') ? 'is-invalid' : '' }}"
                            autocomplete="current-password"
                            required
                        >
                        @if ($errors->emailChange->has('current_password'))
                            <div class="invalid-feedback">{{ $errors->emailChange->first('current_password') }}</div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="email_change_email">{{ __('New Email') }}</label>
                        <input
                            id="email_change_email"
                            name="email"
                            type="email"
                            class="form-control {{ $errors->emailChange->has('email') ? 'is-invalid' : '' }}"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                        >
                        @if ($errors->emailChange->has('email'))
                            <div class="invalid-feedback">{{ $errors->emailChange->first('email') }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-lookcrim">{{ __('Send verification link') }}</button>
                </form>
            </div>
        </div>

        {{-- 3) Update password --}}
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

        {{-- 4) Delete account --}}
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

                    <button type="submit" class="btn btn-delete">{{ __('Delete Account') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection