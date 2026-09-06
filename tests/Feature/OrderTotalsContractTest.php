<?php

namespace Tests\Feature;

use App\Support\Orders\OrderTotalsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTotalsContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_totals_calculate_from_line_items_and_order_adjustments(): void
    {
        $totals = app(OrderTotalsCalculator::class)->fromLineTotals(
            lineTotalsMinor: [1500, 2500, 1000],
            discountAmountMinor: 500,
            shippingAmountMinor: 200,
            taxAmountMinor: 360,
        );

        $this->assertSame(5000, $totals->subtotalAmountMinor());
        $this->assertSame(500, $totals->discountAmountMinor());
        $this->assertSame(200, $totals->shippingAmountMinor());
        $this->assertSame(360, $totals->taxAmountMinor());
        $this->assertSame(5060, $totals->totalAmountMinor());
        $this->assertSame(0, $totals->paidAmountMinor());
        $this->assertSame(0, $totals->refundAmountMinor());
        $this->assertSame(0, $totals->netPaidAmountMinor());
        $this->assertSame(5060, $totals->balanceDueMinor());
        $this->assertSame(5060, $totals->outstandingAmountMinor());
        $this->assertFalse($totals->isBalanced());
        $this->assertFalse($totals->isOverpaid());
        $this->assertFalse($totals->hasRefundActivity());
    }

    public function test_paid_and_refund_inputs_adjust_balance_without_conflating_payment_status(): void
    {
        $totals = app(OrderTotalsCalculator::class)->fromAmounts(
            subtotalAmountMinor: 10000,
            discountAmountMinor: 1000,
            shippingAmountMinor: 500,
            taxAmountMinor: 270,
            paidAmountMinor: 9000,
            refundAmountMinor: 1500,
        );

        $this->assertSame(9770, $totals->totalAmountMinor());
        $this->assertSame(9000, $totals->paidAmountMinor());
        $this->assertSame(1500, $totals->refundAmountMinor());
        $this->assertSame(7500, $totals->netPaidAmountMinor());
        $this->assertSame(770, $totals->balanceDueMinor());
        $this->assertSame(2270, $totals->outstandingAmountMinor());
        $this->assertTrue($totals->hasRefundActivity());
        $this->assertFalse($totals->isBalanced());
        $this->assertFalse($totals->isOverpaid());
    }

    public function test_order_totals_are_serializable_for_later_service_usage(): void
    {
        $totals = app(OrderTotalsCalculator::class)->fromAmounts(
            subtotalAmountMinor: 1200,
            shippingAmountMinor: 100,
            taxAmountMinor: 54,
            paidAmountMinor: 1354,
        );

        $this->assertSame(
            [
                'subtotal_amount_minor' => 1200,
                'discount_amount_minor' => 0,
                'shipping_amount_minor' => 100,
                'tax_amount_minor' => 54,
                'total_amount_minor' => 1354,
                'paid_amount_minor' => 1354,
                'refund_amount_minor' => 0,
                'net_paid_amount_minor' => 1354,
                'balance_due_minor' => 0,
                'outstanding_amount_minor' => 0,
                'is_balanced' => true,
                'is_overpaid' => false,
                'has_refund_activity' => false,
            ],
            $totals->toArray(),
        );
    }
}
