<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSensitiveDataMaskingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_sensitive_keys_are_masked_in_all_payload_columns_recursively(): void
    {
        // 1. Dispatch an order event containing sensitive fields in old_values, new_values, and metadata
        $order = Order::factory()->create();

        event(new AuditEvent('orders.order_edited', $this->adminUser, [
            'subject_type' => 'order',
            'subject_id' => $order->id,
            'subject_public_id' => $order->public_id,
            'old_values' => [
                'status' => 'pending_payment',
                'payment_credentials' => [
                    'card_number' => '1234567812345678',
                    'card_cvv' => '123',
                ],
                'token' => 'old-token-xyz',
                'details' => [
                    'password' => 'super-secret',
                    'safe_field' => 'hello',
                ],
            ],
            'new_values' => [
                'status' => 'confirmed',
                'gateway_payload' => [
                    'secret' => 'new-secret-abc',
                    'api_key' => 'key-123',
                ],
            ],
            'metadata' => [
                'ip' => '127.0.0.1',
                'webhook_payload' => [
                    'private_key' => 'ssh-rsa-blah',
                    'otp' => '654321',
                ],
            ],
        ]));

        $log = AuditLog::where('action', 'order.edited')->firstOrFail();

        // Assert old_values are masked recursively
        $this->assertSame('pending_payment', $log->old_values['status']);
        $this->assertSame('[redacted]', $log->old_values['payment_credentials']); // Entire sensitive block redacted
        $this->assertSame('[redacted]', $log->old_values['token']);
        $this->assertSame('[redacted]', $log->old_values['details']['password']); // Child sensitive key redacted
        $this->assertSame('hello', $log->old_values['details']['safe_field']); // Child non-sensitive key preserved

        // Assert new_values are masked recursively
        $this->assertSame('confirmed', $log->new_values['status']);
        $this->assertSame('[redacted]', $log->new_values['gateway_payload']); // Entire sensitive block redacted

        // Assert metadata is masked recursively
        $this->assertSame('127.0.0.1', $log->metadata['ip']);
        $this->assertSame('[redacted]', $log->metadata['webhook_payload']); // Entire sensitive block redacted
    }

    public function test_non_sensitive_fields_are_preserved_without_modification(): void
    {
        $product = Product::factory()->create();

        event(new AuditEvent('products.product_updated', $this->adminUser, [
            'subject_type' => 'product',
            'subject_id' => $product->id,
            'subject_public_id' => $product->slug,
            'old_values' => [
                'name' => 'Old Product Name',
                'status' => 'draft',
            ],
            'new_values' => [
                'name' => 'New Product Name',
                'status' => 'active',
            ],
            'metadata' => [
                'user_agent' => 'Mozilla/5.0',
            ],
        ]));

        $log = AuditLog::where('action', 'product.updated')->firstOrFail();

        $this->assertSame('Old Product Name', $log->old_values['name']);
        $this->assertSame('draft', $log->old_values['status']);
        $this->assertSame('New Product Name', $log->new_values['name']);
        $this->assertSame('active', $log->new_values['status']);
        $this->assertSame('Mozilla/5.0', $log->metadata['user_agent']);
    }

    public function test_masking_applies_across_multiple_event_types(): void
    {
        // 1. Payment Recorded
        $order = Order::factory()->create();
        event(new AuditEvent('payments.payment_recorded', $this->adminUser, [
            'subject_type' => 'payment',
            'subject_id' => 1,
            'subject_public_id' => 'pay_123',
            'old_values' => ['amount' => 100],
            'new_values' => [
                'amount' => 100,
                'gateway_payload' => ['card_number' => '4111111111111111'],
            ],
        ]));

        // 2. Inventory Stock Moved
        $sku = ProductSku::factory()->create();
        event(new AuditEvent('inventory.stock_moved', $this->adminUser, [
            'subject_type' => 'inventory_movement',
            'subject_id' => 2,
            'subject_public_id' => 'mov_456',
            'old_values' => ['quantity' => 10],
            'new_values' => [
                'quantity' => 20,
                'secret' => 'confidential-stock-code',
            ],
        ]));

        // 3. User Role Assigned
        $targetUser = User::factory()->create();
        event(new AuditEvent('users.role_assigned', $this->adminUser, [
            'subject_type' => 'user',
            'subject_id' => $targetUser->id,
            'subject_public_id' => $targetUser->email,
            'new_values' => [
                'role_slug' => 'admin',
                'password' => 'temporary-pass',
            ],
        ]));

        // Verify Payment Redaction
        $paymentLog = AuditLog::where('action', 'payment.recorded')->firstOrFail();
        $this->assertSame(100, $paymentLog->new_values['amount']);
        $this->assertSame('[redacted]', $paymentLog->new_values['gateway_payload']);

        // Verify Inventory Redaction
        $inventoryLog = AuditLog::where('action', 'stock.moved')->firstOrFail();
        $this->assertSame(20, $inventoryLog->new_values['quantity']);
        $this->assertSame('[redacted]', $inventoryLog->new_values['secret']);

        // Verify User Redaction
        $userLog = AuditLog::where('action', 'role.assigned')->firstOrFail();
        $this->assertSame('admin', $userLog->new_values['role_slug']);
        $this->assertSame('[redacted]', $userLog->new_values['password']);
    }
}
