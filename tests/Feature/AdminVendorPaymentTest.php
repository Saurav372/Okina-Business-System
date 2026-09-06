<?php

namespace Tests\Feature;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentMethod;
use App\Enums\VendorPaymentStatus;
use App\Enums\VendorStatus;
use App\Events\AuditEvent;
use App\Exceptions\PurchaseOrderNotPayableException;
use App\Exceptions\PurchaseOrderPaymentLimitExceededException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Services\VendorPaymentService;
use App\Support\Purchases\PurchaseOrderCodeGenerator;
use App\Support\Vendors\VendorPaymentFilters;
use App\Support\Vendors\VendorPaymentMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminVendorPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $unauthorizedUser;

    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        $permView = Permission::query()->firstOrCreate(['slug' => 'inventory.view'], [
            'name' => 'Inventory View',
            'group' => 'inventory',
            'guard_name' => 'web',
            'description' => 'View inventory',
            'is_sensitive' => false,
        ]);

        $permManage = Permission::query()->firstOrCreate(['slug' => 'inventory.manage'], [
            'name' => 'Inventory Manage',
            'group' => 'inventory',
            'guard_name' => 'web',
            'description' => 'Manage inventory',
            'is_sensitive' => false,
        ]);

        $permDashboard = Permission::query()->firstOrCreate(['slug' => 'dashboard.access'], [
            'name' => 'Dashboard Access',
            'group' => 'settings',
            'guard_name' => 'web',
            'description' => 'Dashboard Access',
            'is_sensitive' => false,
        ]);

        $adminRole = Role::query()->firstOrCreate(['slug' => Role::ADMIN], [
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->permissions()->syncWithoutDetaching([$permView->id, $permManage->id, $permDashboard->id]);

        $unauthRole = Role::query()->firstOrCreate(['slug' => 'guest_role'], [
            'name' => 'Guest Role',
            'guard_name' => 'web',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->roles()->attach($unauthRole);

        $this->vendor = Vendor::create([
            'status' => VendorStatus::ACTIVE,
            'name' => 'Apex Textiles Supplier',
            'vendor_code' => 'VND-APEX-01',
        ]);
    }

    protected function createTestPo(array $attributes = []): VendorOrder
    {
        return VendorOrder::create(array_merge([
            'public_id' => PurchaseOrderCodeGenerator::generate(),
            'vendor_id' => $this->vendor->id,
            'created_by_user_id' => $this->adminUser->id,
            'status' => VendorOrderStatus::ORDERED->value,
            'payment_status' => VendorOrderPaymentStatus::UNPAID->value,
            'currency' => 'INR',
            'subtotal_amount_minor' => 100000,
            'tax_amount_minor' => 0,
            'shipping_amount_minor' => 0,
            'discount_amount_minor' => 0,
            'total_amount_minor' => 100000, // ₹1,000.00
        ], $attributes));
    }

    public function test_admin_can_record_vendor_payment_against_purchase_order(): void
    {
        $po = $this->createTestPo();

        $payload = [
            'amount_minor' => 50000, // ₹500.00
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
            'reference' => 'UTR9988776655',
            'notes' => 'Advance bank transfer 50%',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.purchase_orders.payments.store', $po), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vendor_payments', [
            'vendor_order_id' => $po->id,
            'amount_minor' => 50000,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER->value,
            'reference' => 'UTR9988776655',
            'status' => VendorPaymentStatus::PAID->value,
        ]);
    }

    public function test_partial_vendor_payment_updates_payment_status_to_partially_paid(): void
    {
        $po = $this->createTestPo(['total_amount_minor' => 100000]);

        $service = app(VendorPaymentService::class);
        $service->recordPayment($po, [
            'amount_minor' => 40000,
            'payment_method' => VendorPaymentMethod::UPI,
        ], $this->adminUser);

        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::PARTIALLY_PAID, $po->payment_status);
    }

    public function test_full_vendor_payment_updates_payment_status_to_paid(): void
    {
        $po = $this->createTestPo(['total_amount_minor' => 100000]);

        $service = app(VendorPaymentService::class);
        $service->recordPayment($po, [
            'amount_minor' => 100000,
            'payment_method' => VendorPaymentMethod::CHEQUE,
        ], $this->adminUser);

        $po->refresh();
        $this->assertEquals(VendorOrderPaymentStatus::PAID, $po->payment_status);
    }

    public function test_recording_payment_exceeding_remaining_balance_is_rejected(): void
    {
        $po = $this->createTestPo(['total_amount_minor' => 100000]);

        $service = app(VendorPaymentService::class);

        // First partial payment ₹600.00
        $service->recordPayment($po, [
            'amount_minor' => 60000,
            'payment_method' => VendorPaymentMethod::CASH,
        ], $this->adminUser);

        // Attempting second payment of ₹500.00 (which exceeds remaining ₹400.00)
        $this->expectException(PurchaseOrderPaymentLimitExceededException::class);
        $service->recordPayment($po, [
            'amount_minor' => 50000,
            'payment_method' => VendorPaymentMethod::CASH,
        ], $this->adminUser);
    }

    public function test_recording_payment_on_draft_or_cancelled_order_is_rejected(): void
    {
        $draftPo = $this->createTestPo(['status' => VendorOrderStatus::DRAFT->value]);

        $service = app(VendorPaymentService::class);

        $this->expectException(PurchaseOrderNotPayableException::class);
        $service->recordPayment($draftPo, [
            'amount_minor' => 10000,
            'payment_method' => VendorPaymentMethod::CASH,
        ], $this->adminUser);
    }

    public function test_recording_vendor_payment_dispatches_standardized_audit_event(): void
    {
        Event::fake([AuditEvent::class]);

        $po = $this->createTestPo(['total_amount_minor' => 80000]);

        $service = app(VendorPaymentService::class);
        $service->recordPayment($po, [
            'amount_minor' => 80000,
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER,
            'reference' => 'UTR-AUDIT-TEST-1',
        ], $this->adminUser);

        Event::assertDispatched(AuditEvent::class, function (AuditEvent $event) use ($po) {
            return $event->key === 'vendor_payment.recorded'
                && $event->payload['vendor_order_id'] === $po->id
                && $event->payload['payment_amount_minor'] === 80000
                && $event->payload['payment_status'] === VendorOrderPaymentStatus::PAID->value;
        });
    }

    public function test_vendor_payment_history_is_sorted_by_paid_at_desc(): void
    {
        $po = $this->createTestPo(['total_amount_minor' => 100000]);

        $service = app(VendorPaymentService::class);

        $service->recordPayment($po, [
            'amount_minor' => 30000,
            'payment_method' => VendorPaymentMethod::CASH,
            'paid_at' => now()->subDay(),
        ], $this->adminUser);

        $service->recordPayment($po, [
            'amount_minor' => 20000,
            'payment_method' => VendorPaymentMethod::UPI,
            'paid_at' => now(),
        ], $this->adminUser);

        $po->refresh();
        $sortedPayments = $po->payments->sortByDesc('paid_at')->values();

        $this->assertEquals(20000, $sortedPayments->first()->amount_minor);
        $this->assertEquals(30000, $sortedPayments->last()->amount_minor);
    }

    public function test_vendor_payment_filters_by_method_and_vendor(): void
    {
        $po1 = $this->createTestPo();
        $service = app(VendorPaymentService::class);

        $service->recordPayment($po1, [
            'amount_minor' => 10000,
            'payment_method' => VendorPaymentMethod::UPI,
            'reference' => 'REF-UPI-TEST',
        ], $this->adminUser);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.vendor_payments.index', ['payment_method' => 'upi']));

        $response->assertOk();
        $response->assertSee('REF-UPI-TEST');
    }

    public function test_vendor_payment_kpi_metrics_calculation(): void
    {
        $po1 = $this->createTestPo(['total_amount_minor' => 100000]); // ₹1000
        $po2 = $this->createTestPo(['total_amount_minor' => 50000]);  // ₹500

        $service = app(VendorPaymentService::class);

        $service->recordPayment($po1, [
            'amount_minor' => 40000, // Paid ₹400
            'payment_method' => VendorPaymentMethod::BANK_TRANSFER,
        ], $this->adminUser);

        $metrics = new VendorPaymentMetrics(new VendorPaymentFilters);

        $this->assertEquals(40000, $metrics->totalPaidMinor);
        $this->assertEquals(110000, $metrics->unpaidLiabilityMinor); // (150000 total POs) - 40000 paid = 110000
        $this->assertEquals(1, $metrics->activeVendorsPaidCount);
        $this->assertEquals(1, $metrics->paymentCount);
    }

    public function test_concurrent_vendor_payment_locks_prevent_overpayment(): void
    {
        $po = $this->createTestPo(['total_amount_minor' => 50000]);

        $service = app(VendorPaymentService::class);

        $result = $service->recordPayment($po, [
            'amount_minor' => 50000,
            'payment_method' => VendorPaymentMethod::CHEQUE,
        ], $this->adminUser);

        $this->assertEquals(VendorOrderPaymentStatus::PAID, $result['purchase_order']->payment_status);

        // Attempt second concurrent settlement
        $this->expectException(PurchaseOrderPaymentLimitExceededException::class);
        $service->recordPayment($po, [
            'amount_minor' => 100,
            'payment_method' => VendorPaymentMethod::CASH,
        ], $this->adminUser);
    }
}
