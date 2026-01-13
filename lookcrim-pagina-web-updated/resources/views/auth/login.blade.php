<x-guest-layout>
    <x-lc-toast :message="session('status')" type="success" />

    <form method="POST" action="{{ route('login') }}" style="margin-top:4px;">
        @csrf

        <div class="login-container">
            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="login-label" />
                <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" class="login-field" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div style="margin-top:12px;">
                <x-input-label for="password" :value="__('Password')" class="login-label" />
                <x-text-input id="password" type="password" name="password" required autocomplete="current-password" class="login-field" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me -->
            <div class="login-row">
                <input id="remember_me" type="checkbox" name="remember" style="width:16px;height:16px;border:1px solid #d1d5db;border-radius:4px;" />
                <label for="remember_me" style="font-size:12px;color:#374151;">{{ __('Remember me') }}</label>
            </div>

            <div class="login-actions">
                @if (Route::has('password.request'))
                    <a class="" style="font-size:12px;color:#374151;text-decoration:underline;" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif

                <x-primary-button class="btn-primary">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
