<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundLedgerIndexRequest;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use Illuminate\Support\Facades\Gate;

class RefundController extends Controller
{
    public function index(RefundLedgerIndexRequest $request)
    {
        Gate::authorize('viewAny', Refund::class);

        $query = Refund::query()->with(['order', 'payment']);

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
        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (! empty($validated['refund_type'])) {
            $query->where('refund_type', $validated['refund_type']);
        }

        $totalQuery = clone $query;

        $perPage = $validated['per_page'] ?? 20;
        $refunds = $query->latest('id')->paginate($perPage);

        return RefundResource::collection($refunds)->additional([
            'meta' => [
                'total_amount_minor' => (int) $totalQuery->sum('amount_minor'),
            ],
        ]);
    }

    public function show(Refund $refund)
    {
        Gate::authorize('view', $refund);

        $refund->load(['order', 'payment']);

        return new RefundResource($refund);
    }
}
