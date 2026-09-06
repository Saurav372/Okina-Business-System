<?php

namespace App\Support\Vendors;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class VendorQueryBuilder
{
    /**
     * Build filtered query for vendors based on VendorFilters DTO.
     *
     * @return Builder<Vendor>
     */
    public static function buildQuery(VendorFilters $filters): Builder
    {
        $query = Vendor::query()->withCount('purchaseOrders');

        if ($filters->search) {
            $pattern = "%{$filters->search}%";

            $query->where(function (Builder $sub) use ($pattern) {
                $sub->where('name', 'like', $pattern)
                    ->orWhere('vendor_code', 'like', $pattern)
                    ->orWhere('contact_name', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern)
                    ->orWhere('phone', 'like', $pattern)
                    ->orWhere('gstin', 'like', $pattern)
                    ->orWhere('city', 'like', $pattern);
            });
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        return $query->orderBy($filters->sortBy, $filters->sortOrder)
            ->orderBy('id', $filters->sortOrder);
    }
}
