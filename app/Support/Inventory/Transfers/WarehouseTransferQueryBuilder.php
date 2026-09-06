<?php

namespace App\Support\Inventory\Transfers;

use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Builder;

class WarehouseTransferQueryBuilder
{
    /**
     * Build query for warehouse transfers using WarehouseTransferFilters.
     *
     * @return Builder<WarehouseTransfer>
     */
    public static function buildQuery(WarehouseTransferFilters $filters): Builder
    {
        $query = WarehouseTransfer::query();

        if ($filters->search !== null) {
            $term = $filters->search;

            $query->where(function (Builder $sub) use ($term) {
                $sub->where('transfer_code', 'LIKE', "%{$term}%")
                    ->orWhereHas('productSku', function (Builder $skuQ) use ($term) {
                        $skuQ->where('sku_code', 'LIKE', "%{$term}%")
                            ->orWhere('barcode', 'LIKE', "%{$term}%")
                            ->orWhereHas('product', fn (Builder $pQ) => $pQ->where('name', 'LIKE', "%{$term}%"));
                    });

                if (ctype_digit($term)) {
                    $sub->orWhere('id', (int) $term);
                }
            });
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status->value);
        }

        if ($filters->sourceLocation !== null) {
            $query->where('source_location', $filters->sourceLocation->value);
        }

        if ($filters->destinationLocation !== null) {
            $query->where('destination_location', $filters->destinationLocation->value);
        }

        if ($filters->dateFrom !== null) {
            $query->where('created_at', '>=', $filters->dateFrom);
        }

        if ($filters->dateTo !== null) {
            $query->where('created_at', '<=', $filters->dateTo);
        }

        switch ($filters->sortBy) {
            case 'quantity':
                $query->orderBy('quantity', $filters->sortOrder);
                break;
            case 'id':
            default:
                $query->orderBy('id', $filters->sortOrder);
                break;
        }

        return $query;
    }
}
