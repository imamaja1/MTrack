<?php

use App\Http\Controllers\SocialAuthController;
use App\Livewire\Admin\Coupons;
use App\Livewire\Admin\Expenses;
use App\Livewire\Admin\FinancialReport;
use App\Livewire\Admin\MemberConfig;
use App\Livewire\Admin\OperatorManagement;
use App\Livewire\Admin\ProductManagement;
use App\Livewire\Admin\Stocks;
use App\Livewire\Admin\TaxConfig;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:superadmin'])->group(function () {
    Route::view('/superadmin/security', 'livewire.pages.superadmin.security')->name('superadmin.security');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/operators', OperatorManagement::class)->name('admin.operators');
    Route::get('/admin/stocks', Stocks::class)->name('admin.stocks');
    Route::get('/admin/products', ProductManagement::class)->name('admin.products');
    Route::get('/admin/coupons', Coupons::class)->name('admin.coupons');
    Route::get('/admin/member-config', MemberConfig::class)->name('admin.member_config');
    Route::get('/admin/tax-config', TaxConfig::class)->name('admin.tax_config');
    Route::get('/admin/expenses', Expenses::class)->name('admin.expenses');
    Route::get('/admin/financial-report', FinancialReport::class)->name('admin.financial_report');
});

Route::middleware(['auth', 'verified', 'role:admin,operator'])->group(function () {
    Route::view('/pos', 'livewire.pages.operator.pos')->name('pos');
    Route::view('/operator/history', 'livewire.pages.operator.history')->name('operator.history');
    Route::view('/members', 'livewire.pages.shared.members')->name('members');
    Route::view('/restock', 'livewire.pages.shared.restock')->name('restock');
});

require __DIR__.'/settings.php';

// Google Socialite Auth Routes
Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
