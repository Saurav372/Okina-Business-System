<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Support\Payments\FullRefundCatalog;
use App\Support\Payments\FullRefundRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullRefundRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_refund_rules_define_the_complete_refund_shape(): void
    {
        $rules = app(FullRefundRules::class);

        $this->assertSame('full', $rules->refundType());
        $this->assertSame('succeeded', $rules->successfulRefundStatus());
        $this->assertSame(OrderStatus::Refunded->value(), $rules->orderStatusAfterFullRefund());
        $this->assertSame(10000, $rules->refundTotalAfterApplying(10000, 0));
        $this->assertSame(10000, $rules->refundTotalAfterApplying(10000, 2500));
        $this->assertSame(PaymentStatus::Refunded->value(), $rules->paymentStatusAfterFullRefund());
        $this->assertTrue($rules->keepsOriginalPayments());
        $this->assertTrue($rules->keepsCustomerVisibilitySafe());
    }

    public function test_full_refund_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(FullRefundRules::class);

        $this->assertSame(
            [
                'refund_type' => 'full',
                'successful_refund_status' => 'succeeded',
                'order_status_after_full_refund' => 'refunded',
                'payment_status_after_full_refund' => 'refunded',
                'keeps_original_payments' => true,
                'keeps_customer_visibility_safe' => true,
                'refund_total_formula' => 'refund_total = max(current_refund_total, paid_total)',
            ],
            $rules->toArray(),
        );
    }

    public function test_full_refund_catalog_documents_financial_and_customer_safe_outcomes(): void
    {
        $catalog = app(FullRefundCatalog::class);

        $this->assertSame(
            [
                'key' => 'full_refund_rules',
                'label' => 'Full Refund Rules',
                'usage' => 'A full refund restores the full successful paid amount, marks the order refunded, and keeps the original payment history intact.',
                'rules' => [
                    'refund_type' => 'full',
                    'successful_refund_status' => 'succeeded',
                    'order_status_after_full_refund' => 'refunded',
                    'payment_status_after_full_refund' => 'refunded',
                    'refund_total_formula' => 'refund_total = max(current_refund_total, paid_total)',
                    'keeps_original_payments' => true,
                    'keeps_customer_visibility_safe' => true,
                ],
                'safety_note' => 'Full refund rules set the financial outcome and order marker only; payment-state recalculation, refund approval flow, and audit storage remain separate tasks.',
                'references' => ['A5.1.3', 'A5.1.4', 'A5.2.3', 'A5.2.5', 'C5.2', 'C1.1'],
            ],
            $catalog->definition(),
        );
    }
}
