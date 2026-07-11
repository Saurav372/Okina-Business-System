<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SyncRecordToGoogleSheetsJob;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminOrderBulkActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        Permission::query()->updateOrCreate(['slug' => 'orders.manage'], [
            'name' => 'Orders Manage',
            'group' => 'orders',
            'guard_name' => 'web',
            'description' => 'Manage orders',
            'is_sensitive' => false,
        ]);

        Permission::query()->updateOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $role = Role::query()->updateOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'guard_name' => 'web',
            'description' => 'Admin role',
            'is_system' => true,
            'sort_order' => 0,
        ]);

        $role->permissions()->sync(Permission::query()->whereIn('slug', ['orders.manage', 'dashboard.access'])->pluck('id')->all());

        $staffRole = Role::query()->updateOrCreate(['slug' => 'sales_staff'], [
            'name' => 'Sales Staff',
            'guard_name' => 'web',
            'description' => 'Sales staff role',
            'is_system' => true,
            'sort_order' => 0,
        ]);
        $staffRole->permissions()->sync(Permission::query()->where('slug', 'dashboard.access')->pluck('id')->all());
    }

    public function test_unauthorized_users_are_denied(): void
    {
        $order = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);

        // Guest
        $this->postJson(route('admin.orders.bulk'), [
            'action' => 'confirm',
            'order_ids' => [$order->public_id],
        ])->assertStatus(401);

        // User with dashboard access but without orders.manage permission
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->assignRole('sales_staff');

        $this->actingAs($unauthorizedUser)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order->public_id],
            ])->assertStatus(403);
    }

    public function test_bulk_confirm_succeeds_for_pending_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);
        $order2 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);

        Queue::fake();
        config(['sheets.enabled' => true]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order1->public_id, $order2->public_id],
            ]);

        $expectedIds = collect([$order1->public_id, $order2->public_id])->sort()->values()->all();

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '2 orders confirmed successfully.',
            'data' => [
                'updated_count' => 2,
                'updated_ids' => $expectedIds,
            ],
        ]);

        $this->assertEquals(OrderStatus::Confirmed->value(), $order1->fresh()->status);
        $this->assertEquals(OrderStatus::Confirmed->value(), $order2->fresh()->status);
        $this->assertNotNull($order1->fresh()->confirmed_at);
        $this->assertNotNull($order2->fresh()->confirmed_at);

        // Assert audit logs
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.edited',
            'subject_public_id' => $order1->public_id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.edited',
            'subject_public_id' => $order2->public_id,
        ]);

        // Assert background jobs queued (due to observer)
        Queue::assertPushed(SyncRecordToGoogleSheetsJob::class, 2);
    }

    public function test_bulk_cancel_succeeds_for_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);
        $order2 = Order::factory()->create(['status' => OrderStatus::Confirmed->value()]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'cancel',
                'order_ids' => [$order1->public_id, $order2->public_id],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(OrderStatus::Cancelled->value(), $order1->fresh()->status);
        $this->assertEquals(OrderStatus::Cancelled->value(), $order2->fresh()->status);
        $this->assertNotNull($order1->fresh()->cancelled_at);
        $this->assertNotNull($order2->fresh()->cancelled_at);
    }

    public function test_duplicates_are_normalized(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);
        $order2 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order1->public_id, $order1->public_id, $order2->public_id],
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.updated_count', 2);
    }

    public function test_unknown_id_fails_atomically(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order1->public_id, 'ORD-UNKNOWN-999'],
            ]);

        $response->assertStatus(500); // Throws InvalidArgumentException which returns 500
        $this->assertEquals(OrderStatus::PendingPayment->value(), $order1->fresh()->status);
    }

    public function test_empty_selection_returns_validation_error(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_already_confirmed_orders_fails_and_rolls_back(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);
        $order2 = Order::factory()->create(['status' => OrderStatus::Confirmed->value(), 'confirmed_at' => now()->subDay()]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order1->public_id, $order2->public_id],
            ]);

        $response->assertStatus(422); // ValidationException
        $this->assertEquals(OrderStatus::PendingPayment->value(), $order1->fresh()->status);
        // Order 2 confirmed_at remains original (no rollback because it was not changed, but Order 1 didn't transition)
        $this->assertTrue($order2->fresh()->confirmed_at->isPast());
    }

    public function test_concurrent_modification_validation_failure_rolls_back(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order1 = Order::factory()->create(['status' => OrderStatus::PendingPayment->value()]);
        // order 2 in terminal Shipped state (cannot transition to Confirmed)
        $order2 = Order::factory()->create(['status' => OrderStatus::Shipped->value()]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.orders.bulk'), [
                'action' => 'confirm',
                'order_ids' => [$order1->public_id, $order2->public_id],
            ]);

        $response->assertStatus(422); // ValidationException
        $this->assertEquals(OrderStatus::PendingPayment->value(), $order1->fresh()->status);
    }
}
