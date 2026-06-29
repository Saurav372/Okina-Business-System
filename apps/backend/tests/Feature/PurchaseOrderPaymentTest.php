<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentMethod;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PurchaseOrderPaymentTest extends TestCase
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
     * Test gated access: only purchases.manage permission allows recording vendor payments.
     */
    public function test_authorization_gating(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PAYGATE',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'total_amount_minor' => 10000,
        ]);

        // Guest gets 401
        $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 5000,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
        ])->assertStatus(401);

        // Unprivileged staff gets 403
        $this->actingAs($this->unprivilegedUser)
            ->postJson(route('admin.purchase_orders.payments.store', $po->id), [
                'amount_minor' => 5000,
                'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
            ])->assertStatus(403);

        // Privileged staff gets 200
        $this->actingAs($this->privilegedStaff)
            ->postJson(route('admin.purchase_orders.payments.store', $po->id), [
                'amount_minor' => 5000,
                'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
            ])->assertStatus(200);
    }

    /**
     * Test zero payments state when PO is created.
     */
    public function test_zero_payments_initial_state(): void
    {
        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-ZERO-PAY',
            'status' => VendorOrderStatus::DRAFT->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'total_amount_minor' => 10000,
        ]);

        $this->assertEquals(VendorOrderPaymentStatus::UNPAID, $po->payment_status);
        $this->assertEquals(0, VendorPayment::where('vendor_order_id', $po->id)->count());
    }

    /**
     * Test single and multiple partial payments, including exact remaining payment.
     */
    public function test_valid_payments_and_status_transitions(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-VALIDPAY',
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'total_amount_minor' => 1000,
            'currency' => 'USD',
        ]);

        // 1. Pay 600 USD (Partial payment)
        $response = $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 600,
            'payment_method' => VendorPaymentMethod::UPI->value,
            'reference' => 'REF-UPI-123',
            'notes' => 'Partial payment',
        ])->assertStatus(200);

        // Assert response format
        $response->assertJsonStructure([
            'payment' => ['id', 'amount_minor', 'status', 'currency', 'payment_method'],
            'purchase_order' => ['id', 'payment_status'],
        ]);

        $response->assertJsonPath('payment.amount_minor', 600)
            ->assertJsonPath('payment.currency', 'USD')
            ->assertJsonPath('payment.payment_method', 'upi')
            ->assertJsonPath('purchase_order.payment_status', 'partially_paid');

        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::PARTIALLY_PAID, $po->payment_status);

        // Assert exact AuditEvent details
        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po) {
            return $event->key === 'purchase_orders.payments.recorded'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['payment_amount_minor'] === 600
                && $event->payload['total_paid_minor'] === 600
                && $event->payload['remaining_balance_minor'] === 400
                && $event->payload['previous_payment_status'] === 'unpaid'
                && $event->payload['payment_status'] === 'partially_paid'
                && $event->payload['currency'] === 'USD'
                && $event->payload['payment_method'] === 'upi'
                && $event->payload['reference'] === 'REF-UPI-123';
        });

        // 2. Pay exact remaining balance 400 USD
        Event::fake([AuditEvent::class]);
        $response = $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 400,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
            'reference' => 'REF-BANK-456',
        ])->assertStatus(200);

        $response->assertJsonPath('purchase_order.payment_status', 'paid');

        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::PAID, $po->payment_status);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po) {
            return $event->key === 'purchase_orders.payments.recorded'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['payment_amount_minor'] === 400
                && $event->payload['total_paid_minor'] === 1000
                && $event->payload['remaining_balance_minor'] === 0
                && $event->payload['previous_payment_status'] === 'partially_paid'
                && $event->payload['payment_status'] === 'paid'
                && $event->payload['currency'] === 'USD'
                && $event->payload['payment_method'] === 'bank_transfer'
                && $event->payload['reference'] === 'REF-BANK-456';
        });
    }

    /**
     * Test duplicate reference numbers are allowed by business rules.
     */
    public function test_duplicate_reference_numbers_are_accepted(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-DUPREF',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 10000,
        ]);

        // First payment with REF-ABC
        $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 3000,
            'payment_method' => VendorPaymentMethod::CASH->value,
            'reference' => 'REF-ABC',
        ])->assertStatus(200);

        // Second payment with same REF-ABC
        $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 2000,
            'payment_method' => VendorPaymentMethod::CASH->value,
            'reference' => 'REF-ABC',
        ])->assertStatus(200);

        // Verify database has 2 payments recorded
        $this->assertEquals(2, VendorPayment::where('vendor_order_id', $po->id)->count());
    }

    /**
     * Test that overpaying the PO returns 422.
     */
    public function test_overpayment_rejected(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-OVERPAY',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 1000,
        ]);

        // Attempting to pay 1001 (exceeding 1000) must return 422
        $response = $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 1001,
            'payment_method' => VendorPaymentMethod::CHEQUE->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['amount_minor']);

        // Verify database and status remains unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::UNPAID, $po->payment_status);
        $this->assertEquals(0, VendorPayment::where('vendor_order_id', $po->id)->count());

        Event::assertNotDispatched(AuditEvent::class);
    }

    /**
     * Test zero remaining balance regression.
     */
    public function test_zero_remaining_balance_regression(): void
    {
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-ZEROREG',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 1000,
        ]);

        // Pay full amount (1000)
        $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 1000,
            'payment_method' => VendorPaymentMethod::CASH->value,
        ])->assertStatus(200);

        // Attempt to pay 1 extra unit (should fail with 422 since remaining is 0)
        Event::fake([AuditEvent::class]);
        $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
            'amount_minor' => 1,
            'payment_method' => VendorPaymentMethod::CASH->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['amount_minor']);

        // Assert database properties remain unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::PAID, $po->payment_status);
        $this->assertEquals(1, VendorPayment::where('vendor_order_id', $po->id)->count());

        Event::assertNotDispatched(AuditEvent::class);
    }

    /**
     * Test that recording payment on draft or cancelled POs fails with 422.
     */
    public function test_payment_fails_on_non_payable_po_status(): void
    {
        $this->actingAs($this->privilegedStaff);

        // 1. Draft PO
        $poDraft = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PAYDRAFT',
            'status' => VendorOrderStatus::DRAFT->value,
            'total_amount_minor' => 5000,
        ]);

        $this->postJson(route('admin.purchase_orders.payments.store', $poDraft->id), [
            'amount_minor' => 2000,
            'payment_method' => VendorPaymentMethod::CASH->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['purchase_order']);

        // 2. Cancelled PO
        $poCancelled = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PAYCANCEL',
            'status' => VendorOrderStatus::CANCELLED->value,
            'total_amount_minor' => 5000,
        ]);

        $this->postJson(route('admin.purchase_orders.payments.store', $poCancelled->id), [
            'amount_minor' => 2000,
            'payment_method' => VendorPaymentMethod::CASH->value,
        ])->assertStatus(422)->assertJsonValidationErrors(['purchase_order']);
    }

    /**
     * Test transaction rollback on audit or database failure.
     */
    public function test_rollback_on_database_exception(): void
    {
        Event::fake([AuditEvent::class]);
        $this->actingAs($this->privilegedStaff);

        $po = VendorOrder::create([
            'vendor_id' => $this->activeVendor->id,
            'public_id' => 'PO-PAYROLLBACK',
            'status' => VendorOrderStatus::ORDERED->value,
            'total_amount_minor' => 5000,
        ]);

        // Force exception inside transaction by registering saving listener on VendorPayment
        VendorPayment::saving(function ($model) {
            throw new \RuntimeException('Forced payment saving failure.');
        });

        $this->withoutExceptionHandling();

        try {
            $this->postJson(route('admin.purchase_orders.payments.store', $po->id), [
                'amount_minor' => 2000,
                'payment_method' => VendorPaymentMethod::CASH->value,
            ]);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Forced payment saving failure.', $e->getMessage());
        }

        // Clean up saving listener
        VendorPayment::flushEventListeners();

        // Assert database values remain unchanged
        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::UNPAID, $po->payment_status);
        $this->assertEquals(0, VendorPayment::where('vendor_order_id', $po->id)->count());

        // Verify no audit event was dispatched
        Event::assertNotDispatched(AuditEvent::class);
    }
}
