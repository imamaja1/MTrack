<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('components.partials.head')
    </head>
    <body class="min-h-screen font-sans antialiased bg-base-100 text-base-content">
        {{-- Toast Notifications --}}
        <x-toast />


        {{-- NAVBAR / TOP HEADER (Mobile & Desktop Quick Actions) --}}
        <div class="navbar lg:hidden bg-base-100 border-b border-base-300 px-4 flex items-center justify-between sticky top-0 z-10">
            <div class="flex items-center gap-2">
                <label for="main-drawer" class="btn btn-ghost btn-square drawer-button lg:hidden">
                    <x-icon name="o-bars-3" class="w-6 h-6" />
                </label>
                <div class="font-bold text-lg">
                    {{ config('app.name', 'MTrack') }}
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button onclick="toggleTheme()" data-theme-toggle class="btn btn-ghost btn-square" aria-label="Toggle theme">
                    <x-icon name="o-sun" data-icon="sun" class="w-5 h-5" />
                    <x-icon name="o-moon" data-icon="moon" class="w-5 h-5" style="display:none" />
                </button>

                <x-dropdown right>
                <x-slot:trigger>
                    <div class="flex aspect-square size-8 items-center justify-center rounded-full bg-primary text-primary-content font-bold shrink-0 cursor-pointer">
                        {{ auth()->user()->initials() }}
                    </div>
                </x-slot:trigger>
                <x-menu-item title="{{ auth()->user()->name }}" subtitle="{{ auth()->user()->email }}" class="font-bold border-b border-base-200 pointer-events-none" />
                <x-menu-item title="Settings" icon="o-cog-6-tooth" link="{{ route('profile.edit') }}" wire:navigate />
                <livewire:actions.logout />
            </x-dropdown>
        </div>
    </div>

        {{-- Main Layout Shell --}}
        <x-main full-width>
            {{-- SIDEBAR --}}
            <x-slot:sidebar drawer="main-drawer" collapse-text="Tutup" class="bg-base-200 border-r border-base-300">
                {{-- BRAND / LOGO --}}
                <div class="p-4 flex items-center justify-between">
                    <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                    
                    {{-- Mobile & Tab Drawer Close Button (Visible only on small screens) --}}
                    <label for="main-drawer" class="btn btn-ghost btn-circle btn-sm lg:hidden">
                        <x-icon name="o-x-mark" class="w-5 h-5" />
                    </label>
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
                            <x-menu-item title="Pengaturan PPN" icon="o-scale" link="{{ route('admin.tax_config') }}" wire:navigate />
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

                <div class="mt-auto p-3 border-t border-base-300 space-y-2">
                    <button onclick="toggleTheme()" data-theme-toggle class="btn btn-ghost btn-sm w-full justify-start" aria-label="Toggle theme">
                        <x-icon name="o-sun" data-icon="sun" class="w-4 h-4" />
                        <x-icon name="o-moon" data-icon="moon" class="w-4 h-4" style="display:none" />
                        <span class="hidden-when-collapsed text-sm">Tema</span>
                    </button>
                    <x-desktop-user-menu />
                </div>
            </x-slot:sidebar>



            {{-- MAIN CONTENT AREA --}}
            <x-slot:content class="p-6 md:p-8">
                {{ $slot }}
            </x-slot:content>
        </x-main>

        @livewireScripts

    <!-- Global Loading Overlay -->
    <div id="page-loading-overlay" style="display:none; position:fixed; inset:0; z-index:99999; flex-direction:column; align-items:center; justify-content:center; background:oklch(var(--b1)/0.8); backdrop-filter:blur(4px);">
        <div style="display:flex; flex-direction:column; align-items:center; gap:12px;">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <span style="font-size:0.875rem; font-weight:500;" class="text-base-content animate-pulse">Memuat Halaman...</span>
        </div>
    </div>
</body>
</html>

