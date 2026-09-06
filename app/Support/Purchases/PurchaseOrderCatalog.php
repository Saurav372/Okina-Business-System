<?php

namespace App\Support\Purchases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseOrderCatalog
{
    /**
     * Query and paginate purchase orders using PurchaseOrderFilters.
     */
    public function getPaginatedOrders(PurchaseOrderFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = PurchaseOrderQueryBuilder::buildQuery($filters);

        $query->with([
            'vendor:id,name,vendor_code',
            'creator:id,name',
            'items.productSku.product',
        ]);

        return $query->paginate($perPage)->withQueryString();
    }
}
