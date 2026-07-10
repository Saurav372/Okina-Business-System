<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\AuditLog;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $viewerUser;
    private User $unauthorizedUser;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed defaults for settings
        app(SettingsService::class)->seedDefaults();

        // Create orders.view permission
        Permission::query()->updateOrCreate(
            ['slug' => 'orders.view'],
            [
                'name' => 'View Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'View orders',
                'is_sensitive' => false,
            ]
        );

        // Setup role for viewer
        $viewerRole = Role::query()->updateOrCreate(
            ['slug' => 'order_viewer'],
            [
                'name' => 'Order Viewer',
                'guard_name' => 'web',
                'description' => 'Can view orders',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $viewerRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['orders.view'])->pluck('id')->all()
        );

        // Setup standard Sales Staff role for dashboard access
        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );

        $this->viewerUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->viewerUser->assignRole($viewerRole);
        $this->viewerUser->assignRole($salesRole); // Give dashboard access

        $this->unauthorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedUser->assignRole($salesRole); // Only dashboard access, no orders.view

        // Create a test order
        $this->order = Order::factory()->create([
            'order_type' => 'website_order',
            'status' => 'pending_payment',
            'customer_snapshot' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
            ],
            'shipping_address_snapshot' => [
                'contact_name' => 'Test Customer',
                'phone' => '1234567890',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'state' => 'TS',
                'postal_code' => '12345',
            ]
        ]);

        $product = Product::factory()->create(['name' => 'Premium Polo T-Shirt']);
        $sku = ProductSku::factory()->create([
            'product_id' => $product->id,
            'sku_code' => 'POLO-XL',
            'price_minor' => 27597,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 25,
            'product_name_snapshot' => $product->name,
            'product_slug_snapshot' => $product->slug,
            'sku_code_snapshot' => $sku->sku_code,
            'customization_fingerprint' => 'test-fingerprint',
            'unit_price_minor' => 27597,
            'line_subtotal_minor' => 689928,
            'line_total_minor' => 689928,
            'currency' => 'INR',
            'customization_snapshot' => [
                'selected_options_snapshot' => [
                    ['option_code' => 'size', 'value_label' => 'XL'],
                ],
            ],
        ]);
    }

    /**
     * Test that authorized user can preview order PDF.
     */
    public function test_authorized_user_can_preview_pdf(): void
    {
        $this->actingAs($this->viewerUser)
            ->get("/admin/orders/{$this->order->public_id}/pdf/preview")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee($this->order->public_id)
            ->assertSee('Order Confirmation')
            ->assertSee('Test Customer');
        
        // Assert audit log event was dispatched
        $auditLog = AuditLog::where('action', 'order.pdf_generated')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->order->id, $auditLog->subject_id);
        $this->assertEquals($this->order->public_id, $auditLog->subject_public_id);
        $this->assertEquals($this->viewerUser->id, $auditLog->actor_user_id);
    }

    /**
     * Test that authorized user can download order PDF.
     */
    public function test_authorized_user_can_download_pdf(): void
    {
        $response = $this->actingAs($this->viewerUser)
            ->get("/admin/orders/{$this->order->public_id}/pdf/download")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="Order_Confirmation_' . $this->order->public_id . '.pdf"');
        
        // Assert binary PDF content starts with %PDF marker
        $this->assertStringStartsWith('%PDF', $response->getContent());

        // Assert audit log event was dispatched
        $auditLog = AuditLog::where('action', 'order.pdf_generated')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($this->order->id, $auditLog->subject_id);
        $this->assertEquals($this->order->public_id, $auditLog->subject_public_id);
        $this->assertEquals($this->viewerUser->id, $auditLog->actor_user_id);
    }

    /**
     * Test that unauthorized user cannot preview order PDF.
     */
    public function test_unauthorized_user_cannot_preview_pdf(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get("/admin/orders/{$this->order->public_id}/pdf/preview")
            ->assertStatus(403);
            
        $this->assertNull(AuditLog::where('action', 'orders.pdf_generated')->first());
    }

    /**
     * Test that unauthorized user cannot download order PDF.
     */
    public function test_unauthorized_user_cannot_download_pdf(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get("/admin/orders/{$this->order->public_id}/pdf/download")
            ->assertStatus(403);
            
        $this->assertNull(AuditLog::where('action', 'orders.pdf_generated')->first());
    }

    /**
     * Test that previewing an order with no items returns a validation error.
     */
    public function test_preview_order_with_no_items_returns_validation_error(): void
    {
        $emptyOrder = Order::factory()->create();

        $this->actingAs($this->viewerUser)
            ->get("/admin/orders/{$emptyOrder->public_id}/pdf/preview")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }

    /**
     * Test that downloading an order with no items returns a validation error.
     */
    public function test_download_order_with_no_items_returns_validation_error(): void
    {
        $emptyOrder = Order::factory()->create();

        $this->actingAs($this->viewerUser)
            ->get("/admin/orders/{$emptyOrder->public_id}/pdf/download")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order']);
    }
}
