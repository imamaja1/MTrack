<?php

use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Locked]
    public bool $requiresConfirmation;

    #[Locked]
    public string $qrCodeSvg = '';

    #[Locked]
    public string $manualSetupKey = '';

    public bool $showVerificationStep = false;

    public bool $setupComplete = false;

    #[Validate('required|string|size:6', onUpdate: false)]
    public string $code = '';

    /**
     * Mount the component.
     */
    public function mount(bool $requiresConfirmation): void
    {
        $this->requiresConfirmation = $requiresConfirmation;
    }

    #[On('start-two-factor-setup')]
    public function startTwoFactorSetup(): void
    {
        $enableTwoFactorAuthentication = app(EnableTwoFactorAuthentication::class);
        $enableTwoFactorAuthentication(auth()->user());

        $this->loadSetupData();
    }

    /**
     * Load the two-factor authentication setup data for the user.
     */
    private function loadSetupData(): void
    {
        $user = auth()->user()?->fresh();

        try {
            if (! $user || ! $user->two_factor_secret) {
                throw new Exception('Two-factor setup secret is not available.');
            }

            $this->qrCodeSvg = $user->twoFactorQrCodeSvg();
            $this->manualSetupKey = decrypt($user->two_factor_secret);
        } catch (Exception) {
            $this->addError('setupData', 'Failed to fetch setup data.');

            $this->reset('qrCodeSvg', 'manualSetupKey');
        }
    }

    /**
     * Show the two-factor verification step if necessary.
     */
    public function showVerificationIfNecessary(): void
    {
        if ($this->requiresConfirmation) {
            $this->showVerificationStep = true;

            $this->resetErrorBag();

            return;
        }

        $this->closeModal();
        $this->dispatch('two-factor-enabled');
    }

    /**
     * Confirm two-factor authentication for the user.
     */
    public function confirmTwoFactor(ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication): void
    {
        $this->validate();

        $confirmTwoFactorAuthentication(auth()->user(), $this->code);

        $this->setupComplete = true;

        $this->closeModal();

        $this->dispatch('two-factor-enabled');
    }

    /**
     * Reset two-factor verification state.
     */
    public function resetVerification(): void
    {
        $this->reset('code', 'showVerificationStep');

        $this->resetErrorBag();
    }

    /**
     * Close the two-factor authentication modal.
     */
    public function closeModal(): void
    {
        $this->reset(
            'code',
            'manualSetupKey',
            'qrCodeSvg',
            'showVerificationStep',
            'setupComplete',
        );

        $this->resetErrorBag();
    }

    /**
     * Get the current modal configuration state.
     */
    #[Computed]
    public function modalConfig(): array
    {
        if ($this->setupComplete) {
            return [
                'title' => __('Two-factor authentication enabled'),
                'description' => __('Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.'),
                'buttonText' => __('Close'),
            ];
        }

        if ($this->showVerificationStep) {
            return [
                'title' => __('Verify authentication code'),
                'description' => __('Enter the 6-digit code from your authenticator app.'),
                'buttonText' => __('Continue'),
            ];
        }

        return [
            'title' => __('Enable two-factor authentication'),
            'description' => __('To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app.'),
            'buttonText' => __('Continue'),
        ];
    }
}; ?>

<x-modal id="two_factor_setup_modal" title="{{ $this->modalConfig['title'] }}">
    <div class="space-y-6">
        <p class="text-sm text-base-content/70 text-center">{{ $this->modalConfig['description'] }}</p>

        @if ($showVerificationStep)
            <div class="space-y-4">
                <div class="flex flex-col items-center justify-center">
                    <x-input
                        name="code"
                        wire:model="code"
                        placeholder="000000"
                        class="text-center font-mono text-xl tracking-widest"
                        icon="o-key"
                    />
                </div>

                <div class="flex items-center space-x-3">
                    <x-button
                        label="{{ __('Back') }}"
                        class="btn-ghost flex-1"
                        wire:click="resetVerification"
                    />

                    <x-button
                        label="{{ __('Confirm') }}"
                        class="btn-primary flex-1"
                        wire:click="confirmTwoFactor"
                    />
                </div>
            </div>
        @else
            @error('setupData')
                <div class="alert alert-error text-sm">
                    <x-icon name="o-x-circle" class="w-5 h-5" />
                    <span>{{ $message }}</span>
                </div>
            @enderror

            <div class="flex justify-center">
                <div class="relative w-64 overflow-hidden border rounded-xl border-base-300 p-2 bg-white">
                    @empty($qrCodeSvg)
                        <div class="flex items-center justify-center h-48">
                            <span class="loading loading-spinner loading-md text-primary"></span>
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full p-2 bg-white">
                            {!! $qrCodeSvg !!}
                        </div>
                    @endempty
                </div>
            </div>

            <div>
                <x-button
                    label="{{ $this->modalConfig['buttonText'] }}"
                    class="btn-primary w-full"
                    wire:click="showVerificationIfNecessary"
                />
            </div>

            <div class="space-y-3">
                <div class="relative flex items-center justify-center w-full">
                    <div class="absolute inset-0 w-full h-px top-1/2 bg-base-300"></div>
                    <span class="relative px-2 text-xs bg-base-100 text-base-content/60">
                        {{ __('or, enter the code manually') }}
                    </span>
                </div>

                <div
                    class="flex items-center"
                    x-data="{
                        copied: false,
                        async copy() {
                            try {
                                await navigator.clipboard.writeText('{{ $manualSetupKey }}');
                                this.copied = true;
                                setTimeout(() => this.copied = false, 1500);
                            } catch (e) {
                                console.warn('Could not copy to clipboard');
                            }
                        }
                    }"
                >
                    <div class="flex items-center justify-between w-full border rounded-xl border-base-300 p-2 bg-base-200/50">
                        @empty($manualSetupKey)
                            <span class="loading loading-spinner loading-xs text-primary mx-auto"></span>
                        @else
                            <input
                                type="text"
                                readonly
                                value="{{ $manualSetupKey }}"
                                class="w-full bg-transparent text-sm font-mono outline-none px-2 text-base-content"
                            />

                            <button
                                type="button"
                                @click="copy()"
                                class="btn btn-ghost btn-xs"
                            >
                                <x-icon name="o-document-duplicate" x-show="!copied" class="w-4 h-4" />
                                <x-icon name="o-check" x-show="copied" class="w-4 h-4 text-success" />
                            </button>
                        @endempty
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-modal>
