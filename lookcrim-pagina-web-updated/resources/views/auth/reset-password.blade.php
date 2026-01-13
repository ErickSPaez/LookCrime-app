<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}" style="margin-top:4px;">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="login-container">
            <div style="font-size:13px;color:#6b7280;margin-bottom:12px;">
                {{ __('Set a new password for your account.') }}
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="login-label" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email', $request->email)"
                    required
                    autofocus
                    autocomplete="username"
                    class="login-field"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div style="margin-top:12px;">
                <x-input-label for="password" :value="__('Password')" class="login-label" />
                <x-text-input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="login-field"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div style="margin-top:12px;">
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="login-label" />
                <x-text-input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="login-field"
                />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div style="display:flex;justify-content:flex-end;margin-top:16px;">
                <x-primary-button class="btn-primary">
                    {{ __('Reset Password') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
