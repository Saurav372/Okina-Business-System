<?php

namespace Tests\Feature;

use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SecurityReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions for testing using custom models
        $dashboardAccess = Permission::query()->updateOrCreate(
            ['slug' => 'dashboard.access'],
            [
                'name' => 'Dashboard Access',
                'group' => 'settings',
                'guard_name' => 'web',
                'description' => 'Can view dashboard',
                'is_sensitive' => false,
            ]
        );

        $ordersView = Permission::query()->updateOrCreate(
            ['slug' => 'orders.view'],
            [
                'name' => 'View Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'Can view orders',
                'is_sensitive' => false,
            ]
        );

        $superAdminRole = Role::query()->updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'Super Admin',
                'guard_name' => 'web',
                'description' => 'Super Admin',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );
        $superAdminRole->permissions()->sync([$dashboardAccess->id, $ordersView->id]);

        $salesStaffRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales Staff',
                'is_system' => false,
                'sort_order' => 2,
            ]
        );
        // Only dashboard access, no orders.view
        $salesStaffRole->permissions()->sync([$dashboardAccess->id]);
    }

    /**
     * Helper to create a staff user with dashboard access.
     */
    protected function createStaffUser(string $roleSlug): User
    {
        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($roleSlug);

        return $user;
    }

    /**
     * 1. Test Admin Endpoint Permissions Matrix.
     */
    public function test_admin_endpoint_permissions_matrix(): void
    {
        // Create an order in the database so that route model binding resolves successfully
        $order = Order::factory()->create(['public_id' => '123']);

        // GUEST: Returns 401 unauthenticated for JSON requests
        $response = $this->getJson("/admin/orders/{$order->public_id}/detail");
        $response->assertStatus(401);

        // UNAUTHORIZED STAFF (no orders.view): Returns 403 Forbidden
        $staff = $this->createStaffUser(Role::SALES_STAFF);
        $response = $this->actingAs($staff)->getJson("/admin/orders/{$order->public_id}/detail");
        $response->assertStatus(403);

        // AUTHORIZED STAFF (Super Admin): Returns 200/OK (passes policy checks)
        $admin = $this->createStaffUser(Role::SUPER_ADMIN);
        $response = $this->actingAs($admin)->getJson("/admin/orders/{$order->public_id}/detail");
        $response->assertStatus(200);
    }

    /**
     * 2. Test Rate Limiting Active on Sensitive Routes.
     */
    public function test_rate_limiting_active_on_sensitive_routes(): void
    {
        // Send 6 rapid requests to the throttled webhook endpoint. Rate limit is 5 attempts per minute.
        // We use different event_ids to bypass the database duplicate check.
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->postJson('/api/webhooks/payments/cashfree', [
                'event_id' => "evt_rate_{$i}",
                'event_type' => 'payment_success',
            ]);
        }

        // The 6th request should fail with 429 Too Many Requests from the throttle middleware
        $response->assertStatus(429);
    }

    /**
     * 3. Test CORS Origins Restricted.
     */
    public function test_cors_origins_restricted(): void
    {
        // 1. Authorized origin should succeed and return headers
        $response = $this->get('/api/catalog/categories', [
            'Origin' => 'http://localhost:4321',
        ]);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4321');

        // 2. Unauthorized origin should not return CORS headers allowing it
        $response2 = $this->get('/api/catalog/categories', [
            'Origin' => 'http://malicious-website.com',
        ]);
        $this->assertFalse($response2->headers->has('Access-Control-Allow-Origin')
            && $response2->headers->get('Access-Control-Allow-Origin') === 'http://malicious-website.com');
    }

    /**
     * 4. Test File Upload Safety.
     */
    public function test_file_upload_safety(): void
    {
        Config::set('sheets.enabled', false);

        // Ensure product is active and public to be visible in controller
        $product = Product::factory()->create([
            'status' => Product::STATUS_ACTIVE,
            'visibility' => Product::VISIBILITY_PUBLIC,
        ]);
        $customer = CustomerAccount::factory()->create();

        // 1. Uploading dangerous .php file fails with validation error (422)
        $dangerousFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-httpd-php');
        $response = $this->actingAs($customer, 'customer')
            ->postJson("/api/catalog/products/{$product->slug}/design-upload", [
                'design_file' => $dangerousFile,
                'sku_code' => 'TEST-SKU',
                'print_position' => 'front',
                'print_method' => 'screen_print',
            ]);

        $response->assertStatus(422);

        // 2. Uploading too large file (> 5MB) fails with validation error (422)
        $largeFile = UploadedFile::fake()->create('large.png', 6000, 'image/png'); // 6MB
        $response2 = $this->actingAs($customer, 'customer')
            ->postJson("/api/catalog/products/{$product->slug}/design-upload", [
                'design_file' => $largeFile,
                'sku_code' => 'TEST-SKU',
                'print_position' => 'front',
                'print_method' => 'screen_print',
            ]);

        $response2->assertStatus(422);
    }

    /**
     * 5. Test Webhook Signature Verification.
     */
    public function test_payment_webhook_signature_verification(): void
    {
        // 1. Send webhook without signature -> returns 401
        $response = $this->postJson('/api/webhooks/payments/cashfree', [
            'event_id' => 'evt_123',
            'event_type' => 'payment_success',
        ]);
        $response->assertStatus(401);

        // 2. Send webhook with invalid signature -> returns 401
        // We use a different event_id to prevent hitting the duplicate webhook check (which returns 200).
        $response2 = $this->postJson('/api/webhooks/payments/cashfree', [
            'event_id' => 'evt_124',
            'event_type' => 'payment_success',
        ], [
            'x-signature' => 'invalid-signature-hash',
        ]);
        $response2->assertStatus(401);
    }
}
