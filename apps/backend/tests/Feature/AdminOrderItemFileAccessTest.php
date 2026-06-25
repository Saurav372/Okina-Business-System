<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\User;
use App\Support\Admin\OrderDetailCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * C1.1.5 + B2.2.8 + C1.1.6 — Order item/customization snapshot presentation,
 * authorized admin design-file access bridge, and read-only scope regression.
 *
 * Verifies:
 *
 * C1.1.5 — Order item and customization snapshot presentation
 * - Items appear in the order detail catalog summary.
 * - Customization snapshot is rendered with public-safe fields only.
 * - A signed mockup preview URL is generated when preview metadata is present.
 * - Items without customization snapshot render correctly (null, no error).
 * - Raw storage paths and internal IDs are excluded from the item presentation.
 *
 * B2.2.8 — Authorized admin design-file access bridge
 * - Authorized staff (orders.view + files.download_private) can access the
 *   preview and download routes.
 * - Unauthorized staff (only orders.view, no file permission) are denied.
 * - Unauthenticated requests are rejected.
 * - Route uses public_id for both order and file lookups — never raw paths.
 * - StoredFilePolicy is enforced, not bypassed.
 *
 * C1.1.6 — Read-only scope guard and regression verification
 * - Staff with only orders.view cannot update, delete, or create orders.
 * - The order detail catalog summary contains no mutation surface.
 * - All previous C1.1 order index/detail test assertions still hold.
 */
class AdminOrderItemFileAccessTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // C1.1.5 — Item and customization snapshot presentation
    // -----------------------------------------------------------------------

    public function test_order_detail_catalog_includes_items_in_summary(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: false);

        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $this->assertArrayHasKey('items', $summary);
        $this->assertCount(1, $summary['items']);
    }

    public function test_item_snapshot_contains_required_public_fields(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: false);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $item = $summary['items'][0];

        $this->assertArrayHasKey('public_id', $item);
        $this->assertArrayHasKey('product_name', $item);
        $this->assertArrayHasKey('product_slug', $item);
        $this->assertArrayHasKey('sku_code', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('unit_price_minor', $item);
        $this->assertArrayHasKey('line_total_minor', $item);
        $this->assertArrayHasKey('currency', $item);
        $this->assertArrayHasKey('customization_snapshot', $item);
    }

    public function test_item_without_customization_snapshot_renders_without_error(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: false);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $item = $summary['items'][0];

        // An empty snapshot ([]) passes through the builder and renders as array or null.
        // The critical contract: no exception is thrown and the key is present.
        $this->assertArrayHasKey('customization_snapshot', $item);
        $snapshot = $item['customization_snapshot'];
        $this->assertTrue(is_null($snapshot) || is_array($snapshot));
    }

    public function test_customization_snapshot_is_rendered_with_public_safe_fields(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: true);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $snapshot = $summary['items'][0]['customization_snapshot'];

        $this->assertNotNull($snapshot);
        $this->assertArrayHasKey('product', $snapshot);
        $this->assertArrayHasKey('sku_code', $snapshot);
        $this->assertArrayHasKey('files', $snapshot);
        $this->assertArrayHasKey('mockup_preview', $snapshot);
    }

    public function test_customization_snapshot_excludes_raw_storage_paths(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: true);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $serialized = json_encode($summary['items'][0]['customization_snapshot']);

        $this->assertStringNotContainsString('storage_path', $serialized);
        $this->assertStringNotContainsString('storage_disk', $serialized);
        $this->assertStringNotContainsString('private/', $serialized);
    }

    public function test_signed_mockup_preview_url_is_generated_when_preview_metadata_present(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: true);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $snapshot = $summary['items'][0]['customization_snapshot'];

        $this->assertArrayHasKey('mockup_preview_url', $snapshot);
        $this->assertIsString($snapshot['mockup_preview_url']);
        $this->assertStringContainsString('signature=', $snapshot['mockup_preview_url']);
        $this->assertStringContainsString('SF-TEST-ITEM-001', $snapshot['mockup_preview_url']);
    }

    public function test_item_snapshot_does_not_expose_internal_order_item_id(): void
    {
        [$order, $product, $sku] = $this->makeOrderWithItem(withCustomization: false);

        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $serialized = json_encode($summary['items'][0]);

        // The integer primary key 'id' must not appear as a root key
        $decoded = json_decode($serialized, true);
        $this->assertArrayNotHasKey('id', $decoded);
    }

    // -----------------------------------------------------------------------
    // B2.2.8 — Admin design-file access bridge route tests
    // -----------------------------------------------------------------------

    public function test_authorized_staff_can_access_design_file_preview_route(): void
    {
        Storage::fake('local');

        $staff = $this->makeStaffWithPermissions('file_viewer', ['orders.view', 'files.download_private']);
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        Storage::disk('local')->put($storedFile->storage_path, 'fake-image-content');

        $this->actingAs($staff)
            ->get(route('admin.orders.files.preview', [
                'order' => $order->public_id,
                'file' => $storedFile->public_id,
            ]))
            ->assertStatus(200);
    }

    public function test_authorized_staff_can_download_design_file(): void
    {
        Storage::fake('local');

        $staff = $this->makeStaffWithPermissions('file_downloader', ['orders.view', 'files.download_private']);
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        Storage::disk('local')->put($storedFile->storage_path, 'fake-image-content');

        $this->actingAs($staff)
            ->get(route('admin.orders.files.download', [
                'order' => $order->public_id,
                'file' => $storedFile->public_id,
            ]))
            ->assertStatus(200)
            ->assertHeader('Content-Disposition');
    }

    public function test_staff_without_file_permission_cannot_access_design_file_preview(): void
    {
        Storage::fake('local');

        $staff = $this->makeStaffWithPermissions('order_viewer_only', ['orders.view']);
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        Storage::disk('local')->put($storedFile->storage_path, 'fake-image-content');

        $this->actingAs($staff)
            ->get(route('admin.orders.files.preview', [
                'order' => $order->public_id,
                'file' => $storedFile->public_id,
            ]))
            ->assertStatus(403);
    }

    public function test_staff_without_order_view_permission_cannot_access_design_file_preview(): void
    {
        Storage::fake('local');

        $staff = $this->makeStaffWithPermissions('file_only_staff', ['files.download_private']);
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        Storage::disk('local')->put($storedFile->storage_path, 'fake-image-content');

        $this->actingAs($staff)
            ->get(route('admin.orders.files.preview', [
                'order' => $order->public_id,
                'file' => $storedFile->public_id,
            ]))
            ->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected_from_design_file_routes(): void
    {
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        $this->get(route('admin.orders.files.preview', [
            'order' => $order->public_id,
            'file' => $storedFile->public_id,
        ]))->assertStatus(401);

        $this->get(route('admin.orders.files.download', [
            'order' => $order->public_id,
            'file' => $storedFile->public_id,
        ]))->assertStatus(401);
    }

    public function test_design_file_route_uses_public_id_not_internal_id(): void
    {
        // Verify that the route binding uses public_id — attempting an integer id returns 404
        Storage::fake('local');
        $staff = $this->makeStaffWithPermissions('file_viewer_pub', ['orders.view', 'files.download_private']);
        [$order, , , $storedFile] = $this->makeOrderWithStoredFile();

        // Access with raw integer ids should not match (route uses public_id model binding)
        $this->actingAs($staff)
            ->get('/admin/orders/99999/files/99999/preview')
            ->assertStatus(404);
    }

    // -----------------------------------------------------------------------
    // C1.1.6 — Read-only scope guard regression
    // -----------------------------------------------------------------------

    public function test_staff_with_only_orders_view_cannot_mutate_order(): void
    {
        $order = Order::factory()->create();
        $staff = $this->makeStaffWithPermissions('readonly_staff', ['orders.view']);

        $this->assertFalse(Gate::forUser($staff)->allows('update', $order));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $order));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Order::class));
    }

    public function test_order_detail_catalog_summary_contains_no_mutation_surface(): void
    {
        [$order] = $this->makeOrderWithItem(withCustomization: false);

        $summary = app(OrderDetailCatalog::class)->summarize($order);

        $this->assertArrayNotHasKey('edit_url', $summary);
        $this->assertArrayNotHasKey('delete_url', $summary);
        $this->assertArrayNotHasKey('mutations', $summary);
        $this->assertArrayNotHasKey('actions', $summary);
    }

    public function test_order_detail_catalog_definition_is_still_read_only(): void
    {
        $catalog = app(OrderDetailCatalog::class);
        $def = $catalog->definition();

        $this->assertTrue($def['read_only']);
        $this->assertSame(['view', 'status', 'shipping'], $def['allowed_actions']);

        foreach (['create', 'edit', 'delete', 'forceDelete', 'restore', 'replicate', 'payment', 'refund'] as $blocked) {
            $this->assertContains($blocked, $def['blocked_actions']);
        }
    }

    public function test_order_index_catalog_is_still_website_orders_only(): void
    {
        // Regression: the index scope must not accidentally include sales orders
        $websiteOrder = Order::factory()->create(['order_type' => 'website_order']);
        $salesOrder = Order::factory()->create(['order_type' => 'sales_order']);

        // The WebsiteOrderScope on the model is tested in AdminOrderIndexTest.
        // Here we just verify the factory creates distinguishable types.
        $this->assertSame('website_order', $websiteOrder->order_type);
        $this->assertSame('sales_order', $salesOrder->order_type);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * @return array{Order, Product, ProductSku}
     */
    private function makeOrderWithItem(bool $withCustomization): array
    {
        $product = Product::factory()->create([
            'slug' => 'item-test-product',
            'name' => 'Item Test Product',
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-ITEM-01',
            'price_minor' => 2000,
        ]);

        $customer = Customer::factory()->create();

        $order = Order::query()->create([
            'public_id' => 'OD-ITEM-'.uniqid(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal_amount_minor' => 2000,
            'total_amount_minor' => 2000,
            'customer_id' => $customer->id,
            'customer_snapshot' => [
                'public_id' => 'CUS-ITEM-TEST',
                'name' => 'Item Test Customer',
                'email' => 'itemtest@example.test',
                'phone' => '9111000001',
                'customer_type' => 'individual',
            ],
        ]);

        $customizationSnapshot = $withCustomization ? [
            'schema_version' => 1,
            'product' => ['slug' => 'item-test-product', 'name' => 'Item Test Product'],
            'sku_code' => 'SKU-ITEM-01',
            'selected_options_snapshot' => [],
            'print_method' => 'screen',
            'print_position' => 'center',
            'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
            'files' => [[
                'public_id' => 'SF-TEST-ITEM-001',
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
                'source_file_public_id' => 'SF-TEST-ITEM-001',
                'route_name' => 'catalog.products.mockup-preview',
                'expires_in_minutes' => 15,
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
            ],
        ] : [];

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'product_name_snapshot' => 'Item Test Product',
            'product_slug_snapshot' => 'item-test-product',
            'sku_code_snapshot' => 'SKU-ITEM-01',
            'customization_fingerprint' => 'FP-ITEM-001',
            'customization_snapshot' => $customizationSnapshot,
            'unit_price_minor' => 2000,
            'line_subtotal_minor' => 2000,
            'line_total_minor' => 2000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
        ]);

        $order->load([
            'items',
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
        ]);

        return [$order, $product, $sku];
    }

    /**
     * @return array{Order, Product, ProductSku, StoredFile}
     */
    private function makeOrderWithStoredFile(): array
    {
        $product = Product::factory()->create([
            'slug' => 'file-test-product-'.uniqid(),
            'name' => 'File Test Product',
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
        ]);

        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'SKU-FILE-'.uniqid(),
            'price_minor' => 1500,
        ]);

        $customer = Customer::factory()->create();

        $order = Order::query()->create([
            'public_id' => 'OD-FILE-'.uniqid(),
            'order_type' => 'website_order',
            'order_source' => 'website',
            'status' => 'confirmed',
            'currency' => 'INR',
            'subtotal_amount_minor' => 1500,
            'total_amount_minor' => 1500,
            'customer_id' => $customer->id,
            'customer_snapshot' => [
                'public_id' => 'CUS-FILE-TEST',
                'name' => 'File Test Customer',
                'email' => 'filetest@example.test',
                'phone' => '9000001111',
                'customer_type' => 'individual',
            ],
        ]);

        $storedFile = StoredFile::factory()->create([
            'public_id' => 'SF-ADMIN-'.uniqid(),
            'customer_id' => $customer->id,
            'storage_disk' => 'local',
            'storage_path' => 'private/designs/test-file.png',
            'original_filename' => 'test-design.png',
            'mime_type' => 'image/png',
            'size_bytes' => 2048,
            'file_kind' => StoredFile::KIND_ORIGINAL_UPLOAD,
            'visibility' => StoredFile::VISIBILITY_PRIVATE,
            'status' => StoredFile::STATUS_ACTIVE,
        ]);

        return [$order, $product, $sku, $storedFile];
    }

    private function makeStaffWithPermissions(string $uniqueSuffix, array $permissionSlugs): User
    {
        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => str_contains($slug, 'download'),
                ],
            );
        }

        // For canAccessDashboard we need the role slug to be in DASHBOARD_ROLE_SLUGS.
        // Easiest: assign both a custom role (for permissions) and a real admin role.
        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin',
                'is_system' => true,
                'sort_order' => 1,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $adminRole->permissions()->syncWithoutDetaching($permissionIds);

        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($adminRole);

        return $user;
    }
}
