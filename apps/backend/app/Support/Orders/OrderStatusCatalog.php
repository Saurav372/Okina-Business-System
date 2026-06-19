<?php

namespace App\Support\Orders;

use App\Enums\OrderStatus;

final class OrderStatusCatalog
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return [
            [
                'key' => OrderStatus::PendingPayment->value(),
                'label' => OrderStatus::PendingPayment->label(),
                'usage' => 'Checkout creates this status before payment confirmation.',
                'terminal' => false,
                'customer_visible' => true,
                'references' => ['A5.1.5', 'B3.1.6', 'B3.1.10'],
            ],
            [
                'key' => OrderStatus::Confirmed->value(),
                'label' => OrderStatus::Confirmed->label(),
                'usage' => 'Staff or business rules can move an accepted order into confirmed.',
                'terminal' => false,
                'customer_visible' => true,
                'references' => ['A5.1.2', 'C4.1'],
            ],
            [
                'key' => OrderStatus::InProduction->value(),
                'label' => OrderStatus::InProduction->label(),
                'usage' => 'Production work has started.',
                'terminal' => false,
                'customer_visible' => true,
                'references' => ['C4.1', 'C4.2'],
            ],
            [
                'key' => OrderStatus::ReadyToShip->value(),
                'label' => OrderStatus::ReadyToShip->label(),
                'usage' => 'Production is complete and shipping can be arranged.',
                'terminal' => false,
                'customer_visible' => true,
                'references' => ['C4.1', 'C4.2'],
            ],
            [
                'key' => OrderStatus::Shipped->value(),
                'label' => OrderStatus::Shipped->label(),
                'usage' => 'Order has been handed to the courier or delivery process.',
                'terminal' => false,
                'customer_visible' => true,
                'references' => ['C4.1', 'C4.2', 'B4.2'],
            ],
            [
                'key' => OrderStatus::Delivered->value(),
                'label' => OrderStatus::Delivered->label(),
                'usage' => 'Delivery is complete.',
                'terminal' => true,
                'customer_visible' => true,
                'references' => ['C4.1', 'B4.2'],
            ],
            [
                'key' => OrderStatus::Cancelled->value(),
                'label' => OrderStatus::Cancelled->label(),
                'usage' => 'Order is no longer active and is handled by cancellation rules later.',
                'terminal' => true,
                'customer_visible' => true,
                'references' => ['A5.2.1', 'A5.2.2', 'C4.1'],
            ],
            [
                'key' => OrderStatus::Refunded->value(),
                'label' => OrderStatus::Refunded->label(),
                'usage' => 'Order has been fully refunded after later refund rules allow it.',
                'terminal' => true,
                'customer_visible' => true,
                'references' => ['A5.2.4', 'A5.2.5', 'C5.2'],
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

    public function definition(OrderStatus|string $status): ?array
    {
        $key = $status instanceof OrderStatus ? $status->value() : $status;

        foreach ($this->definitions() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }
}
