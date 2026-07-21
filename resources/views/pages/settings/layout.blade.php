<div class="flex items-start max-md:flex-col gap-6">
    <div class="w-full md:w-[220px]">
        <x-menu class="bg-base-200/50 rounded-xl p-2">
            <x-menu-item title="{{ __('Profile') }}" icon="o-user" link="{{ route('profile.edit') }}" wire:navigate />
            <x-menu-item title="{{ __('Security') }}" icon="o-shield-check" link="{{ route('security.edit') }}" wire:navigate />
            <x-menu-item title="{{ __('Appearance') }}" icon="o-swatch" link="{{ route('appearance.edit') }}" wire:navigate />
        </x-menu>
    </div>

    <div class="flex-1 self-stretch">
        @if(isset($heading))
            <h2 class="text-xl font-bold text-base-content">{{ $heading }}</h2>
        @endif
        @if(isset($subheading))
            <p class="text-sm text-base-content/70 mb-4">{{ $subheading }}</p>
        @endif

        <div class="mt-4 w-full max-w-xl">
            {{ $slot }}
        </div>
    </div>
</div>
