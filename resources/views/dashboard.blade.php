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
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Halo, {{ $user->name }} 👋</h1>
                <p class="text-zinc-500 dark:text-zinc-400">{{ now()->translatedFormat('l, d F Y') }} — Berikut ringkasan bisnis Anda.</p>
            </div>
            <flux:badge color="{{ $user->role === 'superadmin' ? 'red' : ($user->role === 'admin' ? 'blue' : 'green') }}" size="sm" class="capitalize mt-1">
                {{ $user->role }}
            </flux:badge>
        </div>

        {{-- ─── SUPERADMIN VIEW ─────────────────────────────────────────── --}}
        @if ($user->role === 'superadmin')
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400">
                            <flux:icon.users variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Total Admin Toko</div>
                            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalAdmins) }}</div>
                            <div class="text-xs text-zinc-400">+{{ $newAdminsMonth }} bulan ini</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                            <flux:icon.user-group variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Total Operator</div>
                            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalOperators) }}</div>
                            <div class="text-xs text-zinc-400">Kasir aktif terdaftar</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                            <flux:icon.shield-exclamation variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Log Keamanan</div>
                            <div class="text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalSecurityLogs) }}</div>
                            <div class="text-xs text-zinc-400">Total kejadian tercatat</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card class="relative overflow-hidden bg-zinc-900 dark:bg-zinc-800 border-none">
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/30 to-purple-600/30"></div>
                    <div class="relative z-10 flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                            <flux:icon.building-storefront variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-300">Total Toko</div>
                            <div class="text-3xl font-bold text-white">{{ number_format($totalAdmins + $totalOperators) }}</div>
                            <div class="text-xs text-zinc-400">Pengguna aktif</div>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Security Log Table --}}
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Log Keamanan Terbaru</h2>
                    <flux:button :href="route('superadmin.security')" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-right">Lihat Semua</flux:button>
                </div>
                <flux:card class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                            <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                                <tr>
                                    <th class="px-6 py-3">Aktivitas</th>
                                    <th class="px-6 py-3">Alamat IP</th>
                                    <th class="px-6 py-3">Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentLogs as $log)
                                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                    <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">
                                        <flux:badge color="red" size="sm" class="mr-2">Alert</flux:badge>
                                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $log->event_type)) }}
                                    </td>
                                    <td class="px-6 py-4">{{ $log->ip_address }}</td>
                                    <td class="px-6 py-4">{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-zinc-500">Belum ada aktivitas keamanan yang mencurigakan.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </flux:card>
            </div>

        {{-- ─── ADMIN VIEW ──────────────────────────────────────────────── --}}
        @elseif ($user->role === 'admin')
            {{-- Stats Row 1: Today --}}
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">Ringkasan Hari Ini</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:card>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                <flux:icon.banknotes variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500">Pendapatan</div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($salesToday, 0, ',', '.') }}</div>
                                <div class="text-xs text-zinc-400">{{ $trxToday }} transaksi</div>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                <flux:icon.arrow-trending-up variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500">Laba Kotor</div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($profitToday, 0, ',', '.') }}</div>
                                <div class="text-xs text-zinc-400">Pendapatan - HPP</div>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400">
                                <flux:icon.cube variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500">Total Produk</div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalProducts) }}</div>
                                @if($lowStockProducts->count() > 0)
                                    <div class="text-xs text-red-500 font-medium">{{ $lowStockProducts->count() }} stok tipis!</div>
                                @else
                                    <div class="text-xs text-emerald-500">Semua stok aman</div>
                                @endif
                            </div>
                        </div>
                    </flux:card>
                </div>
            </div>

            {{-- Stats Row 2: This Month --}}
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400">Rekap Bulan {{ now()->translatedFormat('F Y') }}</p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <flux:card>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                <flux:icon.chart-bar-square variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500">Total Omset</div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($salesThisMonth, 0, ',', '.') }}</div>
                                <div class="text-xs text-zinc-400">Pendapatan bulan ini</div>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card>
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400">
                                <flux:icon.arrow-trending-down variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-500">Total Pengeluaran</div>
                                <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($expenseThisMonth, 0, ',', '.') }}</div>
                                <div class="text-xs text-zinc-400">Operasional bulan ini</div>
                            </div>
                        </div>
                    </flux:card>

                    <flux:card class="relative overflow-hidden bg-zinc-900 dark:bg-zinc-800 border-none">
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-600/30 to-purple-600/30"></div>
                        <div class="relative z-10 flex items-center gap-4">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-white/10 text-white">
                                <flux:icon.sparkles variant="solid" />
                            </div>
                            <div>
                                <div class="text-sm text-zinc-300">Laba Bersih</div>
                                <div class="text-2xl font-bold {{ $netProfitThisMonth >= 0 ? 'text-white' : 'text-red-400' }}">
                                    Rp {{ number_format($netProfitThisMonth, 0, ',', '.') }}
                                </div>
                                <div class="text-xs text-zinc-400">Laba kotor - pengeluaran</div>
                            </div>
                        </div>
                    </flux:card>
                </div>
            </div>

            {{-- Tables Row --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Recent Transactions --}}
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Transaksi Terbaru</h2>
                        <flux:button :href="route('operator.history')" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-right">Lihat Semua</flux:button>
                    </div>
                    <flux:card class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                                    <tr>
                                        <th class="px-6 py-3">No. Nota</th>
                                        <th class="px-6 py-3">Kasir</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTrx as $trx)
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">#{{ $trx->invoice_number }}</td>
                                        <td class="px-6 py-4">{{ $trx->operator->name }}</td>
                                        <td class="px-6 py-4 font-bold text-emerald-600">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-xs">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-zinc-500">Belum ada transaksi hari ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </flux:card>
                </div>

                {{-- Sidebar: Low Stock + Recent Expenses --}}
                <div class="flex flex-col gap-6">
                    {{-- Low Stock Alert --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Stok Tipis</h2>
                            <flux:button :href="route('admin.stocks')" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-right">Stok</flux:button>
                        </div>
                        <flux:card class="flex flex-col gap-3">
                            @forelse($lowStockProducts as $product)
                                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white text-sm">{{ $product->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $product->sku }}</div>
                                    </div>
                                    <flux:badge color="red" size="sm">{{ $product->stock }} sisa</flux:badge>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-4 text-center text-zinc-500">
                                    <flux:icon.check-circle class="mb-2 h-8 w-8 text-emerald-500" />
                                    <p class="text-sm">Semua stok aman!</p>
                                </div>
                            @endforelse
                        </flux:card>
                    </div>

                    {{-- Recent Expenses --}}
                    <div>
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Pengeluaran Terakhir</h2>
                            <flux:button :href="route('admin.expenses')" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-right">Kelola</flux:button>
                        </div>
                        <flux:card class="flex flex-col gap-3">
                            @forelse($recentExpenses as $expense)
                                <div class="flex items-center justify-between border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                                    <div>
                                        <div class="font-medium text-zinc-900 dark:text-white text-sm">{{ $expense->title }}</div>
                                        <div class="text-xs text-zinc-500">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</div>
                                    </div>
                                    <span class="text-sm font-semibold text-red-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</span>
                                </div>
                            @empty
                                <div class="flex flex-col items-center justify-center py-4 text-center text-zinc-500">
                                    <flux:icon.inbox class="mb-2 h-8 w-8 text-zinc-300" />
                                    <p class="text-sm">Belum ada pengeluaran.</p>
                                </div>
                            @endforelse
                        </flux:card>
                    </div>
                </div>
            </div>

        {{-- ─── OPERATOR VIEW ──────────────────────────────────────────── --}}
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                            <flux:icon.banknotes variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Penjualan Saya Hari Ini</div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($salesToday, 0, ',', '.') }}</div>
                            <div class="text-xs text-zinc-400">{{ $trxToday }} transaksi</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                            <flux:icon.shopping-cart variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Jumlah Transaksi</div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($trxToday) }}</div>
                            <div class="text-xs text-zinc-400">Hari ini</div>
                        </div>
                    </div>
                </flux:card>

                <flux:card>
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400">
                            <flux:icon.cube variant="solid" />
                        </div>
                        <div>
                            <div class="text-sm text-zinc-500">Total Produk</div>
                            <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($totalProducts) }}</div>
                            @if($lowStockProducts->count() > 0)
                                <div class="text-xs text-red-500 font-medium">{{ $lowStockProducts->count() }} stok tipis!</div>
                            @else
                                <div class="text-xs text-emerald-500">Semua stok aman</div>
                            @endif
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Operator Tables --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Transaksi Saya Terbaru</h2>
                        <flux:button :href="route('operator.history')" variant="ghost" size="sm" wire:navigate icon-trailing="arrow-right">Lihat Semua</flux:button>
                    </div>
                    <flux:card class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                                <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                                    <tr>
                                        <th class="px-6 py-3">No. Nota</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Waktu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentTrx as $trx)
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <td class="px-6 py-4 font-medium text-zinc-900 dark:text-white">#{{ $trx->invoice_number }}</td>
                                        <td class="px-6 py-4 font-bold text-emerald-600">Rp {{ number_format($trx->total_amount, 0, ',', '.') }}</td>
                                        <td class="px-6 py-4 text-xs">{{ $trx->created_at->format('H:i, d M') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-zinc-500">Belum ada transaksi hari ini.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </flux:card>
                </div>

                <div>
                    <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-white">Peringatan Stok Tipis</h2>
                    <flux:card class="flex flex-col gap-3">
                        @forelse($lowStockProducts as $product)
                            <div class="flex items-center justify-between border-b border-zinc-100 pb-3 last:border-0 last:pb-0 dark:border-zinc-800">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white text-sm">{{ $product->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $product->sku }}</div>
                                </div>
                                <flux:badge color="red" size="sm">{{ $product->stock }} sisa</flux:badge>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-4 text-center text-zinc-500">
                                <flux:icon.check-circle class="mb-2 h-8 w-8 text-emerald-500" />
                                <p class="text-sm">Semua stok aman!</p>
                            </div>
                        @endforelse
                    </flux:card>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
