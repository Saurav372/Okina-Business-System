<?php

namespace App\Support\Purchases;

use App\Enums\VendorOrderPaymentStatus;
use App\Enums\VendorOrderStatus;

class PurchaseOrderMetrics
{
    public int $totalOrdersCount = 0;

    public int $activePendingCount = 0;

    public int $totalPurchaseValueMinor = 0;

    public int $unpaidLiabilityMinor = 0;

    public function __construct(PurchaseOrderFilters $filters)
    {
        $baseQuery = PurchaseOrderQueryBuilder::buildQuery($filters);

        $this->totalOrdersCount = (clone $baseQuery)->count();

        // Active Pending POs: ORDERED or PARTIALLY_RECEIVED awaiting receipt
        $this->activePendingCount = (clone $baseQuery)
            ->whereIn('status', [VendorOrderStatus::ORDERED->value, VendorOrderStatus::PARTIALLY_RECEIVED->value])
            ->count();

        // Total Purchase Value: ORDERED, PARTIALLY_RECEIVED, RECEIVED (excludes DRAFT & CANCELLED)
        $this->totalPurchaseValueMinor = (int) (clone $baseQuery)
            ->whereIn('status', [
                VendorOrderStatus::ORDERED->value,
                VendorOrderStatus::PARTIALLY_RECEIVED->value,
                VendorOrderStatus::RECEIVED->value,
            ])
            ->sum('total_amount_minor');

        // Unpaid PO Liability: ORDERED/PARTIALLY_RECEIVED/RECEIVED with UNPAID or PARTIALLY_PAID
        $this->unpaidLiabilityMinor = (int) (clone $baseQuery)
            ->whereIn('status', [
                VendorOrderStatus::ORDERED->value,
                VendorOrderStatus::PARTIALLY_RECEIVED->value,
                VendorOrderStatus::RECEIVED->value,
            ])
            ->whereIn('payment_status', [
                VendorOrderPaymentStatus::UNPAID->value,
                VendorOrderPaymentStatus::PARTIALLY_PAID->value,
            ])
            ->sum('total_amount_minor');
    }
}
