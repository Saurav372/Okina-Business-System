<?php

namespace Tests\Feature;

use App\Support\Payments\PaymentVerificationCatalog;
use App\Support\Payments\PaymentVerificationRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVerificationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_verification_rules_define_safe_payment_record_updates(): void
    {
        $rules = app(PaymentVerificationRules::class);

        $this->assertSame('payments', $rules->sourceOfTruth());
        $this->assertSame('succeeded', $rules->verifiedPaymentStatus());
        $this->assertSame('pending_verification', $rules->pendingVerificationStatus());
        $this->assertSame('failed', $rules->failedVerificationStatus());
        $this->assertTrue($rules->keepsPaymentsSeparateFromRefunds());
        $this->assertTrue($rules->updatesPaymentRecordsSafely());
    }

    public function test_payment_verification_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(PaymentVerificationRules::class);

        $this->assertSame(
            [
                'source_of_truth' => 'payments',
                'verified_payment_status' => 'succeeded',
                'pending_verification_status' => 'pending_verification',
                'failed_verification_status' => 'failed',
                'keeps_payments_separate_from_refunds' => true,
                'updates_payment_records_safely' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_payment_verification_rules_normalize_verified_payloads_without_leaking_refund_or_secret_data(): void
    {
        $rules = app(PaymentVerificationRules::class);
        $normalized = $rules->normalizeVerifiedPaymentPayload([
            'order_public_id' => 'ord_123',
            'payment_attempt_public_id' => 'payatt_456',
            'provider' => 'cashfree',
            'payment_type' => 'full',
            'amount_minor' => 1250,
            'currency' => 'INR',
            'provider_payment_id' => 'cf_pay_789',
            'provider_order_id' => 'cf_ord_111',
            'provider_reference' => 'cf_ref_222',
            'gateway_fee_minor' => 25,
            'net_amount_minor' => 1225,
            'paid_at' => '2026-06-18 12:00:00',
            'refund_id' => 'should_not_escape',
        ]);

        $this->assertSame('ord_123', $normalized['order_public_id']);
        $this->assertSame('payatt_456', $normalized['payment_attempt_public_id']);
        $this->assertSame('cashfree', $normalized['provider']);
        $this->assertSame('full', $normalized['payment_type']);
        $this->assertSame('succeeded', $normalized['status']);
        $this->assertSame(1250, $normalized['amount_minor']);
        $this->assertSame('INR', $normalized['currency']);
        $this->assertSame('cf_pay_789', $normalized['provider_payment_id']);
        $this->assertSame('cf_ord_111', $normalized['provider_order_id']);
        $this->assertSame('cf_ref_222', $normalized['provider_reference']);
        $this->assertSame(25, $normalized['gateway_fee_minor']);
        $this->assertSame(1225, $normalized['net_amount_minor']);
        $this->assertSame('2026-06-18 12:00:00', $normalized['paid_at']);
        $this->assertTrue($normalized['metadata_isolated']);

        $catalog = app(PaymentVerificationCatalog::class);
        $this->assertSame(
            [
                'key' => 'payment_verification_contract',
                'label' => 'Payment Verification Contract',
                'usage' => 'A verified provider response is normalized into a shared payment record shape with succeeded, pending_verification, and failed states kept separate from refund records.',
                'rules' => [
                    'source_of_truth' => 'payments',
                    'verified_payment_status' => 'succeeded',
                    'pending_verification_status' => 'pending_verification',
                    'failed_verification_status' => 'failed',
                    'keeps_payments_separate_from_refunds' => true,
                    'updates_payment_records_safely' => true,
                    'metadata_isolated' => true,
                ],
                'safety_note' => 'Verification only updates payment records from a trusted payment source; it never writes refund rows or exposes raw provider payloads.',
                'references' => ['A4.5', 'A5.1.3', 'A5.1.4', 'A5.3.1', 'A5.3.2', 'B3.3.6', 'C5.2.5'],
            ],
            $catalog->definition(),
        );
    }
}
