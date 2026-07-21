<?php

use App\Http\Controllers\SocialAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:superadmin'])->group(function () {
    Route::view('/superadmin/security', 'superadmin.security')->name('superadmin.security');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::view('/admin/operators', 'admin.operators')->name('admin.operators');
    Route::view('/admin/stocks', 'admin.stocks')->name('admin.stocks');
    Route::view('/admin/products', 'admin.products')->name('admin.products');
    Route::view('/admin/coupons', 'admin.coupons')->name('admin.coupons');
    Route::view('/admin/member-config', 'admin.member-config')->name('admin.member_config');
    Route::view('/admin/expenses', 'admin.expenses')->name('admin.expenses');
    Route::view('/admin/financial-report', 'admin.financial-report')->name('admin.financial_report');
});

Route::middleware(['auth', 'verified', 'role:admin,operator'])->group(function () {
    Route::view('/pos', 'operator.pos')->name('pos');
    Route::view('/operator/history', 'operator.history')->name('operator.history');
    Route::view('/members', 'shared.members')->name('members');
    Route::view('/restock', 'shared.restock')->name('restock');
});

require __DIR__.'/settings.php';

// Google Socialite Auth Routes
Route::get('/auth/google', [SocialAuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])->name('auth.google.callback');
