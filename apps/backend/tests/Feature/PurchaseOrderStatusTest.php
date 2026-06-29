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
use Illuminate\Support\Facades\DB;
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

        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

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
            Permission::query()->where('slug', 'purchases.manage')->pluck('id')->all()
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
     * Test gated access: only purchases.manage permission allows updating status.
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

        // Assert response values
        $response->assertJsonPath('status', 'ordered')
            ->assertJsonPath('ordered_at', $now->jsonSerialize())
            ->assertJsonPath('received_at', null)
            ->assertJsonPath('cancelled_at', null);

        $po->refresh();
        $this->assertEquals($now->toDateTimeString(), $po->ordered_at->toDateTimeString());
        $this->assertNull($po->received_at);
        $this->assertNull($po->cancelled_at);

        // Verify exact AuditEvent payload
        Event::assertDispatchedTimes(AuditEvent::class, 1);
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po, $now) {
            return $event->key === 'purchase_orders.status_updated'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['previous_status'] === 'draft'
                && $event->payload['status'] === 'ordered'
                && $event->payload['previous_payment_status'] === 'unpaid'
                && $event->payload['payment_status'] === 'unpaid'
                && $event->payload['ordered_at'] === $now->toIso8601String()
                && $event->payload['received_at'] === null
                && $event->payload['cancelled_at'] === null;
        });

        // 2. ordered -> partially_received
        Event::fake([AuditEvent::class]); // reset fakes
        $later = Carbon::now()->addHour()->microsecond(0);
        Carbon::setTestNow($later);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::PARTIALLY_RECEIVED->value,
        ])->assertStatus(200);

        // received_at must remain null for partially_received
        $response->assertJsonPath('status', 'partially_received')
            ->assertJsonPath('received_at', null);

        $po->refresh();
        $this->assertNull($po->received_at);
        // ordered_at must remain unchanged
        $this->assertEquals($now->toDateTimeString(), $po->ordered_at->toDateTimeString());

        Event::assertDispatchedTimes(AuditEvent::class, 1);

        // 3. partially_received -> received
        Event::fake([AuditEvent::class]); // reset
        $evenLater = Carbon::now()->addHours(2)->microsecond(0);
        Carbon::setTestNow($evenLater);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::RECEIVED->value,
        ])->assertStatus(200);

        // received_at should be stamped now
        $response->assertJsonPath('status', 'received')
            ->assertJsonPath('received_at', $evenLater->jsonSerialize());

        $po->refresh();
        $this->assertEquals($evenLater->toDateTimeString(), $po->received_at->toDateTimeString());
        $this->assertEquals($now->toDateTimeString(), $po->ordered_at->toDateTimeString());

        // 4. received -> closed
        Event::fake([AuditEvent::class]); // reset
        $closedTime = Carbon::now()->addHours(3)->microsecond(0);
        Carbon::setTestNow($closedTime);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::CLOSED->value,
        ])->assertStatus(200);

        $response->assertJsonPath('status', 'closed');

        $po->refresh();
        $this->assertEquals($now->toDateTimeString(), $po->ordered_at->toDateTimeString());
        $this->assertEquals($evenLater->toDateTimeString(), $po->received_at->toDateTimeString());

        Carbon::setTestNow(); // reset
    }

    /**
     * Test draft -> cancelled and ordered -> cancelled transitions.
     */
    public function test_cancellation_transitions_and_timestamps(): void
    {
        $this->actingAs($this->privilegedStaff);

        // 1. draft -> cancelled
        $po1 = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-CANCEL1',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $now = Carbon::now()->microsecond(0);
        Carbon::setTestNow($now);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po1->id), [
            'status' => VendorOrderStatus::CANCELLED->value,
        ])->assertStatus(200);

        $response->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('cancelled_at', $now->jsonSerialize())
            ->assertJsonPath('ordered_at', null)
            ->assertJsonPath('received_at', null);

        // Attempting another transition out of cancelled must fail and leave cancelled_at unchanged
        $this->postJson(route('admin.purchase_orders.status.update', $po1->id), [
            'status' => VendorOrderStatus::ORDERED->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);

        $po1->refresh();
        $this->assertEquals($now->toDateTimeString(), $po1->cancelled_at->toDateTimeString());

        // 2. ordered -> cancelled
        $po2 = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-CANCEL2',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'ordered_at' => $now,
        ]);

        $later = Carbon::now()->addHour()->microsecond(0);
        Carbon::setTestNow($later);

        $response = $this->postJson(route('admin.purchase_orders.status.update', $po2->id), [
            'status' => VendorOrderStatus::CANCELLED->value,
        ])->assertStatus(200);

        $response->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('cancelled_at', $later->jsonSerialize())
            ->assertJsonPath('ordered_at', $now->jsonSerialize());

        Carbon::setTestNow(); // reset
    }

    /**
     * Test that invalid and same-status transitions throw 422,
     * and that failed transitions preserve the database state exactly.
     */
    public function test_invalid_transitions_rejection_and_database_idempotency(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-INVALID',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // 1. Same status transition (draft -> draft) should fail
        $response = $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::DRAFT->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);

        // Assert database state is unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderStatus::DRAFT, $po->status);
        $this->assertNull($po->ordered_at);
        $this->assertNull($po->received_at);
        $this->assertNull($po->cancelled_at);

        // 2. Invalid jump (draft -> received) should fail
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::RECEIVED->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);

        // Assert database remains unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderStatus::DRAFT, $po->status);
        $this->assertNull($po->ordered_at);

        // Assert no audit event was dispatched on failures
        Event::assertNotDispatched(AuditEvent::class);
    }

    /**
     * Test transaction rollback protection.
     */
    public function test_transaction_rollback_on_failure(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-ROLLBACK',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // Force an exception inside the transaction using a fake hook or listener,
        // or by intentionally throwing inside a mock/events structure.
        // Let's hook into afterCommit and force an exception, but wait: afterCommit executes after the transaction commits.
        // Let's trigger a query failure or model event exception during saving to force rollback.
        VendorOrder::saving(function ($model) {
            if ($model->public_id === 'PO-ROLLBACK' && $model->status === VendorOrderStatus::ORDERED) {
                throw new \RuntimeException('Forced exception to trigger rollback.');
            }
        });

        $this->withoutExceptionHandling();

        try {
            $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
                'status' => VendorOrderStatus::ORDERED->value,
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Forced exception to trigger rollback.', $e->getMessage());
        }

        // Clean up saving listener
        VendorOrder::flushEventListeners();

        // Verify status and timestamps in DB remain unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderStatus::DRAFT, $po->status);
        $this->assertNull($po->ordered_at);

        // Verify no audit event was dispatched
        Event::assertNotDispatched(AuditEvent::class);
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
        // We transition status from ordered -> partially_received (valid status transition)
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::PARTIALLY_RECEIVED->value,
            'payment_status' => VendorOrderPaymentStatus::PARTIALLY_PAID->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_status']);

        // 3. Invalid reverse payment transition (partially_paid -> unpaid) should throw 422
        // We transition status from ordered -> received (valid transition since we didn't save partially_received on failure above)
        $this->postJson(route('admin.purchase_orders.status.update', $po->id), [
            'status' => VendorOrderStatus::RECEIVED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_status']);
    }
}
