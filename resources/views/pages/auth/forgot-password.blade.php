<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
            @csrf

            <!-- Email Address -->
            <x-input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
                icon="o-envelope"
            />

            <div class="mt-2">
                <x-button label="Email password reset link" type="submit" class="btn-primary w-full" data-test="email-password-reset-link-button" />
            </div>
        </form>

        <div class="space-x-1 text-center text-sm text-base-content/70">
            <span>{{ __('Or, return to') }}</span>
            <a href="{{ route('login') }}" class="text-primary font-semibold hover:underline" wire:navigate>{{ __('log in') }}</a>
        </div>
    </div>
</x-layouts::auth>
