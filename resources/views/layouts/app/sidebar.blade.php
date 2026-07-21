<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>

                @if(auth()->user()->role === 'superadmin')
                    <flux:sidebar.group :heading="__('Super Admin')" class="grid mt-4">
                        <flux:sidebar.item icon="shield-check" :href="route('superadmin.security')" :current="request()->routeIs('superadmin.security')" wire:navigate>
                            {{ __('Security') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @if(auth()->user()->role === 'admin')
                    <flux:sidebar.group :heading="__('Store Admin')" class="grid mt-4">
                        <flux:navlist.group expandable :heading="__('Katalog & Promo')" class="mb-1">
                            <flux:navlist.item icon="cube" :href="route('admin.products')" :current="request()->routeIs('admin.products')" wire:navigate>
                                {{ __('Products') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="chart-bar-square" :href="route('admin.stocks')" :current="request()->routeIs('admin.stocks')" wire:navigate>
                                {{ __('Laporan Stok') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="ticket" :href="route('admin.coupons')" :current="request()->routeIs('admin.coupons')" wire:navigate>
                                {{ __('Kupon Promo') }}
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.group expandable :heading="__('Manajemen Keuangan')" class="mb-1">
                            <flux:navlist.item icon="banknotes" :href="route('admin.expenses')" :current="request()->routeIs('admin.expenses')" wire:navigate>
                                {{ __('Pengeluaran') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="document-chart-bar" :href="route('admin.financial_report')" :current="request()->routeIs('admin.financial_report')" wire:navigate>
                                {{ __('Laporan Keuangan') }}
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.group expandable :heading="__('Pengguna & Config')">
                            <flux:navlist.item icon="users" :href="route('admin.operators')" :current="request()->routeIs('admin.operators')" wire:navigate>
                                {{ __('Operators') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="identification" :href="route('admin.member_config')" :current="request()->routeIs('admin.member_config')" wire:navigate>
                                {{ __('Config Member') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    </flux:sidebar.group>
                @endif

                @if(in_array(auth()->user()->role, ['admin', 'operator']))
                    <flux:sidebar.group :heading="__('Store Operations')" class="grid mt-4">
                        <flux:sidebar.item icon="calculator" :href="route('pos')" :current="request()->routeIs('pos')" wire:navigate>
                            {{ __('Point of Sale') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="clock" :href="route('operator.history')" :current="request()->routeIs('operator.history')" wire:navigate>
                            {{ __('Riwayat Penjualan') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="archive-box-arrow-down" :href="route('restock')" :current="request()->routeIs('restock')" wire:navigate>
                            {{ __('Restock Barang') }}
                        </flux:sidebar.item>
                        <flux:sidebar.item icon="user-group" :href="route('members')" :current="request()->routeIs('members')" wire:navigate>
                            {{ __('Pelanggan') }}
                        </flux:sidebar.item>
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:spacer />

            <!-- <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav> -->

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Global Loading Overlay for wire:navigate -->
        <div id="page-loading-overlay" style="display: none;" class="fixed inset-0 z-[9999] flex-col items-center justify-center bg-white/60 backdrop-blur-sm dark:bg-zinc-900/60 transition-all duration-200">
            <div class="flex flex-col items-center gap-3">
                <svg class="h-10 w-10 animate-spin text-zinc-900 dark:text-zinc-100" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100 animate-pulse">Memuat Halaman...</span>
            </div>
        </div>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
