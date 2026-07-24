<?php

namespace App\Support\Inventory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryMovementCatalog
{
    /**
     * Query and paginate inventory movements using InventoryMovementFilters.
     */
    public function getPaginatedMovements(InventoryMovementFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = InventoryMovementQueryBuilder::buildQuery($filters);

        $query->with([
            'productSku' => fn ($skuQ) => $skuQ->with(['product.category', 'product.coverMedia.file']),
            'user',
            'order',
        ]);

        return $query->paginate($perPage)->withQueryString();
    }
}
