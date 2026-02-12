<x-guest-layout>
    <x-lc-toast :message="session('status')" type="success" />

    @if (session('lc_banned'))
        <div id="lc-banned-backdrop" class="lc-modal-backdrop show" role="dialog" aria-hidden="false">
            <div class="lc-modal" role="document">
                <div style="text-align:center;">
                    <img src="{{ asset('img/LookCrim-Logo1.png') }}" alt="LookCrim" style="max-width:220px;height:auto;display:block;margin:0 auto 10px;" />
                </div>
                <h3 id="lc-banned-title" style="text-align:center;">{{ __('Account banned') }}</h3>
                <p id="lc-banned-message" style="text-align:center;">
                    {{ __('Your account has been banned by an administrator. If you believe this is a mistake, contact the site administrators.') }}
                </p>
                <div class="lc-modal-actions" style="justify-content:center;">
                    <button id="lc-banned-confirm" class="lc-btn-primary" type="button">{{ __('Return to login') }}</button>
                </div>
            </div>
        </div>

        <script>
            (function(){
                const backdrop = document.getElementById('lc-banned-backdrop');
                const btn = document.getElementById('lc-banned-confirm');
                if (!backdrop || !btn) return;

                function hide() {
                    backdrop.classList.remove('show');
                    backdrop.setAttribute('aria-hidden', 'true');
                    const password = document.getElementById('password');
                    if (password) password.value = '';
                    const email = document.getElementById('email');
                    if (email) email.focus();
                }

                btn.addEventListener('click', hide);
                backdrop.addEventListener('click', function(e){
                    if (e.target === backdrop) hide();
                });
                document.addEventListener('keydown', function(e){
                    if (e.key === 'Escape') hide();
                });
            })();
        </script>
    @endif

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
