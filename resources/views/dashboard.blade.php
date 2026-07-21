<x-layouts::app :title="__('Dashboard')">
    @php
        $user = auth()->user();

        // ─── Superadmin Stats ────────────────────────────────────────────
        if ($user->role === 'superadmin') {
            $totalAdmins     = \App\Models\User::where('role', 'admin')->count();
            $totalOperators  = \App\Models\User::where('role', 'operator')->count();
            $totalSecurityLogs = \Illuminate\Support\Facades\DB::table('security_logs')->count();
            $recentLogs      = \Illuminate\Support\Facades\DB::table('security_logs')->orderByDesc('created_at')->limit(5)->get();
            $newAdminsMonth  = \App\Models\User::where('role', 'admin')
                                    ->whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count();
        }

        // ─── Admin Stats ─────────────────────────────────────────────────
        elseif ($user->role === 'admin') {
            $adminId = $user->id;

            // Produk
            $totalProducts   = \App\Models\Product::where('admin_id', $adminId)->count();
            $lowStockProducts = \App\Models\Product::where('admin_id', $adminId)
                                    ->whereColumn('stock', '<=', 'min_stock_alert')
                                    ->get();

            // Penjualan hari ini
            $salesToday      = \App\Models\Transaction::where('admin_id', $adminId)->whereDate('created_at', today())->sum('total_amount');
            $profitToday     = \App\Models\Transaction::where('admin_id', $adminId)->whereDate('created_at', today())->sum('profit');
            $trxToday        = \App\Models\Transaction::where('admin_id', $adminId)->whereDate('created_at', today())->count();

            // Penjualan bulan ini
            $salesThisMonth  = \App\Models\Transaction::where('admin_id', $adminId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('total_amount');
            $profitThisMonth = \App\Models\Transaction::where('admin_id', $adminId)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('profit');

            // Pengeluaran bulan ini
            $expenseThisMonth = \App\Models\Expense::where('admin_id', $adminId)
                                    ->whereMonth('expense_date', now()->month)
                                    ->whereYear('expense_date', now()->year)
                                    ->sum('amount');

            // Laba Bersih bulan ini
            $netProfitThisMonth = $profitThisMonth - $expenseThisMonth;

            $recentTrx = \App\Models\Transaction::with('operator')
                                ->where('admin_id', $adminId)
                                ->orderByDesc('created_at')
                                ->limit(5)
                                ->get();

            $recentExpenses = \App\Models\Expense::where('admin_id', $adminId)
                                ->orderByDesc('expense_date')
                                ->limit(5)
                                ->get();
        }

        // ─── Operator Stats ──────────────────────────────────────────────
        else {
            $adminId = $user->admin_id;

            $salesToday  = \App\Models\Transaction::where('admin_id', $adminId)->where('operator_id', $user->id)->whereDate('created_at', today())->sum('total_amount');
            $trxToday    = \App\Models\Transaction::where('admin_id', $adminId)->where('operator_id', $user->id)->whereDate('created_at', today())->count();
            $totalProducts = \App\Models\Product::where('admin_id', $adminId)->count();

            $lowStockProducts = \App\Models\Product::where('admin_id', $adminId)
                                    ->whereColumn('stock', '<=', 'min_stock_alert')
                                    ->get();

            $recentTrx = \App\Models\Transaction::with('operator')
                                ->where('admin_id', $adminId)
                                ->where('operator_id', $user->id)
                                ->orderByDesc('created_at')
                                ->limit(5)
                                ->get();
        }
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-8">
        {{-- Greeting --}}
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Halo, {{ $user->name }} 👋</h1>
                <p class="text-base-content/70">{{ now()->translatedFormat('l, d F Y') }} — Berikut ringkasan bisnis Anda.</p>
            </div>
            <x-badge value="{{ $user->role }}" class="badge-{{ $user->role === 'superadmin' ? 'error' : ($user->role === 'admin' ? 'info' : 'success') }} capitalize font-bold p-3" />
        </div>

        {{-- ─── SUPERADMIN VIEW ─────────────────────────────────────────── --}}
        @if ($user->role === 'superadmin')
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-600">
                            <x-icon name="o-users" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Total Admin Toko</div>
                            <div class="text-3xl font-bold text-base-content">{{ number_format($totalAdmins) }}</div>
                            <div class="text-xs text-base-content/50">+{{ $newAdminsMonth }} bulan ini</div>
                        </div>
                    </div>
                </x-card>

                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                            <x-icon name="o-user-group" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Total Operator</div>
                            <div class="text-3xl font-bold text-base-content">{{ number_format($totalOperators) }}</div>
                            <div class="text-xs text-base-content/50">Kasir aktif terdaftar</div>
                        </div>
                    </div>
                </x-card>

                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-error/10 text-error">
                            <x-icon name="o-shield-exclamation" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Log Keamanan</div>
                            <div class="text-3xl font-bold text-base-content">{{ number_format($totalSecurityLogs) }}</div>
                            <div class="text-xs text-base-content/50">Total kejadian tercatat</div>
                        </div>
                    </div>
                </x-card>

                <x-card class="relative overflow-hidden bg-neutral text-neutral-content shadow-sm border-none">
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                            <x-icon name="o-building-storefront" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-neutral-content/80">Total Toko</div>
                            <div class="text-3xl font-bold text-white">{{ number_format($totalAdmins + $totalOperators) }}</div>
                            <div class="text-xs text-neutral-content/60">Pengguna aktif</div>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Security Log Table --}}
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-base-content">Log Keamanan Terbaru</h2>
                    <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('superadmin.security') }}" wire:navigate class="btn-ghost btn-sm" />
                </div>
                <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Aktivitas</th>
                                    <th>Alamat IP</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                <tr>
                                    <td class="font-medium">
                                        <x-badge value="Alert" class="badge-error badge-sm mr-2" />
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->event_type)) }}
                                    </td>
                                    <td>{{ $log->ip_address }}</td>
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-base-content/60 py-6">Belum ada aktivitas keamanan yang mencurigakan.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-card>
            </div>

        {{-- ─── ADMIN VIEW ──────────────────────────────────────────────── --}}
        @elseif ($user->role === 'admin')
            {{-- Stats Row 1: Today --}}
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-base-content/50">Ringkasan Hari Ini</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                <x-icon name="o-banknotes" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Pendapatan</div>
                                <div class="text-2xl font-bold text-base-content">Rp {{ number_format($salesToday, 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">{{ $trxToday }} transaksi</div>
                            </div>
                        </div>
                    </x-card>

                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                                <x-icon name="o-arrow-trending-up" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Laba Kotor</div>
                                <div class="text-2xl font-bold text-base-content">Rp {{ number_format($profitToday, 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Pendapatan - HPP</div>
                            </div>
                        </div>
                    </x-card>

                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-warning/10 text-warning">
                                <x-icon name="o-cube" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Total Produk</div>
                                <div class="text-2xl font-bold text-base-content">{{ number_format($totalProducts) }}</div>
                                @if($lowStockProducts->count() > 0)
                                    <div class="text-xs text-error font-medium">{{ $lowStockProducts->count() }} stok tipis!</div>
                                @else
                                    <div class="text-xs text-success">Semua stok aman</div>
                                @endif
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>

            {{-- Stats Row 2: This Month --}}
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-base-content/50">Rekap Bulan {{ now()->translatedFormat('F Y') }}</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                                <x-icon name="o-chart-bar-square" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Total Omset</div>
                                <div class="text-2xl font-bold text-base-content">Rp {{ number_format($salesThisMonth, 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Pendapatan bulan ini</div>
                            </div>
                        </div>
                    </x-card>

                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-error/10 text-error">
                                <x-icon name="o-arrow-trending-down" class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="text-sm text-base-content/70">Total Pengeluaran</div>
                                <div class="text-2xl font-bold text-base-content">Rp {{ number_format($expenseThisMonth, 0, ',', '.') }}</div>
                                <div class="text-xs text-base-content/50">Operasional bulan ini</div>
                            </div>
                        </div>
                    </x-card>

                    <x-card class="bg-neutral text-neutral-content shadow-sm border-none">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                                <x-icon name="o-sparkles" class="w-6 h-6 text-warning" />
                            </div>
                            <div>
                                <div class="text-sm text-neutral-content/80">Laba Bersih</div>
                                <div class="text-2xl font-bold {{ $netProfitThisMonth >= 0 ? 'text-white' : 'text-error' }}">
                                    Rp {{ number_format($netProfitThisMonth, 0, ',', '.') }}
                                </div>
                                <div class="text-xs text-neutral-content/60">Laba kotor - pengeluaran</div>
                            </div>
                        </div>
                    </x-card>
                </div>
            </div>

            {{-- Tables Row --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Recent Transactions --}}
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-base-content">Transaksi Terbaru</h2>
                        <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('operator.history') }}" wire:navigate class="btn-ghost btn-sm" />
                    </div>
                    <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>No. Nota</th>
                                        <th>Kasir</th>
                                        <th>Total</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTrx as $trx)
                                    <tr>
                                        <td class="font-medium text-base-content">#{{ $trx->invoice_number }}</td>
                                        <td>{{ $trx->operator->name }}</td>
                                        <td class="font-bold text-success">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-xs">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-base-content/60 py-6">Belum ada transaksi hari ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                </div>

                {{-- Sidebar: Low Stock + Recent Expenses --}}
                <div class="flex flex-col gap-6">
                    {{-- Low Stock Alert --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-base-content">Stok Tipis</h2>
                            <x-button label="Stok" icon-right="o-arrow-right" link="{{ route('admin.stocks') }}" wire:navigate class="btn-ghost btn-sm" />
                        </div>
                        <x-card class="bg-base-100 shadow-sm border border-base-200">
                            @forelse($lowStockProducts as $product)
                                <div class="flex items-center justify-between border-b border-base-200 pb-3 last:border-0 last:pb-0">
                                    <div>
                                        <div class="font-medium text-base-content text-sm">{{ $product->name }}</div>
                                        <div class="text-xs text-base-content/60">{{ $product->sku }}</div>
                                    </div>
                                    <x-badge value="{{ $product->stock }} sisa" class="badge-error badge-sm font-semibold" />
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-4 text-center text-base-content/60">
                                    <x-icon name="o-check-circle" class="mb-2 h-8 w-8 text-success" />
                                    <p class="text-sm">Semua stok aman!</p>
                                </div>
                            @endforelse
                        </x-card>
                    </div>

                    {{-- Recent Expenses --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-base-content">Pengeluaran Terakhir</h2>
                            <x-button label="Kelola" icon-right="o-arrow-right" link="{{ route('admin.expenses') }}" wire:navigate class="btn-ghost btn-sm" />
                        </div>
                        <x-card class="bg-base-100 shadow-sm border border-base-200">
                            @forelse($recentExpenses as $expense)
                                <div class="flex items-center justify-between border-b border-base-200 pb-3 last:border-0 last:pb-0">
                                    <div>
                                        <div class="font-medium text-base-content text-sm">{{ $expense->title }}</div>
                                        <div class="text-xs text-base-content/60">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</div>
                                    </div>
                                    <span class="text-sm font-semibold text-error">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-4 text-center text-base-content/60">
                                    <x-icon name="o-inbox" class="mb-2 h-8 w-8 text-base-content/30" />
                                    <p class="text-sm">Belum ada pengeluaran.</p>
                                </div>
                            @endforelse
                        </x-card>
                    </div>
                </div>
            </div>

        {{-- ─── OPERATOR VIEW ──────────────────────────────────────────── --}}
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-success/10 text-success">
                            <x-icon name="o-banknotes" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Penjualan Saya Hari Ini</div>
                            <div class="text-2xl font-bold text-base-content">Rp {{ number_format($salesToday, 0, ',', '.') }}</div>
                            <div class="text-xs text-base-content/50">{{ $trxToday }} transaksi</div>
                        </div>
                    </div>
                </x-card>

                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-info/10 text-info">
                            <x-icon name="o-shopping-cart" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Jumlah Transaksi</div>
                            <div class="text-2xl font-bold text-base-content">{{ number_format($trxToday) }}</div>
                            <div class="text-xs text-base-content/50">Hari ini</div>
                        </div>
                    </div>
                </x-card>

                <x-card class="bg-base-100 shadow-sm border border-base-200">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-warning/10 text-warning">
                            <x-icon name="o-cube" class="w-6 h-6" />
                        </div>
                        <div>
                            <div class="text-sm text-base-content/70">Total Produk</div>
                            <div class="text-2xl font-bold text-base-content">{{ number_format($totalProducts) }}</div>
                            @if($lowStockProducts->count() > 0)
                                <div class="text-xs text-error font-medium">{{ $lowStockProducts->count() }} stok tipis!</div>
                            @else
                                <div class="text-xs text-success">Semua stok aman</div>
                            @endif
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Operator Tables --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-base-content">Transaksi Saya Terbaru</h2>
                        <x-button label="Lihat Semua" icon-right="o-arrow-right" link="{{ route('operator.history') }}" wire:navigate class="btn-ghost btn-sm" />
                    </div>
                    <x-card class="p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="table w-full">
                                <thead>
                                    <tr>
                                        <th>No. Nota</th>
                                        <th>Total</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTrx as $trx)
                                    <tr>
                                        <td class="font-medium text-base-content">#{{ $trx->invoice_number }}</td>
                                        <td class="font-bold text-success">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="text-xs">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-base-content/60 py-6">Belum ada transaksi hari ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-card>
                </div>

                <div>
                    <h2 class="mb-4 text-lg font-semibold text-base-content">Peringatan Stok Tipis</h2>
                    <x-card class="bg-base-100 shadow-sm border border-base-200">
                        @forelse($lowStockProducts as $product)
                            <div class="flex items-center justify-between border-b border-base-200 pb-3 last:border-0 last:pb-0">
                                <div>
                                    <div class="font-medium text-base-content text-sm">{{ $product->name }}</div>
                                    <div class="text-xs text-base-content/60">{{ $product->sku }}</div>
                                </div>
                                <x-badge value="{{ $product->stock }} sisa" class="badge-error badge-sm font-semibold" />
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-4 text-center text-base-content/60">
                                <x-icon name="o-check-circle" class="mb-2 h-8 w-8 text-success" />
                                <p class="text-sm">Semua stok aman!</p>
                            </div>
                        @endforelse
                    </x-card>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
