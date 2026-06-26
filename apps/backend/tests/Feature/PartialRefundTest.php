<?php

namespace Tests\Feature;

use App\Events\AuditEvent;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\Role;
use App\Models\User;
use App\Support\Admin\OrderDetailCatalog;
use App\Support\Orders\OrderTotalsCalculator;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PartialRefundTest extends TestCase
{
    use RefreshDatabase;

    private User $financeStaff;
    private User $salesStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);

        $this->financeStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->financeStaff->assignRole(Role::FINANCE_STAFF);

        $this->salesStaff = User::factory()->create([
            'user_type' => User::TYPE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->salesStaff->assignRole(Role::SALES_STAFF);
    }

    public static function validTransitionsProvider(): array
    {
        return [
            [Refund::STATUS_REQUESTED, Refund::STATUS_APPROVED],
            [Refund::STATUS_REQUESTED, Refund::STATUS_CANCELLED],
            [Refund::STATUS_APPROVED, Refund::STATUS_PROCESSING],
            [Refund::STATUS_APPROVED, Refund::STATUS_CANCELLED],
            [Refund::STATUS_PROCESSING, Refund::STATUS_SUCCEEDED],
            [Refund::STATUS_PROCESSING, Refund::STATUS_FAILED],
            [Refund::STATUS_FAILED, Refund::STATUS_PROCESSING],
        ];
    }

    public static function invalidTransitionsProvider(): array
    {
        return [
            [Refund::STATUS_REQUESTED, Refund::STATUS_SUCCEEDED],
            [Refund::STATUS_REQUESTED, Refund::STATUS_FAILED],
            [Refund::STATUS_CANCELLED, Refund::STATUS_PROCESSING],
            [Refund::STATUS_CANCELLED, Refund::STATUS_SUCCEEDED],
            [Refund::STATUS_SUCCEEDED, Refund::STATUS_PROCESSING],
            [Refund::STATUS_SUCCEEDED, Refund::STATUS_CANCELLED],
            [Refund::STATUS_FAILED, Refund::STATUS_CANCELLED],
            [Refund::STATUS_APPROVED, Refund::STATUS_APPROVED],
            [Refund::STATUS_PROCESSING, Refund::STATUS_PROCESSING],
        ];
    }

    #[DataProvider('validTransitionsProvider')]
    public function test_valid_transitions(string $from, string $to): void
    {
        $refund = new Refund();
        $refund->status = $from;
        $this->assertTrue($refund->canTransitionTo($to));
    }

    #[DataProvider('invalidTransitionsProvider')]
    public function test_invalid_transitions(string $from, string $to): void
    {
        $refund = new Refund();
        $refund->status = $from;
        $this->assertFalse($refund->canTransitionTo($to));

        $expectedMessage = Refund::TRANSITION_ERRORS[$to] ?? "Invalid status transition to {$to}";

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($expectedMessage);

        $refund->ensureCanTransitionTo($to);
    }

    public function test_transition_map_integrity_assertions(): void
    {
        $definedConstants = [
            Refund::STATUS_REQUESTED,
            Refund::STATUS_APPROVED,
            Refund::STATUS_PROCESSING,
            Refund::STATUS_SUCCEEDED,
            Refund::STATUS_FAILED,
            Refund::STATUS_CANCELLED,
        ];

        // 1. Every target status in ALLOWED_TRANSITIONS exists as a defined status constant
        // 2. ALLOWED_TRANSITIONS contains no duplicate targets for the same source
        foreach (Refund::ALLOWED_TRANSITIONS as $source => $targets) {
            $this->assertContains($source, $definedConstants);
            $this->assertSame(count($targets), count(array_unique($targets)));
            foreach ($targets as $target) {
                $this->assertContains($target, $definedConstants);
            }
        }

        // 3. Every target status in ALLOWED_TRANSITIONS maps to a message in TRANSITION_ERRORS
        // 4. Every key in TRANSITION_ERRORS is reachable as a transition target
        $allReachableTargets = [];
        foreach (Refund::ALLOWED_TRANSITIONS as $source => $targets) {
            foreach ($targets as $target) {
                $allReachableTargets[] = $target;
                $this->assertArrayHasKey($target, Refund::TRANSITION_ERRORS);
            }
        }
        $allReachableTargets = array_unique($allReachableTargets);

        foreach (Refund::TRANSITION_ERRORS as $targetKey => $msg) {
            $this->assertContains($targetKey, $allReachableTargets);
        }
    }

    public function test_unauthorized_user_cannot_access_process_and_cancel(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_APPROVED,
            'amount_minor' => 4000,
            'currency' => 'INR',
        ]);

        $responseProcess = $this->actingAs($this->salesStaff)
            ->postJson(route('admin.refunds.process', $refund->id));
        $responseProcess->assertStatus(403);

        $responseCancel = $this->actingAs($this->salesStaff)
            ->postJson(route('admin.refunds.cancel', $refund->id));
        $responseCancel->assertStatus(403);
    }

    public function test_authorized_user_can_process_and_cancel(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create(['public_id' => 'ORD-101']);
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // Test cancel
        $refundToCancel = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'reason_code' => 'reason',
            'reason_note' => 'note',
            'provider_refund_id' => 'cf_ref_1',
            'provider_payment_id' => 'cf_pay_1',
            'provider_reference' => 'ref_1',
        ]);

        $responseCancel = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.cancel', $refundToCancel->id));
        $responseCancel->assertStatus(200);

        $refundToCancel->refresh();
        $this->assertSame(Refund::STATUS_CANCELLED, $refundToCancel->status);
        // Assert preservation of cancel metadata
        $this->assertSame('reason', $refundToCancel->reason_code);
        $this->assertSame('note', $refundToCancel->reason_note);
        $this->assertSame('cf_ref_1', $refundToCancel->provider_refund_id);
        $this->assertSame('cf_pay_1', $refundToCancel->provider_payment_id);
        $this->assertSame('ref_1', $refundToCancel->provider_reference);

        Event::assertDispatched(AuditEvent::class, function ($event) use ($refundToCancel, $order, $payment) {
            return $event->key === 'refunds.refund_cancelled' &&
                $event->payload['refund_public_id'] === $refundToCancel->id &&
                $event->payload['payment_public_id'] === $payment->id &&
                $event->payload['order_public_id'] === $order->public_id &&
                $event->payload['old_status'] === Refund::STATUS_REQUESTED &&
                $event->payload['new_status'] === Refund::STATUS_CANCELLED &&
                $event->payload['actor_type'] === 'user' &&
                $event->payload['actor_id'] === $this->financeStaff->id;
        });

        // Test process
        $refundToProcess = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_APPROVED,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'approved_by_user_id' => $this->financeStaff->id,
            'approved_at' => now()->subHour(),
        ]);

        $responseProcess = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.process', $refundToProcess->id));
        $responseProcess->assertStatus(200);

        $refundToProcess->refresh();
        $this->assertSame(Refund::STATUS_PROCESSING, $refundToProcess->status);
        $this->assertSame($this->financeStaff->id, $refundToProcess->processed_by_user_id);
        $this->assertNotNull($refundToProcess->processed_at);

        Event::assertDispatched(AuditEvent::class, function ($event) use ($refundToProcess, $order, $payment) {
            return $event->key === 'refunds.refund_processing_started' &&
                $event->payload['refund_public_id'] === $refundToProcess->id &&
                $event->payload['payment_public_id'] === $payment->id &&
                $event->payload['order_public_id'] === $order->public_id &&
                $event->payload['old_status'] === Refund::STATUS_APPROVED &&
                $event->payload['new_status'] === Refund::STATUS_PROCESSING &&
                $event->payload['actor_type'] === 'user' &&
                $event->payload['actor_id'] === $this->financeStaff->id;
        });
    }

    public function test_retry_preserves_immutable_history(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $requestedUser = User::factory()->create();
        $approvedUser = User::factory()->create();
        $processedUser = User::factory()->create();

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_FAILED,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'requested_by_user_id' => $requestedUser->id,
            'requested_at' => now()->subDays(2),
            'approved_by_user_id' => $approvedUser->id,
            'approved_at' => now()->subDays(1),
            'processed_by_user_id' => $processedUser->id,
            'processed_at' => now()->subHours(12),
            'reason_code' => 'GATEWAY_ERROR',
            'reason_note' => 'Original error note',
        ]);

        // Transition back from failed to processing (retry)
        $response = $this->actingAs($this->financeStaff)
            ->postJson(route('admin.refunds.process', $refund->id));
        $response->assertStatus(200);

        $refund->refresh();
        $this->assertSame(Refund::STATUS_PROCESSING, $refund->status);

        // Verification of cleared transient reasons
        $this->assertNull($refund->reason_code);
        $this->assertNull($refund->reason_note);

        // Verify re-assigned processed_by_user_id
        $this->assertSame($this->financeStaff->id, $refund->processed_by_user_id);

        // Verify that original approval and request metadata never change
        $this->assertSame($requestedUser->id, $refund->requested_by_user_id);
        $this->assertSame($approvedUser->id, $refund->approved_by_user_id);

        Event::assertDispatched(AuditEvent::class, function ($event) use ($refund, $payment) {
            return $event->key === 'refunds.refund_processing_started' &&
                $event->payload['refund_public_id'] === $refund->id &&
                $event->payload['payment_public_id'] === $payment->id &&
                $event->payload['old_status'] === Refund::STATUS_FAILED &&
                $event->payload['new_status'] === Refund::STATUS_PROCESSING;
        });
    }

    public function test_webhook_idempotency_returns_successful_noop(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'gateway_payment_id' => 'cf_pay_888',
            'gateway_order_id' => 'order_xyz',
            'initiated_at' => now(),
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_888',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'provider_refund_id' => 'cf_ref_777',
            'provider_payment_id' => 'cf_pay_888',
        ]);

        $webhookPayload = [
            'provider' => 'cashfree',
            'event_id' => 'evt_duplicate_succeed',
            'event_type' => 'refund.succeeded',
            'order_id' => 'order_xyz',
            'payment_id' => 'cf_pay_888',
            'refund_id' => 'cf_ref_777',
            'amount_minor' => 4000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => now()->toIso8601String(),
        ];

        // Store current updated_at timestamp
        $originalUpdatedAt = $refund->updated_at;

        // Post duplicate webhook
        $response = $this->withHeader('X-Signature', $this->signatureFor($webhookPayload))
            ->postJson('/api/webhooks/payments/cashfree', $webhookPayload);

        $response->assertOk();

        // Assert no observable business state changes occur (updated_at remains unchanged)
        $refund->refresh();
        $this->assertSame(Refund::STATUS_SUCCEEDED, $refund->status);
        $this->assertEquals($originalUpdatedAt, $refund->updated_at);

        // Assert no audit events were dispatched
        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_webhook_race_conditions_handle_properly(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'gateway_payment_id' => 'cf_pay_race_1',
            'gateway_order_id' => 'order_xyz',
            'initiated_at' => now(),
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_race_1',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_PROCESSING,
            'amount_minor' => 4000,
            'currency' => 'INR',
            'provider_refund_id' => 'cf_ref_race_1',
            'provider_payment_id' => 'cf_pay_race_1',
        ]);

        $webhookPayload = [
            'provider' => 'cashfree',
            'event_id' => 'evt_race_success',
            'event_type' => 'refund.succeeded',
            'order_id' => 'order_xyz',
            'payment_id' => 'cf_pay_race_1',
            'refund_id' => 'cf_ref_race_1',
            'amount_minor' => 4000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => now()->toIso8601String(),
        ];

        // Deliver A
        $responseA = $this->withHeader('X-Signature', $this->signatureFor($webhookPayload))
            ->postJson('/api/webhooks/payments/cashfree', $webhookPayload);
        $responseA->assertOk();

        // Deliver B concurrently (simulate)
        $responseB = $this->withHeader('X-Signature', $this->signatureFor($webhookPayload))
            ->postJson('/api/webhooks/payments/cashfree', $webhookPayload);
        $responseB->assertOk();

        // Only one audit event should be dispatched
        Event::assertDispatchedTimes(AuditEvent::class, 1);
    }

    public function test_audit_transaction_rollback_test(): void
    {
        Event::fake([AuditEvent::class]);

        $order = Order::factory()->create();
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'gateway_payment_id' => 'cf_pay_race_1',
            'gateway_order_id' => 'order_xyz',
            'initiated_at' => now(),
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_APPROVED,
            'amount_minor' => 4000,
            'currency' => 'INR',
        ]);

        Refund::saving(function ($model) {
            if ($model->status === Refund::STATUS_PROCESSING && isset($model->metadata['trigger_test_rollback']) && $model->metadata['trigger_test_rollback'] === true) {
                throw new \RuntimeException('Intentionally trigger rollback');
            }
        });

        $this->actingAs($this->financeStaff);

        try {
            DB::transaction(function () use ($refund) {
                $lockedRefund = Refund::query()->lockForUpdate()->findOrFail($refund->id);
                $lockedRefund->markProcessing($this->financeStaff);
                $lockedRefund->metadata = ['trigger_test_rollback' => true];
                $lockedRefund->save();
            });
            $this->fail('Should have thrown RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('Intentionally trigger rollback', $e->getMessage());
        }

        // Assert database rollback occurred
        $refund->refresh();
        $this->assertSame(Refund::STATUS_APPROVED, $refund->status);

        // Assert zero audit events dispatched
        Event::assertNotDispatched(AuditEvent::class);
    }

    public function test_partial_refund_recalculation_and_presentation_integrity(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
            'subtotal_amount_minor' => 10000,
            'currency' => 'INR',
            'public_id' => 'ORD-12345',
        ]);

        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create(['product_id' => $product->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'sku_code_snapshot' => 'SKU-CODE',
            'unit_price_minor' => 10000,
            'line_subtotal_minor' => 10000,
            'line_total_minor' => 10000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => ['slug' => 'product-slug', 'name' => 'Product Name'],
                'sku_code' => 'SKU-CODE',
                'selected_options_snapshot' => [],
                'print_method' => 'screen',
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
                'files' => [],
                'customer_note' => 'Note',
            ],
        ]);

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'gateway_payment_id' => 'cf_pay_9999',
            'gateway_order_id' => 'order_xyz',
            'initiated_at' => now(),
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_9999',
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_PROCESSING,
            'amount_minor' => 3000,
            'currency' => 'INR',
            'provider_refund_id' => 'cf_ref_9999',
            'provider_payment_id' => 'cf_pay_9999',
        ]);

        // Transition to succeed via webhook
        $webhookPayload = [
            'provider' => 'cashfree',
            'event_id' => 'evt_success_recalc',
            'event_type' => 'refund.succeeded',
            'order_id' => 'order_xyz',
            'payment_id' => 'cf_pay_9999',
            'refund_id' => 'cf_ref_9999',
            'amount_minor' => 3000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => now()->toIso8601String(),
        ];

        $this->withHeader('X-Signature', $this->signatureFor($webhookPayload))
            ->postJson('/api/webhooks/payments/cashfree', $webhookPayload)
            ->assertOk();

        // 1. Check at the domain calculator level
        $calculator = new OrderTotalsCalculator();
        $totals = $calculator->fromAmounts(
            subtotalAmountMinor: $order->subtotal_amount_minor,
            paidAmountMinor: 10000,
            refundAmountMinor: 3000
        );
        $this->assertSame(10000, $totals->paidAmountMinor());
        $this->assertSame(3000, $totals->refundAmountMinor());

        // 2. Check via OrderDetailCatalog::summarize()
        $order->refresh();
        $order->load([
            'items',
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
        ]);
        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $this->assertSame(10000, $summary['amounts']['paid_amount_minor']);
        $this->assertSame(3000, $summary['amounts']['refunded_amount_minor']);

        // 3. Check via Order Detail View summary data
        $response = $this->actingAs($this->financeStaff)
            ->get(route('admin.orders.detail', $order->public_id))
            ->assertOk();
        $viewSummary = $response->viewData('summary');
        $this->assertSame(10000, $viewSummary['amounts']['paid_amount_minor']);
        $this->assertSame(3000, $viewSummary['amounts']['refunded_amount_minor']);
    }

    public function test_full_refund_recalculation_and_presentation_integrity(): void
    {
        $order = Order::factory()->create([
            'total_amount_minor' => 10000,
            'subtotal_amount_minor' => 10000,
            'currency' => 'INR',
            'public_id' => 'ORD-12345',
        ]);

        $product = Product::factory()->create();
        $sku = ProductSku::factory()->create(['product_id' => $product->id]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'sku_id' => $sku->id,
            'quantity' => 1,
            'product_name_snapshot' => 'Product Name',
            'product_slug_snapshot' => 'product-slug',
            'sku_code_snapshot' => 'SKU-CODE',
            'unit_price_minor' => 10000,
            'line_subtotal_minor' => 10000,
            'line_total_minor' => 10000,
            'currency' => 'INR',
            'price_source' => 'order_snapshot',
            'customization_fingerprint' => 'FINGERPRINT',
            'customization_snapshot' => [
                'schema_version' => 1,
                'product' => ['slug' => 'product-slug', 'name' => 'Product Name'],
                'sku_code' => 'SKU-CODE',
                'selected_options_snapshot' => [],
                'print_method' => 'screen',
                'placement' => ['x' => 50, 'y' => 50, 'scale' => 1.0, 'rotation' => 0],
                'files' => [],
                'customer_note' => 'Note',
            ],
        ]);

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'gateway_payment_id' => 'cf_pay_9999',
            'gateway_order_id' => 'order_xyz',
            'initiated_at' => now(),
            'idempotency_key' => 'idempotency:payment_attempt:'.$order->public_id,
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_9999',
        ]);

        // A full refund matches the net paid amount (10000)
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_FULL,
            'status' => Refund::STATUS_PROCESSING,
            'amount_minor' => 10000,
            'currency' => 'INR',
            'provider_refund_id' => 'cf_ref_9999',
            'provider_payment_id' => 'cf_pay_9999',
        ]);

        // Transition to succeed via webhook
        $webhookPayload = [
            'provider' => 'cashfree',
            'event_id' => 'evt_success_recalc',
            'event_type' => 'refund.succeeded',
            'order_id' => 'order_xyz',
            'payment_id' => 'cf_pay_9999',
            'refund_id' => 'cf_ref_9999',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'status' => 'succeeded',
            'received_at' => now()->toIso8601String(),
        ];

        $this->withHeader('X-Signature', $this->signatureFor($webhookPayload))
            ->postJson('/api/webhooks/payments/cashfree', $webhookPayload)
            ->assertOk();

        // 1. Check at the domain calculator level
        $calculator = new OrderTotalsCalculator();
        $totals = $calculator->fromAmounts(
            subtotalAmountMinor: $order->subtotal_amount_minor,
            paidAmountMinor: 10000,
            refundAmountMinor: 10000
        );
        $this->assertSame(10000, $totals->paidAmountMinor());
        $this->assertSame(10000, $totals->refundAmountMinor());
        $this->assertSame(10000, $totals->outstandingAmountMinor()); // total (10000) - net paid (0) = 10000

        // 2. Check via OrderDetailCatalog::summarize()
        $order->refresh();
        $order->load([
            'items',
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
        ]);
        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $this->assertSame(10000, $summary['amounts']['paid_amount_minor']);
        $this->assertSame(10000, $summary['amounts']['refunded_amount_minor']);
        $this->assertSame(10000, $summary['amounts']['outstanding_balance_minor']);

        // 3. Check via Order Detail View summary data
        $response = $this->actingAs($this->financeStaff)
            ->get(route('admin.orders.detail', $order->public_id))
            ->assertOk();
        $viewSummary = $response->viewData('summary');
        $this->assertSame(10000, $viewSummary['amounts']['paid_amount_minor']);
        $this->assertSame(10000, $viewSummary['amounts']['refunded_amount_minor']);
        $this->assertSame(10000, $viewSummary['amounts']['outstanding_balance_minor']);
    }

    private function signatureFor(array $payload): string
    {
        $parts = [
            (string) ($payload['provider'] ?? ''),
            (string) ($payload['event_id'] ?? ''),
            (string) ($payload['event_type'] ?? ''),
            (string) ($payload['order_id'] ?? ''),
            (string) ($payload['payment_id'] ?? ''),
            (string) ($payload['refund_id'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['amount_minor'] ?? ''),
            (string) ($payload['currency'] ?? ''),
        ];

        return hash_hmac('sha256', implode('|', $parts), (string) config('app.key'));
    }
}
