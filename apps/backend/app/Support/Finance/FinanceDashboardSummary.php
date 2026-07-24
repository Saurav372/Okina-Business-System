<?php

namespace App\Support\Finance;

class FinanceDashboardSummary
{
    public int $grossCollectionsMinor = 0;

    public int $totalGatewayFeesMinor = 0;

    public int $refundVolumeMinor = 0;

    public int $netRevenueMinor = 0;

    public int $totalPaymentsCount = 0;

    public int $succeededPaymentsCount = 0;

    public function __construct(PaymentMetrics $paymentMetrics, RefundMetrics $refundMetrics)
    {
        $this->grossCollectionsMinor = $paymentMetrics->grossCollectionsMinor;
        $this->totalGatewayFeesMinor = $paymentMetrics->totalGatewayFeesMinor;
        $this->totalPaymentsCount = $paymentMetrics->totalCount;
        $this->succeededPaymentsCount = $paymentMetrics->succeededCount;

        $this->refundVolumeMinor = $refundMetrics->totalRefundedVolumeMinor;

        // Net Revenue = Gross Collections - Refund Volume - Gateway Fees
        $this->netRevenueMinor = $this->grossCollectionsMinor - $this->refundVolumeMinor - $this->totalGatewayFeesMinor;
    }
}
