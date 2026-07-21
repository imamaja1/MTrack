<x-layouts::auth :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirm with passkey')"
            :loading-label="__('Confirming...')"
            :separator="__('Or confirm with password')"
        />

        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-4">
            @csrf

            <x-input
                name="password"
                :label="__('Password')"
                type="password"
                required
                placeholder="Password"
                icon="o-key"
            />

            <div class="mt-2">
                <x-button label="{{ __('Confirm') }}" type="submit" class="btn-primary w-full" data-test="confirm-password-button" />
            </div>
        </form>
    </div>
</x-layouts::auth>
