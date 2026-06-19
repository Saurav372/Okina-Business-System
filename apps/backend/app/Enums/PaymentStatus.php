<?php

namespace App\Enums;

use App\Contracts\PaymentStatusContract;

enum PaymentStatus: string implements PaymentStatusContract
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::PartiallyPaid => 'Partially Paid',
            self::Paid => 'Paid',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Refunded => 'Refunded',
        };
    }

    public function isOpenBalance(): bool
    {
        return in_array($this, [self::Unpaid, self::PartiallyPaid], true);
    }

    public function isRefundState(): bool
    {
        return in_array($this, [self::PartiallyRefunded, self::Refunded], true);
    }

    public function isSettled(): bool
    {
        return in_array($this, [self::Paid, self::Refunded], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value(),
            'label' => $this->label(),
            'is_open_balance' => $this->isOpenBalance(),
            'is_refund_state' => $this->isRefundState(),
            'is_settled' => $this->isSettled(),
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
