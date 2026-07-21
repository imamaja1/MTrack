<?php

use Livewire\Component;
use App\Models\Transaction;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

new class extends Component
{
    public $filterMonth;
    public $filterYear;

    public function mount()
    {
        $this->filterMonth = date('m');
        $this->filterYear = date('Y');
    }

    public function with()
    {
        $adminId = Auth::id();
        
        $startDate = Carbon::create($this->filterYear, $this->filterMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->filterYear, $this->filterMonth, 1)->endOfMonth();

        // 1. Total Pendapatan & HPP dari Transaksi (Omset)
        $transactions = Transaction::where('admin_id', $adminId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        $totalRevenue = $transactions->sum('total_amount');
        $totalCogs = $transactions->sum('total_cost');
        $grossProfit = $totalRevenue - $totalCogs;

        // 2. Total Pengeluaran
        $expenses = Expense::where('admin_id', $adminId)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();
            
        $totalExpense = $expenses->sum('amount');
        
        // 3. Laba Bersih
        $netProfit = $grossProfit - $totalExpense;

        return [
            'data' => [
                'totalRevenue' => $totalRevenue,
                'totalCogs' => $totalCogs,
                'grossProfit' => $grossProfit,
                'totalExpense' => $totalExpense,
                'netProfit' => $netProfit,
                'transactionsCount' => $transactions->count(),
                'expensesList' => $expenses,
            ]
        ];
    }
};
?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col items-start gap-4 mb-4 md:flex-row md:justify-between md:items-center">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Laporan Keuangan</h1>
                <p class="text-zinc-500 dark:text-zinc-400">Ringkasan pendapatan, HPP, pengeluaran, dan laba bersih toko.</p>
            </div>
            
            <div class="flex gap-2">
                <flux:select wire:model.live="filterMonth" class="w-32">
                    @for($i=1; $i<=12; $i++)
                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                    @endfor
                </flux:select>
                
                <flux:select wire:model.live="filterYear" class="w-24">
                    @for($i=date('Y'); $i>=date('Y')-3; $i--)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </flux:select>
            </div>
        </div>

        <!-- Rekap Cards -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-zinc-500">Total Omset (Pendapatan)</div>
                    <div class="text-2xl font-bold text-zinc-900 dark:text-white">Rp {{ number_format($data['totalRevenue'], 0, ',', '.') }}</div>
                    <div class="text-xs text-zinc-400 mt-1">Dari {{ $data['transactionsCount'] }} transaksi</div>
                </div>
            </flux:card>
            
            <flux:card>
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-zinc-500">Total HPP (Modal Barang)</div>
                    <div class="text-2xl font-bold text-orange-600">Rp {{ number_format($data['totalCogs'], 0, ',', '.') }}</div>
                    <div class="text-xs text-zinc-400 mt-1">Harga modal produk terjual</div>
                </div>
            </flux:card>
            
            <flux:card>
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-zinc-500">Total Pengeluaran</div>
                    <div class="text-2xl font-bold text-red-600">Rp {{ number_format($data['totalExpense'], 0, ',', '.') }}</div>
                    <div class="text-xs text-zinc-400 mt-1">Operasional & lainnya</div>
                </div>
            </flux:card>
            
            <flux:card class="bg-zinc-900 dark:bg-zinc-800 border-none relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-purple-500/20"></div>
                <div class="relative z-10 flex flex-col gap-1">
                    <div class="text-sm font-medium text-zinc-300">Laba Bersih</div>
                    <div class="text-3xl font-bold text-white">Rp {{ number_format($data['netProfit'], 0, ',', '.') }}</div>
                    <div class="text-xs text-zinc-400 mt-1">Laba Kotor - Pengeluaran</div>
                </div>
            </flux:card>
        </div>

        <!-- Detail Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Rincian Laba -->
            <flux:card class="lg:col-span-1">
                <flux:heading size="lg" class="mb-4">Kalkulasi Laba</flux:heading>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Total Omset</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">Rp {{ number_format($data['totalRevenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Total HPP</span>
                        <span class="font-medium text-orange-600">- Rp {{ number_format($data['totalCogs'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800 font-bold">
                        <span class="text-zinc-800 dark:text-zinc-200">Laba Kotor</span>
                        <span class="text-zinc-900 dark:text-zinc-100">Rp {{ number_format($data['grossProfit'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500">Total Pengeluaran</span>
                        <span class="font-medium text-red-600">- Rp {{ number_format($data['totalExpense'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pt-2 text-lg font-black">
                        <span class="text-zinc-900 dark:text-white">Laba Bersih</span>
                        <span class="text-indigo-600 dark:text-indigo-400">Rp {{ number_format($data['netProfit'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </flux:card>

            <!-- Rincian Pengeluaran -->
            <flux:card class="lg:col-span-2 p-0">
                <div class="p-4 border-b border-zinc-100 dark:border-zinc-800">
                    <flux:heading size="lg">Rincian Pengeluaran Bulan Ini</flux:heading>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-500 dark:text-zinc-400">
                        <thead class="bg-zinc-50 text-xs uppercase text-zinc-700 dark:bg-zinc-800/50 dark:text-zinc-300">
                            <tr>
                                <th class="px-4 py-2">Tanggal</th>
                                <th class="px-4 py-2">Keterangan</th>
                                <th class="px-4 py-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['expensesList'] as $expense)
                                <tr class="border-b border-zinc-100 dark:border-zinc-800 last:border-0">
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M') }}</td>
                                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-200">{{ $expense->title }}</td>
                                    <td class="px-4 py-3 text-right text-red-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-zinc-500">
                                        Tidak ada catatan pengeluaran di bulan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </flux:card>
        </div>
    </div>
</div>
