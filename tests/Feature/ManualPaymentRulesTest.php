<?php

namespace Tests\Feature;

use App\Support\Payments\ManualPaymentCatalog;
use App\Support\Payments\ManualPaymentRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualPaymentRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_payment_rules_define_shared_record_updates_without_gateway_dependency(): void
    {
        $rules = app(ManualPaymentRules::class);

        $this->assertSame('manual', $rules->provider());
        $this->assertSame('manual_adjustment', $rules->paymentType());
        $this->assertTrue($rules->supportsManualPayments());
        $this->assertFalse($rules->requiresPaymentAttempt());
        $this->assertTrue($rules->requiresVerifiedByUser());
        $this->assertTrue($rules->keepsManualPaymentsSeparateFromGatewayLogic());
    }

    public function test_manual_payment_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(ManualPaymentRules::class);

        $this->assertSame(
            [
                'provider' => 'manual',
                'payment_type' => 'manual_adjustment',
                'supports_manual_payments' => true,
                'requires_payment_attempt' => false,
                'requires_verified_by_user' => true,
                'keeps_manual_payments_separate_from_gateway_logic' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_manual_payment_rules_normalize_payloads_without_pretending_to_be_gateway_payments(): void
    {
        $rules = app(ManualPaymentRules::class);
        $normalized = $rules->normalizeManualPayment([
            'order_public_id' => 'ord_123',
            'amount_minor' => 2500,
            'currency' => 'INR',
            'method' => 'cash',
            'provider_reference' => 'receipt-789',
            'verified_by_user_id' => 42,
            'recorded_by_user_id' => 21,
            'notes' => 'customer paid at counter',
            'provider_payment_id' => 'should_not_be_gateway_like',
        ]);

        $this->assertSame('ord_123', $normalized['order_public_id']);
        $this->assertSame('manual', $normalized['provider']);
        $this->assertSame('manual_adjustment', $normalized['payment_type']);
        $this->assertSame('pending_verification', $normalized['status']);
        $this->assertSame(2500, $normalized['amount_minor']);
        $this->assertSame('INR', $normalized['currency']);
        $this->assertSame('cash', $normalized['method']);
        $this->assertSame('should_not_be_gateway_like', $normalized['provider_payment_id']);
        $this->assertSame('receipt-789', $normalized['provider_reference']);
        $this->assertSame(42, $normalized['verified_by_user_id']);
        $this->assertSame(21, $normalized['recorded_by_user_id']);
        $this->assertNull($normalized['payment_attempt_public_id']);
        $this->assertTrue($normalized['notes_isolated']);

        $catalog = app(ManualPaymentCatalog::class);
        $this->assertSame(
            [
                'key' => 'manual_payment_support',
                'label' => 'Manual Payment Support',
                'usage' => 'Staff can record manual payments using the same shared payment records, with no payment attempt required and verification handled through user attribution.',
                'rules' => [
                    'provider' => 'manual',
                    'payment_type' => 'manual_adjustment',
                    'supports_manual_payments' => true,
                    'requires_payment_attempt' => false,
                    'requires_verified_by_user' => true,
                    'keeps_manual_payments_separate_from_gateway_logic' => true,
                    'notes_isolated' => true,
                ],
                'safety_note' => 'Manual payment entries update balances through the shared payments table and never impersonate gateway-originated payments.',
                'references' => ['A4.2', 'A5.1.3', 'A5.1.4', 'A5.3.4', 'C5.2.2', 'C5.2.6'],
            ],
            $catalog->definition(),
        );
    }
}
