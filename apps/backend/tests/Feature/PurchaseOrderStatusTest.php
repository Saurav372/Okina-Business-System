<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private User $privilegedStaff;

    private User $unprivilegedUser;

    private Vendor $activeVendor;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.manage'],
            [
                'name' => 'Manage Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can manage purchase orders',
                'is_sensitive' => true,
            ]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.approve'],
            [
                'name' => 'Approve Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can approve purchase orders',
                'is_sensitive' => false,
            ]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.cancel'],
            [
                'name' => 'Cancel Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can cancel purchase orders',
                'is_sensitive' => false,
            ]
        );

        Permission::query()->updateOrCreate(
            ['slug' => 'purchases.view'],
            [
                'name' => 'View Purchases',
                'group' => 'purchases',
                'guard_name' => 'web',
                'description' => 'Can view purchase orders',
                'is_sensitive' => false,
            ]
        );

        // Create roles
        $staffRole = Role::query()->updateOrCreate(
            ['slug' => Role::INVENTORY_STAFF],
            [
                'name' => 'Inventory Staff',
                'guard_name' => 'web',
                'description' => 'Inventory staff role',
                'is_system' => true,
                'sort_order' => 1,
            ]
        );
        $staffRole->permissions()->sync(
            Permission::query()->whereIn('slug', ['purchases.manage', 'purchases.approve', 'purchases.cancel'])->pluck('id')->all()
        );

        $salesRole = Role::query()->updateOrCreate(
            ['slug' => Role::SALES_STAFF],
            [
                'name' => 'Sales Staff',
                'guard_name' => 'web',
                'description' => 'Sales staff role',
                'is_system' => true,
                'sort_order' => 2,
            ]
        );
        $salesRole->permissions()->sync(
            Permission::query()->where('slug', 'purchases.view')->pluck('id')->all()
        );

        // Create users
        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);

        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        // Create vendor
        $this->activeVendor = Vendor::create([
            'name' => 'Supplier Inc',
            'vendor_code' => 'VND-SUPP99',
            'status' => VendorStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test guest and unprivileged user authorization gating.
     */
    public function test_authorization_gating(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-AUTH11',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // Guest gets 401
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::ORDERED->value,
        ])->assertStatus(401);

        // Unprivileged staff gets 403
        $this->actingAs($this->unprivilegedUser)
            ->postJson(route('admin.purchase_orders.status.update', $po->id), [
                'status' => VendorOrderStatus::ORDERED->value,
            ])->assertStatus(403);

        // Privileged staff gets 200
        $this->actingAs($this->privilegedStaff)
            ->postJson(route('admin.purchase_orders.status.update', $po->id), [
                'status' => VendorOrderStatus::ORDERED->value,
            ])->assertStatus(200);
    }

    /**
     * Test valid lifecycle transitions and correct timestamp behaviors.
     */
    public function test_valid_status_transitions_and_timestamps(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-LIFECYCLE',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $this->assertNull($po->ordered_at);
        $this->assertNull($po->received_at);
        $this->assertNull($po->cancelled_at);

        // 1. draft -> ordered
        $now = Carbon::now()->microsecond(0);
        Carbon::setTestNow($now);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::ORDERED->value,
        ])->assertStatus(200);

        $response->assertJsonPath('status', 'ordered')
            ->assertJsonPath('ordered_at', $now->jsonSerialize())
            ->assertJsonPath('received_at', null)
            ->assertJsonPath('cancelled_at', null);

        $po->refresh();
        $this->assertEquals($now->toDateTimeString(), $po->ordered_at->toDateTimeString());
        $this->assertNull($po->received_at);
        $this->assertNull($po->cancelled_at);

        // Verify AuditEvent
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po) {
            return $event->key === 'purchase_orders.status_updated'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['previous_status'] === 'draft'
                && $event->payload['status'] === 'ordered';
        });

        // 2. Manual transition to partially_received or received is rejected with 422
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::PARTIALLY_RECEIVED->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);

        // 3. ordered -> cancelled
        Event::fake([AuditEvent::class]);
        $cancelledTime = Carbon::now()->addHours(2)->microsecond(0);
        Carbon::setTestNow($cancelledTime);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::CANCELLED->value,
        ])->assertStatus(200);

        $response->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('cancelled_at', $cancelledTime->jsonSerialize());

        $po->refresh();
        $this->assertEquals(VendorOrderStatus::CANCELLED, $po->status);
        $this->assertEquals($cancelledTime->toDateTimeString(), $po->cancelled_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /**
     * Test payment status transitions.
     */
    public function test_payment_status_transitions(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PAYMENT',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // 1. Valid payment transition: unpaid -> partially_paid (with status transition: draft -> ordered)
        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::PARTIALLY_PAID->value,
        ])->assertStatus(200);

        $response->assertJsonPath('payment_status', 'partially_paid');

        // 2. Same status payment transition (partially_paid -> partially_paid) should throw 422
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'payment_status' => VendorOrderPaymentStatus::PARTIALLY_PAID->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_status']);
    }
}
