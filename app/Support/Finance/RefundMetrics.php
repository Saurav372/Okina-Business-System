<?php

namespace App\Support\Finance;

use App\Models\Refund;

class RefundMetrics
{
    public int $totalCount = 0;

    public int $requestedCount = 0;

    public int $approvedCount = 0;

    public int $succeededCount = 0;

    public int $failedCount = 0;

    public int $totalRefundedVolumeMinor = 0;

    public function __construct(RefundFilters $filters)
    {
        $baseQuery = RefundQueryBuilder::buildQuery($filters);

        $this->totalCount = (clone $baseQuery)->count();

        $this->requestedCount = (clone $baseQuery)
            ->where('status', Refund::STATUS_REQUESTED)
            ->count();

        $this->approvedCount = (clone $baseQuery)
            ->where('status', Refund::STATUS_APPROVED)
            ->count();

        $this->succeededCount = (clone $baseQuery)
            ->where('status', Refund::STATUS_SUCCEEDED)
            ->count();

        $this->failedCount = (clone $baseQuery)
            ->where('status', Refund::STATUS_FAILED)
            ->count();

        $this->totalRefundedVolumeMinor = (int) (clone $baseQuery)
            ->where('status', Refund::STATUS_SUCCEEDED)
            ->sum('amount_minor');
    }
}
