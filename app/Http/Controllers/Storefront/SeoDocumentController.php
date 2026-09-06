<?php

namespace App\Http\Controllers\Storefront;

use App\Contracts\PublicCatalogContract;
use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Response;

class SeoDocumentController extends Controller
{
    public function __construct(
        private readonly PublicCatalogContract $catalog,
        private readonly SettingsService $settings,
    ) {}

    public function robots(): Response
    {
        if (! (bool) $this->settings->get('seo', 'robots_index', true)) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /account',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /order-confirmation',
            'Disallow: /track-order',
            'Sitemap: '.route('storefront.sitemap'),
            '',
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function sitemap(): Response
    {
        $paths = [
            route('storefront.home'),
            route('storefront.categories.index'),
            route('storefront.how-it-works'),
            route('storefront.policy', ['slug' => 'shipping']),
            route('storefront.policy', ['slug' => 'returns']),
            route('storefront.policy', ['slug' => 'privacy']),
            route('storefront.policy', ['slug' => 'terms']),
        ];

        foreach ($this->catalog->categories() as $category) {
            $paths[] = route('storefront.categories.show', ['category' => $category['slug']]);
        }
        foreach ($this->catalog->products() as $product) {
            $paths[] = route('storefront.products.show', ['product' => $product['slug']]);
        }

        $urls = collect($paths)->unique()->map(
            fn (string $url): string => '  <url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>',
        )->implode("\n");
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n{$urls}\n</urlset>\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=900',
        ]);
    }
}
