<div>
    @if($variant === 'button')
        <x-button label="{{ __('Log out') }}" wire:click="logout" class="btn-ghost btn-sm w-full text-error" data-test="logout-button" />
    @else
        <x-menu-item title="Log out" icon="o-arrow-right-on-rectangle" wire:click="logout" class="text-error cursor-pointer" />
    @endif
</div>
