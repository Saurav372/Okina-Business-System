<?php

use App\Http\Controllers\Admin\AdminOrderActionController;
use App\Http\Controllers\Admin\AdminOrderDesignFileController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BulkOrderActionController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinanceLedgerController;
use App\Http\Controllers\Admin\GoogleSheetsConnectionController;
use App\Http\Controllers\Admin\GoogleSheetsSyncLogController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\InventoryMovementController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductMediaController;
use App\Http\Controllers\Admin\ProductSeoController;
use App\Http\Controllers\Admin\ProductSkuController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\PurchaseOrderAdminController;
use App\Http\Controllers\Admin\PurchaseOrderReceivingController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\SalesOrderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorOrderController;
use App\Http\Controllers\Admin\VendorOrderItemController;
use App\Http\Controllers\Admin\VendorPaymentController;
use App\Http\Controllers\Admin\WarehouseTransferController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
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
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::get('/security', [AdminAuthController::class, 'security'])->name('admin.security');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/products', [ProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('admin.products.variants.store');
    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('admin.products.variants.update')->scopeBindings();
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('admin.products.variants.destroy')->scopeBindings();
    Route::post('/products/{product}/skus/generate', [ProductSkuController::class, 'generate'])->name('admin.products.skus.generate');
    Route::put('/products/{product}/skus/{sku}', [ProductSkuController::class, 'update'])->name('admin.products.skus.update')->scopeBindings();
    Route::delete('/products/{product}/skus/{sku}', [ProductSkuController::class, 'destroy'])->name('admin.products.skus.destroy')->scopeBindings();
    // Product Media
    Route::post('/products/{product}/media', [ProductMediaController::class, 'store'])->name('admin.products.media.store');
    Route::post('/products/{product}/media/reorder', [ProductMediaController::class, 'reorder'])->name('admin.products.media.reorder');
    Route::post('/products/{product}/media/{media}/cover', [ProductMediaController::class, 'setCover'])->name('admin.products.media.cover')->scopeBindings();
    Route::delete('/products/{product}/media/{media}', [ProductMediaController::class, 'destroy'])->name('admin.products.media.destroy')->scopeBindings();
    // Product SEO
    Route::put('/products/{product}/seo', [ProductSeoController::class, 'update'])->name('admin.products.seo.update');
    // Purchase Orders & Vendor Stock In
    Route::get('/purchases', [PurchaseOrderAdminController::class, 'index'])->name('admin.purchases.index');
    Route::get('/purchases/create', [PurchaseOrderAdminController::class, 'create'])->name('admin.purchases.create');
    Route::get('/purchases/{vendorOrder:public_id}', [PurchaseOrderAdminController::class, 'show'])->name('admin.purchases.show');
    Route::post('/purchases/{vendorOrder:public_id}/receive', [PurchaseOrderReceivingController::class, 'receive'])->name('admin.purchases.receive');
    // Inventory & Stock Balances
    Route::get('/inventory', [InventoryController::class, 'index'])->name('admin.inventory.index');
    Route::post('/inventory/{sku}/adjust', [InventoryController::class, 'adjust'])->name('admin.inventory.adjust');
    Route::get('/inventory/movements', [InventoryMovementController::class, 'index'])->name('admin.inventory.movements.index');
    Route::get('/inventory/movements/export', [InventoryMovementController::class, 'export'])->name('admin.inventory.movements.export');
    // Warehouse Transfers (Multi-Location Logistics)
    Route::get('/inventory/transfers', [WarehouseTransferController::class, 'index'])->name('admin.inventory.transfers.index');
    Route::get('/inventory/transfers/create', [WarehouseTransferController::class, 'create'])->name('admin.inventory.transfers.create');
    Route::post('/inventory/transfers', [WarehouseTransferController::class, 'store'])->name('admin.inventory.transfers.store');
    Route::get('/inventory/transfers/{transfer}', [WarehouseTransferController::class, 'show'])->name('admin.inventory.transfers.show');
    Route::post('/inventory/transfers/{transfer}/ship', [WarehouseTransferController::class, 'ship'])->name('admin.inventory.transfers.ship');
    Route::post('/inventory/transfers/{transfer}/receive', [WarehouseTransferController::class, 'receive'])->name('admin.inventory.transfers.receive');
    Route::post('/inventory/transfers/{transfer}/cancel', [WarehouseTransferController::class, 'cancel'])->name('admin.inventory.transfers.cancel');
    Route::get('/orders/{order:public_id}', [OrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{order:public_id}/status', [AdminOrderActionController::class, 'updateStatus'])->name('admin.orders.status.update');
    Route::post('/orders/bulk', [BulkOrderActionController::class, 'handle'])->middleware('can:orders.manage')->name('admin.orders.bulk');
    Route::post('/orders/{order:public_id}/shipping', [AdminOrderActionController::class, 'updateShipping'])->name('admin.orders.shipping.update');
    Route::post('/orders/{order:public_id}/payments', [AdminOrderActionController::class, 'recordPayment'])->name('admin.orders.payments.record');
    Route::get('/orders/{order:public_id}/pdf/preview', [SalesOrderController::class, 'previewPdf'])->name('admin.orders.pdf.preview');
    Route::get('/orders/{order:public_id}/pdf/download', [SalesOrderController::class, 'downloadPdf'])->name('admin.orders.pdf.download');
    Route::get('/sales-orders/create', [SalesOrderController::class, 'create'])->name('admin.sales_orders.create');
    Route::post('/sales-orders', [SalesOrderController::class, 'store'])->name('admin.sales_orders.store');
    Route::put('/sales-orders/{order:public_id}', [SalesOrderController::class, 'update'])->name('admin.sales_orders.update');
    Route::get('/skus/search', [SalesOrderController::class, 'skuSearch'])->name('admin.skus.search');

    // B2.2.8 — Admin design-file access bridge (order-scoped, policy-gated)
    Route::get('/orders/{order:public_id}/files/{file:public_id}/preview', [AdminOrderDesignFileController::class, 'preview'])->name('admin.orders.files.preview')->withoutScopedBindings();
    Route::get('/orders/{order:public_id}/files/{file:public_id}/download', [AdminOrderDesignFileController::class, 'download'])->name('admin.orders.files.download')->withoutScopedBindings();

    // Finance payment & refund boundary routes
    Route::get('/payments', [PaymentController::class, 'index'])->name('admin.payments.index');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('admin.payments.show');
    Route::get('/refunds', [RefundController::class, 'index'])->name('admin.refunds.index');

    // Finance ledgers routes
    Route::get('/accounting/customer-ledger', [FinanceLedgerController::class, 'customerLedger'])->name('admin.accounting.customer_ledger');
    Route::get('/accounting/vendor-ledger', [FinanceLedgerController::class, 'vendorLedger'])->name('admin.accounting.vendor_ledger');
    Route::get('/accounting/business-ledger', [FinanceLedgerController::class, 'businessLedger'])->name('admin.accounting.business_ledger');
    Route::post('/refunds', [RefundController::class, 'store'])->name('admin.refunds.store');
    Route::post('/refunds/{refund}/approve', [RefundController::class, 'approve'])->name('admin.refunds.approve');
    Route::post('/refunds/{refund}/process', [RefundController::class, 'process'])->name('admin.refunds.process');
    Route::post('/refunds/{refund}/retry', [RefundController::class, 'retry'])->name('admin.refunds.retry');
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

    // Vendors admin routes
    Route::apiResource('/vendors', VendorController::class)->names([
        'index' => 'admin.vendors.index',
        'store' => 'admin.vendors.store',
        'show' => 'admin.vendors.show',
        'update' => 'admin.vendors.update',
        'destroy' => 'admin.vendors.destroy',
    ]);
    Route::get('/vendors/{vendor}/purchase-orders', [VendorController::class, 'purchaseOrders'])
        ->name('admin.vendors.purchase_orders.index');

    // Purchase Orders admin routes
    Route::apiResource('/purchase-orders', VendorOrderController::class)->parameters([
        'purchase-orders' => 'purchase_order',
    ])->names([
        'index' => 'admin.purchase_orders.index',
        'store' => 'admin.purchase_orders.store',
        'show' => 'admin.purchase_orders.show',
        'update' => 'admin.purchase_orders.update',
        'destroy' => 'admin.purchase_orders.destroy',
    ]);
    Route::post('/purchase-orders/{purchase_order}/status', [VendorOrderController::class, 'updateStatus'])
        ->name('admin.purchase_orders.status.update');
    Route::post('/purchase-orders/{purchase_order}/payments', [VendorPaymentController::class, 'store'])
        ->name('admin.purchase_orders.payments.store');
    Route::get('/vendor-payments', [VendorPaymentController::class, 'index'])
        ->name('admin.vendor_payments.index');

    // Purchase Order Items admin routes
    Route::apiResource('/purchase-orders.items', VendorOrderItemController::class)->parameters([
        'purchase-orders' => 'purchase_order',
        'items' => 'item',
    ])->only(['store', 'update', 'destroy'])->names([
        'store' => 'admin.purchase_orders.items.store',
        'update' => 'admin.purchase_orders.items.update',
        'destroy' => 'admin.purchase_orders.items.destroy',
    ]);
    Route::post('/purchase-orders/{purchase_order}/items/{item}/receive', [VendorOrderItemController::class, 'receive'])
        ->name('admin.purchase_orders.items.receive');

    // Audit logs admin routes
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit_logs.index');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('admin.audit_logs.show');

    // Notification logs admin routes
    Route::get('/notification-logs', [NotificationLogController::class, 'index'])->name('admin.notification_logs.index');
    Route::get('/notification-logs/{notification_log}', [NotificationLogController::class, 'show'])->name('admin.notification_logs.show');

    // Google Sheets admin routes
    Route::post('/google-sheets/test-connection', [GoogleSheetsConnectionController::class, 'testConnection'])->name('admin.google_sheets.test_connection');
    Route::get('/google-sheets/sync-logs', [GoogleSheetsSyncLogController::class, 'index'])->name('admin.google_sheets.sync_logs.index');
    Route::get('/google-sheets/sync-logs/{google_sheets_sync_log}', [GoogleSheetsSyncLogController::class, 'show'])->name('admin.google_sheets.sync_logs.show');
    Route::post('/google-sheets/sync-logs/{google_sheets_sync_log}/retry', [GoogleSheetsSyncLogController::class, 'retry'])->name('admin.google_sheets.sync_logs.retry');
    Route::post('/google-sheets/sync-logs/bulk-retry', [GoogleSheetsSyncLogController::class, 'bulkRetry'])->name('admin.google_sheets.sync_logs.bulk_retry');
    Route::post('/google-sheets/sync-logs/prune', [GoogleSheetsSyncLogController::class, 'prune'])->name('admin.google_sheets.sync_logs.prune');
    Route::post('/google-sheets/sync-record', [GoogleSheetsSyncLogController::class, 'syncRecord'])->name('admin.google_sheets.sync_record');

    // Settings admin routes
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('admin.settings.update');
});

// Web App Manifest Dynamic Route
Route::get('/manifest.webmanifest', function () {
    $b = config('branding');

    return response()->json([
        'name' => $b['name'],
        'short_name' => $b['short_name'],
        'description' => $b['seo']['description'],
        'theme_color' => $b['colors']['theme'],
        'background_color' => '#ffffff',
        'display' => 'standalone',
        'icons' => [[
            'src' => $b['logo']['icon'],
            'sizes' => 'any',
            'type' => 'image/svg+xml',
            'purpose' => 'any maskable',
        ]],
    ])->header('Content-Type', 'application/manifest+json');
})->name('manifest');

// Component Showcase Route
Route::view('/admin/components', 'component-showcase');
