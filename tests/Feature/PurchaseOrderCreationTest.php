<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Exceptions\InvalidPurchaseOrderExpectedDateException;
use App\Exceptions\InvalidPurchaseOrderStatusTransitionException;
use App\Exceptions\PurchaseOrderImmutableException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $unprivilegedUser;

    private User $privilegedStaff;

    private User $viewOnlyStaff;

    private Vendor $activeVendor;

    private Vendor $inactiveVendor;

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
        $adminRole = Role::query()->updateOrCreate(
            ['slug' => Role::ADMIN],
            [
                'name' => 'Admin',
                'guard_name' => 'web',
                'description' => 'Admin role',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
        $adminRole->permissions()->sync(
            Permission::query()->pluck('id')->all()
        );

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
        $this->adminUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->unprivilegedUser = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->unprivilegedUser->assignRole($salesRole);

        $this->privilegedStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->privilegedStaff->assignRole($staffRole);

        $this->viewOnlyStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->viewOnlyStaff->assignRole($salesRole);

        // Seed vendors
        $this->activeVendor = Vendor::create([
            'name' => 'Active Supplier',
            'vendor_code' => 'VND-ACTIVE99',
            'status' => VendorStatus::ACTIVE->value,
        ]);

        $this->inactiveVendor = Vendor::create([
            'name' => 'Inactive Supplier',
            'vendor_code' => 'VND-INACTIVE99',
            'status' => VendorStatus::INACTIVE->value,
        ]);
    }

    /**
     * Test guest and unauthorized user are blocked.
     */
    public function test_unauthorized_users_are_blocked(): void
    {
        // Guest
        $this->getJson(route('admin.purchase_orders.index'))->assertStatus(401);
        $this->postJson(route('admin.purchase_orders.store'), [])->assertStatus(401);

        // Authenticated but unprivileged
        $salesRole = Role::where('slug', Role::SALES_STAFF)->first();
        $salesRole->permissions()->detach();

        $this->actingAs($this->unprivilegedUser);
        $this->getJson(route('admin.purchase_orders.index'))->assertStatus(403);
        $this->postJson(route('admin.purchase_orders.store'), [])->assertStatus(403);
    }

    /**
     * Test view-only staff can list/show but not create/update/delete.
     */
    public function test_view_only_staff_permissions(): void
    {
        $this->actingAs($this->viewOnlyStaff);

        // List/Show: Allowed
        $this->getJson(route('admin.purchase_orders.index'))->assertStatus(200);

        // Create/Update/Delete: Blocked
        $this->postJson(route('admin.purchase_orders.store'), [])->assertStatus(403);
    }

    /**
     * Test CRUD operations and total calculations.
     */
    public function test_crud_operations_and_totals(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        // 1. Create (Store)
        $response = $this->postJson(route('admin.purchase_orders.store'), [
            'vendor_id' => $this->activeVendor->id,
            'subtotal_amount_minor' => 10000,
            'tax_amount_minor' => 1800,
            'shipping_amount_minor' => 500,
            'discount_amount_minor' => 1000,
            'currency' => 'inr', // should be normalized to INR
        ])->assertStatus(201);

        $poId = $response->json('id');
        $publicId = $response->json('public_id');

        $this->assertNotNull($publicId);
        $this->assertStringStartsWith('PO-', $publicId);

        // subtotal (10000) + tax (1800) + shipping (500) - discount (1000) = 11300
        $this->assertEquals(11300, $response->json('total_amount_minor'));

        $this->assertDatabaseHas('vendor_orders', [
            'id' => $poId,
            'public_id' => $publicId,
            'vendor_id' => $this->activeVendor->id,
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'currency' => 'INR',
        ]);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($publicId) {
            return $event->key === 'purchase_orders.created'
                && $event->payload['public_id'] === $publicId
                && $event->payload['vendor_id'] === $this->activeVendor->id
                && $event->payload['previous_status'] === null
                && $event->payload['new_status'] === 'draft'
                && $event->payload['payment_status'] === 'unpaid'
                && $event->payload['total_amount_minor'] === 11300;
        });

        // 2. Index (List)
        $response = $this->getJson(route('admin.purchase_orders.index'))->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        // Verify eager loading
        $this->assertArrayHasKey('vendor', $response->json('data')[0]);
        $this->assertEquals($this->activeVendor->name, $response->json('data')[0]['vendor']['name']);

        // 3. Show
        $this->getJson(route('admin.purchase_orders.show', $poId))
            ->assertStatus(200)
            ->assertJsonFragment([
                'public_id' => $publicId,
            ]);

        // 4. Update
        $this->putJson(route('admin.purchase_orders.update', $poId), [
            'subtotal_amount_minor' => 12000,
            'notes' => 'Updated notes',
        ])->assertStatus(200);

        // subtotal (12000) + tax (1800) + shipping (500) - discount (1000) = 13300
        $this->assertDatabaseHas('vendor_orders', [
            'id' => $poId,
            'total_amount_minor' => 13300,
            'notes' => 'Updated notes',
        ]);

        // 5. Delete
        $this->deleteJson(route('admin.purchase_orders.destroy', $poId))->assertStatus(200);
        $this->assertDatabaseMissing('vendor_orders', ['id' => $poId]);
    }

    /**
     * Test active vendor validation.
     */
    public function test_inactive_vendor_rejection(): void
    {
        $this->actingAs($this->privilegedStaff);

        $this->postJson(route('admin.purchase_orders.store'), [
            'vendor_id' => $this->inactiveVendor->id,
            'subtotal_amount_minor' => 10000,
        ])->assertStatus(422)->assertJsonValidationErrors(['vendor_id']);
    }

    /**
     * Test invalid transitions are rejected and trigger custom exceptions.
     */
    public function test_invalid_transitions(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-TEST11',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        // draft cannot jump directly to received
        $this->withoutExceptionHandling();
        $this->expectException(InvalidPurchaseOrderStatusTransitionException::class);

        $this->putJson(route('admin.purchase_orders.update', $po->id), [
            'status' => VendorOrderStatus::RECEIVED->value,
        ]);
    }

    /**
     * Test transition timestamps are system-managed.
     */
    public function test_managed_timestamps_and_idempotence(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-TEST22',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $this->assertNull($po->ordered_at);

        // Transition to ordered
        $po->transitionStatusTo(VendorOrderStatus::ORDERED);
        $po->save();

        $orderedAt = $po->ordered_at;
        $this->assertNotNull($orderedAt);

        // Test idempotency: transitioning to same status throws exception
        try {
            $po->transitionStatusTo(VendorOrderStatus::ORDERED);
            $this->fail('Expected InvalidPurchaseOrderStatusTransitionException was not thrown.');
        } catch (InvalidPurchaseOrderStatusTransitionException $e) {
            $this->assertEquals($orderedAt->toDateTimeString(), $po->ordered_at->toDateTimeString());
        }
        Carbon::setTestNow(); // reset
    }

    /**
     * Test delete rules.
     */
    public function test_delete_forbidden_once_ordered(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-TEST33',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
        ]);

        $this->deleteJson(route('admin.purchase_orders.destroy', $po->id))->assertStatus(400);
    }

    /**
     * Test immutable fields once ordered.
     */
    public function test_immutable_fields(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-TEST44',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'subtotal_amount_minor' => 10000,
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(PurchaseOrderImmutableException::class);

        $this->putJson(route('admin.purchase_orders.update', $po->id), [
            'subtotal_amount_minor' => 12000,
        ]);
    }

    /**
     * Test expected_at chronology.
     */
    public function test_expected_at_chronology(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-TEST55',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'ordered_at' => Carbon::now(),
        ]);

        $this->withoutExceptionHandling();
        $this->expectException(InvalidPurchaseOrderExpectedDateException::class);

        $this->putJson(route('admin.purchase_orders.update', $po->id), [
            'expected_at' => Carbon::now()->subDay()->toDateTimeString(),
        ]);
    }

    /**
     * Test negative totals prevention.
     */
    public function test_negative_totals_prevention(): void
    {
        $this->actingAs($this->privilegedStaff);

        // Discount exceeds subtotal + tax + shipping (10000 + 0 + 0)
        $this->postJson(route('admin.purchase_orders.store'), [
            'vendor_id' => $this->activeVendor->id,
            'subtotal_amount_minor' => 10000,
            'discount_amount_minor' => 15000,
        ])->assertStatus(422)->assertJsonValidationErrors(['discount_amount_minor']);
    }
}
