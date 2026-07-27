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
        $totalTax = $transactions->sum('tax_amount');
        $netRevenue = $totalRevenue - $totalTax;
        $totalCogs = $transactions->sum('total_cost');
        $grossProfit = $netRevenue - $totalCogs;

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
                'totalTax' => $totalTax,
                'netRevenue' => $netRevenue,
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
                <h1 class="text-2xl font-bold text-base-content">Laporan Keuangan</h1>
                <p class="text-sm text-base-content/70">Ringkasan pendapatan, HPP, pengeluaran, dan laba bersih toko.</p>
            </div>
            
            @php
                $months = array_map(fn($i) => [
                    'id' => str_pad($i, 2, '0', STR_PAD_LEFT),
                    'name' => date('F', mktime(0, 0, 0, $i, 1))
                ], range(1, 12));

                $years = array_map(fn($i) => [
                    'id' => (string) $i,
                    'name' => (string) $i
                ], range((int)date('Y'), (int)date('Y') - 3));
            @endphp
            
            <div class="flex gap-2">
                <x-select wire:model.live="filterMonth" :options="$months" class="w-36" />
                <x-select wire:model.live="filterYear" :options="$years" class="w-28" />
            </div>
        </div>

        <!-- Rekap Cards -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-5">
            <x-card class="bg-base-100 shadow-sm border border-base-200">
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-base-content/70">Total Omset (Bruto)</div>
                    <div class="text-2xl font-bold text-base-content">Rp {{ number_format($data['totalRevenue'], 0, ',', '.') }}</div>
                    <div class="text-xs text-base-content/50 mt-1">Dari {{ $data['transactionsCount'] }} transaksi</div>
                </div>
            </x-card>
            
            <x-card class="bg-base-100 shadow-sm border border-base-200">
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-base-content/70">Pajak (PPN) Terkumpul</div>
                    <div class="text-2xl font-bold text-info">Rp {{ number_format($data['totalTax'], 0, ',', '.') }}</div>
                    <div class="text-xs text-base-content/50 mt-1">Titipan pajak transaksi</div>
                </div>
            </x-card>
            
            <x-card class="bg-base-100 shadow-sm border border-base-200">
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-base-content/70">Total HPP (Modal Barang)</div>
                    <div class="text-2xl font-bold text-warning">Rp {{ number_format($data['totalCogs'], 0, ',', '.') }}</div>
                    <div class="text-xs text-base-content/50 mt-1">Harga modal produk terjual</div>
                </div>
            </x-card>
            
            <x-card class="bg-base-100 shadow-sm border border-base-200">
                <div class="flex flex-col gap-1">
                    <div class="text-sm font-medium text-base-content/70">Total Pengeluaran</div>
                    <div class="text-2xl font-bold text-error">Rp {{ number_format($data['totalExpense'], 0, ',', '.') }}</div>
                    <div class="text-xs text-base-content/50 mt-1">Operasional & lainnya</div>
                </div>
            </x-card>
            
            <x-card class="bg-neutral text-neutral-content shadow-sm border-none relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-primary/30 to-orange-500/15"></div>
                <div class="relative z-10 flex flex-col gap-1">
                    <div class="text-sm font-medium text-neutral-content/80">Laba Bersih</div>
                    <div class="text-3xl font-bold text-white">Rp {{ number_format($data['netProfit'], 0, ',', '.') }}</div>
                    <div class="text-xs text-neutral-content/60 mt-1">Laba Kotor - Pengeluaran</div>
                </div>
            </x-card>
        </div>

        <!-- Detail Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Rincian Laba -->
            <x-card class="lg:col-span-1 bg-base-100 shadow-sm border border-base-200">
                <h3 class="text-lg font-bold text-base-content mb-4">Kalkulasi Laba</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between pb-2 border-b border-base-200">
                        <span class="text-base-content/70">Total Omset (Bruto)</span>
                        <span class="font-medium text-base-content">Rp {{ number_format($data['totalRevenue'], 0, ',', '.') }}</span>
                    </div>
                    @if($data['totalTax'] > 0)
                    <div class="flex justify-between pb-2 border-b border-base-200">
                        <span class="text-base-content/70">Pajak (PPN) Terkumpul</span>
                        <span class="font-medium text-info">- Rp {{ number_format($data['totalTax'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-base-200 font-semibold">
                        <span class="text-base-content">Omset Bersih (Netto)</span>
                        <span class="text-base-content">Rp {{ number_format($data['netRevenue'], 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between pb-2 border-b border-base-200">
                        <span class="text-base-content/70">Total HPP</span>
                        <span class="font-medium text-warning">- Rp {{ number_format($data['totalCogs'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-base-200 font-bold">
                        <span class="text-base-content">Laba Kotor</span>
                        <span class="text-base-content">Rp {{ number_format($data['grossProfit'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pb-2 border-b border-base-200">
                        <span class="text-base-content/70">Total Pengeluaran</span>
                        <span class="font-medium text-error">- Rp {{ number_format($data['totalExpense'], 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between pt-2 text-lg font-black">
                        <span class="text-base-content">Laba Bersih</span>
                        <span class="text-primary">Rp {{ number_format($data['netProfit'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </x-card>

            <!-- Rincian Pengeluaran -->
            <x-card class="lg:col-span-2 p-0 bg-base-100 shadow-sm border border-base-200 overflow-hidden">
                <div class="p-4 border-b border-base-200">
                    <h3 class="text-lg font-bold text-base-content">Rincian Pengeluaran Bulan Ini</h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data['expensesList'] as $expense)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M') }}</td>
                                    <td class="font-medium text-base-content">{{ $expense->title }}</td>
                                    <td class="text-right text-error">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-base-content/60">
                                        Tidak ada catatan pengeluaran di bulan ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</div>
