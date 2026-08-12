<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductMedia;
use App\Models\ProductSeo;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Models\StoredFile;
use App\Services\SettingsService;
use App\Support\Products\PublicCatalogRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_api_returns_only_public_categories_and_products(): void
    {
        $publicCategory = ProductCategory::factory()->create([
            'name' => 'Public Apparel',
            'slug' => 'public-apparel',
        ]);
        ProductCategory::factory()->create([
            'name' => 'Draft Collection',
            'slug' => 'draft-collection',
            'status' => 'draft',
            'published_at' => now()->addDay(),
        ]);

        $publicProduct = Product::factory()->create([
            'name' => 'Public T-Shirt',
            'slug' => 'public-t-shirt',
            'primary_category_id' => $publicCategory->id,
        ]);
        ProductVariant::factory()->create([
            'product_id' => $publicProduct->id,
            'name' => 'Size',
            'code' => 'size',
        ]);
        ProductSku::factory()->create([
            'product_id' => $publicProduct->id,
            'sku_code' => 'SKU-PUBLIC',
            'variant_key' => 'size-m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'track_stock' => true,
            'stock_quantity' => 7,
            'low_stock_threshold' => 3,
            'price_minor' => 1499,
        ]);

        Product::factory()->draft()->create([
            'name' => 'Private Hoodie',
            'slug' => 'private-hoodie',
            'primary_category_id' => $publicCategory->id,
        ]);

        $this->getJson('/api/catalog/categories')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'public-apparel')
            ->assertJsonPath('data.0.products_count', 1)
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.deleted_at');

        $this->getJson('/api/catalog/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'public-t-shirt')
            ->assertJsonPath('data.0.category.slug', 'public-apparel')
            ->assertJsonPath('data.0.skus.0.sku_code', 'SKU-PUBLIC')
            ->assertJsonPath('data.0.skus.0.availability.available_for_checkout', true)
            ->assertJsonMissingPath('data.0.id')
            ->assertJsonMissingPath('data.0.skus.0.stock_quantity')
            ->assertJsonMissingPath('data.0.skus.0.deleted_at');

        $this->getJson('/api/catalog/categories/public-apparel/products')
            ->assertOk()
            ->assertJsonPath('data.category.slug', 'public-apparel')
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.slug', 'public-t-shirt');

        $this->getJson('/api/catalog/products/public-t-shirt')
            ->assertOk()
            ->assertJsonPath('data.slug', 'public-t-shirt')
            ->assertJsonPath('data.category.slug', 'public-apparel')
            ->assertJsonMissingPath('data.id')
            ->assertJsonMissingPath('data.skus.0.stock_quantity');

        $this->getJson('/api/catalog/products/private-hoodie')
            ->assertNotFound();

        $this->getJson('/api/catalog/categories/draft-collection/products')
            ->assertNotFound();
    }

    public function test_public_catalog_guidance_matches_astro_usage(): void
    {
        $rules = app(PublicCatalogRules::class);
        $guidance = $rules->guidance();

        $this->assertSame('/api/catalog/categories', $guidance['categories_endpoint']);
        $this->assertSame('/api/catalog/products', $guidance['products_endpoint']);
        $this->assertContains('Use category responses for listing and navigation.', $guidance['astro_usage']);
        $this->assertContains('Do not depend on raw stock counts or internal database ids.', $guidance['astro_usage']);
        $this->assertContains('sku_code', $guidance['public_sku_fields']);
        $this->assertContains('variants', $guidance['public_product_fields']);
    }

    public function test_dashboard_media_and_seo_are_exposed_to_the_public_storefront_contract(): void
    {
        Storage::fake('private');

        $product = Product::factory()->create([
            'name' => 'Dashboard Connected Tee',
            'slug' => 'dashboard-connected-tee',
        ]);
        $file = StoredFile::factory()->create([
            'storage_disk' => 'private',
            'storage_path' => 'files/dashboard-connected/original.png',
            'mime_type' => 'image/png',
            'visibility' => StoredFile::VISIBILITY_PUBLIC_SAFE_PREVIEW,
            'status' => StoredFile::STATUS_ACTIVE,
            'metadata' => [
                'preview' => [
                    'path' => 'files/dashboard-connected/preview.png',
                    'storage_disk' => 'private',
                    'mime_type' => 'image/png',
                    'width' => 900,
                    'height' => 1100,
                ],
            ],
        ]);
        Storage::disk('private')->put('files/dashboard-connected/preview.png', 'public-preview');

        ProductMedia::query()->create([
            'product_id' => $product->id,
            'file_id' => $file->id,
            'role' => ProductMedia::ROLE_COVER,
            'alt_text' => 'Front view of the dashboard connected tee',
            'sort_order' => 0,
        ]);
        ProductSeo::query()->create([
            'product_id' => $product->id,
            'meta_title' => 'Dashboard SEO title',
            'meta_description' => 'Metadata controlled from the product dashboard.',
            'canonical_url' => 'https://okinacraft.com/products/dashboard-connected-tee',
            'robots_index' => false,
            'robots_follow' => true,
            'og_title' => 'Dashboard social title',
            'og_image_id' => $file->id,
            'twitter_title' => 'Dashboard Twitter title',
            'twitter_image_id' => $file->id,
        ]);

        $response = $this->getJson('/api/catalog/products/dashboard-connected-tee')
            ->assertOk()
            ->assertJsonPath('data.seo.title', 'Dashboard SEO title')
            ->assertJsonPath('data.seo.canonical_url', 'https://okinacraft.com/products/dashboard-connected-tee')
            ->assertJsonPath('data.seo.robots.index', false)
            ->assertJsonPath('data.seo.robots.follow', true)
            ->assertJsonPath('data.seo.open_graph.title', 'Dashboard social title')
            ->assertJsonPath('data.seo.twitter.title', 'Dashboard Twitter title')
            ->assertJsonPath('data.cover_image.public_id', $file->public_id)
            ->assertJsonPath('data.cover_image.alt_text', 'Front view of the dashboard connected tee')
            ->assertJsonPath('data.media.0.width', 900);

        $mediaUrl = $response->json('data.cover_image.url');
        $this->get($mediaUrl)
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertStreamedContent('public-preview');
    }

    public function test_non_public_product_files_cannot_be_read_through_the_catalog_media_route(): void
    {
        $file = StoredFile::factory()->create([
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
            'mime_type' => 'image/png',
        ]);

        $this->get(route('catalog.media.preview', ['file' => $file->public_id]))->assertNotFound();
    }

    public function test_storefront_configuration_uses_dashboard_business_and_commerce_settings(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('business', 'company_name', 'Okina Studio');
        $settings->set('business', 'support_email', 'help@example.test');
        $settings->set('business', 'tax_inclusive_pricing', true);
        $settings->set('payment', 'online_payments_enabled', false);
        $settings->set('seo', 'robots_index', false);

        $this->getJson('/api/catalog/storefront')
            ->assertOk()
            ->assertJsonPath('data.business.company_name', 'Okina Studio')
            ->assertJsonPath('data.business.support_email', 'help@example.test')
            ->assertJsonPath('data.business.tax_inclusive_pricing', true)
            ->assertJsonPath('data.checkout.online_payments_enabled', false)
            ->assertJsonPath('data.seo.robots.index', false)
            ->assertJsonMissingPath('data.payments');
    }
}
