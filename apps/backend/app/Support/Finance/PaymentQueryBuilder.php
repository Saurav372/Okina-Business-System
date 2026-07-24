<?php

namespace App\Support\Finance;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class PaymentQueryBuilder
{
    /**
     * Build query for customer payments using PaymentFilters.
     *
     * @return Builder<Payment>
     */
    public static function buildQuery(PaymentFilters $filters): Builder
    {
        $query = Payment::query();

        if ($filters->search !== null) {
            $term = $filters->search;

            $query->where(function (Builder $sub) use ($term) {
                $sub->where('receipt_number', 'LIKE', "%{$term}%")
                    ->orWhere('provider_payment_id', 'LIKE', "%{$term}%")
                    ->orWhere('provider_reference', 'LIKE', "%{$term}%")
                    ->orWhereHas('order', function (Builder $oQ) use ($term) {
                        $oQ->where('public_id', 'LIKE', "%{$term}%")
                            ->orWhereHas('customer', fn (Builder $cQ) => $cQ->where('name', 'LIKE', "%{$term}%")->orWhere('email', 'LIKE', "%{$term}%"));
                    });

                if (ctype_digit($term)) {
                    $sub->orWhere('id', (int) $term);
                }
            });
        }

        if ($filters->provider !== null) {
            $query->where('provider', $filters->provider);
        }

        if ($filters->method !== null) {
            $query->where('method', $filters->method);
        }

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        if ($filters->startDate !== null) {
            $query->where('created_at', '>=', $filters->startDate);
        }

        if ($filters->endDate !== null) {
            $query->where('created_at', '<=', $filters->endDate);
        }

        switch ($filters->sortBy) {
            case 'amount_minor':
                $query->orderBy('amount_minor', $filters->sortOrder);
                break;
            case 'id':
            default:
                $query->orderBy('id', $filters->sortOrder);
                break;
        }

        return $query;
    }
}
