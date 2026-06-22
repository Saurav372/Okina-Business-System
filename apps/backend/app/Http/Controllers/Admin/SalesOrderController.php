<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderCreateRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductSku;
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

    public function create()
    {
        Gate::authorize('create', Order::class);

        $customers = Customer::query()
            ->orderBy('display_name')
            ->limit(200)
            ->get(['id', 'display_name', 'name']);

        $skus = ProductSku::query()
            ->with('product')
            ->limit(500)
            ->get(['id', 'sku_code']);

        return view('admin.orders.create', [
            'customers' => $customers,
            'skus' => $skus,
        ]);
    }
}
