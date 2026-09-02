<?php

namespace App\Http\Controllers\Storefront;

use App\Contracts\CustomizationOptionContract;
use App\Contracts\PublicCatalogContract;
use App\Http\Controllers\Controller;
use App\Services\CartResponsePresenter;
use App\Services\CartService;
use App\Services\SettingsService;
use App\Support\Storefront\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CatalogController extends Controller
{
    public function __construct(
        private readonly PublicCatalogContract $catalog,
        private readonly CustomizationOptionContract $customization,
        private readonly SettingsService $settings,
        private readonly CartService $carts,
        private readonly CartResponsePresenter $cartPresenter,
        private readonly MoneyFormatter $money,
    ) {}

    public function home(Request $request): View
    {
        $products = $this->presentProducts($this->catalog->products());
        $categories = $this->presentCategories($this->catalog->categories(), $products);
        $storefront = $this->storefrontData($request, $categories);

        return view('storefront.home', [
            ...$storefront,
            'featuredCategories' => array_slice($categories, 0, 5),
            'featuredProducts' => array_slice($products, 0, 8),
            'heroProduct' => $products[0] ?? null,
            'structuredData' => $this->homeStructuredData($storefront['site']),
        ]);
    }

    public function categories(Request $request): View
    {
        $products = $this->presentProducts($this->catalog->products());
        $categories = $this->presentCategories($this->catalog->categories(), $products);

        return view('storefront.categories.index', [
            ...$this->storefrontData($request, $categories),
            'categories' => $categories,
        ]);
    }

    public function category(Request $request, string $category): View
    {
        $currentCategory = $this->catalog->category($category);

        abort_if($currentCategory === null, 404);

        $products = $this->presentProducts($this->catalog->categoryProducts($category));
        $allProducts = $this->presentProducts($this->catalog->products());
        $categories = $this->presentCategories($this->catalog->categories(), $allProducts);
        $currentCategory = $this->presentCategories([$currentCategory], $products)[0];

        return view('storefront.categories.show', [
            ...$this->storefrontData($request, $categories),
            'category' => $currentCategory,
            'products' => $products,
            'structuredData' => $this->categoryStructuredData($currentCategory, $products, $request),
        ]);
    }

    public function search(Request $request): View
    {
        $query = Str::of($request->string('q')->toString())->squish()->limit(80, '')->toString();
        $allProducts = $this->presentProducts($this->catalog->products());
        $categories = $this->presentCategories($this->catalog->categories(), $allProducts);
        $needle = Str::lower($query);
        $products = $query === '' ? $allProducts : array_values(array_filter(
            $allProducts,
            fn (array $product): bool => Str::contains(Str::lower(implode(' ', [
                $product['name'] ?? '',
                $product['short_description'] ?? '',
                data_get($product, 'category.name', ''),
            ])), $needle),
        ));

        return view('storefront.search', [
            ...$this->storefrontData($request, $categories),
            'query' => $query,
            'products' => $products,
        ]);
    }

    public function product(Request $request, string $product): View
    {
        $catalogProduct = $this->catalog->product($product);
        $customization = $this->customization->product($product);

        abort_if($catalogProduct === null || $customization === null, 404);

        $presentedProduct = $this->presentProducts([$catalogProduct])[0];
        $allProducts = $this->presentProducts($this->catalog->products());
        $categories = $this->presentCategories($this->catalog->categories(), $allProducts);
        $currency = (string) ($catalogProduct['currency'] ?? 'INR');
        $catalogSkus = collect($catalogProduct['skus'] ?? [])->keyBy('sku_code');
        $customization['skus'] = array_map(function (array $sku) use ($catalogSkus, $currency): array {
            $catalogSku = $catalogSkus->get($sku['sku_code'] ?? '');

            return [
                ...$sku,
                'availability' => is_array($catalogSku) ? ($catalogSku['availability'] ?? $sku['availability']) : $sku['availability'],
                'display_price' => $this->money->format(
                    is_int($sku['price_minor'] ?? null) ? $sku['price_minor'] : null,
                    $currency,
                ),
            ];
        }, $customization['skus'] ?? []);

        return view('storefront.products.show', [
            ...$this->storefrontData($request, $categories),
            'product' => $presentedProduct,
            'customization' => $customization,
            'structuredData' => $this->productStructuredData($presentedProduct, $request),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function presentProducts(array $products): array
    {
        return array_map(function (array $product): array {
            $skus = collect($product['skus'] ?? []);
            $availableSkus = $skus->filter(fn (array $sku): bool => (bool) data_get($sku, 'availability.available_for_checkout', false));
            $pricedSkus = ($availableSkus->isNotEmpty() ? $availableSkus : $skus)
                ->filter(fn (array $sku): bool => is_int($sku['price_minor'] ?? null));
            $priceMinor = $pricedSkus->min('price_minor');
            $priceMinor = is_int($priceMinor) ? $priceMinor : ($product['base_price_minor'] ?? null);
            $compareAtMinor = $pricedSkus
                ->pluck('compare_at_price_minor')
                ->filter(fn (mixed $price): bool => is_int($price) && $price > (int) $priceMinor)
                ->min();

            return [
                ...$product,
                'display_price' => $this->money->format(is_int($priceMinor) ? $priceMinor : null, (string) ($product['currency'] ?? 'INR')),
                'display_compare_at_price' => is_int($compareAtMinor) ? $this->money->format($compareAtMinor, (string) ($product['currency'] ?? 'INR')) : null,
                'price_minor' => is_int($priceMinor) ? $priceMinor : PHP_INT_MAX,
                'url' => route('storefront.products.show', ['product' => $product['slug']]),
                'badge' => $this->productBadge($product, $availableSkus->isNotEmpty()),
            ];
        }, $products);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    private function presentCategories(array $categories, array $products): array
    {
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
        }, $categories);
    }

    /**
     * @param  array<int, array<string, mixed>>  $categories
     * @return array<string, mixed>
     */
    private function storefrontData(Request $request, array $categories): array
    {
        $cart = $this->cartPresenter->payload($this->carts->current($request, false));
        $site = [
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
            'frontend_url' => rtrim((string) config('app.frontend_url', 'http://127.0.0.1:4321'), '/'),
        ];

        return [
            'site' => $site,
            'navigationCategories' => array_slice($categories, 0, 5),
            'cartCount' => (int) ($cart['item_count'] ?? 0),
        ];
    }

    private function productBadge(array $product, bool $hasAvailableSku): string
    {
        if (($product['status'] ?? null) === 'bulk_only') {
            return 'Bulk quote';
        }

        if (! $hasAvailableSku && ! empty($product['skus'])) {
            return 'Enquire';
        }

        return ($product['customization_mode'] ?? 'none') === 'none' ? 'Ready to wear' : 'Custom-ready';
    }

    /** @return array<int, array<string, mixed>> */
    private function homeStructuredData(array $site): array
    {
        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $site['company_name'],
                'url' => route('storefront.home'),
                'description' => $site['meta_description'],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => $site['site_title'],
                'url' => route('storefront.home'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('storefront.search').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function categoryStructuredData(array $category, array $products, Request $request): array
    {
        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'CollectionPage',
                'name' => $category['name'],
                'description' => $category['seo_description'] ?? $category['description'],
                'url' => $request->url(),
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => count($products),
                    'itemListElement' => array_map(fn (array $product, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $product['name'],
                        'url' => $product['url'],
                    ], $products, array_keys($products)),
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('storefront.home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Collections', 'item' => route('storefront.categories.index')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $category['name'], 'item' => $request->url()],
                ],
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function productStructuredData(array $product, Request $request): array
    {
        $availability = collect($product['skus'] ?? [])->contains(
            fn (array $sku): bool => (bool) data_get($sku, 'availability.available_for_checkout', false),
        ) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

        return [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product['name'],
                'description' => $product['seo_description'] ?: $product['short_description'],
                'image' => data_get($product, 'cover_image.url'),
                'sku' => data_get($product, 'skus.0.sku_code'),
                'offers' => [
                    '@type' => 'AggregateOffer',
                    'priceCurrency' => $product['currency'],
                    'lowPrice' => number_format(((int) $product['price_minor']) / 100, 2, '.', ''),
                    'offerCount' => count($product['skus'] ?? []),
                    'availability' => $availability,
                    'url' => $request->url(),
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('storefront.home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => data_get($product, 'category.name', 'Shop'), 'item' => route('storefront.categories.show', ['category' => data_get($product, 'category.slug')])],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $product['name'], 'item' => $request->url()],
                ],
            ],
        ];
    }
}
