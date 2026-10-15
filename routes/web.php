<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServedController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StatisticsController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('home') : redirect()->route('login'));

// ── Guests only ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');
});

// New branch + owner account (needs BRANCH_SETUP_KEY from .env)
Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
Route::post('/branches', [BranchController::class, 'store'])->middleware('throttle:5,1')->name('branches.store');

// ── Logged in ──────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/receipt-settings', [HomeController::class, 'receiptSettings'])->name('receipt.settings');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/update', [OrderController::class, 'update'])->name('orders.update');
    Route::post('/orders/serve', [OrderController::class, 'serve'])->name('orders.serve');
    Route::post('/orders/void', [OrderController::class, 'void'])->name('orders.void');
    Route::get('/order-notifications', [OrderController::class, 'notifications'])->name('orders.notifications');

    Route::get('/served', [ServedController::class, 'index'])->name('served');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // ── Admin only ─────────────────────────────────────────────────────────
    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/receipt', [SettingsController::class, 'saveReceipt'])->name('settings.receipt.save');
        Route::post('/settings/logo', [SettingsController::class, 'uploadLogo'])->name('settings.logo');
        Route::post('/settings/menu', [SettingsController::class, 'storeMenu'])->name('settings.menu.store');
        Route::put('/settings/menu/{menuItem}', [SettingsController::class, 'updateMenu'])->name('settings.menu.update');
        Route::delete('/settings/menu/{menuItem}', [SettingsController::class, 'destroyMenu'])->name('settings.menu.destroy');

        Route::get('/statistics', [StatisticsController::class, 'index'])->name('statistics');
        Route::get('/statistics/report', [StatisticsController::class, 'report'])->name('statistics.report');
        Route::post('/statistics/daily-cash', [StatisticsController::class, 'saveDailyCash'])->name('statistics.daily-cash');
        Route::post('/statistics/expenses', [StatisticsController::class, 'addExpense'])->name('statistics.expenses.store');

        Route::get('/cashiers/create', [CashierController::class, 'create'])->name('cashiers.create');
        Route::post('/cashiers', [CashierController::class, 'store'])->name('cashiers.store');
    });
});
