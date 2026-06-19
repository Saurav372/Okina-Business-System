<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Support\Orders\CancellationEffectCatalog;
use App\Support\Orders\CancellationEffectRules;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationEffectRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancellation_effect_rules_define_the_safe_order_state_only_shape(): void
    {
        $rules = app(CancellationEffectRules::class);

        $this->assertSame(OrderStatus::Cancelled->value(), $rules->cancelledOrderStatus());
        $this->assertFalse($rules->changesPaymentFacts());
        $this->assertFalse($rules->triggersRefundExecution());
        $this->assertFalse($rules->changesStockOnCancellation());
        $this->assertTrue($rules->keepsTheCancelledOrderCustomerVisible());
        $this->assertTrue($rules->hidesSensitiveCancellationNotesFromCustomers());
    }

    public function test_cancellation_effect_rules_are_serializable_for_later_shared_use(): void
    {
        $rules = app(CancellationEffectRules::class);

        $this->assertSame(
            [
                'cancelled_order_status' => 'cancelled',
                'changes_payment_facts' => false,
                'triggers_refund_execution' => false,
                'changes_stock_on_cancellation' => false,
                'keeps_the_cancelled_order_customer_visible' => true,
                'hides_sensitive_cancellation_notes_from_customers' => true,
            ],
            $rules->toArray(),
        );
    }

    public function test_cancellation_effect_catalog_documents_the_separation_from_refunds_and_stock_reversal(): void
    {
        $catalog = app(CancellationEffectCatalog::class);

        $this->assertSame(
            [
                'key' => 'order_cancellation_effects',
                'label' => 'Order Cancellation Effects',
                'usage' => 'Cancellation marks the order cancelled, keeps payment facts separate, avoids stock reversal, and keeps customer-safe visibility.',
                'rules' => [
                    'cancelled_order_status' => 'cancelled',
                    'payment_facts_changed' => false,
                    'refund_execution_triggered' => false,
                    'stock_changed_on_cancellation' => false,
                    'customer_visible' => true,
                    'sensitive_notes_hidden_from_customers' => true,
                ],
                'safety_note' => 'Cancellation rules define the order state only; refund execution and stock reversal stay in later tasks.',
                'references' => ['A5.1.2', 'A5.2.1', 'A5.2.2', 'B4.2', 'C4.1', 'C2.1'],
            ],
            $catalog->definition(),
        );
    }
}
