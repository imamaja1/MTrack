<?php

namespace App\Livewire\Admin;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Laporan Keuangan')]
class FinancialReport extends Component
{
    public string $filterMonth;

    public string $filterYear;

    public function mount(): void
    {
        $this->filterMonth = date('m');
        $this->filterYear = date('Y');
    }

    public function with(): array
    {
        $adminId = Auth::id();

        $startDate = Carbon::create($this->filterYear, $this->filterMonth, 1)->startOfMonth();
        $endDate = Carbon::create($this->filterYear, $this->filterMonth, 1)->endOfMonth();

        $transactions = Transaction::where('admin_id', $adminId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $totalRevenue = $transactions->sum('total_amount');
        $totalTax = $transactions->sum('tax_amount');
        $netRevenue = $totalRevenue - $totalTax;
        $totalCogs = $transactions->sum('total_cost');
        $grossProfit = $netRevenue - $totalCogs;

        $expenses = Expense::where('admin_id', $adminId)
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $totalExpense = $expenses->sum('amount');
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
            ],
        ];
    }

    public function render()
    {
        return view('livewire.admin.financial-report');
    }
}
