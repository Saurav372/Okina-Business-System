<?php

namespace App\Support\Orders;

use App\Enums\OrderType;

final class CancellationEligibilityCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => OrderType::WebsiteOrder->value(),
                'label' => OrderType::WebsiteOrder->label(),
                'usage' => 'Website orders can be cancelled before production starts, while pending payment or confirmed.',
                'cancellable_statuses' => ['pending_payment', 'confirmed'],
                'safety_note' => 'Cancellation eligibility stays separate from payment refund execution.',
                'references' => ['A5.1.2', 'A5.1.4', 'A5.1.5', 'A5.2.1', 'B3.1.6'],
            ],
            [
                'key' => OrderType::SalesOrder->value(),
                'label' => OrderType::SalesOrder->label(),
                'usage' => 'Sales orders can be cancelled while still confirmed and before production starts.',
                'cancellable_statuses' => ['confirmed'],
                'safety_note' => 'Cancellation rules do not process refunds, payment reversals, or inventory reversal yet.',
                'references' => ['A5.1.2', 'A5.1.4', 'A5.1.6', 'A5.2.1', 'C1.2.6'],
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

    public function definition(OrderType|string $type): ?array
    {
        $key = $type instanceof OrderType ? $type->value() : $type;

        foreach ($this->definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }
}
