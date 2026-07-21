<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen font-sans antialiased bg-base-100 text-base-content">
        <x-toast />

        <div class="navbar bg-base-100 border-b border-base-300 px-4">
            <div class="flex-1 gap-4">
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
            </div>
            <div class="flex-none gap-2">
                <x-desktop-user-menu />
            </div>
        </div>

        <main class="p-6 md:p-8">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
