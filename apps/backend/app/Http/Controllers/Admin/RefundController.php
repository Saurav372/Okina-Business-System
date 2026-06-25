<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\RefundResource;
use App\Models\Refund;
use Illuminate\Support\Facades\Gate;

class RefundController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Refund::class);

        $refunds = Refund::query()->with(['order', 'payment'])->paginate(20);

        return RefundResource::collection($refunds);
    }

    public function show(Refund $refund)
    {
        Gate::authorize('view', $refund);

        $refund->load(['order', 'payment']);

        return new RefundResource($refund);
    }
}
