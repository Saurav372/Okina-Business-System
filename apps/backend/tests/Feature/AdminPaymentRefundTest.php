<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Services\RefundService;
use App\Support\Finance\FinanceDashboardSummary;
use App\Support\Finance\PaymentFilters;
use App\Support\Finance\PaymentMetrics;
use App\Support\Finance\RefundFilters;
use App\Support\Finance\RefundMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminPaymentRefundTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $financeStaffUser;

    protected User $unauthorizedUser;

    protected Customer $customer;

    protected Order $order;

    protected Payment $succeededPayment;

    protected function setUp(): void
    {
        parent::setUp();

        $permPayView = Permission::query()->firstOrCreate(['slug' => 'payments.view'], [
            'name' => 'Payments View',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'View payments',
            'is_sensitive' => false,
        ]);

        $permRefReq = Permission::query()->firstOrCreate(['slug' => 'refunds.request'], [
            'name' => 'Refunds Request',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'Request refunds',
            'is_sensitive' => false,
        ]);

        $permRefApprove = Permission::query()->firstOrCreate(['slug' => 'refunds.approve'], [
            'name' => 'Refunds Approve',
            'group' => 'finance',
            'guard_name' => 'web',
            'description' => 'Approve refunds',
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
        $adminRole->permissions()->syncWithoutDetaching([$permPayView->id, $permRefReq->id, $permRefApprove->id, $permDashboard->id]);

        $financeRole = Role::query()->firstOrCreate(['slug' => 'finance_staff'], [
            'name' => 'Finance Staff',
            'guard_name' => 'web',
        ]);
        $financeRole->permissions()->syncWithoutDetaching([$permPayView->id, $permRefReq->id, $permDashboard->id]);

        $unauthRole = Role::query()->firstOrCreate(['slug' => 'guest_role'], [
            'name' => 'Guest Role',
            'guard_name' => 'web',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach($adminRole);

        $this->financeStaffUser = User::factory()->create();
        $this->financeStaffUser->roles()->attach($financeRole);

        $this->unauthorizedUser = User::factory()->create();
        $this->unauthorizedUser->roles()->attach($unauthRole);

        $this->customer = Customer::factory()->create([
            'name' => 'Acme Corporation',
            'email' => 'finance@acme.com',
        ]);

        $this->order = Order::factory()->create([
            'customer_id' => $this->customer->id,
            'public_id' => 'ORD-FINANCE-101',
            'total_amount_minor' => 100000, // ₹1,000.00
        ]);

        $this->succeededPayment = Payment::create([
            'order_id' => $this->order->id,
            'payment_type' => 'full',
            'provider' => 'razorpay',
            'method' => 'upi',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 100000, // ₹1,000.00
            'currency' => 'INR',
            'receipt_number' => 'RCPT-1001',
            'gateway_fee_minor' => 2000, // ₹20.00
            'net_amount_minor' => 98000,
            'paid_at' => now(),
        ]);
    }

    public function test_admin_and_finance_staff_can_view_payments(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.payments.index'));
        $response->assertOk();
        $response->assertSee('RCPT-1001');

        $staffResponse = $this->actingAs($this->financeStaffUser)->get(route('admin.payments.index'));
        $staffResponse->assertOk();
    }

    public function test_unauthorized_users_cannot_access_payments(): void
    {
        $response = $this->actingAs($this->unauthorizedUser)->get(route('admin.payments.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_payment_kpi_metrics_calculation(): void
    {
        $paymentMetrics = new PaymentMetrics(new PaymentFilters);
        $refundMetrics = new RefundMetrics(new RefundFilters);
        $summary = new FinanceDashboardSummary($paymentMetrics, $refundMetrics);

        $this->assertEquals(1, $summary->succeededPaymentsCount);
        $this->assertEquals(100000, $summary->grossCollectionsMinor);
        $this->assertEquals(2000, $summary->totalGatewayFeesMinor);
        $this->assertEquals(0, $summary->refundVolumeMinor);
        $this->assertEquals(98000, $summary->netRevenueMinor);
    }

    public function test_admin_can_request_refund_for_succeeded_payment(): void
    {
        $payload = [
            'payment_id' => $this->succeededPayment->id,
            'amount_minor' => 30000, // ₹300.00
            'reason_code' => 'damaged_goods',
            'reason_note' => 'Minor cosmetic damage on delivered items',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.refunds.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $this->succeededPayment->id,
            'amount_minor' => 30000,
            'reason_code' => 'damaged_goods',
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_refund_cannot_exceed_original_payment_amount(): void
    {
        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);
        $service->requestRefund(
            payment: $this->succeededPayment,
            amountMinor: 200000, // ₹2,000 (exceeds ₹1,000 payment!)
            reasonCode: 'customer_cancellation',
            actor: $this->adminUser
        );
    }

    public function test_refund_cannot_be_requested_for_failed_payment(): void
    {
        $failedPayment = Payment::create([
            'order_id' => $this->order->id,
            'payment_type' => 'full',
            'provider' => 'razorpay',
            'method' => 'upi',
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 50000,
            'currency' => 'INR',
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);
        $service->requestRefund(
            payment: $failedPayment,
            amountMinor: 10000,
            reasonCode: 'duplicate_payment',
            actor: $this->adminUser
        );
    }

    public function test_finance_manager_can_approve_and_process_refund(): void
    {
        $service = app(RefundService::class);

        $requested = $service->requestRefund(
            payment: $this->succeededPayment,
            amountMinor: 40000, // ₹400.00
            reasonCode: 'order_adjustment',
            actor: $this->adminUser
        );

        $approved = $service->approveRefund($requested, $this->adminUser);
        $this->assertEquals(Refund::STATUS_APPROVED, $approved->status);

        $processed = $service->processRefund($approved, 'rfnd_razorpay_999', $this->adminUser);
        $this->assertEquals(Refund::STATUS_SUCCEEDED, $processed->status);
        $this->assertEquals('rfnd_razorpay_999', $processed->provider_refund_id);
    }

    public function test_failed_refund_can_be_retried_once_status_is_failed(): void
    {
        $service = app(RefundService::class);

        $requested = $service->requestRefund(
            payment: $this->succeededPayment,
            amountMinor: 20000,
            reasonCode: 'pricing_correction',
            actor: $this->adminUser
        );

        $approved = $service->approveRefund($requested, $this->adminUser);
        $failed = $service->markFailed($approved, 'ERR_GATEWAY_TIMEOUT', 'Razorpay connection timeout', null, $this->adminUser);

        $this->assertEquals(Refund::STATUS_FAILED, $failed->status);

        // Retry payout
        $retried = $service->retryRefund($failed, $this->adminUser);
        $this->assertEquals(Refund::STATUS_SUCCEEDED, $retried->status);
    }

    public function test_retry_attempt_limit_exceeded_throws_exception(): void
    {
        $refund = Refund::create([
            'order_id' => $this->order->id,
            'payment_id' => $this->succeededPayment->id,
            'provider' => 'razorpay',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_FAILED,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'reason_code' => 'other',
            'metadata' => [
                'attempt_count' => 5, // Already 5 attempts!
            ],
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);
        $service->retryRefund($refund, $this->adminUser);
    }

    public function test_successful_refund_is_immutable(): void
    {
        $service = app(RefundService::class);

        $requested = $service->requestRefund($this->succeededPayment, 10000, 'other', null, $this->adminUser);
        $approved = $service->approveRefund($requested, $this->adminUser);
        $succeeded = $service->processRefund($approved, 'rfnd_immutable_1', $this->adminUser);

        $this->expectException(ValidationException::class);
        $service->cancelRefund($succeeded, $this->adminUser);
    }

    public function test_partial_refunds_accumulate_remaining_balance_correctly(): void
    {
        $service = app(RefundService::class);

        // Payment total: ₹1,000.00 (100,000 minor)
        // 1st partial refund: ₹200.00 (20,000 minor)
        $r1 = $service->requestRefund($this->succeededPayment, 20000, 'damaged_goods', null, $this->adminUser);

        // 2nd partial refund: ₹300.00 (30,000 minor)
        $r2 = $service->requestRefund($this->succeededPayment, 30000, 'pricing_correction', null, $this->adminUser);

        // Total refunded requested = ₹500.00. Remaining = ₹500.00 (50,000 minor).
        // Requesting ₹600.00 (60,000 minor) should fail!
        $this->expectException(ValidationException::class);
        $service->requestRefund($this->succeededPayment, 60000, 'other', null, $this->adminUser);
    }

    public function test_provider_refund_id_must_be_unique(): void
    {
        $service = app(RefundService::class);

        $req1 = $service->requestRefund($this->succeededPayment, 10000, 'other', null, $this->adminUser);
        $app1 = $service->approveRefund($req1, $this->adminUser);
        $service->processRefund($app1, 'rfnd_duplicate_key_x', $this->adminUser);

        $req2 = $service->requestRefund($this->succeededPayment, 10000, 'other', null, $this->adminUser);
        $app2 = $service->approveRefund($req2, $this->adminUser);

        $this->expectException(ValidationException::class);
        $service->processRefund($app2, 'rfnd_duplicate_key_x', $this->adminUser); // Duplicate key!
    }

    public function test_concurrent_refund_processing_only_succeeds_once(): void
    {
        $service = app(RefundService::class);

        $requested = $service->requestRefund($this->succeededPayment, 15000, 'order_adjustment', null, $this->adminUser);
        $approved = $service->approveRefund($requested, $this->adminUser);

        $first = $service->processRefund($approved, 'rfnd_concurrent_1', $this->adminUser);
        $this->assertEquals(Refund::STATUS_SUCCEEDED, $first->status);

        // Attempting to re-process an already succeeded refund should fail
        $this->expectException(ValidationException::class);
        $service->processRefund($first, 'rfnd_concurrent_2', $this->adminUser);
    }

    public function test_cancelling_requested_refund(): void
    {
        $service = app(RefundService::class);

        $requested = $service->requestRefund($this->succeededPayment, 10000, 'other', null, $this->adminUser);
        $cancelled = $service->cancelRefund($requested, $this->adminUser);

        $this->assertEquals(Refund::STATUS_CANCELLED, $cancelled->status);
    }

    public function test_refund_status_transitions_dispatch_audit_events(): void
    {
        Event::fake([AuditEvent::class]);

        $service = app(RefundService::class);

        $requested = $service->requestRefund($this->succeededPayment, 15000, 'customer_cancellation', null, $this->adminUser);
        Event::assertDispatched(AuditEvent::class, function ($event) {
            return $event->key === 'refund.requested';
        });

        $approved = $service->approveRefund($requested, $this->adminUser);
        Event::assertDispatched(AuditEvent::class, function ($event) {
            return $event->key === 'refund.approved';
        });

        $succeeded = $service->processRefund($approved, 'rfnd_event_test_1', $this->adminUser);
        Event::assertDispatched(AuditEvent::class, function ($event) {
            return $event->key === 'refund.succeeded';
        });
    }

    public function test_refund_kpi_metrics_calculation(): void
    {
        $service = app(RefundService::class);

        $req = $service->requestRefund($this->succeededPayment, 25000, 'other', null, $this->adminUser);
        $app = $service->approveRefund($req, $this->adminUser);
        $service->processRefund($app, 'rfnd_kpi_1', $this->adminUser);

        $metrics = new RefundMetrics(new RefundFilters);

        $this->assertEquals(1, $metrics->totalCount);
        $this->assertEquals(1, $metrics->succeededCount);
        $this->assertEquals(25000, $metrics->totalRefundedVolumeMinor);
    }
}
