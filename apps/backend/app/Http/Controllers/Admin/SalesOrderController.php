<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderCreateRequest;
use App\Models\Order;
use App\Services\SalesOrderService;
use Illuminate\Support\Facades\Gate;

class SalesOrderController extends Controller
{
    public function store(SalesOrderCreateRequest $request, SalesOrderService $service)
    {
        Gate::authorize('create', Order::class);

        $order = $service->create($request->validated(), $request->user());

        return response()->json([
            'public_id' => $order->public_id,
            'order' => $order->toArray(),
        ], 201);
    }
}
