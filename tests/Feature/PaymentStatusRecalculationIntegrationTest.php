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
use App\Support\Payments\PaymentStateRecalculationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusRecalculationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithItem(int $totalAmountMinor): Order
    {
        $order = Order::factory()->create([
            'total_amount_minor' => $totalAmountMinor,
            'subtotal_amount_minor' => $totalAmountMinor,
            'currency' => 'INR',
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
            'unit_price_minor' => $totalAmountMinor,
            'line_subtotal_minor' => $totalAmountMinor,
            'line_total_minor' => $totalAmountMinor,
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

        return $order;
    }

    private function addPayment(Order $order, int $amountMinor): Payment
    {
        $attempt = PaymentAttempt::create([
            'order_id' => $order->id,
            'provider' => 'cashfree',
            'attempt_type' => 'website_checkout',
            'status' => 'succeeded',
            'amount_minor' => $amountMinor,
            'currency' => 'INR',
            'idempotency_key' => 'idempotency:payment_attempt:'.uniqid(),
        ]);

        return Payment::create([
            'order_id' => $order->id,
            'payment_attempt_id' => $attempt->id,
            'payment_type' => Payment::TYPE_FULL,
            'provider' => 'cashfree',
            'status' => Payment::STATUS_SUCCEEDED,
            'amount_minor' => $amountMinor,
            'currency' => 'INR',
        ]);
    }

    private function addRefund(Order $order, Payment $payment, int $amountMinor): Refund
    {
        return Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'provider' => 'cashfree',
            'refund_type' => Refund::TYPE_PARTIAL,
            'status' => Refund::STATUS_SUCCEEDED,
            'amount_minor' => $amountMinor,
            'currency' => 'INR',
        ]);
    }

    private function assertOrderPaymentStatus(Order $order, string $expectedStatus): void
    {
        $order->refresh();
        $order->load([
            'items',
            'paymentAttempts',
            'payments.paymentAttempt',
            'refunds.payment.paymentAttempt',
        ]);

        // 1. Verify via OrderDetailCatalog
        $summary = app(OrderDetailCatalog::class)->summarize($order);
        $paidTotal = (int) $order->payments->where('status', 'succeeded')->sum('amount_minor');
        $refundTotal = (int) $order->refunds->where('status', 'succeeded')->sum('amount_minor');
        $calculatedStatus = app(PaymentStateRecalculationRules::class)->calculate(
            $order->total_amount_minor,
            $paidTotal,
            $refundTotal
        );

        $this->assertSame($expectedStatus, $calculatedStatus);
        $this->assertSame($paidTotal, $summary['amounts']['paid_amount_minor']);
        $this->assertSame($refundTotal, $summary['amounts']['refunded_amount_minor']);
    }

    public function test_payment_status_unpaid(): void
    {
        $order = $this->createOrderWithItem(10000);
        $this->assertOrderPaymentStatus($order, 'unpaid');
    }

    public function test_payment_status_partially_paid(): void
    {
        $order = $this->createOrderWithItem(10000);
        $this->addPayment($order, 4000);
        $this->assertOrderPaymentStatus($order, 'partially_paid');
    }

    public function test_payment_status_paid(): void
    {
        $order = $this->createOrderWithItem(10000);
        $this->addPayment($order, 10000);
        $this->assertOrderPaymentStatus($order, 'paid');
    }

    public function test_payment_status_partially_refunded(): void
    {
        $order = $this->createOrderWithItem(10000);
        $payment = $this->addPayment($order, 10000);
        $this->addRefund($order, $payment, 3000);
        $this->assertOrderPaymentStatus($order, 'partially_refunded');
    }

    public function test_payment_status_refunded(): void
    {
        $order = $this->createOrderWithItem(10000);
        $payment = $this->addPayment($order, 10000);
        $this->addRefund($order, $payment, 10000);
        $this->assertOrderPaymentStatus($order, 'refunded');
    }

    public function test_payment_status_refunded_partial_payments(): void
    {
        // Net paid becomes 0 via partial refunds equal to total paid
        $order = $this->createOrderWithItem(10000);
        $payment = $this->addPayment($order, 6000);
        $this->addRefund($order, $payment, 6000);
        $this->assertOrderPaymentStatus($order, 'refunded');
    }
}
