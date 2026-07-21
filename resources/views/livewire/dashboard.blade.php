<div>
    <div class="flex flex-col gap-6" wire:poll.{{ $pollInterval }}s="refresh">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-base-content/60">{{ now()->translatedFormat('l, d F Y') }}</p>
            </div>
            <x-badge value="{{ auth()->user()->role }}" class="badge-primary capitalize font-bold px-3 py-1" />
        </div>

        @if ($data['role'] === 'superadmin')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <x-icon name="o-users" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Admin Toko</div>
                            <div class="text-2xl font-bold">{{ number_format($data['totalAdmins']) }}</div>
                            <div class="text-xs text-base-content/50">+{{ $data['newAdminsMonth'] }} bulan ini</div>
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <x-icon name="o-user-group" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Operator</div>
                            <div class="text-2xl font-bold">{{ number_format($data['totalOperators']) }}</div>
                            <div class="text-xs text-base-content/50">Kasir aktif</div>
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-base-100 shadow-sm border border-base-200 cursor-pointer hover:border-primary/30 transition-colors" link="{{ route('superadmin.security') }}" wire:navigate>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-error/10 text-error">
                            <x-icon name="o-shield-exclamation" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Log Keamanan</div>
                            <div class="text-2xl font-bold">{{ number_format($data['totalSecurityLogs']) }}</div>
                            <div class="text-xs text-base-content/50">Total kejadian</div>
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-neutral text-neutral-content shadow-sm border-none">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <x-icon name="o-building-storefront" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-neutral-content/60">Total Pengguna</div>
                            <div class="text-2xl font-bold">{{ number_format($data['totalAdmins'] + $data['totalOperators']) }}</div>
                            <div class="text-xs text-neutral-content/50">Akun aktif</div>
                        </div>
                    </div>
                </x-card>
            </div>

            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Log Keamanan Terbaru</h2>
                    <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('superadmin.security') }}" wire:navigate class="btn-ghost btn-sm" />
                </div>
                <x-card class="bg-base-100 shadow-sm border border-base-200 p-0 overflow-hidden">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Aktivitas</th>
                                <th>IP</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['recentLogs'] as $log)
                                <tr class="hover:bg-base-200/50">
                                    <td>
                                        <x-badge value="{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->event_type)) }}" class="badge-error badge-sm font-medium" />
                                    </td>
                                    <td class="font-mono text-sm">{{ $log->ip_address }}</td>
                                    <td class="text-xs text-base-content/60">{{ Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-12 text-center text-base-content/40">
                                        <x-icon name="o-shield-check" class="mx-auto mb-3 w-10 h-10 text-success/40" />
                                        Belum ada aktivitas keamanan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-card>
            </div>

        @elseif ($data['role'] === 'admin')
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-base-content/40">Hari Ini</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                <x-icon name="o-banknotes" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Pendapatan</div>
                                <div class="text-xl font-bold">Rp {{ number_format($data['salesToday'], 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">{{ $data['trxToday'] }} transaksi</div>
                            </div>
                        </div>
                    </x-card>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <x-icon name="o-arrow-trending-up" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Laba Kotor</div>
                                <div class="text-xl font-bold">Rp {{ number_format($data['profitToday'], 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Pendapatan &minus; HPP</div>
                            </div>
                        </div>
                    </x-card>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-warning/10 text-warning">
                                <x-icon name="o-cube" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Total Produk</div>
                                <div class="text-xl font-bold">{{ number_format($data['totalProducts']) }}</div>
                                @if ($data['lowStockProducts']->count() > 0)
                                    <div class="text-xs text-error font-medium">{{ $data['lowStockProducts']->count() }} stok tipis</div>
                                @else
                                    <div class="text-xs text-success">Aman</div>
                                @endif
                            </div>
                        </div>
                    </x-card>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                                <x-icon name="o-chart-pie" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Margin Kotor</div>
                                <div class="text-xl font-bold">{{ $data['grossMargin'] }}%</div>
                                <div class="text-xs text-base-content/50">Bulan {{ now()->translatedFormat('M') }}</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>

            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-base-content/40">Bulan {{ now()->translatedFormat('F Y') }}</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                <x-icon name="o-chart-bar-square" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Total Omset</div>
                                <div class="text-2xl font-bold">Rp {{ number_format($data['salesThisMonth'], 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Pendapatan bulan ini</div>
                            </div>
                        </div>
                    </x-card>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-error/10 text-error">
                                <x-icon name="o-arrow-trending-down" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-base-content/50">Total Pengeluaran</div>
                                <div class="text-2xl font-bold">Rp {{ number_format($data['expenseThisMonth'], 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Operasional</div>
                            </div>
                        </div>
                    </x-card>
                    <x-card class="bg-neutral text-neutral-content shadow-sm border-none">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/10">
                                <x-icon name="o-sparkles" class="w-6 h-6" />
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-neutral-content/60">Laba Bersih</div>
                                <div class="text-2xl font-bold {{ $data['netProfitThisMonth'] >= 0 ? 'text-white' : 'text-error' }}">
                                    Rp {{ number_format($data['netProfitThisMonth'], 0, ',', '.') }}
                                </div>
                                <div class="text-xs text-neutral-content/50">Laba kotor &minus; pengeluaran</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Transaksi Terbaru</h2>
                        <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('operator.history') }}" wire:navigate class="btn-ghost btn-sm" />
                    </div>
                    <x-card class="bg-base-100 shadow-sm border border-base-200 p-0 overflow-hidden">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Kasir</th>
                                    <th>Total</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['recentTrx'] as $trx)
                                    <tr class="hover:bg-base-200/50">
                                        <td class="font-mono font-medium">#{{ $trx->invoice_number }}</td>
                                        <td class="text-sm">{{ $trx->operator?->name ?? '&mdash;' }}</td>
                                        <td class="font-bold text-success">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-xs text-base-content/60">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-12 text-center text-base-content/40">
                                            <x-icon name="o-inbox" class="mx-auto mb-3 w-10 h-10 text-base-content/15" />
                                            Belum ada transaksi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-card>
                </div>

                <div class="flex flex-col gap-6">
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Stok Menipis</h2>
                            @if ($data['lowStockProducts']->count() > 0)
                                <x-badge value="{{ $data['lowStockProducts']->count() }}" class="badge-warning badge-sm font-medium" />
                            @endif
                        </div>
                        <x-card class="bg-base-100 shadow-sm border border-base-200">
                            @forelse ($data['lowStockProducts'] as $product)
                                <div class="flex items-center justify-between border-b border-base-200 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                    <div class="min-w-0 flex-1 mr-3">
                                        <div class="font-medium text-sm truncate">{{ $product->name }}</div>
                                        <div class="text-xs text-base-content/50">{{ $product->sku }}</div>
                                    </div>
                                    <x-badge value="Stok: {{ $product->stock }}" class="badge-error badge-sm font-semibold shrink-0" />
                                </div>
                            @empty
                                <div class="flex flex-col items-center py-8 text-base-content/30">
                                    <x-icon name="o-check-circle" class="mb-3 w-10 h-10 text-success/40" />
                                    <span class="text-sm">Semua stok aman.</span>
                                </div>
                            @endforelse
                        </x-card>
                    </div>

                    <div>
                        <h2 class="mb-4 text-lg font-semibold">Pengeluaran Terakhir</h2>
                        <x-card class="bg-base-100 shadow-sm border border-base-200">
                            @forelse ($data['recentExpenses'] as $expense)
                                <div class="flex items-start justify-between border-b border-base-200 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                    <div class="min-w-0 flex-1 mr-3">
                                        <div class="font-medium text-sm truncate">{{ $expense->title }}</div>
                                        <div class="text-xs text-base-content/50">
                                            {{ $expense->category?->name ?? 'Umum' }} &middot; {{ Carbon\Carbon::parse($expense->expense_date)->format('d M') }}
                                        </div>
                                    </div>
                                    <span class="text-sm font-semibold text-error shrink-0">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="flex flex-col items-center py-8 text-base-content/30">
                                    <x-icon name="o-receipt-percent" class="mb-3 w-10 h-10 text-base-content/15" />
                                    <span class="text-sm">Belum ada pengeluaran.</span>
                                </div>
                            @endforelse
                        </x-card>
                    </div>
                </div>
            </div>

        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                            <x-icon name="o-banknotes" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Penjualan Hari Ini</div>
                            <div class="text-xl font-bold">Rp {{ number_format($data['salesToday'], 0, ',', '.') }}</div>
                            <div class="text-xs text-base-content/50">{{ $data['trxToday'] }} transaksi</div>
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <x-icon name="o-shopping-cart" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Jumlah Transaksi</div>
                            <div class="text-xl font-bold">{{ number_format($data['trxToday']) }}</div>
                            <div class="text-xs text-base-content/50">Hari ini</div>
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-warning/10 text-warning">
                            <x-icon name="o-cube" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Total Produk</div>
                            <div class="text-xl font-bold">{{ number_format($data['totalProducts']) }}</div>
                            @if ($data['lowStockProducts']->count() > 0)
                                <div class="text-xs text-error font-medium">{{ $data['lowStockProducts']->count() }} stok tipis</div>
                            @else
                                <div class="text-xs text-success">Aman</div>
                            @endif
                        </div>
                    </div>
                </x-card>
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                            <x-icon name="o-tag" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs text-base-content/50">Produk Tersedia</div>
                            <div class="text-xl font-bold">{{ number_format($data['totalProducts']) }}</div>
                            <div class="text-xs text-base-content/50">Dalam katalog</div>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Transaksi Terbaru</h2>
                        <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('operator.history') }}" wire:navigate class="btn-ghost btn-sm" />
                    </div>
                    <x-card class="bg-base-100 shadow-sm border border-base-200 p-0 overflow-hidden">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Nota</th>
                                    <th>Total</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data['recentTrx'] as $trx)
                                    <tr class="hover:bg-base-200/50">
                                        <td class="font-mono font-medium">#{{ $trx->invoice_number }}</td>
                                        <td class="font-bold text-success">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-xs text-base-content/60">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-12 text-center text-base-content/40">
                                            <x-icon name="o-inbox" class="mx-auto mb-3 w-10 h-10 text-base-content/15" />
                                            Belum ada transaksi.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-card>
                </div>

                <div>
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Stok Menipis</h2>
                        @if ($data['lowStockProducts']->count() > 0)
                            <x-badge value="{{ $data['lowStockProducts']->count() }}" class="badge-warning badge-sm font-medium" />
                        @endif
                    </div>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        @forelse ($data['lowStockProducts'] as $product)
                            <div class="flex items-center justify-between border-b border-base-200 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
                                <div class="min-w-0 flex-1 mr-3">
                                    <div class="font-medium text-sm truncate">{{ $product->name }}</div>
                                    <div class="text-xs text-base-content/50">{{ $product->sku }}</div>
                                </div>
                                <x-badge value="Stok: {{ $product->stock }}" class="badge-error badge-sm font-semibold shrink-0" />
                            </div>
                        @empty
                            <div class="flex flex-col items-center py-8 text-base-content/30">
                                <x-icon name="o-check-circle" class="mb-3 w-10 h-10 text-success/40" />
                                <span class="text-sm">Semua stok aman.</span>
                            </div>
                        @endforelse
                    </x-card>
                </div>
            </div>
        @endif
    </div>
</div>
