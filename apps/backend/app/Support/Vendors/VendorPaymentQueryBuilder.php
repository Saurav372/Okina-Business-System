<?php

namespace App\Support\Vendors;

use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Builder;

class VendorPaymentQueryBuilder
{
    /**
     * Build base query for VendorPayment records with eager relationships.
     *
     * @return Builder<VendorPayment>
     */
    public static function baseQuery(): Builder
    {
        return VendorPayment::query()->with([
            'vendorOrder' => fn ($q) => $q->with('vendor'),
            'recordedBy',
        ]);
    }

    /**
     * Apply filter object criteria to query builder.
     *
     * @param  Builder<VendorPayment>  $query
     * @return Builder<VendorPayment>
     */
    public static function applyFilters(Builder $query, VendorPaymentFilters $filters): Builder
    {
        if (! empty($filters->search)) {
            $search = $filters->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('vendorOrder', function (Builder $poQuery) use ($search) {
                        $poQuery->where('public_id', 'like', "%{$search}%")
                            ->orWhereHas('vendor', fn (Builder $vQuery) => $vQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('vendor_code', 'like', "%{$search}%"));
                    });
            });
        }

        if (! empty($filters->status) && $filters->status !== 'all') {
            $query->where('status', $filters->status);
        }

        if (! empty($filters->paymentMethod) && $filters->paymentMethod !== 'all') {
            $query->where('payment_method', $filters->paymentMethod);
        }

        if ($filters->vendorId !== null) {
            $query->whereHas('vendorOrder', fn (Builder $q) => $q->where('vendor_id', $filters->vendorId));
        }

        if ($filters->dateFrom) {
            $query->whereDate('paid_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo) {
            $query->whereDate('paid_at', '<=', $filters->dateTo);
        }

        return $query;
    }
}
