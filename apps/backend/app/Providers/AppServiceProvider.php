<?php

namespace App\Providers;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoogleSheetsSyncLog;
use App\Models\InventoryMovement;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Models\VendorOrder;
use App\Observers\CustomerObserver;
use App\Observers\GoogleSheetsSyncObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductSkuObserver;
use App\Policies\AuditLogPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\GoogleSheetsSyncLogPolicy;
use App\Policies\NotificationLogPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\RefundPolicy;
use App\Support\Products\CustomizationOptionRules;
use App\Support\Products\PublicCatalogRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PublicCatalogContract::class, PublicCatalogRules::class);
        $this->app->singleton(CustomizationOptionContract::class, CustomizationOptionRules::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(NotificationLog::class, NotificationLogPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(GoogleSheetsSyncLog::class, GoogleSheetsSyncLogPolicy::class);

        ProductSku::observe(ProductSkuObserver::class);
        Customer::observe(CustomerObserver::class);
        Product::observe(ProductObserver::class);

        // Google Sheets Sync Observers
        Order::observe(GoogleSheetsSyncObserver::class);
        Payment::observe(GoogleSheetsSyncObserver::class);
        InventoryMovement::observe(GoogleSheetsSyncObserver::class);
        Customer::observe(GoogleSheetsSyncObserver::class);
        VendorOrder::observe(GoogleSheetsSyncObserver::class);

        // Inject navigation items automatically to the admin layout
        view()->composer('components.layouts.admin', function ($view) {
            $view->with('navigation', (new \App\Support\Navigation\Navigation)->forUser(auth()->user()));
        });
    }
}
