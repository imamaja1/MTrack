<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Appearance settings')] class extends Component {
    //
}; ?>

<section class="w-full">
    @include('components.partials.settings-heading')

    <x-pages::settings.layout :heading="__('Appearance')" :subheading="__('Update the appearance settings for your account')">
        <div class="join border border-base-300">
            <button type="button" onclick="setTheme('light')" class="btn join-item border-none hover:bg-base-200">
                <x-icon name="o-sun" class="w-5 h-5" />
                {{ __('Light') }}
            </button>
            <button type="button" onclick="setTheme('dark')" class="btn join-item border-none hover:bg-base-200">
                <x-icon name="o-moon" class="w-5 h-5" />
                {{ __('Dark') }}
            </button>
        </div>
    </x-pages::settings.layout>
</section>

