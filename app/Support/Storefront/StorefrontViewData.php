<?php

namespace App\Support\Storefront;

use App\Contracts\PublicCatalogContract;
use App\Services\CartResponsePresenter;
use App\Services\CartService;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class StorefrontViewData
{
    public function __construct(
        private readonly PublicCatalogContract $catalog,
        private readonly SettingsService $settings,
        private readonly CartService $carts,
        private readonly CartResponsePresenter $cartPresenter,
        private readonly MoneyFormatter $money,
    ) {}

    /** @return array<string, mixed> */
    public function base(Request $request): array
    {
        $products = $this->products();
        $categories = $this->categories($products);
        $cart = $this->cartPresenter->payload($this->carts->current($request, false));

        return [
            'site' => [
                'company_name' => (string) $this->settings->get('business', 'company_name', 'Okina Craft'),
                'support_email' => $this->settings->get('business', 'support_email'),
                'support_phone' => $this->settings->get('business', 'support_phone'),
                'currency' => (string) $this->settings->get('business', 'default_currency', 'INR'),
                'tax_inclusive' => (bool) $this->settings->get('business', 'tax_inclusive_pricing', false),
                'site_title' => (string) $this->settings->get('seo', 'site_title', 'Okina Craft'),
                'meta_description' => $this->settings->get('seo', 'meta_description')
                    ?: 'Custom apparel for teams, events, businesses, and creators. Proof approved before production.',
                'robots' => ((bool) $this->settings->get('seo', 'robots_index', true) ? 'index' : 'noindex')
                    .', '.((bool) $this->settings->get('seo', 'robots_follow', true) ? 'follow' : 'nofollow'),
            ],
            'navigationCategories' => array_slice($categories, 0, 5),
            'cartCount' => (int) ($cart['item_count'] ?? 0),
            'cart' => $cart,
            'money' => $this->money,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function products(): array
    {
        return array_map(function (array $product): array {
            $skus = collect($product['skus'] ?? []);
            $availableSkus = $skus->filter(
                fn (array $sku): bool => (bool) data_get($sku, 'availability.available_for_checkout', false),
            );
            $pricedSkus = ($availableSkus->isNotEmpty() ? $availableSkus : $skus)
                ->filter(fn (array $sku): bool => is_int($sku['price_minor'] ?? null));
            $priceMinor = $pricedSkus->min('price_minor');
            $priceMinor = is_int($priceMinor) ? $priceMinor : ($product['base_price_minor'] ?? null);

            return [
                ...$product,
                'display_price' => $this->money->format(
                    is_int($priceMinor) ? $priceMinor : null,
                    (string) ($product['currency'] ?? 'INR'),
                ),
                'price_minor' => is_int($priceMinor) ? $priceMinor : PHP_INT_MAX,
                'url' => route('storefront.products.show', ['product' => $product['slug']]),
            ];
        }, $this->catalog->products());
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $products
     * @return array<int, array<string, mixed>>
     */
    public function categories(?array $products = null): array
    {
        $products ??= $this->products();

        return array_map(function (array $category) use ($products): array {
            $categoryProducts = array_values(array_filter(
                $products,
                fn (array $product): bool => data_get($product, 'category.slug') === ($category['slug'] ?? null),
            ));

            return [
                ...$category,
                'cover_image' => data_get($categoryProducts, '0.cover_image'),
                'preview_product' => $categoryProducts[0] ?? null,
                'url' => route('storefront.categories.show', ['category' => $category['slug']]),
            ];
        }, $this->catalog->categories());
    }
}
