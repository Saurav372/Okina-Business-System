<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentLedgerIndexRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(PaymentLedgerIndexRequest $request)
    {
        Gate::authorize('viewAny', Payment::class);

        $query = Payment::query()->with('order');

        $validated = $request->validated();

        if (! empty($validated['start_date'])) {
            $query->whereDate('created_at', '>=', $validated['start_date']);
        }
        if (! empty($validated['end_date'])) {
            $query->whereDate('created_at', '<=', $validated['end_date']);
        }
        if (! empty($validated['provider'])) {
            $query->where('provider', $validated['provider']);
        }
        if (! empty($validated['method'])) {
            $query->where('method', $validated['method']);
        }
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['payment_type'])) {
            $query->where('payment_type', $validated['payment_type']);
        }

        $totalQuery = clone $query;

        $perPage = $validated['per_page'] ?? 20;
        $payments = $query->latest('id')->paginate($perPage);

        $meta = [
            'total_amount_minor' => (int) $totalQuery->sum('amount_minor'),
        ];

        if ($request->user()?->hasPermissionTo('finance.view_cost')) {
            $meta['total_gateway_fee_minor'] = (int) $totalQuery->sum('gateway_fee_minor');
            $meta['total_net_amount_minor'] = (int) $totalQuery->sum('net_amount_minor');
        }

        return PaymentResource::collection($payments)->additional([
            'meta' => $meta,
        ]);
    }

    public function show(Payment $payment)
    {
        Gate::authorize('view', $payment);

        $payment->load('order');

        return new PaymentResource($payment);
    }
}
