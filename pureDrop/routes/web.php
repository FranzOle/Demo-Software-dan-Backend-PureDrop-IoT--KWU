<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\TransactionController;

Route::get('/', [TransactionController::class, 'index'])->name('order.form');
Route::post('/order', [TransactionController::class, 'store'])->name('order.store');
Route::get('/payment/{order_id}', [TransactionController::class, 'payment'])->name('order.payment');
Route::post('/payment/confirm/{order_id}', [TransactionController::class, 'confirm'])->name('order.confirm');
Route::get('/success', [TransactionController::class, 'success'])->name('order.success');
Route::post('/payment/callback', [TransactionController::class, 'callback'])->name('order.callback');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('admin')->middleware(['auth', 'is_admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.home');    
    
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/settings', [AdminDashboardController::class, 'updateSetting'])->name('admin.settings.update');
});
