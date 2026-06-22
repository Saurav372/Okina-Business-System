<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SalesOrderCreateRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductSku;
use App\Services\SalesOrderService;
use Illuminate\Http\Request;
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

    public function skuSearch(Request $request)
    {
        $q = (string) $request->query('q', '');

        $results = ProductSku::query()
            ->with('product')
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where('sku_code', 'like', "%{$q}%")
                    ->orWhereHas('product', function ($b) use ($q) {
                        $b->where('name', 'like', "%{$q}%");
                    });
            })
            ->limit(50)
            ->get(['id', 'sku_code', 'product_id'])
            ->map(function (ProductSku $sku) {
                return [
                    'id' => $sku->id,
                    'sku_code' => $sku->sku_code,
                    'label' => $sku->sku_code.($sku->product ? ' - '.$sku->product->name : ''),
                ];
            });

        return response()->json($results->values());
    }
}
