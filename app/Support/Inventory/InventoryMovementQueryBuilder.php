<?php

namespace App\Support\Inventory;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementQueryBuilder
{
    /**
     * Build base query for active inventory movements using InventoryMovementFilters.
     *
     * @return Builder<InventoryMovement>
     */
    public static function buildQuery(InventoryMovementFilters $filters): Builder
    {
        $query = InventoryMovement::query();

        // 1. SKU ID Filter
        if ($filters->skuId !== null) {
            $query->where('product_sku_id', $filters->skuId);
        }

        // 2. Search (SKU code, barcode, product name, reference ID)
        if ($filters->search !== null) {
            $term = $filters->search;

            $query->where(function (Builder $sub) use ($term) {
                $sub->whereHas('productSku', function (Builder $skuQuery) use ($term) {
                    $skuQuery->where(function (Builder $sq) use ($term) {
                        $sq->where('sku_code', 'LIKE', "%{$term}%")
                            ->orWhere('barcode', 'LIKE', "%{$term}%")
                            ->orWhereHas('product', fn (Builder $pq) => $pq->where('name', 'LIKE', "%{$term}%"));
                    });
                });

                if (ctype_digit($term)) {
                    $id = (int) $term;
                    $sub->orWhere('order_id', $id)
                        ->orWhere('vendor_order_id', $id)
                        ->orWhere('reference_id', $id)
                        ->orWhere('id', $id);
                }
            });
        }

        // 3. Date Range Filter
        if ($filters->dateFrom !== null) {
            $query->where('occurred_at', '>=', $filters->dateFrom);
        }
        if ($filters->dateTo !== null) {
            $query->where('occurred_at', '<=', $filters->dateTo);
        }

        // 4. Enum Filters
        if ($filters->movementType !== null) {
            $query->where('movement_type', $filters->movementType->value);
        }

        if ($filters->direction !== null) {
            $query->where('direction', $filters->direction->value);
        }

        if ($filters->reasonCode !== null) {
            $query->where('reason_code', $filters->reasonCode->value);
        }

        if ($filters->createdByUserId !== null) {
            $query->where('created_by_user_id', $filters->createdByUserId);
        }

        // 5. Default Sorting (Newest movements first)
        switch ($filters->sortBy) {
            case 'quantity':
                $query->orderBy('quantity', $filters->sortOrder);
                break;
            case 'product_name':
                $query->join('product_skus', 'inventory_movements.product_sku_id', '=', 'product_skus.id')
                    ->join('products', 'product_skus.product_id', '=', 'products.id')
                    ->orderBy('products.name', $filters->sortOrder)
                    ->select('inventory_movements.*');
                break;
            case 'sku_code':
                $query->join('product_skus', 'inventory_movements.product_sku_id', '=', 'product_skus.id')
                    ->orderBy('product_skus.sku_code', $filters->sortOrder)
                    ->select('inventory_movements.*');
                break;
            case 'occurred_at':
            default:
                $query->orderBy('occurred_at', $filters->sortOrder)
                    ->orderBy('id', $filters->sortOrder);
                break;
        }

        return $query;
    }
}
