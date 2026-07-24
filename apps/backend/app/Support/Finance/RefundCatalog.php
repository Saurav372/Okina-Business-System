<?php

namespace App\Support\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RefundCatalog
{
    /**
     * Query and paginate customer refunds using RefundFilters.
     */
    public function getPaginatedRefunds(RefundFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = RefundQueryBuilder::buildQuery($filters);

        $query->with([
            'order.customer',
            'payment',
            'requester:id,name',
            'approver:id,name',
            'processor:id,name',
        ]);

        return $query->paginate($perPage)->withQueryString();
    }
}
