<?php

use App\Http\Controllers\Admin\AdminOrderActionController;
use App\Http\Controllers\Admin\AdminOrderDesignFileController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\LeadActivityController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\OrderDetailController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\RefundController;
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
    Route::post('/orders/{order:public_id}/payments', [AdminOrderActionController::class, 'recordPayment'])->name('admin.orders.payments.record');
    Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('admin.sales_orders.create');
    Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('admin.sales_orders.store');
    Route::put('/sales-orders/{order:public_id}', [SalesOrderController::class, 'update'])->name('admin.sales_orders.update');
    Route::get('/skus/search', [SalesOrderController::class, 'skuSearch'])->name('admin.skus.search');
    Route::get('/leads', [LeadController::class, 'index'])->name('admin.leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->name('admin.leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('admin.leads.show');
    Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('admin.leads.update');
    Route::get('/leads/{lead}/activities', [LeadActivityController::class, 'index'])->name('admin.leads.activities.index');
    Route::post('/leads/{lead}/activities', [LeadActivityController::class, 'store'])->name('admin.leads.activities.store');
    Route::post('/quotations', [QuotationController::class, 'store'])->name('admin.quotations.store');
    Route::patch('/quotations/{quotation:public_id}/status', [QuotationController::class, 'updateStatus'])->name('admin.quotations.status.update');
    Route::post('/quotations/{quotation:public_id}/convert', [QuotationController::class, 'convert'])->name('admin.quotations.convert');
    // B2.2.8 — Admin design-file access bridge (order-scoped, policy-gated)
    Route::get('/orders/{order:public_id}/files/{file:public_id}/preview', [AdminOrderDesignFileController::class, 'preview'])->name('admin.orders.files.preview')->withoutScopedBindings();
    Route::get('/orders/{order:public_id}/files/{file:public_id}/download', [AdminOrderDesignFileController::class, 'download'])->name('admin.orders.files.download')->withoutScopedBindings();

    // Finance payment & refund boundary routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('admin.payments.show');
    Route::get('/refunds', [RefundController::class, 'index'])->name('admin.refunds.index');
    Route::post('/refunds', [RefundController::class, 'store'])->name('admin.refunds.store');
    Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve'])->name('admin.refunds.approve');
    Route::post('/refunds/{refund}/process', [RefundController::class, 'process'])->name('admin.refunds.process');
    Route::post('/refunds/{refund}/cancel', [RefundController::class, 'cancel'])->name('admin.refunds.cancel');
    Route::get('/refunds/{refund}', [RefundController::class, 'show'])->name('admin.refunds.show');

    // Expense Categories admin routes
    Route::apiResource('/expense-categories', ExpenseCategoryController::class)->names([
        'index' => 'admin.expense_categories.index',
        'store' => 'admin.expense_categories.store',
        'show' => 'admin.expense_categories.show',
        'update' => 'admin.expense_categories.update',
        'destroy' => 'admin.expense_categories.destroy',
    ]);

    // Expenses admin routes
    Route::get('/expenses/report', [ExpenseController::class, 'reportSummary'])->name('admin.expenses.report');
    Route::apiResource('/expenses', ExpenseController::class)->names([
        'index' => 'admin.expenses.index',
        'store' => 'admin.expenses.store',
        'show' => 'admin.expenses.show',
        'update' => 'admin.expenses.update',
        'destroy' => 'admin.expenses.destroy',
    ]);
    Route::post('/expenses/{expense:public_id}/submit', [ExpenseController::class, 'submit'])->name('admin.expenses.submit');
    Route::post('/expenses/{expense:public_id}/approve', [ExpenseController::class, 'approve'])->name('admin.expenses.approve');
    Route::post('/expenses/{expense:public_id}/reject', [ExpenseController::class, 'reject'])->name('admin.expenses.reject');
});
