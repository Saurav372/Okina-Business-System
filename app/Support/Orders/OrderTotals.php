<?php

namespace App\Support\Orders;

use App\Contracts\OrderTotalsContract;

readonly class OrderTotals implements OrderTotalsContract
{
    private function __construct(
        private int $subtotalAmountMinor,
        private int $discountAmountMinor,
        private int $shippingAmountMinor,
        private int $taxAmountMinor,
        private int $paidAmountMinor,
        private int $refundAmountMinor,
    ) {}

    public static function fromAmounts(
        int $subtotalAmountMinor,
        int $discountAmountMinor = 0,
        int $shippingAmountMinor = 0,
        int $taxAmountMinor = 0,
        int $paidAmountMinor = 0,
        int $refundAmountMinor = 0,
    ): self {
        return new self(
            subtotalAmountMinor: max(0, $subtotalAmountMinor),
            discountAmountMinor: max(0, $discountAmountMinor),
            shippingAmountMinor: max(0, $shippingAmountMinor),
            taxAmountMinor: max(0, $taxAmountMinor),
            paidAmountMinor: max(0, $paidAmountMinor),
            refundAmountMinor: max(0, $refundAmountMinor),
        );
    }

    public static function fromLineTotals(
        array $lineTotals,
        int $discountAmountMinor = 0,
        int $shippingAmountMinor = 0,
        int $taxAmountMinor = 0,
        int $paidAmountMinor = 0,
        int $refundAmountMinor = 0,
    ): self {
        $subtotal = array_reduce(
            $lineTotals,
            static fn (int $carry, mixed $lineTotal): int => $carry + max(0, (int) $lineTotal),
            0,
        );

        return self::fromAmounts(
            subtotalAmountMinor: $subtotal,
            discountAmountMinor: $discountAmountMinor,
            shippingAmountMinor: $shippingAmountMinor,
            taxAmountMinor: $taxAmountMinor,
            paidAmountMinor: $paidAmountMinor,
            refundAmountMinor: $refundAmountMinor,
        );
    }

    public function subtotalAmountMinor(): int
    {
        return $this->subtotalAmountMinor;
    }

    public function discountAmountMinor(): int
    {
        return $this->discountAmountMinor;
    }

    public function shippingAmountMinor(): int
    {
        return $this->shippingAmountMinor;
    }

    public function taxAmountMinor(): int
    {
        return $this->taxAmountMinor;
    }

    public function totalAmountMinor(): int
    {
        return max(0, $this->subtotalAmountMinor - $this->discountAmountMinor + $this->shippingAmountMinor + $this->taxAmountMinor);
    }

    public function paidAmountMinor(): int
    {
        return $this->paidAmountMinor;
    }

    public function refundAmountMinor(): int
    {
        return $this->refundAmountMinor;
    }

    public function netPaidAmountMinor(): int
    {
        return max(0, $this->paidAmountMinor - $this->refundAmountMinor);
    }

    public function balanceDueMinor(): int
    {
        return max(0, $this->totalAmountMinor() - $this->paidAmountMinor);
    }

    public function outstandingAmountMinor(): int
    {
        return max(0, $this->totalAmountMinor() - $this->netPaidAmountMinor());
    }

    public function isBalanced(): bool
    {
        return $this->balanceDueMinor() === 0 && $this->refundAmountMinor() === 0;
    }

    public function isOverpaid(): bool
    {
        return $this->paidAmountMinor > $this->totalAmountMinor();
    }

    public function hasRefundActivity(): bool
    {
        return $this->refundAmountMinor > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal_amount_minor' => $this->subtotalAmountMinor(),
            'discount_amount_minor' => $this->discountAmountMinor(),
            'shipping_amount_minor' => $this->shippingAmountMinor(),
            'tax_amount_minor' => $this->taxAmountMinor(),
            'total_amount_minor' => $this->totalAmountMinor(),
            'paid_amount_minor' => $this->paidAmountMinor(),
            'refund_amount_minor' => $this->refundAmountMinor(),
            'net_paid_amount_minor' => $this->netPaidAmountMinor(),
            'balance_due_minor' => $this->balanceDueMinor(),
            'outstanding_amount_minor' => $this->outstandingAmountMinor(),
            'is_balanced' => $this->isBalanced(),
            'is_overpaid' => $this->isOverpaid(),
            'has_refund_activity' => $this->hasRefundActivity(),
        ];
    }
}
