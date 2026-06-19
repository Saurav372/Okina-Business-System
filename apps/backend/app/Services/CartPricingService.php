<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Orders\OrderTotalsCalculator;
use Illuminate\Support\Collection;

class CartPricingService
{
    public function __construct(
        private readonly OrderTotalsCalculator $totalsCalculator,
    ) {}

    /**
     * @return array{currency: string, subtotal_amount_minor: int, discount_amount_minor: int, shipping_amount_minor: int, tax_amount_minor: int, total_amount_minor: int}
     */
    public function summary(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'items.sku']);

        $lineTotals = $cart->items
            ->map(fn (CartItem $item): int => $this->lineTotalMinor($item))
            ->all();

        $totals = $this->totalsCalculator->fromLineTotals($lineTotals);

        return [
            'currency' => $this->currency($cart->items),
            'subtotal_amount_minor' => $totals->subtotalAmountMinor(),
            'discount_amount_minor' => $totals->discountAmountMinor(),
            'shipping_amount_minor' => $totals->shippingAmountMinor(),
            'tax_amount_minor' => $totals->taxAmountMinor(),
            'total_amount_minor' => $totals->totalAmountMinor(),
        ];
    }

    /**
     * @return array{currency: string, unit_price_minor: int, line_subtotal_minor: int, line_total_minor: int, price_source: string}
     */
    public function lineItem(CartItem $item): array
    {
        $unitPrice = $this->unitPriceMinor($item);
        $lineTotal = $unitPrice * max(1, $item->quantity);

        return [
            'currency' => $this->currencyForItem($item),
            'unit_price_minor' => $unitPrice,
            'line_subtotal_minor' => $lineTotal,
            'line_total_minor' => $lineTotal,
            'price_source' => $this->priceSource($item),
        ];
    }

    private function lineTotalMinor(CartItem $item): int
    {
        return $this->unitPriceMinor($item) * max(1, $item->quantity);
    }

    private function unitPriceMinor(CartItem $item): int
    {
        $skuPrice = $item->sku?->price_minor;

        if (is_int($skuPrice)) {
            return max(0, $skuPrice);
        }

        $basePrice = $item->product?->base_price_minor;

        if (is_int($basePrice)) {
            return max(0, $basePrice);
        }

        return 0;
    }

    private function priceSource(CartItem $item): string
    {
        if (is_int($item->sku?->price_minor)) {
            return 'sku_price';
        }

        if (is_int($item->product?->base_price_minor)) {
            return 'product_base_price';
        }

        return 'unpriced';
    }

    /**
     * @param  Collection<int, CartItem>  $items
     */
    private function currency(Collection $items): string
    {
        foreach ($items as $item) {
            $currency = $this->currencyForItem($item);

            if ($currency !== '') {
                return $currency;
            }
        }

        return 'INR';
    }

    private function currencyForItem(CartItem $item): string
    {
        $currency = $item->product?->currency;

        if (is_string($currency) && $currency !== '') {
            return strtoupper($currency);
        }

        return 'INR';
    }
}
