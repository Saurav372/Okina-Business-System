<?php

namespace App\Enums;

use App\Contracts\OrderStatusContract;

enum OrderStatus: string implements OrderStatusContract
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case ReadyToShip = 'ready_to_ship';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Confirmed => 'Confirmed',
            self::InProduction => 'In Production',
            self::ReadyToShip => 'Ready to Ship',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled, self::Refunded], true);
    }

    public function isCustomerVisible(): bool
    {
        return in_array($this, [
            self::PendingPayment,
            self::Confirmed,
            self::InProduction,
            self::ReadyToShip,
            self::Shipped,
            self::Delivered,
            self::Cancelled,
            self::Refunded,
        ], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value(),
            'label' => $this->label(),
            'is_terminal' => $this->isTerminal(),
            'is_customer_visible' => $this->isCustomerVisible(),
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
        return array_map(static fn (self $status): string => $status->value(), self::cases());
    }
}
