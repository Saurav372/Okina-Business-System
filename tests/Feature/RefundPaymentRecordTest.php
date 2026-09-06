<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Refund;
use App\Support\Admin\OrderDetailCatalog;
use App\Support\Orders\OrderTotalsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class RefundPaymentRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_ensure_payment_is_refundable_validations(): void
    {
        $order = Order::factory()->create();

        // 1. Missing payment_id
        $refundWithoutPayment = Refund::create([
            'order_id' => $order->id,
            'payment_id' => null,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Payment ID is missing on the refund.');
        $refundWithoutPayment->ensurePaymentIsRefundable();
    }

    public function test_ensure_payment_is_refundable_not_found(): void
    {
        $order = Order::factory()->create();

        // 2. Dangling / non-existent payment reference
        $refundDanglingPayment = new Refund([
            'order_id' => $order->id,
            'payment_id' => 999999,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The associated payment record cannot be resolved.');
        $refundDanglingPayment->ensurePaymentIsRefundable();
    }

    public function test_ensure_payment_is_refundable_status_not_succeeded(): void
    {
        $order = Order::factory()->create();

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'failed',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:failed:'.$order->id,
        ]);

        $paymentFailed = Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_FAILED,
            'amount_minor' => 10000,
            'currency' => 'INR',
        ]);

        // 3. Status != succeeded
        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $paymentFailed->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The associated payment status must be succeeded.');
        $refund->ensurePaymentIsRefundable();
    }

    public function test_ensure_payment_association_is_immutable(): void
    {
        $order = Order::factory()->create();

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:immutable_test:'.$order->id,
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
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        // Same ID is fine
        $refund->ensurePaymentAssociationIsImmutable($payment->id);

        // Different ID throws
        $this->assertThrows(function () use ($refund) {
            $refund->ensurePaymentAssociationIsImmutable($refund->payment_id + 1);
        }, \LogicException::class, 'The payment association on a refund is immutable and cannot be changed.');

        // Null throws
        $this->assertThrows(function () use ($refund) {
            $refund->ensurePaymentAssociationIsImmutable(null);
        }, \LogicException::class, 'The payment association on a refund is immutable and cannot be changed.');
    }

    public function test_immutable_payment_and_multiple_refunds(): void
    {
        $order = Order::factory()->create();

        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => 10000,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:multiple_refunds:'.$order->id,
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
            'provider_order_id' => 'order_xyz',
            'gateway_fee_minor' => 200,
            'net_amount_minor' => 9800,
            'paid_at' => now(),
        ]);

        // Refresh to load all database defaults/null fields
        $payment->refresh();

        // Capture snapshot of business attributes (excluding DB/Eloquent keys/updates)
        $ignoredKeys = ['id', 'updated_at'];
        $paymentSnapshot = Arr::except($payment->getAttributes(), $ignoredKeys);

        // Create three separate refunds
        $refund1 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        $refund2 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 3000,
            'currency' => 'INR',
        ]);

        $refund3 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_REQUESTED,
            'amount_minor' => 5000,
            'currency' => 'INR',
        ]);

        // Transition each to succeeded
        foreach ([$refund1, $refund2, $refund3] as $refund) {
            $refund->status = Refund::STATUS_SUCCEEDED;
            $refund->save();
        }

        // Reload payment and assert immutable attributes match exactly
        $payment->refresh();
        $paymentPostRefundSnapshot = Arr::except($payment->getAttributes(), $ignoredKeys);

        $this->assertEquals($paymentSnapshot, $paymentPostRefundSnapshot);

        // Verify refunds relation contains exactly the three refund IDs
        $refundIds = $payment->refunds->pluck('id')->all();
        $this->assertEqualsCanonicalizing(
            [$refund1->id, $refund2->id, $refund3->id],
            $refundIds
        );
    }

    public function test_ledger_aggregates_verification(): void
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
            'idempotency_key' => 'idempotency:payment_attempt:ledger_test:'.$order->id,
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

        // Create three separate refunds: 2000, 3000, 5000
        $refund1 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 2000,
            'currency' => 'INR',
        ]);

        $refund2 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 3000,
            'currency' => 'INR',
        ]);

        $refund3 = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => 5000,
            'currency' => 'INR',
        ]);

        // 1. Verify totals using OrderTotalsCalculator
        $calculator = new OrderTotalsCalculator;
        $totals = $calculator->fromAmounts(
            subtotalAmountMinor: $order->subtotal_amount_minor,
            paidAmountMinor: $payment->amount_minor,
            refundAmountMinor: $refund1->amount_minor + $refund2->amount_minor + $refund3->amount_minor
        );

        $this->assertSame(10000, $totals->paidAmountMinor());
        $this->assertSame(10000, $totals->refundAmountMinor());
        $this->assertSame(10000, $totals->outstandingAmountMinor()); // net paid is 0, so outstanding is order total (10000)

        // 2. Verify via OrderDetailCatalog::summarize()
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

        // Assert count of records
        $this->assertCount(1, $order->payments);
        $this->assertCount(3, $order->refunds);
    }
}
