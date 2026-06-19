<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use App\Support\Products\PublicCatalogRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
