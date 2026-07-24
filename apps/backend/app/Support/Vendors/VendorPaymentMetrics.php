<?php

namespace App\Support\Vendors;

use App\Enums\VendorOrderStatus;
use App\Enums\VendorPaymentStatus;
use App\Models\VendorOrder;
use App\Models\VendorPayment;

class VendorPaymentMetrics
{
    public int $totalPaidMinor;

    public int $unpaidLiabilityMinor;

    public int $activeVendorsPaidCount;

    public int $paymentCount;

    public function __construct(?VendorPaymentFilters $filters = null)
    {
        $query = VendorPaymentQueryBuilder::baseQuery();

        if ($filters) {
            VendorPaymentQueryBuilder::applyFilters($query, $filters);
        }

        $this->paymentCount = (clone $query)->count();

        $this->totalPaidMinor = (int) (clone $query)
            ->where('status', VendorPaymentStatus::PAID->value)
            ->sum('amount_minor');

        // Total Outstanding Vendor Payables = (Sum of all non-cancelled PO totals) - (Sum of all paid vendor payments)
        $totalPoSum = (int) VendorOrder::query()
            ->where('status', '!=', VendorOrderStatus::CANCELLED->value)
            ->sum('total_amount_minor');

        $totalPaidGlobal = (int) VendorPayment::query()
            ->where('status', VendorPaymentStatus::PAID->value)
            ->sum('amount_minor');

        $this->unpaidLiabilityMinor = max(0, $totalPoSum - $totalPaidGlobal);

        $this->activeVendorsPaidCount = (int) VendorPayment::query()
            ->where('vendor_payments.status', VendorPaymentStatus::PAID->value)
            ->whereHas('vendorOrder', fn ($q) => $q->whereNotNull('vendor_id'))
            ->join('vendor_orders', 'vendor_payments.vendor_order_id', '=', 'vendor_orders.id')
            ->distinct('vendor_orders.vendor_id')
            ->count('vendor_orders.vendor_id');
    }
}
