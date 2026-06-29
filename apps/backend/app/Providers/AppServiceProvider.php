<?php

namespace App\Providers;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\Quotation;
use App\Models\Refund;
use App\Observers\ProductSkuObserver;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\RefundPolicy;
use App\Support\Products\CustomizationOptionRules;
use App\Support\Products\PublicCatalogRules;
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
            \DB::statement('PRAGMA foreign_keys = ON;');
        }
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);

        ProductSku::observe(ProductSkuObserver::class);
    }
}
