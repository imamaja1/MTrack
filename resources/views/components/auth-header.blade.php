@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center gap-1">
    <h2 class="text-2xl font-bold text-base-content">{{ $title }}</h2>
    <p class="text-sm text-base-content/70">{{ $description }}</p>
</div>
