<x-dropdown class="w-full">
    <x-slot:trigger>
        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-base-200 cursor-pointer w-full text-left transition-colors">
            <x-avatar :title="auth()->user()->initials()" class="!w-9 !h-9 bg-primary text-primary-content font-bold rounded-full" />
            <div class="flex-1 min-w-0">
                <p class="font-medium text-sm text-base-content truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-base-content/60 truncate capitalize">{{ auth()->user()->role }}</p>
            </div>
            <x-icon name="o-chevron-up-down" class="w-4 h-4 text-base-content/50" />
        </div>
    </x-slot:trigger>

    <x-menu-item title="{{ auth()->user()->name }}" subtitle="{{ auth()->user()->email }}" class="font-bold border-b border-base-200 pointer-events-none" />
    <x-menu-item title="Settings" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" wire:navigate />

    <form method="POST" action="{{ route('logout') }}" class="w-full">
        @csrf
        <x-menu-item title="Log out" icon="o-arrow-right-on-rectangle" type="submit" class="text-error" />
    </form>
</x-dropdown>
