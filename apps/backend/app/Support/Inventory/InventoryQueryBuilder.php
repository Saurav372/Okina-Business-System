<?php

namespace App\Support\Inventory;

use App\Models\InventoryItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class InventoryQueryBuilder
{
    /**
     * Build base query for active inventory items.
     *
     * @return Builder<InventoryItem>
     */
    public static function baseQuery(?string $locationId = null): Builder
    {
        $query = InventoryItem::query()
            ->whereHas('productSku', function (Builder $skuQuery) {
                $skuQuery->whereHas('product', function (Builder $productQuery) {
                    $productQuery->whereNull('deleted_at');
                });
            });

        if ($locationId !== null && $locationId !== '' && $locationId !== 'all') {
            if (Schema::hasColumn('inventory_items', 'location_id')) {
                $query->where(function (Builder $q) use ($locationId) {
                    $q->whereNull('location_id')
                        ->orWhere('location_id', $locationId);
                });
            }
        }

        return $query;
    }

    /**
     * Apply search criteria to inventory query.
     * Searches SKU code, barcode, product name, and exact numeric product/SKU IDs.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public static function applySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $mainQuery) use ($term) {
            $mainQuery->whereHas('productSku', function (Builder $skuQuery) use ($term) {
                $skuQuery->where(function (Builder $sq) use ($term) {
                    $sq->where('sku_code', 'LIKE', "%{$term}%")
                        ->orWhere('barcode', 'LIKE', "%{$term}%")
                        ->orWhereHas('product', fn (Builder $pq) => $pq->where('name', 'LIKE', "%{$term}%"));
                });
            });

            if (ctype_digit($term)) {
                $id = (int) $term;
                $mainQuery->orWhere('product_sku_id', $id)
                    ->orWhereHas('productSku', fn (Builder $sq) => $sq->where('product_id', $id));
            }
        });
    }

    /**
     * Apply status filter to inventory query based on InventoryStatusResolver precedence.
     *
     * @param  Builder<InventoryItem>  $query
     */
    public static function applyStatus(Builder $query, ?string $status): Builder
    {
        if ($status === null || $status === '' || $status === 'all') {
            return $query;
        }

        return match ($status) {
            'negative' => $query->where(fn (Builder $q) => $q->where('available_quantity', '<', 0)->orWhere('on_hand_quantity', '<', 0)),
            'out_of_stock' => $query->where('available_quantity', '<=', 0)->where('on_hand_quantity', '>=', 0),
            'low_stock' => $query->where('available_quantity', '>', 0)->whereRaw('available_quantity <= COALESCE(inventory_items.low_stock_threshold, (SELECT low_stock_threshold FROM product_skus WHERE product_skus.id = inventory_items.product_sku_id), 10)'),
            'in_stock' => $query->whereRaw('available_quantity > COALESCE(inventory_items.low_stock_threshold, (SELECT low_stock_threshold FROM product_skus WHERE product_skus.id = inventory_items.product_sku_id), 10)'),
            default => $query,
        };
    }
}
