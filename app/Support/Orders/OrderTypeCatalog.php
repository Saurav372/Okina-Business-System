<?php

namespace App\Support\Orders;

use App\Enums\OrderType;

final class OrderTypeCatalog
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
                'usage' => 'Customer checkout creates website orders before payment starts.',
                'channels' => ['website', 'checkout'],
                'references' => ['A5.1.5', 'B3.1.6', 'B3.1.8'],
            ],
            [
                'key' => OrderType::SalesOrder->value(),
                'label' => OrderType::SalesOrder->label(),
                'usage' => 'Staff creates sales orders from admin workflows and may manage advance or final payment later.',
                'channels' => ['admin', 'sales'],
                'references' => ['A5.1.6', 'C1.2.6', 'C1.2.8'],
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
