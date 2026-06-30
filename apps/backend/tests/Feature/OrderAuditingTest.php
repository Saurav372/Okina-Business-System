<?php

namespace Tests\Feature;

use App\Enums\AuditActorType;
use App\Events\AuditEvent;
use App\Models\AuditLog;
use App\Models\AuditLogRelatedRecord;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderAuditingTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->staffUser = $this->makeStaffWithPermissions(['orders.manage']);
    }

    /**
     * Helper to create a staff user with specific permissions.
     */
    private function makeStaffWithPermissions(array $permissionSlugs): User
    {
        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->headline()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                    'guard_name' => 'web',
                    'description' => str($slug)->headline()->toString(),
                    'is_sensitive' => false,
                ],
            );
        }

        $customSlug = 'custom_role_'.implode('_', $permissionSlugs);
        $role = Role::query()->updateOrCreate(
            ['slug' => $customSlug],
            [
                'name' => 'Custom Role '.$customSlug,
                'guard_name' => 'web',
                'description' => 'Custom Role for Test',
                'is_system' => false,
                'sort_order' => 10,
            ],
        );

        $permissionIds = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);

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

        $user = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($adminRole);
        $user->assignRole($role);

        return $user;
    }

    /**
     * Test that updating order status creates an audit log entry.
     */
    public function test_updating_order_status_creates_audit_log(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'design_status' => 'under_review',
            'production_status' => 'not_started',
            'shipping_status' => 'not_shipped',
        ]);

        $this->actingAs($this->staffUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertOk();

        $auditLog = AuditLog::first();
        $this->assertNotNull($auditLog);
        $this->assertEquals('order', $auditLog->subject_type);
        $this->assertEquals($order->id, $auditLog->subject_id);
        $this->assertEquals($order->public_id, $auditLog->subject_public_id);
        $this->assertEquals('orders', $auditLog->module);
        $this->assertSame(AuditActorType::USER, $auditLog->actor_type);
        $this->assertEquals($this->staffUser->id, $auditLog->actor_user_id);

        // Old values snapshot
        $this->assertEquals('pending_payment', $auditLog->old_values['status']);
        $this->assertEquals('under_review', $auditLog->old_values['design_status']);

        // New values snapshot
        $this->assertEquals('confirmed', $auditLog->new_values['status']);
    }

    /**
     * Test that related records are written along with the audit log.
     */
    public function test_updating_order_status_creates_related_records(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'design_status' => 'under_review',
            'production_status' => 'not_started',
            'shipping_status' => 'not_shipped',
        ]);

        $this->actingAs($this->staffUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'approved',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertOk();

        $auditLog = AuditLog::first();
        $this->assertNotNull($auditLog);

        // Subject should be linked as related record
        $related = AuditLogRelatedRecord::where('audit_log_id', $auditLog->id)
            ->where('related_type', 'order')
            ->where('relation', 'subject')
            ->first();

        $this->assertNotNull($related);
        $this->assertEquals($order->id, $related->related_id);
        $this->assertEquals($order->public_id, $related->related_public_id);
    }

    /**
     * Test that if a transaction is rolled back, no audit log is written.
     */
    public function test_no_audit_log_written_when_transaction_rolls_back(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
            'design_status' => 'under_review',
            'production_status' => 'not_started',
            'shipping_status' => 'not_shipped',
        ]);

        $auditLogCountBefore = AuditLog::count();

        // Simulate a transaction that rolls back
        try {
            DB::transaction(function () use ($order): void {
                $order->update(['status' => 'confirmed']);

                DB::afterCommit(function () use ($order): void {
                    event(new AuditEvent('orders.order_edited', null, [
                        'subject_type' => 'order',
                        'subject_id' => $order->id,
                        'subject_public_id' => $order->public_id,
                        'old_values' => ['status' => 'pending_payment'],
                        'new_values' => ['status' => 'confirmed'],
                    ]));
                });

                // Force a rollback by throwing an exception
                throw new \RuntimeException('Simulated rollback');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        // No audit log should have been written
        $this->assertEquals($auditLogCountBefore, AuditLog::count());
    }

    /**
     * Test that dispatching the same event twice with the same idempotency key
     * creates only one audit log entry and does not throw exceptions.
     */
    public function test_idempotency_key_prevents_duplicate_audit_logs(): void
    {
        $idempotencyKey = 'order-status-test-idem-001';

        $order = Order::factory()->create([
            'status' => 'pending_payment',
        ]);

        $payload = [
            'subject_type' => 'order',
            'subject_id' => $order->id,
            'subject_public_id' => $order->public_id,
            'old_values' => ['status' => 'pending_payment'],
            'new_values' => ['status' => 'confirmed'],
            'idempotency_key' => $idempotencyKey,
        ];

        // First dispatch
        event(new AuditEvent('orders.order_edited', $this->staffUser, $payload));

        // Second dispatch with same idempotency key
        event(new AuditEvent('orders.order_edited', $this->staffUser, $payload));

        // Only one audit log should exist
        $this->assertEquals(1, AuditLog::where('idempotency_key', $idempotencyKey)->count());
    }
}
