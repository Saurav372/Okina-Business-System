<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Order;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private User $authorizedUser;

    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the manage orders permission
        Permission::query()->updateOrCreate(
            ['slug' => 'orders.manage'],
            [
                'name' => 'Manage Orders',
                'group' => 'orders',
                'guard_name' => 'web',
                'description' => 'Manage orders',
                'is_sensitive' => false,
            ]
        );

        // Setup roles
        $manageRole = Role::query()->updateOrCreate(
            ['slug' => 'order_manager'],
            [
                'name' => 'Order Manager',
                'guard_name' => 'web',
                'description' => 'Can manage orders',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $manageRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['orders.manage'])->pluck('id')->all()
        );

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

        // Setup users
        $this->authorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->authorizedUser->assignRole($manageRole);
        $this->authorizedUser->assignRole($salesRole);

        $this->unauthorizedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unauthorizedUser->assignRole($salesRole);
    }

    /**
     * Test confirming a pending_payment website order successfully.
     */
    public function test_it_confirms_pending_payment_order(): void
    {
        $order = Order::factory()->create([
            'order_type' => 'website_order',
            'status' => 'pending_payment',
            'confirmed_at' => null,
        ]);

        $this->actingAs($this->authorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_it_cannot_confirm_cancelled_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'cancelled',
            'confirmed_at' => null,
            'cancelled_at' => now()->subDay(),
        ]);

        $this->actingAs($this->authorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['status']);

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertNull($order->confirmed_at);
    }

    /**
     * Test that we cannot confirm an order in delivered state.
     */
    public function test_it_cannot_confirm_delivered_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'delivered',
            'confirmed_at' => now()->subDays(2),
            'delivered_at' => now()->subDay(),
        ]);

        $originalConfirmedAt = $order->confirmed_at;

        $this->actingAs($this->authorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'completed',
                'shipping_status' => 'delivered',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['status']);

        $order->refresh();
        $this->assertSame('delivered', $order->status);
        $this->assertSame($originalConfirmedAt->toDateTimeString(), $order->confirmed_at->toDateTimeString());
    }

    /**
     * Test that we cannot confirm an order in refunded state.
     */
    public function test_it_cannot_confirm_refunded_order(): void
    {
        $order = Order::factory()->create([
            'status' => 'refunded',
            'confirmed_at' => now()->subDays(2),
            'refunded_at' => now()->subDay(),
        ]);

        $originalConfirmedAt = $order->confirmed_at;

        $this->actingAs($this->authorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'completed',
                'shipping_status' => 'not_shipped',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['status']);

        $order->refresh();
        $this->assertSame('refunded', $order->status);
        $this->assertSame($originalConfirmedAt->toDateTimeString(), $order->confirmed_at->toDateTimeString());
    }

    /**
     * Test that updateStatus requires orders.manage permission.
     */
    public function test_it_requires_orders_manage_permission(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending_payment',
        ]);

        $this->actingAs($this->unauthorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertStatus(403);
    }

    /**
     * Test that customer accounts cannot confirm orders.
     */
    public function test_it_prevents_customer_confirmation_attempts(): void
    {
        $customerAccount = CustomerAccount::factory()->create();
        $order = Order::factory()->create([
            'status' => 'pending_payment',
        ]);

        // A customer account acting under customer guard should get redirected to login (302)
        $this->actingAs($customerAccount, 'customer')
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'under_review',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertStatus(302);
    }

    /**
     * Test that reconfirming an already confirmed order does not update confirmed_at.
     */
    public function test_it_does_not_reconfirm_already_confirmed_order(): void
    {
        $confirmedAtTime = now()->subDays(2);
        $order = Order::factory()->create([
            'status' => 'confirmed',
            'confirmed_at' => $confirmedAtTime,
        ]);

        $this->actingAs($this->authorizedUser)
            ->postJson("/admin/orders/{$order->public_id}/status", [
                'status' => 'confirmed',
                'design_status' => 'approved',
                'production_status' => 'not_started',
                'shipping_status' => 'not_shipped',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('confirmed', $order->status);
        $this->assertSame($confirmedAtTime->toDateTimeString(), $order->confirmed_at->toDateTimeString());
    }
}
