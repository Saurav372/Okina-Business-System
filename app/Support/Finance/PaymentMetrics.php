<?php

namespace App\Support\Finance;

use App\Models\Payment;

class PaymentMetrics
{
    public int $totalCount = 0;

    public int $succeededCount = 0;

    public int $grossCollectionsMinor = 0;

    public int $totalGatewayFeesMinor = 0;

    public function __construct(PaymentFilters $filters)
    {
        $baseQuery = PaymentQueryBuilder::buildQuery($filters);

        $this->totalCount = (clone $baseQuery)->count();

        $succeededQuery = (clone $baseQuery)->where('status', Payment::STATUS_SUCCEEDED);

        $this->succeededCount = (clone $succeededQuery)->count();
        $this->grossCollectionsMinor = (int) (clone $succeededQuery)->sum('amount_minor');
        $this->totalGatewayFeesMinor = (int) (clone $succeededQuery)->sum('gateway_fee_minor');
    }
}
