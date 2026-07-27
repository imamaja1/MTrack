<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <div class="relative mb-5">
        <h3 class="text-lg font-bold text-error">{{ __('Delete account') }}</h3>
        <p class="text-sm text-base-content/70">{{ __('Delete your account and all of its resources') }}</p>
    </div>

    <x-button label="{{ __('Delete account') }}" class="btn-error" onclick="delete_user_modal.showModal()" data-test="delete-user-button" />

    <livewire:pages::settings.delete-user-modal />
</section>
