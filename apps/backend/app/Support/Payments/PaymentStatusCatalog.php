<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

final class PaymentStatusCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => PaymentStatus::Unpaid->value(),
                'label' => PaymentStatus::Unpaid->label(),
                'calculation' => 'paid_total = 0 and refund_total = 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'B3.3.6'],
            ],
            [
                'key' => PaymentStatus::PartiallyPaid->value(),
                'label' => PaymentStatus::PartiallyPaid->label(),
                'calculation' => 'paid_total > 0 and paid_total < order_total and refund_total = 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'B3.3.6'],
            ],
            [
                'key' => PaymentStatus::Paid->value(),
                'label' => PaymentStatus::Paid->label(),
                'calculation' => 'paid_total >= order_total and refund_total = 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'B3.3.6'],
            ],
            [
                'key' => PaymentStatus::PartiallyRefunded->value(),
                'label' => PaymentStatus::PartiallyRefunded->label(),
                'calculation' => 'refund_total > 0 and net_paid > 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'A5.2.5'],
            ],
            [
                'key' => PaymentStatus::Refunded->value(),
                'label' => PaymentStatus::Refunded->label(),
                'calculation' => 'refund_total > 0 and net_paid = 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'A5.2.5'],
            ],
            [
                'key' => PaymentStatus::AdvancePaid->value(),
                'label' => PaymentStatus::AdvancePaid->label(),
                'calculation' => 'paid_total >= expected_advance and paid_total < order_total and refund_total = 0',
                'source_of_truth' => 'payments and refunds',
                'references' => ['A5.1.3', 'A5.1.4', 'B3.3.6'],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_map(static fn (array $definition): string => $definition['key'], $this->definitions());
    }

    public function definition(PaymentStatus|string $status): ?array
    {
        $key = $status instanceof PaymentStatus ? $status->value() : $status;

        foreach ($this->definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }
}
