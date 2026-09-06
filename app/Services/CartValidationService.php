<?php

namespace App\Services;

use App\Contracts\CustomizationOptionContract;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductSku;
use App\Support\Products\CustomizationSnapshotBuilder;

class CartValidationService
{
    public function __construct(
        private readonly CustomizationOptionContract $customizationRules,
        private readonly CustomizationSnapshotBuilder $snapshots,
        private readonly CartResponsePresenter $presenter,
    ) {}

    /**
     * @return array{cart: array<string, mixed>, valid: bool, items: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>}
     */
    public function payload(?Cart $cart): array
    {
        if ($cart === null) {
            return [
                'cart' => $this->presenter->payload(null),
                'valid' => true,
                'items' => [],
                'errors' => [],
            ];
        }

        $results = $cart->items
            ->sortBy('id')
            ->values()
            ->map(fn (CartItem $item): array => $this->validateItem($item));

        $invalidItems = $results->filter(fn (array $item): bool => $item['valid'] === false)->values();

        return [
            'cart' => $this->presenter->payload($cart),
            'valid' => $invalidItems->isEmpty(),
            'items' => $results->all(),
            'errors' => $invalidItems
                ->map(fn (array $item): array => [
                    'item_id' => $item['id'],
                    'errors' => $item['errors'],
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateItem(CartItem $item): array
    {
        $product = Product::query()
            ->publiclyVisible()
            ->with([
                'variants' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
                'skus' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            ])
            ->whereKey($item->product_id)
            ->first();

        if ($product === null) {
            return $this->itemResult($item, false, ['product_unavailable']);
        }

        $sku = ProductSku::query()
            ->whereKey($item->sku_id)
            ->first();

        if ($sku === null || $sku->product_id !== $product->id) {
            return $this->itemResult($item, false, ['sku_unavailable']);
        }

        $errors = [];
        $quantityMinimum = max(1, (int) ($product->min_order_quantity ?? 1));
        $quantityMaximum = $product->max_order_quantity !== null ? (int) $product->max_order_quantity : 9999;

        if ($item->quantity < $quantityMinimum || $item->quantity > $quantityMaximum) {
            $errors[] = 'quantity_out_of_range';
        }

        if (! $product->direct_checkout_enabled) {
            $errors[] = 'product_unavailable';
        }

        if (($sku->status ?? null) !== 'active' || ! $sku->direct_checkout_enabled || $sku->quote_required) {
            $errors[] = 'sku_unavailable';
        }

        $normalizedSnapshot = $this->snapshots->publicCartSnapshot($item->customization_snapshot ?? []);
        $selection = $this->selectionFromCustomizationSnapshot($sku->sku_code, $normalizedSnapshot);
        $validation = $this->customizationRules->validateSelection($product->slug, $selection);

        if (! ($validation['valid'] ?? false)) {
            foreach (($validation['errors'] ?? ['customization_invalid']) as $error) {
                $errors[] = (string) $error;
            }
        }

        return $this->itemResult($item, $errors === [], array_values(array_unique($errors)));
    }

    /**
     * @return array<string, mixed>
     */
    private function itemResult(CartItem $item, bool $valid, array $errors): array
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
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function selectionFromCustomizationSnapshot(string $skuCode, array $snapshot): array
    {
        $selectedOptions = data_get($snapshot, 'selected_options_snapshot', []);
        $selectedOptionsMap = [];

        if (is_array($selectedOptions)) {
            foreach ($selectedOptions as $option) {
                if (! is_array($option)) {
                    continue;
                }

                $optionCode = $this->normalizedString($option['option_code'] ?? null);
                $valueCode = $this->normalizedString($option['value_code'] ?? null);

                if ($optionCode === null || $valueCode === null) {
                    continue;
                }

                $selectedOptionsMap[$optionCode] = $valueCode;
            }
        }

        return [
            'sku_code' => $skuCode,
            'selected_options' => $selectedOptionsMap,
            'print_position' => $this->normalizedString(data_get($snapshot, 'print_position')),
            'print_method' => $this->normalizedString(data_get($snapshot, 'print_method')),
        ];
    }

    private function normalizedString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}
