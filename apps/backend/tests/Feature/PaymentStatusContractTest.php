<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Support\Payments\PaymentStatusCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentStatusContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_payment_statuses_expose_the_approved_values(): void
    {
        $this->assertSame([
            'unpaid',
            'partially_paid',
            'paid',
            'partially_refunded',
            'refunded',
        ], PaymentStatus::values());
    }

    public function test_shared_payment_statuses_provide_labels_and_state_flags(): void
    {
        $this->assertSame('Unpaid', PaymentStatus::Unpaid->label());
        $this->assertSame('Partially Paid', PaymentStatus::PartiallyPaid->label());
        $this->assertSame('Partially Refunded', PaymentStatus::PartiallyRefunded->label());
        $this->assertTrue(PaymentStatus::Unpaid->isOpenBalance());
        $this->assertTrue(PaymentStatus::PartiallyPaid->isOpenBalance());
        $this->assertTrue(PaymentStatus::Paid->isSettled());
        $this->assertTrue(PaymentStatus::Refunded->isSettled());
        $this->assertTrue(PaymentStatus::PartiallyRefunded->isRefundState());
        $this->assertFalse(PaymentStatus::Paid->isRefundState());
        $this->assertSame(
            [
                'value' => 'unpaid',
                'label' => 'Unpaid',
                'is_open_balance' => true,
                'is_refund_state' => false,
                'is_settled' => false,
            ],
            PaymentStatus::Unpaid->toArray(),
        );
    }

    public function test_payment_status_catalog_keeps_calculation_rules_derived_from_records(): void
    {
        $catalog = app(PaymentStatusCatalog::class);

        $this->assertSame([
            'unpaid',
            'partially_paid',
            'paid',
            'partially_refunded',
            'refunded',
        ], $catalog->keys());

        $paid = $catalog->definition(PaymentStatus::Paid);
        $refunded = $catalog->definition('refunded');

        $this->assertSame('Paid', $paid['label']);
        $this->assertSame('paid_total >= order_total and refund_total = 0', $paid['calculation']);
        $this->assertSame('payments and refunds', $paid['source_of_truth']);
        $this->assertSame(['A5.1.3', 'A5.1.4', 'B3.3.6'], $paid['references']);

        $this->assertSame('Refunded', $refunded['label']);
        $this->assertSame('refund_total > 0 and net_paid = 0', $refunded['calculation']);
        $this->assertSame(['A5.1.3', 'A5.1.4', 'A5.2.5'], $refunded['references']);
    }
}
