<?php

namespace App\Livewire;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public int $pollInterval = 30;

    public function with(): array
    {
        $user = Auth::user();
        $data = $this->computeData($user);

        return compact('data');
    }

    public function refresh(): void
    {
        //
    }

    public function render()
    {
        return view('livewire.dashboard');
    }

    private function computeData($user): array
    {
        return match ($user->role) {
            'superadmin' => $this->superadminData(),
            'admin' => $this->adminData($user),
            default => $this->operatorData($user),
        };
    }

    private function superadminData(): array
    {
        return [
            'role' => 'superadmin',
            'totalAdmins' => User::where('role', 'admin')->count(),
            'totalOperators' => User::where('role', 'operator')->count(),
            'totalSecurityLogs' => DB::table('security_logs')->count(),
            'newAdminsMonth' => User::where('role', 'admin')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'recentLogs' => DB::table('security_logs')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
        ];
    }

    private function adminData($user): array
    {
        $adminId = $user->id;

        $products = Product::where('admin_id', $adminId)->get();
        $today = today();
        $thisMonth = now()->month;
        $thisYear = now()->year;

        $salesToday = Transaction::where('admin_id', $adminId)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $profitToday = Transaction::where('admin_id', $adminId)
            ->whereDate('created_at', $today)
            ->sum('profit');

        $trxToday = Transaction::where('admin_id', $adminId)
            ->whereDate('created_at', $today)
            ->count();

        $salesThisMonth = Transaction::where('admin_id', $adminId)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum('total_amount');

        $profitThisMonth = Transaction::where('admin_id', $adminId)
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum('profit');

        $expenseThisMonth = Expense::where('admin_id', $adminId)
            ->whereMonth('expense_date', $thisMonth)
            ->whereYear('expense_date', $thisYear)
            ->sum('amount');

        $netProfitThisMonth = $profitThisMonth - $expenseThisMonth;
        $grossMargin = $salesThisMonth > 0
            ? round(($profitThisMonth / $salesThisMonth) * 100)
            : 0;

        $lowStockProducts = $products->filter(fn ($p) => $p->stock <= $p->min_stock_alert)->values();

        return [
            'role' => 'admin',
            'totalProducts' => $products->count(),
            'lowStockProducts' => $lowStockProducts,
            'salesToday' => $salesToday,
            'profitToday' => $profitToday,
            'trxToday' => $trxToday,
            'salesThisMonth' => $salesThisMonth,
            'profitThisMonth' => $profitThisMonth,
            'expenseThisMonth' => $expenseThisMonth,
            'netProfitThisMonth' => $netProfitThisMonth,
            'grossMargin' => $grossMargin,
            'recentTrx' => Transaction::with('operator')
                ->where('admin_id', $adminId)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentExpenses' => Expense::with('category')
                ->where('admin_id', $adminId)
                ->orderByDesc('expense_date')
                ->limit(5)
                ->get(),
        ];
    }

    private function operatorData($user): array
    {
        $adminId = $user->admin_id;
        $today = today();

        $products = Product::where('admin_id', $adminId)->get();

        $salesToday = Transaction::where('admin_id', $adminId)
            ->where('operator_id', $user->id)
            ->whereDate('created_at', $today)
            ->sum('total_amount');

        $trxToday = Transaction::where('admin_id', $adminId)
            ->where('operator_id', $user->id)
            ->whereDate('created_at', $today)
            ->count();

        $lowStockProducts = $products->filter(fn ($p) => $p->stock <= $p->min_stock_alert)->values();

        return [
            'role' => 'operator',
            'totalProducts' => $products->count(),
            'salesToday' => $salesToday,
            'trxToday' => $trxToday,
            'lowStockProducts' => $lowStockProducts,
            'recentTrx' => Transaction::with('operator')
                ->where('admin_id', $adminId)
                ->where('operator_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ];
    }
}
