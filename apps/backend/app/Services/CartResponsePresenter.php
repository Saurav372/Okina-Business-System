<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Support\Products\CustomizationSnapshotBuilder;

class CartResponsePresenter
{
    public function __construct(
        private readonly CustomizationSnapshotBuilder $snapshots,
    ) {}

    public function payload(?Cart $cart): array
    {
        if ($cart === null) {
            return [
                'items' => [],
                'item_count' => 0,
            ];
        }

        $items = $cart->items
            ->sortBy('id')
            ->values()
            ->map(fn (CartItem $item): array => $this->itemPayload($item));

        return [
            'items' => $items->all(),
            'item_count' => $items->sum('quantity'),
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
            'customization' => $this->snapshots->publicCartSnapshot($item->customization_snapshot ?? []),
        ];
    }
}
