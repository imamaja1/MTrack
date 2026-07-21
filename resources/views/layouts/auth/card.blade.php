<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-base-200 antialiased font-sans">
        <x-toast />

        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo />
                </a>

                <div class="card bg-base-100 shadow-xl border border-base-200">
                    <div class="card-body p-8">{{ $slot }}</div>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
