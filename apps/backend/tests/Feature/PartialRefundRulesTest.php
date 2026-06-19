<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Support\Payments\PartialRefundCatalog;
use App\Support\Payments\PartialRefundRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialRefundRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_refund_rules_define_how_refund_totals_and_payment_states_move(): void
    {
        $rules = app(PartialRefundRules::class);

        $this->assertSame('partial', $rules->refundType());
        $this->assertSame('succeeded', $rules->successfulRefundStatus());
        $this->assertSame(1500, $rules->refundTotalAfterApplying(1000, 500));
        $this->assertSame(500, $rules->refundTotalAfterApplying(0, 500));
        $this->assertSame(200, $rules->refundTotalAfterApplying(200, -300));
        $this->assertSame(PaymentStatus::PartiallyRefunded->value(), $rules->paymentStatusAfterPartialRefund(10000, 1500));
        $this->assertSame(PaymentStatus::Refunded->value(), $rules->paymentStatusAfterPartialRefund(10000, 10000));
        $this->assertTrue($rules->refundsPreserveOriginalPayments());
        $this->assertTrue($rules->refundsStaySeparateFromCancellationEffects());
    }

    public function test_partial_refund_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PartialRefundRules::class);

        $this->assertSame(
            [
                'refund_type' => 'partial',
                'successful_refund_status' => 'succeeded',
                'refunds_preserve_original_payments' => true,
                'refunds_stay_separate_from_cancellation_effects' => true,
                'refund_total_formula' => 'refund_total = current_refund_total + partial_refund_amount',
                'payment_status_formula' => 'refund_total = 0 -> paid; refund_total > 0 and net_paid > 0 -> partially_refunded; refund_total > 0 and net_paid = 0 -> refunded',
            ],
            $rules->toArray(),
        );
    }

    public function test_partial_refund_catalog_documents_the_safety_boundary_for_later_full_refunds(): void
    {
        $catalog = app(PartialRefundCatalog::class);

        $this->assertSame(
            [
                'key' => 'partial_refund_rules',
                'label' => 'Partial Refund Rules',
                'usage' => 'Partial refunds add to the successful refund total and move payment status to partially refunded or refunded based on remaining net paid amount.',
                'rules' => [
                    'refund_type' => 'partial',
                    'successful_refund_status' => 'succeeded',
                    'refund_total_formula' => 'refund_total = current_refund_total + partial_refund_amount',
                    'payment_status_formula' => 'refund_total = 0 -> paid; refund_total > 0 and net_paid > 0 -> partially_refunded; refund_total > 0 and net_paid = 0 -> refunded',
                    'preserves_original_payments' => true,
                    'separate_from_cancellation_effects' => true,
                ],
                'safety_note' => 'Partial refund rules update financial totals only; cancellation handling and full refund handling remain separate tasks.',
                'references' => ['A5.1.3', 'A5.1.4', 'A5.2.2', 'A5.2.3', 'C5.2', 'B3.3.6'],
            ],
            $catalog->definition(),
        );
    }
}
