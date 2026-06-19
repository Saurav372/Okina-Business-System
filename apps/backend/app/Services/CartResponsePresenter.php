<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Products\CustomizationSnapshotBuilder;

class CartResponsePresenter
{
    public function __construct(
        private readonly CartPricingService $pricing,
        private readonly CustomizationSnapshotBuilder $snapshots,
    ) {}

    public function payload(?Cart $cart): array
    {
        if ($cart === null) {
            return [
                'items' => [],
                'item_count' => 0,
                'pricing' => [
                    'currency' => 'INR',
                    'subtotal_amount_minor' => 0,
                    'discount_amount_minor' => 0,
                    'shipping_amount_minor' => 0,
                    'tax_amount_minor' => 0,
                    'total_amount_minor' => 0,
                ],
            ];
        }

        $cart->loadMissing(['items.product', 'items.sku']);

        $items = $cart->items
            ->sortBy('id')
            ->values()
            ->map(fn (CartItem $item): array => $this->itemPayload($item));

        return [
            'items' => $items->all(),
            'item_count' => $items->sum('quantity'),
            'pricing' => $this->pricing->summary($cart),
        ];
    }

    private function itemPayload(CartItem $item): array
    {
        return [
            'id' => $item->public_id,
            'product' => [
                'slug' => $item->product_slug_snapshot,
                'name' => $item->product_name_snapshot,
            ],
            'sku' => [
                'code' => $item->sku_code_snapshot,
            ],
            'quantity' => $item->quantity,
            'pricing' => $this->pricing->lineItem($item),
            'customization' => $this->snapshots->publicCartSnapshot($item->customization_snapshot ?? []),
        ];
    }
}
