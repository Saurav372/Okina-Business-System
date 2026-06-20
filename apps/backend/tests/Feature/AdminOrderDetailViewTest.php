<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderDetailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_order_detail_and_see_preview_thumbnail(): void
    {
        // Create permission and admin role
        Permission::query()->updateOrCreate([
            'slug' => 'orders.view',
        ], [
            'name' => 'Orders View',
            'group' => 'orders',
            'guard_name' => 'web',
            'description' => 'Allow viewing orders',
            'is_sensitive' => false,
        ]);

        $role = Role::query()->updateOrCreate([
            'slug' => 'admin',
        ], [
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $role->permissions()->sync(Permission::query()->where('slug', 'orders.view')->pluck('id')->all());

        $user = User::factory()->create();
        $user->assignRole($role);

        // Create product + sku
        $product = Product::factory()->create(['slug' => 'snapshot-product-one', 'name' => 'Snapshot Product One', 'visibility' => Product::VISIBILITY_PUBLIC, 'status' => Product::STATUS_ACTIVE]);
        $sku = ProductSku::factory()->create(['product_id' => $product->id, 'sku_code' => 'SKU-ONE', 'price_minor' => 1000]);

        $customer = Customer::factory()->create();

        $order = Order::query()->create([
            'public_id' => 'OD-VIEW-001',
            'order_type' => 'website_order',
            'order_source' => 'website',
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal_amount_minor' => 1000,
            'total_amount_minor' => 1000,
            'customer_id' => $customer->id,
            'customer_snapshot' => [
                'public_id' => 'CUS-SNAPSHOT-TEST',
                'name' => 'Snapshot Customer',
                'email' => 'snapshot@example.test',
                'phone' => '9000000000',
                'customer_type' => 'individual',
            ],
        ]);

        $snapshot = [
            'schema_version' => 1,
            'product' => ['slug' => 'snapshot-product-one', 'name' => 'Snapshot Product One'],
            'sku_code' => 'SKU-ONE',
            'selected_options_snapshot' => [],
            'print_method' => 'screen',
            'print_position' => 'center',
            'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
            'files' => [[
                'public_id' => 'SF-TEST-001',
                'role' => 'original_upload',
                'file_kind' => 'original_upload',
                'visibility' => 'private',
                'status' => 'active',
                'original_filename' => 'design.png',
                'mime_type' => 'image/png',
                'size_bytes' => 1234,
                'has_preview' => true,
            ]],
            'mockup_preview' => [
                'role' => 'mockup_preview',
                'render_type' => 'signed_svg_mockup',
                'source_file_public_id' => 'SF-TEST-001',
                'route_name' => 'catalog.products.mockup-preview',
                'expires_in_minutes' => 15,
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
            ],
        ];

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'product_name_snapshot' => 'Snapshot Product One',
            'product_slug_snapshot' => 'snapshot-product-one',
            'sku_code_snapshot' => 'SKU-ONE',
            'customization_fingerprint' => 'FINGERPRINT-DESIGN',
            'customization_snapshot' => $snapshot,
            'unit_price_minor' => 1000,
            'line_subtotal_minor' => 1000,
            'line_total_minor' => 1000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
        ]);

        $this->actingAs($user)
            ->get(route('admin.orders.detail', ['order' => $order->public_id]))
            ->assertStatus(200)
            ->assertSee('design-preview/SF-TEST-001');
    }
}
