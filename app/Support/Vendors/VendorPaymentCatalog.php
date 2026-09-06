<?php

namespace App\Support\Vendors;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VendorPaymentCatalog
{
    /**
     * Get paginated vendor payment records with filters applied.
     */
    public function getPaginatedPayments(VendorPaymentFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = VendorPaymentQueryBuilder::baseQuery();
        VendorPaymentQueryBuilder::applyFilters($query, $filters);

        return $query->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }
}
