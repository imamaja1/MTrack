<x-layouts::auth :title="__('Two-factor authentication')">
    <div class="flex flex-col gap-6">
        <div
            class="relative w-full h-auto"
            x-cloak
            x-data="{
                showRecoveryInput: @js($errors->has('recovery_code')),
                code: '',
                recovery_code: '',
                toggleInput() {
                    this.showRecoveryInput = !this.showRecoveryInput;
                    this.code = '';
                    this.recovery_code = '';
                },
            }"
        >
            <div x-show="!showRecoveryInput">
                <x-auth-header
                    :title="__('Authentication code')"
                    :description="__('Enter the authentication code provided by your authenticator application.')"
                />
            </div>

            <div x-show="showRecoveryInput">
                <x-auth-header
                    :title="__('Recovery code')"
                    :description="__('Please confirm access to your account by entering one of your emergency recovery codes.')"
                />
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4">
                @csrf

                <div class="space-y-4 text-center">
                    <div x-show="!showRecoveryInput">
                        <div class="my-4">
                            <x-input
                                name="code"
                                type="text"
                                x-model="code"
                                placeholder="000000"
                                class="text-center font-mono text-xl tracking-widest"
                                icon="o-key"
                                autofocus
                            />
                        </div>
                    </div>

                    <div x-show="showRecoveryInput">
                        <div class="my-4">
                            <x-input
                                type="text"
                                name="recovery_code"
                                x-bind:required="showRecoveryInput"
                                autocomplete="one-time-code"
                                x-model="recovery_code"
                                placeholder="XXXXX-XXXXX"
                                icon="o-shield-check"
                            />
                        </div>
                    </div>

                    <x-button
                        label="{{ __('Continue') }}"
                        type="submit"
                        class="btn-primary w-full"
                    />
                </div>

                <div class="mt-5 text-sm text-center text-base-content/70">
                    <span>{{ __('or you can') }}</span>
                    <button type="button" class="text-primary font-semibold hover:underline ml-1" @click="toggleInput()">
                        <span x-show="!showRecoveryInput">{{ __('login using a recovery code') }}</span>
                        <span x-show="showRecoveryInput">{{ __('login using an authentication code') }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
