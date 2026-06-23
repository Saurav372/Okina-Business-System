<?php

namespace App\Providers;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quotation;
use App\Models\StoredFile;
use App\Policies\LeadPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductCategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\StoredFilePolicy;
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
        $this->app->bind(PublicCatalogContract::class, PublicCatalogRules::class);
        $this->app->bind(CustomizationOptionContract::class, CustomizationOptionRules::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(StoredFile::class, StoredFilePolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
    }
}
