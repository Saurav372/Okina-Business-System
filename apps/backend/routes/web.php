<?php

use App\Http\Controllers\Admin\AdminOrderActionController;
use App\Http\Controllers\Admin\AdminOrderDesignFileController;
use App\Http\Controllers\Admin\OrderDetailController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\StoredFileAccessController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('signed')->prefix('files')->group(function (): void {
    Route::get('/{file:public_id}/preview', [StoredFileAccessController::class, 'preview'])->name('files.preview');
    Route::get('/{file:public_id}/download', [StoredFileAccessController::class, 'download'])->name('files.download');
});

Route::middleware('guest:customer')->group(function () {
    Route::get('/register', [CustomerAuthController::class, 'register'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'storeRegistration'])->name('customer.register.store');
    Route::get('/login', [CustomerAuthController::class, 'login'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'storeLogin'])->name('customer.login.store');
});

Route::middleware('customer.access')->group(function () {
    Route::get('/account', [CustomerAuthController::class, 'account'])->name('customer.account');
    Route::post('/logout', [CustomerAuthController::class, 'destroy'])->name('customer.logout');
});

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'store'])->name('admin.login');
    Route::get('/forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('admin.password.request');
    Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->name('admin.password.email');
    Route::get('/reset-password/{token}', [AdminAuthController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [AdminAuthController::class, 'updatePassword'])->name('admin.password.update');
    Route::get('/two-factor-challenge', [AdminAuthController::class, 'twoFactorChallenge'])->name('admin.two-factor.challenge');
});

Route::middleware(['auth', 'dashboard.access'])->prefix('admin')->group(function () {
    Route::get('/', [AdminAuthController::class, 'index'])->name('admin.dashboard');
    Route::get('/profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::get('/security', [AdminAuthController::class, 'security'])->name('admin.security');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
    Route::get('/orders/{order:public_id}/detail', [OrderDetailController::class, 'show'])->name('admin.orders.detail');
    Route::post('/orders/{order:public_id}/status', [AdminOrderActionController::class, 'updateStatus'])->name('admin.orders.status.update');
    Route::post('/orders/{order:public_id}/shipping', [AdminOrderActionController::class, 'updateShipping'])->name('admin.orders.shipping.update');
    Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('admin.sales_orders.create');
    Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('admin.sales_orders.store');
    Route::put('/sales-orders/{order:public_id}', [SalesOrderController::class, 'update'])->name('admin.sales_orders.update');
    Route::get('/skus/search', [SalesOrderController::class, 'skuSearch'])->name('admin.skus.search');
    // B2.2.8 — Admin design-file access bridge (order-scoped, policy-gated)
    Route::get('/orders/{order:public_id}/files/{file:public_id}/preview', [AdminOrderDesignFileController::class, 'preview'])->name('admin.orders.files.preview')->withoutScopedBindings();
    Route::get('/orders/{order:public_id}/files/{file:public_id}/download', [AdminOrderDesignFileController::class, 'download'])->name('admin.orders.files.download')->withoutScopedBindings();
});
