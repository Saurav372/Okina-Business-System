<?php

namespace App\Support\Purchases;

use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderQueryBuilder
{
    /**
     * Build query for purchase orders using PurchaseOrderFilters.
     *
     * @return Builder<VendorOrder>
     */
    public static function buildQuery(PurchaseOrderFilters $filters): Builder
    {
        $query = VendorOrder::query();

        if ($filters->search !== null) {
            $term = $filters->search;

            $query->where(function (Builder $sub) use ($term) {
                $sub->where('public_id', 'LIKE', "%{$term}%")
                    ->orWhereHas('vendor', fn (Builder $vQ) => $vQ->where('name', 'LIKE', "%{$term}%")->orWhere('vendor_code', 'LIKE', "%{$term}%"))
                    ->orWhereHas('items', function (Builder $itemQ) use ($term) {
                        $itemQ->whereHas('productSku', function (Builder $skuQ) use ($term) {
                            $skuQ->where('sku_code', 'LIKE', "%{$term}%")
                                ->orWhere('barcode', 'LIKE', "%{$term}%");
                        });
                    });

                if (ctype_digit($term)) {
                    $sub->orWhere('id', (int) $term)
                        ->orWhere('vendor_id', (int) $term);
                }
            });
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status->value);
        }

        if ($filters->paymentStatus !== null) {
            $query->where('payment_status', $filters->paymentStatus->value);
        }

        if ($filters->vendorId !== null) {
            $query->where('vendor_id', $filters->vendorId);
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', $filters->dateTo);
        }

        switch ($filters->sortBy) {
            case 'total_amount_minor':
                $query->orderBy('total_amount_minor', $filters->sortOrder);
                break;
            case 'ordered_at':
                $query->orderBy('ordered_at', $filters->sortOrder);
                break;
            case 'id':
            default:
                $query->orderBy('id', $filters->sortOrder);
                break;
        }

        return $query;
    }
}
