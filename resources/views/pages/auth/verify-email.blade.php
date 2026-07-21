<x-layouts::auth :title="__('Email verification')">
    <div class="mt-4 flex flex-col gap-6">
        <p class="text-center text-base-content/80 text-sm">
            {{ __('Please verify your email address by clicking on the link we just emailed to you.') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <p class="text-center font-medium text-success text-sm">
                {{ __('A new verification link has been sent to the email address you provided during registration.') }}
            </p>
        @endif

        <div class="flex flex-col items-center justify-between space-y-3">
            <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                @csrf
                <x-button label="{{ __('Resend verification email') }}" type="submit" class="btn-primary w-full" />
            </form>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <x-button label="{{ __('Log out') }}" type="submit" class="btn-ghost btn-sm w-full text-error" data-test="logout-button" />
            </form>
        </div>
    </div>
</x-layouts::auth>
