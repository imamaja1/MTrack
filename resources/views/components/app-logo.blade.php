@props([
    'sidebar' => false,
])

<a {{ $attributes->merge(['class' => 'flex items-center gap-2 font-bold text-lg text-base-content']) }}>
    <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-content shadow-sm">
        <x-app-logo-icon class="size-5 fill-current" />
    </div>
    <span class="truncate tracking-wide">{{ config('app.name', 'MTrack') }}</span>
</a>
