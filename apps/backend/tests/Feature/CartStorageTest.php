<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomerAccount;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSku;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class CartStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_is_persisted_and_reused_through_the_session(): void
    {
        $catalog = $this->createCatalog();

        $firstResponse = $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2, [
            'customer_note' => 'Keep centered',
        ]));

        $firstResponse->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.items.0.product.slug', 'custom-tee')
            ->assertJsonPath('data.items.0.product.name', 'Custom Tee')
            ->assertJsonPath('data.items.0.sku.code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.customization.product.slug', 'custom-tee')
            ->assertJsonPath('data.items.0.customization.placement.scale', 0.72)
            ->assertJsonMissingPath('data.items.0.product_id')
            ->assertJsonMissingPath('data.items.0.sku_id')
            ->assertJsonMissingPath('data.items.0.cart_id')
            ->assertJsonMissingPath('data.items.0.customer_id')
            ->assertJsonMissingPath('data.items.0.cart_token')
            ->assertJsonMissingPath('data.items.0.customization.files.0.storage_path')
            ->assertJsonMissingPath('data.items.0.customization.files.0.storage_disk');

        $cart = Cart::query()->firstOrFail();
        $item = CartItem::query()->firstOrFail();

        $this->assertSame(1, Cart::count());
        $this->assertSame(1, CartItem::count());
        $this->assertSame($cart->id, $item->cart_id);
        $this->assertNotEmpty($cart->cart_token);

        $secondResponse = $this->getJson('/api/cart');

        $secondResponse->assertOk()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.items.0.id', $item->public_id)
            ->assertJsonPath('data.items.0.customization.customer_note', 'Keep centered');
    }

    public function test_different_session_cannot_access_or_modify_another_guest_cart(): void
    {
        $catalog = $this->createCatalog();

        $addResponse = $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1));
        $addResponse->assertOk();

        $itemPublicId = $addResponse->json('data.items.0.id');

        $this->withSession(['cart_token' => Str::random(64)])
            ->patchJson('/api/cart/items/'.$itemPublicId, ['quantity' => 3])
            ->assertNotFound();

        $this->withSession(['cart_token' => Str::random(64)])
            ->deleteJson('/api/cart/items/'.$itemPublicId)
            ->assertNotFound();
    }

    public function test_customer_cart_ownership_uses_customer_identity_not_staff_users_table(): void
    {
        $catalog = $this->createCatalog();
        $customerAccount = CustomerAccount::factory()->create([
            'status' => CustomerAccount::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($customerAccount, 'customer')->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1));

        $response->assertOk();

        $cart = Cart::query()->firstOrFail();

        $this->assertSame($customerAccount->customer_id, $cart->customer_id);
        $this->assertTrue(Schema::hasColumn('carts', 'customer_id'));
        $this->assertFalse(Schema::hasColumn('carts', 'user_id'));
    }

    public function test_add_update_and_remove_operations_work(): void
    {
        $catalog = $this->createCatalog();

        $addResponse = $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 2));
        $addResponse->assertOk();

        $itemPublicId = $addResponse->json('data.items.0.id');

        $this->patchJson('/api/cart/items/'.$itemPublicId, ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('data.item_count', 4)
            ->assertJsonPath('data.items.0.quantity', 4);

        $this->deleteJson('/api/cart/items/'.$itemPublicId)
            ->assertOk()
            ->assertJsonPath('data.item_count', 0)
            ->assertJsonPath('data.items', []);
    }

    public function test_identical_normalized_selections_merge_and_distinct_customization_creates_separate_items(): void
    {
        $catalog = $this->createCatalog();

        $snapshotOne = $this->customizationSnapshot($catalog['product'], $catalog['sku'], [
            'placement' => [
                'x' => 42,
                'y' => 58,
                'scale' => 0.72,
                'rotation' => 0,
            ],
        ]);

        $snapshotTwo = $this->customizationSnapshot($catalog['product'], $catalog['sku'], [
            'placement' => [
                'x' => '42.0',
                'y' => '58.0',
                'scale' => '0.72',
                'rotation' => '0',
            ],
            'selected_options_snapshot' => [
                [
                    'option_code' => 'size',
                    'value_code' => 'm',
                    'value_label' => 'Medium',
                ],
            ],
        ]);

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1, $snapshotOne))
            ->assertOk();

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 3, $snapshotTwo))
            ->assertOk()
            ->assertJsonPath('data.item_count', 4)
            ->assertJsonCount(1, 'data.items');

        $snapshotThree = $this->customizationSnapshot($catalog['product'], $catalog['sku'], [
            'customer_note' => 'Slightly higher on chest',
        ]);

        $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1, $snapshotThree))
            ->assertOk()
            ->assertJsonCount(2, 'data.items');
    }

    public function test_snapshot_retention_and_public_safe_payload(): void
    {
        $catalog = $this->createCatalog();

        $response = $this->postJson('/api/cart/items', $this->cartPayload($catalog['product'], $catalog['sku'], 1, [
            'placement' => [
                'x' => -200,
                'y' => 240,
                'scale' => 9,
                'rotation' => -90,
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
                    'x' => 7,
                    'y' => 8,
                    'scale' => 0.3,
                    'rotation' => 90,
                ],
                'storage_path' => 'files/should-not-leak.svg',
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.items.0.product.slug', 'custom-tee')
            ->assertJsonPath('data.items.0.product.name', 'Custom Tee')
            ->assertJsonPath('data.items.0.sku.code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.items.0.customization.placement.x', 0)
            ->assertJsonPath('data.items.0.customization.placement.y', 100)
            ->assertJsonPath('data.items.0.customization.placement.scale', 1.5)
            ->assertJsonPath('data.items.0.customization.placement.rotation', -45)
            ->assertJsonPath('data.items.0.customization.files.0.public_id', 'FIL-ORIGINAL-123')
            ->assertJsonPath('data.items.0.customization.mockup_preview.source_file_public_id', 'FIL-ORIGINAL-123')
            ->assertJsonMissingPath('data.items.0.customization.files.0.storage_path')
            ->assertJsonMissingPath('data.items.0.customization.files.0.storage_disk')
            ->assertJsonMissingPath('data.items.0.customization.files.0.preview.path')
            ->assertJsonMissingPath('data.items.0.customization.mockup_preview.storage_path')
            ->assertJsonMissingPath('data.items.0.product_id')
            ->assertJsonMissingPath('data.items.0.sku_id')
            ->assertJsonMissingPath('data.items.0.cart_id')
            ->assertJsonMissingPath('data.items.0.customer_id')
            ->assertJsonMissingPath('data.items.0.cart_token');

        $item = CartItem::query()->firstOrFail();

        Product::query()->whereKey($catalog['product']->id)->update([
            'name' => 'Renamed Tee',
            'slug' => 'renamed-tee',
        ]);

        ProductSku::query()->whereKey($catalog['sku']->id)->update([
            'sku_code' => 'SKU-RENAMED-TEE-M',
        ]);

        $refreshed = $this->getJson('/api/cart');

        $refreshed->assertOk()
            ->assertJsonPath('data.items.0.id', $item->public_id)
            ->assertJsonPath('data.items.0.product.slug', 'custom-tee')
            ->assertJsonPath('data.items.0.product.name', 'Custom Tee')
            ->assertJsonPath('data.items.0.sku.code', 'SKU-CUSTOM-TEE-M')
            ->assertJsonPath('data.items.0.customization.mockup_preview.role', 'mockup_preview');

        $this->assertSame('custom-tee', $item->fresh()->customization_snapshot['product']['slug']);
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
            'published_at' => now(),
        ]);

        ProductVariant::factory()->create([
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
