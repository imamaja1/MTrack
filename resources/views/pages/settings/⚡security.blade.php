<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;
use Laravel\Passkeys\Actions\DeletePasskey;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Mary\Traits\Toast;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules, Toast;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    #[Locked]
    public bool $canManagePasskeys;

    #[Locked]
    public array $passkeys = [];

    public bool $showDeleteModal = false;

    #[Locked]
    public ?int $deletingPasskeyId = null;

    #[Locked]
    public string $deletingPasskeyName = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }

        $this->canManagePasskeys = Features::canManagePasskeys();

        if ($this->canManagePasskeys) {
            $this->loadPasskeys();
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->success(__('Password updated.'));
    }

    /**
     * Load the user's passkeys.
     */
    public function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()
            ->select(['id', 'name', 'credential', 'created_at', 'last_used_at'])
            ->latest()
            ->get()
            ->map(fn ($passkey) => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->toArray();
    }

    /**
     * Show the delete confirmation modal.
     */
    public function confirmDelete(int $passkeyId): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($passkeyId);

        $this->deletingPasskeyId = $passkey->id;
        $this->deletingPasskeyName = $passkey->name;
        $this->showDeleteModal = true;
    }

    /**
     * Delete the passkey.
     */
    public function deletePasskey(DeletePasskey $deletePasskey): void
    {
        if (! $this->deletingPasskeyId) {
            return;
        }

        $passkey = auth()->user()->passkeys()->findOrFail($this->deletingPasskeyId);

        $deletePasskey(auth()->user(), $passkey);

        $this->closeDeleteModal();
        $this->loadPasskeys();
        $this->success(__('Passkey removed.'));
    }

    /**
     * Close the delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPasskeyId = null;
        $this->deletingPasskeyName = '';
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
        $this->success(__('2FA disabled.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-4 space-y-4">
            <x-input
                wire:model="current_password"
                label="{{ __('Current password') }}"
                type="password"
                required
                icon="o-key"
            />
            <x-input
                wire:model="password"
                label="{{ __('New password') }}"
                type="password"
                required
                icon="o-key"
            />
            <x-input
                wire:model="password_confirmation"
                label="{{ __('Confirm password') }}"
                type="password"
                required
                icon="o-key"
            />

            <div class="flex items-center gap-4 pt-2">
                <x-button label="{{ __('Save') }}" type="submit" class="btn-primary" data-test="update-password-button" />
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-10 border-t border-base-300 pt-8">
                <h3 class="text-lg font-bold text-base-content">{{ __('Two-factor authentication') }}</h3>
                <p class="text-sm text-base-content/70 mb-4">{{ __('Manage your two-factor authentication settings') }}</p>

                <div class="flex flex-col w-full space-y-4 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <p class="text-sm text-base-content/70">
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </p>

                            <div class="flex justify-start">
                                <x-button label="{{ __('Disable 2FA') }}" class="btn-error" wire:click="disable" />
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <p class="text-sm text-base-content/70">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </p>

                            <x-button
                                label="{{ __('Enable 2FA') }}"
                                class="btn-primary"
                                onclick="two_factor_setup_modal.showModal()"
                                wire:click="$dispatch('start-two-factor-setup')"
                            />

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        @if ($canManagePasskeys)
            <section class="mt-10 border-t border-base-300 pt-8">
                <h3 class="text-lg font-bold text-base-content">{{ __('Passkeys') }}</h3>
                <p class="text-sm text-base-content/70 mb-4">{{ __('Manage your passkeys for passwordless sign-in') }}</p>

                <div class="mt-4 flex flex-col w-full space-y-4 text-sm" wire:cloak>
                    <div class="border rounded-xl border-base-300 overflow-hidden bg-base-100">
                        @forelse ($passkeys as $passkey)
                            <div class="flex items-center justify-between p-4 {{ ! $loop->last ? 'border-b border-base-200' : '' }}">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-base-200">
                                        <x-icon name="o-key" class="size-5 text-base-content/70" />
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium tracking-tight text-base-content">{{ $passkey['name'] }}</p>
                                            @if ($passkey['authenticator'])
                                                <x-badge value="{{ $passkey['authenticator'] }}" class="badge-neutral badge-sm" />
                                            @endif
                                        </div>
                                        <p class="text-base-content/60 text-xs">
                                            {{ __('Added :time', ['time' => $passkey['created_at_diff']]) }}
                                            @if ($passkey['last_used_at_diff'])
                                                <span class="opacity-50 mx-1">/</span>
                                                {{ __('Last used :time', ['time' => $passkey['last_used_at_diff']]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <x-button
                                    icon="o-trash"
                                    class="btn-ghost btn-sm text-error"
                                    wire:click="confirmDelete({{ $passkey['id'] }})"
                                />
                            </div>
                        @empty
                            <div class="p-8 text-center">
                                <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-base-200">
                                    <x-icon name="o-key" class="size-6 text-base-content/50" />
                                </div>
                                <p class="font-medium text-base-content">{{ __('No passkeys yet') }}</p>
                                <p class="text-sm text-base-content/60 mt-1">{{ __('Add a passkey to sign in without a password') }}</p>
                            </div>
                        @endforelse
                    </div>

                    <x-passkey-registration />
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    <x-modal wire:model="showDeleteModal" title="{{ __('Remove passkey') }}">
        <p class="text-sm text-base-content/70">
            {{ __('Are you sure you want to remove the passkey ":name"? You will no longer be able to use it to sign in.', ['name' => $deletingPasskeyName]) }}
        </p>

        <x-slot:actions>
            <x-button label="{{ __('Cancel') }}" wire:click="closeDeleteModal" class="btn-ghost" />
            <x-button label="{{ __('Remove passkey') }}" wire:click="deletePasskey" class="btn-error" />
        </x-slot:actions>
    </x-modal>
</section>
