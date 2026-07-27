<x-layouts.auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
                icon="o-envelope"
            />

            <!-- Password -->
            <div class="relative">
                <x-input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    placeholder="Password"
                    icon="o-key"
                />

                @if (Route::has('password.request'))
                    <a class="absolute top-0 right-0 text-xs text-primary hover:underline" href="{{ route('password.request') }}" wire:navigate>
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>

            <!-- Remember Me -->
            <x-checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div class="mt-2">
                <x-button label="Masuk" type="submit" class="btn-primary w-full" data-test="login-button" />
            </div>

            <div class="relative flex items-center py-2">
                <div class="flex-grow border-t border-base-300"></div>
                <span class="flex-shrink-0 mx-4 text-base-content/50 text-xs">Atau masuk dengan</span>
                <div class="flex-grow border-t border-base-300"></div>
            </div>

            <x-button link="{{ route('auth.google') }}" no-wire-navigate class="btn-outline w-full flex items-center justify-center gap-2">
                <svg class="w-5 h-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google
            </x-button>
        </form>

        <div class="space-x-1 text-sm text-center text-base-content/70">
            <span>{{ __('Don\'t have an account?') }}</span>
            <a href="{{ route('register') }}" class="text-primary font-semibold hover:underline" wire:navigate>{{ __('Sign up') }}</a>
        </div>
    </div>
</x-layouts.auth>

