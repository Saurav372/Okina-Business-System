<?php

namespace App\Support\Inventory\Transfers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WarehouseTransferCatalog
{
    /**
     * Query and paginate warehouse transfers using WarehouseTransferFilters.
     */
    public function getPaginatedTransfers(WarehouseTransferFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = WarehouseTransferQueryBuilder::buildQuery($filters);

        $query->with([
            'productSku.product',
            'initiator:id,name',
            'completer:id,name',
        ]);

        return $query->paginate($perPage)->withQueryString();
    }
}
