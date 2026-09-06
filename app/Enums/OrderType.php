<?php

namespace App\Enums;

use App\Contracts\OrderTypeContract;

enum OrderType: string implements OrderTypeContract
{
    case WebsiteOrder = 'website_order';
    case SalesOrder = 'sales_order';

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::WebsiteOrder => 'Website Order',
            self::SalesOrder => 'Sales Order',
        };
    }

    public function isWebsiteOrder(): bool
    {
        return $this === self::WebsiteOrder;
    }

    public function isSalesOrder(): bool
    {
        return $this === self::SalesOrder;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value(),
            'label' => $this->label(),
            'is_website_order' => $this->isWebsiteOrder(),
            'is_sales_order' => $this->isSalesOrder(),
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function options(): array
    {
        return self::cases();
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value(), self::cases());
    }
}
