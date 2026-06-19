<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Support\Payments\PaymentStateRecalculationCatalog;
use App\Support\Payments\PaymentStateRecalculationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStateRecalculationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_state_recalculation_rules_derive_status_from_payment_and_refund_records_only(): void
    {
        $rules = app(PaymentStateRecalculationRules::class);

        $this->assertSame('payments and refunds', $rules->sourceOfTruth());
        $this->assertSame(PaymentStatus::Unpaid->value(), $rules->calculate(1000, 0, 0));
        $this->assertSame(PaymentStatus::PartiallyPaid->value(), $rules->calculate(1000, 400, 0));
        $this->assertSame(PaymentStatus::Paid->value(), $rules->calculate(1000, 1000, 0));
        $this->assertSame(PaymentStatus::PartiallyRefunded->value(), $rules->calculate(1000, 1000, 200));
        $this->assertSame(PaymentStatus::Refunded->value(), $rules->calculate(1000, 1000, 1000));
        $this->assertSame(PaymentStatus::Refunded->value(), $rules->calculate(1000, 400, 700));
        $this->assertSame(300, $rules->netPaid(1000, 700));
        $this->assertTrue($rules->refundStateTakesPriorityOverPaidState());
    }

    public function test_payment_state_recalculation_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PaymentStateRecalculationRules::class);

        $this->assertSame(
            [
                'source_of_truth' => 'payments and refunds',
                'unpaid_status' => 'unpaid',
                'partially_paid_status' => 'partially_paid',
                'paid_status' => 'paid',
                'partially_refunded_status' => 'partially_refunded',
                'refunded_status' => 'refunded',
                'refund_state_takes_priority_over_paid_state' => true,
                'net_paid_formula' => 'net_paid = max(0, paid_total - refund_total)',
            ],
            $rules->toArray(),
        );
    }

    public function test_payment_state_recalculation_catalog_documents_the_derived_financial_state_boundary(): void
    {
        $catalog = app(PaymentStateRecalculationCatalog::class);

        $this->assertSame(
            [
                'key' => 'payment_state_recalculation',
                'label' => 'Payment-State Recalculation',
                'usage' => 'Payment status is derived from successful payments and refunds, with refund states taking priority once refunds exist.',
                'rules' => [
                    'source_of_truth' => 'payments and refunds',
                    'unpaid_status' => 'unpaid',
                    'partially_paid_status' => 'partially_paid',
                    'paid_status' => 'paid',
                    'partially_refunded_status' => 'partially_refunded',
                    'refunded_status' => 'refunded',
                    'refund_state_takes_priority_over_paid_state' => true,
                    'net_paid_formula' => 'net_paid = max(0, paid_total - refund_total)',
                ],
                'safety_note' => 'Payment status is derived only from payment and refund records; order status and cancellation behavior remain separate concerns.',
                'references' => ['A5.1.3', 'A5.1.4', 'A5.2.3', 'A5.2.4', 'C5.2', 'C1.1', 'B4.2'],
            ],
            $catalog->definition(),
        );
    }
}
