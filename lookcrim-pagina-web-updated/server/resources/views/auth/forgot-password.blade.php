<x-guest-layout>
    <x-lc-toast :message="session('status')" type="success" />

    <form method="POST" action="{{ route('password.email') }}" style="margin-top:4px;">
        @csrf

        <div class="login-container">
            <div style="font-size:13px;color:#6b7280;margin-bottom:12px;">
                {{ __('Forgot your password? No problem. Enter your email and we will send you a password reset link.') }}
            </div>

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="login-label" />
                <x-text-input
                    id="email"
                    type="email"
                    name="email"
                    :value="old('email')"
                    required
                    autofocus
                    autocomplete="username"
                    class="login-field"
                />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;">
                <a style="font-size:12px;color:#374151;text-decoration:underline;" href="{{ route('login') }}">
                    {{ __('Back to login') }}
                </a>

                <x-primary-button class="btn-primary">
                    {{ __('Email Password Reset Link') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
