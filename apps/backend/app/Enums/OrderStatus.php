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

    public function canTransitionTo(OrderStatusContract $target): bool
    {
        $targetStatus = $target instanceof self ? $target : self::tryFrom($target->value());
        if (! $targetStatus) {
            return false;
        }

        // Allow transition to self (no-op)
        if ($this === $targetStatus) {
            return true;
        }

        // Terminal states cannot transition to any other state
        if ($this->isTerminal()) {
            return false;
        }

        return match ($this) {
            self::PendingPayment => in_array($targetStatus, [self::Confirmed, self::Cancelled], true),
            self::Confirmed => in_array($targetStatus, [self::InProduction, self::Cancelled, self::Refunded], true),
            self::InProduction => in_array($targetStatus, [self::ReadyToShip, self::Refunded], true),
            self::ReadyToShip => in_array($targetStatus, [self::Shipped, self::Refunded], true),
            self::Shipped => in_array($targetStatus, [self::Delivered, self::Refunded], true),
            self::Delivered => in_array($targetStatus, [self::Refunded], true),
            default => false,
        };
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
