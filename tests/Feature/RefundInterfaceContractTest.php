<?php

namespace Tests\Feature;

use App\Support\Payments\RefundInterfaceCatalog;
use App\Support\Payments\RefundInterfaceRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefundInterfaceContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_interface_rules_define_shared_refund_state_handling(): void
    {
        $rules = app(RefundInterfaceRules::class);

        $this->assertSame('refunds', $rules->sourceOfTruth());
        $this->assertSame('partial', $rules->partialRefundType());
        $this->assertSame('full', $rules->fullRefundType());
        $this->assertSame('requested', $rules->requestedStatus());
        $this->assertSame('approved', $rules->approvedStatus());
        $this->assertSame('processing', $rules->processingStatus());
        $this->assertSame('succeeded', $rules->succeededStatus());
        $this->assertSame('failed', $rules->failedStatus());
        $this->assertSame('cancelled', $rules->cancelledStatus());
        $this->assertTrue($rules->usesSharedRefundsTable());
        $this->assertTrue($rules->keepsOriginalPayments());
    }

    public function test_refund_interface_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(RefundInterfaceRules::class);

        $this->assertSame(
            [
                'source_of_truth' => 'refunds',
                'partial_refund_type' => 'partial',
                'full_refund_type' => 'full',
                'requested_status' => 'requested',
                'approved_status' => 'approved',
                'processing_status' => 'processing',
                'succeeded_status' => 'succeeded',
                'failed_status' => 'failed',
                'cancelled_status' => 'cancelled',
                'uses_shared_refunds_table' => true,
                'keeps_original_payments' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_refund_interface_rules_normalize_payloads_without_leaking_gateway_specific_state(): void
    {
        $rules = app(RefundInterfaceRules::class);
        $normalized = $rules->normalizeRefundPayload([
            'order_public_id' => 'ord_123',
            'payment_public_id' => 'pay_456',
            'provider' => 'cashfree',
            'refund_type' => 'partial',
            'status' => 'requested',
            'amount_minor' => 500,
            'currency' => 'INR',
            'provider_refund_id' => 'cf_ref_789',
            'provider_payment_id' => 'cf_pay_111',
            'provider_reference' => 'ref-222',
            'requested_by_user_id' => 1,
            'approved_by_user_id' => 2,
            'processed_by_user_id' => 3,
            'requested_at' => '2026-06-18 13:00:00',
            'approved_at' => '2026-06-18 13:05:00',
            'processed_at' => '2026-06-18 13:10:00',
            'raw_payload' => ['token' => 'secret'],
        ]);

        $this->assertSame('ord_123', $normalized['order_public_id']);
        $this->assertSame('pay_456', $normalized['payment_public_id']);
        $this->assertSame('cashfree', $normalized['provider']);
        $this->assertSame('partial', $normalized['refund_type']);
        $this->assertSame('requested', $normalized['status']);
        $this->assertSame(500, $normalized['amount_minor']);
        $this->assertSame('INR', $normalized['currency']);
        $this->assertSame('cf_ref_789', $normalized['provider_refund_id']);
        $this->assertSame('cf_pay_111', $normalized['provider_payment_id']);
        $this->assertSame('ref-222', $normalized['provider_reference']);
        $this->assertSame(1, $normalized['requested_by_user_id']);
        $this->assertSame(2, $normalized['approved_by_user_id']);
        $this->assertSame(3, $normalized['processed_by_user_id']);
        $this->assertSame('2026-06-18 13:00:00', $normalized['requested_at']);
        $this->assertSame('2026-06-18 13:05:00', $normalized['approved_at']);
        $this->assertSame('2026-06-18 13:10:00', $normalized['processed_at']);
        $this->assertTrue($normalized['metadata_isolated']);

        $catalog = app(RefundInterfaceCatalog::class);
        $this->assertSame(
            [
                'key' => 'refund_interface_contract',
                'label' => 'Refund Interface Contract',
                'usage' => 'Refund requests, approvals, and processed outcomes use the shared refunds table and preserve original payments.',
                'rules' => [
                    'source_of_truth' => 'refunds',
                    'partial_refund_type' => 'partial',
                    'full_refund_type' => 'full',
                    'requested_status' => 'requested',
                    'approved_status' => 'approved',
                    'processing_status' => 'processing',
                    'succeeded_status' => 'succeeded',
                    'failed_status' => 'failed',
                    'cancelled_status' => 'cancelled',
                    'uses_shared_refunds_table' => true,
                    'keeps_original_payments' => true,
                ],
                'safety_note' => 'Refund handling stays focused on shared refund records and state transitions; payment calculation and audit storage remain separate concerns.',
                'references' => ['A5.2.3', 'A5.2.4', 'A5.2.5', 'A5.2.6', 'C5.2.1', 'C5.2.5'],
            ],
            $catalog->definition(),
        );
    }
}
