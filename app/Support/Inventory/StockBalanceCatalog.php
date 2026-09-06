<?php

namespace App\Support\Inventory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StockBalanceCatalog
{
    /**
     * Query and paginate stock balance items with filters and eager loading.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPaginatedBalances(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;
        $status = $filters['status'] ?? null;
        $locationId = $filters['location'] ?? null;
        $sortBy = $filters['sort_by'] ?? 'available_quantity';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = InventoryQueryBuilder::baseQuery($locationId);

        InventoryQueryBuilder::applySearch($query, $search);
        InventoryQueryBuilder::applyStatus($query, $status);

        $query->with([
            'productSku' => function ($skuQuery) {
                $skuQuery->with([
                    'product' => fn ($pQuery) => $pQuery->with(['category', 'coverMedia.file']),
                ]);
            },
        ]);

        switch ($sortBy) {
            case 'product_name':
                $query->join('product_skus', 'inventory_items.product_sku_id', '=', 'product_skus.id')
                    ->join('products', 'product_skus.product_id', '=', 'products.id')
                    ->orderBy('products.name', $sortOrder)
                    ->select('inventory_items.*');
                break;
            case 'sku_code':
                $query->join('product_skus', 'inventory_items.product_sku_id', '=', 'product_skus.id')
                    ->orderBy('product_skus.sku_code', $sortOrder)
                    ->select('inventory_items.*');
                break;
            case 'on_hand_quantity':
                $query->orderBy('on_hand_quantity', $sortOrder);
                break;
            case 'reserved_quantity':
                $query->orderBy('reserved_quantity', $sortOrder);
                break;
            case 'threshold':
                $query->orderBy('low_stock_threshold', $sortOrder);
                break;
            case 'last_movement_at':
                $query->orderBy('last_movement_at', $sortOrder);
                break;
            case 'available_quantity':
            default:
                $query->orderBy('available_quantity', $sortOrder);
                break;
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
