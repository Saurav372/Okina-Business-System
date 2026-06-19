<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_validation_passes_for_current_public_cart_items_and_payloads_remain_public_safe(): void
    {
        $catalog = $this->createCatalog();

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2, [
            'customer_note' => 'Keep centered',
        ]))->assertOk();

        $response = $this->getJson('/api/cart/validation');

        $response->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonCount(0, 'data.errors')
            ->assertJsonPath('data.items.0.id', CartItem::query()->firstOrFail()->public_id)
            ->assertJsonPath('data.items.0.valid', true)
            ->assertJsonPath('data.items.0.errors', [])
            ->assertJsonPath('data.cart.item_count', 2)
            ->assertJsonPath('data.cart.items.0.product.slug', 'custom-tee')
            ->assertJsonPath('data.cart.items.0.sku.code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.cart.items.0.customization.files.0.public_id', 'FIL-ORIGINAL-123')
            ->assertJsonPath('data.cart.items.0.pricing.unit_price_minor', 1899)
            ->assertJsonPath('data.cart.items.0.pricing.line_total_minor', 3798)
            ->assertJsonPath('data.cart.pricing.subtotal_amount_minor', 3798)
            ->assertJsonPath('data.cart.pricing.total_amount_minor', 3798)
            ->assertJsonMissingPath('data.cart.items.0.product_id')
            ->assertJsonMissingPath('data.cart.items.0.sku_id')
            ->assertJsonMissingPath('data.cart.items.0.cart_id')
            ->assertJsonMissingPath('data.cart.items.0.customer_id')
            ->assertJsonMissingPath('data.cart.items.0.cart_token')
            ->assertJsonMissingPath('data.cart.items.0.customization.files.0.storage_path')
            ->assertJsonMissingPath('data.cart.items.0.customization.files.0.preview.path')
            ->assertJsonMissingPath('data.cart.items.0.customization.mockup_preview.storage_path');
    }

    public function test_cart_validation_fails_when_product_is_no_longer_publicly_purchasable(): void
    {
        $catalog = $this->createCatalog();

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))->assertOk();

        Product::query()->whereKey($catalog['product']->id)->update([
            'status' => Product::STATUS_DISCONTINUED,
        ]);

        $response = $this->getJson('/api/cart/validation');

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.items.0.valid', false)
            ->assertJsonPath('data.items.0.errors.0', 'product_unavailable')
            ->assertJsonPath('data.errors.0.item_id', CartItem::query()->firstOrFail()->public_id);
    }

    public function test_cart_validation_fails_when_sku_is_disabled_for_checkout(): void
    {
        $catalog = $this->createCatalog();

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))->assertOk();

        ProductSku::query()->whereKey($catalog['sku']->id)->update([
            'direct_checkout_enabled' => false,
        ]);

        $response = $this->getJson('/api/cart/validation');

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.items.0.valid', false)
            ->assertJsonPath('data.items.0.errors.0', 'sku_unavailable');
    }

    public function test_cart_validation_fails_when_stored_customization_no_longer_matches_current_rules(): void
    {
        $catalog = $this->createCatalog();

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1))->assertOk();

        ProductVariant::query()->whereKey($catalog['size_variant']->id)->update([
            'values' => [
                ['code' => 's', 'label' => 'Small', 'sort_order' => 10, 'is_active' => true],
            ],
        ]);

        $response = $this->getJson('/api/cart/validation');

        $response->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.items.0.valid', false)
            ->assertJsonPath('data.items.0.errors.0', 'invalid_option_value:size');
    }

    private function createCatalog(): array
    {
        $category = ProductCategory::factory()->create([
            'slug' => 'custom-apparel',
        ]);

        $product = Product::factory()->create([
            'slug' => 'custom-tee',
            'name' => 'Custom Tee',
            'primary_category_id' => $category->id,
            'customization_mode' => Product::CUSTOMIZATION_REQUIRED,
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
            'direct_checkout_enabled' => true,
            'published_at' => now(),
        ]);

        $sizeVariant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'name' => 'Size',
            'code' => 'size',
            'values' => [
                ['code' => 'm', 'label' => 'Medium', 'sort_order' => 10, 'is_active' => true],
            ],
            'is_required' => true,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-CUSTOM-TEE-M',
            'variant_key' => 'size:m',
            'option_values' => [
                ['code' => 'm', 'label' => 'Medium'],
            ],
            'status' => 'active',
            'direct_checkout_enabled' => true,
            'quote_required' => false,
            'price_minor' => 1899,
        ]);

        return [
            'category' => $category,
            'product' => $product->refresh(),
            'size_variant' => $sizeVariant->refresh(),
            'sku' => $sku->refresh(),
        ];
    }

    private function cartPayload(Product $product, ProductSku $sku, int $quantity, array $overrides = []): array
    {
        return [
            'product_slug' => $product->slug,
            'sku_code' => $sku->sku_code,
            'quantity' => $quantity,
            'customization_snapshot' => $this->customizationSnapshot($product, $sku, $overrides),
        ];
    }

    private function customizationSnapshot(Product $product, ProductSku $sku, array $overrides = []): array
    {
        $base = [
            'schema_version' => 1,
            'product' => [
                'slug' => $product->slug,
                'name' => $product->name,
            ],
            'sku_code' => $sku->sku_code,
            'variant_key' => $sku->variant_key,
            'selected_options_snapshot' => [
                [
                    'option_code' => 'size',
                    'value_code' => 'm',
                    'value_label' => 'Medium',
                ],
            ],
            'print_method' => 'dtf',
            'print_position' => 'front',
            'placement' => [
                'x' => 42,
                'y' => 58,
                'scale' => 0.72,
                'rotation' => 0,
            ],
            'files' => [
                [
                    'public_id' => 'FIL-ORIGINAL-123',
                    'role' => 'original_upload',
                    'file_kind' => 'original_upload',
                    'visibility' => 'private',
                    'status' => 'active',
                    'original_filename' => 'logo-design.png',
                    'mime_type' => 'image/png',
                    'size_bytes' => 2048,
                    'storage_disk' => 'private',
                    'storage_path' => 'files/2026/06/logo-design.png',
                    'has_preview' => true,
                    'preview' => [
                        'mime_type' => 'image/png',
                        'size_bytes' => 1024,
                        'width' => 1200,
                        'height' => 1200,
                        'storage_disk' => 'private',
                        'path' => 'files/2026/06/logo-preview.png',
                    ],
                ],
            ],
            'mockup_preview' => [
                'role' => 'mockup_preview',
                'render_type' => 'signed_svg_mockup',
                'source_file_public_id' => 'FIL-ORIGINAL-123',
                'route_name' => 'catalog.products.mockup-preview',
                'expires_in_minutes' => 15,
                'placement' => [
                    'x' => 42,
                    'y' => 58,
                    'scale' => 0.72,
                    'rotation' => 0,
                ],
                'storage_path' => 'files/should-not-leak.svg',
            ],
            'customer_note' => 'Keep centered',
        ];

        return array_replace_recursive($base, $overrides);
    }
}
