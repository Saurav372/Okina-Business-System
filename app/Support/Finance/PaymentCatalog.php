<?php

namespace App\Support\Finance;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentCatalog
{
    /**
     * Query and paginate customer payments using PaymentFilters.
     */
    public function getPaginatedPayments(PaymentFilters $filters, int $perPage = 25): LengthAwarePaginator
    {
        $query = PaymentQueryBuilder::buildQuery($filters);

        $query->with([
            'order.customer',
        ]);

        return $query->paginate($perPage)->withQueryString();
    }
}
