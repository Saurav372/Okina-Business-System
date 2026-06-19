<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_can_have_products(): void
    {
        $category = ProductCategory::factory()->create();
        Product::factory()->count(2)->create([
            'primary_category_id' => $category->id,
        ]);

        $this->assertCount(2, $category->products);
    }

    public function test_products_can_have_variants_and_skus(): void
    {
        $product = Product::factory()->create();

        ProductVariant::factory()->count(2)->create([
            'product_id' => $product->id,
        ]);

        ProductSku::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $product->refresh();

        $this->assertCount(2, $product->variants);
        $this->assertCount(3, $product->skus);
        $this->assertTrue($product->skus->every(fn (ProductSku $sku) => $sku->product_id === $product->id));
    }

    public function test_skus_belong_to_their_product(): void
    {
        $sku = ProductSku::factory()->create();

        $this->assertInstanceOf(Product::class, $sku->product);
        $this->assertSame($sku->product_id, $sku->product->id);
    }

    public function test_public_visibility_rules_are_enforced(): void
    {
        $draftProduct = Product::factory()->draft()->create();
        $publicProduct = Product::factory()->create([
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'published_at' => now(),
        ]);

        $this->assertFalse($draftProduct->isPubliclyVisible());
        $this->assertTrue($publicProduct->isPubliclyVisible());
    }

    public function test_bulk_only_products_still_keep_shared_catalog_data(): void
    {
        $product = Product::factory()->bulkOnly()->create();

        $this->assertSame(Product::STATUS_BULK_ONLY, $product->status);
        $this->assertSame(Product::VISIBILITY_PUBLIC, $product->visibility);
        $this->assertFalse($product->isPubliclyVisible() && $product->direct_checkout_enabled);
    }
}
