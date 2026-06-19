<?php

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
});
