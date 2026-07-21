<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen font-sans antialiased bg-base-100 text-base-content">
        {{-- Toast Notifications --}}
        <x-toast />

        {{-- Main Layout Shell --}}
        <x-main full-width>
            {{-- SIDEBAR --}}
            <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-200/60 border-r border-base-300">
                {{-- BRAND / LOGO --}}
                <div class="p-4 flex items-center justify-between">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                </div>

                {{-- MENU --}}
                <x-menu activate-by-route class="px-3">
                    <x-menu-item title="Dashboard" icon="o-home" link="{{ route('dashboard') }}" wire:navigate />

                    @if(auth()->user()->role === 'superadmin')
                        <x-menu-separator />
                        <x-menu-item title="Security Dashboard" icon="o-shield-check" link="{{ route('superadmin.security') }}" wire:navigate />
                    @endif

                    @if(auth()->user()->role === 'admin')
                        <x-menu-separator />
                        <x-menu-sub title="Katalog & Promo" icon="o-cube">
                            <x-menu-item title="Products" icon="o-squares-2x2" link="{{ route('admin.products') }}" wire:navigate />
                            <x-menu-item title="Laporan Stok" icon="o-chart-bar-square" link="{{ route('admin.stocks') }}" wire:navigate />
                            <x-menu-item title="Kupon Promo" icon="o-ticket" link="{{ route('admin.coupons') }}" wire:navigate />
                        </x-menu-sub>

                        <x-menu-sub title="Manajemen Keuangan" icon="o-banknotes">
                            <x-menu-item title="Pengeluaran" icon="o-receipt-percent" link="{{ route('admin.expenses') }}" wire:navigate />
                            <x-menu-item title="Laporan Keuangan" icon="o-document-chart-bar" link="{{ route('admin.financial_report') }}" wire:navigate />
                        </x-menu-sub>

                        <x-menu-sub title="Pengguna & Config" icon="o-user-group">
                            <x-menu-item title="Operators" icon="o-users" link="{{ route('admin.operators') }}" wire:navigate />
                            <x-menu-item title="Config Member" icon="o-identification" link="{{ route('admin.member_config') }}" wire:navigate />
                        </x-menu-sub>
                    @endif

                    @if(in_array(auth()->user()->role, ['admin', 'operator']))
                        <x-menu-separator />
                        <x-menu-item title="Point of Sale" icon="o-calculator" link="{{ route('pos') }}" wire:navigate />
                        <x-menu-item title="Riwayat Penjualan" icon="o-clock" link="{{ route('operator.history') }}" wire:navigate />
                        <x-menu-item title="Restock Barang" icon="o-archive-box-arrow-down" link="{{ route('restock') }}" wire:navigate />
                        <x-menu-item title="Pelanggan" icon="o-user-group" link="{{ route('members') }}" wire:navigate />
                    @endif
                </x-menu>

                <div class="mt-auto p-3 border-t border-base-300">
                    <x-desktop-user-menu />
                </div>
            </x-slot:sidebar>

            {{-- NAVBAR / TOP HEADER (Mobile & Desktop Quick Actions) --}}
            <x-slot:navbar class="lg:hidden bg-base-100 border-b border-base-300 px-4 py-2 flex items-center justify-between">
                <label for="main-drawer" class="btn btn-ghost btn-square drawer-button lg:hidden">
                    <x-icon name="o-bars-3" class="w-6 h-6" />
                </label>
                <div class="font-bold text-lg">
                    {{ config('app.name', 'MTrack') }}
                </div>
                <x-dropdown>
                    <x-slot:trigger>
                        <x-avatar :title="auth()->user()->initials()" class="!w-8 !h-8 bg-primary text-primary-content font-bold rounded-full" />
                    </x-slot:trigger>
                    <x-menu-item title="{{ auth()->user()->name }}" subtitle="{{ auth()->user()->email }}" class="font-bold border-b border-base-200 pointer-events-none" />
                    <x-menu-item title="Settings" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" wire:navigate />
                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <x-menu-item title="Log out" icon="o-arrow-right-on-rectangle" type="submit" class="text-error" />
                    </form>
                </x-dropdown>
            </x-slot:navbar>

            {{-- MAIN CONTENT AREA --}}
            <x-slot:content class="p-6 md:p-8">
                <!-- Global Loading Overlay for wire:navigate -->
                <div id="page-loading-overlay" style="display: none;" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-base-100/70 backdrop-blur-sm transition-all duration-200">
                    <div class="flex flex-col items-center gap-3">
                        <span class="loading loading-spinner loading-lg text-primary"></span>
                        <span class="text-sm font-medium text-base-content animate-pulse">Memuat Halaman...</span>
                    </div>
                </div>

                {{ $slot }}
            </x-slot:content>
        </x-main>

        @livewireScripts
    </body>
</html>
